# Implementation Plan: Tareas del Proyecto — Modal de Nueva Tarea y Listado Estilo Mis Tareas

**Branch**: `013-project-detail-tasks` | **Date**: 2026-10-01 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/013-project-detail-tasks/spec.md`

## Summary

En el detalle de un proyecto: (1) el botón "Nueva Tarea" abrirá el `TaskFormModal` mejorado (spec 012) con el proyecto preseleccionado y fijo, en lugar de solo cambiar de pestaña; (2) la sección "Tareas" en vista Lista mostrará la misma experiencia de listado que `/tasks` (grupos, filtros, acciones de fila) restringida a las tareas del proyecto, eliminando el formulario inline de creación rápida. Enfoque: reutilización del modal y de los componentes de lista con una extracción a componente compartido, una ampliación opcional del modal (`destroyUrl`), y alineación del payload de tareas del proyecto al shape `BoardTask` (sin rutas ni capacidades nuevas — ver Complejidad).

## Technical Context

**Language/Version**: PHP 8.4 (Laravel, ver `composer.json`) + Vue 3 + TypeScript (Inertia.js v3, Vite)

**Primary Dependencies**: Inertia.js (`@inertiajs/vue3`), Wayfinder (`@/routes/*`), componentes internos `Components/Tasks/Board/*` (TaskFormModal, TaskRow, TaskGroupSection, TaskBoard)

**Storage**: N/A (sin cambios de esquema; SQLite en dev vía Herd)

**Testing**: Pest (backend, `tests/Feature`), `npm run types:check` (vue-tsc), `npm run build`; validación manual guiada por [quickstart.md](quickstart.md)

**Target Platform**: Web (Laravel monolito con SPA por Inertia; dev en Herd `friday.test`)

**Project Type**: Web application (monolito: backend Laravel + frontend Inertia/Vue en `resources/js/`)

**Performance Goals**: Sin objetivos nuevos. Beneficio colateral esperado: payload del detalle de proyecto más liviano (se dejan de embeber comentarios de todas las tareas).

**Constraints**: Sin dependencias nuevas; no alterar el comportamiento de `/tasks` (regresión controlada); respetar permisos existentes (`TaskPolicy`, `canWriteTasks`)

**Scale/Scope**: 1 página de proyecto, 1 página de tareas, ~6 archivos frontend, 1 método de controlador, 2-3 componentes de tareas del proyecto retipados, 2 archivos eliminados

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

No existe `.specify/memory/constitution.md` adoptado; se aplican las convenciones del proyecto (AGENTS.md / Laravel Boost):

| Gate | Estado | Notas |
| ---- | ------ | ----- |
| Sin dependencias nuevas | ✓ PASS | Todo con componentes/rutas existentes (research D1–D9) |
| Convenciones del proyecto (Pint, Pest, make:*) | ✓ PASS | Cambio PHP solo en `ProjectController::projectPayload`; Pint al final; sin archivos nuevos de servidor |
| Estructura de directorios existente | ✓ PASS | Nuevo componente bajo `resources/js/Components/Tasks/Board/` (ubicación de sus pares) |
| Tests según reglas del proyecto | ✓ PASS | Cambio de UI + forma de payload; regresión con suite Pest existente y gates de frontend (research D9) |
| Sin cambios de servidor "contemplados" en spec | ⚠️ JUSTIFICADO | D3 alinea la forma del payload de tareas del proyecto (sin rutas/capacidades nuevas). Alternativa de adaptador cliente rechazada por duplicación de reglas de permisos. Detalle en Complexity Tracking |

**Re-evaluación post-diseño**: las decisiones D1–D9 no introducen nuevas violaciones; la única desviación (D3) queda justificada abajo y el spec la tolera como enmienda documentada de su supuesto.

## Project Structure

### Documentation (this feature)

```text
specs/013-project-detail-tasks/
├── plan.md              # Este archivo
├── research.md          # Fase 0 (D1–D9)
├── data-model.md        # Fase 1
├── quickstart.md        # Fase 1
├── contracts/           # Fase 1
│   └── 013-project-detail-tasks.md
└── tasks.md             # Fase 2 (speckit-tasks; aún no creado)
```

### Source Code (repository root)

```text
app/Http/Controllers/
└── ProjectController.php          # projectPayload(): tasks → shape BoardTask (D3)

resources/js/
├── Components/Tasks/Board/
│   ├── TaskFormModal.vue          # + prop opcional destroyUrl (D2)
│   ├── TaskListPanel.vue          # NUEVO: listado reutilizable extraído de Tasks/Index.vue (D4)
│   ├── TaskRow.vue                # sin cambios
│   └── TaskGroupSection.vue       # sin cambios
├── Components/Tasks/
│   ├── TaskList.vue               # ELIMINAR (sin usos tras D6/D7)
│   ├── TaskModal.vue              # ELIMINAR (sin usos tras D2)
│   ├── BacklogView.vue            # prop tasks: TaskItem[] → BoardTask[] (D3/D8)
│   ├── KanbanBoard.vue            # prop tasks: TaskItem[] → BoardTask[] (D3/D8)
│   └── TaskCommentsModal.vue      # sin cambios (uso en modo global desde Show.vue, D5)
├── pages/Projects/Show.vue        # botón→modal, TaskListPanel, sin form inline, comentarios globales (D1/D5/D6/D8)
├── pages/Tasks/Index.vue          # refactor a TaskListPanel, comportamiento idéntico (D4)
└── types/
    ├── task.ts                    # sin cambios (BoardTask ya existe)
    └── project.ts                 # ProjectDetail.tasks: TaskItem[] → BoardTask[] (D3)
```

**Structure Decision**: Monolito web existente; el único archivo nuevo es `TaskListPanel.vue` junto a sus pares en `Components/Tasks/Board/` (convención de specs 009/012). No hay opciones alternativas relevantes.

## Complexity Tracking

> Violación del spec: supuesto "sin cambios en el servidor".

| Violación | Why Needed | Simpler Alternative Rejected Because |
| --------- | ---------- | ------------------------------------ |
| Cambio de forma del payload `tasks` en `ProjectController::projectPayload` (D3) | El listado reutiliza `TaskRow`/grupos de `/tasks`, que consumen `BoardTask` con `commentsCount`, `canUpdate`, `canDelete` y `project`; la fuente única de esas reglas debe seguir en el servidor (`TaskController::taskPayload` ya las implementa) | Adaptador cliente `TaskItem → BoardTask`: duplicaría las reglas de `canUpdate`/`canDelete` en el cliente con riesgo de deriva respecto a `TaskPolicy`, y mantendría el payload pesado con todos los comentarios embebidos |
