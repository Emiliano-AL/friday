---
description: 'Task list for 017-sprints-section (Sección Sprints del detalle de proyecto)'
---

# Tasks: Sección Sprints del detalle de proyecto

**Input**: Design documents from `/specs/017-sprints-section/` (plan.md, spec.md, research.md, data-model.md, contracts/sprints-section.md, quickstart.md)

**Prerequisites**: plan.md ✅ spec.md ✅ research.md ✅ data-model.md ✅ contracts/ ✅

**Tests**: Sin tests PHP nuevos — cero lógica de dominio nueva (solo derivaciones client-side de datos existentes, plan.md D2/D9); el contrato del payload queda protegido por los tests existentes (`ProjectShowPayloadTest`, `SprintCrudTest`, `SprintAccessTest`, `TaskBoardTest`), que deben seguir verdes sin modificación. QA frontend: `npm run check` + escenarios manuales.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: US1, US2, US3 (maps to spec.md user stories)
- Include exact file paths in descriptions

## Path Conventions

- Single monolithic project: `app/`, `resources/js/` at repository root
- Componentes en `resources/js/Components/<Domain>/`; utilidades puras en `resources/js/utils/`; página en `resources/js/pages/Projects/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Feature branch readiness

- [X] T001 Create git branch `017-sprints-section` from the current base branch (note: branches 015/016 exist with uncommitted work — do not commit or modify their files)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Helpers puros de derivación que bloquean el componente de US1 (research.md D2). Sin backend.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T002 Create `resources/js/utils/sprint.ts` with two pure typed helpers per data-model.md/contracts (no Vue imports, unit-testable by types): (1) `sprintMetrics(sprint: { id: number }, tasks: Array<{ status: string; sprint?: { id: number } | null }>) => { total: number; done: number; coverage: number }` — total = tasks with `sprint?.id === sprint.id`, done = those with `status === 'done'`, coverage = `total === 0 ? 0 : Math.round(done / total * 100)`; (2) `weekdayDaysRemaining(today: Date, endDate: string) => number` — espejo laborable de `ProjectSprintController::sprintDetail()` (ProjectController mirroring in `app/Http/Controllers/ProjectSprintController.php:171-180`): cursor = mañana (today+1 día), contar días lun–dom excluyendo sábado y domingo mientras cursor <= endDate; 0 si endDate < mañana; parse endDate como fecha local (`new Date(endDate + 'T00:00:00')`)

**Checkpoint**: helpers compilando con `npx vue-tsc --noEmit` sin errores

---

## Phase 3: User Story 1 - Sección Sprints rediseñada (Priority: P1) 🎯 MVP

**Goal**: La pestaña "Sprints y tareas" muestra la sección completa: encabezado con total + chips filtro con conteos + "Planificar un sprint"; tarjeta expandida del activo (badge, fechas, días restantes, meta, progreso %, "X de Y tareas", "Ver tablero", "Completar sprint" con permisos); lista "Otros Sprints" con filas por estado (fechas, tareas, % completado, meta; Editar/Eliminar en planificados —solo propietario—, "Ver resumen" en completados).

**Independent Test**: quickstart.md escenarios A, B, C y D + suite PHP existente verde + pestaña General sin cambios (regresión).

### Implementation for User Story 1

- [X] T003 [US1] Create `resources/js/Components/Sprints/ProjectSprintsSection.vue` per contracts/sprints-section.md with props `{ project: ProjectDetail; tasks: BoardTask[]; canWriteSprints: boolean }` and emits `{ create: []; edit: [SprintSummary]; destroy: [SprintSummary]; complete: [ActiveSprint] }`; use helpers from `@/utils/sprint` (T002) and `SprintStatusValue` from `@/types/sprint`: header "Sprints · N en total" + chips Todos/Activo (a)/Planificados (p)/Completados (c) with real counts (`ref` filter state, applies to the rows list) + "Planificar un sprint" button (emits `create`); active card (only when `project.activeSprint`): `UiBadge label="Sprint Activo" tone="primary"` with dot, name linking to `show` route (Wayfinder `@/routes/projects/sprints`), dates es-MX short (`startDate — endDate`), "N días restantes" via `weekdayDaysRemaining`, meta block labeled "Meta del Sprint" only when `goal` non-null, "Progreso de tareas" with coverage % bar + "X de Y tareas" from `sprintMetrics`, actions "Ver tablero" (all users) and "Completar sprint" (only `canWriteSprints`, emits `complete`); "Otros Sprints" header with rows = `project.sprints` minus `activeSprint` filtered by chip: each row = name link to show, `UiBadge` statusLabel (tone: planned `outline`, completed `neutral`), dates, "· N tareas planificadas" (planned) or "· N de N tareas concluidas" (completed), "N% completado" via `sprintMetrics`, truncated goal when present; row actions: planned → "Editar" (emits `edit`) + "Eliminar" (`window.confirm` with the project's existing delete message then emits `destroy`), completed → "Ver resumen" link to show; empty states per contract (no sprints / no active / filter empty); Spanish copy, Tailwind tokens only, `aria-disabled` only where the contract says
- [X] T004 [US1] Edit `resources/js/pages/Projects/Show.vue`: replace the whole "Sprints y tareas" tab content block (the `<ul>` + "Planificar un sprint" template, ~lines 711-884, inside `v-else-if="activeTab === 'sprints'"` or equivalent) with `<ProjectSprintsSection :project="project" :tasks="project.tasks" :can-write-sprints="canWriteSprints" @create="openSprintCreate" @edit="openSprintEdit" @destroy="confirmSprintDestroy" @complete="onSprintComplete" />`; add `import ProjectSprintsSection from '@/Components/Sprints/ProjectSprintsSection.vue'`; add `function onSprintComplete(sprint)` — `window.confirm(`¿Completar el sprint "${sprint.name}"? Esta acción no se puede deshacer.`)` then `router.post(completeSprint.url([project.id, sprint.id]), { preserveScroll: true })` importing `complete as completeSprint` from `@/routes/projects/sprints` (mirror the general-tab precedent); delete now-unused template code and any orphaned imports/helpers only if unreferenced elsewhere (check `nextSprint`, `activeSprintProgress`, `goToSprintsTab` usages first — they are used by the General tab and MUST stay); do NOT touch the General tab
- [X] T005 [US1] Run `npm run check` and `npm run build` fixing all errors in your files; then run `php artisan test --compact` confirming the full suite stays green with zero backend changes (existing payload tests untouched)

**Checkpoint**: US1 fully functional — pestaña "Sprints y tareas" renderiza la sección completa con datos reales; pestaña General intacta (quickstart A–C + regresión)

---

## Phase 4: User Story 2 - Elementos no implementados deshabilitados (Priority: P2)

**Goal**: El diseño queda completo pero honesto: "Puntos de historia" aparece deshabilitado con "Próximamente" en la tarjeta activa, las filas no insinúan métricas inexistentes y ningún texto promete datos que el sistema no calcula (FR-006/FR-007).

**Independent Test**: quickstart.md scenario D — todo dato visible es real; "Próximamente" no ejecuta acciones.

### Implementation for User Story 2

- [X] T006 [US2] In `resources/js/Components/Sprints/ProjectSprintsSection.vue` active card: add the disabled story-points element after the progress block — bordered container `aria-disabled="true"` with lock icon, label "Puntos de historia", caption "Estimación en puntos y velocidad del equipo." and `<UiBadge label="Próximamente" tone="outline" />` (precedent `resources/js/Components/Sprints/SprintFormModal.vue:193-220`); audit the whole component: rows must NOT contain any points/velocity text or placeholder values; chips, counts, %, days and copy must only assert real data; remove/rewrite any label that promises unimplemented metrics
- [X] T007 [US2] Run `npm run check` clean for the component

**Checkpoint**: US1 + US2 — sección fiel al diseño con estados honestos (quickstart D)

---

## Phase 5: User Story 3 - Colapsar y expandir "Otros Sprints" (Priority: P3)

**Goal**: La lista "Otros Sprints" se colapsa/expande con un control visible, con estado de sesión (FR-008).

**Independent Test**: quickstart.md scenario E.

### Implementation for User Story 3

- [X] T008 [US3] In `resources/js/Components/Sprints/ProjectSprintsSection.vue`: add `const othersCollapsed = ref(false)` and a control button in the "Otros Sprints" header toggling it (labels "Colapsar todos" / "Expandir" with `AppIcon` `expand_less`/`expand_more`); wrap the rows container in `v-show="!othersCollapsed"` (keep state client-side only — spec requires session persistence only)
- [X] T009 [US3] Run `npm run check` + `npm run build` clean

**Checkpoint**: All three stories independently functional (quickstart A–E)

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Validación transversal antes de cerrar la capacidad

- [X] T010 [P] Run full verification: `php artisan test --compact` (all green, backend untouched), `npm run check`, `npm run build`; fix anything that surfaces
- [X] T011 [P] Validate quickstart.md scenarios A–E manually in browser (Herd site + built assets) including the General-tab regression check; exercise edge cases: project without sprints, active sprint without goal, completed sprint without tasks, filter with zero results, non-owner view without mutation actions
- [X] T012 [P] Spec traceability pass: confirm FR-001–FR-008 and SC-001–SC-005 are each covered by at least one quickstart scenario or existing test; note any gap in the completion report

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: T001 first
- **Foundational (Phase 2)**: T002 blocks US1 (component imports the helpers); US2/US3 also live in that component but add no helper needs
- **User Stories (Phase 3–5)**: US1 after Phase 2; US2 and US3 after US1 (same file `ProjectSprintsSection.vue` → sequential, not parallel)
- **Polish (Phase 6)**: After all desired stories

### User Story Dependencies

- **US1 (P1)**: After Phase 2. Self-contained: helpers + component + Show.vue wiring.
- **US2 (P2)**: After US1 (extends the same component).
- **US3 (P3)**: After US1 (same component). US2 and US3 can run in either order between themselves but not concurrently (same file).

### Within Each User Story

- US1: T003 (component) → T004 (wiring) → T005 (verification). No failing-test step possible (frontend-only, no harness) — verification = types/build/suite + manual scenarios.
- US2/US3: single-file additive edits followed by type check.

### Parallel Opportunities

- Phase 6: T010 ∥ T011 ∥ T012
- Phase 2 helper authoring can overlap with nothing else (blocks US1); no other [P] pairs exist (single component + single page file)

---

## Parallel Example: (single-developer feature)

```bash
# Secuencia única recomendada:
Task: "T002 utils/sprint.ts helpers"
Task: "T003 ProjectSprintsSection.vue"
Task: "T004 Show.vue wiring + onSprintComplete"
Task: "T005 checks + suite"
Task: "T006-T007 US2 disabled points"
Task: "T008-T009 US3 collapse"
Task: "T010-T012 polish"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1 (T001) + Phase 2 (T002): helpers de derivación
2. Phase 3 (T003–T005): sección completa en la pestaña "Sprints y tareas"
3. **STOP and VALIDATE**: quickstart scenarios A–C + regresión de la pestaña General; suite verde
4. Deploy/demo — la sección ya entrega todo el valor operativo del spec

### Incremental Delivery

1. Setup + Foundational → helpers listos
2. US1 → sección funcional → demo
3. US2 → diseño completo con estados honestos → demo
4. US3 → colapso de "Otros Sprints" → demo
5. Cada historia agrega valor sin romper las anteriores

### Parallel Team Strategy

Feature de un solo desarrollador (un componente + una página). Si se dividiera: Developer A → T002+T003 (componente); Developer B no tiene bloque paralelo real hasta Phase 6.

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to spec.md user story (US1=P1, US2=P2, US3=P3)
- **Congelado**: el backend (`ProjectController`, `ProjectSprintController`, payloads) y los tests existentes no se tocan; si un test de payload falla, el bug está en la implementación
- **No crear**: endpoints, migraciones, columna de puntos de historia, estado de colapso persistente (localStorage/BD) — todo fuera de alcance (research.md D2/D5/D7)
- Las tarjetas de la pestaña **General** ("Sprint Actual", "Próximo Sprint", Backlog) NO se modifican (research.md D1)
- Copy en español hardcodeado, tokens Tailwind existentes, modo claro únicamente
- Stop at any checkpoint to validate the story independently

---

## Phase 7: Convergence

**Purpose**: Trabajo restante detectado por `/skill:speckit-converge` contra spec.md (edge cases de presentación, gaps parciales LOW). Cero cambios de comportamiento funcional.

- [X] T013 Add bounded scrolling to the "Otros Sprints" list in `resources/js/Components/Sprints/ProjectSprintsSection.vue` per the spec edge case "Muchos sprints históricos" (partial): give the rows `<ul>` a max height (e.g. `max-h-80` / `max-h-96`) with `overflow-y-auto pr-space-xs` so the section scrolls internally instead of growing the page without limit; keep the empty-state `<li>` visible outside the scroll area or ensure it renders correctly when the list is empty
- [X] T014 Clamp the active sprint goal with ellipsis in `resources/js/Components/Sprints/ProjectSprintsSection.vue` per the spec edge case "meta extensa" (partial): add `line-clamp-3` (or equivalent) to the goal paragraph inside the "Meta del Sprint" block so long goals truncate with ellipsis instead of wrapping unbounded; verify `npm run check` and `npm run build` stay clean after both edits
