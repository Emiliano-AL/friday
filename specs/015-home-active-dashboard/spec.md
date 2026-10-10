# Feature Specification: Panel de inicio con actividad del usuario

**Feature Branch**: `015-home-active-dashboard`

**Created**: 2026-10-03

**Status**: Draft

**Input**: User description: "usando el mcp de stitch en el proyecto 3835093330700486424, vamos a usar el item "Friday - home welcome screen", actualmente ya tenemos el home / pero unicamente cuando todo esta en blanco (un nuevo usuario), debemos agregar el nuevo UI para cuando el usuario ya tiene actividad dentro del sistema en el misma ruta /"

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Panel resumen personalizado para usuarios con actividad (Priority: P1)

Un usuario autenticado que ya participa en el sistema (pertenece a proyectos o tiene tareas asignadas) entra a la ruta de inicio (`/`) y, en lugar del lienzo vacío de bienvenida, encuentra un panel de resumen personalizado: un saludo con su nombre y un subtítulo contextual con sus tareas prioritarias de hoy y el avance de su sprint activo; tarjetas de resumen con el sprint activo (nombre y porcentaje de avance), el total de sus tareas activas (y cuántas son urgentes o de alta prioridad) y sus proyectos en progreso; su lista de "Mis tareas para hoy & prioritarias"; el panel de su sprint activo con el progreso de entrega y un gráfico de avance; sus proyectos activos con porcentaje de avance; y una tarjeta de insight semanal con sus métricas reales. Todo con datos reales del sistema, sin datos simulados.

**Why this priority**: Es el corazón de la funcionalidad: el inicio deja de ser un lienzo estático para convertirse en el centro de operaciones diario del usuario. Sin este panel, un usuario activo no tiene una visión consolidada de sus prioridades al entrar a la aplicación.

**Independent Test**: Puede probarse de forma independiente: crear un usuario con al menos un proyecto y tareas asignadas, acceder a `/` y verificar que el panel completo se renderiza con los datos reales de ese usuario (saludo personalizado, tarjetas de resumen, lista de tareas, panel de sprint, proyectos activos e insight semanal).

**Acceptance Scenarios**:

1. **Given** un usuario autenticado con actividad (pertenece a al menos un proyecto o tiene tareas asignadas), **When** accede a `/`, **Then** ve el panel de resumen con un saludo personalizado con su nombre y un subtítulo contextual con sus tareas prioritarias de hoy y el avance de su sprint activo.
2. **Given** un usuario con un sprint activo en curso, **When** observa las tarjetas de resumen, **Then** la tarjeta de sprint activo muestra el nombre del sprint, su porcentaje de avance y los días restantes.
3. **Given** un usuario con tareas asignadas, **When** observa la sección "Mis tareas para hoy & prioritarias", **Then** ve cada tarea con su proyecto, prioridad, título, fecha límite o estado y número de comentarios, ordenadas por prioridad y cercanía de la fecha límite, con un enlace para ver todas sus tareas.
4. **Given** un usuario con proyectos en progreso, **When** observa la sección "Proyectos activos", **Then** ve cada proyecto con su nombre, una nota de estado reciente y su porcentaje de avance, con un enlace para ver todos.
5. **Given** un usuario sin actividad (nuevo en el sistema), **When** accede a `/`, **Then** sigue viendo el lienzo vacío de bienvenida actual, sin cambios respecto al comportamiento previo.

---

### User Story 2 - Contexto del equipo: actividad reciente e insight semanal (Priority: P2)

Un usuario con actividad ve, dentro del mismo panel de inicio, el contexto de su equipo: un feed "Actividad del equipo" con los eventos recientes y reales generados dentro del sistema (tareas completadas, comentarios, creaciones de tarea, aperturas o cierres de sprint), cada uno indicando quién realizó la acción, sobre qué y hace cuánto tiempo; y una tarjeta de insight semanal con sus propias métricas reales de la semana (tareas resueltas y tiempo medio de ciclo).

**Why this priority**: Aporta conciencia situacional sin salir del inicio, pero el valor principal del panel (las prioridades propias del usuario) ya existe sin esta historia; por eso es prioridad secundaria.

**Independent Test**: Puede probarse de forma independiente: con un usuario que tiene eventos de equipo recientes y tareas resueltas en la semana, acceder a `/` y verificar que el feed muestra quién hizo qué y hace cuánto, y que la tarjeta de insight muestra sus métricas reales de la semana.

**Acceptance Scenarios**:

1. **Given** un usuario con actividad y eventos recientes del equipo, **When** accede a `/`, **Then** el feed "Actividad del equipo" muestra los eventos reales con quién los realizó, sobre qué entidad y hace cuánto tiempo (tiempo relativo).
2. **Given** un usuario con actividad pero sin eventos recientes del equipo, **When** accede a `/`, **Then** el feed muestra un estado vacío honesto, sin datos simulados.
3. **Given** un usuario que resolvió tareas durante la semana actual, **When** observa la tarjeta de insight semanal, **Then** ve la cantidad de tareas resueltas en la semana y su tiempo medio de ciclo calculados con datos reales.

---

### User Story 3 - Acciones rápidas y navegación desde el panel (Priority: P3)

Un usuario con actividad puede disparar las acciones más frecuentes directamente desde el panel de inicio: ir a su sprint activo, crear una tarea nueva, abrir el tablero de su sprint activo, ver todas sus tareas y ver todos sus proyectos. Cada acción navega al destino correspondiente ya existente en la aplicación.

**Why this priority**: Las acciones rápidas aceleran el trabajo diario, pero el panel ya entrega su valor completo como vista de solo lectura; la navegación es el engranaje final que convierte al panel en punto de partida operativo.

**Independent Test**: Puede probarse de forma independiente: desde el panel de inicio, activar cada acción y verificar que navega al destino correcto de la aplicación (sprint activo, creación de tarea, tablero del sprint, lista de tareas, listado de proyectos).

**Acceptance Scenarios**:

1. **Given** un usuario con un sprint activo, **When** hace clic en "Ir al sprint", **Then** navega a la vista de su sprint activo.
2. **Given** un usuario con actividad, **When** hace clic en "Nueva tarea", **Then** se abre el flujo de creación de tarea existente en la aplicación.
3. **Given** un usuario con actividad, **When** hace clic en "Abrir tablero del sprint" (con sprint activo), **Then** navega al tablero de ese sprint.
4. **Given** un usuario con actividad, **When** hace clic en "Ver todas mis tareas" o en "Ver todos" de proyectos, **Then** navega a la sección correspondiente (Mis tareas, Proyectos).

---

### Edge Cases

- **Usuario con proyectos pero sin tareas asignadas**: la sección de tareas muestra un estado vacío honesto; el resto del panel funciona con normalidad.
- **Usuario con tareas pero sin sprint activo**: no se muestran la tarjeta de sprint activo, el panel del sprint ni la acción "Ir al sprint"; el resto del panel funciona con normalidad.
- **Sprint activo sin tareas**: el panel del sprint muestra su progreso real (0 de 0 resueltas) y sus fechas, sin fallar.
- **Usuario sin eventos recientes del equipo**: el feed muestra un estado vacío, sin datos simulados.
- **Listas extensas**: si el usuario tiene muchas tareas o proyectos, las secciones muestran un subconjunto acotado con su enlace "ver todos", sin paginación compleja dentro del panel.
- **Franja horaria del saludo**: el saludo se adapta a la hora local del usuario (mañana, tarde, noche) incluso en cambios de zona horaria o usuarios en husos distintos.
- **Transición de estado**: un usuario nuevo crea su primer proyecto y, al volver a `/`, ve el panel de actividad en lugar del lienzo vacío (y viceversa: si su única actividad desaparece, vuelve a ver el lienzo vacío).
- **Contenido largo**: nombres de usuario, proyecto, sprint o tarea muy largos se truncan con elipsis sin romper el layout del panel.
- **Pantallas estrechas**: en viewports pequeños el panel se apila en una sola columna sin scroll horizontal.
- **Proyectos completados o archivados**: no aparecen dentro de "Proyectos activos" ni cuentan como proyectos "en progreso".

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: La ruta de inicio (`/`) MUST mostrar el panel de actividad cuando el usuario autenticado tiene actividad en el sistema: pertenece a al menos un proyecto o tiene al menos una tarea asignada.
- **FR-002**: La ruta de inicio (`/`) MUST conservar el estado vacío actual (lienzo de bienvenida) cuando el usuario autenticado no tiene actividad en el sistema.
- **FR-003**: El panel MUST mostrar un saludo personalizado con el nombre del usuario, adaptado a la franja horaria local (mañana, tarde o noche), y un subtítulo contextual que resuma sus tareas prioritarias del día y, cuando existe, el avance de su sprint activo.
- **FR-004**: El panel MUST presentar tarjetas de resumen con: el sprint activo (nombre, porcentaje de avance y días restantes), las tareas activas del usuario (total y cuántas son urgentes o de alta prioridad) y los proyectos en progreso del usuario (total).
- **FR-005**: El panel MUST listar las "tareas para hoy & prioritarias" del usuario con, como mínimo, proyecto, prioridad, título, fecha límite o estado, y número de comentarios; ordenadas por prioridad y cercanía de la fecha límite; y MUST incluir un enlace "Ver todas mis tareas" con el total real.
- **FR-006**: Cuando existe un sprint activo, el panel MUST mostrar su panel correspondiente con nombre, objetivo, progreso de entrega (tareas resueltas vs. totales), un gráfico de avance del sprint y un enlace al tablero del sprint.
- **FR-007**: El panel MUST listar los proyectos activos del usuario con nombre, nota de estado reciente y porcentaje de avance, y MUST incluir un enlace para ver todos los proyectos.
- **FR-008**: El panel MUST mostrar una tarjeta de insight semanal con métricas reales del usuario: tareas resueltas en la semana y tiempo medio de ciclo.
- **FR-009**: El panel MUST mostrar el feed "Actividad del equipo" con eventos reales y recientes generados dentro del sistema (p. ej., tareas completadas, comentarios, creaciones de tarea, aperturas o cierres de sprint), indicando quién realizó la acción, sobre qué entidad y hace cuánto tiempo (tiempo relativo). Cuando no hay eventos, MUST mostrar un estado vacío.
- **FR-010**: El panel MUST ofrecer las acciones rápidas "Ir al sprint activo" (solo cuando existe un sprint activo) y "Nueva tarea".
- **FR-011**: Cuando no hay sprint activo, el panel MUST omitir la tarjeta de sprint activo, el panel del sprint y la acción "Ir al sprint activo".
- **FR-012**: Toda la información visible en el panel MUST provenir exclusivamente de datos reales del sistema; está prohibido mostrar datos de ejemplo, simulados o hardcodeados.

### Key Entities _(include if feature involves data)_

- **Usuario**: persona autenticada; se usa su nombre (saludo) y su relación con proyectos y tareas decide si ve el panel o el lienzo vacío.
- **Proyecto**: espacio de trabajo con nombre, estado (en progreso, completado), miembros, porcentaje de avance y una nota de estado reciente; alimenta las tarjetas de resumen y la sección "Proyectos activos".
- **Sprint**: iteración con nombre, objetivo, fechas de inicio y fin, estado (activo, cerrado) y tareas asociadas; alimenta la tarjeta y el panel de sprint activo.
- **Tarea**: unidad de trabajo con título, proyecto, prioridad, estado, fecha límite, responsable asignado y comentarios; alimenta la lista de tareas del usuario y el progreso del sprint.
- **Evento de actividad**: acción realizada por un usuario sobre una entidad del sistema (completar tarea, comentar, crear tarea, abrir o cerrar sprint) con su marca temporal; alimenta el feed "Actividad del equipo".

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: El 100% de las visitas de usuarios autenticados a `/` muestran el estado correcto (panel de actividad o lienzo vacío) según la actividad real del usuario en el sistema.
- **SC-002**: El 100% de los datos visibles en el panel (resumen, tareas, sprint, proyectos, insight y actividad del equipo) corresponden a datos reales del sistema en el momento de la visita; no se observa ningún dato simulado.
- **SC-003**: El usuario puede llegar a su sprint activo o iniciar la creación de una tarea desde el inicio en un máximo de 2 clics.
- **SC-004**: El usuario identifica sus prioridades del día (tareas prioritarias y avance del sprint activo) sin navegar fuera del inicio.
- **SC-005**: El panel completo se muestra al usuario en menos de 2 segundos tras la navegación, en condiciones estándar de escritorio.

## Assumptions

- **Fuente visual**: la referencia de diseño es la pantalla "Friday — Inicio / Panel con Datos Activos (Minimalista)" del proyecto Stitch `3835093330700486424`, en modo claro y idioma español, consistente con el design system del workspace existente.
- **Definición de "tener actividad"**: el usuario pertenece a al menos un proyecto o tiene al menos una tarea asignada; si no se cumple ninguna de las dos, se muestra el lienzo vacío actual.
- **Alcance de la ruta**: el cambio aplica únicamente a la ruta de inicio (`/`); no se rediseña ninguna otra pantalla existente.
- **Eventos del feed**: el feed "Actividad del equipo" incluye únicamente eventos generados dentro de la aplicación (tareas completadas, comentarios, creaciones de tarea, aperturas o cierres de sprint); no se integran eventos de sistemas externos (p. ej., pull requests).
- **Saludo**: la franja horaria (mañana, tarde, noche) se calcula con la hora local del usuario.
- **Secciones sin datos**: cuando no hay sprint activo se omiten sus secciones (tarjeta, panel y acción); el resto de secciones sin datos muestran estados vacíos honestos.
- **Métricas existentes**: los porcentajes de avance de proyecto y de sprint, los estados y las prioridades ya forman parte del dominio existente de la aplicación.
