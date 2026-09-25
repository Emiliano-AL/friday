# Research: Vistas Backlog y Kanban de Tareas

**Feature**: 005-task-board-views | **Date**: 2026-09-24
**Spec**: [spec.md](./spec.md)

Phase 0. El stack está fijado por la constitución y las features 002–004 fijaron
las convenciones del dominio y del módulo de tareas (código real implementado
en esta base). No quedó ningún `NEEDS CLARIFICATION` en el Technical Context;
las decisiones se consolidan aquí con sus alternativas.

## R-1 — Cero cambios de backend: el tablero reutiliza la edición de tareas

- **Decision**: la feature es íntegramente frontend (+ tests). El cambio de
  estado por arrastre usa el endpoint existente
  `PUT /projects/{project}/tasks/{task}` (`projects.tasks.update`) enviando el
  payload completo de la tarea que el cliente ya posee, con el `status`
  destino. No se añaden rutas, controladores, requests ni columnas.
- **Rationale**: la spec asume la reutilización ("misma validación de estados,
  mismo contrato de errores"); `UpdateTaskRequest` ya valida el enum, el
  controlador ya aplica `ensureProjectIsActive` (FR-008) y los guardes de
  membresía/pertenencia (FR-009). Revalidar responsable/sprint con datos
  intactos es inocuo. YAGNI: un endpoint PATCH de estado solo duplicaría lo
  existente.
- **Alternativas**: endpoint dedicado de transición (p. ej.
  `projects.tasks.move`) — rechazada (ruta muerta semánticamente redundante);
  relajar `UpdateTaskRequest` a `sometimes` — rechazada (cambia el contrato de
  004 sin necesidad, el cliente ya tiene todos los campos).

## R-2 — Drag-and-drop nativo HTML5 (cero dependencias)

- **Decision**: tarjetas con `draggable`, eventos `dragstart`/`dragover`/
  `drop` y `dataTransfer` con el id de la tarea; clases de resaltado en la
  columna destino durante el arrastre. Sin librerías (Principio II/V).
- **Rationale**: la constitución prohíbe añadir dependencias sin aprobación y
  la funcionalidad cubre el alcance MVP (mover entre columnas, sin
  ordenación interna). El soporte nativo es suficiente en navegadores
  modernos.
- **Alternativas**: librerías de DnD (SortableJS, vue-draggable) — rechazadas
  (dependencia nueva, YAGNI); pointer events a mano — rechazado (reinventar
  lo que el nativo resuelve).

## R-3 — Movimiento dirigido por el servidor (sin optimismo local)

- **Decision**: al soltar, se emite el `PUT` y el tablero se re-renderiza con
  las props frescas de Inertia (`preserveScroll`); si la petición falla
  (bloqueo por estado del proyecto, destino inválido), las props no cambian y
  la tarjeta permanece visualmente en su columna mientras el banner
  `errors.project` muestra el mensaje. Suelta fuera de columna = no-op por
  diseño del evento; misma columna = no se emite petición (FR-006).
- **Rationale**: el servidor es fuente de verdad; un movimiento optimista
  local exigiría rollback manual y duplicaría la máquina de estados en el
  cliente. La red local (Herd) hace el viaje de ida y vuelta imperceptible
  (SC-001 < 3 s con holgura).
- **Alternativas**: actualización optimista con revert — rechazada
  (complejidad sin necesidad a esta escala).

## R-4 — Pestañas de vista con estado local (Lista / Backlog / Kanban)

- **Decision**: la sección Tareas de `Projects/Show.vue` gana un conmutador de
  tres pestañas —"Lista" (gestión actual, contenido existente), "Backlog" y
  "Kanban"— con estado local (`activeView`), por omisión "Lista". La pestaña
  activa no persiste (spec, Assumptions).
- **Rationale**: preserva el comportamiento existente (YAGNI: sin persistencia
  ni query params) y añade ambas vistas sin páginas nuevas, coherente con
  "dentro de la vista del proyecto".
- **Alternativas**: rutas por vista — rechazada (persistencia fuera de alcance).

## R-5 — Ordenamiento y filtro del Backlog como cómputo cliente

- **Decision**: `BacklogView.vue` calcula la lista filtrada/ordenada con
  computed a partir de `project.tasks` y `project.sprints` (ya presentes en
  el payload: prioridad, estado, sprint, responsable y `startDate` de cada
  sprint). Filtro: `all` | `none` (sin sprint) | id de sprint. Orden:
  prioridad (rank `urgent=3 > high=2 > medium=1 > low=0`, desc por omisión,
  conmutador asc) o sprint (por `startDate` ascendente, aisladas al final).
- **Rationale**: ningún dato nuevo se requiere; el cómputo es trivial y
  instantáneo para ≤ 100 tareas (SC-002). Mantenerlo cliente evita endpoints
  de consulta adicionales.
- **Alternativas**: parámetros de ordenamiento en el servidor — rechazada
  (round-trip innecesario para ≤ 100 filas).

## R-6 — Kanban: columnas desde el enum, contadores y vacíos

- **Decision**: `KanbanBoard.vue` define las cinco columnas a partir de los
  cases de `TaskStatus` (backlog, todo, in_progress, in_review, done) con
  `label()` para el encabezado; las tarjetas se agrupan por `status` desde
  `project.tasks` (el payload ya viene ordenado por recientemente actualizada,
  así la disposición interna queda por `updated_at` desc sin campo nuevo);
  contador por columna y estado vacío explícito (FR-004).
- **Rationale**: una sola fuente para los estados (Principio IV); sin columna
  de "aisladas" ni estados inventados.

## R-7 — Tarjeta compacta y arrastre condicionado

- **Decision**: `TaskCard.vue` muestra título, badge de prioridad,
  responsable (o "Sin responsable") y sprint (o "Sin sprint"). `draggable` solo
  cuando `canWriteTasks` (proyecto activo); el handler de `drop` además
  ignora el movimiento si no es escribible (doble defensa con el servidor,
  FR-008/FR-009).
- **Rationale**: cumple FR-005 y la regla de solo lectura sin estados
  visuales engañosos (no se ofrece lo que el servidor bloquearía).

## R-8 — Tests: contrato de payload + cadena completa de transiciones

- **Decision**: `tests/Feature/Tasks/TaskBoardTest.php` (Pest) con dos
  grupos: (1) contrato que las vistas consumen — el prop `project.tasks` incluye
  `status`, `priority`, `sprint` (id+name), `assignee` (id+name) y el payload
  incluye `sprints` con `startDate` (seam de regresión para R-5/R-6); (2) la
  cadena completa de movimientos del tablero vía `projects.tasks.update`
  (backlog → todo → in_progress → in_review → done y regreso a backlog), con
  el error `errors.project` al mover con proyecto archivado. La interacción de
  arrastre se valida en navegador (quickstart), igual que en features previas.
- **Rationale**: la lógica nueva es de presentación (sin dominio nuevo que
  cubrir en Pest más allá del contrato reutilizado); la constitución exige
  tests de feature para el dominio, y el dominio aquí es el cambio de estado
  ya probado en 004 — el test nuevo fija el contrato de la vista y la cadena
  extremo a extremo.
- **Alternativas**: unit tests TS del cómputo de ordenamiento — rechazada (el
  proyecto no tiene runner de tests JS; `vue-tsc` + validación en navegador
  cubren).

## R-9 — Simulación de drag-and-drop en la validación de navegador

- **Decision**: la validación manual S3 del quickstart se automatiza en
  navegador construyendo `DragEvent` con `new DataTransfer()` y despachando
  `dragstart` en la tarjeta y `dragover`/`drop` en la columna destino (Chrome
  real soporta ambos constructores), verificando estado persistido y
  contadores.
- **Rationale**: demuestra el flujo FR-006/FR-007 end-to-end sin depender del
  gesto físico; mismo patrón de validación en navegador usado en 003/004.
