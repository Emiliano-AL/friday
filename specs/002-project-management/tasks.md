---
description: 'Task list for 002-project-management'
---

# Tasks: Gestión de Proyectos (CRUD, métricas y colaboradores)

**Input**: Design documents from `/specs/002-project-management/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/web-projects-surface.md, quickstart.md

**Tests**: INCLUIDOS — obligatorios por Principio III de la constitución (Test-First con Pest, NON-NEGOTIABLE). Los tests de cada historia deben escribirse PRIMERO y FALLAR antes de la implementación.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: User story label (US1–US4, según spec.md)
- Include exact file paths in descriptions

## Path Conventions

Single project (Laravel monolith). Rutas reales según `plan.md` — `app/`, `routes/`, `resources/js/pages/`, `tests/Feature/Projects/`, `database/migrations/`, `database/factories/`.

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Baseline verde antes de tocar código (no hay dependencias nuevas)

- [x] T001 Run baseline verification: `composer install && npm install && composer ci:check` passes on the current tree before any change (feature 001 must be green)

**Checkpoint**: Baseline verde confirmado

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Esquema de datos, enum, modelo, policy y rutas que TODAS las historias necesitan

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T002 Create backed enum `app/Enums/ProjectStatus.php` (string) with cases `Active = 'active'`, `Archived = 'archived'`, `Completed = 'completed'` and method `transitions(): array` — `Active ⇒ [Archived, Completed]`, `Archived ⇒ [Active]`, `Completed ⇒ [Active]` (constraints verbatim de data-model.md)
- [x] T003 Create migration `database/migrations/2026_09_20_000001_create_projects_table.php` via `php artisan make:migration create_projects_table`: `owner_id` FK → `users.id` (cascadeOnDelete), `title` string(255), `description` text nullable, `status` string default `'active'`, timestamps; `index('owner_id')`
- [x] T004 Create migration `database/migrations/2026_09_20_000002_create_project_user_table.php` via `php artisan make:migration create_project_user_table`: `project_id` FK → `projects.id` (cascadeOnDelete), `user_id` FK → `users.id` (cascadeOnDelete), timestamps; `unique(['project_id', 'user_id'])`
- [x] T005 Run `php artisan migrate` on the development database and verify both tables exist (`migrate:status` shows Ran)
- [x] T006 Create `app/Models/Project.php` + `database/factories/ProjectFactory.php`: model with `#[Fillable(['owner_id', 'title', 'description', 'status'])]`, cast `status => ProjectStatus::class`, relations `owner(): BelongsTo`, `members(): BelongsToMany` (via `project_user`), helpers `isOwnedBy(User $user): bool`, `isMember(User $user): bool`, and `progressPercentage(): int` returning `0` with a docblock noting the future formula (round(done/total*100), total=0 ⇒ 0); factory definition (title, nullable description, owner via `User::factory()`, status active) with states `archived()`, `completed()` and `withMembers(int $count)` using `->hasAttached(User::factory()->count($n), [], 'members')`
- [x] T007 [P] Update `app/Models/User.php`: add relations `ownedProjects(): HasMany` → Project and `memberProjects(): BelongsToMany` → Project via `project_user`; update the class PHPDoc
- [x] T008 Create `app/Policies/ProjectPolicy.php`: `view` = owner or pivot member (non-member answers 404 per contract); `update`, `delete`, `transition`, `manageMembers` = owner only; verify Laravel 11+ policy auto-discovery maps `Project` → `ProjectPolicy` (register in `AppServiceProvider` only if discovery fails)
- [x] T009 Update `routes/web.php` (inside the `auth` scope): `Route::resource('projects', ProjectController::class)` (names per contract: `projects.index/create/store/show/edit/update/destroy`) plus nested `Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store')` and `Route::delete('projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy')`; add `Route::put('projects/{project}/status', [ProjectController::class, 'status'])->name('projects.status')`

**Checkpoint**: Foundation ready — tablas migradas, enum/modelo/policy/rutas en su lugar

---

## Phase 3: User Story 1 - Crear y listar mis proyectos (Priority: P1) 🎯 MVP

**Goal**: Un usuario autenticado crea un proyecto (título obligatorio, descripción opcional), queda como propietario y lo ve en su lista con estado y avance (US1, FR-001/002/012).

**Independent Test**: quickstart S1 + S5: crear válido (redirect a ficha, propietario fijado, estado activo), validaciones, y la lista solo muestra proyectos propios/miembro.

### Tests for User Story 1 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T010 [US1] Create `tests/Feature/Projects/ProjectCrudTest.php` (Pest) — create + index portion: valid creation stores project with authenticated user as `owner_id`, default status `active`, redirects to `projects.show`; missing/too-long title rejected with `errors` and nothing created; `GET /projects` lists owned projects with title/status/progress but never foreign projects

### Implementation for User Story 1

- [x] T011 [US1] Create `app/Http/Requests/StoreProjectRequest.php`: `title` required string max:255, `description` nullable string; `authorize(): true`
- [x] T012 [US1] Create `app/Http/Controllers/ProjectController.php` with `index()` (projects where owner or member, ordered by `updated_at` desc, with progress via `progressPercentage()`), `create()`, `store()` (create with `owner_id = $request->user()->id`, redirect to `route('projects.show')`); leave `show/edit/update/destroy/status` as TODO stubs that throw `BadMethodCallException` (implemented in US2)
- [x] T013 [P] [US1] Create `resources/js/pages/Projects/Index.vue` (`<script setup lang="ts">`): list of projects with title, status label and progress %, "Nuevo proyecto" link; empty state; props typed per contract
- [x] T014 [P] [US1] Create `resources/js/pages/Projects/Create.vue`: form with title (required, max 255) and description inputs, error rendering, submit via Wayfinder to `projects.store`
- [x] T015 [US1] Add "Proyectos" link to `resources/js/Layouts/AuthenticatedLayout.vue` navigation (Wayfinder `projects.index`, active-state via `page.url.startsWith('/projects')`); rebuild Wayfinder routes (`npm run build`) and run quickstart S1/S5 green

**Checkpoint**: User Story 1 fully functional and testable independently

---

## Phase 4: User Story 2 - Ver, editar y gestionar el ciclo de vida (Priority: P1) 🎯 MVP

**Goal**: Ficha del proyecto con edición de campos, transiciones de estado activo↔archivado/completado, y eliminación con disolución de membresías (US2, FR-003/004/005/011).

**Independent Test**: quickstart S2 + S6: editar persiste, archivar/reactivar/completar funcionan, transición ilegal rechazada, eliminar disuelve membresías.

### Tests for User Story 2 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T016 [P] [US2] Extend `tests/Feature/Projects/ProjectCrudTest.php` — edit + delete portion: `PUT /projects/{id}` persists title/description and redirects to show; non-owner gets 404 on edit/update/destroy; `DELETE` removes project and its `project_user` rows and redirects to index
- [x] T017 [P] [US2] Create `tests/Feature/Projects/ProjectLifecycleTest.php`: owner can transition active→archived, archived→active, active→completed, completed→active (redirect to show, status persisted); illegal transitions (archived→completed, completed→archived) rejected with `errors` and status unchanged; non-owner 404 on status endpoint

### Implementation for User Story 2

- [x] T018 [US2] Create `app/Http/Requests/UpdateProjectRequest.php` (same rules as store; authorize: owner-only via `ProjectPolicy::update`)
- [x] T019 [US2] Implement `show()` (props per contract: `project` with owner, members, `allowedTransitions` from `ProjectStatus::transitions()`, `progress`), `edit()`, `update()`, `destroy()` (dissolves memberships via cascade, redirect to index), and `status(Request $request, Project $project)` (validate `status` is a permitted transition, save, redirect to show) in `app/Http/Controllers/ProjectController.php`; enforce policy authorization with 404 for non-members/non-owners per contract
- [x] T020 [P] [US2] Create `resources/js/pages/Projects/Show.vue`: ficha with title/description/status label/progress, members list (read-only here), transition buttons only for owner and only from `allowedTransitions`, delete button with confirm (owner only), read-only banner when status ≠ active
- [x] T021 [P] [US2] Create `resources/js/pages/Projects/Edit.vue`: form reusing create layout, Wayfinder submit to `projects.update` (PUT)
- [x] T022 [US2] Rebuild Wayfinder (`npm run build`), run `npm run types:check`, and verify US2 via quickstart S2/S6/S9 green

**Checkpoint**: User Stories 1 AND 2 both work independently — MVP completo (ambas P1)

---

## Phase 5: User Story 3 - Métricas de avance del proyecto (Priority: P2)

**Goal**: El avance (0% sin tareas hoy) se muestra idéntico e entre 0–100 en índice y ficha, con el punto único de cálculo acotado para el módulo de tareas (US3, FR-006, SC-003).

**Independent Test**: quickstart S3 + S9: proyecto sin tareas muestra 0% en ambas vistas; el método del modelo es el único consumido por ambas props.

### Tests for User Story 3 ⚠️

- [x] T023 [US3] Create `tests/Feature/Projects/ProjectProgressTest.php`: a project without tasks reports `progressPercentage() === 0` and the index/show props both carry `0`; the value is always within 0–100; the method exists on `App\Models\Project` (contract seam for the tasks module)

### Implementation for User Story 3

- [x] T024 [US3] Confirm `App\Models\Project::progressPercentage()` returns `0` and is the single source used by both `ProjectController::index()` and `show()` props (no duplicated calculation in views or controller); adjust if any view computes its own value

**Checkpoint**: User Story 3 independently functional

---

## Phase 6: User Story 4 - Asignación de colaboradores (Priority: P2)

**Goal**: El propietario añade/retira colaboradores por correo; colaboradores ven el proyecto; duplicados, correos inexistentes y retiro del propietario quedan bloqueados (US4, FR-007/008/009/010).

**Independent Test**: quickstart S4 + S7/S8: alta/retiro reflejado en ambos usuarios, validaciones de alta, acceso de no miembros 404.

### Tests for User Story 4 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T025 [P] [US4] Create `tests/Feature/Projects/ProjectMembersTest.php`: owner adds an existing user by email (member appears, project appears in collaborator's index); duplicate add rejected with `errors` and no duplicate row; unregistered email rejected; owner removes collaborator (membership row gone, project gone from their index); owner cannot remove themselves; non-owner gets 404 on store/destroy
- [x] T026 [P] [US4] Create `tests/Feature/Projects/ProjectAccessTest.php`: non-member gets 404 on show/edit/update/destroy/status/members routes for an existing project; `GET /projects` never lists foreign projects; unauthenticated requests redirect to login

### Implementation for User Story 4

- [x] T027 [US4] Create `app/Http/Controllers/ProjectMemberController.php`: `store()` validates `email` required+email+`exists:users`, rejects users already members (including owner) with `errors`, attaches via `$project->members()->attach($user)`; `destroy()` detaches only if the target is a current member (404 otherwise), never the owner; policy `manageMembers` (owner) on both, 404 for non-owners
- [x] T028 [US4] Add member management to `resources/js/pages/Projects/Show.vue` (owner only): add-collaborator form (email input + submit via Wayfinder to `projects.members.store`), remove buttons per member (Wayfinder delete to `projects.members.destroy`), error rendering
- [x] T029 [US4] Rebuild Wayfinder, run `npm run types:check`, and verify US4 via quickstart S4/S7/S8 green

**Checkpoint**: All four user stories independently functional

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Quality gates y validación completa

- [x] T030 [P] Run full quality gates: `composer ci:check` (vp check, pint --test, phpstan, full test suite) — all must pass; run `vendor/bin/pint --dirty --format agent` first if PHP files were touched
- [x] T031 Run quickstart.md validation S1–S10 end-to-end (manual S1–S4 + automated S5–S9) and confirm success criteria SC-001–SC-006 from spec.md

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — empieza de inmediato
- **Foundational (Phase 2)**: Depends on Setup — BLOCKS all user stories (T002 antes que T006; T003/T004 antes que T005; T006 antes que T008; T007/T008 ∥ resto)
- **User Stories (Phase 3–6)**: All depend on Foundational; luego en orden US1 → US2 → US3 → US4 (US1 y US2 son P1 y conforman el MVP)
- **Polish (Phase 7)**: Depends on all four stories complete

### User Story Dependencies

- **US1 (P1)**: Foundational only
- **US2 (P1)**: Foundational + US1 comparten `ProjectController` y páginas; el MVP requiere ambas
- **US3 (P2)**: Foundational + US2 (las props de Index/Show deben existir); es verificación más que desarrollo
- **US4 (P2)**: Foundational + US2 (la gestión de miembros vive en `Show.vue`); sus tests son independientes

### Within Each User Story

- Tests MUST exist and FAIL before implementation tasks start
- Requests before controller actions; controller before pages; per story: tests → backend → frontend → verify

### Parallel Opportunities

- T007 ∥ (T002–T006, T008–T009) — distinto archivo
- T013 ∥ T014 (páginas distintas); T016 ∥ T017, T020 ∥ T021, T025 ∥ T026 (tests/páginas distintos)
- US3 puede avanzar en paralelo a US4 si se coordina `Show.vue` (T024 solo toca controller/modelo)

---

## Parallel Example: User Story 2

```bash
# Tests first, juntos:
Task: "Extend tests/Feature/Projects/ProjectCrudTest.php (edit + delete)"
Task: "Create tests/Feature/Projects/ProjectLifecycleTest.php"

# Luego páginas en paralelo:
Task: "Create resources/js/pages/Projects/Show.vue"
Task: "Create resources/js/pages/Projects/Edit.vue"
```

---

## Implementation Strategy

### MVP First (User Stories 1 + 2)

1. Complete Phase 1 (Setup) + Phase 2 (Foundational)
2. Complete Phase 3 (US1) → validar con quickstart S1/S5
3. Complete Phase 4 (US2) → validar con S2/S6/S9
4. **STOP and VALIDATE**: ambas historias P1 verdes — MVP de gestión de proyectos
5. Desplegar/demo

### Incremental Delivery

1. Setup + Foundational → foundation ready
2. US1 → alta/listado funcionando
3. US2 → ficha/ciclo de vida/eliminación funcionando (MVP completo)
4. US3 → métricas visibles y acotadas
5. US4 → colaboración multiusuario
6. Polish → quality gates + validación completa

### Parallel Team Strategy

Con dos desarrolladores tras la Fase 2:

- Developer A: US1 (T010–T015)
- Developer B: prepara T016/T017 (tests de US2) y T025/T026 (tests de US4) — todos contra el controlador aún en stubs; coordinar `ProjectController.php` (T012 vs T019) y `Show.vue` (T020 vs T028)

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to spec user story (US1–US4)
- Cada historia es independiente y testeable por sí sola (quickstart la respalda)
- Tests MUST fail antes de implementar (Principio III, NON-NEGOTIABLE)
- Los constraints de campos (longitudes, nullable, valores de enum, transiciones) se citan verbatim de data-model.md en las tareas
- Commit after each task or logical group

---

## Phase 8: Convergence

**Purpose**: Trabajo restante detectado al cotejar el código contra spec/plan/tasks tras la implementación

- [x] T032 Add an explicit feature test that a project title of only whitespace is rejected on create and update (`POST /projects` and `PUT /projects/{id}` with `title: '   '` must return validation errors and create/update nothing); the runtime behavior currently relies on the framework's default `TrimStrings`/`ConvertEmptyStringsToNull` middleware — if the test fails, tighten the rules in `app/Http/Requests/StoreProjectRequest.php` and `UpdateProjectRequest.php` instead of relying on defaults per spec Edge Cases (partial)
- [ ] T033 Run the manual quickstart validation in a browser per quickstart.md: S1 (create/list flows and error messages), S2 (edit/lifecycle/delete), S3 (progress display), S4 (collaborators), and record the SC-001 (< 1 min creation), SC-002 (< 2 s list load with up to 50 projects) and SC-006 (90% of owners complete create-edit-archive unaided) results per spec Success Criteria (partial)
