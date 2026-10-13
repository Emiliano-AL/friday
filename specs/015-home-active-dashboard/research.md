# Research: Panel de inicio con actividad del usuario

**Feature**: `specs/015-home-active-dashboard`
**Date**: 2026-10-03
**Input**: spec.md + hallazgos de exploración del codebase (backend/frontend)

## Contexto técnico confirmado

- Stack: PHP 8.4, Laravel 13+, Inertia v3, Vue 3.5 (`<script setup lang="ts">`), Tailwind CSS v4 (tokens CSS-first en `resources/css/app.css`, solo modo claro), Wayfinder (`@/routes/`), Pest, PostgreSQL (conexión `pgsql` activa), Vite.
- Ruta `/`: closure en `routes/web.php:11-13` que renderiza `AppHome` sin props. El middleware `HandleInertiaRequests` comparte props globales (`auth.user`, `flash`, `projects` para el sidebar) — ese contrato hay que preservarlo (`tests/Feature/Shell/ShellRenderingTest.php`).
- Convención de payloads: arrays camelCase construidos a mano en controladores (`status` + `statusLabel`, relaciones cargadas con `loadMissing`/`with`, `withCount`), sin API resources.
- Existen: `Project::progressPercentage()`, `Project::isMember()`, `activeSprint()` (HasOne `status=active` ordenado por `start_date`), `TaskStatus`/`TaskPriority`/`SprintStatus`/`ProjectStatus` enums, `ProjectSprintController::metrics()`/`sprintDetail()` (privados, patrón a replicar).
- **No existen**: `due_date` en tasks, `completed_at` en tasks/sprints, tabla de actividad/auditoría, eventos de dominio, librería de gráficos (el donut/burndown de la pantalla Stitch requiere SVG hecho a mano), helper de tiempo relativo, tests de frontend (QA = `npm run check`/vue-tsc + Pest).
- Zona horaria de la app: **UTC**; los usuarios se formatean fechas en frontend con `Intl` (`es-MX`).

## Decisiones

### D1 — Estructura de entrega: controlador invocable + prop condicional
- **Decisión**: Reemplazar el closure de `/` por `HomeController` invocable que renderiza la misma página `AppHome` con una prop `dashboard` (nullable). `AppHome.vue` decide: si `dashboard` existe → renderiza el panel de actividad; si es `null` → conserva `HomeCanvas` (estado vacío actual, sin tocar).
- **Rationale**: una sola ruta y una sola página, condición decidida en servidor con datos reales; el contrato del sidebar (`projects`) queda intacto. Coincide con FR-001/FR-002 y con el escenario 5 de P1.
- **Alternativas**: dos rutas/componentes separados (rechazada: rompería "misma ruta /" del spec); decisión de componente en cliente (rechazada: el gate depende de datos del servidor).

### D2 — Feed de actividad: nueva tabla `activities` + observers
- **Decisión**: Crear migración `activities` (`id`, `user_id` FK cascade, `project_id` FK cascade, `task_id` FK nullable set-null, `sprint_id` FK nullable set-null, `type` string, `created_at`/`updated_at`), enum `ActivityType` (TaskCreated, TaskCompleted, CommentCreated, SprintStarted, SprintCompleted) y `Activity` model. Se registra vía observers de Eloquent (`TaskObserver`, `CommentObserver`, `SprintObserver`) en `AppServiceProvider`. Sin paquetes nuevos.
- **Rationale**: FR-009 exige eventos reales (completar tarea, comentar, abrir/cerrar sprint). No hay nada que derivar: `updated_at` se corrompe con ediciones posteriores y los comments solo cubren una fracción. Es el mecanismo más simple dentro del monolito (Principio I/II) y además habilita D3/D4.
- **Alternativas**: derivar de `comments` + `updated_at` (rechazada: datos incompletos/incorrectos); paquete spatie/activitylog (rechazada: viola Principio II — dependencia nueva — y es más poder de lo que el MVP necesita).

### D3 — Timestamps de completado: `tasks.completed_at`
- **Decisión**: Añadir columna nullable `completed_at` timestamp a `tasks`, poblada por `TaskObserver` cuando `status` pasa a `Done` (y volviendo a `null` si se reabre). Misma migración añade índice compuesto para la consulta principal.
- **Rationale**: FR-008 (insight semanal: resueltas en la semana + tiempo medio de ciclo) requiere saber cuándo se resolvió cada tarea sin join con `activities` por tarea. Coste: una columna + lógica en el observer ya creado por D2.
- **Alternativas**: solo `activities` con join (rechazada: consultas más complejas para un dato que es propio de la tarea); `updated_at` como proxy (rechazada: incorrecto tras ediciones).

### D4 — Gráfico de avance del sprint (FR-006): línea ideal vs acumulado real
- **Decisión**: El backend devuelve `burndown: { points: [{date, ideal, done}] }` por día natural del sprint (ideal = línea recta 0→total entre start_date y end_date; `done` = acumulado de `activities` tipo `task_completed` de ese sprint por día). El frontend dibuja SVG a mano (línea ideal + línea real), sin librerías.
- **Rationale**: satisface "gráfico de avance del sprint" con datos 100% reales (FR-012) usando el mismo mecanismo de D2. Si no hay eventos aún, la línea real arranca en 0 — honesto, no simulado.
- **Alternativas**: snapshots diarios de remaining (rechazada: persistencia extra sin valor MVP); solo barra de progreso sin serie temporal (rechazada: la pantalla de referencia incluye el gráfico y FR-006 lo exige).

### D5 — Sin `due_date` en esta entrega
- **Decisión**: FR-005 dice "fecha límite o estado" — v1 muestra **estado** (badge con `statusLabel`). No se añade `due_date`.
- **Rationale**: añadir fecha límite implica migración + cambios en formularios de tarea, payloads y tests de task CRUD (specs 004/009/012), una expansión de alcance fuera de "misma ruta /". YAGNI (Principio V).
- **Alternativas**: añadir `due_date` opcional completo (rechazada: scope creep documentado para una entrega futura).

### D6 — "Mis tareas para hoy & prioritarias"
- **Decisión**: tasks con `assignee_id = usuario`, `status != Done`, ordenadas por peso de prioridad (Urgent > High > Medium > Low) y luego `updated_at` desc; máx. 6 en el panel. `total` = conteo de ese mismo conjunto; "urgentes/alta" = conteo de prioridades Urgent+High. Enlace "Ver todas mis tareas" → `tasks.index` (ya existe y usa el scope asignado+miembro).
- **Rationale**: sin `due_date`, "para hoy" se interpreta como "activas y prioritarias" (FR-005 lo permite: prioridad primero). El orden es determinista y testeable.
- **Alternativas**: incluir Done recientes (rechazada: el diseño las lista como pendientes con check vacío).

### D7 — Sprint activo del panel: el más reciente entre los proyectos del usuario
- **Decisión**: `activeSprint` (relación existente) de cada proyecto del usuario; se toma el de mayor `start_date`; uno solo en tarjeta/panel/botón, aunque haya varios.
- **Rationale**: el diseño muestra un sprint singular ("el" sprint activo, FR-003/006/010/011 en singular); los demás sprints activos siguen alcanzables desde Proyectos.
- **Alternativas**: selector de sprint activo en home (rechazada: complejidad UI no requerida por el spec).

### D8 — "Proyectos activos": proyectos del usuario con estado activo
- **Decisión**: proyectos donde el usuario es owner o miembro, `status = Active`, ordenados por `updated_at` desc, máx. 4. `nota de estado`: si tiene sprint activo → `"{nombreSprint} • {díasRestantes} días restantes"`; si no → `"{n} tareas abiertas"` (tareas no Done).
- **Rationale**: FR-007 exige "nota de estado reciente" sin especificarla; estos dos valores son reales, baratos y útiles. Los proyectos Completed/Archived quedan excluidos (edge case del spec).

### D9 — Feed "Actividad del equipo"
- **Decisión**: `activities` de los proyectos del usuario (owner o miembro), últimos 8 por `created_at` desc. Cada ítem: usuario (`id`, `name`, `avatar`), `type` + `typeLabel`, referencia a entidad (task `id`+`title`, o project/sprint `id`+`name`/`title`), `createdAt` (ISO). Tiempo relativo ("hace 20 min") calculado en frontend con `Intl.RelativeTimeFormat('es')` (helper nuevo, sin dependencias).
- **Rationale**: FR-009; el feed cubre "proyectos del usuario", no toda la organización — coherente con el resto de scopes de la app (`isMember` pattern). Sin eventos → estado vacío honesto (FR-009).
- **Alternativas**: feed global (rechazada: filtrar por membresía es el patrón de acceso existente, evita filtrar información).

### D10 — Insight semanal (FR-008)
- **Decisión**: `completed` = nº de `task_completed` del usuario desde el lunes de la semana actual (inicio de semana ISO); `avgCycleDays` = media de días entre `created_at` y `completed_at` de las tareas que el usuario completó esta semana. Si `completed = 0` → la tarjeta muestra estado vacío ("Aún sin tareas resueltas esta semana"), sin métricas falsas.
- **Rationale**: horizonte "semana" del diseño ("Esta semana"); métricas 100% reales. Media redondeada a 1 decimal.
- **Alternativas**: ventana de 7 días móvil (rechazada: "semana" calendario es lo que el diseño sugiere con el selector "Esta semana"); ciclo medio sobre todas las tareas históricas (rechazada: el insight es semanal).

### D11 — Gráficos y donut en SVG a mano
- **Decisión**: componentes Vue propios (línea para burndown, donut de progreso para la tarjeta de sprint, barras de progreso ya existentes como patrón) construidos con SVG/Tailwind. Sin librerías de charts.
- **Rationale**: Principio II — añadir Chart.js/D3 requeriría aprobación de dependencia; el volumen de gráficos (2) no la justifica. La app ya muestra progreso con divs estilados (`SprintMetricsPanel.vue`, `Projects/Show.vue`).

### D12 — Saludo por franja horaria: cliente, no servidor
- **Decisión**: el saludo ("Buenos días / Buenas tardes / Buenas noches") se calcula en frontend con la hora local del navegador (`new Date().getHours()`) + `firstName` del usuario (primer nombre desde `auth.user`). El servidor solo envía los datos del subtítulo contextual (prioritarias, sprint, coverage).
- **Rationale**: la app corre en UTC y no hay preferencia de zona horaria por usuario; la hora local real solo la conoce el cliente. Cumple "hora local del usuario" del spec sin columnas ni TZ guess.
- **Alternativas**: columna `timezone` en users (rechazada: dato nuevo sin otra necesidad); TZ fija es-MX en servidor (rechazada: frágil para usuarios fuera de MX).

### D13 — Rendimiento e índices
- **Decisión**: migración añade: índice `tasks(assignee_id, status)` (hoy solo existe `project_id`) para "mis tareas"; en `activities`: índices `(project_id, created_at)`, `(user_id, created_at)` y `(sprint_id, type, created_at)` para burndown/feed/insight. Todas las consultas del home usan `with()` eager loading y límites explícitos (6/4/8). Meta: < 2 s (SC-005) con holgura en Postgres.
- **Rationale**: las 3 consultas nuevas son por columnas sin índice; coste ínfimo frente a full-scan en crecimiento.

### D14 — Verificación
- **Decisión**: Pest feature tests (Principio III) en `tests/Feature/Home/`: payload con/sin actividad, orden y conteos de tareas, panel de sprint + burndown, feed (registro de activities vía observers), insight semanal, aislamiento de datos de otros usuarios, regresión del estado vacío, y verificación del contrato del sidebar intacto. QA frontend con `npm run check` (vue-tsc).
- **Rationale**: cobertura de cada FR en el orden P1→P3 del spec.

## NEEDS CLARIFICATION
Ninguno pendiente: todas las lagunas de datos se resolvieron con D2–D5 y quedan registradas como supuestos/alcance en spec.md §Assumptions.
