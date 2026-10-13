---
description: 'Task list template for feature implementation'
---

# Tasks: Tareas del Proyecto — Modal de Nueva Tarea y Listado Estilo Mis Tareas

**Input**: Design documents from `/specs/013-project-detail-tasks/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/, quickstart.md

**Tests**: No se generan tests nuevos — cambio de UI + forma de payload (research D9; precedente D5 de spec 012). Excepción: T003 actualiza aserciones de payload existentes que D3 invalida (regla del proyecto: no eliminar tests). Regresión: `npm run types:check`, `npm run build` y suite Pest completa (fase de pulido).

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: Which user story this task belongs to (US1, US2)
- Include exact file paths in descriptions

## Path Conventions

- Proyecto único tipo web app monolito: frontend en `resources/js/`; backend en `app/`; tests backend en `tests/`
- Contratos: [contracts/013-project-detail-tasks.md](contracts/013-project-detail-tasks.md)
- Guía de validación: [quickstart.md](quickstart.md)

## Phase 1: Setup (Baseline)

**Purpose**: Punto de partida verificado y inventario del impacto del cambio de payload (D3) sobre los tests existentes

- [X] T001 Ejecutar `npm run types:check`, `npm run build` y `php artisan test --compact` para fijar el baseline en verde, anotar el resultado en [quickstart.md](quickstart.md) §0 y verificar que los tests `tests/Feature/Tasks/TaskAccessTest.php` (línea ~119), `tests/Feature/Tasks/TaskCrudTest.php` (línea ~53) y `tests/Feature/Tasks/TaskCommentsTest.php` (líneas ~30-33 y ~53-55) son los únicos que afirman el shape embebido `project.tasks.N.comments` que D3 va a cambiar (sin archivos de código a modificar; si algo falla, detenerse y resolver antes de continuar)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Alineación del payload de tareas del proyecto al shape `BoardTask` (D3) — MUST completarse antes de cualquier historia, porque los tipos del frontend dependen de ella

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T002 Cambiar el mapeo de `'tasks'` en `app/Http/Controllers/ProjectController.php` (`projectPayload`) para devolver el shape `BoardTask` según [data-model.md](data-model.md): añadir `project: {id, title}` (no-nulo), `commentsCount` vía `withCount('comments')` en el `loadMissing`, `canUpdate` y `canDelete` con las mismas reglas que `TaskController::taskPayload` (miembro+activo / owner+activo), y `assignee` con `avatar`; eliminar `'tasks.comments.author'` del `loadMissing` y el mapeo embebido de `comments`; y actualizar `resources/js/types/project.ts` para que `ProjectDetail.tasks` sea `BoardTask[]` (import desde `@/types/task`)
- [X] T003 Actualizar las aserciones de payload rompidas por T002 (no eliminar tests): en `tests/Feature/Tasks/TaskAccessTest.php` cambiar `has('project.tasks.0.comments', 1)` por `where('project.tasks.0.commentsCount', 1)`; en `tests/Feature/Tasks/TaskCrudTest.php` cambiar `has('project.tasks.0.comments', 0)` por `where('project.tasks.0.commentsCount', 0)`; en `tests/Feature/Tasks/TaskCommentsTest.php` sustituir las aserciones de `project.tasks.0.comments.*` por `where('project.tasks.0.commentsCount', N)` (líneas ~30-33 y ~53-55); ejecutar `php artisan test --compact tests/Feature/Tasks tests/Feature/Projects` y confirmar verde
- [X] T004 [P] Retipar las props `tasks` de `resources/js/Components/Tasks/BacklogView.vue` y `resources/js/Components/Tasks/KanbanBoard.vue` de `TaskItem[]` a `BoardTask[]` (import desde `@/types/task`; ambos componentes solo usan campos que existen en ambos shapes, sin cambios de plantilla)

**Checkpoint**: Foundation ready — el detalle del proyecto entrega `project.tasks` en forma `BoardTask`; tests de Tasks/Projects en verde. Nota: hasta que US2 reemplace el `TaskList`/`TaskModal` legacy, `vue-tsc` puede mostrar fricción de tipos en esos dos componentes y el modal legacy mostrará comentarios vacíos; se resuelve en T009/T010.

---

## Phase 3: User Story 1 - Crear tarea desde el detalle del proyecto (Priority: P1) 🎯 MVP

**Goal**: El botón "Nueva Tarea" del encabezado abre el `TaskFormModal` con el proyecto preseleccionado y fijo; al guardar, la tarea se crea en el proyecto y es visible sin recarga (spec FR-001–FR-004, FR-009)

**Independent Test**: [quickstart.md](quickstart.md) §1 — abrir el detalle, pulsar "Nueva Tarea", verificar modal con proyecto fijo y sprints del proyecto, crear y ver la tarea en el listado sin recarga manual

### Implementation for User Story 1

- [X] T005 [P] [US1] Añadir el prop opcional `destroyUrl?: string` a `resources/js/Components/Tasks/Board/TaskFormModal.vue` (con `withDefaults` a `undefined`) y usarlo en `removeTask`: `form.delete(props.destroyUrl ?? destroy.url(props.task.id), …)` — contrato [contracts/013-project-detail-tasks.md](contracts/013-project-detail-tasks.md) §1; el comportamiento de `/tasks` queda idéntico al no pasarse la prop
- [X] T006 [P] [US1] En `resources/js/pages/Projects/Show.vue`: añadir estado `taskModalOpen`/`taskModalMode`/`editingBoardTask` y `openTaskCreate()`; el `projectOption` computado (`TaskProjectOption` con miembros owner+members `{id,name,avatar}` y sprints `{id,name}` del proyecto) y `currentUser` desde `usePage()`; cambiar el botón "Nueva Tarea" del encabezado (líneas ~378-385) para que llame a `openTaskCreate()` con `v-if="canWriteTasks"` y eliminar `goToSprintsTab`; montar `<TaskFormModal>` con `:projects="[projectOption]"`, `:initial-project="{ id: project.id, title: project.title }"`, modo create y `@close` (props de edición/`destroy-url` se cablean en T009)

**Checkpoint**: User Story 1 funcional de forma independiente — el flujo de creación completo se valida con quickstart §1 (MVP entregable)

---

## Phase 4: User Story 2 - Listado de tareas del proyecto estilo Mis Tareas (Priority: P2)

**Goal**: La sección "Tareas" (vista Lista) renderiza la experiencia de `/tasks` restringida al proyecto, sin formulario inline; acciones de fila y comentarios homologadas (spec FR-005–FR-008)

**Independent Test**: [quickstart.md](quickstart.md) §2 y §3 — la vista Lista se ve y se comporta como `/tasks` solo con tareas del proyecto, sin formulario inline, y las acciones de fila funcionan en contexto

### Implementation for User Story 2

- [X] T007 [P] [US2] Crear `resources/js/Components/Tasks/Board/TaskListPanel.vue` según el contrato §2: extraer de `resources/js/pages/Tasks/Index.vue` la lógica de lista (búsqueda por título, tabs, filtro de responsable, grupos "Alta Prioridad & Urgentes"/"En Curso"/"Sin Proyecto Asignado"/"Próximas & Backlog"/"Completadas", render `TaskGroupSection` + `TaskRow` con `can-write = task.canUpdate`, línea de resumen) con props `tasks`, `currentUser`, `showProjectFilter` y `showStandaloneTab` (ambas default `true`) y eventos `create`/`edit`/`status`/`comment`/`remove`
- [X] T008 [P] [US2] Refactorizar `resources/js/pages/Tasks/Index.vue` para usar `TaskListPanel` con los defaults (render y comportamiento píxel-a-píxel idénticos), conservando en la página: el atajo de teclado `C`, el parámetro `?new=1`, la propiedad `view` lista/kanban, el `TaskBoard` kanban y la propiedad de los modales (`TaskFormModal`, `TaskCommentsModal` con `use-global` + `endpoints`)
- [X] T009 [US2] En `resources/js/pages/Projects/Show.vue`: sustituir el `TaskList` y el formulario inline de la vista "Lista" (líneas ~895-1040) por `<TaskListPanel :tasks="project.tasks" :current-user="currentUser" :show-project-filter="false" :show-standalone-tab="false">` con sus eventos (`status` → `router.put(update.url(task.id), { status }, { preserveScroll: true })` de `@/routes/tasks`; `remove`/`edit`/`open` → abrir `TaskFormModal` en modo edición; `comment` → `commentingTask`), eliminando `taskForm`, `submitTask` y el import de `storeTask`; cablear en el `TaskFormModal` el modo edición (`:task`, `:destroy-url="destroyTask.url([project.id, task.id])"` en edición) y cambiar `TaskCommentsModal` al patrón global de `/tasks` (`use-global` + `endpoints` con `taskShow`/`storeTaskComment` de `@/routes/tasks` y `@/routes/tasks/comments`), con `commentingTask: BoardTask | null` y eliminando `commentingTaskFresh` y el import de `TaskItem`
- [X] T010 [US2] Eliminar `resources/js/Components/Tasks/TaskList.vue` y `resources/js/Components/Tasks/TaskModal.vue` (quedan sin usos tras T009, decisión D7) y verificar con grep que ningún archivo los sigue importando

**Checkpoint**: User Stories 1 y 2 funcionando de forma independiente (quickstart §2, §3)

---

## Phase 5: Polish & Cross-Cutting Concerns

**Purpose**: Gates de calidad y validación de conjunto tras integrar las dos historias

- [X] T011 [P] Ejecutar `npm run types:check` y `npm run build` en verde; verificar con grep que no quedan imports de `TaskList`/`TaskModal` ni del `storeTask` anidado en `resources/js/pages/Projects/Show.vue`
- [X] T012 [P] Ejecutar `php artisan test --compact` (suite completa) y confirmar toda la regresión en verde, incluidos los tests actualizados en T003
- [X] T013 Ejecutar `vendor/bin/pint --dirty --format agent` (hubo cambios PHP en `app/Http/Controllers/ProjectController.php`) y revisar que el diff de `projectPayload` sigue las reglas de estilo del proyecto
- [X] T014 Recorrer la validación manual completa de [quickstart.md](quickstart.md) §1–§5 en navegador (modal de creación, listado estilo `/tasks` sin formulario inline, acciones de fila, regresión de `/tasks` y de las demás tabs del proyecto) y resolver los ajustes que aparezcan

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Sin dependencias — T001 valida el punto de partida
- **Foundational (Phase 2)**: T002 bloquea a T003 (tests) y T004 (retipado consume el nuevo shape); las tres bloquean a las historias — sin `BoardTask` en `ProjectDetail.tasks` los tipos del frontend no cierran
- **User Stories (Phase 3-4)**: Dependen de Phase 2; T005 ∥ T006 (archivos distintos, sin dependencia mutua — el create flow no usa `destroyUrl`); T007 bloquea a T008 y T009; T008 ∥ T009 (archivos distintos); T010 depende de T009
- **Polish (Phase 5)**: Depende de T005–T010 completos

### User Story Dependencies

- **User Story 1 (P1)**: T005 → T006 (MVP; creación desde el detalle del proyecto)
- **User Story 2 (P2)**: T007 → (T008 ∥ T009) → T010 (listado reutilizable + homologación de acciones y comentarios); integra lo construido en US1 (el modal ya montado) pero es validable de forma independiente con quickstart §2–§3

### Within Each User Story

- T005 antes que cualquier uso de `destroy-url`; T006 monta el modal en modo create y T009 completa el cableado de edición
- T007 (componente nuevo) antes de cualquier integración; integraciones en `Tasks/Index.vue` y `Projects/Show.vue` secuenciales por página, paralelas entre sí
- Verificación manual de la historia (quickstart) antes de pasar a la siguiente fase

### Parallel Opportunities

- T004 [P] puede ejecutarse en paralelo a T003 (tests vs. componentes de vista, archivos distintos, ambos dependen solo de T002)
- T005 [P] ∥ T006 [P]: `TaskFormModal.vue` y `Projects/Show.vue` no se tocan entre sí y el flujo de creación no usa `destroyUrl`
- Tras T007: T008 [P] ∥ T009 [P] (`Tasks/Index.vue` vs. `Projects/Show.vue`)
- T011 [P] ∥ T012 [P] (gates de frontend y backend) en Polish

---

## Parallel Example: User Story 1 + Foundational

```text
# Tras T002 (payload), lanzar en paralelo:
Task T003: "Actualizar aserciones de payload en tests/Feature/Tasks/*"
Task T004: "Retipar BacklogView.vue y KanbanBoard.vue a BoardTask[]"

# Historia 1, en paralelo:
Task T005 [US1]: "destroyUrl opcional en TaskFormModal.vue"
Task T006 [US1]: "Botón Nueva Tarea abre modal en pages/Projects/Show.vue"
```

---

## Implementation Strategy

### MVP First (User Story 1)

1. T001 (baseline) → T002–T004 (foundational D3)
2. T005 + T006 (US1: creación desde el detalle)
3. **STOP and VALIDATE**: quickstart §1 — ya es entregable por sí solo

### Incremental Delivery

1. Setup + Foundational → payload `BoardTask` estable
2. US1 → crear tareas desde el proyecto (MVP)
3. US2 → listado estilo `/tasks`, sin formulario inline, acciones y comentarios homologados
4. Polish → gates (types/build/Pint/Pest) + validación manual completa

### Parallel Team Strategy

Con dos desarrolladores: uno ejecuta T003 (tests) mientras otro hace T004 (retipado); después, uno hace T008 (refactor `/tasks`) mientras otro hace T009 (integración en el proyecto); convergen en T010 y Polish.

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Cada historia es independiente y validable por [quickstart.md](quickstart.md)
- No se eliminan tests: T003 actualiza aserciones al nuevo contrato (data-model.md)
- Cero dependencias nuevas y cero rutas nuevas (research D9)
- Commit after each task or logical group
