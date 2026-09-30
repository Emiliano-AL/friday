---
description: 'Task list for feature 010: Flujo de Sprint en la UI'
---

# Tasks: Flujo de Sprint en la UI (010)

**Input**: Design documents from `/specs/010-sprint-flow-ui/` (plan.md, research.md, data-model.md, contracts/sprint-flow-ui.md, quickstart.md)

**Prerequisites**: plan.md ✅ spec.md ✅ research.md ✅ data-model.md ✅ contracts/ ✅

**Tests**: Incluidos — la constitución III (Test-First con Pest, NON-NEGOTIABLE) y el quickstart §1 los exigen. Tests primero en rojo, luego implementación.

**Organization**: Por historia de usuario (US1 detalle / US2 modal / US3 ciclo de vida), cada una independiente y testeable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: paralelizable (archivos distintos, sin dependencias pendientes)
- **[Story]**: US1, US2, US3 (trazabilidad con spec.md)
- Rutas exactas en cada descripción

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Rama y preparación mínima (el proyecto ya existe y arranca)

- [x] T001 Crear/confirmar la rama de la feature (`git checkout -b 010-sprint-flow-ui` desde `main` actualizado; si se trabaja en `main` como las features previas, registrarlo en el commit final)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Dominio del sprint ampliado (estado + objetivo) que TODAS las historias usan

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T002 Crear `tests/Feature/Sprints/SprintFoundationTest.php` (RED, con `RefreshDatabase`): cubre los contratos de data-model.md — `goal` es "nullable; máx. 1000 caracteres"; migración con backfill por fechas (`end_date < hoy → completed`; `start_date <= hoy <= end_date → active`; `start_date > hoy → planned`); `Project::activeSprint()` devuelve el sprint con `status = active` (más reciente por `start_date`), no el derivado por fechas; `SprintSummary` en los payloads de `projects.index`/`projects.show` incluye `status`, `statusLabel` y `goal`; un sprint `completed` rechaza update y destroy con mensaje de solo lectura; update/destroy de sprints `planned`/`active` sigue funcionando (regresión spec 003)
- [x] T003 [P] Crear `app/Enums/SprintStatus.php` con casos `Planned = 'planned'`, `Active = 'active'`, `Completed = 'completed'` (coherente con `ProjectStatus`/`TaskStatus`), con método `label(): string` en español (Planificado/Activo/Completado)
- [x] T004 Crear migración con `php artisan make:migration --no-interaction` (`database/migrations/2026_09_28_000000_add_goal_and_status_to_sprints.php`): añade `goal` (text, nullable) y `status` (string, indexado) a `sprints`, con backfill por fechas según research.md D2 (`DB::table` update en el `up()`, sin modelo), y correr `php artisan migrate`
- [x] T005 Editar `app/Models/Sprint.php`: PHPDoc/fillable con `goal` (`string|null`) y `status` (`SprintStatus`), cast de `status` al enum, y helpers de transición `start(): void` / `complete(): void` que lanzan `InvalidOperationException` (o ValidationException) con mensaje claro si la transición no es válida (forward-only: planned→active→completed)
- [x] T006 Editar `app/Models/Project.php`: `activeSprint()` pasa a `hasOne(Sprint::class)->where('status', SprintStatus::Active)->orderByDesc('start_date')` (research.md D3); actualizar el docblock
- [x] T007 Editar `app/Http/Controllers/ProjectController.php`: extender el mapeo de `sprints` y `activeSprint` (index y show) con `status`, `statusLabel` y `goal` según contrato §4
- [x] T008 Editar `app/Policies/SprintPolicy.php`: denegar `update`/`delete` cuando el sprint está `completed` (mensaje "El sprint ya está completado y es de solo lectura."), conservando las reglas de propietario/proyecto activo; verificar el registro de la policy en `AppServiceProvider`/`AuthServiceProvider`

**Checkpoint**: `SprintFoundationTest` en verde; el dominio conoce goal+status; proyectos siguen verdes (regresión)

---

## Phase 3: User Story 1 - Detalle completo de un sprint (Priority: P1) 🎯 MVP

**Goal**: Página `Sprints/Show` con encabezado (estado, iteración, fechas, días restantes, % transcurrido, objetivo), métricas reales y lista de tareas filtrable/agrupable, accesible solo a miembros (spec US1)

**Independent Test**: Abrir el detalle de un sprint con tareas en varios estados y comprobar que cobertura/desglose/conteos/días coinciden con los datos reales; buscar/filtrar/agrupar actualiza la lista; "Añadir tarea" abre el diálogo con proyecto y sprint preseleccionados (quickstart §3)

### Tests for User Story 1 ⚠️

- [x] T009 Crear `tests/Feature/Sprints/SprintDetailTest.php` (RED): miembro recibe `sprint` (con `status`, `statusLabel`, `goal`, `iteration.{current,total}`, `daysRemaining` >= 0, `percentElapsed` entre 0 y 100, `canWrite` = propietario && proyecto activo && status !== 'completed'), `metrics` (coverage exacto `round(done/total*100)` con total=0 → 0; breakdown solo con estados de count > 0; `total`/`done` exactos) y `tasks` en shape `BoardTask`; no miembro o sprint de otro proyecto → 404; métricas cambian al crear tareas (done/todo/in_review)

### Implementation for User Story 1

- [x] T010 [P] [US1] Crear `resources/js/types/sprint.ts` con `SprintStatusValue`, `SprintDetail`, `SprintMetrics`, `SprintTaskContext` según contrato §2, y extender `SprintSummary` en `resources/js/types/project.ts` con `status`, `statusLabel`, `goal` (contrato §4)
- [x] T011 [US1] Añadir `projects.sprints.show` en `routes/web.php` y el método `show` en `app/Http/Controllers/ProjectSprintController.php`: abort 404 si no miembro del proyecto o sprint ajeno; `Inertia::render('Sprints/Show')` con `sprint` (métricas de fechas: días laborables lun–vie de hoy a `end_date` mínimo 0; `percentElapsed` acotado 0–100, sprint de un día → 100 si hoy >= inicio), `metrics` (cálculo sobre tareas del sprint), `tasks` en shape `BoardTask` (mismo mapeo que `TaskController::index`), `taskContext` (proyecto + sprints del proyecto), `canWrite`
- [x] T012 [P] [US1] Crear `resources/js/Components/Sprints/SprintHeader.vue`: migas Proyectos / proyecto / Sprints / sprint (enlaces Wayfinder reales), nombre, chip de estado (tono por status), "Iteración N de M", fechas, días laborables restantes + % transcurrido, objetivo (si existe); slots para acciones (US3 las llena)
- [x] T013 [P] [US1] Crear `resources/js/Components/Sprints/SprintMetricsPanel.vue`: cobertura (valor + "X de Y"), desglose por estado con contador y porcentaje, total; estado vacío comprensible cuando total = 0 (data-model: total=0 → coverage 0)
- [x] T014 [P] [US1] Crear `resources/js/Components/Sprints/SprintTaskList.vue`: buscador (placeholder + hint ⌘F, `defineExpose({focus})`), filtros Responsable/Tipo, agrupación conmutada Estado/Prioridad con contadores por grupo, estados vacíos (sin tareas invita a añadir; sin coincidencias con "limpiar"); reutiliza el patrón de fila de `TaskRow` (icono tipo, título, prioridad, estado, responsable, conteo de comentarios) — sin story points ni clave legible (FR-009)
- [x] T015 [P] [US1] Crear `resources/js/Components/Sprints/SprintComingSoon.vue`: tarjetas laterales deshabilitadas con chip "Próximamente" (sin foco, sin datos simulados) para Story Points/Burn-up/Velocidad, Burndown, Notas del Daily, Bitácora del Sprint, Carga por Miembro, grupo Bloqueadas, clave legible y % por tarea (FR-009)
- [x] T016 [US1] Crear `resources/js/pages/Sprints/Show.vue` (raíz única, ensambla T012–T015): integra `TaskFormModal` (modo crear con proyecto+sprint preseleccionados y modo edit al abrir tarea) y `TaskCommentsModal` en modo global, como en Mis Tareas; atajo ⌘F enfoca el buscador; sin `canWrite` no se muestran acciones de escritura
- [x] T017 [US1] Extender `resources/js/Components/Tasks/Board/TaskFormModal.vue` con props opcionales `initialProject`/`initialSprint` (defaults = comportamiento actual) que preseleccionan y bloquean el proyecto del contexto (sprint sigue pudiendo ser "Sin sprint"); y editar `resources/js/pages/Projects/Show.vue` para que cada fila de sprint de la pestaña Sprints enlace a `projects.sprints.show` (nombre como link, chip de estado, Editar/Eliminar solo con permiso y sprint no completado)

**Checkpoint**: US1 funcional de forma independiente — detalle navegable con métricas reales y CRUD de tareas vía diálogo (quickstart §3)

---

## Phase 4: User Story 2 - Diálogo de creación y edición de sprint (Priority: P2)

**Goal**: `SprintFormModal` con nombre, objetivo, fechas y "comenzar inmediatamente", reemplazando el modal inline de `Projects/Show.vue` (spec US2)

**Independent Test**: Crear un sprint con objetivo desde el diálogo (validando errores en línea) y verlo en la lista; editar precargado y ver los cambios reflejados; colaborador/proyecto no activo sin acciones (quickstart §2)

### Tests for User Story 2 ⚠️

- [x] T018 [P] [US2] Crear `tests/Feature/Sprints/SprintGoalTest.php` (RED) y `tests/Feature/Sprints/SprintStoreStartNowTest.php` (RED): `goal` acepta null y rechaza > 1000 caracteres; `start_now` boolean (ausente → false); fechas: `end_date` >= `start_date` en store y update con errores en sesión; sprint creado con `start_now=true` queda `status=active`, sin él `planned`; colaborador y proyecto archivado/completado siguen bloqueados

### Implementation for User Story 2

- [x] T019 [US2] Editar `app/Http/Requests/StoreSprintRequest.php` (reglas: `name` required string max:255; `goal` nullable string max:1000; fechas required date + `end_date` >= `start_date`; `start_now` boolean) y `UpdateSprintRequest.php` (`goal` nullable string max:1000; fechas `sometimes` con la misma regla); en `ProjectSprintController::store` crear con `status = $request->boolean('start_now') ? SprintStatus::Active : SprintStatus::Planned`
- [x] T020 [P] [US2] Crear `resources/js/Components/Sprints/SprintFormModal.vue`: nombre con autofocus, objetivo (textarea con hint de valor entregable), fecha inicio, fecha fin con validación en línea (fin ≥ inicio), y solo en modo crear el switch "Comenzar inmediatamente" con nota "Estado por defecto: Planificado"; footer Cancelar / Crear Sprint | Guardar cambios; ESC y click-fuera cierran; doble envío bloqueado; usa `useForm` hacia `projects.sprints.store`/`update` (Wayfinder)
- [x] T021 [US2] Integrar `SprintFormModal` en `resources/js/pages/Projects/Show.vue`: sustituir el modal inline de sprint (crear y editar precargado) manteniendo la confirmación de eliminación; el botón "Planificar un sprint" abre el nuevo diálogo; la lista muestra chip de estado por sprint y enlaza al detalle (completado sin Editar/Eliminar)

**Checkpoint**: US1 + US2 — detalle y alta/edición alineados al diseño de referencia

---

## Phase 5: User Story 3 - Ciclo de vida del sprint (Priority: P3)

**Goal**: Transiciones reales planned → active → completed con endpoints dedicados y acciones en el detalle; completado = solo lectura (spec US3)

**Independent Test**: Activar un planificado desde el detalle; completar un activo con confirmación; forzar transiciones inválidas y ver rechazo con mensaje (quickstart §4)

### Tests for User Story 3 ⚠️

- [x] T022 [P] [US3] Crear `tests/Feature/Sprints/SprintLifecycleTest.php` (RED): `start` feliz planned→active; `complete` feliz active→completed; rechazos con mensaje claro — complete sobre planned, start sobre active/completed, cualquier transición desde completed (reactivación), no propietario, proyecto archivado/completado; flash de éxito presente; tras completar, update/destroy/transiciones quedan bloqueadas

### Implementation for User Story 3

- [x] T023 [US3] Añadir rutas `projects.sprints.start` y `projects.sprints.complete` (POST) en `routes/web.php` y las acciones en `app/Http/Controllers/ProjectSprintController.php`: mismos guardas de propietario + proyecto activo + sprint del proyecto; usar los helpers `Sprint::start()`/`complete()` (T005); `back()` con flash ("Sprint activado." / "Sprint completado.")
- [x] T024 [US3] Implementar las acciones de ciclo de vida en `resources/js/Components/Sprints/SprintHeader.vue`: botón "Activar sprint" (solo `planned`), botón "Completar Sprint" (solo `active`, con diálogo de confirmación), visibilidad condicionada a `canWrite`; refresco del estado tras la petición (Inertia v3 gestiona el re-render con las props del servidor)
- [x] T025 [US3] Ajustar `resources/js/pages/Sprints/Show.vue` para el solo lectura del completado: ocultar "Añadir tarea" y las acciones de tarea que impliquen escritura cuando `status === 'completed'` (la lista queda navegable); verificar que `Projects/Show.vue` refleja el chip y las acciones coherentes con el estado

**Checkpoint**: Las tres historias operan de forma independiente; quickstart §4–§5 validado

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Calidad transversal y cierre

- [x] T026 [P] Pase responsive/accesibilidad: a 375px sin scroll horizontal de página (detalle en una columna, tablero/lista con scroll interno, diálogo a ancho completo); recorrido con teclado (⌘F, ESC, modal de sprint, confirmación de completar, menús); anillos de foco visibles; `SprintComingSoon` sin foco ni interacción
- [x] T027 [P] Validar `quickstart.md` (§0–§7) contra la implementación real y actualizarlo si algún paso difiere; revisar `browser-logs` y consola del navegador sin errores
- [x] T028 Quality gates y cierre: `vendor/bin/pint --dirty --format agent`, `npm run check:fix`, `npm run types:check`, `npm run build` sin errores; `php artisan test --compact` completo en verde (incluida la regresión de specs 002/003/009); commit final de la feature

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias
- **Foundational (Phase 2)**: bloquea todas las historias (enum + migración + policy son base)
- **User Stories (Phase 3–5)**: dependen de Foundational; en orden de prioridad P1 → P2 → P3
- **Polish (Phase 6)**: depende de US1–US3 completas

### User Story Dependencies

- **US1 (P1)**: tras Foundational; sin dependencias de otras historias — es el MVP
- **US2 (P2)**: tras Foundational; integra con US1 (enlaces al detalle en T017/T021) pero su diálogo es testeable solo
- **US3 (P3)**: tras Foundational; T024 edita `SprintHeader.vue` (creado en US1) — secuencial con US1

### Within Each User Story

- Tests (rojo) antes de implementación (verde)
- Tipos/contratos antes de controlador; componentes antes de la página que los ensambla
- Tareas que tocan el mismo archivo se ejecutan en orden (T012→T024; T016→T025; T020→T021)

### Parallel Opportunities

- T003, T004 (enum/migración) pueden ir en paralelo al redactar T002
- T010–T015 (tipos + 4 componentes) en paralelo entre sí
- T018 (tests US2) en paralelo con la implementación de US1
- T020 (modal) en paralelo con T023 (rutas/transiciones, backend)

---

## Parallel Example: User Story 1

```bash
# Test primero (solo):
Task: "Crear tests/Feature/Sprints/SprintDetailTest.php (RED)"

# En paralelo, tipos + componentes:
Task: "Crear resources/js/types/sprint.ts + extender SprintSummary"
Task: "Crear resources/js/Components/Sprints/SprintHeader.vue"
Task: "Crear resources/js/Components/Sprints/SprintMetricsPanel.vue"
Task: "Crear resources/js/Components/Sprints/SprintTaskList.vue"
Task: "Crear resources/js/Components/Sprints/SprintComingSoon.vue"

# Luego, secuencial: controlador (T011) → página (T016) → TaskFormModal/Show.vue (T017)
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1 + Phase 2 (T001–T008) — dominio ampliado verde
2. Phase 3 (T009–T017) — detalle del sprint
3. **STOP y VALIDA**: quickstart §3; demo si se desea

### Incremental Delivery

1. Setup + Foundational → base
2. US1 → detalle → validar (MVP)
3. US2 → diálogo crear/editar → validar (quickstart §2)
4. US3 → ciclo de vida → validar (§4–§5)
5. Polish → gates finales

### Parallel Team Strategy

- Desarrollador A: T002 + T004–T008 (dominio) y T009–T011 (payload)
- Desarrollador B: T010 + T012–T015 (tipos y componentes)
- Desarrollador C: T018 + T019–T021 (US2) en cuanto Foundational existe; T022–T025 (US3) tras US1

---

## Notes

- [P] tasks = archivos distintos, sin dependencias
- Reglas citadas verbatim de data-model.md para no dejarlas a criterio (goal "máx. 1000 caracteres", días laborables mínimo 0, percentElapsed 0–100, transiciones solo hacia adelante)
- "Próximamente" es siempre un estado deshabilitado real (sin navegación, sin foco, sin datos simulados)
- Verificar tests en rojo antes de cada implementación y en verde después
- `Projects/Show.vue`, `Mis Tareas` y el detalle de proyecto deben seguir verdes en todo momento (regresión)
- El item global "Sprints" del sidebar NO se habilita en esta feature (FR-010)

## Phase 7: Convergence

- [x] T029 Añade la sección deshabilitada con chip "Próximamente" para la selección/planificación de tareas del backlog en `resources/js/Components/Sprints/SprintFormModal.vue` (solo modo crear, sin foco ni datos simulados, igual que las tarjetas de `SprintComingSoon.vue`) per FR-009 (partial)
- [x] T030 Alinea `app/Http/Requests/UpdateSprintRequest.php` a reglas `sometimes` para `name`, `goal`, `start_date` y `end_date` según data-model.md (validación "Update: mismas reglas con `sometimes`"), manteniendo los mensajes y la regla `end_date` after_or_equal, y verifica que la edición completa desde `SprintFormModal` sigue en verde per T019 (partial)
