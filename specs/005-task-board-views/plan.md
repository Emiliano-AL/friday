# Implementation Plan: Vistas Backlog y Kanban de Tareas

**Branch**: `005-task-board-views` | **Date**: 2026-09-24 | **Spec**: [spec.md](./spec.md)

## Summary

Vistas de tareas dentro de la sección Tareas de la ficha del proyecto, con
cero cambios de backend y cero dependencias nuevas: un conmutador de tres
pestañas (Lista existente, Backlog, Kanban) en `Projects/Show.vue`. El Backlog
ordena por prioridad (rank urgent→low, desc por omisión, asc conmutable) y
por sprint (por `startDate`, aisladas al final) y filtra por sprint (todas /
sin sprint / concreto), todo como cómputo cliente sobre el payload existente.
El Kanban agrupa tarjetas compactas en cinco columnas derivadas del enum
`TaskStatus` con contadores y vacíos; el drag-and-drop nativo (sin librerías)
mueve tarjetas entre columnas reutilizando `projects.tasks.update` con el
payload completo — sin optimismo local, con el mismo contrato de errores y el
mensaje de bloqueo heredado. Fuera del estado activo las vistas se ven y
ordenan, pero el arrastre se deshabilita. Cobertura: `TaskBoardTest` fija el
contrato de payload y la cadena de transiciones; la interacción de arrastre se
valida en navegador.

## Technical Context

**Language/Version**: PHP 8.4 (constraint `^8.3`) + TypeScript / Vue 3.5 en el frontend

**Primary Dependencies**: Laravel 13.x, Inertia Laravel 3 + `@inertiajs/vue3`, Tailwind 4, Wayfinder, Pest 5. **Sin dependencias nuevas** (arrastre nativo HTML5).

**Storage**: PostgreSQL (desarrollo/producción); SQLite `:memory:` en tests — **sin migraciones nuevas**

**Testing**: Pest 5 (`TaskBoardTest` de feature para el contrato de payload y las transiciones); validación del arrastre en navegador real (Chrome con `DragEvent` + `DataTransfer` construidos por script)

**Target Platform**: Aplicación web monolítica (Laravel Herd local); navegadores modernos (drag-and-drop nativo)

**Project Type**: web application (Inertia monolith, sin API desacoplada — Principio I)

**Performance Goals**: Backlog y tablero con hasta 100 tareas en < 2 s (SC-002) — cómputo cliente sobre el payload ya cargado; movimiento de tarjeta < 3 s (SC-001) — un `PUT` por movimiento

**Constraints**: cero rutas/endpoints nuevos (reutilización de `projects.tasks.update`); cero dependencias nuevas; columnas solo de los cinco cases de `TaskStatus`; arrastre deshabilitado fuera del estado activo con el mensaje `errors.project` heredado; CI exige `vp check`, `vue-tsc`, `pint --test` y `phpstan`

**Scale/Scope**: MVP — sin ordenación manual dentro de columna (sin posiciones), sin persistencia de pestaña/orden/filtro, sin nuevas entidades

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio                                | Evaluación pre-diseño                                                                                            | Resultado |
| ---------------------------------------- | ---------------------------------------------------------------------------------------------------------------- | --------- |
| I. Monolith-First con Inertia            | Vistas dentro de `Projects/Show`; única escritura vía endpoint Inertia existente; sin API                        | PASS      |
| II. Alineación con el Ecosistema         | Componentes Vue + TypeScript con `vue-tsc`; drag-and-drop nativo del navegador; cero deps nuevas                 | PASS      |
| III. Test-First con Pest                 | `TaskBoardTest` (contrato de payload + cadena de transiciones) antes de la integración del tablero               | PASS      |
| IV. Tipado Estricto y Estados como Enums | Columnas derivadas de `TaskStatus` (única fuente); labels desde el enum; tipos compartidos en `Components/Tasks` | PASS      |
| V. Simplicidad y YAGNI                   | Sin endpoints, migraciones, posiciones ni persistencia de preferencias; movimiento sin optimismo local           | PASS      |

**Post-design re-check (tras Phase 1)**: el diseño mantiene los 5 PASS — el
backend queda literalmente sin tocar (research R-1), las vistas consumen el
payload existente (R-5/R-6) y el arrastre se apoya en el contrato de errores
heredado (R-3/R-7). Sin violaciones que justificar.

## Project Structure

### Documentation (this feature)

```text
specs/005-task-board-views/
├── plan.md              # This file (/skill:speckit-plan command output)
├── research.md          # Phase 0 output (/skill:speckit-plan command)
├── data-model.md        # Phase 1 output (/skill:speckit-plan command)
├── quickstart.md        # Phase 1 output (/skill:speckit-plan command)
├── contracts/           # Phase 1 output (/skill:speckit-plan command)
│   └── web-board-views.md
└── tasks.md             # Phase 2 output (/skill:speckit-tasks command - NOT created by /skill:speckit-plan)
```

### Source Code (repository root)

Cambios **íntegramente frontend + tests** (research R-1); sin migraciones ni
rutas nuevas:

```text
resources/js/
├── Components/
│   └── Tasks/                                # MODIFICADO (componentes nuevos)
│       ├── BacklogView.vue                   # NUEVO: lista ordenable + filtro por sprint
│       ├── KanbanBoard.vue                   # NUEVO: columnas por estado + drag-and-drop
│       └── TaskCard.vue                      # NUEVO: tarjeta compacta (Kanban)
└── pages/
    └── Projects/
        └── Show.vue                          # MODIFICADO: pestañas Lista/Backlog/Kanban
tests/
└── Feature/
    └── Tasks/
        └── TaskBoardTest.php                 # NUEVO: contrato de payload + cadena de transiciones
```

**Structure Decision**: se conserva la estructura single-project existente y
el patrón de componentes locales de 004 (`Components/Tasks/`). Las vistas son
componentes de presentación que consumen `project.tasks`/`project.sprints`
(props existentes) y emiten el movimiento al padre, que ejecuta el `PUT` vía
Wayfinder (`update` de `@/routes/projects/tasks`) — el mismo helper de
Inertia usado por `TaskModal`. Los tipos se importan del `types.ts` compartido
creado en 004. No se generan clases PHP nuevas: la única pieza de servidor que
se toca es la suite de tests.
