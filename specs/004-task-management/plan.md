# Implementation Plan: Gestión de Tareas (Tasks)

**Branch**: `004-task-management` | **Date**: 2026-09-23 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/004-task-management/spec.md`

## Summary

Módulo de tareas anidado en proyectos sobre las features 001–003: cualquier
miembro de un proyecto **activo** crea tareas con título obligatorio,
descripción markdown opcional, tipo/prioridad con defaults, responsable y
sprint opcionales (tarea aislada cuando no hay sprint); las ve ordenadas por
recientemente actualizadas en la ficha del proyecto, las edita con la misma
validación cruzada (responsable miembro del proyecto, sprint del mismo
proyecto) y las mueve libremente por el flujo backlog → todo → in_progress →
in_review → done. Comentarios cronológicos por tarea para notas de avance;
eliminación solo propietario con confirmación y cascada de comentarios. Fuera
del estado activo la superficie queda en solo lectura con mensaje claro
(FR-010/SC-003). Con los enums `TaskType`/`TaskPriority`/`TaskStatus`
(Principio IV), `TaskPolicy` (miembro escribe / propietario elimina), sección
de UI con tres componentes locales en `Projects/Show.vue` y suites Pest en
`tests/Feature/Tasks/`. Sin paquetes nuevos, sin vistas Backlog/Kanban (fuera
de alcance por spec).

## Technical Context

**Language/Version**: PHP 8.4 (constraint `^8.3`) + TypeScript / Vue 3.5 en el frontend

**Primary Dependencies**: Laravel 13.x, Inertia Laravel 3 + `@inertiajs/vue3`, Tailwind 4, Wayfinder, Pest 5. **Sin dependencias nuevas** — se apoya en el scaffolding de las features 001–003.

**Storage**: PostgreSQL (desarrollo/producción); SQLite `:memory:` en tests (phpunit.xml)

**Testing**: Pest 5 feature tests con `TaskFactory` y `CommentFactory`; TDD obligatorio (Principio III)

**Target Platform**: Aplicación web monolítica (Laravel Herd local); despliegue web estándar

**Project Type**: web application (Inertia monolith, sin API desacoplada — Principio I)

**Performance Goals**: Lista de hasta 100 tareas del proyecto en < 2 s (SC-002) — `index('project_id')` + eager loading (`tasks.assignee`, `tasks.sprint`, `tasks.comments.author`) en la ficha; sin requisitos adicionales

**Constraints**: Mensaje claro al bloquear escrituras en proyecto no activo (FR-010/SC-003: redirect con `errors.project`, mismo mensaje verbatim que sprints); 404 indistinguible para no miembros, tareas ajenas y eliminación por colaborador; estados del dominio como enums `TaskType`/`TaskPriority`/`TaskStatus` (Principio IV); CI exige `vp check`, `vue-tsc`, `pint --test` y `phpstan`

**Scale/Scope**: MVP — sin vistas Backlog/Kanban, fechas límite, etiquetas, adjuntos, estimaciones, ni activación/cierre de sprints (spec, Assumptions); movimiento libre entre estados (sin máquina de transiciones)

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio                                | Evaluación pre-diseño                                                                                                                           | Resultado |
| ---------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- | --------- |
| I. Monolith-First con Inertia            | Controlador + sección dentro de `Projects/Show`; cero endpoints de API                                                                          | PASS      |
| II. Alineación con el Ecosistema         | Artisan (`make:model -f`, `make:policy`, `make:enum`/`make:class`, `make:migration`), Eloquent, Form Requests, Policy auto-discovery; cero deps | PASS      |
| III. Test-First con Pest                 | Suites `TaskCrudTest` / `TaskCommentsTest` / `TaskAccessTest` por historia (US1–US6) escritas antes de la implementación                        | PASS      |
| IV. Tipado Estricto y Estados como Enums | `TaskType`, `TaskPriority` y `TaskStatus` backed enums con labels (tres máquinas de valores del dominio); casts; sin strings sueltos            | PASS      |
| V. Simplicidad y YAGNI                   | Sin vistas de tablero, sin máquina de transiciones, sin ruta de lectura de comentarios, sin páginas propias; solo lo de la spec                 | PASS      |

**Post-design re-check (tras Phase 1)**: el diseño mantiene los 5 PASS — los
defaults de tipo/prioridad viven en el servidor (fusión tras validación, no en
la vista), los comentarios viajan en el payload ya existente de la ficha (sin
rutas nuevas de lectura), la destrucción queda solo en propietario vía
`TaskPolicy`, y la UI se acota a tres componentes locales reutilizando los
Breeze existentes. Sin violaciones que justificar.

## Project Structure

### Documentation (this feature)

```text
specs/004-task-management/
├── plan.md              # This file (/skill:speckit-plan command output)
├── research.md          # Phase 0 output (/skill:speckit-plan command)
├── data-model.md        # Phase 1 output (/skill:speckit-plan command)
├── quickstart.md        # Phase 1 output (/skill:speckit-plan command)
├── contracts/           # Phase 1 output (/skill:speckit-plan command)
│   └── web-tasks-surface.md
└── tasks.md             # Phase 2 output (/skill:speckit-tasks command - NOT created by /skill:speckit-plan)
```

### Source Code (repository root)

Estructura single-project (web app). Rutas reales; todo lo nuevo es aditivo
sobre las features 001–003:

```text
app/
├── Enums/
│   ├── TaskPriority.php                         # NUEVO: low/medium/high/urgent + label()
│   ├── TaskStatus.php                           # NUEVO: backlog/todo/in_progress/in_review/done + label()
│   └── TaskType.php                             # NUEVO: bug/feature/test/other + label()
├── Http/
│   ├── Controllers/
│   │   └── ProjectTaskController.php            # NUEVO: store/update/destroy + ensureProjectIsActive
│   ├── Requests/
│   │   ├── StoreCommentRequest.php              # NUEVO: body requerido
│   │   ├── StoreTaskRequest.php                 # NUEVO: validación cruzada responsable/sprint + defaults
│   │   └── UpdateTaskRequest.php                # NUEVO: idem + status libre
├── Models/
│   ├── Comment.php                              # NUEVO: + factory, BelongsTo task/author
│   ├── Project.php                              # MODIFICADO: + tasks() HasMany
│   ├── Sprint.php                               # SIN cambios estructurales (FK nullOnDelete desde tasks)
│   ├── Task.php                                 # NUEVO: + factory, relaciones, casts de enums
│   └── User.php                                 # SIN cambios (relaciones inversas no requeridas, YAGNI)
├── Policies/
│   └── TaskPolicy.php                           # NUEVO: view=miembro; create/update=miembro+activo; delete=propietario+activo
database/
├── factories/
│   ├── CommentFactory.php                       # NUEVO
│   └── TaskFactory.php                          # NUEVO: estados por enum, for(Project)/for(Sprint)
├── migrations/
│   ├── 2026_09_23_000001_create_tasks_table.php     # NUEVO: FKs nullOnDelete (sprint/assignee) + index(project_id)
│   └── 2026_09_23_000002_create_comments_table.php  # NUEVO: task_id cascade + index(task_id)
resources/js/
├── Components/
│   └── Tasks/                                   # NUEVO (componentes locales, research R-7)
│       ├── TaskList.vue                         # lista + acciones por fila
│       ├── TaskModal.vue                        # alta/edición compartida
│       └── TaskCommentsModal.vue                # hilo cronológico + alta de comentario
└── pages/
    └── Projects/
        └── Show.vue                             # MODIFICADO: sección Tareas (orquestación)
routes/
└── web.php                                      # MODIFICADO: projects.tasks.* + projects.tasks.comments.store (auth)
tests/
└── Feature/
    └── Tasks/                                   # NUEVO (suites Pest por historia)
        ├── TaskCrudTest.php                     # US1–US3, US5: CRUD, estados, vinculación, validaciones
        ├── TaskCommentsTest.php                 # US4: comentarios
        └── TaskAccessTest.php                   # US6: permisos y estados del proyecto
```

**Structure Decision**: se conserva la estructura single-project existente.
Convenciones heredadas de las features 002–003: controlador plano anidado por
convención de nombre (`ProjectTaskController`, como `ProjectMemberController`
y `ProjectSprintController`), rutas explícitas nombradas `projects.tasks.*`
dentro del grupo `auth`, Form Requests con `authorize()` de autenticación
(propiedad, membresía y estado se resuelven en controlador/policy), payload de
la ficha como arrays en camelCase con labels de enum, Policy auto-discovery
por convención (Laravel 11+), páginas Inertia TS con imports de Wayfinder
(`@/routes/projects/tasks`) y componentes Breeze existentes. La gestión vive
dentro de `Projects/Show.vue` según la assumption de la spec; a diferencia de
sprints, la superficie (dos modales + lista + formulario) se extrae en tres
componentes locales bajo `resources/js/Components/Tasks/` para no inflar
`Show.vue` más allá de lo razonable (research R-7). Los enums viven en
`app/Enums/` junto a `ProjectStatus`.
