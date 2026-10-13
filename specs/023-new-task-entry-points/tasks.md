---
description: 'Task list for 023-new-task-entry-points'
---

# Tasks: Accesos a "Nueva tarea" y "Acciones rápidas"

**Input**: Design documents from `/specs/023-new-task-entry-points/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/ui.md, quickstart.md

**Tests**: INCLUIDOS para la parte de servidor (Constitución III): el prop compartido `taskFormProjects` y la regresión del Backlog. La parte de interfaz se verifica con `npm run types:check` y los escenarios de `quickstart.md` (el proyecto no tiene runner de tests de frontend).

**Organization**: por historia de usuario (US1, US2 de spec.md).

## Format: `[ID] [P?] [Story] Description`

---

## Phase 1: Setup

No aplica: sin dependencias, migraciones ni configuración nuevas.

---

## Phase 2: Foundational (bloquea US1)

- [X] T001 Crear `tests/Feature/Shell/TaskFormOptionsSharedPropTest.php` (`php artisan make:test --pest Shell/TaskFormOptionsSharedPropTest --no-interaction`) con tests que deben fallar antes de T002–T003: (a) una visita completa autenticada a `/` **no** incluye el prop `taskFormProjects` (`assertInertia(fn ($page) => $page->missing('taskFormProjects'))`); (b) un partial reload a `/` con headers `X-Inertia: true`, `X-Inertia-Partial-Component: AppHome`, `X-Inertia-Partial-Data: taskFormProjects` y el `X-Inertia-Version` actual sí lo incluye; (c) contiene solo proyectos propios o donde el usuario es miembro, ordenados por título, cada uno con `members` (propietario + miembros, sin duplicados) y `sprints` solo **activos y planificados** (excluye completados); (d) un proyecto ajeno no aparece.
- [X] T002 Mover `BacklogController::projectOptions()` a un método estático `public static function taskFormOptionsFor(User $user): Collection` en `app/Models/Project.php` (misma consulta y misma forma: `id`, `title`, `members` = propietario + miembros únicos con `id`/`name`/`avatar`, `sprints` = `whereIn('status', [SprintStatus::Active, SprintStatus::Planned])` con `id`/`name`, `orderBy('title')`), con PHPDoc de array shape; actualizar `app/Http/Controllers/BacklogController.php` para usar `Project::taskFormOptionsFor($user)` y eliminar el método privado.
- [X] T003 En `app/Http/Middleware/HandleInertiaRequests.php::share()`, dentro del bloque de usuario autenticado, añadir `'taskFormProjects' => Inertia::optional(fn () => Project::taskFormOptionsFor($user)->values())` (importar `Inertia\Inertia`). Nombre distinto de `projects` para no chocar con props de página.

**Checkpoint**: T001 en verde; `php artisan test --compact tests/Feature/Backlog` en verde (regresión de la extracción).

---

## Phase 3: User Story 1 - "Nueva tarea" abre el formulario (Priority: P1) 🎯 MVP

**Goal**: inicio, inicio vacío, paleta "Crear nueva tarea" y tecla C abren el formulario de nueva tarea en la página actual.

**Independent Test**: escenarios 1–6 y 8–9 de `quickstart.md`.

### Implementation for User Story 1

- [X] T004 [US1] En `resources/js/Layouts/AuthenticatedLayout.vue`: importar `TaskFormModal` (`@/Components/Tasks/Board/TaskFormModal.vue`) y el tipo `TaskProjectOption`; añadir `taskFormOpen = ref(false)`; leer `page.props.taskFormProjects` (tipado `TaskProjectOption[] | undefined`); implementar `openTaskForm()` que: si `taskFormOpen` ya es `true` no hace nada (FR-007); cierra la paleta; si `taskFormProjects` es `undefined` hace `router.reload({ only: ['taskFormProjects'], onSuccess: () => (taskFormOpen.value = true) })`, si no abre directo. `provide('friday:open-task-form', openTaskForm)`. Montar al final del template `<TaskFormModal :open="taskFormOpen" mode="create" :projects="taskFormProjects ?? []" :current-user="user" @close="taskFormOpen = false" />` usando el usuario autenticado existente del layout (id, name, avatar).
- [X] T005 [US1] En el mismo `resources/js/Layouts/AuthenticatedLayout.vue`: cambiar el `run` de la acción de paleta "Crear nueva tarea" de `router.visit(backlogIndex.url())` a `openTaskForm` (conserva `group: 'create'`, icono y atajo `C`); en `onGlobalKeydown`, la rama de la tecla `c` (mismas guardas: sin `meta/ctrl/alt`, `!isEditableTarget`) debe además exigir `!paletteOpen.value && document.querySelector('dialog[open]') === null` y llamar `openTaskForm()` en lugar de abrir la paleta. No tocar el manejador de ⌘K.
- [X] T006 [P] [US1] En `resources/js/Components/Home/ActivityDashboard.vue`: inyectar `friday:open-task-form` (`inject<() => void>('friday:open-task-form', () => {})`) y usarlo en el botón "Nueva Tarea" del encabezado en lugar de `openPalette`; eliminar la inyección de `friday:open-palette` si queda sin uso.
- [X] T007 [P] [US1] En `resources/js/Components/AppShell/HomeCanvas.vue`: inyectar `friday:open-task-form` y usarlo en la tarjeta "Nueva tarea · Presiona C" (≈ línea 100); dejar el botón de acciones rápidas del encabezado (≈ línea 35, icono `keyboard_command_key`) abriendo la paleta.

**Checkpoint**: `npm run types:check` sin errores; escenarios 1–6 y 8–9 de `quickstart.md` pasan; en Mis Tareas la tecla C abre un solo formulario.

---

## Phase 4: User Story 2 - Barra lateral "Acciones rápidas" (Priority: P1)

**Goal**: el botón principal de la barra lateral se llama "Acciones rápidas", muestra ⌘K y abre la paleta sin navegar.

**Independent Test**: escenario 7 de `quickstart.md` en varias páginas.

### Implementation for User Story 2

- [X] T008 [P] [US2] En `resources/js/Components/AppShell/AppShellSidebar.vue`: renombrar el emit `newTask` → `quickActions` (en `defineEmits` y en `@click`); etiqueta "Acciones rápidas"; icono `bolt`; añadir a la derecha un `<kbd>` "⌘K" discreto (`text-[10px]`, fondo `bg-white/20`, como el `kbd` del botón "Nuevo Proyecto"); `aria-label="Acciones rápidas (⌘K)"`.
- [X] T009 [US2] En `resources/js/Layouts/AuthenticatedLayout.vue`: cambiar `@new-task="goToQuickCapture"` por `@quick-actions="openPalette"` y eliminar `goToQuickCapture` (y el import de `backlogIndex` solo si queda sin uso; la acción de paleta "Ir al Backlog" lo sigue usando).

**Checkpoint**: escenario 7 pasa en Inicio, Proyectos, Mis Tareas y Backlog.

---

## Phase 5: Polish & Cross-Cutting Concerns

- [X] T010 Ejecutar `vendor/bin/pint --dirty --format agent`.
- [X] T011 [P] Ejecutar `npm run types:check` y corregir errores en los archivos tocados.
- [X] T012 Ejecutar `php artisan test --compact tests/Feature/Shell tests/Feature/Backlog tests/Feature/Tasks` y luego la suite completa `php artisan test --compact`.
- [ ] T013 Validar en navegador los escenarios 1–9 de `specs/023-new-task-entry-points/quickstart.md` (incluido el panel Network para el escenario 9).

---

## Dependencies & Execution Order

- **Foundational (T001–T003)** → **US1 (T004–T007)**: el formulario global necesita `taskFormProjects`.
- **US2 (T008–T009)**: independiente de Foundational y de US1 salvo que T009 y T004/T005 editan `AuthenticatedLayout.vue` (hacerlos en secuencia).
- **Polish (T010–T013)**: al final.

### Parallel Opportunities

- T006, T007 y T008 tocan archivos distintos y pueden hacerse en paralelo una vez hecho T004.
- T001 puede escribirse en paralelo con la lectura de T002.

```text
# Tras T004–T005:
T006 [US1] ActivityDashboard.vue  |  T007 [US1] HomeCanvas.vue  |  T008 [US2] AppShellSidebar.vue
# Luego:
T009 [US2] AuthenticatedLayout.vue
```

## Implementation Strategy

### MVP (US1)

1. Foundational (T001–T003) con tests en verde.
2. US1 (T004–T007): el inicio abre el formulario. Validar escenarios 1–6.

### Entrega incremental

1. MVP → validar.
2. US2 (T008–T009) → validar escenario 7.
3. Polish.
