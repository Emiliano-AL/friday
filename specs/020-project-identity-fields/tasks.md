---
description: 'Task list template for feature implementation'
---

# Tasks: Identidad y fecha de término del proyecto

**Input**: Design documents from `/specs/020-project-identity-fields/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/project-identity.md](contracts/project-identity.md), [quickstart.md](quickstart.md)

**Tests**: INCLUIDOS. La Constitución (Principio III, test-first) exige tests antes de la implementación: validación/normalización/unicidad (server-side) y presencia en payloads. Las reglas de validación se citan verbatim desde `data-model.md` para no dejarlas a criterio de implementación.

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

**Purpose**: Punto de partida verde. El árbol tiene trabajo paralelo sin commit — este baseline lo documenta.

- [X] T001 Ejecutar `php artisan test --compact` y `npm run build`; documentar el resultado en `specs/020-project-identity-fields/research.md` (sección "Baseline"); anotar cualquier fallo pre-existente sin tocarlo

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Esquema (4 columnas nulables), enums de paleta/iconos, modelo, validación y persistencia. Bloquea las tres user stories.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

### Tests (test-first: escribir y verificar que FALLAN antes de implementar)

- [X] T002 [P] Extender `tests/Feature/Projects/ProjectCrudTest.php` con las pruebas del contrato de escritura (deben fallar por columnas inexistentes): store completo persiste `key` en mayúsculas ("frid" → "FRID"), `color`, `icon`, `target_date`; clave duplicada del mismo propietario rechazada en create y en update (ignore-self permitido); misma clave de otro propietario válida; clave inválida rechazada (1 carácter, 6 caracteres, "FR-ID" por regex `/^[A-Z0-9]+$/`); color fuera de paleta (`#000000`) rechazado; icono fuera de conjunto (`not_an_icon`) rechazado; `target_date` imposible (`2026-02-31`) rechazada; update con fecha vacía persiste `null`; no-propietario recibe 404; propietario en proyecto archivado recibe 403
- [X] T003 [P] Extender tests de payload (aditivos, deben fallar por campos ausentes): `tests/Feature/Projects/ProjectShowPayloadTest.php` (`project.key/color/icon/targetDate`), `tests/Feature/Projects/ProjectDirectoryTest.php` (`projects.0.*` mismos 4 campos) y `tests/Feature/Shell/ShellRenderingTest.php` (`projects.0.color` y `projects.0.icon` en la prop compartida del sidebar)

### Implementation

- [X] T004 Crear enums `app/Enums/ProjectColor.php` (8 casos string-backed, valor = hex: `#5b5bd6, #2563eb, #10b981, #f59e0b, #ef4444, #06b6d4, #8b5cf6, #ec4899`) y `app/Enums/ProjectIcon.php` (8 casos, valor = nombre Material Symbols: `folder, rocket, bug_report, design_services, code, database, campaign, terminal`) y la migración `database/migrations/2026_10_09_000000_add_identity_fields_to_projects_table.php` con `key` string(5) nullable, `color` string(7) nullable, `icon` string(32) nullable, `target_date` date nullable (sin backfill); ejecutar `php artisan migrate`
- [X] T005 [P] Actualizar `app/Models/Project.php`: fillable + `key/color/icon/target_date`; casts `color => ProjectColor::class`, `icon => ProjectIcon::class`, `target_date => date:Y-m-d`; docblock `@property` actualizado
- [X] T006 Actualizar `app/Http/Requests/StoreProjectRequest.php` y `app/Http/Requests/UpdateProjectRequest.php`: `prepareForValidation` normaliza `key` (trim + strtoupper); reglas `key => ['nullable','string','min:2','max:5','regex:/^[A-Z0-9]+$/', Rule::unique('projects','key')->where(fn ($q) => $q->where('owner_id', $this->user()->id))]` (con `->ignore($this->project)` en update), `color => ['nullable', Rule::enum(ProjectColor::class)]`, `icon => ['nullable', Rule::enum(ProjectIcon::class)]`, `target_date => ['nullable','date']`; mensajes de error en español junto al campo (clave duplicada incluida)
- [X] T007 Actualizar `app/Http/Controllers/ProjectController.php`: `store()` y `update()` persisten los 4 campos desde `validated()`; `projectPayload()` añade `key`, `color` ($project->color?->value), `icon` ($project->icon?->value), `targetDate` ($project->target_date?->toDateString()); `index()` añade los mismos 4 al mapa del directorio
- [X] T008 [P] Actualizar `app/Http/Middleware/HandleInertiaRequests.php` (`projectSummaries()`): el `get(['id','title'])` pasa a `get(['id','title','color','icon'])` serializando valores escalares

**Checkpoint**: `php artisan test --compact tests/Feature/Projects tests/Feature/Shell` en verde (tests nuevos incluidos); `vendor/bin/pint --dirty` limpio.

---

## Phase 3: User Story 1 - Identidad visual y clave del proyecto (Priority: P1) 🎯 MVP

**Goal**: El formulario de crear/editar proyecto tiene habilitados clave, color e icono (pickers cerrados), validación inline y precarga en edición; nada de "Próximamente" en el bloque.

**Independent Test**: Quickstart escenarios 1, 3, 5, 6 y 7: crear con identidad completa, edición precargada, clave duplicada rechazada, misma clave de otro propietario válida, validaciones inline.

### Implementation for User Story 1

- [X] T009 [P] [US1] En `resources/js/types/project.ts` añadir las constantes `PROJECT_PALETTE` (8 hex de `ProjectColor`) y `PROJECT_ICONS` (8 nombres de `ProjectIcon`), y extender `ProjectDetail` y `DirectoryProject` con `key: string | null`, `color: string | null`, `icon: string | null`, `targetDate: string | null`; en `resources/js/types/shell.ts` extender `ProjectSummary` con `color: string | null` e `icon: string | null`
- [X] T010 [US1] En `resources/js/components/Projects/ProjectFormModal.vue` habilitar el bloque "Identidad Visual": eliminar el badge "Próximamente" y el footer informativo; input clave habilitado (maxlength 5, transform a mayúsculas al teclear, placeholder "CLAVE", error inline `form.errors.key` con `ring-error`); picker de color: fila de 8 swatches de `PROJECT_PALETTE` con ring en el activo; picker de icono: grid de 8 botones con `AppIcon` de `PROJECT_ICONS` con ring en el activo; input fecha habilitado (`type="date"`, error inline); extender el prop `project` a `{ id, title, description, key, color, icon, targetDate }`, el `useForm` con los 4 campos y el `watch` de re-seed en edición
- [X] T011 [US1] Actualizar los call sites del modal para pasar la identidad: `resources/js/pages/Projects/Index.vue` (`openEdit` incluye key/color/icon/targetDate del `DirectoryProject`) y `resources/js/pages/Projects/Show.vue` (objeto inline del modal de edición, ~línea 1005, incluye los 4 campos de `ProjectDetail`)
- [X] T012 [US1] Ejecutar `npm run build` y `npm run types:check`; validar en navegador los escenarios 1, 3, 5, 6 y 7 del quickstart (formulario: guardado, precarga, duplicado, validaciones)

**Checkpoint**: US1 verificable de forma independiente; la identidad se guarda y precarga.

---

## Phase 4: User Story 2 - Fecha de término del proyecto (Priority: P2)

**Goal**: La fecha de término se guarda, edita y elimina desde el formulario (cubierto en T010) y la ficha la muestra con estados real/sin fecha/vencida.

**Independent Test**: Quickstart escenarios 4, 8 y 12: cambiar y quitar la fecha, badge "Vencida" con fecha pasada, persistencia tras recargar.

### Implementation for User Story 2

- [X] T013 [US2] En `resources/js/pages/Projects/Show.vue` reemplazar la celda "Fecha Objetivo" (~líneas 532-546): muestra la fecha formateada en español (p. ej. `9 oct 2026`) cuando `project.targetDate` existe; "Sin fecha" cuando es `null`; y un `UiBadge` "Vencida" en tono `error` cuando la fecha es anterior a hoy (computed cliente); mantener la jerarquía visual de la celda
- [X] T014 [US2] Validar en navegador los escenarios 4, 8 y 12 del quickstart (fecha cambiada, quitada, vencida y persistente)

**Checkpoint**: US1 + US2 funcionan de forma independiente.

---

## Phase 5: User Story 3 - La identidad se ve en la aplicación (Priority: P3)

**Goal**: La ficha muestra clave junto al título y tile con icono/color; la navegación (ContextSwitcher) muestra icono y color por proyecto, con defaults para los sin identidad.

**Independent Test**: Quickstart escenarios 2, 9, 10 y 11: ficha con clave e identidad, navegación con iconos/colores, proyecto antiguo con defaults, solo lectura para miembros.

### Implementation for User Story 3

- [X] T015 [US3] En `resources/js/pages/Projects/Show.vue` header (~líneas 429-459): tile del proyecto con `AppIcon` de `project.icon` (fallback `folder`) sobre fondo tenue de `project.color` (fallback a los estilos actuales `bg-primary`), y chip con `project.key` junto al h1 (oculto cuando es `null`), con tipografía del sistema de chips existente
- [X] T016 [US3] En `resources/js/components/AppShell/ContextSwitcher.vue` pintar icono/color en los tiles: tile del proyecto actual y de cada fila usan `AppIcon` con el `icon` del proyecto y fondo tenue de `color`; cuando `color`/`icon` son `null`, comportamiento actual (iniciales / `bg-primary-container`); cuidar el diff con el trabajo paralelo en `AppShellSidebar.vue` (no tocar ese archivo salvo necesidad)
- [X] T017 [US3] Validar en navegador los escenarios 2, 9, 10 y 11 del quickstart (defaults, permisos de solo lectura, navegación, proyecto antiguo)

**Checkpoint**: Las tres historias funcionan; ningún "Próximamente" queda en identidad ni fecha.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Calidad transversal antes de cerrar.

- [X] T018 Ejecutar `vendor/bin/pint --dirty` y dejar el código formateado
- [X] T019 Ejecutar la suite completa `php artisan test --compact`, `npm run build` y `npm run types:check` — todo en verde
- [X] T020 [P] Recorrer los 12 escenarios manuales de `quickstart.md` en navegador y confirmar SC-001…SC-005

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies
- **Foundational (Phase 2)**: Depende de T001; BLOQUEA US1, US2 y US3 (columnas, enums, validación, payloads)
- **User Stories (Phase 3+)**: Dependen de la fase Foundational; US2 y US3 conviene ejecutarlas tras US1 (comparten tipos del T009 y el formulario del T010)
- **Polish (Phase 6)**: Depende de US1–US3

### User Story Dependencies

- **User Story 1 (P1)**: Tras Foundational. Sin dependencias de otras stories. Es el MVP.
- **User Story 2 (P2)**: Tras Foundational; el input de fecha ya existe en el formulario desde T010 — esta historia es display + comportamiento de la fecha.
- **User Story 3 (P3)**: Tras US1 (usa los tipos extendidos) y recomendado tras US2 (misma página Show).

### Within Each User Story

- Tests primero (fase Foundational); luego tipos → formulario/call sites → display → verificación navegador
- T010/T011/T013/T015 comparten archivos (`ProjectFormModal.vue`, `Show.vue`) → secuenciales entre fases
- Checkpoint de la historia antes de pasar a la siguiente prioridad

### Parallel Opportunities

- T002 ∥ T003 (tests, archivos distintos)
- T004 ∥ T005 ∥ T008 tras los tests (enums+migración / modelo / middleware — archivos distintos; T006 y T007 dependen de T004/T005)
- T006 ∥ T008
- T009 ∥ nada más en su fase (tipos primero)
- T018 ∥ T020 (polish)

---

## Parallel Example: Foundational Phase

```bash
# Tests primero (deben fallar por columnas/campos ausentes):
Task: "Extender tests/Feature/Projects/ProjectCrudTest.php con el contrato de escritura (T002)"
Task: "Extender tests de payload show/directory/shell (T003)"

# Luego, en paralelo:
Task: "Crear enums + migración y ejecutar migrate (T004)"
Task: "Actualizar app/Models/Project.php casts/fillable (T005)"
Task: "Actualizar HandleInertiaRequests::projectSummaries (T008)"

# Después:
Task: "Validación en Store/UpdateProjectRequest (T006)"
Task: "Persistencia y payloads en ProjectController (T007)"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1: Setup (T001)
2. Phase 2: Foundational (T002–T008)
3. Phase 3: US1 (T009–T012)
4. **STOP and VALIDATE**: quickstart escenarios 1, 3, 5–7 (formulario de identidad funcionando de extremo a extremo)

### Incremental Delivery

1. Setup + Foundational → backend listo (todo verde)
2. US1 → identidad y clave (MVP)
3. US2 → fecha de término visible con estados
4. US3 → identidad reflejada en ficha y navegación
5. Polish → quality gates

### Parallel Team Strategy

1. Equipo completa Setup + Foundational
2. Con Foundacional verde: developer A en US1 (formulario); developer B prepara escenarios de navegador del quickstart

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Todas las columnas son **nulables**: cero backfill; los defaults son presentacionales (FR-011)
- Los enums se serializan al cliente como **valor escalar** (`?->value`)
- No tocar: `Projects/Index.vue` tarjetas del directorio (fuera de alcance, research D4), `AppShellSidebar.vue` (trabajo paralelo del usuario)
- Verificar que los tests fallen antes de implementar (rojo → verde)
- Ejecutar `vendor/bin/pint --dirty` tras cambios PHP y `npm run build` tras cambios Vue/TS
- Formato estricto en cada tarea: `- [ ] TNNN [P?] [Story?] descripción con ruta de archivo`
