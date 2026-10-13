# Contract: Sección Sprints (pestaña "Sprints y tareas")

**Feature**: `specs/017-sprints-section`
**Date**: 2026-10-06
**Type**: UI contract (componente Vue sobre el payload existente de `projects.show`)

## Semántica

La pestaña "Sprints y tareas" de `Projects/Show` renderiza `<ProjectSprintsSection>` en lugar de la lista plana actual. El componente consume **únicamente** props derivadas del payload existente; no hay endpoints ni payloads nuevos.

## Props (entrada)

```ts
{
    project: ProjectDetail;      // existente: sprints[], activeSprint, isOwner, status, tasks viven aquí o aparte
    tasks: BoardTask[];          // project.tasks (con task.sprint?.id por tarea)
    canWriteSprints: boolean;    // isOwner && project.status === 'active' (regla actual de Show.vue)
}
```

## Eventos (salida → Show.vue mantiene la propiedad del modal/rutas)

| Evento | Payload | Manejador existente en Show.vue |
| --- | --- | --- |
| `create` | — | `openSprintCreate()` → `SprintFormModal` (modo crear) |
| `edit` | `SprintSummary` | `openSprintEdit(sprint)` → modal (modo editar) |
| `destroy` | `SprintSummary` | `confirmSprintDestroy(sprint)` → `window.confirm` + `destroy` route |
| `complete` | `ActiveSprint` | `window.confirm` + `complete` route + flash existente |

Navegación directa del componente (sin evento): `show` route de Wayfinder para "Ver tablero"/"Ver resumen" y el enlace del nombre del sprint.

## Estructura renderizada

1. **Encabezado**: "Sprints" + "N en total"; chips `Todos | Activo (a) | Planificados (p) | Completados (c)` (conteos reales); botón "Planificar un sprint" (emite `create`).
2. **Tarjeta sprint activo** (solo si `activeSprint` existe): badge "Sprint Activo"; nombre; fechas `startDate — endDate` (formato es-MX corto); "N días restantes" (días laborables, 0 si venció); meta (si existe) etiquetada "Meta del Sprint"; "Progreso de tareas" con % real + "X de Y tareas"; elemento deshabilitado "Puntos de historia" con `UiBadge "Próximamente"` (aria-disabled, sin acción); acciones: "Ver tablero" (todos) y "Completar sprint" (solo `canWriteSprints`).
3. **Otros Sprints**: encabezado con control "Colapsar todos"/"Expandir"; filas (filtradas por chip, excluye el activo de la tarjeta): nombre (enlace al show), `UiBadge` de estado, fechas, "· N tareas planificadas" (planificado) / "· N de N tareas concluidas" (completado), % completado (todos), meta resumida truncada (si existe); acciones: planificado → Editar/Eliminar (`canWriteSprints`), completado → "Ver resumen" (todos).
4. **Estados vacíos**: sin sprints → estado vacío con "Planificar un sprint"; sin activo → sin tarjeta (chips reflejan 0); filtro sin resultados → mensaje honesto del estado.

## Reglas de datos

- Métricas derivadas con helpers puros: `sprintMetrics(sprint, tasks)` → `{total, done, coverage}`; `weekdayDaysRemaining(today, endDate)` (espejo laborable del cálculo del detalle de sprint).
- Todo valor proviene de `project.sprints` / `project.activeSprint` / `tasks` — nada simulado; única excepción honesta: "Puntos de historia" como "Próximamente".
- `coverage` con total=0 → 0% (sin división por cero).

## Invariantes verificables (tests/manuales)

1. Los tests de payload existentes (`ProjectShowPayloadTest`, `SprintCrudTest`, `SprintAccessTest`, `TaskBoardTest`) siguen verdes sin modificación (backend intacto).
2. Con datos seed (activo 14/20 done, planificado 0/12, completado 19/19): tarjeta muestra "70%", "14 de 20 tareas"; filas "0% completado" y "100% … 19 de 19 tareas concluidas".
3. Chips: `Todos = a+p+c`, conteos por estado exactos.
4. Completado sin tareas → "0 de 0 tareas", 0%, sin errores.
5. No propietario → sin botones de mutación; propietario con proyecto archivado → `canWriteSprints=false` igualmente.
