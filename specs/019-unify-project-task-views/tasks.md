---
description: 'Task list template for feature implementation'
---

# Tasks: Unificar vistas de tareas del proyecto con las vistas globales

**Input**: Design documents from `/specs/019-unify-project-task-views/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/project-task-views.md](contracts/project-task-views.md), [quickstart.md](quickstart.md)

**Tests**: INCLUIDOS. La Constitución (Principio III, test-first) exige tests escritos antes de la implementación donde hay comportamiento verificable server-side (payload extendido y patrón de cambio de estado). El resto de la verificación es visual/contratos (quickstart).

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

**Purpose**: Punto de partida verde. Importante: el árbol tiene trabajo paralelo sin commit (migración Tiptap, Tasks/Index reescrito, componentes legacy aún presentes) — este baseline fija el estado inicial de esta feature.

- [X] T001 Ejecutar `php artisan test --compact` y `npm run build`, documentar resultado como baseline en `specs/019-unify-project-task-views/research.md` (sección "Baseline"); cualquier fallo pre-existente (no introducido por esta feature) se anota y no se toca

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Extensión aditiva del payload de tareas de `Projects/Show` (6 campos nuevos) que necesitan las vistas unificadas, y fijación del patrón de cambio de estado unificado. Bloquea US1 y US2.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

### Tests (test-first: escribir y verificar que FALLAN antes de implementar)

- [X] T002 [P] Extender `tests/Feature/Projects/ProjectShowPayloadTest.php`: nueva prueba que fija los 6 campos aditivos por tarea — `code` (string|null), `points` (number|null), `createdAt` (ISO 8601), `completedAt` (ISO 8601|null), `authorId` (number|null), `sprint.status` (in: planned, active, completed); debe FALLAR antes de T004
- [X] T003 [P] Extender `tests/Feature/Tasks/TaskCrudTest.php`: nueva prueba "un miembro mueve una tarea de proyecto vía status-only PUT global" — `PUT route('tasks.update', $task)` con body solo `{status: 'in_progress'}` sobre una tarea cuyo `project_id` pertenece al usuario; assert 302/exitoso y `status` persistido; variante no-miembro → 403/404; si ya pasa por comportamiento existente (`SaveTaskRequest` sometimes), registrarlo como test de caracterización

### Implementation

- [X] T004 Extender `app/Http/Controllers/ProjectController.php` (`projectPayload()`): añadir al mapeo de cada tarea `code`, `points`, `createdAt` ($task->created_at->toIso8601String()), `completedAt` ($task->completed_at?->toIso8601String()), `authorId` ($task->author_id), y en `sprint` el campo `status` ($task->sprint?->status->value); sin cambiar campos existentes ni permisos
- [X] T005 [P] Extender `resources/js/types/project.ts`: `ProjectTask` gana `code: string | null`, `points: number | null`, `createdAt: string`, `completedAt: string | null`, `authorId: number | null` y `sprint: { id: number; name: string; status: 'planned' | 'active' | 'completed' } | null`

**Checkpoint**: `php artisan test --compact tests/Feature/Projects tests/Feature/Tasks` en verde (incluidos los dos tests nuevos); `vendor/bin/pint --dirty` limpio.

---

## Phase 3: User Story 1 - Vista Backlog del proyecto con el estilo del backlog global (Priority: P1) 🎯 MVP

**Goal**: La pestaña Backlog de `/projects/{id}` renderiza con `BacklogSectionList` + `BacklogTaskRow` (estilo `/backlog`): secciones por prioridad, orden por fecha, selector Prioridad/Recientes, filtro de sprint a nivel de sección y estado vacío adaptado.

**Independent Test**: Quickstart escenarios 1–4: comparación lado a lado con `/backlog` indistinguible (salvo chip de proyecto omitido), secciones/orden correctos, filtro de sprint funciona, estado vacío propio.

### Implementation for User Story 1

- [X] T006 [US1] En `resources/js/pages/Projects/Show.vue` sustituir el render de la pestaña backlog (`<BacklogView>`) por `<BacklogSectionList>` + `<BacklogTaskRow>` (imports desde `@/Components/Backlog/…`): conjunto base `project.tasks` filtrado a `sprint === null && status !== 'done'`; evento `select` (cuando `task.canUpdate`) → abrir el `TaskFormModal` de edición ya montado en la página; eliminar el import de `BacklogView`
- [X] T007 [US1] En `resources/js/pages/Projects/Show.vue` añadir el control de filtro de sprint a nivel de la sección Tareas (visible en vistas backlog y kanban, oculto en lista): opciones "Sin sprint" (default), una por sprint de `project.sprints`, y "Todos"; computar `filteredTasks` que ambas vistas consumen — `none` → solo tareas sin sprint, sprint concreto → solo ese sprint, `all` → todas; en backlog, además excluir `status === 'done'`
- [X] T008 [US1] En `resources/js/pages/Projects/Show.vue` añadir el estado vacío de la vista Backlog (mensaje adaptado al proyecto invitando a crear una tarea) cuando el conjunto filtrado es vacío, y el toggle de orden Prioridad/Recientes que propaga `sort` a `BacklogSectionList`

**Checkpoint**: US1 verificable de forma independiente (escenarios 1–4 del quickstart); `npm run build` compila.

---

## Phase 4: User Story 2 - Vista Kanban del proyecto con el estilo del tablero global (Priority: P2)

**Goal**: La pestaña Kanban de `/projects/{id}` renderiza con `TaskBoard` + `TaskBoardCard` (el tablero de `/tasks`): mismas columnas y tarjetas, cambio de estado por drag con `PUT /tasks/{id} {status}`, respeta filtro de sprint y permisos.

**Independent Test**: Quickstart escenarios 5–8: paridad visual con `/tasks` kanban, drag persiste con un único PUT status-only, solo lectura para no miembros/proyecto no activo, edición desde tarjeta abre el modal.

### Implementation for User Story 2

- [X] T009 [US2] En `resources/js/pages/Projects/Show.vue` sustituir el render de la pestaña kanban (`<KanbanBoard>`) por `<TaskBoard>` (import desde `@/Components/Tasks/Board/TaskBoard.vue`) con las tareas filtradas del conjunto de sección (D6: solo `status` en `todo|in_progress|in_review|done`), `:can-write="project.status === 'active'"`; eliminar el import de `KanbanBoard` y la función `moveTask` legacy
- [X] T010 [US2] En `resources/js/pages/Projects/Show.vue` cablear los eventos de `TaskBoard`: `moved` → `router.put(update.url(task.id), { status }, { preserveScroll: true })` (Wayfinder `update` de `@/routes/tasks`); `open` → `TaskFormModal` edición; `add` → `TaskFormModal` creación con `initialStatus` de la columna y `initialProject` del proyecto; verificar que tras guardar el modal las props se refrescan
- [X] T011 [US2] Ejecutar `npm run build` y validar escenarios 5–8 del quickstart en navegador (proyecto de prueba con tareas en varios estados y sprints; usuario miembro y no miembro)

**Checkpoint**: US1 + US2 funcionan de forma independiente; drag de tarjeta persiste tras recargar.

---

## Phase 5: User Story 3 - La vista Lista y los flujos existentes no se alteran (Priority: P3)

**Goal**: La vista Lista conserva su comportamiento; la eliminación de componentes legacy no deja código muerto ni rompe métricas; suite completa verde.

**Independent Test**: Quickstart escenarios 9–12: Lista idéntica, métricas intactas, creación desde header con proyecto preseleccionado, grep sin referencias a componentes eliminados.

### Implementation for User Story 3

- [X] T012 [US3] Verificar en `resources/js/pages/Projects/Show.vue` que la vista Lista (`<TaskListPanel>`) no recibe cambios de props ni eventos y que el filtro de sprint de sección está oculto en ella; ejecutar los escenarios 9–11 del quickstart y confirmar métricas/progreso/contadores sin cambios
- [X] T013 [US3] Eliminar `resources/js/components/Tasks/BacklogView.vue`, `resources/js/components/Tasks/KanbanBoard.vue` y `resources/js/components/Tasks/TaskCard.vue` (huérfanos verificados: solo los importaba `Projects/Show.vue`); confirmar con `grep -r "BacklogView\|KanbanBoard\|Tasks/TaskCard" resources/js` que no quedan referencias; ejecutar `npm run build`

**Checkpoint**: Las tres vistas funcionan; suite PHP completa en verde.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Calidad transversal antes de cerrar.

- [X] T014 Ejecutar `vendor/bin/pint --dirty` y dejar el código formateado
- [X] T015 Ejecutar la suite completa `php artisan test --compact`, `npm run build` y `npm run types:check` (si existe el script) — todo en verde
- [X] T016 [P] Recorrer los 12 escenarios manuales de `quickstart.md` en navegador y marcar los criterios de aceptación finales (SC-001…SC-005)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies
- **Foundational (Phase 2)**: Depende de T001; BLOQUEA US1 y US2 (payload extendido)
- **User Stories (Phase 3+)**: Dependen de la fase Foundamental; US2 conviene ejecutarla tras US1 (comparten el filtro de sprint de T007 y la página); US3 depende de US1+US2 por la eliminación de legacy
- **Polish (Phase 6)**: Depende de US1–US3

### User Story Dependencies

- **User Story 1 (P1)**: Tras Foundational. Sin dependencias de otras stories. Es el MVP.
- **User Story 2 (P2)**: Tras Foundational; recomendado tras US1 (misma página, comparte el filtro de sprint).
- **User Story 3 (P3)**: Tras US1+US2 (verificación de no regresión + eliminación de huérfanos).

### Within Each User Story

- Tests primero donde aplica (fase Foundational); luego backend (T004/T005) antes que frontend
- Las tareas de `Show.vue` dentro de una historia son secuenciales (mismo archivo)
- Checkpoint de la historia antes de pasar a la siguiente prioridad

### Parallel Opportunities

- T002 ∥ T003 ∥ T005 (tests y tipos, archivos distintos)
- T004 tras T002/T003 (necesita ver los tests fallar primero)
- T014 ∥ T016 (polish)
- Con Foundacional verde, un desarrollador puede avanzar US1 mientras otro prepara US2 (mismo archivo `Show.vue` → coordinación recomendada secuencial)

---

## Parallel Example: Foundational Phase

```bash
# Tests primero (deben fallar por campos ausentes):
Task: "Extender tests/Feature/Projects/ProjectShowPayloadTest.php con los 6 campos (T002)"
Task: "Extender tests/Feature/Tasks/TaskCrudTest.php con status-only PUT global (T003)"
Task: "Extender resources/js/types/project.ts (T005)"

# Luego backend:
Task: "Extender app/Http/Controllers/ProjectController.php projectPayload() (T004)"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1: Setup (T001)
2. Phase 2: Foundational (T002–T005)
3. Phase 3: US1 (T006–T008)
4. **STOP and VALIDATE**: quickstart escenarios 1–4 (la vista Backlog del proyecto ya es indistinguible de `/backlog`)

### Incremental Delivery

1. Setup + Foundational → payload listo
2. US1 → vista Backlog unificada (MVP visible)
3. US2 → vista Kanban unificada
4. US3 → regresión limpia y legacy eliminado
5. Polish → quality gates

### Parallel Team Strategy

1. Equipo completa Setup + Foundational
2. Con Foundacional verde: un developer en US1→US2 (mismo archivo, secuencial), otro preparando escenarios de validación del quickstart

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- El único cambio backend es aditivo (payload); la ruta de proyecto `projects.tasks.*` NO se modifica (sigue usándose para crear/borrar desde el modal)
- Los tests existentes `TaskBoardTest` (full-PUT a ruta de proyecto) y `ProjectShowPayloadTest` (aserciones a nivel campo) deben pasar SIN modificaciones — si alguno falla, es señal de desviación del plan
- Verificar que los tests fallen antes de implementar (rojo → verde)
- Ejecutar `vendor/bin/pint --dirty` tras cambios PHP y `npm run build` tras cambios Vue/TS
- Formato estricto en cada tarea: `- [ ] TNNN [P?] [Story?] descripción con ruta de archivo`

---

## Phase 7: Convergence

**Purpose**: Cerrar el único hueco detectado entre el spec y el estado actual del código tras la implementación.

- [X] T017 Ocultar el chip de proyecto en las filas de la vista Backlog del proyecto: añadir prop opcional `hideProjectChip?: boolean` (default `false`, sin efecto en `/backlog`) a `resources/js/components/Backlog/BacklogTaskRow.vue` para condicionar el render del chip de proyecto / "Sin Proyecto", pasarla desde `resources/js/pages/Projects/Show.vue` en el contexto de proyecto, y verificar `npm run build` + `npm run types:check` + comparación visual de que `/backlog` no cambia y la vista Backlog del proyecto ya no muestra el chip según FR-002 / US1-AC2 / SC-001 (partial)
