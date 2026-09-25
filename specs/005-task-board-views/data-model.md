# Data Model: Vistas Backlog y Kanban de Tareas

**Feature**: 005-task-board-views | **Date**: 2026-09-24
**Base**: `tasks`, `sprints` (features 003–004). **Sin cambios de esquema.**

## Sin entidades nuevas

Las vistas son presentaciones derivadas del **Task** existente; no se crean
tablas, columnas ni enums. El tablero escribe únicamente el `status` de la
tarea a través de la operación de edición ya existente (research R-1).

## Modelos de lectura derivados (vista, no persistidos)

**Fila de Backlog** — proyección de Task: `{ id, title, priority,
priorityLabel, status, statusLabel, assignee: {id, name} | null, sprint: {id,
name} | null }`, ordenada por rank de prioridad (`urgent=3 > high=2 > medium=1

> low=0`, desc por omisión, asc conmutable) o por sprint (según `startDate`del sprint referenciado en el payload`project.sprints`, aisladas al final);
filtro por sprint (`all`|`none` | id).

**Columna de Kanban** — una por case de `TaskStatus`
(`backlog`/`todo`/`in_progress`/`in_review`/`done`), encabezado con
`label()` del enum y contador; tarjetas = Task con el mismo subconjunto de
campos que la fila de backlog, disposición interna por recientemente
actualizada (orden ya presente en el payload).

## Reglas de escritura (heredadas, sin cambios)

- El movimiento de tarjeta es un cambio de `status` vía
  `projects.tasks.update` con el payload completo de la tarea (misma
  validación de enum, mismos guardes de membresía/pertenencia y
  `ensureProjectIsActive`).
- Misma columna: sin petición. Destino inválido: rechazo de
  `UpdateTaskRequest` (enum) sin alterar la tarea. Proyecto no activo:
  redirect con `errors.project` = "El proyecto no admite cambios en su estado
  actual."; la UI además deshabilita `draggable`.

## Contrato de payload consumido (seam de regresión)

`Projects/Show` ya entrega todo lo que las vistas necesitan; el feature no
modifica el payload. `TaskBoardTest` fija: `project.tasks[*]` incluye
`status`, `priority`, `sprint`, `assignee`; `project.sprints[*]` incluye
`startDate`.
