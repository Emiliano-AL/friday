# Contract: Superficie web de tareas

**Feature**: 004-task-management | **Date**: 2026-09-23
**Modelo**: [data-model.md](./data-model.md) · **Decisiones**: [research.md](./research.md)

Contrato de UI/rutas del módulo de tareas para la aplicación Inertia
monolítica. Sesión por cookies; sin API desacoplada (Principio I). La gestión
ocurre dentro de la ficha del proyecto (spec, Assumptions); no existen páginas
de detalle ni listado propios de tareas. Todas las rutas exigen sesión
autenticada (grupo `auth`).

## Convenciones del contrato

- **Autorización**: `TaskPolicy` — `viewAny`/`view` = miembro del proyecto;
  `create`/`update` = miembro y proyecto activo; `delete` = propietario y
  proyecto activo (FR-010/FR-011).
- **No miembro** (o proyecto inexistente): **404** en toda la superficie, para
  no revelar existencia.
- **Escritura con proyecto archivado/completado**: redirect de vuelta con
  `errors.project` = "El proyecto no admite cambios en su estado actual."
  (mismo contrato que sprints; research R-3).
- **Pertenencia cruzada**: una tarea o comentario de otro proyecto se trata
  como inexistente → 404.
- **Eliminación por colaborador**: 404 (solo el propietario elimina).
- **Éxito**: redirect 302 a la ficha del proyecto (`projects.show`).
- **Validación**: redirect de vuelta con `errors` por campo (título,
  responsable, sprint, tipo, prioridad, estado, cuerpo del comentario) con
  mensajes en español.
- **Frontend**: imports de Wayfinder (`@/routes/projects/tasks`).

## Rutas (middleware `auth`)

| Método | Ruta                                        | Nombre                          | Resultado éxito                                   | Resultado error                                                                                       |
| ------ | ------------------------------------------- | ------------------------------- | ------------------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| POST   | `/projects/{project}/tasks`                 | `projects.tasks.store`          | Tarea creada (estado `backlog`); redirect a ficha | 404 si no miembro; `errors` de validación; `errors.project` si no está activo                         |
| PUT    | `/projects/{project}/tasks/{task}`          | `projects.tasks.update`         | Cambios persistidos; redirect a ficha             | 404 si no miembro o tarea ajena; mismas reglas de validación y de estado                              |
| DELETE | `/projects/{project}/tasks/{task}`          | `projects.tasks.destroy`        | Tarea y comentarios eliminados; redirect a ficha  | 404 si no propietario o tarea ajena; `errors.project` si no está activo                               |
| POST   | `/projects/{project}/tasks/{task}/comments` | `projects.tasks.comments.store` | Comentario añadido; redirect a ficha              | 404 si no miembro o tarea ajena; `errors` si el cuerpo está vacío; `errors.project` si no está activo |

## Props y comportamiento de las páginas

- **`Projects/Show`** (única superficie):
    - `project.tasks` — array ordenado por recientemente actualizadas
      (FR-005), cada elemento:
      `{ id, title, description, type, typeLabel, priority, priorityLabel,
status, statusLabel, assignee: { id, name } | null, sprint: { id, name }
| null, comments: [{ id, body, author: { id, name }, createdAt }] }` con
      comentarios en orden cronológico ascendente.
    - Acciones de escritura (crear, editar, comentar) solo si
      `project.status === 'active'` (ser miembro es condición previa de ver la
      página); la eliminación además solo si `project.isOwner`.
    - Alta inline en la sección; edición en `Modal` (formulario completo);
      comentarios en `Modal` con hilo cronológico y formulario propio; baja con
      `window.confirm`.
- Sin páginas `Tasks/*` independientes.

## Restricciones de contrato

- No existe ruta de lectura de comentarios: viajan siempre dentro de
  `project.tasks` (research R-5).
- No existe ruta de cambio de estado separada: el estado se edita con la
  tarea (movimiento libre dentro de los cinco valores).
- El estado inicial siempre es `backlog`; el cliente no puede fijarlo en el
  alta.
- El responsable debe ser miembro del proyecto y el sprint debe pertenecer al
  mismo proyecto; el servidor lo valida siempre (FR-002/FR-003).
- Una tarea fuera de sprint (`sprint: null`) es un estado válido y permanente
  (pendiente fuera de sprint / backlog general).
