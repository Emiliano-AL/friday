---
description: 'Task list for 003-sprint-crud'
---

# Tasks: Gestión de Sprints (CRUD y fechas)

**Input**: Design documents from `/specs/003-sprint-crud/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/web-sprints-surface.md, quickstart.md

**Tests**: INCLUIDOS — obligatorios por Principio III de la constitución (Test-First con Pest, NON-NEGOTIABLE). Los tests de cada historia deben escribirse PRIMERO y FALLAR antes de la implementación.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: User story label (US1–US4, según spec.md)
- Include exact file paths in descriptions

## Path Conventions

Single project (Laravel monolith). Rutas reales según `plan.md` — `app/`, `routes/`, `resources/js/pages/`, `resources/js/Components/`, `tests/Feature/Sprints/`, `database/migrations/`, `database/factories/`.

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Baseline verde antes de tocar código (no hay dependencias nuevas)

- [x] T001 Run baseline verification: `composer install && npm install && composer ci:check` passes on the current tree before any change (features 001–002 deben estar verdes)

**Checkpoint**: Baseline verde confirmado

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Esquema, modelo, policy y rutas que TODAS las historias necesitan

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T002 Create migration `database/migrations/2026_09_21_000001_create_sprints_table.php` via `php artisan make:migration create_sprints_table --no-interaction`: `project_id` FK → `projects.id` (`cascadeOnDelete`), `name` string(255), `start_date` date, `end_date` date, timestamps; `index(['project_id', 'start_date'])` (constraints verbatim de data-model.md; research R-1/R-9)
- [x] T003 Run `php artisan migrate` on the development database and verify `sprints` exists with the composite index (`migrate:status` shows Ran)
- [x] T004 Create `app/Models/Sprint.php` + `database/factories/SprintFactory.php`: model con `#[Fillable(['project_id', 'name', 'start_date', 'end_date'])]`, casts `start_date`/`end_date` a `date`, relación `project(): BelongsTo`; PHPDoc de propiedades; factory con `name` (words), `start_date` (fecha pasada cercana), `end_date` (= `start_date` + intervalo corto), `for(Project)` — sin estado propio del sprint (research R-3)
- [x] T005 Create `app/Policies/SprintPolicy.php`: `viewAny`/`view` = `$project->isMember($user)`; `create`/`update`/`delete` = `$project->isOwnedBy($user) && $project->status->isActive()` (FR-006/FR-007; red de seguridad tras el chequeo del controlador, research R-4); verificar auto-discovery de la policy
- [x] T006 Update `routes/web.php` (inside the `auth` scope): `Route::post('projects/{project}/sprints', [ProjectSprintController::class, 'store'])->name('projects.sprints.store')`, `Route::put('projects/{project}/sprints/{sprint}', [ProjectSprintController::class, 'update'])->name('projects.sprints.update')` y `Route::delete('projects/{project}/sprints/{sprint}', [ProjectSprintController::class, 'destroy'])->name('projects.sprints.destroy')` (research R-5; el controlador se crea en US1 — resolución lazy, no ejecutar `route:list` hasta entonces)
- [x] T007 [P] Update `app/Models/Project.php`: add relation `sprints(): HasMany` → Sprint, **sin orden global** (el orden `start_date` asc se aplica al construir el payload, research R-9); update the class PHPDoc

**Checkpoint**: Foundation ready — tabla migrada, modelo/factory/policy/rutas en su lugar

---

## Phase 3: User Story 1 - Crear y listar sprints en un proyecto (Priority: P1) 🎯 MVP

**Goal**: El propietario de un proyecto activo crea un sprint (nombre + fechas de inicio/fin) y lo ve en la lista de la ficha del proyecto, ordenada por fecha de inicio ascendente (US1, FR-001/002/003).

**Independent Test**: quickstart S1 + S5: alta válida con redirect a ficha, orden por fecha de inicio, validaciones de nombre y `end_date >= start_date`.

### Tests for User Story 1 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T008 [US1] Create `tests/Feature/Sprints/SprintCrudTest.php` (Pest; vía `php artisan make:test --pest SprintCrudTest --no-interaction`, mover a `tests/Feature/Sprints/`) — create + list portion: `POST /projects/{project}/sprints` del propietario de un proyecto activo guarda el sprint con `project_id` correcto y redirige a `projects.show`; el sprint aparece en el prop `project.sprints` de `GET /projects/{project}` (Inertia `Projects/Show`); varios sprints aparecen ordenados por `start_date` ascendente; nombre ausente, `end_date` anterior a `start_date` o valores no-fecha → `assertSessionHasErrors` (`name`/`start_date`/`end_date`) y nada se crea; fechas pasadas y sprints solapados se aceptan (FR-009)

### Implementation for User Story 1

- [x] T009 [US1] Create `app/Http/Requests/StoreSprintRequest.php`: `name` required string max:255, `start_date` required date, `end_date` required date + `after_or_equal:start_date` (FR-001/FR-002; `'   '` lo rechaza `required` + `ConvertEmptyStringsToNull`, edge case de spec.md); `authorize(): $this->user() !== null` (propiedad y estado se resuelven en controlador/policy, research R-4)
- [x] T010 [US1] Create `app/Http/Controllers/ProjectSprintController.php` con `store()`: `abort_unless($project->isOwnedBy($request->user()), 404)`; helper privado `ensureProjectIsActive(Project $project)` que redirige de vuelta con `errors.project` = "El proyecto no admite cambios en su estado actual." cuando `! $project->status->isActive()` (FR-006, mensaje verbatim del contrato; research R-4); `$this->authorize('create', [Sprint::class, $project])` (red de seguridad de `SprintPolicy`); crea el sprint vía `$project->sprints()->create($validated)` y redirige a `route('projects.show', $project)`
- [x] T011 [US1] Update `app/Http/Controllers/ProjectController.php` (`projectPayload`/`show`): add `'sprints' => $project->sprints()->orderBy('start_date')->get()->map(fn (Sprint $s) => ['id' => $s->id, 'name' => $s->name, 'startDate' => $s->start_date->toDateString(), 'endDate' => $s->end_date->toDateString()])` (FR-003; contrato: `YYYY-MM-DD` ordenados asc; research R-6/R-9)
- [x] T012 [P] [US1] Update `resources/js/pages/Projects/Show.vue`: sección "Sprints" — lista de `project.sprints` (nombre + inicio + fin) y formulario de alta inline (`useForm({ name, start_date, end_date })`, inputs `type="date"`, `InputLabel`/`TextInput`/`InputError`, submit POST vía Wayfinder `store` de `@/routes/projects/sprints` con `preserveScroll`); las acciones de escritura solo se renderizan si `project.isOwner && project.status === 'active'` (FR-006/FR-007, research R-7)
- [x] T013 [US1] Rebuild Wayfinder (`npm run build`), run `npm run types:check`, and verify US1 via quickstart S1/S5 green (`php artisan test --compact --filter=SprintCrudTest`)

**Checkpoint**: User Story 1 fully functional and testable independently — MVP

---

## Phase 4: User Story 2 - Editar un sprint (Priority: P2)

**Goal**: El propietario corrige nombre y fechas de un sprint existente en un proyecto activo; la misma validación del alta aplica a la edición (US2, FR-002/004).

**Independent Test**: quickstart S2 + porción de S5: edición persistida reflejada en la lista; `end_date` inválido rechazado y datos conservados.

### Tests for User Story 2 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T014 [US2] Extend `tests/Feature/Sprints/SprintCrudTest.php` — update portion: `PUT /projects/{project}/sprints/{sprint}` del propietario persiste nombre/fechas y redirige a `projects.show`; `end_date` anterior a `start_date` → `assertSessionHasErrors('end_date')` y el sprint conserva sus datos; sprint que pertenece a OTRO proyecto → 404 (research R-5)

### Implementation for User Story 2

- [x] T015 [US2] Create `app/Http/Requests/UpdateSprintRequest.php` (reglas idénticas a `StoreSprintRequest`, T009)
- [x] T016 [US2] Add `update(UpdateSprintRequest $request, Project $project, Sprint $sprint)` a `app/Http/Controllers/ProjectSprintController.php`: `abort_unless($project->isOwnedBy($request->user()), 404)`; `abort_unless($sprint->project_id === $project->id, 404)`; `ensureProjectIsActive($project)` (helper de T010); `$this->authorize('update', [Sprint::class, $project])`; `$sprint->update($validated)`; redirect a `projects.show`
- [x] T017 [P] [US2] Update `resources/js/pages/Projects/Show.vue`: edición en el componente `Modal` existente — botón "Editar" por sprint (solo con permiso de escritura), formulario `useForm` precargado, submit PUT vía Wayfinder `update` de `@/routes/projects/sprints` con `preserveScroll`, cierre y reset en éxito (research R-7)
- [x] T018 [US2] Rebuild Wayfinder (`npm run build`), run `npm run types:check`, and verify US2 via quickstart S2 green (`php artisan test --compact --filter=SprintCrudTest`)

**Checkpoint**: User Stories 1 AND 2 both work independently

---

## Phase 5: User Story 3 - Eliminar un sprint (Priority: P3)

**Goal**: El propietario elimina un sprint de un proyecto activo tras confirmación; el proyecto y sus demás sprints quedan intactos (US3, FR-005).

**Independent Test**: quickstart S3 + porción de S5: eliminación confirmada desaparece de la lista; proyecto y demás sprints intactos.

### Tests for User Story 3 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T019 [US3] Extend `tests/Feature/Sprints/SprintCrudTest.php` — destroy portion: `DELETE /projects/{project}/sprints/{sprint}` del propietario elimina el sprint y redirige a `projects.show`; el proyecto y los demás sprints permanecen intactos (FR-005); sprint que pertenece a OTRO proyecto → 404

### Implementation for User Story 3

- [x] T020 [US3] Add `destroy(Request $request, Project $project, Sprint $sprint)` a `app/Http/Controllers/ProjectSprintController.php`: mismos guardas que `update()` (propietario 404 → pertenencia 404 → `ensureProjectIsActive` → `authorize('destroy', [Sprint::class, $project])`); `$sprint->delete()`; redirect a `projects.show`
- [x] T021 [P] [US3] Update `resources/js/pages/Projects/Show.vue`: botón "Eliminar" por sprint (solo con permiso de escritura) con `window.confirm(...)` previo (precedente en Show.vue; US3 escenario 2: sin confirmación no se emite petición) y `router.delete` vía Wayfinder `destroy` de `@/routes/projects/sprints` con `preserveScroll`
- [x] T022 [US3] Rebuild Wayfinder (`npm run build`), run `npm run types:check`, and verify US3 via quickstart S3 green (`php artisan test --compact --filter=SprintCrudTest`)

**Checkpoint**: User Stories 1–3 independently functional

---

## Phase 6: User Story 4 - Solo lectura fuera del estado activo y colaboración (Priority: P4)

**Goal**: Con proyecto archivado/completado los sprints quedan visibles pero las escrituras se bloquean con mensaje claro; colaboradores solo leen; no miembros no acceden (US4, FR-006/FR-007, SC-003).

**Independent Test**: quickstart S4 + S6: lista visible fuera de activo, escrituras bloqueadas con `errors.project`, colaborador/no miembro → 404.

### Tests for User Story 4 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T023 [US4] Create `tests/Feature/Sprints/SprintAccessTest.php` (Pest; vía `php artisan make:test --pest SprintAccessTest --no-interaction`, mover a `tests/Feature/Sprints/`) — permissions + states matrix: colaborador (`->hasAttached($user, [], 'members')`) recibe 404 en store/update/destroy; usuario no miembro recibe 404 en `GET /projects/{project}` y en las tres escrituras; propietario con proyecto `archived()`/`completed()` recibe `assertSessionHasErrors('project')` en store/update/destroy y nada cambia (mensaje "El proyecto no admite cambios en su estado actual.", research R-4); con proyecto archivado/completado el prop `project.sprints` sigue presente en `Projects/Show` (solo lectura visible, FR-006)

### Implementation for User Story 4

- [x] T024 [US4] Update `resources/js/pages/Projects/Show.vue`: banner de error `errors.project` visible tras un intento de escritura bloqueado (mismo mecanismo de errores de formulario, research R-4) y verificación de que la sección Sprints queda en solo lectura fuera de activo y para colaboradores (condición `project.isOwner && project.status === 'active'` ya aplicada en T012/T017/T021 — corregir cualquier brecha detectada por T023)
- [x] T025 [US4] Rebuild Wayfinder (`npm run build`), run `npm run types:check`, and verify US4 via quickstart S4/S6 green (`php artisan test --compact --filter=SprintAccessTest`)

**Checkpoint**: All four user stories independently functional

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Quality gates y validación completa

- [x] T026 [P] Run full quality gates: `vendor/bin/pint --dirty --format agent` si se tocó PHP, luego `composer ci:check` (vp check, pint --test, phpstan, full test suite) — all must pass
- [x] T027 Run quickstart.md validation S1–S7 end-to-end (manual S1–S4 + automated S5–S7) and confirm success criteria SC-001–SC-004 from spec.md (creación < 1 min, lista < 2 s con 50 sprints, 100% de escrituras bloqueadas fuera de activo, flujo completo sin ayuda)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — empieza de inmediato
- **Foundational (Phase 2)**: Depends on Setup — BLOCKS all user stories (T002 antes que T003/T004; T005 ∥ T006 ∥ T007, distintos archivos)
- **User Stories (Phase 3–6)**: Todas dependen de Foundational; luego en orden US1 → US2 → US3 → US4 (US1 es el MVP)
- **Polish (Phase 7)**: Depends on all four stories complete

### User Story Dependencies

- **US1 (P1)**: Foundational only — MVP
- **US2 (P2)**: Foundational + US1 (crea `ProjectSprintController` y el helper `ensureProjectIsActive`; comparten `Show.vue`)
- **US3 (P3)**: Foundational + US2 (mismo controlador y página; guardas ya establecidas)
- **US4 (P4)**: Foundational + US1–US3 (la matriz de permisos se prueba sobre las tres escrituras ya implementadas)

### Within Each User Story

- Tests MUST exist and FAIL before implementation tasks start
- Request before controller action; controller before page changes; per story: tests → backend → frontend → rebuild/verify

### Parallel Opportunities

- T005 ∥ T006 ∥ T007 (policy/rutas/relación — archivos distintos)
- T011 ∥ T012 (payload del controlador vs sección de Show.vue — contrato fija la interfaz); T015 ∥ T017, T021 ∥ nada (mismo archivo Show.vue dentro de su historia, secuencial)
- Las cuatro historias no son paralelizables entre sí por archivos compartidos (`ProjectSprintController.php`, `Projects/Show.vue`), pero sus tests sí pueden escribirse en paralelo

---

## Parallel Example: User Story 1

```bash
# Tras Foundational, backend y frontend en paralelo:
Task: "Update app/Http/Controllers/ProjectController.php (payload con sprints ordenados)"
Task: "Update resources/js/pages/Projects/Show.vue (sección Sprints con alta inline)"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1 (Setup) + Phase 2 (Foundational)
2. Complete Phase 3 (US1) → validar con quickstart S1/S5
3. **STOP and VALIDATE**: alta + listado ordenado funcionando — ya es planificación por sprints usable (spec, US1 Independent Test)
4. Desplegar/demo

### Incremental Delivery

1. Setup + Foundational → foundation ready
2. US1 → alta/listado funcionando (MVP)
3. US2 → edición con la misma validación
4. US3 → eliminación con confirmación
5. US4 → matriz de permisos y solo lectura verificada
6. Polish → quality gates + validación completa

### Parallel Team Strategy

Con dos desarrolladores tras la Fase 2:

- Developer A: US1 → US2 (T008–T018, comparten `ProjectSprintController.php`)
- Developer B: escribe T014/T019/T023 (tests de US2–US4) contra las firmas del contrato mientras A implementa US1 — coordinar `Show.vue` (T012 vs T017/T021/T024)

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to spec user story (US1–US4)
- Cada historia es independiente y testeable por sí sola (quickstart la respalda)
- Tests MUST fail antes de implementar (Principio III, NON-NEGOTIABLE)
- Los constraints de campos (longitudes, nullable, reglas de fecha, índice compuesto, mensajes verbatim) se citan de data-model.md/contracts en las tareas
- Los directorios `resources/js/routes/` y `resources/js/actions/` son generados por Wayfinder: regenerar con `npm run build`, nunca editar a mano
- Commit after each task or logical group
