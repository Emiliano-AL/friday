# Research: Sección Sprints del detalle de proyecto

**Feature**: `specs/017-sprints-section`
**Date**: 2026-10-06
**Input**: spec.md + exploración del estado actual de la pestaña Sprints en `/projects/{id}`

## Contexto técnico confirmado

- Stack: PHP 8.4 (Laravel 13), Inertia v3, Vue 3.5 + TS, Tailwind v4 (tokens, solo claro), Wayfinder, Pest, PostgreSQL. Sin dependencias nuevas.
- **Corrección de alcance**: las tarjetas "Sprint Actual / Próximo Sprint / Backlog" viven en la pestaña **General** (`Show.vue:503-708`) y **quedan fuera** del alcance. La pestaña **"Sprints y tareas"** (`Show.vue:711-884`) contiene hoy: una lista plana `<ul>` de `project.sprints` (enlace al show, badge `statusLabel`, fechas, Editar/Eliminar) + botón "Planificar un sprint" (abre `SprintFormModal` en modo crear). **El rediseño reemplaza el contenido de esta pestaña.**
- **Payload actual** (`ProjectController::projectPayload`, :206-214, 243-251, 266-274):
  - `activeSprint`: `{id, name, startDate, endDate, status, statusLabel, goal}` (HasOne: activo más reciente por `start_date` desc, `Project.php:113-118`).
  - `sprints[]`: mismo shape, ordenadas por `start_date` asc. **Sin conteos ni días restantes.**
  - Tests que acotan el contrato (usan `has`/`where` sobre campos existentes; añadir campos es seguro, cambiar orden/fields existentes no): `ProjectShowPayloadTest` (activeSprint.id/name), `SprintCrudTest` (:30-33, :48-51 orden y nombres), `SprintAccessTest` (:18), `TaskBoardTest` (:33-34).
- **`project.tasks`** ya está en el payload completo (con `sprint{id,name}` por tarea) — `Show.vue` ya deriva client-side `activeSprintProgress` (:210-218) y `nextSprintTasks` (:220-237) filtrando `project.tasks`. **Precedente directo para derivar los conteos de la sección sin tocar backend.**
- **Permisos**: mutaciones de sprint = solo propietario y proyecto activo (`SprintPolicy:29-48` create/update/delete; `ProjectSprintController` aborts owner-only en start/complete). Completados son de solo lectura (`ensureSprintIsWritable`). `Show.vue:43-47` ya deriva `canWriteSprints = isOwner && project.status === 'active'`. El show del sprint es `isMember`.
- **Wayfinder** (`@/routes/projects/sprints`): `store`, `show`, `update`, `destroy`, `start`, `complete` — todos con firma `[project, sprint]`.
- **Lógica espejo disponible**: `ProjectSprintController::sprintDetail()` (:165-209) `daysRemaining` (días laborables restantes, loop excluyendo fines de semana), `percentElapsed`; `metrics()` (:217-240) `coverage` = done/total. Nombres ya usados por `SprintDetailTest` — se replican en el cliente con los mismos nombres conceptuales.
- **Tipos**: `SprintSummary`/`ActiveSprint` (`types/project.ts:4-22`) = 7 campos; `SprintDetail`/`SprintMetrics` (`types/sprint.ts`) con iteration/daysRemaining/percentElapsed/coverage — solo existen para la página de detalle.
- UiBadge: props `label`, `tone: primary|neutral|error|tertiary|outline`, `dot` (`UiBadge.vue:5-13`). Precedente "Próximamente" en `SprintFormModal.vue:193-220`.

## Decisiones

### D1 — Reemplazar el contenido de la pestaña "Sprints y tareas" (no la pestaña General)
- **Decisión**: El rediseño actúa únicamente sobre la sección Sprints de la pestaña "Sprints y tareas" (`Show.vue:711-884`). Las tarjetas de la pestaña General ("Sprint Actual", "Próximo Sprint", Backlog) **no se tocan** (viven en otra pestaña y el spec las excluye: "la sección Sprints dentro de la pestaña 'Sprints y tareas'").
- **Rationale**: alcance exacto del spec; evita duplicar la tarjeta de activo en dos pestañas.

### D2 — Cero cambios de backend: métricas derivadas client-side desde el payload existente
- **Decisión**: Los conteos ("X de Y tareas"), porcentajes y días restantes se calculan en el cliente: helper puro `sprintMetrics(sprint, tasks)` = `{total, done, coverage}` filtrando `project.tasks` por `task.sprint?.id`; helper `weekdayDaysRemaining(today, endDate)` replicando el loop laborable de `sprintDetail()`. El backend (`projectPayload`) **no se modifica**.
- **Rationale**: `project.tasks` ya llega completo; Show.vue ya usa este patrón (`activeSprintProgress`). Cero riesgo para los tests de payload congelados, cero queries extra, Principio V.
- **Alternativas**: `withCount` por sprint en el payload (rechazada: toca ProjectController y duplica datos ya presentes; sin beneficio medible).

### D3 — Estructura de la sección: `ProjectSprintsSection.vue` con eventos hacia Show.vue
- **Decisión**: Nuevo componente `resources/js/Components/Sprints/ProjectSprintsSection.vue` con props `{project, tasks, canWriteSprints}`; emite `create`, `edit(sprint)`, `destroy(sprint)`, `complete(sprint)`; Show.vue conserva la propiedad de `SprintFormModal` y las funciones existentes (`openSprintCreate/openSprintEdit/confirmSprintDestroy`) y solo cambia el contenido de la pestaña por `<ProjectSprintsSection … />`. El componente internamente: encabezado (total + chips + "Planificar un sprint"), `ActiveSprintCard`, lista `SprintRow[]` — todo en un solo archivo (subcomponentes inline por simplicidad YAGNI).
- **Rationale**: Show.vue ya supera las 950 líneas; extraer es la convención del proyecto (`Components/<Dominio>/`). Un solo archivo nuevo mantiene el diff acotado.

### D4 — Chips de filtro client-side sobre "Otros Sprints"
- **Decisión**: Chips `Todos | Activo (N) | Planificados (N) | Completados (N)` con conteos calculados sobre `project.sprints`; el filtro aplica a la lista "Otros Sprints" (`sprints` menos el `activeSprint` mostrado en tarjeta). Estado en `ref` local del componente (sin persistencia; el spec no la pide).
- **Rationale**: FR-004; datos ya en el cliente; sin estado de URL (YAGNI).

### D5 — Puntos de historia: omitidos en filas, "Próximamente" en la tarjeta activa
- **Decisión**: El diseño muestra "32 / 45 Puntos" (tarjeta activa) y "· 28 puntos de historia" (filas). Como no existe en el sistema: en la tarjeta activa se muestra un elemento deshabilitado "Puntos de historia" con lock + `UiBadge "Próximamente"` (patrón `SprintFormModal.vue:193-220`); en las filas se omite por completo (las filas sí muestran tareas y %, que son reales). FR-006.
- **Alternativas**: implementar puntos (rechazada: fuera de alcance, columna nueva); mostrar ceros (rechazada: dato simulado, viola FR-007).

### D6 — Acciones contextuales reutilizando el flujo existente
- **Decisión**: Tarjeta activa: "Ver tablero" (→ `show` route, todos los miembros) + "Completar sprint" (solo `canWriteSprints`, `window.confirm` con el texto existente del proyecto + `complete` route, precedente pestaña General). Filas: Planificado → "Editar" (emite `edit`) + "Eliminar" (`window.confirm` + `destroy`, reutilizando `confirmSprintDestroy` existente); Completado → "Ver resumen" (→ `show`). Sin acciones de mutación para no propietarios (render condicional por `canWriteSprints`).
- **Rationale**: FR-005 reutiliza endpoints y permisos actuales; nada nuevo server-side.

### D7 — Colapsar/expandir "Otros Sprints" (estado de sesión)
- **Decisión**: Control "Colapsar todos"/"Expandir" con `ref<boolean>` en el componente; colapsa la lista de filas. Solo estado de sesión (el spec solo exige "durante la sesión").
- **Rationale**: FR-008 mínima.

### D8 — "Planificar un sprint"
- **Decisión**: El botón del encabezado emite `create`; Show.vue abre `SprintFormModal` en modo crear (flujo existente de la pestaña actual, `Show.vue:792-800`).

### D9 — Verificación
- **Decisión**: Sin tests PHP nuevos (cero lógica de dominio nueva: solo derivaciones client-side de datos existentes). Cobertura: tests de payload existentes (deben seguir verdes), `npm run check` (vue-tsc) y escenarios manuales de quickstart.md. Los helpers de derivación se escriben como funciones puras tipadas (verificables por tipado).
- **Rationale**: Principio III aplica a lógica de dominio; aquí no hay dominio nuevo. El proyecto no tiene harness de tests frontend (convención establecida en features anteriores).

## NEEDS CLARIFICATION
Ninguno pendiente.
