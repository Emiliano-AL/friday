# Data Model: Unificar vistas de tareas del proyecto con las vistas globales

**Fecha**: 2026-10-09 | **Feature**: [spec.md](spec.md) | **Research**: [research.md](research.md)

## Cambios de esquema

**Ninguno.** Las columnas necesarias (`code`, `points`, `author_id`,
`completed_at`) existen desde la feature 018; `created_at` es nativo de
timestamps. Esta feature solo extiende el **mapeo de payload** del controlador.

## Payload extendido: tarea en `Projects/Show` (`ProjectController@projectPayload`)

Campos existentes (sin cambio): `id`, `title`, `description`, `type`,
`typeLabel`, `priority`, `priorityLabel`, `status`, `statusLabel`,
`assignee {id,name,avatar}|null`, `commentsCount`, `canUpdate`, `canDelete`.

### Campos nuevos (aditivos)

| Campo | Tipo | Origen | Consumidor |
| --- | --- | --- | --- |
| `code` | `string \| null` | `tasks.code` (FRN-###) | `BacklogTaskRow` (chip identificador) |
| `points` | `number \| null` | `tasks.points` | fila (chip "N pts") y tarjeta kanban |
| `createdAt` | `string` (ISO 8601) | `tasks.created_at` | orden por fecha en secciones / sort "Recientes" |
| `completedAt` | `string \| null` (ISO 8601) | `tasks.completed_at` | contrato `BacklogTask` / tratamiento visual de completadas |
| `authorId` | `number \| null` | `tasks.author_id` | contrato `BacklogTask` |
| `sprint.status` | `'planned' \| 'active' \| 'completed'` | `sprints.status` | contrato `BacklogTask.sprint` |

### Tipos TypeScript

- `resources/js/types/project.ts` — `ProjectTask` gana los campos anteriores
  (mismos tipos que `BacklogTask`). Tras el cambio, `ProjectTask` es
  estructuralmente asignable a `BacklogTask` (los campos extra `typeLabel`,
  `priorityLabel`, `statusLabel`, `commentsCount` no impiden asignación por
  variable).
- `resources/js/types/backlog.ts` — sin cambios.
- `resources/js/types/task.ts` (`BoardTask`) — sin cambios; ya declara
  `code?`, `points?`, `authorId?` opcionales, así que `TaskBoardCard` compila
  igual antes y después.

## Reglas de dominio reutilizadas (sin cambio)

| Regla | Fuente |
| --- | --- |
| Conjunto de la vista Backlog del proyecto: `sprint === null && status !== 'done'` (antes del filtro de sprint de la sección) | spec FR-001 + edge case; paridad con `scopeBacklog` |
| Conjunto del kanban: columnas `todo/in_progress/in_review/done`; tareas `status = 'backlog'` no se muestran (paridad con `/tasks`) | D6 |
| Cambio de estado: `PUT tasks.update` con `{status}`; autorización vía `TaskPolicy::update` (miembro + proyecto activo) | D4 |
| `canUpdate = (owner \|\| member) && project active`; `canDelete = owner && active` | reglas actuales del payload, se conservan |
| Sprint filter de sección: `sin sprint` (default) \| sprint concreto \| `todos` | D5 |

## Invariantes verificables

1. Todo `ProjectTask` del payload incluye los 6 campos nuevos (test de payload).
2. La vista Backlog del proyecto con default "Sin sprint" lista exactamente las
   tareas del proyecto con `sprint === null && status !== 'done'`, agrupadas en
   las tres secciones por prioridad.
3. Mover una tarjeta en el kanban del proyecto produce un único `PUT
   /tasks/{id}` con body `{status}` y la tarjeta persiste en la nueva columna
   tras recargar.
4. Ningún test existente cambia: `TaskBoardTest` (ruta de proyecto con body
   completo) y `ProjectShowPayloadTest` (aserciones a nivel campo) siguen
   pasando sin modificaciones.
