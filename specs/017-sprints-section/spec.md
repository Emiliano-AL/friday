# Feature Specification: Sección Sprints del detalle de proyecto

**Feature Branch**: `017-sprints-section`

**Created**: 2026-10-06

**Status**: Draft

**Input**: User description: "en la vista de /projects/{id} en el menu de Sprint y tareas, vamos a mejorar la seccion de Sprints. Usando el mcp de Stitch del proyecto 3835093330700486424 en concreto con el item "Sprint y tareas section" como base"

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Sección Sprints rediseñada (Priority: P1)

Un miembro del proyecto, dentro de la pestaña "Sprints y tareas" de la página del proyecto, encuentra la sección Sprints completamente renovada según el diseño de referencia: un encabezado con el total de sprints, chips de filtro por estado (Todos, Activo, Planificados, Completados) con sus conteos reales y un botón "Planificar un sprint"; el sprint activo como tarjeta expandida con su badge "Sprint Activo", nombre, fechas, días restantes, meta del sprint, progreso de tareas (porcentaje y "X de Y tareas") y acceso directo al tablero; y debajo la lista "Otros Sprints" con cada sprint restante en fila compacta (estado, fechas, número de tareas, porcentaje completado y meta resumida) y sus acciones contextuales. Todo con los datos reales del proyecto.

**Why this priority**: Es la mejora solicitada en sí: hoy la sección muestra tarjetas básicas (actual/próximo/backlog) sin filtros, sin meta visible ni progreso por sprint; el rediseño concentra en una sola vista el estado completo de los sprints del proyecto y reduce clics para actuar.

**Independent Test**: Puede probarse de forma independiente: abrir un proyecto con sprints en varios estados y verificar el encabezado con conteos, la tarjeta del sprint activo con meta y progreso reales, la lista de otros sprints con su información y acciones, y que "Planificar un sprint" abre el flujo de creación existente.

**Acceptance Scenarios**:

1. **Given** un proyecto con sprints en varios estados, **When** un miembro abre la pestaña "Sprints y tareas", **Then** la sección Sprints muestra el encabezado con el total de sprints, los chips "Todos / Activo (N) / Planificados (N) / Completados (N)" con conteos reales y el botón "Planificar un sprint".
2. **Given** un proyecto con un sprint activo, **When** se observa la sección, **Then** el sprint activo aparece como tarjeta expandida con badge "Sprint Activo", nombre, fechas de inicio y fin, días restantes, meta del sprint (cuando existe), progreso de tareas con porcentaje y "X de Y tareas", y un acceso directo al tablero del sprint.
3. **Given** un proyecto con sprints planificados o completados además del activo, **When** se observa la sección, **Then** la lista "Otros Sprints" muestra cada uno con su estado (Planificado/Completado), fechas, número de tareas (y "de N concluidas" para los completados), porcentaje completado y meta resumida.
4. **Given** el usuario tiene permisos de gestión, **When** observa un sprint planificado en "Otros Sprints", **Then** ve las acciones "Editar" y "Eliminar"; en un sprint completado ve "Ver resumen"; y en el activo las acciones de completar/editar existentes.
5. **Given** cualquier usuario con acceso al proyecto, **When** pulsa "Planificar un sprint", **Then** se abre el flujo de creación de sprint existente; al pulsar "Ver tablero" o "Ver resumen", navega a la vista del sprint existente.

---

### User Story 2 - Elementos no implementados deshabilitados honestamente (Priority: P2)

El diseño de referencia incluye elementos que el sistema actual no tiene: puntos de historia por sprint ("32 / 45 Puntos", "puntos de historia") y velocidad. Esos elementos se muestran deshabilitados con el indicador "Próximamente" (siguiendo el precedente del propio detalle de sprint) o se omiten de forma honesta, y ningún texto de la sección promete métricas que el sistema no calcula. El resto de la información (porcentajes, conteos de tareas, días restantes) usa únicamente datos reales.

**Why this priority**: Protege la credibilidad de la interfaz mientras el rediseño principal (P1) ya entrega su valor; es la aplicación del principio de estados honestos del proyecto a los nuevos elementos visuales.

**Independent Test**: Puede probarse de forma independiente: revisar la sección y verificar que todo dato mostrado corresponde a información real del sistema y que los elementos de puntos de historia aparecen deshabilitados con "Próximamente" sin ejecutar acción alguna.

**Acceptance Scenarios**:

1. **Given** la sección Sprints renderizada, **When** el usuario observa cualquier métrica (porcentajes, conteos, días), **Then** todos los valores se corresponden con los datos reales del proyecto.
2. **Given** un elemento de puntos de historia del diseño, **When** se muestra, **Then** aparece deshabilitado con el indicador "Próximamente" y no responde a interacciones; si se omite, ningún otro texto sugiere que la métrica existe.

---

### User Story 3 - Colapsar y expandir "Otros Sprints" (Priority: P3)

La lista "Otros Sprints" puede colapsarse y expandirse mediante un control visible ("Colapsar todos" / "Expandir"), permitiendo al usuario enfocarse en el sprint activo cuando la lista de sprints históricos es larga.

**Why this priority**: Mejora la usabilidad con muchos sprints, pero la sección ya es completamente funcional sin él; por eso es la menor prioridad.

**Independent Test**: Puede probarse de forma independiente: pulsar el control y verificar que la lista de "Otros Sprints" se oculta y muestra con su estado guardado durante la sesión.

**Acceptance Scenarios**:

1. **Given** la lista "Otros Sprints" visible, **When** el usuario pulsa "Colapsar todos", **Then** la lista se oculta y el control pasa a "Expandir".
2. **Given** la lista colapsada, **When** el usuario pulsa "Expandir", **Then** la lista vuelve a mostrarse completa.

---

### Edge Cases

- **Proyecto sin sprints**: la sección muestra un estado vacío honesto con el botón "Planificar un sprint" como acción principal.
- **Proyecto sin sprint activo** (solo planificados/completados): no se muestra la tarjeta expandida; todos los sprints aparecen en "Otros Sprints" y el chip "Activo (0)" refleja la realidad.
- **Varios sprints activos a la vez**: la tarjeta expandida muestra el más reciente (misma regla que el comportamiento actual); los demás activos aparecen en la lista con su estado.
- **Sprint activo vencido** (fecha fin pasada): los días restantes muestran 0 (o el valor real sin negativos) sin romperse.
- **Sprint sin meta**: el bloque "Meta del Sprint" se omite limpiamente en lugar de mostrar texto vacío.
- **Sprint completado sin tareas**: muestra "0 de 0 tareas" y su porcentaje real sin errores de cálculo.
- **Sprint con nombre largo o meta extensa**: se truncan con elipsis sin desbordar la tarjeta o la fila.
- **Usuario sin permisos de gestión**: ve toda la información pero sin acciones de editar/eliminar/completar (comportamiento actual de permisos).
- **Muchos sprints históricos**: la lista "Otros Sprints" es desplazable de forma acotada sin romper el layout.
- **Filtro que deja la lista vacía** (p. ej. "Planificados (0)"): mensaje de estado vacío honesto para ese filtro.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: La sección Sprints de la pestaña "Sprints y tareas" MUST mostrar un encabezado con el número total de sprints del proyecto, chips de filtro por estado (Todos, Activo, Planificados, Completados) con conteos reales, y la acción "Planificar un sprint".
- **FR-002**: El sprint activo MUST mostrarse como tarjeta expandida con: badge "Sprint Activo", nombre, fechas de inicio y fin, días restantes, meta del sprint (omitida si no existe), progreso de tareas con porcentaje real y conteo "X de Y tareas", y acceso directo al tablero del sprint.
- **FR-003**: Los sprints no activos MUST listarse en "Otros Sprints" con: nombre, estado (Planificado/Completado), fechas, número de tareas ("de N concluidas" en completados), porcentaje completado real y meta resumida (cuando existe).
- **FR-004**: Los chips de filtro MUST filtrar la lista "Otros Sprints" por el estado seleccionado y actualizar la vista; el chip "Todos" MUST mostrar todos los sprints no activos.
- **FR-005**: Las acciones MUST ser las existentes del sistema con sus permisos actuales: editar y eliminar en sprints planificados (solo quien puede gestionar), completar en el activo (según reglas actuales), y "Ver resumen"/"Ver tablero" navegando a la vista del sprint (disponible para cualquier miembro); los sprints completados siguen siendo de solo lectura.
- **FR-006**: Los elementos del diseño que no existen en el sistema (puntos de historia y métricas derivadas) MUST mostrarse deshabilitados con el indicador "Próximamente" u omitirse de forma honesta, sin ejecutar acciones ni prometer métricas no calculadas.
- **FR-007**: Toda la información visible de la sección MUST provenir de datos reales del proyecto (fechas, metas, conteos y porcentajes calculados sobre las tareas existentes).
- **FR-008**: La lista "Otros Sprints" MUST poder colapsarse y expandirse con un control visible, conservando su estado durante la sesión del usuario.

### Key Entities _(include if feature involves data)_

- **Proyecto**: contenedor de los sprints; la sección vive en su página de detalle.
- **Sprint**: iteración con nombre, meta (opcional), fechas de inicio y fin, y estado (Planificado, Activo, Completado); es la unidad principal de la sección.
- **Tarea**: alimenta los conteos y porcentajes de progreso de cada sprint ("X de Y tareas", % completado).
- **Usuario**: miembro del proyecto que consulta la sección; solo quien tiene permisos de gestión puede editar/eliminar/completar sprints.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Un miembro comprende el estado completo de los sprints del proyecto (activo, planificados, completados y sus progresos) sin navegar fuera de la sección.
- **SC-002**: El 100% de las métricas visibles (conteos, porcentajes, días restantes) corresponden a datos reales; el 0% de elementos "Próximamente" ejecuta acciones.
- **SC-003**: Filtrar la lista a cualquier estado se completa en un máximo de 2 clics.
- **SC-004**: Llegar al tablero del sprint activo o a la vista de cualquier otro sprint toma un máximo de 2 clics.
- **SC-005**: Abrir la sección y cada interacción (filtrar, colapsar, acciones) se percibe en menos de 1 segundo en condiciones normales de escritorio.

## Assumptions

- **Fuente visual**: la referencia de diseño es la pantalla "Friday — Sprints y Tareas de Proyecto (Sección Sprints Pulida)" del proyecto Stitch `3835093330700486424`, en modo claro e idioma español, consistente con el design system del workspace.
- **Alcance**: únicamente la sección Sprints dentro de la pestaña "Sprints y tareas" existente de `/projects/{id}`; la sección de Tareas que aparece debajo en el diseño queda fuera (ya existe con sus vistas Lista/Backlog/Kanban).
- **Puntos de historia**: no existe en el sistema (la vista de detalle de sprint ya los marca como "Próximamente"); en esta sección se muestran deshabilitados con "Próximamente" u omitidos, nunca simulados.
- **Sprint activo**: si hubiera varios activos, la tarjeta expandida muestra el más reciente, igual que la regla actual de la aplicación; los demás aparecen en la lista.
- **"Ver resumen" y "Ver tablero"**: navegan a la vista de detalle del sprint ya existente (que es de solo lectura para sprints completados).
- **Datos existentes**: el sistema ya almacena nombre, fechas, meta, estado y tareas de cada sprint; el rediseño usa esos datos sin cambios de dominio (las ampliaciones concretas de payload se deciden en la fase de planificación).
