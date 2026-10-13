# Data Model: Sección Sprints del detalle de proyecto

**Feature**: `specs/017-sprints-section`
**Date**: 2026-10-06

**Sin cambios de esquema ni de payloads** (research.md D2). La sección deriva toda su información de datos que ya viajan en la página del proyecto.

## Entidades existentes (relevantes)

### Proyecto
- Propietario + miembros; la sección vive en su página de detalle, pestaña "Sprints y tareas".
- `status` (`ProjectStatus`): solo proyectos activos admiten mutaciones de sprint.

### Sprint
- Campos existentes usados por la sección: `id`, `name`, `goal` (nullable), `start_date`, `end_date` (dates), `status` (`SprintStatus`: `planned | active | completed`), relación `tasks`.
- Reglas del dominio (conservadas, sin cambios):
  - Planificado → Activo → Completado (forward-only, `start()`/`complete()`).
  - Completados son de solo lectura (`ensureSprintIsWritable`).
  - Mutaciones: solo propietario del proyecto y proyecto activo.
  - `activeSprint` del proyecto = activo con mayor `start_date` (si hay varios, ese es el destacado; los demás aparecen en la lista).

### Tarea
- Alimenta las derivaciones: `status` (`TaskStatus`, interesa `Done`), `sprint_id` → agrupación por sprint.

## Derivaciones client-side (nuevas, puras, sin persistencia)

| Derivación | Definición | Uso |
| --- | --- | --- |
| `sprintMetrics(sprint, tasks)` | `{ total: tareas del sprint, done: tareas con status='done', coverage: done/total % (0 si total=0) }` | tarjeta activa y filas |
| `weekdayDaysRemaining(today, endDate)` | días laborables entre mañana y `endDate` (excluye sábados/domingos; 0 si `endDate` < mañana) — espejo de `ProjectSprintController::sprintDetail()` | tarjeta activa ("N días restantes") |
| conteos de chips | nº de sprints por `status` sobre `project.sprints` | encabezado (Todos/Activo/Planificados/Completados) |
| "Otros Sprints" | `project.sprints` filtrados por chip y excluyendo el `activeSprint` mostrado en tarjeta | lista |

## Reglas de presentación (del spec)

- Meta del sprint: se muestra cuando `goal !== null`; se omite el bloque si no existe.
- Puntos de historia: **no existe en el dominio** → elemento deshabilitado "Próximamente" en tarjeta activa, omitido en filas (FR-006).
- Porcentajes/conteos: 100% derivados de tareas reales (FR-007).
- Permisos: mutaciones visibles solo con `canWriteSprints` (propietario + proyecto activo); "Ver tablero"/"Ver resumen" para cualquier miembro.

## Invariantes verificables

1. Los campos existentes del payload de sprints (`sprints[].{id,name,startDate,endDate,status,statusLabel,goal}`, `activeSprint.*`) no cambian ni reordenan.
2. Todo valor numérico mostrado (conteos, %, días) es calculado sobre `project.sprints`/`project.tasks` existentes.
3. Un sprint completado nunca muestra acciones de mutación; un planificado nunca muestra "Completar".
