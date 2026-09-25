# Feature Specification: Vistas Backlog y Kanban de Tareas

**Feature Branch**: `005-task-board-views`

**Created**: 2026-09-24

**Status**: Draft

**Input**: User description: "Vista Backlog: Lista estructurada y ordenable por prioridad/sprint, optimizada para refinamiento rápido y triage de pendientes. Vista Kanban: Tablero visual interactivo dividido por estados (todo, in_progress, etc.) con soporte para drag-and-drop."

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Vista Backlog ordenable para refinamiento (Priority: P1)

Un miembro del proyecto abre la sección de tareas del proyecto en la vista
Backlog: una lista estructurada donde cada tarea muestra su título, prioridad,
sprint, estado y responsable. El miembro puede ordenar la lista por prioridad
(de mayor a menor o de menor a mayor) y por sprint, y filtrar por sprint
(todas, sin sprint o un sprint concreto) para hacer triage de los pendientes.
El orden por omisión es de mayor a menor prioridad.

**Why this priority**: El backlog es la herramienta principal de refinamiento
y planificación; sin ordenamiento y filtro, la lista de tareas es difícil de
pivotar para decidir qué entra al siguiente sprint.

**Independent Test**: Abrir la vista Backlog de un proyecto con tareas de
prioridades y sprints variados, ordenar por prioridad descendente y comprobar
que las urgentes aparecen primero; filtrar por un sprint y comprobar que solo
se ven sus tareas.

**Acceptance Scenarios**:

1. **Given** un proyecto con tareas de distinta prioridad y sprint, **When** un miembro abre la vista Backlog, **Then** ve la lista estructurada con título, prioridad, sprint, estado y responsable de cada tarea, ordenada por prioridad descendente.
2. **Given** la vista Backlog abierta, **When** el miembro elige ordenar por prioridad ascendente, **Then** las tareas se reordenan de menor a mayor prioridad sin recargar la página.
3. **Given** la vista Backlog abierta, **When** el miembro elige ordenar por sprint, **Then** las tareas se agrupan en orden de sprint (las aisladas quedan identificadas como sin sprint).
4. **Given** la vista Backlog abierta, **When** el miembro filtra por un sprint concreto o por "sin sprint", **Then** solo se muestran las tareas de ese filtro, y al volver a "todas" reaparecen todas.

---

### User Story 2 - Vista Kanban con columnas por estado (Priority: P2)

Un miembro del proyecto abre la vista Kanban: un tablero dividido en cinco
columnas (backlog, por hacer, en progreso, en revisión y hecho) donde cada
tarea aparece como tarjeta en la columna de su estado, con el contador de
tarjetas por columna visible. Las tarjetas muestran de forma compacta el
título, la prioridad, el responsable y el sprint.

**Why this priority**: El tablero da visibilidad instantánea del flujo de
trabajo; es la segunda cara de la gestión de tareas y la base sobre la que
operará el arrastre de tarjetas.

**Independent Test**: Abrir la vista Kanban de un proyecto con tareas en
varios estados y comprobar que cada tarjeta aparece en la columna correcta con
el contador adecuado, y que una columna sin tareas muestra su estado vacío.

**Acceptance Scenarios**:

1. **Given** un proyecto con tareas en distintos estados, **When** un miembro abre la vista Kanban, **Then** cada tarea aparece como tarjeta en la columna correspondiente a su estado.
2. **Given** la vista Kanban abierta, **When** el miembro revisa el tablero, **Then** cada columna muestra el número de tareas que contiene.
3. **Given** una columna sin tareas, **When** un miembro mira el tablero, **Then** la columna muestra claramente que está vacía.

---

### User Story 3 - Mover tarjetas con drag-and-drop (Priority: P3)

Un miembro arrastra una tarjeta de una columna a otra y la tarea cambia a ese
estado: la tarjeta queda visible en la nueva columna, el contador se actualiza
y el cambio se refleja en las demás vistas y en la lista de tareas. Si la
tarjeta se suelta fuera de una zona válida, permanece donde estaba.

**Why this priority**: Es la interacción central del tablero; sin ella el
Kanban es solo una visualización. Depende de US2 pero entrega valor por sí
sola.

**Independent Test**: Arrastrar una tarjeta de "por hacer" a "hecho" y
comprobar que la tarea queda con estado hecho, visible en la nueva columna y
con los contadores actualizados.

**Acceptance Scenarios**:

1. **Given** una tarjeta en la columna "por hacer", **When** un miembro la arrastra y suelta en "en progreso", **Then** la tarea pasa a estado "en progreso" y la tarjeta aparece en esa columna.
2. **Given** un movimiento realizado, **When** el miembro revisa la vista Backlog o la lista, **Then** la tarea muestra su nuevo estado.
3. **Given** una tarjeta suelta fuera de cualquier columna, **When** termina la interacción, **Then** la tarjeta permanece en su columna original y su estado no cambia.
4. **Given** una petición de cambio de estado manipulada con un estado inválido, **When** llega al servidor, **Then** el sistema rechaza la operación y la tarea conserva su estado.

---

### User Story 4 - Solo lectura fuera del estado activo (Priority: P4)

Cuando el proyecto está archivado o completado, las vistas Backlog y Kanban
siguen siendo visibles para los miembros (incluido el ordenamiento y el
filtro), pero el arrastre de tarjetas está deshabilitado y cualquier intento de
cambio de estado es rechazado en el servidor con el mensaje estándar de
proyecto no activo; la interfaz no ofrece las acciones de escritura.

**Why this priority**: Protege la integridad histórica del proyecto terminado;
es regla transversal heredada del módulo de tareas.

**Independent Test**: Con un proyecto archivado, comprobar que ambas vistas se
ven y ordenan, que el drag-and-drop no arranca y que una petición de cambio de
estado forzada es rechazada con mensaje.

**Acceptance Scenarios**:

1. **Given** un proyecto archivado o completado, **When** un miembro abre las vistas Backlog o Kanban, **Then** ve las tareas y puede ordenar y filtrar, sin opciones de mover tarjetas.
2. **Given** un proyecto archivado o completado, **When** un miembro intenta cambiar el estado de una tarea manipulando la petición, **Then** el sistema rechaza la operación con un mensaje que indica que el proyecto no admite cambios en su estado actual.

---

### Edge Cases

- ¿Qué pasa si una columna del tablero no tiene tareas? La columna muestra un indicador claro de vacío.
- ¿Qué pasa si se suelta una tarjeta fuera de una zona de soltado válida? La tarjeta vuelve a su columna original y el estado no cambia.
- ¿Qué pasa si se arrastra a la misma columna de origen? No ocurre ningún cambio ni petición.
- ¿Qué pasa si el proyecto está archivado o completado? Las vistas se ven en solo lectura (ordenamiento y filtro disponibles); el arrastre está deshabilitado y las escrituras se bloquean en el servidor con mensaje.
- ¿Qué pasa si un usuario que no es miembro intenta acceder? No encuentra nada (404 indistinguible, igual que el resto del proyecto).
- ¿Qué pasa si el destino del arrastre es un estado inválido (petición manipulada)? El servidor rechaza la operación y la tarea conserva su estado.
- ¿Qué pasa con una tarea sin responsable o aislada en el tablero y el backlog? Se muestran con sus indicadores "sin responsable" / "sin sprint".
- ¿Qué pasa con un proyecto con muchas tareas (100)? El tablero y el backlog cargan y permanecen usables.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: El sistema debe ofrecer, dentro de la sección de tareas del proyecto, dos vistas para todo miembro: Backlog (lista estructurada) y Kanban (tablero por estados), junto a la lista de gestión existente.
- **FR-002**: La vista Backlog debe listar cada tarea con título, prioridad, sprint, estado y responsable, y permitir ordenar por prioridad (ascendente o descendente) y por sprint; el orden por omisión es prioridad descendente.
- **FR-003**: La vista Backlog debe permitir filtrar las tareas por sprint: todas, sin sprint o un sprint concreto del proyecto.
- **FR-004**: La vista Kanban debe mostrar una columna por cada estado del flujo (backlog, por hacer, en progreso, en revisión, hecho), con las tarjetas ubicadas según el estado de su tarea, el número de tarjetas por columna visible y un indicador claro en columnas vacías.
- **FR-005**: Las tarjetas del tablero deben mostrar de forma compacta el título, la prioridad, el responsable y el sprint de la tarea.
- **FR-006**: El sistema debe permitir a cualquier miembro mover una tarjeta de una columna a otra mediante arrastrar y soltar; al soltarla, la tarea cambia al estado de la columna destino y el cambio se refleja en el tablero, el backlog y la lista de tareas. Mover a la misma columna no genera cambio ni petición.
- **FR-007**: El sistema debe validar en servidor cualquier cambio de estado originado por el tablero con las mismas reglas del flujo de estados (solo los cinco valores permitidos) y rechazar destinos inválidos sin alterar la tarea.
- **FR-008**: Cuando el proyecto no esté activo (archivado o completado), el sistema debe impedir los cambios de estado desde el tablero, rechazándolos en el servidor con un mensaje que indique que el proyecto no admite cambios en su estado actual, y la interfaz debe deshabilitar el arrastre; la visualización, el ordenamiento y el filtro siguen disponibles.
- **FR-009**: Las vistas y el movimiento de tarjetas respetan los permisos del módulo de tareas: cualquier miembro ve y mueve; quien no es miembro no accede a nada (404 indistinguible).

### Key Entities _(include if feature involves data)_

No hay entidades nuevas: las vistas son presentaciones del **Task** existente
(feature 004). Referencias utilizadas:

- **Tarea** (existente): fuente única de verdad; las vistas la leen (título,
  prioridad, estado, sprint, responsable) y el tablero actualiza solo su
  estado, con la misma validación y reglas de solo lectura ya definidas.
- **Sprint** (existente): usado por el ordenamiento y el filtro del Backlog.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Un miembro puede cambiar el estado de una tarea arrastrando su tarjeta en menos de 3 segundos.
- **SC-002**: El tablero y el backlog de un proyecto con hasta 100 tareas cargan en menos de 2 segundos.
- **SC-003**: El 100% de los intentos de cambio de estado desde el tablero en un proyecto archivado o completado son bloqueados y muestran un mensaje comprensible.
- **SC-004**: Al menos el 90% de los miembros mueve una tarjeta entre columnas sin necesitar ayuda.

## Assumptions

- Las vistas viven dentro de la sección Tareas de la ficha del proyecto, como pestañas junto a la lista de gestión existente: "Lista" (gestión actual), "Backlog" y "Kanban". La pestaña activa no persiste entre sesiones en esta iteración.
- El tablero Kanban muestra las cinco columnas del flujo, incluida "backlog".
- El arrastrar y soltar no implica ordenación manual dentro de una columna: no hay posiciones propias y la disposición interna de cada columna es por recientemente actualizada.
- El ordenamiento y el filtro del Backlog son preferencias de vista (no persistidas entre sesiones en esta iteración).
- El arrastre se implementa con el mecanismo nativo del navegador; no se añaden dependencias nuevas.
- Las reglas de permisos (cualquier miembro ve y gestiona; solo el propietario elimina) y de solo lectura fuera del estado activo se heredan íntegras del módulo de tareas, incluido el mensaje de bloqueo.
- El cambio de estado por arrastre reutiliza la operación de edición de tareas existente (misma validación de estados, mismo contrato de errores).
