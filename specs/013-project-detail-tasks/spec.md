# Feature Specification: Tareas del Proyecto — Modal de Nueva Tarea y Listado Estilo Mis Tareas

**Feature Branch**: `013-project-detail-tasks`

**Created**: 2026-10-01

**Status**: Draft

**Input**: User description: "Cuando entro al detalle de un proyecto, y doy click en el boton de nueva tarea debe mostrar el modal de nueva tarea (screenshot 8.39.12). En la tab de Sprint y tareas La seccion de tareas debe mostrar la vista de /task con el listado de tareas que pertencen a ese proyecto. El formulario de nueva task no debe aparecer (screenshot 8.43.59)."

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Crear tarea desde el detalle del proyecto (Priority: P1)

El usuario está viendo el detalle de un proyecto y quiere crear una tarea para ese proyecto. Hoy el botón "Nueva Tarea" del encabezado solo cambia a la pestaña "Sprints y tareas" y no abre nada; el usuario debe salir del proyecto e ir a "Mis Tareas" para poder crear la tarea con el modal. El usuario espera que el botón "Nueva Tarea" abra directamente el mismo modal de nueva tarea que ya conoce de "Mis Tareas", con el proyecto actual ya preseleccionado y fijo, listo para completar título y guardar.

**Why this priority**: Es la acción principal que el usuario intenta realizar desde el detalle del proyecto y hoy no funciona: obliga a abandonar el contexto del proyecto y crear la tarea desde otra página. Sin esta historia, la otra mitad de la feature (listado) no tiene forma cómoda de crear tareas.

**Independent Test**: Entrar al detalle de cualquier proyecto, pulsar "Nueva Tarea" en el encabezado y verificar que se abre el modal de nueva tarea con el proyecto actual preseleccionado; completar el título, guardar y verificar que la tarea aparece en la lista del proyecto perteneciente.

**Acceptance Scenarios**:

1. **Given** el usuario está en el detalle de un proyecto, **When** pulsa el botón "Nueva Tarea" del encabezado, **Then** se abre el modal de nueva tarea (el mismo que en "Mis Tareas") con el proyecto actual preseleccionado y sin posibilidad de cambiarlo.
2. **Given** el modal de nueva tarea está abierto desde un proyecto, **When** el usuario guarda la tarea, **Then** la tarea queda asociada a ese proyecto, el modal se cierra y la tarea es visible en el listado del proyecto sin necesidad de recargar manualmente.
3. **Given** el modal abierto desde un proyecto, **When** el usuario revisa el campo de sprint, **Then** solo puede elegir entre los sprints de ese proyecto o ninguno.
4. **Given** el usuario no tiene permiso de escritura en el proyecto, **When** mira el encabezado, **Then** no ve una invitación a crear tareas que luego no podría completar (el botón no se muestra o permanece deshabilitado, según el patrón actual de permisos de la aplicación).

---

### User Story 2 - Listado de tareas del proyecto con la experiencia de "Mis Tareas" (Priority: P2)

El usuario está en la pestaña "Sprints y tareas" del proyecto, en la sección "Tareas" con la vista "Lista" seleccionada. Hoy esa sección muestra un listado básico seguido de un formulario inline de creación rápida (título, descripción, tipo, prioridad, responsable, sprint y botón "Añadir") que ocupa espacio, difiere del resto de la aplicación y duplica la creación con modal. El usuario espera ver aquí la misma experiencia de listado que la página "Mis Tareas": agrupación por prioridad/estado, la información completa por tarea y las mismas acciones de fila (cambiar estado, editar, comentarios, eliminar), pero limitada a las tareas que pertenecen a este proyecto. El formulario inline de nueva tarea no debe aparecer.

**Why this priority**: Unifica la experiencia de gestión de tareas en un único lenguaje visual y funcional, elimina un formulario inline redundante y hace que trabajar dentro del proyecto sea equivalente a trabajar en "Mis Tareas" pero con contexto de proyecto. Depende de US1 para crear tareas (el formulario inline desaparece), pero el listado en sí es verificable de forma independiente.

**Independent Test**: Entrar al detalle de un proyecto, pestaña "Sprints y tareas", vista "Lista": verificar que el listado se ve y se comporta como el de "Mis Tareas" (mismos grupos, filas y acciones), que solo contiene tareas del proyecto y que no existe formulario inline de creación debajo del listado.

**Acceptance Scenarios**:

1. **Given** el usuario está en "Sprints y tareas" del proyecto con la vista "Lista", **When** observa la sección "Tareas", **Then** ve el mismo estilo de listado que en "Mis Tareas" (agrupación por prioridad/estado, tarjetas/filas con tipo, prioridad, estado, responsable y acciones) y únicamente las tareas que pertenecen a ese proyecto.
2. **Given** la vista "Lista" del proyecto, **When** el usuario termina de revisar el listado, **Then** no hay ningún formulario inline de creación de tarea (campos de título/descripción ni botón "Añadir") en la sección.
3. **Given** una tarea del listado del proyecto, **When** el usuario usa sus acciones de fila (editar, comentarios, cambiar estado, eliminar), **Then** funcionan igual que en "Mis Tareas" y la edición abre el mismo modal contextualizado al proyecto.
4. **Given** el proyecto no tiene tareas, **When** el usuario abre la vista "Lista", **Then** ve un estado vacío equivalente al de "Mis Tareas" (sin formulario inline).
5. **Given** el usuario cambia a las vistas "Backlog" o "Kanban", **When** compara con el comportamiento anterior, **Then** estas vistas conservan su comportamiento actual (solo cambia la vista "Lista").

---

### Edge Cases

- ¿Qué pasa si el usuario abre el modal de nueva tarea y el proyecto tiene sprints activos e inactivos? Solo deben ofrecerse sprints del proyecto; por defecto sin sprint.
- ¿Qué ocurre si falla la validación al guardar desde el modal (título vacío)? El modal muestra los errores en línea y permanece abierto, igual que en "Mis Tareas".
- ¿Qué pasa si a otro usuario le asignan la tarea mientras el listado del proyecto está visible? El listado refleja el estado actual tras la siguiente actualización de datos (recarga o refresco del listado).
- ¿Cómo se comporta la sección si el usuario solo tiene permiso de lectura? Ve el listado completo pero las acciones de escritura (crear, editar, eliminar) no aparecen o están deshabilitadas, consistente con "Mis Tareas".
- ¿Qué ocurre con los filtros/búsqueda si el proyecto tiene muchas tareas? El listado del proyecto admite los mismos filtros/búsqueda que "Mis Tareas", siempre restringidos a las tareas del proyecto.
- ¿Qué pasa si el usuario crea la tarea desde el modal y el listado del proyecto está en vista "Backlog" o "Kanban"? La tarea nueva aparece al cambiar a la vista correspondiente o al refrescar, sin requisito de recarga manual de la página.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: En el detalle de un proyecto, pulsar el botón "Nueva Tarea" del encabezado MUST abrir el mismo modal de nueva tarea que en "Mis Tareas", no limitarse a cambiar de pestaña.
- **FR-002**: El modal abierto desde un proyecto MUST iniciar con ese proyecto preseleccionado y fijo (el usuario no puede asignar la tarea a otro proyecto desde este contexto).
- **FR-003**: El campo de sprint del modal, en contexto de proyecto, MUST ofrecer únicamente los sprints de ese proyecto más la opción "sin sprint".
- **FR-004**: Al guardar desde el modal, la tarea MUST quedar asociada al proyecto del contexto, el modal MUST cerrarse y la nueva tarea MUST ser visible en el listado del proyecto sin recarga manual de la página.
- **FR-005**: En la pestaña "Sprints y tareas", la sección "Tareas" con la vista "Lista" seleccionada MUST mostrar el listado de tareas con la misma presentación y comportamiento que la vista de lista de "Mis Tareas" (agrupación, información por tarea, acciones de fila), restringido a las tareas del proyecto.
- **FR-006**: El formulario inline de creación rápida de tareas (título, descripción, tipo, prioridad, responsable, sprint y botón "Añadir") MUST NOT aparecer en la sección "Tareas" del proyecto en ninguna de sus vistas.
- **FR-007**: Las acciones de fila del listado del proyecto (cambiar estado, editar, comentar, eliminar) MUST comportarse igual que en "Mis Tareas", abriendo la edición en el mismo modal contextualizado al proyecto.
- **FR-008**: Las vistas "Backlog" y "Kanban" de la sección "Tareas" MUST conservar su comportamiento actual; los cambios de esta feature se limitan a la vista "Lista" y a la apertura del modal.
- **FR-009**: Los permisos existentes MUST respetarse: en el contexto del proyecto, los usuarios sin permiso de escritura no deben poder crear, editar ni eliminar tareas; las acciones de escritura no deben estar disponibles para ellos (ni el botón "Nueva Tarea" como llamado a la acción habilitado).

### Key Entities

- **Proyecto**: contexto que acota toda la feature; las tareas listadas y creadas pertenecen a él. Sus sprints son las únicas opciones de sprint disponibles en el modal.
- **Tarea**: entidad existente (misma de "Mis Tareas"); se crea desde el modal con proyecto fijo y se lista agrupada como en "Mis Tareas".
- **Sprint**: asociación opcional de la tarea, limitada a los sprints del proyecto en este contexto.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: En el detalle del proyecto, el 100% de los intentos de crear una tarea desde el botón "Nueva Tarea" abren el modal en una sola acción, sin pasos intermedios (validado en sesión manual exploratoria de al menos 10 intentos entre vistas y proyectos distintos).
- **SC-002**: Crear una tarea estándar desde el detalle del proyecto (abrir modal, escribir título, guardar) toma menos de 30 segundos, sin abandonar la página del proyecto.
- **SC-003**: El listado de la sección "Tareas" muestra el 100% de las tareas del proyecto (concordancia 1:1 con el total reportado del proyecto) y únicamente esas.
- **SC-004**: Después del cambio, ningún usuario ve el formulario inline de creación en la sección de tareas del proyecto (0 apariciones en validación manual de las vistas Lista, Backlog y Kanban).
- **SC-005**: Las acciones de fila (estado, editar, comentar, eliminar) se ejecutan desde el contexto del proyecto sin navegar a otra página en el 100% de los casos de prueba manual.

## Assumptions

- "El modal de nueva tarea" se refiere al modal de tareas mejorado que se usa hoy en la página "Mis Tareas", no al modal antiguo que aún aparece al editar desde el detalle del proyecto.
- El botón "Nueva Tarea" mantiene su visibilidad actual según permisos del proyecto; esta feature cambia qué hace al pulsarlo, no a quién se le muestra.
- La vista "Lista" del proyecto reutiliza la experiencia de "Mis Tareas" tal cual (agrupación por prioridad/estado, acciones de fila); los filtros y la búsqueda, si se habilitan en este contexto, operan siempre sobre el conjunto de tareas del proyecto.
- La edición de tareas desde el listado del proyecto también se homologa al modal de "Mis Tareas" (FR-007), reemplazando la experiencia del modal antiguo solo dentro de este contexto.
- Los cambios son de interfaz y comportamiento sobre funcionalidades que la aplicación ya ofrece; no se contemplan cambios en el servidor (la creación de tareas con proyecto y sprint ya funciona hoy en la aplicación).
- Las vistas "Backlog" y "Kanban" quedan fuera del alcance de cambio (FR-008).
