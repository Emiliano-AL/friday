---
description: 'Task list for 005-task-board-views'
---

# Tasks: Vistas Backlog y Kanban de Tareas

**Input**: Design documents from `/specs/005-task-board-views/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/web-board-views.md, quickstart.md

**Tests**: INCLUIDOS — obligatorios por Principio III de la constitución (Test-First con Pest, NON-NEGOTIABLE). La lógica nueva es de presentación (cómputo cliente); el dominio que las vistas consumen (cambio de estado) ya está probado en 004 — los tests de esta feature fijan el contrato de payload (seam de regresión) y la cadena de transiciones del tablero sobre el endpoint existente. La interacción de arrastre se valida en navegador real.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: User story label (US1–US4, según spec.md)
- Include exact file paths in descriptions

## Path Conventions

Single project (Laravel monolith). Rutas reales según `plan.md` — `resources/js/pages/Projects/Show.vue`, `resources/js/Components/Tasks/`, `tests/Feature/Tasks/`. **Sin migraciones ni rutas nuevas** (research R-1).

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Baseline verde antes de tocar código (no hay dependencias nuevas)

- [x] T001 Run baseline verification: `composer install && npm install && composer ci:check` passes on the current tree before any change (features 001–004 deben estar verdes)

**Checkpoint**: Baseline verde confirmado

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Fijar el seam de regresión del payload que consumen ambas vistas (research R-8) — sin bloqueos de esquema ni rutas

- [x] T002 [P] Create `tests/Feature/Tasks/TaskBoardTest.php` (Pest; vía `php artisan make:test --pest Tasks/TaskBoardTest --no-interaction`) — payload contract portion: el prop `project.tasks` incluye `status`, `priority`, `sprint` (id+name) y `assignee` (id+name) para cada tarea, y `project.sprints` incluye `startDate` (aserciones Inertia sobre `Projects/Show`; los datos ya existen — el test pasa de inmediato y bloquea regresiones futuras del contrato)

**Checkpoint**: Contrato de payload fijado en verde

---

## Phase 3: User Story 1 - Vista Backlog ordenable para refinamiento (Priority: P1) 🎯 MVP

**Goal**: La sección Tareas ofrece la pestaña Backlog: lista estructurada (título, prioridad, sprint, estado, responsable), ordenable por prioridad asc/desc (desc por omisión) y por sprint (por `startDate`, aisladas al final), con filtro por sprint (US1, FR-001/002/003).

**Independent Test**: quickstart S1: orden por omisión prioridad descendente, conmutador ascendente, orden por sprint con aisladas al final, filtro por sprint concreto / sin sprint / todas.

### Implementation for User Story 1

- [x] T003 [P] [US1] Create `resources/js/Components/Tasks/BacklogView.vue`: props `tasks: TaskItem[]`, `sprints: { id, name, startDate }[]`; estado local `sortKey: 'priority' | 'sprint'` (por omisión `'priority'`), `sortDir: 'desc' | 'asc'` (solo para prioridad) y `sprintFilter: 'all' | 'none' | number`; computed `visibleTasks` que filtra y ordena — rank de prioridad `urgent=3 > high=2 > medium=1 > low=0`, prioridad asc/desc según `sortDir`, orden por sprint = lookup `startDate` desde `sprints` con aisladas al final; selectores de orden/filtro con `InputLabel`+`select` (estilo del formulario de tareas) y fila con título, badges `priorityLabel`/`statusLabel`, sprint (o "Sin sprint") y responsable (o "Sin responsable"); tipos de `Components/Tasks/types.ts`
- [x] T004 [US1] Update `resources/js/pages/Projects/Show.vue`: conmutador de pestañas sobre la sección Tareas — estado local `taskView: 'lista' | 'backlog' | 'kanban'` (por omisión `'lista'`), botones "Lista" / "Backlog" / "Kanban" (Kanban se añade como pestaña en US2; si `taskView === 'kanban'` y aún no existe el componente, mostrar solo Lista/Backlog), render condicional: contenido actual de la lista en `'lista'`, `<BacklogView :tasks="project.tasks" :sprints="project.sprints" />` en `'backlog'`
- [x] T005 [US1] Rebuild Wayfinder (`npm run build`), run `npm run types:check`, and verify US1 via quickstart S1 in the browser (orden por omisión, conmutadores, filtro)

**Checkpoint**: User Story 1 fully functional and testable independently — MVP

---

## Phase 4: User Story 2 - Vista Kanban con columnas por estado (Priority: P2)

**Goal**: La pestaña Kanban muestra cinco columnas (backlog, todo, in_progress, in_review, done) derivadas del enum `TaskStatus`, con tarjetas compactas ubicadas según el estado, contador por columna e indicador de vacío (US2, FR-004/FR-005).

**Independent Test**: quickstart S2: tarjetas en la columna correcta, contadores correctos, columna vacía visible.

### Implementation for User Story 2

- [x] T006 [P] [US2] Create `resources/js/Components/Tasks/TaskCard.vue`: props `task: TaskItem`, `draggable: boolean`; tarjeta compacta con título, badge `priorityLabel`, responsable (o "Sin responsable") y sprint (o "Sin sprint"); `draggable` enlazado a la prop y `data-task-id` con el id (base del arrastre de US3); sin handlers de drag todavía
- [x] T007 [US2] Create `resources/js/Components/Tasks/KanbanBoard.vue`: props `tasks: TaskItem[]`, `projectId: number`, `canWrite: boolean`; columnas derivadas de los cases de `TaskStatus` (`backlog`, `todo`, `in_progress`, `in_review`, `done`) con `label()` para el encabezado; tarjetas agrupadas por `status` desde `tasks` (el payload ya viene ordenado por recientemente actualizada); contador por columna e indicador "Sin tareas" en columnas vacías; render de `<TaskCard>` por tarea (arrastre deshabilitado hasta US3)
- [x] T008 [US2] Update `resources/js/pages/Projects/Show.vue`: añadir la pestaña "Kanban" al conmutador y render `<KanbanBoard :tasks="project.tasks" :project-id="project.id" :can-write="canWriteTasks" />` en `'kanban'`; rebuild Wayfinder (`npm run build`), run `npm run types:check`, and verify US2 via quickstart S2 in the browser

**Checkpoint**: User Stories 1 AND 2 both work independently

---

## Phase 5: User Story 3 - Mover tarjetas con drag-and-drop (Priority: P3)

**Goal**: Arrastrar una tarjeta a otra columna cambia el estado vía `projects.tasks.update` (payload completo + `status` destino); misma columna = sin petición; suelta fuera = sin cambio; el cambio se refleja en tablero, backlog y lista (US3, FR-006/FR-007).

**Independent Test**: quickstart S3: mover todo → in_progress (y cadena completa), contadores actualizados, persistencia visible en Backlog/Lista.

### Tests for User Story 3 ⚠️

> **NOTE**: los tests fijan el contrato del tablero sobre el endpoint ya probado en 004 (research R-8)

- [x] T009 [P] [US3] Extend `tests/Feature/Tasks/TaskBoardTest.php` — board transitions portion: la cadena completa `backlog → todo → in_progress → in_review → done` y regreso a `backlog` vía `PUT /projects/{project}/tasks/{task}` persiste cada estado (payload completo en cada paso); un `status` inválido como destino es rechazado con `assertSessionHasErrors('status')` y la tarea conserva su estado; con proyecto `archived()`/`completed()` el movimiento recibe `assertSessionHasErrors(['project' => 'El proyecto no admite cambios en su estado actual.'])` y nada cambia (los escenarios cubren endpoints existentes — pasan al implementarse contra el código actual)

### Implementation for User Story 3

- [x] T010 [US3] Update `resources/js/Components/Tasks/KanbanBoard.vue` + `resources/js/pages/Projects/Show.vue`: handlers nativos de arrastre — `dragstart` en `TaskCard` (si `draggable`, `dataTransfer.setData('text/task-id', id)` y `effectAllowed='move'`), `dragover` en columna (`preventDefault` + `dropEffect='move'` + resaltado visual de la columna activa), `dragleave` (quita resaltado), `drop` en columna (recupera el id, resuelve la tarea, ignora si no `canWrite` o si el `status` ya es el de la columna — sin petición, y emite `moved` con `{ task, status }`); en `Show.vue`, handler `moveTask({ task, status })` que ejecuta `router.put(updateTask.url([project.id, task.id]), { ...task completo, status: statusDestino }, { preserveScroll: true })` — `transform` para mapear `assignee`/`sprint` anidados a `assignee_id`/`sprint_id` y `''`/null según el contrato del update
- [x] T011 [US3] Rebuild Wayfinder (`npm run build`), run `npm run types:check`, run `php artisan test --compact --filter=TaskBoardTest` green, and verify US3 via quickstart S3 in the browser simulating the drag with constructed `DragEvent` + `DataTransfer` (dragstart en la tarjeta, dragover+drop en la columna destino), comprobando persistencia y contadores

**Checkpoint**: User Story 3 independently functional

---

## Phase 6: User Story 4 - Solo lectura fuera del estado activo (Priority: P4)

**Goal**: Con proyecto archivado/completado las vistas se ven y el Backlog ordena/filtra, pero el arrastre está deshabilitado y las escrituras forzadas se bloquean con el mensaje heredado (US4, FR-008/FR-009).

**Independent Test**: quickstart S4: vistas visibles y ordenables, tarjetas no arrastrables, petición forzada rechazada con `errors.project`.

### Implementation for User Story 4

- [x] T012 [US4] Verify en navegador (quickstart S4) sobre `resources/js/pages/Projects/Show.vue` + `resources/js/Components/Tasks/`: con proyecto archivado las pestañas Backlog/Kanban renderizan y ordenan/filtran, `draggable` es falso (`can-write` falso desde `canWriteTasks`), el banner `errors.project` aparece tras forzar el `PUT`; corregir cualquier brecha y re-verificar

**Checkpoint**: All four user stories independently functional

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Quality gates y validación completa

- [x] T013 [P] Run full quality gates: `vendor/bin/pint --dirty --format agent` si se tocó PHP, luego `composer ci:check` (vp check, pint --test, phpstan, full test suite) — all must pass
- [x] T014 Run quickstart.md validation S1–S6 end-to-end (manual S1–S4 en navegador + automated S5–S6) and confirm success criteria SC-001–SC-004 from spec.md (movimiento < 3 s, vistas < 2 s con 100 tareas, 100% de movimientos bloqueados fuera de activo, movimiento sin ayuda)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — empieza de inmediato
- **Foundational (Phase 2)**: Depends on Setup — fija el seam de payload (no bloquea código, bloquea regresiones)
- **User Stories (Phase 3–6)**: US1 primero (crea el conmutador de pestañas); US2–US4 comparten `Show.vue` y `KanbanBoard.vue` → secuencial
- **Polish (Phase 7)**: Depends on all four stories complete

### User Story Dependencies

- **US1 (P1)**: Setup/Foundational only — MVP (crea pestañas + Backlog)
- **US2 (P2)**: US1 (el conmutador de pestañas y el render condicional viven en `Show.vue`)
- **US3 (P3)**: US2 (el arrastre opera sobre las tarjetas y columnas de `KanbanBoard.vue`)
- **US4 (P4)**: US1–US3 (verificación de lo construido; el bloqueo de servidor ya existe)

### Within Each User Story

- Componentes antes de cablear en `Show.vue`; verificación en navegador al cierre de cada historia

### Parallel Opportunities

- T002 ∥ cualquier tarea (archivo nuevo)
- T003 (BacklogView) ∥ T006 (TaskCard) — archivos distintos (T006 puede adelantarse a US2)
- T009 (tests de transiciones) ∥ T010 (handlers de arrastre)

---

## Parallel Example: User Story 2

```bash
# Tras US1, en paralelo:
Task: "Create resources/js/Components/Tasks/TaskCard.vue"
Task: "Extend tests/Feature/Tasks/TaskBoardTest.php (board transitions portion)"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1 (Setup) + Phase 2 (Foundational)
2. Complete Phase 3 (US1) → validar con quickstart S1
3. **STOP and VALIDATE**: backlog ordenable y filtrable — ya es una herramienta de refinamiento usable (spec, US1 Independent Test)

### Incremental Delivery

1. Setup + Foundational → seam en verde
2. US1 → Backlog (MVP)
3. US2 → Kanban visual
4. US3 → tablero interactivo
5. US4 → solo lectura verificada
6. Polish → quality gates + validación completa

### Parallel Team Strategy

Con dos desarrolladores tras la Fase 2:

- Developer A: US1 → US2 (pestañas, BacklogView, KanbanBoard en `Show.vue`)
- Developer B: T002 + T009 (tests del contrato y las transiciones) contra el endpoint existente — sin conflictos de archivos

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to spec user story (US1–US4)
- Cada historia es independiente y testeable por sí sola (quickstart la respalda)
- La interacción de arrastre se valida en navegador con `DragEvent` + `DataTransfer` construidos por script (research R-9); no hay runner de tests JS en el proyecto
- Los directorios `resources/js/routes/` y `resources/js/actions/` son generados por Wayfinder: regenerar con `npm run build`, nunca editar a mano
- Commit after each task or logical group
