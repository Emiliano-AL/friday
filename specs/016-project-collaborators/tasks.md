---
description: 'Task list for 016-project-collaborators (Gestión de colaboradores del proyecto)'
---

# Tasks: Gestión de colaboradores del proyecto

**Input**: Design documents from `/specs/016-project-collaborators/` (plan.md, spec.md, research.md, data-model.md, contracts/members-modal.md, quickstart.md)

**Prerequisites**: plan.md ✅ spec.md ✅ research.md ✅ data-model.md ✅ contracts/ ✅

**Tests**: Pest tests INCLUIDOS — constitución del proyecto (Principio III) exige Test-First donde hay lógica de dominio. Las acciones del modal consumen endpoints cuyo contrato ya está cubierto por los 7 tests existentes de `ProjectMembersTest` (NO se modifican).

**Organization**: Tasks are grouped by user story to enable independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: US1, US2, US3 (maps to spec.md user stories)
- Include exact file paths in descriptions

## Path Conventions

- Single monolithic project: `app/`, `routes/`, `resources/js/`, `tests/` at repository root
- Frontend components in `resources/js/Components/<Domain>/`; Pest tests in `tests/Feature/Projects/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Feature branch readiness

- [X] T001 Create git branch `016-project-collaborators` from the current main branch (note: branch `015-home-active-dashboard` exists uncommitted — keep this feature's changes separate and do not commit other features' files)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Extender el payload del proyecto con `openTasksCount` (derivado, 1 consulta agregada) que el modal necesita para listar miembros y advertir al eliminar (FR-002/FR-005). Basado en research.md D3/D8 y data-model.md.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T002 [P] Write failing Pest test `tests/Feature/Projects/ProjectMembersModalPayloadTest.php` (per contracts/members-modal.md invariants 1–2): `GET /projects/{id}` payload `project.owner` and each `project.members[]` include `openTasksCount` = exact count of that project's tasks with `status != Done` assigned to that person (seed Done + non-Done + standalone + foreign-project tasks to prove scoping); `owner.id` never appears inside `members[]`; non-member/foreign users still get 404 (existing access rules)
- [X] T003 [P] Update `resources/js/types/project.ts`: `ProjectDetail.owner` and `ProjectDetail.members[]` gain `openTasksCount: number` (keep existing fields `id/name/email/avatar` unchanged)
- [X] T004 Implement in `app/Http/Controllers/ProjectController.php` `projectPayload()` (~lines 204–297): compute one aggregated query over the project's tasks (`where project_id = $project->id`, `status != Done`, `assignee_id not null`, `groupBy assignee_id` → counts map) and attach `openTasksCount` to `owner` (:243-248) and each `members[]` entry (:249-254); run T002 until green without touching `ProjectMemberController` or the 7 existing member tests

**Checkpoint**: Payload test green; modal data contract ready

---

## Phase 3: User Story 1 - Modal de gestión de colaboradores (Priority: P1) 🎯 MVP

**Goal**: El propietario gestiona colaboradores desde el modal "Gestionar colaboradores" (lista propietario primero + agregar por correo + eliminar con confirmación advertida); el formulario inline desaparece de `Show.vue`.

**Independent Test**: quickstart.md escenarios A, B, C y F + `ProjectMembersModalPayloadTest` verde + los 7 tests de `ProjectMembersTest` intactos.

### Implementation for User Story 1

- [X] T005 [US1] Create `resources/js/Components/Projects/MembersModal.vue` per contracts/members-modal.md following the SprintFormModal pattern (props `{ open: boolean; project: ProjectDetail }`, emit `close`, `Components/Modal.vue` with `maxWidth="2xl"`): header "Gestionar colaboradores" + project name; add-by-email form (`useForm({email})`, Wayfinder `store` from `@/routes/projects/members`, `preserveScroll: true`, `onSuccess` resets email only, inline `errors.email`, modal stays open); members list — owner first with `UiBadge label="Propietario"` and NO remove action, then `project.members` rows with Avatar (initials fallback), name, email, "N tareas abiertas" label when `openTasksCount > 0`, and a retirar button per row (owner only); remove via `window.confirm` with warning text when `openTasksCount > 0` ("…tiene N tareas abiertas en el proyecto que quedarán sin responsable.") then Wayfinder `destroy` DELETE `preserveScroll`, modal stays open; footer button "Listo" emits `close`; `watch(() => props.open)` resets form + focuses email input on open
- [X] T006 [US1] Edit `resources/js/pages/Projects/Show.vue`: delete the inline add-member form (template ~:724-752), the `memberForm` state (~:150-152), `submitMember` (~:184-189) and the direct `removeMember` function (~:191-195); add a "Gestionar colaboradores" button in the Equipo card header area visible only `v-if="project.isOwner"` (same visibility rule the inline form had), a `membersModalOpen = ref(false)`, and render `<MembersModal :open="membersModalOpen" :project="project" @close="membersModalOpen = false" />`; keep the Equipo list section itself unchanged (it now updates via the same redirect flow)
- [X] T007 [US1] Run `npm run check` (vue-tsc + eslint scoped to your files) and `npm run build`; fix all type/lint errors in MembersModal.vue/Show.vue/types; confirm the 7 existing `ProjectMembersTest` still pass (`php artisan test --compact tests/Feature/Projects`)

**Checkpoint**: US1 fully functional — modal opens from project detail, inline form gone, add/remove work against the preserved endpoints (quickstart scenarios A–C, F)

---

## Phase 4: User Story 2 - Funciones no disponibles deshabilitadas (Priority: P2)

**Goal**: El modal refleja el diseño completo con las funciones no implementadas (roles/permisos, enlace rápido) visibles pero deshabilitadas con "Próximamente", y texto honesto que no promete correos ni menciones (FR-007/FR-009).

**Independent Test**: quickstart.md scenario D — elementos deshabilitados presentes, ninguno ejecuta acción ni navega.

### Implementation for User Story 2

- [X] T008 [US2] Add the "Permisos del proyecto" column to the members list in `resources/js/Components/Projects/MembersModal.vue`: owner row shows `UiBadge label="Propietario"`; each collaborator row shows a role selector displaying the generic current-system role "Editor" that is disabled (`aria-disabled="true"`, `disabled` control, `opacity-60 cursor-not-allowed`, no change handlers) with `UiBadge label="Próximamente" tone="outline"` — following the in-modal disabled-section precedent at `resources/js/Components/Sprints/SprintFormModal.vue:193-220`
- [X] T009 [US2] Add the disabled "Enlace de invitación rápida" section to `resources/js/Components/Projects/MembersModal.vue`: explanatory label "Cualquiera con el enlace de la organización podría unirse" + "Copiar enlace" button rendered disabled (`disabled`, lock icon, `UiBadge "Próximamente"`, no click handler); verify no pending-invitation UI is rendered (the system has no such state — research.md D4)
- [X] T010 [US2] Set the modal footer in `resources/js/Components/Projects/MembersModal.vue` to the honest copy "Los colaboradores podrán recibir tareas asignadas de este proyecto."; audit every label in the modal to remove any promise of unimplemented behavior (invitation emails, mentions, differentiated permissions); run `npm run check` clean

**Checkpoint**: US1 + US2 — modal matches the Stitch design with honest disabled states (quickstart scenario D)

---

## Phase 5: User Story 3 - Asignación de tareas limitada a colaboradores (Priority: P3)

**Goal**: Cerrar el gap de asignación (research.md D6): el propietario aparece en las opciones de responsable de Mis Tareas y el detalle de sprint; la validación de servidor existente queda respaldada por regresión.

**Independent Test**: quickstart.md scenario E — opciones = propietario + colaboradores; externo rechazado por el servidor.

### Tests for User Story 3 ⚠️ (write FIRST, must FAIL before implementation)

- [X] T011 [US3] Write failing Pest test `tests/Feature/Projects/ProjectAssigneeOptionsTest.php`: (1) `GET /tasks` payload `projects[].members` includes the project's owner (exactly once, deduplicated against pivot members) for every project the user can see; (2) regression — assigning a project task to a non-collaborator user is rejected with session error "El responsable debe ser miembro del proyecto." through the existing form requests (`POST /projects/{project}/tasks` and global `POST /tasks` with `project_id`)

### Implementation for User Story 3

- [X] T012 [US3] Fix `app/Http/Controllers/TaskController.php` `index()` (~:35-57): build each `projects[]` option's `members` as owner + pivot members (dedupe by id, same shape `{id, name, avatar}`); then verify the task-context payload used by `resources/js/pages/Sprints/Show.vue` (sprint/taskContext mapper in the sprint controller) and give it the same owner-inclusive members treatment if it omits the owner; run T011 until green with no changes to the form-request validation rules
- [X] T013 [US3] Run `npm run check` + `npm run build`; confirm `resources/js/Components/Tasks/Board/TaskFormModal.vue` needs no changes (it already dedupes `currentUser`) and types still compile

**Checkpoint**: All three stories independently functional (quickstart scenarios A–F)

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Calidad transversal antes de cerrar la capacidad

- [X] T014 Run `vendor/bin/pint --dirty --format agent` and fix PHP style across touched files (ProjectController.php, TaskController.php, new tests)
- [X] T015 [P] Run full suite `php artisan test --compact` — all green including the 7 untouched `ProjectMembersTest` cases and both new test files
- [X] T016 [P] Validate quickstart.md scenarios A–F manually in browser (Herd site + built assets): modal open/close, add success + 3 error cases, remove with/without open-task warning, disabled sections no-op, assignee options include owner, non-owner sees no manage button
- [X] T017 [P] Spec traceability pass: confirm FR-001–FR-009 and SC-001–SC-005 are each covered by at least one test or quickstart scenario; note any gap in the completion report

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: T001 first
- **Foundational (Phase 2)**: Depends on T001; BLOCKS US1 (modal needs `openTasksCount`) but not US2/US3 work in isolation
- **User Stories (Phase 3–5)**: US1 after Phase 2; US2 after US1 (same file `MembersModal.vue`); US3 independent of US1/US2 (backend-only files) and can run in parallel with them if desired
- **Polish (Phase 6)**: After all desired stories

### User Story Dependencies

- **US1 (P1)**: After Phase 2. Shares `MembersModal.vue` with US2 → sequential.
- **US2 (P2)**: After US1 (extends the same component). No backend.
- **US3 (P3)**: Independent — touches only `TaskController.php` + sprint task-context payload + one new test file. Can start right after Phase 2 in parallel with US1 by a second developer.

### Within Each User Story

- US3: test T011 written and failing before T012 implementation.
- US1/US2 are UI stories verified by `npm run check`, build, manual quickstart scenarios and the preserved endpoint contract (the project has no frontend test harness).

### Parallel Opportunities

- Phase 2: T002 ∥ T003
- US3 test authoring (T011) ∥ US1 implementation (T005–T007) — different files entirely
- Polish: T015 ∥ T016 ∥ T017

---

## Parallel Example: User Story 1 + US3 (two developers)

```bash
# Developer A — US1 (after Phase 2):
Task: "T005 MembersModal.vue"
Task: "T006 Show.vue wiring"
Task: "T007 npm run check + build"

# Developer B — US3 (after Phase 2, in parallel):
Task: "T011 ProjectAssigneeOptionsTest.php (failing first)"
Task: "T012 TaskController + sprint taskContext owner-inclusive members"
Task: "T013 checks/build"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1 (T001) + Phase 2 (T002–T004): payload con `openTasksCount`
2. Phase 3 (T005–T007): modal funcional completo reemplazando el inline form
3. **STOP and VALIDATE**: quickstart scenarios A–C + F; suite verde con contrato de endpoints intacto
4. Deploy/demo — el valor del spec ya está entregado (gestión real en modal)

### Incremental Delivery

1. Setup + Foundational → datos del modal listos
2. US1 → modal funcional → demo
3. US2 → diseño completo con estados deshabilitados honestos → demo
4. US3 → coherencia total de asignación → demo
5. Cada historia agrega valor sin romper las anteriores

### Parallel Team Strategy

Con 2 desarrolladores tras Phase 2: Developer A → US1+US2 (frontend, secuencial); Developer B → US3 (backend + test) en paralelo. Integración sin conflictos de archivos (A toca `Components/Projects/*` + `Show.vue`; B toca `TaskController.php` + test).

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to spec.md user story (US1=P1, US2=P2, US3=P3)
- The 7 existing `ProjectMembersTest` cases are the frozen contract — never edit them; if one fails, the implementation (not the test) is wrong
- Do NOT create: role column in DB, invitation tables, notification emails, or a confirm-dialog component — all explicitly out of scope (research.md D4/D7)
- Modal copy is Spanish, hardcoded, honest (FR-009); disabled UI follows `SprintFormModal.vue:193-220` precedent
- Stop at any checkpoint to validate the story independently
