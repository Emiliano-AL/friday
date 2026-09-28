---
description: 'Task list for feature implementation'
---

# Tasks: Rediseño del flujo de tareas — listado, kanban y creación

**Input**: Design documents from `/specs/009-tasks-board-ui/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: Test-first obligatorio por constitución (III) — tests en rojo antes de cada implementación. Comandos: `php artisan test --compact --filter=<Nombre>`.

**Organization**: Tareas agrupadas por user story; el diálogo de tarea completo se construye en US1 (sus escenarios lo exigen) y US3 lo refina.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: parallelizable (distintos archivos, sin dependencias)
- **[Story]**: US1, US2, US3
- Rutas exactas en cada descripción

## Path Conventions

- Backend: `app/`, `routes/`, `database/`, `tests/`
- Frontend: `resources/js/` (páginas en `pages/Tasks/`, componentes en `Components/Tasks/Board/`, tipos en `types/`)

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Tipos compartidos por todas las historias

- [X] T001 [P] Crear `resources/js/types/task.ts` con `BoardTask` (id, title, description: string|null, type/typeLabel, priority/priorityLabel, status/statusLabel, project: {id,title}|null, sprint: {id,name}|null, assignee: {id,name,avatar}|null, commentsCount: number), `TasksTab` ('all'|'mine'|'standalone'|'urgent'|'done'), `TasksView` ('list'|'kanban') y el tipo de props de `Tasks/Index` (tasks: BoardTask[], projects: {id,title}[])

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Dominio de tareas independientes + rutas globales — bloquea a las 3 historias

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T002 [P] Crear `tests/Feature/Tasks/TaskStandaloneTest.php` (RED): crear tarea sin proyecto (`project_id` ausente/null) queda en DB con `project_id null`; sin proyecto el `assignee_id` debe ser el propio usuario (otro usuario → error) y `sprint_id` está prohibido; con proyecto se siguen exigiendo miembro/mismo-sprint (regresión); el responsable puede actualizar y eliminar su tarea huérfana; un tercero recibe 403/404
- [X] T003 [P] Crear `tests/Feature/Tasks/TaskGlobalRoutesTest.php` (RED): rutas globales `GET/POST /tasks`, `PUT/DELETE /tasks/{task}`, `GET /tasks/{task}` (JSON con comments ordenados) y `POST /tasks/{task}/comments` responden; actualización parcial con solo `{status}` funciona; autorización según policy (miembro/owner con proyecto, responsable sin proyecto)
- [X] T004 Crear migración `database/migrations/2026_09_26_000000_make_tasks_project_id_nullable.php` con `php artisan make:migration --no-interaction` (`$table->foreignId('project_id')->nullable()->change()`), actualizar PHPDoc/fillable de `app/Models/Task.php` (`int|null $project_id`) y añadir estado `standalone()` a `database/factories/TaskFactory.php` (GREEN de la parte de persistencia de T002)
- [X] T005 Re-anclar `app/Policies/TaskPolicy.php` a firmas `(User, Task)`: con proyecto conserva reglas actuales (miembro + activo; delete = owner + activo); sin proyecto update/delete = `task.assignee_id === user.id`; actualizar los call sites en `app/Http/Controllers/ProjectTaskController.php` para pasar la tarea (mismo comportamiento); crear `app/Http/Requests/SaveTaskRequest.php` según data-model.md: store con `title` required max:255, `project_id` nullable exists + validación condicional (con proyecto: assignee miembro / sprint mismo proyecto; sin proyecto: assignee = usuario actual, sprint `prohibited`); update con todas las reglas `sometimes` (GREEN de T002)
- [X] T006 Añadir rutas globales en `routes/web.php` (`Route::prefix('tasks')` + `tasks.index/store/show/update/destroy/comments.store`) y crear `app/Http/Controllers/TaskController.php` con: `index` (query de visibilidad: assignee = yo, o proyecto mío/miembro; `with` project/sprint/assignee + `withCount comments`; render `Tasks/Index`), `store`/`update` (SaveTaskRequest, `back()` con flash; update acepta parcial), `destroy` (redirect `tasks.index` + flash), `show` (JSON task + comments), `storeComment` (JSON actualizado); autorización vía policy re-anclada (GREEN de T003, depende T004/T005)
- [X] T007 [P] Extender `resources/js/Components/Tasks/TaskCommentsModal.vue` con props opcionales `useGlobal?: boolean` (cuando es true: al abrir hace fetch/visita JSON `tasks.show` para cargar comentarios y postea a `tasks.comments.store`; cuando es false/oMITido conserva el comportamiento anidado actual para Show)

**Checkpoint**: dominio + rutas globales verdes; Mis Tareas ya puede construirse

---

## Phase 3: User Story 1 - Listado global "Mis Tareas" (Priority: P1) 🎯 MVP

**Goal**: Página Mis Tareas con vista lista agrupada (reglas FR-002), búsqueda, filtros/pestañas con contadores reales, cambio de estado circular, diálogo completo de crear/editar (sus escenarios lo exigen; US3 lo refina), comentarios y pie de estado.

**Independent Test**: Crear tareas variadas y verificar grupos/contadores/filtros reales, cambio de estado persistente, creación/edición/eliminación desde el diálogo y comentarios — todo sin salir de `/tasks`.

### Tests for User Story 1 ⚠️

- [X] T008 [US1] Crear `tests/Feature/Tasks/TasksPageTest.php` (RED): `GET /tasks` renderiza `Tasks/Index` con `tasks` en shape `BoardTask` (project `null` para huérfanas, `commentsCount` numérico, assignee con `avatar`) y `projects` del usuario; el alcance incluye tareas asignadas, de proyectos propios y de proyectos miembro, y excluye tareas de proyectos ajenos y huérfanas ajenas; orden `updated_at` desc

### Implementation for User Story 1

- [X] T009 [US1] Implementar el payload de `TaskController::index` en `app/Http/Controllers/TaskController.php` (GREEN, depende T006/T008): mapeo `BoardTask` con labels de enums, `project`/`sprint`/`assignee` (con avatar) y `commentsCount` de `withCount`
- [X] T010 [P] [US1] Crear `resources/js/Components/Tasks/Board/TaskFilters.vue` según contrato §3: buscador con `<kbd>⌘F</kbd>` y `defineExpose({focus})`, pills Proyecto (mis proyectos + "Todos" + "Sin Proyecto") y Responsable (dedup del payload + yo), pestañas rápidas con contadores reales (Todas/Mis Tareas/Sin Proyecto/Urgentes/Completadas), conmutador vista lista/kanban
- [X] T011 [P] [US1] Crear `resources/js/Components/Tasks/Board/TaskRow.vue` según contrato §3: botón circular de estado (menú con los 5 estados, tonos por estado), icono de tipo (`AppIcon`: bug_report/new_releases/check_box/task_alt por tipo), título truncado, pill Proyecto (`bg-surface-container-low` con dot) o "Sin Proyecto", chip Sprint/"Sin Sprint", chip prioridad (tono por nivel), `Avatar` del responsable o "Sin asignar", menú ⋯ (editar, cambiar estado, comentarios, eliminar según permiso); emits edit/status/comment/remove/open
- [X] T012 [P] [US1] Crear `resources/js/Components/Tasks/Board/TaskGroupSection.vue`: header con icono, título, contador y hint opcional; slot de contenido; estilos de contrato §4
- [X] T013 [P] [US1] Crear `resources/js/Components/Tasks/Board/TaskFormModal.vue` (diálogo completo sobre `Components/Modal.vue`): título con autofocus e hint de redacción imperativa; selectores de Tipo (4 reales), Estado (inicial al crear / actual al editar, 4 no-backlog), Prioridad segmentada (4), Responsable (yo + miembros del proyecto elegido; solo "yo" sin proyecto), Proyecto (mis proyectos + "Sin Proyecto"), Sprint (opciones del proyecto, deshabilitado sin proyecto), Descripción textarea; validación inline; modo edit con zona de peligro (confirm + DELETE); useForm → `tasks.store`/`tasks.update`; ESC/click-fuera; doble envío bloqueado
- [X] T014 [US1] Crear `resources/js/pages/Tasks/Index.vue` (depende T009-T013): ensamblar filtros + grupos (reglas FR-002 exactas; terminadas solo en pestaña Completadas) + filas + `TaskFormModal` + `TaskCommentsModal` en modo `useGlobal`; atajo `C` en fase capture con `stopPropagation` (D6) y `⌘F` con focus al buscador; auto-apertura con `?new=1` + `history.replaceState`; estados vacíos (sin tareas / sin coincidencias con limpiar); pie con contadores y kbd hints; persistir vista en `localStorage('tasks.view')`
- [X] T015 [US1] Habilitar "Mis Tareas" en `resources/js/Layouts/AuthenticatedLayout.vue` (navItems: `to: tasks.index.url()`, match '/tasks') y cambiar la acción de paleta "Crear nueva tarea" para navegar a `tasks.index.url() + '?new=1'` (depende T014)

**Checkpoint**: US1 funcional de forma independiente — Mis Tareas navegable, lista completa y CRUD vía diálogo

---

## Phase 4: User Story 2 - Tablero kanban (Priority: P2)

**Goal**: Vista kanban en la misma página: columnas por estado con DnD, columna Bloqueado deshabilitada, creación rápida por columna y tarjetas con datos reales.

**Independent Test**: Alternar a kanban, arrastrar tarjetas entre columnas con persistencia, crear desde columna con estado preseleccionado y verificar contadores y la columna Bloqueado "Próximamente".

### Implementation for User Story 2

- [X] T016 [P] [US2] Crear `resources/js/Components/Tasks/Board/TaskBoardColumn.vue`: header con dot de estado, nombre, contador y (opcional) WIP deshabilitado "Próximamente"; zona de drop (`@dragover/@drop`) con resaltado; slot de tarjetas; botón "Añadir tarea rápida" (emit add) excepto en Terminado; soporte de columna deshabilitada (Bloqueado: chip "Próximamente", sin drop ni add)
- [X] T017 [P] [US2] Crear `resources/js/Components/Tasks/Board/TaskBoardCard.vue`: icono de tipo, título, pill Proyecto/"Sin Proyecto", sprint/"Sin Sprint", prioridad, conteo de comentarios (`chat_bubble_outline` + n), `Avatar` del responsable; atenuada cuando está terminada; `draggable` + emit dragstart; click → emit open
- [X] T018 [US2] Crear `resources/js/Components/Tasks/Board/TaskBoard.vue` (depende T016/T017): columnas Por Hacer/En Curso/En Revisión/Terminado + Bloqueado deshabilitado; DnD nativo (dragstart con dataTransfer 'text/task-id', dragover preventDefault, drop → emit moved(task,status)); emite open/edit/comment por tarjeta
- [X] T019 [US2] Integrar el kanban en `resources/js/pages/Tasks/Index.vue` (depende T014/T018): rama de vista kanban al alternar; `moved` → `router.put(tasks.update.url(id), {status})`; `add(status)` → abre `TaskFormModal` en create con `initialStatus`; tarjetas terminadas atenuadas; verificación en vivo (serve + vite)

**Checkpoint**: US1 + US2 — lista y kanban operativos sobre los mismos datos

---

## Phase 5: User Story 3 - Refinamientos del diálogo (Priority: P3)

**Goal**: Fidelidad completa al diseño del modal: secciones "Próximamente" (estimación, vencimiento, toolbar), flujo "Crear otra al guardar", atajo ⌘↵ y micro-copy.

**Independent Test**: Verificar en el diálogo los controles deshabilitados con "Próximamente", crear varias tareas seguidas sin cerrar, guardar con ⌘↵ y leer el hint de redacción.

### Implementation for User Story 3

- [X] T020 [P] [US3] Extender `resources/js/Components/Tasks/Board/TaskFormModal.vue` con la sección "Próximamente" deshabilitada: Puntos de Estimación (chips 1/2/3/5/8/13 inertes), Fecha de Vencimiento (campo date deshabilitado) y nota de barra de herramientas de marcado/vista previa; nota de tipos de tarea adicionales próximos (FR-008)
- [X] T021 [US3] Añadir a `resources/js/Components/Tasks/Board/TaskFormModal.vue`: checkbox "Crear otra al guardar" (al guardar limpia el formulario y mantiene el diálogo abierto; al desactivarlo cierra), atajo `⌘↵`/`Ctrl+↵` para enviar, kbd hints en el pie y micro-copy del hint de título

**Checkpoint**: Las 3 historias completas; diálogo fiel a la referencia

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Gates de calidad, responsive/accesibilidad y validación del quickstart

- [X] T022 [P] Quality gates: `vendor/bin/pint --dirty --format agent`, `npm run check:fix`, `npm run types:check` y `npm run build` sin errores
- [X] T023 [P] Pase responsive/accesibilidad: a 375px sin scroll horizontal de página (lista una columna, kanban con scroll de columnas, diálogo a ancho completo); recorrido con teclado (C, ⌘F, ESC, menús, diálogo); anillos de foco visibles; `browser-logs` y consola sin errores
- [X] T024 [P] Validar `quickstart.md` (§0-§7) contra la implementación real y actualizarlo si algún paso difiere
- [X] T025 `php artisan test --compact` completo en verde y commit final de la feature

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias
- **Foundational (Phase 2)**: bloquea todo (T002→T004/T005 secuencial TDD; T003→T006; T007 independiente [P])
- **User Stories**: US1 (T008-T015) después de Foundational; US2 (T016-T019) después de US1 (el kanban vive en la página de US1); US3 (T020-T021) después de US1 (el diálogo nace en US1)
- **Polish (Phase 6)**: al final

### User Story Dependencies

- **User Story 1 (P1)**: after Foundational — **MVP**
- **User Story 2 (P2)**: after US1 (superficie compartida)
- **User Story 3 (P3)**: after US1 (extiende el diálogo de US1)

### Within Each User Story

- Tests primero en rojo (T002→T004/T005; T003→T006; T008→T009); componentes [P] comparten solo el contrato (types)
- Cierre de fase con verificación en vivo (`php artisan serve` + `npm run dev`)

### Parallel Opportunities

- T002 + T003 + T007 (foundation: tests y extensión del modal de comentarios, archivos distintos)
- T010–T013 (los 4 componentes de US1 son archivos independientes)
- T016 + T017 (columna y tarjeta kanban)
- T020 + T021 (mismo archivo pero bloques distintos; pueden ir juntos o en paralelo por secciones)
- Polish: T022 + T023 + T024

---

## Parallel Example: User Story 1

```bash
# Tests primero (solo):
Task: "Crear tests/Feature/Tasks/TasksPageTest.php (RED)"

# Luego payload backend:
Task: "Implementar payload de TaskController::index (GREEN)"

# En paralelo, los componentes:
Task: "Crear resources/js/Components/Tasks/Board/TaskFilters.vue"
Task: "Crear resources/js/Components/Tasks/Board/TaskRow.vue"
Task: "Crear resources/js/Components/Tasks/Board/TaskGroupSection.vue"
Task: "Crear resources/js/Components/Tasks/Board/TaskFormModal.vue"

# Integración secuencial al final: Index.vue → shell → verificación
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1 (Setup) + Phase 2 (Foundational) — T001-T007
2. Phase 3 (US1) — T008-T015: Mis Tareas con lista completa + diálogo
3. **STOP y VALIDA**: quickstart §1-§5; demo si se desea

### Incremental Delivery

1. Setup + Foundational → base
2. US1 → Mis Tareas lista + CRUD → validar (MVP)
3. US2 → kanban → validar (quickstart §3)
4. US3 → refinamientos del diálogo → validar (§4)
5. Polish → gates finales

### Parallel Team Strategy

- Desarrollador A: T002+T004+T005 (dominio standalone) y T008+T009 (payload)
- Desarrollador B: T003+T006 (rutas/controlador) y T010-T013 (componentes US1)
- Desarrollador C: T007 y T016-T018 (kanban) en cuanto T001 existe
- Converger en T014, T015, T019 y las integraciones

---

## Notes

- [P] tasks = different files, no dependencies
- IDs secuenciales en orden de ejecución; [P] ejecutables en paralelo entre sí
- Las reglas de agrupamiento (FR-002) y los contadores viven en el contrato §5 — no improvisar en implementación
- "Próximamente" es siempre un estado deshabilitado real (sin navegación, sin foco, sin datos simulados)
- Verificar tests en rojo antes de cada implementación y en verde después
- El detalle de proyecto (Show) y las rutas anidadas `projects.tasks.*` deben seguir verdes en todo momento (regresión)
