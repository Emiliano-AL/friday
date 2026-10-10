---
description: 'Task list template for feature implementation'
---

# Tasks: Backlog General — Captura Rápida de Pendientes

**Input**: Design documents from `/specs/018-backlog-quick-capture/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/backlog.md](contracts/backlog.md), [quickstart.md](quickstart.md)

**Tests**: INCLUIDOS. La Constitución (Principio III, NON-NEGOTIABLE) exige
test-first con Pest: tests escritos → fallan → implementación → verde. Las
tareas de tests de cada fase se ejecutan antes que su implementación.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Single project**: `src/`, `tests/` at repository root
- **Web app**: `backend/src/`, `frontend/src/`
- **Mobile**: `api/src/`, `ios/src/` or `android/src/`
- Paths shown below assume single project - adjust based on plan.md structure

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Punto de partida verde y limpio antes de tocar el dominio.

- [X] T001 Confirmar baseline: ejecutar `php artisan test --compact` y documentar cualquier fallo pre-existente (no introducido por esta feature) en `specs/018-backlog-quick-capture/research.md` bajo el título "Baseline"

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Esquema y reglas de dominio que bloquean TODAS las user stories:
columnas `author_id`/`code`/`points`, visibilidad, policy, validación y
generación del código legible FRN-###.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

### Tests (test-first: escribir y verificar que FALLAN antes de implementar)

- [X] T002 [P] Ajustar `tests/Feature/Tasks/TaskStandaloneTest.php` al nuevo contrato: crear tarea standalone sin `assignee_id` es válida (antes obligatorio); si se envía `assignee_id` sin proyecto sigue exigiendo auto-asignación; `author_id` queda = usuario creador; `code` generado `FRN-1`, `FRN-2`... secuencial por autor
- [X] T003 [P] Ajustar `tests/Feature/Tasks/TaskCrudTest.php`: `tasks.store` respeta `status: 'backlog'` explícito (ya no lo fuerza a `'todo'`); validación `points` rechaza `-1` y `100`, acepta `0` y `99`

### Implementation

- [X] T004 Crear migración `database/migrations/2026_10_08_000000_add_backlog_fields_to_tasks_table.php`: `author_id` FK→users nullable indexado (backfill `COALESCE(assignee_id, owner del proyecto)`), `code` string(16) nullable, `points` unsigned smallint nullable, unique compuesto `(author_id, code)`; ejecutar `php artisan migrate`
- [X] T005 [P] Actualizar `app/Models/Task.php`: fillable + casts (`points` → integer), relación `author()` BelongsTo, `scopeVisibleTo($user)` (autor O responsable O propietario/miembro del proyecto) y `scopeBacklog()` (`whereNull('sprint_id')` + `status != done`) según `data-model.md`
- [X] T006 [P] Actualizar `app/Policies/TaskPolicy.php`: para tareas con `project_id` null, `view`/`update`/`delete` pasan a `author_id === user || assignee_id === user` (hoy solo responsable); reglas con proyecto sin cambio
- [X] T007 [P] Actualizar `app/Http/Requests/SaveTaskRequest.php`: `assignee_id` opcional cuando `project_id` es null (si se envía, solo auto-asignación), añadir `points` con regla `sometimes|integer|min:0|max:99`; actualizar `app/Http/Requests/UpdateTaskRequest.php` con la misma regla `points`
- [X] T008 Implementar generación de código en `app/Observers/TaskObserver.php::created`: dentro de transacción, `code = sprintf('FRN-%d', max número existente del autor + 1)` con reintento ante violación del unique compuesto; actualizar `database/factories/TaskFactory.php` con `author_id` por defecto y estado `withPoints(?int)`

**Checkpoint**: `php artisan test --compact tests/Feature/Tasks` en verde; migración con `down()` correcto. A partir de aquí pueden empezar las user stories.

---

## Phase 3: User Story 1 - Captura rápida de un pendiente (Priority: P1) 🎯 MVP

**Goal**: Un campo + Enter guarda un pendiente con solo el título; aparece al
inicio de la lista con código FRN-### y persiste. Nada más se necesita para el
MVP.

**Independent Test**: Escenarios 1–3 de `quickstart.md`: capturar "Revisar
webhook de Stripe" con Enter → aparece con `FRN-N` bajo "Media"; Enter vacío →
error inline y nada se guarda; recargar → sigue listado.

### Tests for User Story 1 (test-first) ⚠️

- [X] T009 [P] [US1] Crear `tests/Feature/Backlog/QuickCaptureTest.php`: POST `tasks.store` con solo `title` + `status: 'backlog'` crea el pendiente con defaults del spec FR-006 (prioridad `medium`, tipo `other`, `project_id` null, `assignee_id` null, `author_id` = usuario, `code` = `FRN-1` y segundo capture `FRN-2`); título vacío/espacios → 422 sin guardar nada
- [X] T010 [P] [US1] Crear `tests/Feature/Backlog/BacklogPageTest.php` (base): GET `/backlog` autenticado responde 200 renderizando `Backlog/Index` con prop `tasks` que incluye el pendiente capturado; invitado es redirigido a login; prop `tasks` solo contiene tareas visibles (autor/responsable/miembro)

### Implementation for User Story 1

- [X] T011 [US1] Añadir ruta `GET /backlog` → `BacklogController@index` con nombre `backlog.index` en `routes/web.php` (middleware `auth`)
- [X] T012 [US1] Crear `app/Http/Controllers/BacklogController.php` con `index()`: query `Task::visibleTo($user)->backlog()` con eager loads (`project:id,title`, `sprint:id,name,status`, `assignee:id,name,avatar`), payload por tarea según `contracts/backlog.md` (id, code, title, description, type, priority, status, points, completedAt, createdAt, project, sprint, assignee, authorId, canUpdate, canDelete) y `projects` (opciones de asignación, misma forma que `TaskController@index`); renderiza `Backlog/Index`
- [X] T013 [P] [US1] Extender `resources/js/types/task.ts` (code, points, authorId) y crear `resources/js/types/backlog.ts` con `BacklogTask`, `BacklogPageProps` y `BacklogFilter` según `contracts/backlog.md`
- [X] T014 [US1] Crear `resources/js/pages/Backlog/Index.vue`: página con `<Head title="Backlog" />`, encabezado "Backlog general", composer y lista simple de pendientes ordenada por `createdAt` descendente reutilizando `resources/js/components/Tasks/Board/TaskRow.vue`
- [X] T015 [US1] Crear `resources/js/components/Backlog/QuickCapture.vue`: un único `<input>` con foco automático; Enter dispara POST a `tasks.store` (Wayfinder `store` de `@/routes/tasks`) con `{ title, status: 'backlog', priority?, project_id? }` desde chips en línea (prioridad, proyecto — FR-004); `form.processing` bloquea doble envío; `errors.title` inline; reset + foco tras guardar; texto preservado si el guardado falla (FR-015)
- [X] T016 [US1] Habilitar el item de navegación en `resources/js/Layouts/AuthenticatedLayout.vue` (línea ~59): `to: backlog().url`, `match: 'backlog'`; regenerar rutas Wayfinder (`npm run build` o `npm run dev`)

**Checkpoint**: US1 funcional de forma independiente — los 3 escenarios del quickstart pasan. MVP alcanzable aquí.

---

## Phase 4: User Story 2 - Bandeja organizada con resumen, secciones y filtros (Priority: P2)

**Goal**: Lista agrupada por prioridad (Alta y urgente / Media / Baja e ideas),
filtros Todas / Sin proyecto / Con proyecto con conteos exactos, encabezado con
totales y estados vacíos.

**Independent Test**: Escenarios 4–5 de `quickstart.md`: con pendientes de
distintas prioridades y con/sin proyecto, los conteos coinciden exactamente con
lo listado y el resumen es correcto.

### Tests for User Story 2 (test-first) ⚠️

- [X] T017 [P] [US2] Extender `tests/Feature/Backlog/BacklogPageTest.php`: prop `summary` = `{ total, totalPoints, withoutProject, withProject }` exacto; `tasks` excluye completadas (`status != done`) y asignadas a sprint, e incluye tareas con proyecto pero sin sprint (filtro "Con proyecto"); tarea standalone ajena NO aparece (FR-016)

### Implementation for User Story 2

- [X] T018 [US1→US2] Actualizar `app/Http/Controllers/BacklogController.php`: añadir `summary` al payload (`tasks` ya filtrados: `total`, `totalPoints`, `withoutProject`, `withProject`) según `contracts/backlog.md`
- [X] T019 [P] [US2] Crear `resources/js/components/Backlog/BacklogFilters.vue`: chips Todas / Sin proyecto / Con proyecto con conteos desde `summary` (FR-008, SC-003)
- [X] T020 [P] [US2] Crear `resources/js/components/Backlog/BacklogSectionList.vue`: secciones fijas "Alta y urgente" (`high`,`urgent`), "Media" (`medium`), "Baja e ideas" (`low`); dentro de cada sección orden `createdAt` descendente (FR-007); selector de orden (prioridad | recientes)
- [X] T021 [US2] Integrar filtros, secciones y estados vacíos en `resources/js/pages/Backlog/Index.vue`: bandeja vacía → invitación a capturar; filtro sin resultados → mensaje específico (FR-014)
- [X] T022 [US2] Encabezado con resumen "N pendientes · M pts" desde `summary` en `resources/js/pages/Backlog/Index.vue` (FR-009)

**Checkpoint**: US1 + US2 funcionan de forma independiente; escenarios 4–5 del quickstart pasan.

---

## Phase 5: User Story 3 - Triage: completar y asignar desde el panel de detalle (Priority: P3)

**Goal**: Click en un pendiente abre el formulario de tarea existente
(`TaskFormModal`) en modo edición para completar prioridad, puntos,
responsable y asignar a proyecto; borrado con confirmación que devuelve al
backlog.

**Independent Test**: Escenarios 6–7 y 9 de `quickstart.md`: asignar proyecto y
cambiar a prioridad alta → el pendiente se mueve de sección y de filtro; puntos
= 5 suma al encabezado; borrar con confirmación permanece en `/backlog`.

### Tests for User Story 3 (test-first) ⚠️

- [X] T023 [P] [US3] Crear `tests/Feature/Backlog/BacklogTriageTest.php`: el autor ve y edita sus pendientes sin proyecto; un tercero recibe 404/403 (FR-016); `tasks.update` con proyecto del que no es miembro → 422/404 y el pendiente conserva sus datos (FR-011); `points` 0–99 persisten; el autor puede borrar y un tercero no (FR-013)
- [X] T024 [P] [US3] Actualizar `tests/Feature/Tasks/TaskCrudTest.php`: `tasks.destroy` redirige de vuelta a la URL origen (fallback `tasks.index`) — hoy redirige siempre a `tasks.index`

### Implementation for User Story 3

- [X] T025 [US3] Ajustar `app/Http/Controllers/TaskController.php::destroy`: redirigir a `back()` con fallback a `route('tasks.index')` en lugar de siempre `tasks.index`
- [X] T026 [P] [US3] Actualizar `resources/js/components/Tasks/Board/TaskFormModal.vue`: añadir campo puntos (entero 0–99, opcional) y soportar `initialStatus: 'backlog'` al crear desde el backlog
- [X] T027 [US3] Integrar el triage en `resources/js/pages/Backlog/Index.vue`: click en fila abre `TaskFormModal` en modo edición con el payload `BacklogTask`; guardar → `tasks.update` con refresco de props (la fila refleja sección/filtro nuevos); acción eliminar con diálogo de confirmación → `tasks.destroy` (FR-013)

**Checkpoint**: US1–US3 funcionan; escenarios 6–7 y 9 del quickstart pasan.

---

## Phase 6: User Story 4 - Definir entrega: asignar un pendiente a un sprint (Priority: P4)

**Goal**: Un pendiente con proyecto puede asignarse a un sprint activo/próximo
(del mismo proyecto); la bandeja muestra el próximo sprint; al cerrar un
sprint, sus pendientes sin terminar vuelven al backlog conservando proyecto,
prioridad y puntos.

**Independent Test**: Escenarios 8, 10 y 12 de `quickstart.md`: vincular al
sprint próximo → desaparece del backlog y aparece en la planificación; cerrar
el sprint → reaparece conservando datos; el banner muestra el sprint más
próximo.

### Tests for User Story 4 (test-first) ⚠️

- [X] T028 [P] [US4] Crear `tests/Feature/Backlog/SprintRolloverTest.php`: al completar un sprint (`ProjectSprintController@complete`) sus tareas `status != done` quedan con `sprint_id = null` y conservan proyecto/prioridad/puntos (visibles de nuevo en `/backlog`); las `done` se quedan en el sprint
- [X] T029 [P] [US4] Extender `tests/Feature/Backlog/BacklogPageTest.php`: prop `nextSprint` = sprint activo o planificado con `start_date` más próximo entre los proyectos del usuario (`{ id, name, projectId, projectTitle, status, startsAt, endsAt }`), o `null` si no existe

### Implementation for User Story 4

- [X] T030 [US4] Implementar rollover en `app/Observers/SprintObserver.php`: al detectar transición a `SprintStatus::Completed`, batch update de las tareas del sprint con `status != done` → `sprint_id = null` (D8)
- [X] T031 [US4] Añadir `nextSprint` al payload de `app/Http/Controllers/BacklogController.php` según `contracts/backlog.md`
- [X] T032 [P] [US4] Crear `resources/js/components/Backlog/NextSprintBanner.vue`: muestra `nextSprint` (nombre, proyecto, fechas) y enlaza a la planificación; integrarlo en `resources/js/pages/Backlog/Index.vue`
- [X] T033 [US4] Verificar en `resources/js/components/Tasks/Board/TaskFormModal.vue` que el select de sprint solo lista sprints activos/planificados del proyecto asignado y que asignar sprint exige proyecto previo (US4 escenario 2); ajustar si el comportamiento actual difiere

**Checkpoint**: Las 4 user stories funcionan de forma independiente; escenarios 8, 10 y 12 pasan.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Calidad transversal antes de cerrar la capacidad.

- [X] T034 Ejecutar `vendor/bin/pint --dirty` y dejar el código formateado (quality gate de `AGENTS.md`)
- [X] T035 Ejecutar los 12 escenarios manuales de `quickstart.md` en navegador y la suite completa `php artisan test --compact` en verde
- [X] T036 [P] Revisar `mcp__laravel-boost__browser-logs` tras recorrer la página: sin errores de consola ni warnings de hidratación Inertia
- [X] T037 [P] Verificar accesibilidad básica del composer: foco visible, Enter funcional, targets ≥44px en la variante móvil del layout

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depende de T001; BLOQUEA todas las user stories (columnas, scopes, policy, requests, código FRN)
- **User Stories (Phase 3+)**: Dependen de la fase Foundational completa
    - Pueden avanzarse en paralelo si hay capacidad (frontend US2/US3 es mayormente independiente del backend US4)
    - Recomendado: secuencial en orden de prioridad (US1 → US2 → US3 → US4)
- **Polish (Phase 7)**: Depende de las user stories deseadas completas

### User Story Dependencies

- **User Story 1 (P1)**: Tras Foundational. Sin dependencias de otras stories. Es el MVP.
- **User Story 2 (P2)**: Tras Foundational. Toca los mismos archivos de US1 (`BacklogController.php`, `pages/Backlog/Index.vue`), así que conviene ejecutarla tras US1 aunque sus criterios de prueba son independientes.
- **User Story 3 (P3)**: Tras Foundational. Depende de US1 para la integración en la página, pero sus pruebas (policy, redirect, validación) son independientes.
- **User Story 4 (P4)**: Tras Foundational. El rollover (T030) es backend independiente; el banner y `nextSprint` tocan archivos de US1/US2.

### Within Each User Story

- Tests escritos primero y en ROJO antes de implementar (Constitución III)
- Backend antes de frontend cuando comparten contrato (ej. T018 antes de T019–T022)
- Componentes [P] antes de la integración final de la fase
- Checkpoint de la historia antes de pasar a la siguiente prioridad

### Parallel Opportunities

- T002 ∥ T003 ∥ T005 ∥ T006 ∥ T007 (archivos distintos; T004 debe completarse antes de ejecutar la suite)
- T009 ∥ T010 ∥ T013 (tests y tipos)
- T019 ∥ T020 (componentes de filtros y secciones)
- T023 ∥ T024 ∥ T026 (tests y modal)
- T028 ∥ T029 ∥ T032 (tests y banner)
- T036 ∥ T037 (polish)
- Historias distintas pueden trabajarse en paralelo por personas distintas una vez verde la fase Foundational

---

## Parallel Example: User Story 1

```bash
# Tests primero (deben fallar):
Task: "Crear tests/Feature/Backlog/QuickCaptureTest.php (T009)"
Task: "Crear tests/Feature/Backlog/BacklogPageTest.php base (T010)"
Task: "Crear resources/js/types/backlog.ts (T013)"

# Luego backend:
Task: "Ruta GET /backlog (T011) + BacklogController@index (T012)"

# Frontend en paralelo al backend una vez exista el contrato:
Task: "Crear pages/Backlog/Index.vue (T014)"
Task: "Crear components/Backlog/QuickCapture.vue (T015)"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1: Setup (T001)
2. Phase 2: Foundational (T002–T008) — CRITICAL, bloquea todo
3. Phase 3: US1 (T009–T016)
4. **STOP and VALIDATE**: escenarios 1–3 de `quickstart.md`
5. Ya es demo-able: captura en <5 s sin perder nada (SC-001, SC-002)

### Incremental Delivery

1. Setup + Foundational → base verde
2. US1 → captura rápida (MVP)
3. US2 → bandeja organizada (filtros/secciones/resumen)
4. US3 → triage completo (asignación, puntos, borrado)
5. US4 → entrega a sprint + rollover
6. Polish → quality gates (Pint, suite completa, navegador)

### Parallel Team Strategy

1. El equipo completa Setup + Foundational junto (1–2 sesiones)
2. Con Foundacional verde:
    - Developer A: US1 + US2 (página y componentes)
    - Developer B: US3 + US4 (backend policy/rollover + modal)
3. Integración en los checkpoints de cada historia

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Cada user story es independiente en criterios de prueba aunque comparta archivos (ej. la página del backlog crece por fases)
- Verificar que los tests fallen antes de implementar (rojo → verde)
- Ejecutar `vendor/bin/pint --dirty` tras cambios PHP y la suite completa antes de cada checkpoint
- Formato estricto en cada tarea: `- [ ] TNNN [P?] [Story?] descripción con ruta de archivo`
- La spec de referencia vive en `specs/018-backlog-quick-capture/spec.md`; los criterios de aceptación por historia en sus checkpoints

---

## Phase 8: Convergence

**Purpose**: Cerrar el único hueco detectado entre el spec y el estado actual del código tras la implementación.

- [X] T038 Apuntar el acceso global "Nueva Tarea" al campo de captura del backlog: cambiar la acción "Crear nueva tarea" del command palette en `resources/js/Layouts/AuthenticatedLayout.vue` (línea ~144) para que visite `backlogIndex.url()` en lugar de `tasks.index?new=1`, y el handler `@new-task` (línea ~240) para que navegue a `/backlog` en lugar de abrir el palette — el composer ya recibe foco automático al montar; añadir test de feature que visite `/backlog` y verifique que el campo de captura queda disponible (HTTP 200 + componente `Backlog/Index`) según US1/AC4 (partial)
