# Data Model: Tareas del Proyecto — Modal de Nueva Tarea y Listado Estilo Mis Tareas

**Fecha**: 2026-10-01
**Feature**: [spec.md](spec.md) · Decisiones: [research.md](research.md) (D1–D9)

No hay cambios de esquema ni entidades nuevas. La feature opera sobre las entidades existentes (`Project`, `Task`, `Sprint`, `User`, `Comment`) y su única modificación de datos es la **forma del payload** con el que el detalle del proyecto entrega sus tareas al frontend.

## Cambio de forma: `ProjectDetail.tasks`

**Antes** (`TaskItem`, desde `ProjectController::projectPayload`):

```
TaskItem {
  id, title, description: string|null,
  type, typeLabel, priority, priorityLabel, status, statusLabel: string,
  assignee: { id, name } | null,
  sprint: { id, name } | null,
  comments: CommentItem[]            // ← embebidos (payload pesado)
}
```

**Después** (`BoardTask`, idéntico al shape que ya consume `/tasks` vía `TaskController::taskPayload`):

```
BoardTask {
  id: number,
  title: string,
  description: string | null,
  type: TaskTypeValue,        typeLabel: string,
  priority: TaskPriorityValue, priorityLabel: string,
  status: TaskStatusValue,    statusLabel: string,
  project: { id, title } | null,              // ← nuevo; siempre != null en este contexto
  sprint: { id, name } | null,
  assignee: { id, name, avatar } | null,      // ← ahora con avatar
  commentsCount: number,                      // ← nuevo (withCount)
  canUpdate: boolean,                         // ← nuevo
  canDelete: boolean                          // ← nuevo
}
```

## Reglas de cálculo (servidor, en `projectPayload`)

- `commentsCount`: `$task->comments_count` (relación cargada con `withCount('comments')`).
- `canUpdate`: proyecto activo y (`isOwner` o miembro del proyecto). Misma regla que `TaskController::taskPayload`.
- `canDelete`: `isOwner` del proyecto y proyecto activo. Misma regla que `TaskController::taskPayload`.
- `project`: `{ id: $project->id, title: $project->title }` para todas las tareas de este payload (siempre no-nulo en contexto de proyecto).
- `assignee`: incluye `avatar`.
- **Se elimina** `'tasks.comments.author'` del `loadMissing` y el mapeo embebido de comentarios.

## Reglas de validación relevantes (sin cambios)

Creación/actualización vía `SaveTaskRequest` (rutas globales `tasks.store` / `tasks.update`):

- `title`: obligatorio al crear, máx. 255.
- `description`: nullable, máx. 50000 (markdown).
- `type`, `priority`, `status`: enums válidos (`TaskType`, `TaskPriority`, `TaskStatus`).
- `project_id`: nullable, existe en `projects`; si se informa, el usuario debe ser miembro (`TaskController@store` + `TaskPolicy::create`).
- `assignee_id`: con proyecto → debe ser miembro del proyecto; standalone → solo uno mismo.
- `sprint_id`: solo puede pertenecer al mismo proyecto que la tarea.

Borrado: `TaskPolicy::delete` — dueño del proyecto activo (o asignado si standalone). En el contexto del proyecto se invoca por la ruta anidada `projects.tasks.destroy` (a través del `destroyUrl` opcional del modal, research D2/D8).

## Consumidores del nuevo shape

| Consumidor | Impacto |
| ---------- | ------- |
| `Show.vue` (lista) | `TaskListPanel` con `project.tasks: BoardTask[]` (D4) |
| `Show.vue` (backlog/kanban) | `BacklogView`/`KanbanBoard` retipados a `BoardTask[]`; solo usan campos existentes en ambos shapes (D8) |
| `Show.vue` (comentarios) | `commentingTask: BoardTask \| null`, modal en modo global con endpoints `tasks.show` / `tasks.comments.store` (D5) |
| `Show.vue` (cabecera) | contadores `taskDoneCount` / `taskTotalCount` calculados igual desde `project.tasks` |
| `Tasks/Index.vue` | sin cambios de datos (ya consume `BoardTask`) |

## Transiciones de estado

Sin cambios. `TaskStatus`: `backlog → todo → in_progress → in_review → done` (transiciones validadas por el enum y la UI; el cambio de estado desde la fila del proyecto usa `tasks.update` parcial, igual que `/tasks`).
