# Data Model: Gestión de Tareas (Tasks)

**Feature**: 004-task-management | **Date**: 2026-09-23
**Base**: `projects` (002), `sprints` (003), `users` (001). Aditivo.

## Entity: Task (nuevo)

Unidad de trabajo del proyecto, aislada o vinculada a un sprint.

| Campo                       | Tipo                             | Reglas                                                                                                |
| --------------------------- | -------------------------------- | ----------------------------------------------------------------------------------------------------- |
| `id`                        | bigint PK                        | —                                                                                                     |
| `project_id`                | FK → `projects.id` (cascade)     | requerido; pertenencia obligatoria (FR-001)                                                           |
| `sprint_id`                 | FK → `sprints.id` (nullOnDelete) | opcional; debe ser sprint del mismo proyecto (FR-003); sin valor = tarea aislada                      |
| `assignee_id`               | FK → `users.id` (nullOnDelete)   | opcional; debe ser miembro del proyecto (FR-002)                                                      |
| `title`                     | string(255)                      | requerido, max 255; no vacío tras `trim`                                                              |
| `description`               | text nullable                    | opcional; admite markdown, se conserva tal cual                                                       |
| `type`                      | string (cast `TaskType`)         | `bug`/`feature`/`test`/`other`; default `other` (FR-004)                                              |
| `priority`                  | string (cast `TaskPriority`)     | `low`/`medium`/`high`/`urgent`; default `medium` (FR-004)                                             |
| `status`                    | string (cast `TaskStatus`)       | `backlog`/`todo`/`in_progress`/`in_review`/`done`; default `backlog` (FR-007); alta siempre `backlog` |
| `created_at` / `updated_at` | timestamps                       | —                                                                                                     |

**Índices**: `index('project_id')` (FR-005/SC-002).

**Relaciones**:

- `project(): BelongsTo` → Project
- `sprint(): BelongsTo` → Sprint (nullable)
- `assignee(): BelongsTo` → User (nullable)
- `comments(): HasMany` → Comment (orden cronológico asc al exponer)

**Reglas de dominio**:

- El estado inicial de toda tarea es `backlog`; el movimiento entre los cinco
  estados es libre (spec, Assumptions; sin máquina de transiciones).
- Los valores por omisión de tipo/prioridad se aplican en servidor cuando el
  cliente no los envía (research R-6).
- La gestión (crear/editar/estado/comentar) exige proyecto `active` (FR-010);
  la regla vive en `TaskPolicy` + chequeo del controlador (research R-3).
- Borrado del proyecto → cascade a sus tareas; borrado de un sprint → sus
  tareas quedan aisladas (`sprint_id` → null); borrado de la tarea → cascade
  a sus comentarios (FR-009).

## Entity: Comment (nuevo)

Nota o actualización de avance sobre una tarea.

| Campo                       | Tipo                      | Reglas                                      |
| --------------------------- | ------------------------- | ------------------------------------------- |
| `id`                        | bigint PK                 | —                                           |
| `task_id`                   | FK → `tasks.id` (cascade) | requerido                                   |
| `user_id`                   | FK → `users.id` (cascade) | requerido; autor = miembro que comenta      |
| `body`                      | text                      | requerido; no vacío tras `trim` (edge case) |
| `created_at` / `updated_at` | timestamps                | —                                           |

**Índices**: `index('task_id')` (listado cronológico por tarea).

**Relaciones**: `task(): BelongsTo` → Task; `author(): BelongsTo` → User.

**Reglas de dominio**: solo miembros del proyecto comentan (FR-008); se listan
en orden cronológico ascendente junto a la tarea; se eliminan en cascada con
la tarea.

## Enums (nuevo, Principio IV)

Backed enums de string con `label()` en español (mismo patrón que
`ProjectStatus`):

```php
enum TaskType: string { case Bug = 'bug'; case Feature = 'feature'; case Test = 'test'; case Other = 'other'; }
enum TaskPriority: string { case Low = 'low'; case Medium = 'medium'; case High = 'high'; case Urgent = 'urgent'; }
enum TaskStatus: string { case Backlog = 'backlog'; case Todo = 'todo'; case InProgress = 'in_progress'; case InReview = 'in_review'; case Done = 'done'; }
```

Sin `transitions()`: la spec fija movimiento libre dentro del enum
(research R-1).

## Entity: Project (existente, cambios mínimos)

- `tasks(): HasMany` → Task (sin orden global; el orden `updated_at` desc se
  aplica al construir el payload, FR-005).
- Payload de `Projects/Show` gana `tasks` (research R-8). Las opciones de
  responsable (`owner` + `members`) y de sprint (`sprints`) ya existen en el
  payload.
- `ProjectStatus::isActive()` sigue siendo la llave del bloqueo de escritura
  (FR-010).

## Entity: Sprint (existente, sin cambios estructurales)

- Desde Sprint: (futura) relación inversa `tasks()` — solo si el módulo de
  cierre de sprints la necesita; no requerida en esta iteración.
- La relación Task→Sprint es nullable; el borrado de un sprint nunca borra
  tareas (research R-2).

## Validation rules (Form Requests)

- **Store/Update task**: `title` required string max:255; `description`
  nullable string; `type`/`priority` sometimes + valor del enum (default
  `other`/`medium` aplicado en servidor); `assignee_id` nullable + exists
  users + es miembro del proyecto; `sprint_id` nullable + exists sprints +
  pertenece al proyecto. **Update** añade `status` con valor de `TaskStatus`
  (movimiento libre).
- **Store comment**: `body` required string.
- En creación el estado no se acepta del cliente: siempre `backlog`.

## Política de borrado

- Tarea: hard delete por el propietario con confirmación en UI (FR-009);
  efecto limitado a la tarea y sus comentarios (cascade).
- Proyecto: su borrado disuelve tareas y comentarios por cascade (mismo
  contrato que `project_user` y sprints).
- Sprint: su borrado deja las tareas aisladas (`nullOnDelete`), jamás las
  elimina.
- Usuario: su borrado deja las tareas asignadas sin responsable
  (`nullOnDelete`); sus comentarios se eliminan (cascade).
