---
description: 'Task list for 015-home-active-dashboard (Panel de inicio con actividad del usuario)'
---

# Tasks: Panel de inicio con actividad del usuario

**Input**: Design documents from `/specs/015-home-active-dashboard/` (plan.md, spec.md, research.md, data-model.md, contracts/dashboard-payload.md, quickstart.md)

**Prerequisites**: plan.md ✅ spec.md ✅ research.md ✅ data-model.md ✅ contracts/ ✅

**Tests**: Pest tests INCLUIDOS — la constitución del proyecto (Principio III) exige Test-First: tests escritos → fallan → implementación → verde.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- Single monolithic project: `app/`, `routes/`, `resources/js/`, `database/`, `tests/` at repository root
- Frontend pages/components/types under `resources/js/`; Pest tests under `tests/Feature/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Feature branch and working tree readiness

- [X] T001 Create git branch `015-home-active-dashboard` from the current main branch and confirm the working tree is clean before starting

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Persistencia mínima (activities + completed_at) que MUST existir antes de cualquier historia: alimenta el burndown (FR-006), el insight semanal (FR-008) y el feed (FR-009). Basado en research.md D2/D3/D13 y data-model.md.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T002 Create enum `ActivityType` in `app/Enums/ActivityType.php`: string-backed cases `TaskCreated='task_created'`, `TaskCompleted='task_completed'`, `CommentCreated='comment_created'`, `SprintStarted='sprint_started'`, `SprintCompleted='sprint_completed'`, each with Spanish `label()` ("creó la tarea", "completó la tarea", "comentó en", "inició el sprint", "completó el sprint")
- [X] T003 [P] Create migration `create_activities_table` per data-model.md: `user_id` FK→users cascade, `project_id` FK→projects cascade, `task_id` FK→tasks nullable nullOnDelete, `sprint_id` FK→sprints nullable nullOnDelete, `type` string(50); indexes `(project_id, created_at)`, `(user_id, created_at)`, `(sprint_id, type, created_at)`; create `Activity` model in `app/Models/Activity.php` (fillable `user_id, project_id, task_id, sprint_id, type`; cast `type`=>`ActivityType`; belongsTo `user`, `project`, `task`, `sprint`)
- [X] T004 [P] Create migration `add_completed_at_to_tasks_table`: nullable timestamp `completed_at` on `tasks` + index `(assignee_id, status)`
- [X] T005 Create `ActivityFactory` in `database/factories/ActivityFactory.php` (user/project/task/sprint relations via factories, type random enum)
- [X] T006 Write failing Pest tests `tests/Feature/Home/ActivityRecordingTest.php` covering data-model.md transition rules: (1) task status→Done sets `completed_at` and inserts `task_completed` activity with actor=user, project, task, sprint refs; (2) Done→other status clears `completed_at` with NO activity; (3) creating a task WITH project inserts `task_created`; standalone task (no project) inserts none; (4) comment insert creates `comment_created` with the task's project; (5) sprint `start()` creates `sprint_started` and `complete()` creates `sprint_completed`; actor is always the authenticated user
- [X] T007 Create `app/Observers/TaskObserver.php` per data-model.md rules 1–3: `created` → `TaskCreated` activity only when task has project; `updated` when status becomes `Done` → set `completed_at=now()` + `TaskCompleted` activity (with sprint_id when present); when status leaves `Done` → `completed_at=null` (no activity)
- [X] T008 [P] Create `app/Observers/CommentObserver.php`: `created` → `CommentCreated` activity (user=author, project=task.project, task)
- [X] T009 [P] Create `app/Observers/SprintObserver.php`: `started` transition → `SprintStarted` activity; `completed` transition → `SprintCompleted` activity (user=actor, project, sprint). Hook via model events after `Sprint::start()`/`complete()` succeed
- [X] T010 Register observers in `app/Providers/AppServiceProvider.php` boot: `Task::observe(TaskObserver::class)`, `Comment::observe(CommentObserver::class)`, `Sprint::observe(SprintObserver::class)`; run T006 until green

**Checkpoint**: Activity events and `completed_at` persist correctly; `php artisan test --compact tests/Feature/Home` green for ActivityRecordingTest

---

## Phase 3: User Story 1 - Panel resumen personalizado (Priority: P1) 🎯 MVP

**Goal**: Usuario con actividad ve en `/` el panel completo: saludo + subtítulo contextual, tarjetas de resumen, "Mis tareas para hoy & prioritarias", panel del sprint activo con burndown, proyectos activos e insight semanal. Usuario sin actividad sigue viendo `HomeCanvas` intacto.

**Independent Test**: `tests/Feature/Home/*` verde + manual quickstart.md escenarios A y B: usuario con proyecto/tareas/sprint ve el panel con datos reales; usuario nuevo ve el lienzo vacío.

### Tests for User Story 1 ⚠️ (write FIRST, must FAIL before implementation)

- [X] T011 [P] [US1] Failing test `tests/Feature/Home/DashboardEmptyStateTest.php`: user without projects and without assigned tasks → `GET /` renders page `AppHome` with `dashboard` prop `null`; shared `projects` sidebar prop unchanged (regression of `ShellRenderingTest` contract)
- [X] T012 [P] [US1] Failing test `tests/Feature/Home/DashboardPayloadTest.php` (per contracts/dashboard-payload.md): `context.priorityTasks` = count of active (status≠Done) assigned tasks with priority Urgent|High; `summary.tasks.active`/`urgent` counts real; `myTasks.items` ordered Urgent>High>Medium>Low then `updated_at` desc, max 6, each item has status+statusLabel/priority+priorityLabel/project{id,title}/commentsCount; `myTasks.total` real; `projects.items` only user's projects with status Active, max 4, with `progress` (matches `Project::progressPercentage()`) and `note`
- [X] T013 [P] [US1] Failing test `tests/Feature/Home/SprintPanelTest.php`: `sprintPanel` = latest active sprint (max `start_date`) among user's projects, with name/goal/project/total/done/coverage/daysRemaining; `burndown.points` has one entry per natural day start→end with `ideal` straight line 0→total and `done` = cumulative `task_completed` activities per day; `summary.sprint` and `context.sprint` null simultaneously with `sprintPanel` when no active sprint (FR-011)
- [X] T014 [P] [US1] Failing test `tests/Feature/Home/WeeklyInsightTest.php`: `weeklyInsight.completed` = user's `task_completed` activities since current ISO week start; `avgCycleDays` = avg days between `created_at` and `completed_at` of tasks the user completed this week (1 decimal); `weeklyInsight` null when completed = 0

### Implementation for User Story 1

- [X] T015 [US1] Create invokable `app/Http/Controllers/HomeController.php`: gate "has activity" (owns/member of ≥1 project OR ≥1 assigned task — reuse the canonical `owner_id orWhereHas members` + `assignee_id` scope pattern from `TaskController::index`); if none → `Inertia::render('AppHome', ['dashboard' => null])`; else build payload with private mappers per contracts/dashboard-payload.md: `context`, `summary` (sprint card/daysRemaining replicating weekday logic from `ProjectSprintController::sprintDetail`, tasks counts, projects active count), `myTasks` (limit 6, eager `project`, `withCount comments`), `sprintPanel` + `burndown` (from activities type `task_completed` of that sprint), `projects` (Active, limit 4, note = active sprint name + days remaining or open-task count), `weeklyInsight`; `activity` returns `{ items: [] }` until US2; all queries eager-loaded and limited
- [X] T016 [US1] Replace the closure at `routes/web.php:11-13` with `Route::get('/', HomeController::class)->middleware(['auth'])->name('home');` keeping identical name/middleware
- [X] T017 [US1] Create `resources/js/types/dashboard.ts` with TypeScript interfaces matching contracts/dashboard-payload.md exactly (Dashboard, DashboardContext, SummaryCardsPayload, MyTasksPayload, SprintPanelPayload with BurndownPoint, ProjectsPanelPayload, WeeklyInsight, ActivityItem)
- [X] T018 [P] [US1] Create `resources/js/Components/Home/DonutProgress.vue`: SVG donut, props `percent` (0-100) + `size`; accessible (`role="progressbar"`, aria-valuenow); Tailwind token colors, no chart lib (research.md D11)
- [X] T019 [P] [US1] Create `resources/js/Components/Home/BurndownChart.vue`: hand-rolled SVG line chart with props `{ points: BurndownPoint[] }` drawing ideal line vs actual cumulative done; light-mode tokens
- [X] T020 [P] [US1] Create `resources/js/Components/Home/SummaryCards.vue`: sprint card (DonutProgress, name, coverage, days remaining; omitted when null per FR-011), tasks card (active count, urgent/high count), projects card (active count)
- [X] T021 [P] [US1] Create `resources/js/Components/Home/MyTasksPanel.vue`: task rows with status dot + `statusLabel`, priority chip (reuse tone pattern from `Components/Tasks/Board/TaskRow.vue`: high=`tertiary-fixed`, urgent=`error-container`), project title, comments count; footer slot "Ver todas mis tareas (N)"
- [X] T022 [P] [US1] Create `resources/js/Components/Home/SprintPanel.vue`: name, goal, project title, "X de Y resueltas", coverage %, days remaining, BurndownChart, footer slot "Abrir tablero del sprint"
- [X] T023 [P] [US1] Create `resources/js/Components/Home/ProjectsPanel.vue`: rows with title, progress bar (pattern from `Components/Sprints/SprintMetricsPanel.vue`), `note`; footer slot "Ver todos"
- [X] T024 [P] [US1] Create `resources/js/Components/Home/WeeklyInsightCard.vue`: "Semana de alta productividad" style card showing completed count + avg cycle days; null → honest empty state ("Aún sin tareas resueltas esta semana")
- [X] T025 [US1] Create `resources/js/Components/Home/ActivityDashboard.vue`: page-level layout — greeting computed client-side from `new Date().getHours()` ("Buenos días / Buenas tardes / Buenas noches") + `auth.user` first name (research.md D12), contextual subtitle from `context` prop, quick-action slot area, responsive grid (2 cols → 1 col) composing SummaryCards, MyTasksPanel, SprintPanel, ProjectsPanel, WeeklyInsightCard
- [X] T026 [US1] Update `resources/js/pages/AppHome.vue`: `defineProps<{ dashboard: Dashboard | null }>()`; template renders `<ActivityDashboard v-if="dashboard" :dashboard="dashboard" />` else existing `<HomeCanvas />`
- [X] T027 [US1] Run `npm run check` (vue-tsc + eslint) and fix all type/lint errors in new files

**Checkpoint**: US1 fully functional and independently testable — quickstart.md scenarios A (empty state regression) and B (panel with real data) pass

---

## Phase 4: User Story 2 - Feed Actividad del equipo (Priority: P2)

**Goal**: El panel incluye el feed "Actividad del equipo": últimos 8 eventos reales de los proyectos del usuario (quién, qué, sobre qué, hace cuánto), con estado vacío honesto.

**Independent Test**: `ActivityFeedTest` verde + escenario C de quickstart.md: el feed muestra solo eventos de proyectos del usuario, con tiempo relativo en español.

### Tests for User Story 2 ⚠️ (write FIRST, must FAIL before implementation)

- [X] T028 [P] [US2] Failing test `tests/Feature/Home/ActivityFeedTest.php`: `dashboard.activity.items` = latest 8 activities (created_at desc) from projects the user owns/belongs to — events from foreign projects excluded; item shape = `id`, `type`+`typeLabel` (Spanish label), `user{id,name,avatar}`, `task{id,title}` (null when N/A), `project{id,title}`, `createdAt` ISO; empty array when no events

### Implementation for User Story 2

- [X] T029 [US2] Extend `HomeController` with the `activity` mapper replacing the US1 placeholder: query activities of user's projects (owner/member scope), latest 8, eager `user:id,name,avatar`, `task:id,title`, `project:id,title`; `typeLabel` from `ActivityType::label()` until T028 is green
- [X] T030 [P] [US2] Create `resources/js/utils/relativeTime.ts`: helper formatting an ISO date to Spanish relative time ("hace 20 min", "hace 1 hora", "hace 2 días") using `Intl.RelativeTimeFormat('es')`, no date library (research.md D9)
- [X] T031 [US2] Create `resources/js/Components/Home/ActivityFeed.vue`: list items with Avatar (initials fallback), "Name typeLabel taskTitle", relative time via T030; task title links to `tasks.show` slot; empty state "Sin actividad reciente" (FR-009)
- [X] T032 [US2] Wire ActivityFeed into `ActivityDashboard.vue` (right column below SprintPanel) and run `npm run check`

**Checkpoint**: US1 and US2 both work independently — feed shows real team events with relative time; empty state when none

---

## Phase 5: User Story 3 - Acciones rápidas y navegación (Priority: P3)

**Goal**: Todas las acciones del panel navegan: Ir al sprint / Abrir tablero del sprint → tablero del sprint activo; Nueva tarea → paleta de comandos (mismo mecanismo que atajo `C`); Ver todas mis tareas → `tasks.index`; Ver todos → `projects.index`.

**Independent Test**: `DashboardNavigationTest` verde + escenarios B/D de quickstart.md: cada botón navega a su destino existente.

### Tests for User Story 3 ⚠️ (write FIRST, must FAIL before implementation)

- [X] T033 [P] [US3] Failing test `tests/Feature/Home/DashboardNavigationTest.php`: payload exposes the ids/values the links need (`sprintPanel.id` + `sprintPanel.project.id`, `myTasks.total`, each `projects.items[].id`, each `activity.items[].task.id` when present); named targets respond 200 for the acting user: `projects.sprints.show`, `tasks.index`, `projects.index`, `tasks.show`

### Implementation for User Story 3

- [X] T034 [US3] Wire quick actions in `ActivityDashboard.vue` using Wayfinder imports (`@/routes/projects`, `@/routes/tasks`): "Ir al sprint" (visible only when sprintPanel exists, FR-011) and "Abrir tablero del sprint" → `projects.sprints.show.url(projectId, sprintId)`; "Nueva tarea" → injected `friday:open-palette` (identical to `C` shortcut and `HomeCanvas`); "Ver todas mis tareas (N)" → `tasks.index`; "Ver todos" → `projects.index`
- [X] T035 [US3] Wire task navigation in `MyTasksPanel.vue` rows and `ActivityFeed.vue` items → `tasks.show.url(id)`; run `npm run check`

**Checkpoint**: All three user stories independently functional; every dashboard action navigates to an existing destination

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Calidad transversal antes de cerrar la capacidad

- [X] T036 Run `vendor/bin/pint --dirty --format agent` and fix PHP style issues across all touched files
- [X] T037 [P] Run full suite `php artisan test --compact` and fix any regressions (especially `tests/Feature/Shell/ShellRenderingTest.php` shared-prop contract)
- [X] T038 [P] Validate quickstart.md scenarios A–D manually in browser (Herd site + `npm run dev`): empty state, panel with data, cross-user isolation, no-active-sprint case
- [X] T039 Sanity-check SC-005 on realistic seeded data (a user with several projects/tasks/activities): home response renders under 2s locally; inspect query count and add eager loading if N+1 appears

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — T001 first
- **Foundational (Phase 2)**: Depends on T001; BLOCKS all user stories (activities + completed_at required by US1 burndown/insight and US2 feed)
- **User Stories (Phase 3–5)**: All depend on Phase 2 completion; proceed sequentially P1 → P2 → P3 (stories are independent of each other but share `HomeController`/`ActivityDashboard.vue` files, so parallel work would conflict)
- **Polish (Phase 6)**: Depends on all desired user stories

### User Story Dependencies

- **US1 (P1)**: After Phase 2. Self-contained: controller + payload + panel components + empty-state regression.
- **US2 (P2)**: After Phase 2 + US1 controller exists (extends `activity` mapper and `ActivityDashboard`). Backend is a single mapper addition; UI is one component + util.
- **US3 (P3)**: After Phase 2 + US1 components exist (wires navigation into `ActivityDashboard`, `MyTasksPanel`, `ActivityFeed`). No backend changes beyond payload already exposed.

### Within Each User Story

- Tests written first and failing before implementation (T006 before T007–T010; T011–T014 before T015–T016; T028 before T029; T033 before T034)
- T017 (types) before component tasks T018–T024
- Components T018–T024 before composition T25 (`ActivityDashboard`)

### Parallel Opportunities

- Phase 2: T003 ∥ T004; T008 ∥ T009 (after T002/T003)
- US1: tests T011 ∥ T012 ∥ T013 ∥ T014; components T018 ∥ T019 ∥ T020 ∥ T021 ∥ T022 ∥ T023 ∥ T024 (all after T017)
- US2: T030 ∥ T028(test authoring); US3 single-threaded (one file)

---

## Parallel Example: User Story 1

```bash
# Tests first, all in parallel:
Task: "T011 DashboardEmptyStateTest in tests/Feature/Home/"
Task: "T012 DashboardPayloadTest in tests/Feature/Home/"
Task: "T013 SprintPanelTest in tests/Feature/Home/"
Task: "T014 WeeklyInsightTest in tests/Feature/Home/"

# Then types, then all components in parallel:
Task: "T017 dashboard.ts types"
Task: "T018 DonutProgress.vue / T019 BurndownChart.vue / T020-024 panels"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1 (T001) + Phase 2 (T002–T010): persistencia de actividad
2. Phase 3 (T011–T027): panel completo con datos reales + estado vacío preservado
3. **STOP and VALIDATE**: quickstart.md scenarios A + B; `php artisan test --compact tests/Feature/Home`
4. Deploy/demo — el panel P1 ya entrega todo el valor del spec salvo feed y navegación

### Incremental Delivery

1. Setup + Foundational → base de eventos real
2. US1 → panel P1 verde → demo
3. US2 → feed de actividad → demo
4. US3 → acciones rápidas → demo
5. Cada historia agrega valor sin romper las anteriores

### Parallel Team Strategy

Con 2+ desarrolladores tras Phase 2: Developer A → US1 (T011–T027); Developer B puede adelantar tests de US2/US3 (T028, T033) ya que solo dependen del contrato, no del código.

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to spec.md user story (US1=P1, US2=P2, US3=P3)
- Every payload shape decision defers to `contracts/dashboard-payload.md`; every data rule defers to `data-model.md`
- Verify tests fail before implementing; commit after each task or logical group
- Stop at any checkpoint to validate the story independently
- Avoid: adding `due_date`, chart libraries, dark mode, or API resources — all explicitly out of scope (research.md D5/D11, plan.md Constraints)
