# Contract: Superficie web de vistas Backlog y Kanban

**Feature**: 005-task-board-views | **Date**: 2026-09-24
**Modelo**: [data-model.md](./data-model.md) · **Decisiones**: [research.md](./research.md)

Contrato de UI del módulo de vistas de tareas para la aplicación Inertia
monolítica. **No se añaden rutas ni endpoints**: ambas vistas viven dentro de
la ficha del proyecto y consumen el payload existente; la única escritura es
el cambio de estado por arrastre, que reutiliza el endpoint de edición de
tareas (research R-1).

## Convenciones del contrato

- **Superficie**: sección Tareas de `Projects/Show` con tres pestañas —
  "Lista" (gestión existente, por omisión), "Backlog" y "Kanban" — en estado
  local (sin persistencia entre sesiones).
- **Autorización**: idéntica al módulo de tareas (FR-009) — miembros ven y
  mueven; no miembros 404 en toda la ficha.
- **Solo lectura fuera de activo**: ordenamiento y filtro del Backlog y la
  visualización del Kanban siguen disponibles; el arrastre está deshabilitado
  (`draggable` en falso) y cualquier escritura forzada es rechazada con
  `errors.project` = "El proyecto no admite cambios en su estado actual."
- **Errores de validación**: redirect de vuelta con `errors` por campo
  (mismo contrato que tareas; el tablero lo consume vía el banner compartido).

## Rutas

Sin rutas nuevas. La escritura del tablero usa:

| Método | Ruta                               | Nombre                  | Uso en esta feature                                                                      |
| ------ | ---------------------------------- | ----------------------- | ---------------------------------------------------------------------------------------- |
| PUT    | `/projects/{project}/tasks/{task}` | `projects.tasks.update` | Cambio de estado al soltar una tarjeta (payload completo de la tarea + `status` destino) |

## Props y comportamiento de las páginas

- **`Projects/Show`** (única superficie; payload sin cambios):
    - `project.tasks` — orden global por recientemente actualizada; cada tarea
      incluye `{ id, title, priority, priorityLabel, status, statusLabel,
assignee: {id, name} | null, sprint: {id, name} | null, ... }`.
    - `project.sprints` — incluye `startDate` (usado por el ordenamiento del
      Backlog).
- **Pestaña "Backlog"** (`Components/Tasks/BacklogView.vue`):
    - Fila: título, badges de prioridad y estado, sprint (o "Sin sprint"),
      responsable (o "Sin responsable").
    - Controles: selector de orden (Prioridad / Sprint) y, en Prioridad,
      conmutador Asc/Desc (por omisión Desc); filtro de sprint (Todas / Sin
      sprint / cada sprint del proyecto).
- **Pestaña "Kanban"** (`Components/Tasks/KanbanBoard.vue`):
    - Cinco columnas (`backlog`, `todo`, `in_progress`, `in_review`, `done`) con
      `label()` del enum, contador y estado vacío.
    - Tarjeta: título, prioridad, responsable, sprint; `draggable` solo si
      `project.status === 'active'`.
    - Al soltar en otra columna: `PUT projects.tasks.update` con el payload
      completo y el `status` destino; misma columna = sin petición.

## Restricciones de contrato

- El cliente no introduce estados nuevos ni columnas fuera de `TaskStatus`.
- No hay persistencia de pestaña activa, orden ni filtro entre sesiones.
- El arrastre no modifica ningún otro campo de la tarea ni su ordenación
  interna (sin posiciones).
