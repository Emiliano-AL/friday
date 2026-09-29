# Research: Flujo de Sprint en la UI (010)

**Fecha**: 2026-09-29 · **Fase**: 0 (Outline & Research)

Todas las incógnitas del Technical Context quedaron resueltas con inspección del
código existente (constitución fija, sin opciones abiertas). Decisiones:

## D1. SprintStatus como enum PHP

- **Decision**: `App\Enums\SprintStatus` con casos `Planned = 'planned'`, `Active = 'active'`, `Completed = 'completed'` (valores en inglés, igual que `ProjectStatus` y `TaskStatus`; etiquetas en español para la UI, como hacen los enums existentes).
- **Rationale**: Constitución IV (`sprint.status` como enum). El usuario confirmó ciclo de vida real (spec FR-007). La derivación por fechas (`Project::activeSprint()`) queda como sustituto insuficiente: no sabe si un sprint fue cerrado antes de tiempo ni permite "completar" nada.
- **Alternativas**: estado derivado por fechas (rechazada: no soporta transiciones ni solo lectura de cerrados); string suelto (prohibido por constitución IV).

## D2. Migración: `goal` + `status` en `sprints`

- **Decision**: migración que añade `goal` (text, nullable) y `status` (string/varchar, indexado) con backfill por fechas al momento de correrla: `end_date < hoy → completed`; `start_date <= hoy <= end_date → active`; `start_date > hoy → planned`. Sprints nuevos nacen `planned`.
- **Rationale**: preserva la semántica que el proyecto ya mostraba (activeSprint por fechas) para datos existentes; el backfill es una sola corrida en desarrollo.
- **Alternativas**: default `planned` para todos (rechazada: los sprints en curso aparecerían planificados, regresión visible); backfill manual (fricción innecesaria).

## D3. `Project::activeSprint()` pasa a por-status

- **Decision**: `hasOne(Sprint)->where('status', SprintStatus::Active)->orderByDesc('start_date')`.
- **Rationale**: una sola fuente de verdad para "el sprint activo"; el panel "Sprint activo" de `Projects/Show.vue` sigue funcionando sin cambios de semántica (mismo shape `{id, name, startDate, endDate}`; se añaden `status`, `goal` a `SprintSummary`).
- **Alternativas**: mantener derivación por fechas en paralelo al status (rechazada: dos verdades que pueden discrepar).

## D4. Detalle del sprint: ruta y payload

- **Decision**: `GET projects/{project}/sprints/{sprint}` → `projects.sprints.show` → `Inertia::render('Sprints/Show')`. Acceso: miembro del proyecto (mismo criterio que el resto del dominio); 404 indistinguible para no miembros. Payload: `sprint` (name, goal, status, statusLabel, startDate, endDate, iteration `{current,total}`, daysRemaining, percentElapsed, canWrite), `metrics` (total, done, coverage, breakdown por estado), `tasks` en el shape `BoardTask` ya usado en Mis Tareas, y `taskFormContext` (proyecto fijo + sprints del proyecto para el diálogo de tarea).
- **Rationale**: reuso total del contrato de tareas de la feature 009 (misma tarjeta/fila, mismo modal); métricas calculadas en servidor para garantizar datos reales (SC-003) y mantener el cliente simple.
- **Alternativas**: métricas calculadas en cliente (rechazada: duplica lógica y riesgo de "datos simulados"); shape propio de tarea (rechazada: duplicación).

## D5. Transiciones: endpoints dedicados

- **Decision**: `POST projects/{project}/sprints/{sprint}/start` (`planned → active`) y `POST …/complete` (`active → completed`), ambos con confirmación en cliente (complete sí; start directo con flash). Reglas: propietario + proyecto activo + sprint no completado (start exige `planned`; complete exige `active`); violación → respuesta con error claro (excepción de validación / 422 con mensaje) y nada persiste. Sprint `completed` queda en solo lectura: ni edición, ni borrado, ni transiciones (FR-007 + edge case de reactivación).
- **Rationale**: transiciones explícitas > `update` con `status` (impide saltos inválidos por construcción y documenta la máquina de estados en rutas); forward-only se valida en servidor, no solo en UI.
- **Alternativas**: transición vía `update` con campo status (rechazada: hay que re-validar manualmente cada salto); soft delete del completado (rechazada: el dominio pide conservarlo visible en solo lectura).

## D6. Creación con "comenzar inmediatamente"

- **Decision**: `StoreSprintRequest` acepta `goal` (nullable, string, max:1000) y `start_now` (boolean, por defecto false); el controlador crea el sprint con `status = start_now ? Active : Planned`.
- **Rationale**: una sola petición de creación (sin endpoint extra) y la regla queda en un punto.
- **Alternativas**: crear siempre planificado y obligar a pulsar "Activar" después (rechazada: el diseño de referencia incluye el toggle en el propio diálogo).

## D7. Sprint completado = solo lectura

- **Decision**: cuando `sprint.status === Completed`, el servidor rechaza update/destroy/transiciones con mensaje claro ("El sprint ya está completado y es de solo lectura") y la UI oculta esas acciones (chip visible en su lugar). Edición/borrado siguen permitidos en `planned`/`active` si el proyecto está activo y quien pide es propietario.
- **Rationale**: FR-007 ("un sprint completado queda en solo lectura") y el edge case de reactivación; coherente con la regla de proyectos archivados/completados.
- **Alternativas**: permitir editar fechas del completado (rechazada: contradice la spec).

## D8. Métricas: definiciones exactas

- **Decision**: con tareas del sprint (todas las estatus, excluyendo backlog del desglose cuando count=0 no se muestra el grupo… ver contrato): `total` = count(tareas del sprint); `done` = count(status=Done); `coverage` = round(done/total*100), total=0 → 0; `breakdown` = para cada uno de los 4 estados operativos (todo, in_progress, in_review, done): `{status, label, count, percent}`; si una tarea del sprint tuviera status backlog se cuenta en total y done/coverage de forma natural (done sigue siendo Done) — el desglose la omite si count=0. `daysRemaining` = días laborables (lun–vie) de hoy a `end_date`, mínimo 0. `percentElapsed` = (hoy − start)/(end − start) acotado 0–100; sprint de un solo día → 100 si hoy ≥ start, si no 0.
- **Rationale**: "laborables" del diseño; fines de semana no cuentan; sin festivos (YAGNI, se documenta).
- **Alternativas**: días naturales (rechazada: el diseño dice "días laborables"); festivos (rechazada: no hay catálogo, YAGNI).

## D9. Numeración de iteración

- **Decision**: `iteration = { current: posición ordinal del sprint entre los sprints del proyecto ordenados por start_date asc, total: count }`. Solo informativo.
- **Rationale**: el diseño muestra "Iteración 14 de 24"; es derivable, no persistible.
- **Alternativas**: columna persistida (rechazada: derivable y se desincronizaría al reordenar fechas).

## D10. Componentes y reuso

- **Decision**: nuevos `SprintFormModal`, `SprintHeader`, `SprintMetricsPanel`, `SprintTaskList`, `SprintComingSoon` (tarjeta "Próximamente" para burndown, daily, bitácora, carga por miembro) y página `Sprints/Show.vue`. `TaskFormModal` se extiende con `initialProject`/`initialSprint` (defaults = comportamiento actual). `Projects/Show.vue` sustituye su modal inline por `SprintFormModal` y convierte cada fila de sprint en enlace al detalle (botones Editar/Eliminar se mantienen según permisos).
- **Rationale**: convención del repo (componentes por dominio); reuso probado de feature 009.
- **Alternativas**: duplicar el modal de tareas para sprints (rechazada); dejar el modal inline en Show.vue (rechazada: el diálogo nuevo es US2 y necesita goal/start_now).

## D11. Inventario "Próximamente" (FR-009)

- **Decision**: sección lateral en el detalle con tarjetas deshabilitadas (sin foco, sin datos simulados): Story Points/Burn-up (velocidad, ritmo, capacidad), Gráfica de burndown, Notas del Daily, Bitácora del sprint, Carga por miembro, grupo "Bloqueadas" (estado bloqueado de tarea), clave legible de tarea, % de progreso por tarea, selección de backlog al crear, y el item global "Sprints" del sidebar (FR-010). Queda documentado también en `data-model.md` (propiedades futuras).
- **Rationale**: convención establecida en feature 009 (FR-008) — coherencia de producto.

## D12. Tests (test-first, Pest)

- **Decision**: cuatro archivos nuevos: `SprintLifecycleTest` (start/complete felices, forward-only, no propietario, proyecto no activo, completado=solo lectura), `SprintGoalTest` (goal opcional/max 1000, fechas fin≥inicio en store y update), `SprintDetailTest` (miembro ve payload con métricas reales vs tareas creadas; no miembro 404; breakdown y coverage exactos; iteration current/total; daysRemaining/percentElapsed acotados), `SprintStoreStartNowTest` (start_now=true→active, false/absence→planned). Regresión: tests existentes de spec 003 deben seguir verdes (activación/cierre cambia `activeSprint`, se revisan los que asuman derivación por fechas).
- **Rationale**: constitución III.
- **Alternativas**: tests solo del ciclo de vida (rechazada: métricas y permisos son FR testables).

## Estado

Sin NEEDS CLARIFICATION pendientes. Listo para Phase 1 (data-model, contracts, quickstart).
