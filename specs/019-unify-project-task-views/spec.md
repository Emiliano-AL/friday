# Feature Specification: Unificar vistas de tareas del proyecto con las vistas globales

**Feature Branch**: `019-unify-project-task-views`

**Created**: 2026-10-09

**Status**: Draft

**Input**: User description: "ayudame a modificar la UI: en la ruta /projects/{id} en la seccion de tareas, tenemos 3 vistas disponibles, lista, backlog y kanban, necesito que la vista Backlog, se muestre las task tal como se ven en la ruta /backlog con el mismo estilo me refiero, y en la vista Kanban, tal como se ve en la ruta /task en el modo kanban."

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Vista Backlog del proyecto con el estilo del backlog global (Priority: P1)

Un miembro del proyecto entra a la ficha de un proyecto (`/projects/{id}`), abre la
sección de tareas y selecciona la vista "Backlog". Las tareas se muestran con el
mismo aspecto y organización que en la bandeja global `/backlog`: filas con
identificador legible, título, prioridad, puntos, tipo y responsable; agrupadas en
secciones por prioridad (alta y urgente, media, baja e ideas) ordenadas de más
reciente a más antigua; con el selector de orden y estados vacíos equivalentes.
La vista sigue mostrando solo las tareas del proyecto sin sprint, como hace hoy.

**Why this priority**: Es la mitad principal del pedido: elimina la inconsistencia
visual entre la bandeja global y la vista de proyecto, que hoy se ve con un estilo
antiguo y desalineado con el resto de la aplicación.

**Independent Test**: Entrar a un proyecto con tareas sin sprint de distintas
prioridades y comprobar que la vista Backlog se ve y se comporta igual que
`/backlog` (mismas secciones, mismas filas, mismo estado vacío), limitada a las
tareas de ese proyecto.

**Acceptance Scenarios**:

1. **Given** un proyecto con tareas sin sprint de prioridades mezcladas, **When** un miembro abre la vista Backlog, **Then** las tareas aparecen agrupadas en las mismas secciones por prioridad que en `/backlog`, ordenadas por fecha de captura dentro de cada sección.
2. **Given** una tarea con identificador, puntos y prioridad, **When** se lista en la vista Backlog del proyecto, **Then** la fila muestra los mismos elementos visuales que mostraría en `/backlog` (identificador, título, prioridad, puntos, proyecto no se muestra porque es implícito, responsable).
3. **Given** un proyecto cuyas tareas sin sprint están completadas, **When** un miembro abre la vista Backlog, **Then** ve el estado vacío equivalente al de la bandeja global, invitando a crear una tarea.
4. **Given** un visitante sin permisos de edición en el proyecto, **When** abre la vista Backlog, **Then** ve las mismas filas pero sin las interacciones de edición, igual que en la bandeja global.

---

### User Story 2 - Vista Kanban del proyecto con el estilo del tablero global (Priority: P2)

Un miembro del proyecto selecciona la vista "Kanban" en la sección de tareas del
proyecto. El tablero se ve y funciona igual que el modo kanban de la página global
`/tasks`: columnas por estado con los mismos nombres y orden, tarjetas con el mismo
diseño (título, identificador, prioridad, puntos, tipo, responsable) y el mismo
mecanismo para cambiar una tarea de estado. Las tarjetas muestran solo las tareas
del proyecto, respetando el filtro de sprint que ya existe en la vista.

**Why this priority**: Completa la consistencia pedida: hoy el kanban del proyecto
tiene un diseño propio distinto al tablero global, y el usuario quiere una sola
experiencia de tablero en toda la app.

**Independent Test**: Abrir la vista Kanban de un proyecto y compararla lado a lado
con el modo kanban de `/tasks`: mismas columnas, mismas tarjetas, y cambiar el
estado de una tarjeta funciona igual en ambos.

**Acceptance Scenarios**:

1. **Given** un proyecto con tareas en varios estados, **When** un miembro abre la vista Kanban, **Then** las columnas y su orden son idénticos a los del kanban de `/tasks` y las tarjetas tienen el mismo diseño.
2. **Given** una tarjeta en la vista Kanban del proyecto, **When** un miembro con permisos cambia su estado con el mismo mecanismo que en el tablero global, **Then** la tarjeta se mueve a la columna correspondiente y el cambio persiste.
3. **Given** un filtro de sprint activo en la vista Kanban del proyecto, **When** el miembro filtra, **Then** las tarjetas se limitan a ese sprint manteniendo el mismo diseño.
4. **Given** un visitante sin permisos de edición, **When** abre la vista Kanban, **Then** puede ver el tablero pero no cambiar estados, igual que en el tablero global.

---

### User Story 3 - La vista Lista y los flujos existentes no se alteran (Priority: P3)

La vista "Lista" del proyecto conserva su comportamiento actual, y las tres vistas
siguen integradas con los flujos existentes del proyecto: crear tarea, editar
tarea, métricas y contadores de la ficha del proyecto. La unificación visual no
rompe ninguna capacidad previa.

**Why this priority**: Protege la regresión: el cambio es de presentación y no
debe alterar la funcionalidad existente; se prioriza al final porque su valor es
de salvaguarda, no de capacidad nueva.

**Independent Test**: Recorrer la ficha del proyecto completa (lista, backlog,
kanban, métricas, creación y edición de tareas) y comprobar que todo funciona como
antes, con las únicas diferencias visuales pedidas en las vistas Backlog y Kanban.

**Acceptance Scenarios**:

1. **Given** cualquier proyecto, **When** un miembro alterna entre las tres vistas, **Then** la vista Lista se comporta exactamente como antes del cambio.
2. **Given** una tarea creada desde la ficha del proyecto, **When** el miembro la edita desde cualquiera de las vistas unificadas, **Then** el formulario de edición y el guardado funcionan igual que en las páginas globales.
3. **Given** la ficha del proyecto con sus métricas y contadores, **When** se unifican las vistas, **Then** los contadores (totales, por estado, backlog) siguen calculándose sobre el mismo conjunto de tareas.

---

### Edge Cases

- **Tarea sin puntos o sin identificador**: la fila o tarjeta oculta esos elementos, igual que en las vistas globales.
- **Título muy largo**: se trunca en la fila/tarjeta sin perder información, con el mismo tratamiento visual que las vistas globales.
- **Proyecto sin tareas sin sprint**: la vista Backlog muestra su estado vacío en lugar de una lista vacía.
- **Proyecto sin tareas**: la vista Kanban muestra columnas vacías con el mismo tratamiento visual que el tablero global.
- **Visibilidad de solo lectura**: usuarios sin permiso de edición ven las vistas idénticas pero sin interacciones de cambio (sin abrir edición, sin mover tarjetas).
- **Cambio de estado en kanban con el proyecto archivado o completado**: las tarjetas no permiten cambios, consistente con las reglas actuales del proyecto.
- **Tareas completadas**: se muestran con el mismo tratamiento (columna final, tachado o dimming) que en las vistas globales.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: La vista Backlog de la ficha de proyecto MUST mostrar las tareas del proyecto sin sprint con la misma organización que la bandeja global: secciones por prioridad (alta y urgente, media, baja e ideas), orden por fecha de captura dentro de cada sección y selector de orden (prioridad | recientes).
- **FR-002**: Las filas de la vista Backlog del proyecto MUST presentar los mismos elementos visuales que las filas de `/backlog`: identificador legible, título con truncamiento, indicador de prioridad, puntos cuando existen, responsable o "sin asignar"; no deben mostrar el chip de proyecto porque el contexto ya es el proyecto.
- **FR-003**: La vista Backlog del proyecto MUST mostrar el mismo estado vacío de la bandeja global (adaptado al contexto del proyecto) cuando no hay tareas sin sprint que listar.
- **FR-004**: La vista Kanban de la ficha de proyecto MUST usar las mismas columnas por estado, el mismo orden de columnas y el mismo diseño de tarjeta que el modo kanban de `/tasks`.
- **FR-005**: Las tarjetas del kanban del proyecto MUST ofrecer el mismo mecanismo de cambio de estado que el tablero global, respetando los permisos del proyecto (miembros con permiso pueden; visitantes o proyectos no activos no).
- **FR-006**: Ambas vistas unificadas MUST respetar el filtro de sprint existente en la ficha del proyecto: al filtrar por un sprint, solo se listan o tarjetean sus tareas; al filtrar "sin sprint", solo las tareas del backlog del proyecto.
- **FR-007**: La vista Lista del proyecto MUST conservar su comportamiento y apariencia actuales (fuera del alcance del cambio).
- **FR-008**: La interacción de editar una tarea desde las vistas unificadas MUST abrir el mismo formulario de edición que usan las páginas globales y guardar con el mismo comportamiento.
- **FR-009**: Las vistas unificadas MUST mantenerse consistentes con la información que ya muestra la ficha del proyecto (identificador legible, puntos) sin requerir que el usuario actualice datos.
- **FR-010**: Los contadores y métricas de la ficha del proyecto (totales, por estado, pendientes de sprint) MUST seguir calculándose igual tras la unificación.
- **FR-011**: Las vistas unificadas MUST ser utilizables con las mismas capacidades responsivas que las páginas globales correspondientes (tablero con desplazamiento horizontal en pantallas pequeñas, filas legibles en móvil).

### Key Entities _(include if feature involves data)_

- **Task (tarea)**: sin cambios de datos en esta feature; se reutilizan sus atributos existentes (identificador legible, título, descripción, tipo, prioridad, puntos, estado, proyecto, sprint, responsable).
- **Project (proyecto)**: contexto de las vistas; sin cambios estructurales.
- **Sprint**: filtro existente de las vistas del proyecto; sin cambios estructurales.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: En una comparación lado a lado, las filas de la vista Backlog del proyecto son visualmente indistinguibles de las filas de `/backlog` (mismos elementos, jerarquía y estados), con la única diferencia del chip de proyecto omitido por contexto.
- **SC-002**: En una comparación lado a lado, el tablero Kanban del proyecto es visualmente indistinguible del modo kanban de `/tasks` (columnas, tarjetas, estados), limitado a las tareas del proyecto.
- **SC-003**: El 100% de las interacciones disponibles en las vistas globales equivalentes (abrir edición, cambiar estado en kanban) funcionan igual en las vistas del proyecto para usuarios con permisos.
- **SC-004**: Ningún flujo existente de la ficha del proyecto se rompe: la suite de pruebas actual sigue pasando sin cambios de comportamiento en la vista Lista, métricas y creación/edición de tareas.
- **SC-005**: Un usuario encuentra una tarea concreta en cualquiera de las dos vistas unificadas en el mismo tiempo que tarda en la página global equivalente.

## Assumptions

- La vista "Backlog" del proyecto conserva su significado actual: tareas del proyecto que no tienen sprint asignado.
- La vista "Lista" no se modifica; el pedido se limita a las vistas Backlog y Kanban.
- La bandeja global de referencia es `/backlog` (feature 018) y el tablero global de referencia es el modo kanban de `/tasks`.
- No se incluye el campo de captura rápida del backlog global en la vista del proyecto: la creación de tareas del proyecto sigue usando el botón/flujo existente.
- Los filtros de la bandeja global "Sin proyecto / Con proyecto" no aplican en el contexto del proyecto; en su lugar se conserva el filtro de sprint ya existente en la ficha.
- "Mismo estilo" se refiere a presentación visual y organización (secciones, columnas, tarjetas, chips, estados vacíos) y a las interacciones de edición/cambio de estado, no a cambios de datos ni de permisos.
- Los usuarios con acceso a la ficha del proyecto siguen siendo los mismos; las reglas de solo lectura para no miembros se conservan.
