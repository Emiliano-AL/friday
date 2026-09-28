# Feature Specification: Rediseño del flujo de tareas — listado, kanban y creación

**Feature Branch**: `[009-tasks-board-ui]`

**Created**: 2026-09-26

**Status**: Draft

**Input**: User description: "Ayudame a mejorar la UI para el flujo de agregar, actualizar tareas, vamos a seguir usando el @DESIGN.md, vamos a trabajar el listado de tareas (lista y kanban), modal para agregar una nueva tarea. Te paso los archivos html base. Si una funcionalidad no esta disponible la documentas y en la UI la pones como proximamente."

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Listado global de tareas con agrupación inteligente (Priority: P1)

El usuario abre "Mis Tareas" desde la navegación y encuentra todas sus tareas de todos sus proyectos en una vista de lista agrupada: un grupo destacado con las tareas de prioridad alta y urgentes, un grupo con las tareas en curso, y un grupo con las próximas y el backlog. La parte superior ofrece búsqueda por texto, filtros rápidos con contadores reales (Todas, Mis Tareas, Urgentes, Completadas) y filtros por proyecto y por responsable. Cada fila muestra el estado (con un botón circular para cambiarlo), el tipo, el título, el proyecto, el sprint, la prioridad, el responsable y acciones rápidas. Un pie de página resume los contadores y los atajos disponibles. El usuario puede crear una tarea con la tecla `C` o abrir el detalle/edición haciendo clic en la fila.

**Why this priority**: Es la superficie principal del flujo de trabajo diario; concentra todo lo que hay que hacer en un solo lugar y habilita por primera vez la sección "Mis Tareas" de la navegación.

**Independent Test**: Crear tareas con distintas prioridades, estados, proyectos y responsables, y verificar que los grupos, contadores y filtros reflejan los datos reales, pudiendo cambiar el estado de una tarea y crear otra nueva sin salir de la página.

**Acceptance Scenarios**:

1. **Given** que el usuario tiene 6 tareas en 2 proyectos (2 urgentes, 1 en curso, 2 en backlog y 1 completada), **When** abre "Mis Tareas", **Then** ve los grupos "Alta Prioridad & Urgentes" (2), "En Curso" (1) y "Próximas & Backlog" (2), la pestaña "Completadas" con contador 1, y el pie resume "6 tareas".
2. **Given** que el usuario escribe en la búsqueda, **When** filtra por texto, **Then** solo se muestran las tareas cuyo título coincide, con estado vacío cuando no hay coincidencias.
3. **Given** que el usuario selecciona un proyecto en el filtro de proyecto, **When** confirma, **Then** solo se listan tareas de ese proyecto y los contadores de las pestañas se recalculan sobre ese conjunto.
4. **Given** que el usuario pulsa el botón circular de estado de una tarea, **When** elige el nuevo estado, **Then** la tarea se mueve al grupo correspondiente y el cambio persiste al recargar.
5. **Given** que el usuario pulsa `C` fuera de campos de texto, **When** está en Mis Tareas, **Then** se abre el diálogo de creación con el foco en el título.
6. **Given** que el usuario abre la pestaña "Completadas", **When** la vista carga, **Then** solo aparecen tareas terminadas, marcadas como tales.

---

### User Story 2 - Tablero kanban de tareas (Priority: P2)

El usuario alterna la vista de lista a kanban con un conmutador persistente y ve columnas por estado (Por Hacer, En Curso, En Revisión, Terminado), con tarjetas que muestran tipo, título, proyecto, sprint, prioridad, número de comentarios y responsable. Puede arrastrar una tarjeta a otra columna para cambiar su estado, o usar el botón "Añadir tarea rápida" de una columna para crear una tarea ya con ese estado inicial. La columna "Bloqueado" del diseño de referencia no existe aún en el dominio y se muestra deshabilitada con la marca "Próximamente".

**Why this priority**: Es la segunda vista del mismo listado y complementa a la lista para quienes gestionan el flujo visualmente; depende de la página de US1 pero no añade capacidades de datos nuevas.

**Independent Test**: Alternar a kanban, arrastrar una tarjeta de "Por Hacer" a "En Curso", recargar y verificar que el estado persistió; crear una tarea desde el botón de columna y verificar que nace en el estado de esa columna.

**Acceptance Scenarios**:

1. **Given** tareas en varios estados, **When** el usuario alterna a kanban, **Then** cada tarjeta aparece en la columna de su estado, con los contadores de columna correctos.
2. **Given** una tarjeta en "Por Hacer", **When** el usuario la arrastra a "En Curso", **Then** la tarjeta se mueve visualmente y el estado persiste tras recargar.
3. **Given** el kanban visible, **When** el usuario pulsa "Añadir tarea rápida" en una columna, **Then** el diálogo de creación abre con el estado inicial preseleccionado al de esa columna.
4. **Given** la columna "Bloqueado" del diseño, **When** el usuario la observa, **Then** aparece deshabilitada con la marca "Próximamente" y no acepta tarjetas.
5. **Given** una tarjeta completada, **When** el usuario la ve en "Terminado", **Then** se distingue visualmente (atenuada/tachada) del resto.

---

### User Story 3 - Diálogo crear y editar tarea (Priority: P3)

El usuario crea o edita tareas en un diálogo refinado: título grande con foco automático e indicación de redacción imperativa; metadatos primarios (tipo, estado inicial, prioridad en control segmentado y responsable); sección opcional de vinculación (proyecto y sprint dependiente del proyecto); y descripción en texto libre con soporte de marcado. El diálogo valida inline, permite "crear otra al guardar", y responde a atajos (guardar con comando+enter, cerrar con escape). Los campos del diseño que aún no existen en los datos (puntos de estimación, fecha de vencimiento, barra de herramientas de marcado con vista previa, tipos extra) se muestran como "Próximamente" deshabilitados. En modo edición, el mismo diálogo permite eliminar la tarea desde una zona de peligro con confirmación.

**Why this priority**: Completa el flujo de agregar/actualizar con la experiencia refinada; el diálogo se consume desde US1/US2 pero su funcionalidad ya existe (crear/editar/borrar), por lo que el riesgo es bajo.

**Independent Test**: Crear una tarea completa desde el diálogo (título, tipo, prioridad, responsable, proyecto, sprint, descripción), verificar que aparece en el listado y kanban; editarla y eliminarla desde el mismo diálogo comprobando validaciones y atajos.

**Acceptance Scenarios**:

1. **Given** el diálogo de creación abierto con la tecla `C`, **When** el usuario lo observa, **Then** el foco está en el título y los campos operativos (tipo, estado, prioridad, responsable, proyecto, sprint, descripción) están habilitados.
2. **Given** que el usuario deja el título vacío e intenta guardar, **When** envía el formulario, **Then** ve el error junto al campo y el diálogo permanece abierto.
3. **Given** que el usuario selecciona un proyecto, **When** abre el selector de sprint, **Then** solo ve los sprints de ese proyecto.
4. **Given** que el usuario activa "Crear otra al guardar", **When** guarda, **Then** el formulario se limpia y permanece abierto; si no la activa, el diálogo cierra.
5. **Given** que el usuario abre el diálogo en modo edición, **When** observa el pie, **Then** ve la zona de peligro "Eliminar tarea" que exige confirmación y, al confirmar, la tarea desaparece.
6. **Given** los campos "Story Points", "Fecha de Vencimiento" y la barra de herramientas de marcado, **When** el usuario los observa, **Then** están deshabilitados con la marca "Próximamente" y nota explicativa.

---

### Edge Cases

- **Sin tareas**: la página muestra un estado vacío amable con una única acción destacada: crear la primera tarea.
- **Búsqueda/filtros sin resultados**: mensaje claro con acción para limpiar filtros.
- **Responsable sin miembros**: el selector de responsable ofrece al menos al propio usuario.
- **Proyecto sin sprints**: el selector de sprint muestra "Sin Sprint" como única opción válida.
- **Textos largos**: títulos se truncan con elipsis en filas y tarjetas.
- **Tarea completada**: por defecto oculta del listado principal; visible en la pestaña "Completadas" y en la columna "Terminado" atenuada.
- **Cambio de estado masivo por arrastre**: el arrastre fuera de una columna válido no produce cambios; el estado ilegal (p. ej. "Bloqueado") no se ofrece.
- **Doble envío**: el botón de guardar se deshabilita mientras procesa.
- **Atajos con foco en campo**: `C` y demás atajos no se disparan desde inputs ni textareas.
- **Pantallas pequeñas**: a 375px el listado pasa a una columna, el kanban desplaza horizontalmente sus columnas y el diálogo ocupa el ancho disponible, sin scroll horizontal de página.
- **Permisos**: solo quien tiene permiso sobre la tarea (según la regla existente: líder del proyecto o responsable) puede editarla o eliminarla; el servidor rechaza intentos ajenos.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: El sistema debe ofrecer una página "Mis Tareas" accesible desde la navegación principal, que liste las tareas de todos los proyectos en los que participa el usuario (las asignadas a él y, según la regla de acceso existente, las visibles de sus proyectos).
- **FR-002**: La vista lista debe agrupar las tareas con reglas explícitas y predecibles: "Alta Prioridad & Urgentes" (prioridad alta u urgente y no terminadas), "En Curso" (estado en progreso o en revisión), "Próximas & Backlog" (restantes no terminadas con proyecto) y "Sin Proyecto Asignado" (tareas independientes, terminadas o no); cada grupo con su contador real.
- **FR-003**: La página debe ofrecer búsqueda por texto en el título, filtros rápidos con contadores reales (Todas, Mis Tareas —asignadas al usuario—, Urgentes —prioridad alta u urgente—, Completadas) y filtros por proyecto y por responsable; los filtros combinan entre sí.
- **FR-004**: Cada fila de tarea debe mostrar como mínimo: botón circular de estado con sus transiciones permitidas, tipo con icono, título, proyecto (o indicador "Sin Proyecto" para tareas independientes), sprint cuando exista, prioridad, responsable (o "Sin asignar") y menú de acciones rápidas (editar, cambiar estado, comentarios si aplica, eliminar según permisos).
- **FR-005**: Debe existir un conmutador de vista lista/kanban persistente entre visitas; el kanban muestra columnas por estado existente (Por Hacer, En Curso, En Revisión, Terminado), contadores por columna y permite arrastrar tarjetas para cambiar de estado; la columna "Bloqueado" del diseño se muestra deshabilitada con "Próximamente".
- **FR-006**: Las tarjetas kanban deben mostrar como mínimo: tipo, título, proyecto (o indicador equivalente), sprint o "Sin Sprint", prioridad, número de comentarios (dato real) y responsable; las terminadas se distinguen visualmente.
- **FR-007**: El diálogo de tarea debe servir para crear y editar con: título obligatorio con foco automático, tipo (valores existentes del dominio), estado inicial (al crear) o estado actual (al editar), prioridad en control segmentado, responsable (el usuario y los miembros del proyecto elegido), proyecto obligatorio con opción "Sin Proyecto" para tareas independientes, sprint dependiente del proyecto elegido y descripción de texto libre compatible con marcado.
- **FR-008**: Los campos del diseño de referencia que no existen en los datos deben presentarse deshabilitados con "Próximamente": puntos de estimación, fecha de vencimiento, barra de herramientas de marcado con vista previa, tipos de tarea adicionales, límites WIP, motivo de bloqueo y panel de previsualización rápida; deben quedar documentados como propiedades futuras.
- **FR-009**: El diálogo debe validar inline (título obligatorio con longitud máxima razonable), deshabilitar el doble envío, ofrecer "Crear otra al guardar", y responder a atajos (guardar con comando+enter, cerrar con escape, `C` para abrir desde la página).
- **FR-010**: La creación desde el botón de columna kanban debe preseleccionar el estado inicial correspondiente a esa columna.
- **FR-011**: El sistema debe soportar tareas independientes (decisión de alcance confirmada): la vinculación de una tarea a su proyecto pasa a ser opcional, con rutas globales de tareas desacopladas del proyecto para crear, actualizar, eliminar y comentar; las tareas sin proyecto se agrupan y filtran bajo "Sin Proyecto" y se crean desde el diálogo con esa opción; el resto de la aplicación (progreso de proyectos, listados de proyecto) debe seguir comportándose igual cuando una tarea no tiene proyecto.
- **FR-012**: La eliminación de tarea debe exigir confirmación, vivir en la zona de peligro del diálogo de edición y respetar los permisos existentes.
- **FR-013**: El pie de página debe resumir contadores reales (tareas mostradas, completadas) y mostrar los atajos disponibles; el atajo `C` y la acción de la paleta de comandos "Crear nueva tarea" deben abrir el diálogo (la acción de paleta debe llevar a Mis Tareas y abrirlo).
- **FR-014**: Toda la experiencia debe respetar el sistema de diseño, operar en modo claro y ser utilizable a 375px sin scroll horizontal de página.
- **FR-015**: Las acciones de escritura deben conservar las reglas de permisos existentes y la paginación/límites razonables cuando el volumen crece (la página debe seguir siendo usable con cientos de tareas, por ejemplo virtualizando o cargando por bloques cuando aplique).

### Key Entities _(include if feature involves data)_

- **Tarea** (existente, ampliada en esta feature): título, descripción (texto con marcado), tipo, prioridad, estado, sprint opcional, responsable opcional, comentarios; su vinculación al proyecto **pasa a ser opcional** para permitir tareas independientes. Estados actuales: por hacer, en curso, en revisión, terminado, backlog. Tipos actuales: otro, feature, bug, test. Prioridades actuales: baja, media, alta, urgente.
- **Proyecto / Sprint / Usuario** (existentes): se usan como dimensiones de filtrado y vinculación.
- **Propiedades FUTURAS documentadas** (sin persistencia en esta feature): clave legible de tarea (prefijo de proyecto + folio), fecha de vencimiento, puntos de estimación, subtareas, adjuntos, estado "bloqueado" con motivo, tipos adicionales (seguridad, mejora, documentación, investigación), colores de proyecto, límites WIP por columna.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: El usuario puede crear una tarea completa (título, tipo, prioridad, responsable, proyecto y sprint) en menos de 45 segundos desde cualquier punto de la aplicación en un máximo de 3 interacciones.
- **SC-002**: El usuario localiza una tarea por título o filtro en menos de 10 segundos.
- **SC-003**: El 100% de los valores mostrados (grupos, contadores, columnas, comentarios) coincide con los datos reales; lo no disponible se marca "Próximamente" y ningún dato simulado se presenta como real.
- **SC-004**: Cambiar el estado de una tarea (círculo, arrastre o edición) tarda menos de 2 segundos en reflejarse y persiste tras recargar, en el 100% de los intentos.
- **SC-005**: La experiencia completa es utilizable a 375px de ancho sin scroll horizontal de página.

## Assumptions

- El listado es **global** ("Mis Tareas"): todas las tareas visibles del usuario en sus proyectos; la pestaña de tareas dentro del detalle de proyecto se mantiene como está en esta feature.
- Las tareas independientes ("Sin Proyecto") **entran en alcance** (decisión Q1: A): requieren una migración ligera (proyecto opcional en tareas) y rutas globales de tareas; sus ubicaciones en la UI (filtro, grupo, chip y opción del modal) quedan completamente funcionales.
- El agrupamiento por defecto oculta las tareas terminadas; la pestaña "Completadas" y la columna "Terminado" las muestran.
- El filtro "Urgentes" cubre prioridad alta y urgente, coherente con el grupo homónimo del listado.
- "Mis Tareas" (pestaña y página) lista las tareas asignadas al usuario; la pestaña "Todas" incluye además las no asignadas de sus proyectos según la regla de acceso existente.
- La descripción acepta marcado en texto libre (capacidad existente); la barra de herramientas y la vista previa son futuras.
- Los permisos de escritura sobre tareas siguen la política existente (líder del proyecto o responsable, según corresponda).
- El arrastre entre columnas kanban reutiliza la capacidad de cambio de estado existente.
