---
description: 'Task list for 004-task-management'
---

# Tasks: Gestión de Tareas (Tasks)

**Input**: Design documents from `/specs/004-task-management/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/web-tasks-surface.md, quickstart.md

**Tests**: INCLUIDOS — obligatorios por Principio III de la constitución (Test-First con Pest, NON-NEGOTIABLE). Los tests de cada historia deben escribirse PRIMERO y FALLAR antes de la implementación.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: User story label (US1–US6, según spec.md)
- Include exact file paths in descriptions

## Path Conventions

Single project (Laravel monolith). Rutas reales según `plan.md` — `app/`, `routes/`, `resources/js/pages/`, `resources/js/Components/Tasks/`, `tests/Feature/Tasks/`, `database/migrations/`, `database/factories/`.

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Baseline verde antes de tocar código (no hay dependencias nuevas)

- [x] T001 Run baseline verification: `composer install && npm install && composer ci:check` passes on the current tree before any change (features 001–003 deben estar verdes; si phpstan falla por memory_limit del CLI de Herd, validar ese gate con `--memory-limit=1G`)

**Checkpoint**: Baseline verde confirmado

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Enums, esquema, modelos, policy y rutas que TODAS las historias necesitan

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T002 [P] Create the three backed enums via `php artisan make:enum <Name> --string --no-interaction`: `app/Enums/TaskType.php` (`Bug = 'bug'`, `Feature = 'feature'`, `Test = 'test'`, `Other = 'other'` + `label()`), `app/Enums/TaskPriority.php` (`Low = 'low'`, `Medium = 'medium'`, `High = 'high'`, `Urgent = 'urgent'` + `label()`), `app/Enums/TaskStatus.php` (`Backlog = 'backlog'`, `Todo = 'todo'`, `InProgress = 'in_progress'`, `InReview = 'in_review'`, `Done = 'done'` + `label()`); Spanish labels (Bug/Feature/Test/Otro; Baja/Media/Alta/Urgente; Backlog/Por hacer/En progreso/En revisión/Hecho); **sin** método `transitions()` (movimiento libre, research R-1)
- [x] T003 [P] Create migration `database/migrations/2026_09_23_000001_create_tasks_table.php` via `php artisan make:migration create_tasks_table --no-interaction`: `project_id` FK → `projects.id` (`cascadeOnDelete`), `sprint_id` FK → `sprints.id` **nullable + `nullOnDelete`** (borrar sprint deja tareas aisladas), `assignee_id` FK → `users.id` **nullable + `nullOnDelete`**, `title` string(255), `description` text nullable, `type` string default `'other'`, `priority` string default `'medium'`, `status` string default `'backlog'`, timestamps; `index('project_id')` (constraints verbatim de data-model.md; research R-2)
- [x] T004 [P] Create migration `database/migrations/2026_09_23_000002_create_comments_table.php` via `php artisan make:migration create_comments_table --no-interaction`: `task_id` FK → `tasks.id` (`cascadeOnDelete` — FR-009), `user_id` FK → `users.id` (`cascadeOnDelete`), `body` text, timestamps; `index('task_id')`
- [x] T005 Run `php artisan migrate` on the development database and verify both tables exist (`migrate:status` shows Ran)
- [x] T006 Create `app/Models/Task.php` + `database/factories/TaskFactory.php` via `php artisan make:model Task --factory --no-interaction`: model con `#[Fillable(['project_id', 'sprint_id', 'assignee_id', 'title', 'description', 'type', 'priority', 'status'])]`, casts de los tres enums, relaciones `project(): BelongsTo`, `sprint(): BelongsTo`, `assignee(): BelongsTo` (User), `comments(): HasMany`; factory con `title` (words), `description` nullable sentence, type/priority/status con `fake()->randomElement` de los cases, `for(Project)`, sprint/assignee opcionales; PHPDoc de propiedades (modelo sin orden global en la relación)
- [x] T007 [P] Create `app/Models/Comment.php` + `database/factories/CommentFactory.php` via `php artisan make:model Comment --factory --no-interaction`: `#[Fillable(['task_id', 'user_id', 'body'])]`, relaciones `task(): BelongsTo`, `author(): BelongsTo` (User, FK `user_id`); factory con `body` sentence, `for(Task)`, `for(User)` como autor
- [x] T008 [P] Create `app/Policies/TaskPolicy.php` via `php artisan make:policy TaskPolicy --model=Task --no-interaction`: `viewAny`/`view` = `$project->isMember($user)`; `create`/`update` = `$project->isMember($user) && $project->status->isActive()`; `delete` = `$project->isOwnedBy($user) && $project->status->isActive()` (FR-010/FR-011; research R-3); verificar auto-discovery
- [x] T009 [P] Update `app/Models/Project.php`: add relation `tasks(): HasMany` → Task, **sin orden global** (el orden `updated_at` desc se aplica en el payload, FR-005); update the class PHPDoc
- [x] T010 [P] Update `routes/web.php` (inside the `auth` scope): `Route::post('projects/{project}/tasks', [ProjectTaskController::class, 'store'])->name('projects.tasks.store')`, `Route::put('projects/{project}/tasks/{task}', [ProjectTaskController::class, 'update'])->name('projects.tasks.update')`, `Route::delete('projects/{project}/tasks/{task}', [ProjectTaskController::class, 'destroy'])->name('projects.tasks.destroy')` y `Route::post('projects/{project}/tasks/{task}/comments', [ProjectTaskController::class, 'storeComment'])->name('projects.tasks.comments.store')` (research R-4; el controlador se crea en US1 — resolución lazy, no ejecutar `route:list` hasta entonces)

**Checkpoint**: Foundation ready — enums, tablas migradas, modelos/factories, policy, rutas y relación en su lugar

---

## Phase 3: User Story 1 - Crear y listar tareas en un proyecto (Priority: P1) 🎯 MVP

**Goal**: Cualquier miembro crea una tarea (título obligatorio, descripción markdown opcional, tipo/prioridad con defaults, responsable y sprint opcionales) y la ve en la lista del proyecto ordenada por recientemente actualizadas (US1, FR-001/002/003/004/005).

**Independent Test**: quickstart S1 + S5: alta válida aislada y con sprint, defaults `other`/`medium` y estado `backlog`, orden por recientemente actualizadas, validaciones (título, responsable ajeno, sprint de otro proyecto).

### Tests for User Story 1 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T011 [US1] Create `tests/Feature/Tasks/TaskCrudTest.php` (Pest; vía `php artisan make:test --pest Tasks/TaskCrudTest --no-interaction`) — create + list portion: `POST /projects/{project}/tasks` de un miembro guarda la tarea con `project_id`, estado `backlog` y (si no se envían) type `other`/priority `medium`; redirige a `projects.show`; con `sprint_id` válido queda vinculada y con `assignee_id` miembro queda asignada; aparece en el prop `project.tasks` de `Projects/Show` con título, typeLabel, priorityLabel, statusLabel, assignee y sprint; varias tareas aparecen ordenadas por `updated_at` desc; nombre ausente o solo espacios, `assignee_id` de no-miembro y `sprint_id` de otro proyecto → `assertSessionHasErrors` y nada se crea

### Implementation for User Story 1

- [x] T012 [P] [US1] Create `app/Http/Requests/StoreTaskRequest.php` via `php artisan make:request StoreTaskRequest --no-interaction`: `title` required string max:255; `description` nullable string; `type` y `priority` `sometimes` + `Rule::enum(TaskType::class)`/`Rule::enum(TaskPriority::class)`; `assignee_id` nullable + `exists:users,id` + regla a medida (closure) que exija `$this->route('project')->isMember(User::find($value))`; `sprint_id` nullable + `exists:sprints,id` + closure que exija pertenencia al proyecto (FR-002/FR-003; `'   '` lo rechaza `required` + `ConvertEmptyStringsToNull`); `authorize(): $this->user() !== null`; mensajes en español
- [x] T013 [US1] Create `app/Http/Controllers/ProjectTaskController.php` via `php artisan make:controller ProjectTaskController --no-interaction` con `store()`: `abort_unless($project->isMember($request->user()), 404)` (miembro no-activo también escribe — la membresía es la llave de lectura/escritura); helper privado `ensureProjectIsActive(Project $project)` que redirige de vuelta con `errors.project` = "El proyecto no admite cambios en su estado actual." cuando `! $project->status->isActive()` (FR-010, mensaje verbatim del contrato); `$this->authorize('create', [Task::class, $project])`; fusión de defaults (`$validated['type'] ??= TaskType::Other->value`, `priority ??= Medium`) y `$project->tasks()->create($validated)`; redirect a `projects.show`
- [x] T014 [P] [US1] Update `app/Http/Controllers/ProjectController.php` (`projectPayload`): eager loading `tasks.assignee`, `tasks.sprint`, `tasks.comments.author` y `'tasks' => $project->tasks()->orderByDesc('updated_at')->get()->map(...)` con `{ id, title, description, type, typeLabel, priority, priorityLabel, status, statusLabel, assignee: {id,name}|null, sprint: {id,name}|null, comments: [{id, body, author:{id,name}, createdAt}] }` (comments ordenados asc; FR-005 + research R-5/R-8)
- [x] T015 [P] [US1] Create `resources/js/Components/Tasks/TaskList.vue`: lista de `tasks` (props) con título, badges de tipo/prioridad/estado (typeLabel/priorityLabel/statusLabel), responsable (o "Sin responsable") y sprint (o "Aislada"); fila sin acciones todavía (llegan en US2/US4/US5); estado vacío "Sin tareas todavía."
- [x] T016 [US1] Update `resources/js/pages/Projects/Show.vue`: sección "Tareas" — formulario de alta inline (título, descripción, tipo, prioridad, responsable con opciones `owner`+`members`, sprint con opciones `project.sprints` + "Sin sprint") con `useForm` POST vía Wayfinder `store` de `@/routes/projects/tasks`; render de `TaskList`; sección de escritura solo si `project.status === 'active'` (FR-010; la membresía ya garantiza la página)
- [x] T017 [US1] Rebuild Wayfinder (`npm run build`), run `npm run types:check`, and verify US1 via quickstart S1/S5 green (`php artisan test --compact --filter=TaskCrudTest`)

**Checkpoint**: User Story 1 fully functional and testable independently — MVP

---

## Phase 4: User Story 2 - Editar tareas y avanzar su estado (Priority: P2)

**Goal**: Cualquier miembro edita título/descripción/tipo/prioridad/responsable/sprint y cambia el estado libremente entre los cinco valores del flujo (US2, FR-006/FR-007).

**Independent Test**: quickstart S2: edición reflejada, movimiento libre de estados (incl. regresar), título vacío rechazado sin alterar la tarea.

### Tests for User Story 2 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T018 [P] [US2] Extend `tests/Feature/Tasks/TaskCrudTest.php` — update + status portion: `PUT` persiste cambios de todos los campos y redirige; `status` acepta movimiento libre (`todo → done` y `done → backlog`) quedando persistido; `title` vacío o `assignee_id` no-miembro → `assertSessionHasErrors` y la tarea conserva sus datos; tarea de OTRO proyecto → 404 (tanto update como delete)

### Implementation for User Story 2

- [x] T019 [P] [US2] Create `app/Http/Requests/UpdateTaskRequest.php` (mismas reglas que `StoreTaskRequest` T012 **más** `status` required + `Rule::enum(TaskStatus::class)`; sin defaults de type/priority — es edición completa; mensajes en español)
- [x] T020 [US2] Add `update(UpdateTaskRequest $request, Project $project, Task $task)` a `app/Http/Controllers/ProjectTaskController.php`: `abort_unless($project->isMember($request->user()), 404)`; `abort_unless($task->project_id === $project->id, 404)`; `ensureProjectIsActive($project)`; `$this->authorize('update', [Task::class, $project])`; `$task->update($request->validated())`; redirect a `projects.show`
- [x] T021 [P] [US2] Create `resources/js/Components/Tasks/TaskModal.vue`: modal de edición (el alta sigue inline) con todos los campos incl. selector de estado (cinco values con sus labels) y sprint con opción "Sin sprint"; `useForm` PUT vía Wayfinder `update` de `@/routes/projects/tasks`, `preserveScroll`, cierre y reset en éxito
- [x] T022 [US2] Update `resources/js/Components/Tasks/TaskList.vue` + `resources/js/pages/Projects/Show.vue`: botón "Editar" por fila (visible si `project.status === 'active'`) que abre `TaskModal` precargado; pasar props necesarias (tarea activa, emisor de eventos)
- [x] T023 [US2] Rebuild Wayfinder (`npm run build`), run `npm run types:check`, and verify US2 via quickstart S2 green (`php artisan test --compact --filter=TaskCrudTest`)

**Checkpoint**: User Stories 1 AND 2 both work independently

---

## Phase 5: User Story 3 - Vincular y desvincular tareas de sprints (Priority: P3)

**Goal**: Las tareas se asignan a sprints del mismo proyecto y se retiran volviendo al backlog general; borrar un sprint deja sus tareas aisladas (US3, FR-003).

**Independent Test**: quickstart S3: vincular, desvincular (opción "Sin sprint") y aislamiento tras borrar el sprint (verificable en DB).

### Tests for User Story 3 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T024 [US3] Extend `tests/Feature/Tasks/TaskCrudTest.php` — sprint-link portion: editar una tarea aislada con `sprint_id` válido la vincula; enviar `sprint_id: null` (o vacío) la desvincula dejándola aislada; **borrar el sprint (`$sprint->delete()`) deja la tarea en la base con `sprint_id` null** (semántica `nullOnDelete`, research R-2); el resto de tareas del sprint no se ven afectadas

### Implementation for User Story 3

- [x] T025 [US3] Verify quickstart S3 green (`php artisan test --compact --filter=TaskCrudTest`): la vinculación/desvinculación ya funciona vía modal de edición (T021) con la opción "Sin sprint"; si el test de aislamiento falla, revisar la FK `nullOnDelete` de T003; rebuild Wayfinder + `npm run types:check` si se toca frontend

**Checkpoint**: User Story 3 independently functional (la mayoría del soporte llegó de US1/US2)

---

## Phase 6: User Story 4 - Comentarios en las tareas (Priority: P4)

**Goal**: Cualquier miembro añade comentarios de texto a una tarea; se listan cronológicamente con autor junto a la tarea (US4, FR-008).

**Independent Test**: quickstart S4 (comentarios): alta visible en el hilo con autor y orden, cuerpo vacío rechazado.

### Tests for User Story 4 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T026 [US4] Create `tests/Feature/Tasks/TaskCommentsTest.php` (Pest; vía `php artisan make:test --pest Tasks/TaskCommentsTest --no-interaction`): `POST .../tasks/{task}/comments` de un miembro guarda el comentario con `task_id` y `user_id` del autor, redirige a `projects.show` y aparece en el prop `project.tasks.N.comments` en orden cronológico con `author`; cuerpo vacío o solo espacios → `assertSessionHasErrors('body')` y nada se crea; tarea de OTRO proyecto → 404; autor no miembro del proyecto → 404

### Implementation for User Story 4

- [x] T027 [P] [US4] Create `app/Http/Requests/StoreCommentRequest.php` via `php artisan make:request StoreCommentRequest --no-interaction`: `body` required string; `authorize(): $this->user() !== null`; mensajes en español ("El comentario no puede estar vacío.")
- [x] T028 [US4] Add `storeComment(StoreCommentRequest $request, Project $project, Task $task)` a `app/Http/Controllers/ProjectTaskController.php`: `abort_unless($project->isMember($request->user()), 404)`; `abort_unless($task->project_id === $project->id, 404)`; `ensureProjectIsActive($project)`; `$this->authorize('create', [Task::class, $project])` (misma habilidad que crear: miembro+activo); `$task->comments()->create([...$request->validated(), 'user_id' => $request->user()->id])`; redirect a `projects.show`
- [x] T029 [P] [US4] Create `resources/js/Components/Tasks/TaskCommentsModal.vue`: modal con hilo cronológico (autor + fecha + body) y formulario de comentario (`useForm({ body })` POST vía Wayfinder `store` de `@/routes/projects/tasks/comments` con `preserveScroll`, reset en éxito); render de errores de `body`
- [x] T030 [US4] Update `resources/js/Components/Tasks/TaskList.vue` + `resources/js/pages/Projects/Show.vue`: botón "Comentarios" por fila (miembros siempre, para lectura) que abre `TaskCommentsModal` con la tarea; el alta de comentario en el modal solo se renderiza si `project.status === 'active'`
- [x] T031 [US4] Rebuild Wayfinder (`npm run build`), run `npm run types:check`, and verify US4 via quickstart S4 green (`php artisan test --compact --filter=TaskCommentsTest`)

**Checkpoint**: User Story 4 independently functional

---

## Phase 7: User Story 5 - Eliminar una tarea (Priority: P5)

**Goal**: El propietario elimina una tarea con confirmación; se suprimen sus comentarios en cascada y nada más se ve afectado (US5, FR-009).

**Independent Test**: quickstart S4 (eliminación): baja confirmada desaparece con sus comentarios; sin confirmación no pasa nada.

### Tests for User Story 5 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T032 [US5] Extend `tests/Feature/Tasks/TaskCrudTest.php` — destroy portion: `DELETE .../tasks/{task}` del propietario elimina la tarea y **sus comentarios en cascada**, y redirige a `projects.show`; el proyecto, el sprint y las demás tareas permanecen intactos; tarea de OTRO proyecto → 404

### Implementation for User Story 5

- [x] T033 [US5] Add `destroy(Request $request, Project $project, Task $task)` a `app/Http/Controllers/ProjectTaskController.php`: mismos guardas que `update()` con `authorize('delete', ...)` (propietario+activo vía policy, FR-009); `$task->delete()` (la cascada de comentarios la resuelve la FK de T004); redirect a `projects.show`
- [x] T034 [P] [US5] Update `resources/js/Components/Tasks/TaskList.vue`: botón "Eliminar" por fila, solo si `project.isOwner && project.status === 'active'`, con `window.confirm(...)` previo (US5 escenario 2: sin confirmación no se emite petición) y `router.delete` vía Wayfinder `destroy` de `@/routes/projects/tasks` con `preserveScroll`
- [x] T035 [US5] Rebuild Wayfinder (`npm run build`), run `npm run types:check`, and verify US5 via quickstart S4 green (`php artisan test --compact --filter=TaskCrudTest`)

**Checkpoint**: User Stories 1–5 independently functional

---

## Phase 8: User Story 6 - Solo lectura fuera del estado activo y permisos (Priority: P6)

**Goal**: Con proyecto archivado/completado las tareas y comentarios quedan visibles pero las escrituras se bloquean con mensaje claro; colaboradores gestionan pero no eliminan; no miembros no acceden (US6, FR-010/FR-011, SC-003).

**Independent Test**: quickstart S7: matriz de permisos y estados en verde.

### Tests for User Story 6 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T036 [US6] Create `tests/Feature/Tasks/TaskAccessTest.php` (Pest; vía `php artisan make:test --pest Tasks/TaskAccessTest --no-interaction`): colaborador puede crear/editar/comentar (201/302 sin errores) pero recibe 404 al eliminar; usuario no miembro recibe 404 en la ficha del proyecto y en las cuatro escrituras; propietario con proyecto `archived()`/`completed()` recibe `assertSessionHasErrors(['project' => 'El proyecto no admite cambios en su estado actual.'])` en store/update/destroy/comment y nada cambia; con proyecto archivado/completado el prop `project.tasks` sigue presente en `Projects/Show` para propietario y colaborario (solo lectura visible)

### Implementation for User Story 6

- [x] T037 [US6] Verify en `resources/js/pages/Projects/Show.vue` + componentes: las acciones de escritura (alta, editar, eliminar, comentar) quedan ocultas fuera de activo según las condiciones de visibilidad ya implementadas (T016/T022/T030/T034) y el banner `errors.project` se muestra tras un intento forzado (research R-3); corregir cualquier brecha detectada por T036; rebuild Wayfinder + `npm run types:check` si se toca frontend

**Checkpoint**: All six user stories independently functional

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: Quality gates y validación completa

- [x] T038 [P] Run full quality gates: `vendor/bin/pint --dirty --format agent` si se tocó PHP, luego `composer ci:check` (vp check, pint --test, phpstan, full test suite; phpstan con `--memory-limit=1G` si el CLI local lo requiere) — all must pass
- [x] T039 Run quickstart.md validation S1–S8 end-to-end (manual S1–S4 + automated S5–S7 + gates S8) and confirm success criteria SC-001–SC-004 from spec.md (creación < 1 min, lista < 2 s con 100 tareas, 100% de escrituras bloqueadas fuera de activo con mensaje comprensible, flujo completo sin ayuda)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — empieza de inmediato
- **Foundational (Phase 2)**: Depends on Setup — BLOCKS all user stories (T002–T004 ∥; T003/T004 antes que T005; T006 antes que T008; T007/T009/T010 ∥ resto)
- **User Stories (Phase 3–8)**: Todas dependen de Foundational; luego en orden US1 → US2 → US3 → US4 → US5 → US6 (US1 es el MVP)
- **Polish (Phase 9)**: Depends on all six stories complete

### User Story Dependencies

- **US1 (P1)**: Foundational only — MVP
- **US2 (P2)**: Foundational + US1 (crea `ProjectTaskController` y el helper `ensureProjectIsActive`; comparten `Show.vue` y `TaskList.vue`)
- **US3 (P3)**: Foundational + US2 (la edición con sprint vive en el modal; la FK `nullOnDelete` es de T003)
- **US4 (P4)**: Foundational + US1 (mismo controlador; comparten `TaskList.vue`)
- **US5 (P5)**: Foundational + US1 (mismo controlador; comparten `TaskList.vue`)
- **US6 (P6)**: Foundational + US1–US5 (la matriz de permisos se prueba sobre las cuatro escrituras ya implementadas)

### Within Each User Story

- Tests MUST exist and FAIL before implementation tasks start
- Request before controller action; controller before page/components; per story: tests → backend → frontend → rebuild/verify

### Parallel Opportunities

- T002 ∥ T003 ∥ T004 (enums/migraciones, archivos distintos); T006–T010 ∥ entre sí salvo T005 (que depende de T003/T004)
- T012 ∥ T013 ∥ T014 ∥ T015 (request / controller / payload / TaskList — archivos distintos, contrato fija la interfaz); T019 ∥ T018 ∥ T021 (request / tests / modal); T027 ∥ T026 ∥ T029
- Las historias no son plenamente paralelizables entre sí por archivos compartidos (`ProjectTaskController.php`, `TaskList.vue`, `Show.vue`), pero sus tests sí pueden escribirse en paralelo

---

## Parallel Example: User Story 1

```bash
# Tras Foundational, en paralelo:
Task: "Create app/Http/Requests/StoreTaskRequest.php"
Task: "Update app/Http/Controllers/ProjectController.php (payload con tasks)"
Task: "Create resources/js/Components/Tasks/TaskList.vue"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1 (Setup) + Phase 2 (Foundational)
2. Complete Phase 3 (US1) → validar con quickstart S1/S5
3. **STOP and VALIDATE**: alta + listado con defaults y validaciones funcionando — ya es gestión de tareas usable (spec, US1 Independent Test)
4. Desplegar/demo

### Incremental Delivery

1. Setup + Foundational → foundation ready
2. US1 → alta/listado funcionando (MVP)
3. US2 → edición + estados
4. US3 → planificación por sprints
5. US4 → colaboración con comentarios
6. US5 → eliminación con cascada
7. US6 → matriz de permisos y estados verificada
8. Polish → quality gates + validación completa

### Parallel Team Strategy

Con dos desarrolladores tras la Fase 2:

- Developer A: US1 → US2 (T011–T023, comparten `ProjectTaskController.php`)
- Developer B: escribe T018/T024/T026/T032/T036 (tests de US2–US6) contra las firmas del contrato mientras A implementa US1 — coordinar `TaskList.vue`/`Show.vue` (T015/T016 vs T022/T030/T034)

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to spec user story (US1–US6)
- Cada historia es independiente y testeable por sí sola (quickstart la respalda)
- Tests MUST fail antes de implementar (Principio III, NON-NEGOTIABLE)
- Los constraints de campos (longitudes, defaults `other`/`medium`/`backlog`, valores de enum, mensajes verbatim) se citan de data-model.md/contracts en las tareas
- Los directorios `resources/js/routes/` y `resources/js/actions/` son generados por Wayfinder: regenerar con `npm run build`, nunca editar a mano
- Commit after each task or logical group
