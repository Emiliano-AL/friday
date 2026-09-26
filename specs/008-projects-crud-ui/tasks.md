---
description: 'Task list template for feature implementation'
---

# Tasks: Rediseño del CRUD de Proyectos

**Input**: Design documents from `/specs/008-projects-crud-ui/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Test-first obligatorio por constitución (Principio III) — los tests de cada fase se escriben primero y deben FALLAR antes de su implementación. Comandos: `php artisan test --compact --filter=<NombreTest>`.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- Backend: `app/`, `tests/` en raíz
- Frontend: `resources/js/` (páginas en `pages/`, componentes en `Components/Projects/` y `Components/AppShell/`, tipos en `types/`)

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Tipos compartidos que usan todas las historias (shape del contrato §2)

- [x] T001 [P] Crear `resources/js/types/project.ts` con los tipos del contrato: `DirectoryProject` (id, title, description, status: 'active'|'archived'|'completed', statusLabel, progress: number, taskDoneCount, taskTotalCount, activeSprint: {id,name,startDate,endDate}|null, owner {id,name,avatar}, members {id,name,avatar}[], isOwner), `ProjectCounts` {active,completed,archived}, `ProjectKpis` {activeProjects,completedSprintsThisQuarter}, `FlashMessage` {success,error} y `ActiveSprint` — sin tocar aún `Show.vue` (su type inline se reemplaza en US3)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Derivaciones de datos reales, flash compartido y modal a tokens — bloquean a las 3 historias

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T002 [P] Reestilizar `resources/js/Components/Modal.vue` a tokens del contrato 007 (sin cambiar API `show/maxWidth/closeable`): veil `bg-inverse-surface/20 backdrop-blur-sm`, panel `bg-surface-container-lowest rounded-xl shadow-modal`; verificar que los modales existentes de sprints/tareas siguen abriendo y cerrando
- [x] T003 [P] Ampliar `tests/Feature/Projects/ProjectProgressTest.php` (RED) con la fórmula real: proyecto con 3 tareas Done de 4 → 75%; 1 Done de 3 → 33%; `InReview` NO cuenta como terminada; 0 sin tareas; mismo valor en index y show; rango 0-100
- [x] T004 Implementar en `app/Models/Project.php` (GREEN, depende T003): `progressPercentage()` real = `round(tareas con TaskStatus::Done / tareas totales * 100)`, 0 si no hay tareas (documentado en PHPDoc); relación `activeSprint(): HasOne` = sprint con `start_date <= hoy` `orderByDesc('start_date')`
- [x] T005 [P] Crear `tests/Feature/Projects/ProjectFlashTest.php` (RED): `page.props.flash.success` presente tras crear/editar/archivar/eliminar proyecto (redirects `->with('success', ...)`); `flash` compartido en toda respuesta autenticada y ausente/vacío para invitados
- [x] T006 Compartir `flash` (success/error) en `app/Http/Middleware/HandleInertiaRequests.php` y añadir `->with('success', ...)` a los redirects de `app/Http/Controllers/ProjectController.php` (store, update, status, destroy) (GREEN, depende T005)
- [x] T007 [P] Crear `resources/js/Components/AppShell/FlashMessage.vue` (props `message: string|null`, `tone: 'success'|'error'`; banner dismissible `role="status"`/`role="alert"`) y montarlo en `resources/js/Layouts/AuthenticatedLayout.vue` leyendo `page.props.flash`

**Checkpoint**: Foundation ready — progreso real, sprint activo derivable, flash visible, modal en tokens

---

## Phase 3: User Story 1 - Directorio de proyectos (Priority: P1) 🎯 MVP

**Goal**: Directorio completo (búsqueda, píldoras, orden, vistas, tarjetas con datos reales, KPIs) + flujo de creación desde diálogo. El modo _crear_ de `ProjectFormModal` se construye en esta fase porque FR-006 lo exige sobre la superficie del directorio; US2 extenderá el mismo diálogo a edición/eliminación.

**Independent Test**: Crear 3-5 proyectos con distintos estados/tareas/miembros y verificar contadores, progreso real, filtros, búsqueda, orden, ambas vistas y creación vía `N`/tarjeta/`?new=1`, sin salir del directorio.

### Tests for User Story 1 ⚠️

- [x] T008 [US1] Crear `tests/Feature/Projects/ProjectDirectoryTest.php` (RED): payload `projects` con el shape `DirectoryProject` completo (incluye `description`, `taskDoneCount/taskTotalCount`, `activeSprint` con fechas cuando `start_date <= hoy` y null si no inició ninguno, `owner`/`members` con `avatar`, `isOwner`); `counts` sobre el universo completo del usuario (no filtrado por búsqueda); `kpis.activeProjects` = activos y `kpis.completedSprintsThisQuarter` = sprints con `end_date < hoy` dentro del trimestre actual; orden `updated_at` desc; nunca proyectos ajenos; `withCount` sin N+1 (assert de conteos exactos)

### Implementation for User Story 1

- [x] T009 [US1] Reescribir `ProjectController::index()` en `app/Http/Controllers/ProjectController.php` (GREEN, depende T004 y T008): query con `with(['activeSprint','owner','members'])` + `withCount(['tasks', 'tasks as done_tasks_count' => fn ($q) => $q->where('status', TaskStatus::Done)])`, mapear `DirectoryProject`, `counts` y `kpis` por usuario autenticado
- [x] T010 [P] [US1] Crear `resources/js/Components/Projects/UiBadge.vue` (props `label`, `tone?: 'primary'|'neutral'|'error'|'tertiary'|'outline'`, `dot?: boolean`) — chip para estados, contadores y marca "Próximamente"
- [x] T011 [P] [US1] Crear `resources/js/Components/Projects/AvatarStack.vue` (props `people: {id,name,avatar}[]`, `max?=3`): avatares solapados `bg-surface-container`, overflow "+N", fallback iniciales reutilizando `Components/AppShell/Avatar.vue`
- [x] T012 [P] [US1] Crear `resources/js/Components/Projects/FilterPills.vue` (props `counts: ProjectCounts`, `modelValue: 'all'|'active'|'completed'|'archived'`; emite `update:modelValue`): pills "Todos | Activos (n) | Completados (n) | Archivados (n)", pill activa en tono primary, scroll horizontal sin barra en móvil
- [x] T013 [P] [US1] Crear `resources/js/Components/Projects/ProjectsToolbar.vue` (props `sort: 'recent'|'alpha'|'progress'`, `view: 'grid'|'list'`; emite `update:sort`, `update:view`, `search(term)`, `create`): buscador con `<kbd>⌘F</kbd>` (focus global con preventDefault en Index), select de orden (menú custom tipo ContextSwitcher), conmutador vista segmentado (grid/list), botón "Nuevo Proyecto" con kbd `N`
- [x] T014 [P] [US1] Crear `resources/js/Components/Projects/ProjectContextMenu.vue` (props `project: DirectoryProject`; emite acciones): items de estado según `status` y `isOwner` (Editar; Archivar o Completar; Reactivar; Ver detalle), cierre con ESC/click fuera, `data-shell-popover` para integrar con el cierre global del layout
- [x] T015 [P] [US1] Crear `resources/js/Components/Projects/ProjectCard.vue` (props `project: DirectoryProject`): borde superior h-1 tono por estado (Activo primary, Completado tertiary, Archivado outline-variant), icono neutro `folder`, título `line-clamp`, fila estado+progreso con "(done/total)", barra h-1.5, fila sprint activo (o "Sin sprint activo") + `AvatarStack`, click navega a `projects.show` (la zona del menú no navega)
- [x] T016 [P] [US1] Crear `resources/js/Components/Projects/ProjectListRow.vue` (props `project: DirectoryProject`): fila compacta de una línea adaptable (título, estado, progreso mini, avatares, menú) para la vista lista
- [x] T017 [P] [US1] Crear `resources/js/Components/Projects/ProjectEmptyState.vue`: estado vacío del directorio con CTA "Iniciar un nuevo proyecto" (emite `create`)
- [x] T018 [P] [US1] Crear `resources/js/Components/Projects/ProjectsKpiPanel.vue` (props `kpis: ProjectKpis`): tarjetas "Proyectos en marcha" y "Sprints completados este trimestre" con datos reales + 2 tarjetas "Próximamente" (Entregas a tiempo, Velocidad de equipo) sin sparkline
- [x] T019 [US1] Crear `resources/js/Components/Projects/ProjectFormModal.vue` (modo create en esta fase): props `open`, `mode: 'create'|'edit'`, `project?`; campos Nombre (requerido, max:255) y Descripción breve (opcional) con `useForm` → `store.url()`; sección "Identidad visual" con Clave/Color/Icono/Fecha objetivo deshabilitados + `UiBadge` "Próximamente" (FR-008); errores inline por campo; autofocus Nombre; doble envío bloqueado; emite `close`
- [x] T020 [US1] Reescribir `resources/js/pages/Projects/Index.vue` (depende T009-T019): ensamblar toolbar + pills + grid/list + empty state + KPI panel; búsqueda por `title`, filtro por pill y orden (recientes/alfabético/progreso) client-side; persistir vista en `localStorage('projects.view')`; atajo `N` fuera de inputs abre el diálogo; auto-apertura con `?new=1` y limpieza con `router.replace`; menú ⋯ ejecuta PUT `projects.status` con transición válida y muestra flash/error; verificar en vivo con `php artisan serve` + `npm run dev`
- [x] T021 [US1] Añadir acción "Crear nuevo proyecto" (icono `add`, keywords `['proyecto','nuevo','crear']`) a la paleta de comandos en `resources/js/Layouts/AuthenticatedLayout.vue` que navega a `projects.index.url() + '?new=1'` (depende T020)

**Checkpoint**: User Story 1 funcional de forma independiente — directorio navegable + creación completa

---

## Phase 4: User Story 2 - Editar y eliminar proyecto (Priority: P2)

**Goal**: Modo edición del diálogo (precargado), zona de peligro con eliminación confirmada, deep-links de edición y permisos de escritura solo para líder.

**Independent Test**: Como líder: editar nombre/descripción desde el menú ⋯, archivar/reactivar, eliminar con confirmación; verificar deep-links `/projects/{id}/edit` y `/projects/create`; como miembro no líder: sin acciones de escritura y 404/403 en rutas forzadas.

### Tests for User Story 2 ⚠️

- [x] T022 [P] [US2] Ampliar `tests/Feature/Projects/ProjectCrudTest.php` (o crear `ProjectRedirectsTest.php`) (RED): GET `projects.create` redirige a `projects.index?new=1`; GET `projects.edit` del líder redirige a `projects.show?edit=1`; GET `projects.edit` de no-líder sigue 404 (invariante existente); destroy incluye `->with('success')` (flash ya cubierto en T005/T006)

### Implementation for User Story 2

- [x] T023 [US2] Cambiar `create()` y `edit()` en `app/Http/Controllers/ProjectController.php` a redirects (GREEN, depende T022): `create()` → `redirect()->route('projects.index', ['new' => 1])`; `edit()` conserva 404 para no-líder y redirige a `projects.show` con `['edit' => 1]` para el líder
- [x] T024 [US2] Extender `resources/js/Components/Projects/ProjectFormModal.vue` (depende T019): modo `edit` precargado con `useForm` → `update.url(id)`; zona de peligro "Eliminar proyecto" visible solo en modo edit → `confirm()` nativo → `router.delete(destroy.url(id))`; abrir el diálogo en modo edit desde el menú ⋯ de Index con query `?edit=<id>` (auto-apertura + `router.replace`) — el servidor sigue reforzando ownership (404/403)
- [x] T025 [P] [US2] Eliminar `resources/js/pages/Projects/Create.vue` y `resources/js/pages/Projects/Edit.vue` y limpiar cualquier import hacia ellos (depende T023; verificar con `grep -r "Projects/Create\|Projects/Edit" resources/js`)

**Checkpoint**: User Stories 1 y 2 operativas — CRUD de escritura completo con permisos

---

## Phase 5: User Story 3 - Detalle de proyecto con pestañas (Priority: P3)

**Goal**: Detalle rediseñado (cabecera, franja de metadatos, acciones, pestañas) con la funcionalidad existente de sprints/tareas/miembros preservada.

**Independent Test**: Abrir un proyecto con sprint activo y tareas: metadatos y progreso reales; pestañas General/Sprints y tareas con la funcionalidad previa intacta; pestañas futuras "Próximamente"; edición desde "Ajustes"; `?edit=1` auto-abre el diálogo.

### Tests for User Story 3 ⚠️

- [x] T026 [P] [US3] Crear `tests/Feature/Projects/ProjectShowPayloadTest.php` (RED): payload `project` incluye `activeSprint` (misma heurística que el índice), `taskDoneCount`/`taskTotalCount`, `owner`/`members` con `avatar`, `tasks[].assignee` con `avatar`; resto del shape sin regresiones (`sprints`, `tasks`, `allowedTransitions`, `isOwner`)

### Implementation for User Story 3

- [x] T027 [US3] Ampliar `projectPayload()` en `app/Http/Controllers/ProjectController.php` (GREEN, depende T004 y T026): añadir `activeSprint`, conteos de tareas, `avatar` en owner/members/assignee (eager loads existentes)
- [x] T028 [P] [US3] Crear `resources/js/Components/Projects/ProjectTabs.vue` (props `modelValue: 'general'|'sprints'`; emite `update:modelValue`): tabs activas "General y resumen" / "Sprints y tareas (n)" + tres tabs disabled con `UiBadge` "Próximamente" (Documentación, Recursos y enlaces, Historial), `role="tablist"` con teclado ←/→
- [x] T029 [US3] Reestructurar `resources/js/pages/Projects/Show.vue` (depende T024, T027, T028; extraer el type inline a `types/project.ts`): cabecera con breadcrumb (Proyectos / título), título + badges (estado + sprint activo; prioridad omitida — futura), franja de metadatos (Líder, Equipo con AvatarStack, Progreso global "(x/y)", "Fecha objetivo: Próximamente"); acciones: Ajustes → diálogo edit, Compartir deshabilitado con tooltip "Próximamente", Nuevo Sprint/Nueva Tarea → cambian a tab sprints; tab General: tarjeta sprint activo, sprint siguiente, banner backlog, tarjeta Equipo con la gestión de miembros existente movida aquí; tab Sprints y tareas: secciones existentes (sprints, tareas, backlog, kanban, comentarios) preservadas sin cambios en `Components/Tasks/*`; retirar el botón inline de eliminar (FR-011); auto-apertura `?edit=1`; banner solo-lectura si proyecto no activo (ya existente, conservar)
- [x] T030 [US3] Verificar el flujo de `ContextSwitcher` (shell 007) con la ruta `create`: su CTA "Crear proyecto" apunta a `create.url()` que ahora redirige a `?new=1` — confirmar en vivo que el diálogo abre (ajustar en `resources/js/Components/AppShell/ContextSwitcher.vue` solo si el redirect no basta)

**Checkpoint**: Las 3 historias funcionan de forma independiente y el detalle preserva las capacidades previas

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Gates de calidad, responsive/accesibilidad y validación del quickstart

- [ ] T031 [P] Quality gates: `vendor/bin/pint --dirty --format agent`, `npm run check:fix`, `npm run types:check` y `npm run build` sin errores
- [ ] T032 [P] Pase responsive/accesibilidad: a 375px sin scroll horizontal en directorio/diálogo/detalle; recorrido completo solo con teclado (N, ⌘F, ESC, ⋯, diálogo, tabs); anillos de foco visibles; `mcp__laravel-boost__browser-logs` y consola sin errores en todo el recorrido
- [ ] T033 [P] Validar `quickstart.md` (§0-§7) contra la implementación real y actualizar el archivo si algún paso difiere
- [ ] T034 `php artisan test --compact` completo en verde y commit final de la feature

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias; T001 bloquea solo a US1 en tipos (las fases pueden tipar localmente si fuera necesario)
- **Foundational (Phase 2)**: bloquea a las 3 historias (T004 progreso/sprint activo, T006 flash, T002 modal, T007 FlashMessage). Dentro de la fase: T003→T004 y T005→T006 son secuenciales TDD; T002/T007 son [P]
- **User Stories (Phase 3-5)**: secuenciales por dependencia de UI: US2 extiende el diálogo de US1 y se hospeda en su Index; US3 hospeda el diálogo de edición de US2 en Show. El backend de cada historia es independiente y sus tests no se solapan
- **Polish (Phase 6)**: depende de las 3 historias

### User Story Dependencies

- **User Story 1 (P1)**: after Foundational — sin dependencias de otras historias; **MVP**
- **User Story 2 (P2)**: after US1 (extiende `ProjectFormModal` e Index); su parte server (redirects/flash) es independiente
- **User Story 3 (P3)**: after US2 (hospeda edición en Show) y Foundational (payload)

### Within Each User Story

- Tests escritos primero y en ROJO antes de implementar (T008→T009, T022→T023, T026→T027)
- Componentes [P] de cada historia comparten solo el contrato (types), no archivos
- Cierre de fase con verificación en vivo (serve + vite) antes de avanzar

### Parallel Opportunities

- T002 + T003 + T005 (foundation, archivos distintos)
- T010–T018 (los 9 componentes de US1 son archivos independientes)
- T022 + T026 (tests de US2/US3, archivos distintos)
- T028 + T027 (tab component vs payload backend)
- En Polish: T031 + T032 + T033

---

## Parallel Example: User Story 1

```bash
# Tests primero (solo):
Task: "Crear tests/Feature/Projects/ProjectDirectoryTest.php (RED)"

# Luego, en paralelo, los componentes:
Task: "Crear resources/js/Components/Projects/UiBadge.vue"
Task: "Crear resources/js/Components/Projects/AvatarStack.vue"
Task: "Crear resources/js/Components/Projects/FilterPills.vue"
Task: "Crear resources/js/Components/Projects/ProjectsToolbar.vue"
Task: "Crear resources/js/Components/Projects/ProjectContextMenu.vue"
Task: "Crear resources/js/Components/Projects/ProjectCard.vue"
Task: "Crear resources/js/Components/Projects/ProjectListRow.vue"
Task: "Crear resources/js/Components/Projects/ProjectEmptyState.vue"
Task: "Crear resources/js/Components/Projects/ProjectsKpiPanel.vue"

# Integración secuencial al final: Index.vue → paleta → verificación
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1 (Setup) + Phase 2 (Foundational) — T001-T007
2. Phase 3 (US1) — T008-T021: directorio + creación
3. **STOP y VALIDA**: quickstart §1-§3 con el flujo de creación; deploy/demo si se desea

### Incremental Delivery

1. Setup + Foundational → base
2. US1 → directorio + crear → validar (MVP)
3. US2 → editar/eliminar + permisos → validar (quickstart §4)
4. US3 → detalle con pestañas → validar (quickstart §5-§7)
5. Polish → gates finales

### Parallel Team Strategy

- Desarrollador A: T003+T004 y T008+T009 (backend de directorio)
- Desarrollador B: T002, T005-T007 (foundation UI) y T010-T018 (componentes US1)
- Desarrollador C: tests T022/T026 + T028 mientras B cierra componentes
- Converger en T019/T020/T023/T024 y las integraciones secuenciales

---

## Notes

- [P] tasks = different files, no dependencies
- Los IDs son secuenciales en orden de ejecución; los marcados [P] pueden ejecutarse en paralelo entre sí
- Toda acción de escritura respeta la policy existente (solo líder); el cliente oculta y el servidor rechaza
- "Próximamente" es siempre un estado deshabilitado real (no navega, no enfoca, sin datos simulados)
- Verificar tests en rojo antes de cada implementación y en verde después
