# Implementation Plan: Unificar vistas de tareas del proyecto con las vistas globales

**Branch**: `019-unify-project-task-views` | **Date**: 2026-10-09 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/019-unify-project-task-views/spec.md`

## Summary

Reutilizar los componentes de las vistas globales en la ficha de proyecto
(`/projects/{id}`): la vista **Backlog** adopta `BacklogSectionList` +
`BacklogTaskRow` (el estilo de `/backlog`, feature 018) y la vista **Kanban**
adopta `TaskBoard` + `TaskBoardCard` (el tablero de `/tasks`). Para alimentarlos
se **extiende el payload de tareas de `ProjectController@projectPayload`** con
los campos que hoy solo entrega el backlog global (`code`, `points`, `createdAt`,
`completedAt`, `authorId`, `sprint.status`) — cambio aditivo que no rompe
ninguna aserción existente (todas son a nivel campo). El cambio de estado en
kanban se unifica al patrón global: `PUT /tasks/{id}` con solo `{status}`.
Los componentes legacy (`BacklogView.vue`, `KanbanBoard.vue`, `TaskCard.vue`)
quedan sin uso y se eliminan. La vista Lista no se toca. Sin dependencias nuevas.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13.32), Vue 3 + Inertia v3, Tailwind v4,
Wayfinder, Pest. Sin cambios de stack ni dependencias.

**Primary Dependencies**: Reutilización de componentes existentes:
`Components/Backlog/{BacklogSectionList,BacklogTaskRow}.vue`,
`Components/Tasks/Board/{TaskBoard,TaskBoardCard,TaskListPanel}.vue`,
`TaskFormModal.vue`. Payload: `ProjectController@projectPayload`.

**Storage**: PostgreSQL; sin cambios de esquema (las columnas `code`/`points`
ya existen desde la feature 018).

**Testing**: Pest. Ajustes aditivos en `ProjectShowPayloadTest` (nuevos campos)
y test nuevo que fija el patrón de cambio de estado unificado (status-only PUT
global sobre tarea de proyecto).

**Target Platform**: Web monolítica Inertia (mismo alcance responsivo que las
páginas globales: tablero con scroll horizontal en pantallas pequeñas).

**Project Type**: web-app monolito (Constitución I).

**Performance Goals**: Client-side como las referencias; listas de cientos de
tareas por proyecto (mismo orden de magnitud que `/tasks` y `/backlog`).

**Constraints**: Constitución V (YAGNI: reutilizar, no clonar; eliminar código
muerto). No tocar la vista Lista ni los endpoints de proyecto
(`projects.tasks.*` siguen existiendo para otros flujos como el modal de
creación con `initialProject`).

**Scale/Scope**: 1 controlador (payload aditivo), 1 página (Projects/Show:
sustituir 2 vistas, añadir filtro de sprint a nivel de sección, estado vacío),
3 componentes legacy eliminados, 0 migraciones, tests aditivos.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Evaluación |
| --- | --- |
| I. Monolith-First con Inertia | PASS — Todo sigue en páginas Inertia; no hay API nueva. |
| II. Alineación Ecosistema Laravel | PASS — Reutilización de componentes Vue existentes; único cambio backend es un mapeo de payload en el controlador. |
| III. Test-First con Pest | PASS — Tests aditivos planeados para el payload extendido y el patrón de cambio de estado; suite existente (`TaskBoardTest`, `ProjectShowPayloadTest`) debe seguir verde sin modificaciones de comportamiento. |
| IV. Tipado Estricto y Enums | PASS — Los campos nuevos usan tipos existentes (`TaskStatusValue` para `sprint.status`, `string|null` para fechas ISO); sin estados como strings sueltos. |
| V. Simplicidad y YAGNI | PASS — Se reutilizan los componentes de las páginas globales en lugar de crear variantes; se eliminan 3 componentes legacy que quedan huérfanos. |

Sin violaciones. Complexity Tracking: no aplica.

**Post-design re-check (tras Phase 1)**: el diseño mantiene el monolito, no
añade dependencias, no introduce estados nuevos, y cada cambio de payload tiene
prueba de feature asociada. PASS.

## Project Structure

### Documentation (this feature)

```text
specs/019-unify-project-task-views/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output (contrato de payload; sin cambios de esquema)
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
└── tasks.md             # Phase 2 output (speckit-tasks)
```

### Source Code (repository root)

```text
app/
└── Http/
    └── Controllers/
        └── ProjectController.php        # EXISTENTE: projectPayload() + code/points/createdAt/completedAt/authorId/sprint.status

resources/js/
├── pages/
│   └── Projects/
│       └── Show.vue                     # EXISTENTE: sustituir vistas backlog/kanban, filtro de sprint a nivel sección
├── components/
│   ├── Backlog/                         # REUTILIZADOS sin cambios (feature 018)
│   │   ├── BacklogSectionList.vue
│   │   └── BacklogTaskRow.vue
│   ├── Tasks/
│   │   └── Board/                       # REUTILIZADOS sin cambios
│   │   │   ├── TaskBoard.vue
│   │   │   └── TaskBoardCard.vue
│   │   ├── BacklogView.vue              # ELIMINAR (huérfano tras el cambio)
│   │   ├── KanbanBoard.vue              # ELIMINAR (huérfano tras el cambio)
│   │   └── TaskCard.vue                 # ELIMINAR (huérfano tras el cambio)
│   └── Tasks/Board/TaskFormModal.vue    # EXISTENTE: sin cambios (ambas vistas reutilizan el modal de la página)
└── types/
    ├── project.ts                       # EXISTENTE: ProjectTask añade code/points/createdAt/completedAt/authorId
    └── backlog.ts                       # EXISTENTE: sin cambios (el payload extendido satisface BacklogTask)

tests/
└── Feature/
    ├── Projects/
    │   └── ProjectShowPayloadTest.php   # EXISTENTE: tests aditivos de nuevos campos
    └── Tasks/
        └── TaskCrudTest.php             # EXISTENTE: + test status-only PUT global sobre tarea de proyecto
```

**Structure Decision**: estructura existente del monolito; los componentes de
las páginas globales se consumen tal cual (presentacionales y prop-driven), por
lo que la unificación vive casi toda en `Projects/Show.vue` + el payload.

## Complexity Tracking

> Sin violaciones constitucionales. Decisiones con alternativas rechazadas en
> [research.md](research.md).
