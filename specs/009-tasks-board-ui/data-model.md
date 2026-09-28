# Data Model: Rediseño del flujo de tareas (009)

## Cambio de esquema (único)

### Migración `make_tasks_project_id_nullable`

- `tasks.project_id` FK → **`nullable()`** (sigue con `cascadeOnDelete` cuando existe proyecto; `nullOnDelete` no aplica — al eliminar proyecto con tareas huérfanas... decisión: se conserva `cascadeOnDelete` del lado del proyecto, es decir, borrar proyecto borra sus tareas como hoy; las tareas huérfanas nacen solo por creación sin proyecto).
- Nada más: sin columnas nuevas (rechazado `created_by`, ver research D2).

### Ajustes de modelo

- `Task`: `#[Fillable(...)]` igual; PHPDoc `@property int|null $project_id`; `project(): BelongsTo` (nullable).
- `Project::tasks()` sin cambios. `progressPercentage()` cuenta solo tareas del proyecto (las huérfanas no afectan — sigue la fórmula existente).

## Tarea (estado final del dominio)

| Campo         | Tipo                        | Reglas                                                                   |
| ------------- | --------------------------- | ------------------------------------------------------------------------ |
| `id`          | int                         |                                                                          |
| `project_id`  | FK → projects, **nullable** | null = tarea independiente ("Sin Proyecto")                              |
| `sprint_id`   | FK → sprints, nullable      | solo con proyecto; `nullOnDelete`                                        |
| `assignee_id` | FK → users, nullable        | con proyecto: debe ser miembro; sin proyecto: debe ser el usuario actual |
| `title`       | string(255)                 | obligatorio                                                              |
| `description` | text, nullable              | texto libre con marcado (capacidad existente)                            |
| `type`        | enum `TaskType`             | bug / feature / test / other (default other)                             |
| `priority`    | enum `TaskPriority`         | low / medium / high / urgent (default medium)                            |
| `status`      | enum `TaskStatus`           | backlog / todo / in_progress / in_review / done (default backlog)        |

Relaciones: `project` (nullable), `sprint`, `assignee`, `comments` (cascade al borrar tarea).

## Validación (form request global `SaveTaskRequest`)

- **store**: `title` required max:255; `description` nullable; `type/priority` a veces + `Rule::enum`; `status` a veces + enum (estado inicial); `project_id` nullable exists:projects,id → **si hay proyecto**: `assignee_id` nullable exists:users + debe ser miembro del proyecto, `sprint_id` nullable exists:sprints + mismo proyecto → **si no hay proyecto**: `assignee_id` debe ser el usuario autenticado, `sprint_id` prohibido (`prohibited`).
- **update**: mismas reglas en modo `sometimes` (el círculo de estado y el drag envían solo `{status}`).

## Máquina de estados (existente, sin cambios)

- Transiciones **libres** entre estados (flujo no restringido, según tests vigentes). El botón circular y el arrastre ofrecen los 5 estados; el kanban usa todo menos backlog.
- `backlog` existe como estado pero no como columna kanban (vive en la vista lista, grupo "Próximas & Backlog").

## Permisos (TaskPolicy re-anclada a `(User, Task)`)

- Con proyecto (reglas actuales preservadas): ver/comentar/crear/actualizar = miembro del proyecto y proyecto activo; eliminar = líder del proyecto y activo.
- Sin proyecto (nuevo): actualizar/eliminar = `assignee_id === user`.
- Nota: la firma cambia de `(User, Project)` a `(User, Task)`; los call sites actuales pasan la tarea (ajuste mecánico en `ProjectTaskController`, comportamiento idéntico).

## Derivaciones de lectura (payload de Mis Tareas)

- Visibilidad: tarea visible si `assignee_id = yo` **o** `project.owner_id = yo` **o** soy miembro de `project` (con proyecto), o es huérfana y soy su responsable.
- `commentsCount` = `withCount('comments')` (sin cuerpos).
- Agrupamiento (cliente, reglas deterministas — ver contrato §5): solo tareas no terminadas salvo pestaña "Completadas".

## Propiedades FUTURAS documentadas (sin persistencia en 009)

Pendientes para features posteriores con su migración; en 009 se muestran como "Próximamente" (FR-008):

| Propiedad                 | Tipo propuesto                                                        | Uso en la referencia                       |
| ------------------------- | --------------------------------------------------------------------- | ------------------------------------------ |
| clave legible (`key`)     | string, folio por proyecto (p. ej. PAY-104) + `TSK-` para huérfanas   | identificador en fila/tarjeta              |
| `due_date`                | date                                                                  | "Hoy/Mañana/Vencida", campo en modal       |
| `story_points`            | smallint                                                              | chips 1/2/3/5/8/13 en modal                |
| subtareas                 | tabla `subtasks` (task_id, título, done)                              | "2/4 subtareas" + barra de progreso        |
| adjuntos                  | tabla `attachments`                                                   | conteo en tarjeta, botón en toolbar        |
| estado `blocked` + motivo | case en `TaskStatus` + `blocked_reason`                               | columna "Bloqueado" del kanban             |
| tipos extra               | cases en `TaskType` (seguridad, mejora, documentación, investigación) | opciones extra del select                  |
| colores por proyecto      | `accent_color` en projects (ya documentado en 008)                    | pildoras de color por proyecto             |
| límites WIP               | entero por columna/estado                                             | chip "WIP: 6/8"                            |
| panel de previsualización | vista rápida al estilo Linear (tecla Espacio)                         | hint del pie de página                     |
| paginación/virtualización | server-side o ventana virtual                                         | FR-015 al superar varios cientos de tareas |
