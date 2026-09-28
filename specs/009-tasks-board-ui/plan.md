# Implementation Plan: Rediseño del flujo de tareas — listado, kanban y creación

**Branch**: `[009-tasks-board-ui]` | **Date**: 2026-09-26 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/009-tasks-board-ui/spec.md`

## Summary

Nueva página global **"Mis Tareas"** (lista agrupada + kanban) con diálogo refinado de crear/editar tarea, siguiendo las referencias de Stitch y el design system. Incluye la decisión de alcance Q1=A: **tareas independientes** (proyecto opcional) con migración ligera y rutas globales de tareas desacopladas del proyecto. Los componentes heredados de tareas (grises, de features 004/005) se conservan intactos para el detalle de proyecto; la página global construye componentes nuevos con tokens. Todo lo que el dominio no soporta (estimación, vencimientos, subtareas, adjuntos, bloqueo, tipos extra, WIP, previsualización, clave legible) se muestra como "Próximamente" y queda documentado como futuro. Sin dependencias nuevas.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13) + Vue 3 Composition API con TypeScript + Inertia v3.

**Primary Dependencies**: Inertia v3 + Wayfinder, Tailwind v4 (tokens `@theme`), Pest. DnD nativo HTML5 (patrón existente en `KanbanBoard.vue`, sin librería). Sin dependencias nuevas.

**Storage**: SQLite dev / PostgreSQL prod. **Una migración**: `project_id` en `tasks` pasa a nullable. Sin otras columnas (ver research D4).

**Testing**: Pest, feature tests en `tests/Feature/Tasks/`. Test-first obligatorio por constitución.

**Target Platform**: Web responsive (375px sin scroll horizontal de página), modo claro.

**Project Type**: Web application (monolito Inertia).

**Performance Goals**: Filtros/agrupamiento/búsqueda client-side sobre el payload completo (objetivo: fluido hasta varios cientos de tareas; el payload lleva `commentsCount` vía `withCount`, no los comentarios completos — ver D7).

**Constraints**: Sin datos simulados; "Próximamente" deshabilitado siempre; permisos existentes preservados; doble envío bloqueado.

**Scale/Scope**: 1 página nueva (`Tasks/Index`), 1 controlador nuevo (`TaskController`), 1 migración, ~7 componentes nuevos en `Components/Tasks/Board/`, 1 request nueva, 1 endpoint JSON de detalle, cambios mínimos en shell (nav + paleta) y en `TaskCommentsModal` (prop opcional, retrocompatible).

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio                                | Evaluación                                                                                                                                                                                                                                                                                 |
| ---------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| I. Monolith-First con Inertia            | ✅ Página vía `Inertia::render`, un único endpoint JSON ligero (`tasks.show`) para comentarios bajo demanda; sin API desacoplada.                                                                                                                                                          |
| II. Alineación con el ecosistema         | ✅ Sin dependencias nuevas; `artisan make:controller/migration/test`; DnD nativo como el patrón existente.                                                                                                                                                                                 |
| III. Test-First con Pest                 | ✅ Nuevos tests de tareas independientes, rutas globales y payload de la página; tests → rojo → implementación → verde.                                                                                                                                                                    |
| IV. Tipado estricto y estados como enums | ✅ Sin estados nuevos; enums existentes intactos; tipos TS explícitos (`types/task.ts`).                                                                                                                                                                                                   |
| V. Simplicidad y YAGNI                   | ✅ Se **rechazó** añadir `created_by` (regla de permiso por responsable es suficiente); se rechazó paginación server-side (client-side hasta cientos de tareas); una sola migración mínima. La nulabilidad de `project_id` es mandato explícito de la spec (FR-011, decisión del usuario). |

Sin violaciones → **Complexity Tracking** no aplica.

## Project Structure

### Documentation (this feature)

```text
specs/009-tasks-board-ui/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
└── tasks.md             # Phase 2 output (speckit-tasks)
```

### Source Code (repository root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── TaskController.php            # NUEVO — index/store/update/destroy/show(JSON)/storeComment global
│   ├── Requests/
│   │   └── SaveTaskRequest.php           # NUEVO — creación y actualización global (parcial en drag)
│   ├── Middleware/ (sin cambios)
│   └── ...
├── Models/
│   └── Task.php                          # project_id nullable + relación nullable
└── Policies/
    └── TaskPolicy.php                    # update/delete: ancla en Task (proyecto opcional)

database/migrations/
└── 2026_09_26_000000_make_tasks_project_id_nullable.php   # NUEVO

resources/js/
├── pages/
│   └── Tasks/
│       └── Index.vue                     # NUEVO — Mis Tareas (lista + kanban + modal)
├── Components/
│   └── Tasks/
│       ├── Board/                        # NUEVO — componentes de la página global (tokens)
│       │   ├── TaskRow.vue               # fila de lista con botón circular de estado
│       │   ├── TaskGroupSection.vue      # grupo agrupado con header + contador
│       │   ├── TaskBoard.vue             # kanban (columnas + DnD nativo)
│       │   ├── TaskBoardColumn.vue       # columna (+ Bloqueado deshabilitado "Próximamente")
│       │   ├── TaskBoardCard.vue         # tarjeta kanban
│       │   ├── TaskFilters.vue           # búsqueda, filtros pills y pestañas rápidas
│       │   └── TaskFormModal.vue         # diálogo crear/editar (reemplazo visual del modal viejo en la superficie global)
│       └── TaskCommentsModal.vue         # + prop opcional global (retrocompatible con Show)
├── types/
│   └── task.ts                           # BoardTask, filtros y payload de la página
└── Layouts/
    └── AuthenticatedLayout.vue           # nav "Mis Tareas" habilitado + paleta "Crear nueva tarea" → /tasks?new=1

routes/web.php                            # rutas globales /tasks*
tests/Feature/Tasks/
├── TaskStandaloneTest.php                # NUEVO — tareas sin proyecto (CRUD, permisos por responsable, validaciones)
├── TaskGlobalRoutesTest.php              # NUEVO — endpoints globales, autorización y JSON de detalle
└── TasksPageTest.php                     # NUEVO — payload de Mis Tareas (shape, alcance, counts)
```

**Structure Decision**: se crea `Components/Tasks/Board/` (subcarpeta del directorio existente `Components/Tasks/`, sin carpetas base nuevas). Los componentes heredados (`TaskList`, `KanbanBoard`, `BacklogView`, `TaskModal`, `TaskCommentsModal` raíz) **no se tocan** salvo la prop opcional en `TaskCommentsModal`; el detalle de proyecto queda funcionalmente igual.

## Complexity Tracking

> Sin violaciones de constitución — tabla no aplica.
