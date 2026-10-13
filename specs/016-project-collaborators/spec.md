# Feature Specification: Gestión de colaboradores del proyecto

**Feature Branch**: `016-project-collaborators`

**Created**: 2026-10-05

**Status**: Draft

**Input**: User description: "vamos a implmentar la gestion de colaboradores. Un proyecto puede tener varios colaboradores y pueden asignarse las tareas que pertenezcan a ese proyecto. Actualmente en /projects/{id} tenemos la opcion de agregar colaborador, vamos a remover ese inline form y vamos agregar un modal. usando el MCP de stitch en proyecto 3835093330700486424, usa el item "Gestion collaboradores" para el design del mismo, si la funcion no existe la marcas deshabilitada."

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Modal de gestión de colaboradores (Priority: P1)

Un propietario de proyecto, desde la página de detalle del proyecto, abre el modal "Gestionar colaboradores" (reemplazando el formulario inline actual) y puede ver todos los miembros del proyecto — el propietario con etiqueta "Propietario" y cada colaborador con avatar (o iniciales), nombre y correo —, agregar un colaborador existente introduciendo su correo electrónico, y eliminar un colaborador con confirmación. Cada cambio se refleja en la lista del modal y en la sección de equipo de la página sin necesidad de recargar manualmente. El formulario inline que hoy existe en la página de detalle se elimina por completo.

**Why this priority**: Es el corazón de la funcionalidad: concentra en un solo lugar todo lo que hoy se hace con un formulario inline rudimentario, y es la base sobre la que se apoyan las demás historias (estados deshabilitados y asignación de tareas).

**Independent Test**: Puede probarse de forma independiente: abrir el detalle de un proyecto, pulsar "Gestionar colaboradores", verificar que la lista muestra propietario y colaboradores reales; agregar un colaborador existente por correo y verlo aparecer; eliminarlo con confirmación y verlo desaparecer; y verificar que el formulario inline ya no existe en la página.

**Acceptance Scenarios**:

1. **Given** un propietario en la página de detalle de su proyecto, **When** pulsa "Gestionar colaboradores", **Then** se abre el modal con el título "Gestionar colaboradores", el nombre del proyecto, y la lista de miembros actuales: el propietario con etiqueta "Propietario" y cada colaborador con avatar o iniciales, nombre y correo.
2. **Given** el modal abierto, **When** el propietario introduce el correo de un usuario existente que no es miembro y confirma, **Then** el usuario aparece en la lista de miembros sin recargar la página, y la sección de equipo de la página refleja el cambio.
3. **Given** el modal abierto, **When** el propietario introduce el correo de alguien que ya es miembro, **Then** ve un mensaje claro de que ese usuario ya es miembro y la lista no cambia.
4. **Given** el modal abierto, **When** el propietario introduce un correo que no pertenece a ningún usuario registrado, **Then** ve un mensaje claro indicando que el correo no se encontró.
5. **Given** el modal abierto, **When** el propietario elimina a un colaborador tras la confirmación, **Then** el colaborador desaparece de la lista del modal y de la sección de equipo de la página, y el propietario nunca puede eliminarse a sí mismo.
6. **Given** cualquier usuario, **When** visita la página de detalle del proyecto, **Then** ya no existe el formulario inline de agregar colaborador que había anteriormente.

---

### User Story 2 - Funciones no disponibles deshabilitadas honestamente (Priority: P2)

El modal de diseño incluye capacidades que el sistema actual no tiene: roles y permisos por miembro (Editor / Administrador / Solo Lectura), enlace de invitación rápida ("Copiar enlace"), invitaciones pendientes con reenvío/cancelación para correos no registrados, y notificaciones automáticas por correo. Cada una de estas funciones se muestra en el modal en estado deshabilitado con el indicador "Próximamente": se ven, pero no ejecutan ninguna acción ni navegan a ninguna parte. El texto informativo del modal solo afirma lo que el sistema realmente hace.

**Why this priority**: Mantiene la integridad de la promesa de producto: el usuario ve hacia dónde irá la funcionalidad sin que nada "falso" se ejecute, cumpliendo el principio de estados honestos del proyecto. Es prioridad secundaria porque el valor operativo ya existe con la historia P1.

**Independent Test**: Puede probarse de forma independiente: abrir el modal y verificar que el selector de roles, el botón de copiar enlace y las acciones de invitación pendiente aparecen deshabilitados con "Próximamente", y que interactuar con ellos no produce acción, cambio de estado ni navegación.

**Acceptance Scenarios**:

1. **Given** el modal abierto, **When** el usuario observa la fila de un colaborador, **Then** el selector de rol aparece deshabilitado (mostrando el rol actual del sistema: todos los colaboradores son "Editor" por defecto) con el indicador "Próximamente".
2. **Given** el modal abierto, **When** el usuario pulsa sobre el selector de rol, el botón "Copiar enlace" o las acciones de reenviar/cancelar invitación, **Then** no ocurre nada: ningún cambio, llamada o navegación.
3. **Given** el modal abierto, **When** el usuario intenta agregar un correo no registrado, **Then** el sistema muestra el mensaje de "correo no encontrado" existente (no una invitación pendiente), y cualquier elemento de invitación pendiente del diseño permanece deshabilitado.
4. **Given** el modal abierto, **When** el usuario lee el pie del modal, **Then** el texto solo describe funciones que realmente existen (por ejemplo, que los colaboradores pueden recibir tareas asignadas), sin prometer notificaciones por correo ni menciones que el sistema no envía.

---

### User Story 3 - Asignación de tareas limitada a colaboradores del proyecto (Priority: P3)

Las tareas de un proyecto solo pueden asignarse a colaboradores de ese proyecto (propietario incluido): al crear o editar una tarea dentro de un proyecto, el selector de responsable ofrece únicamente al propietario y a los colaboradores de ese proyecto, y el sistema impide (también en el servidor) asignar la tarea a alguien que no pertenezca al proyecto. Las tareas sin proyecto (independientes) siguen pudiendo asignarse al usuario actual como hoy.

**Why this priority**: Cierra la coherencia del dominio ("un proyecto puede tener varios colaboradores y pueden asignárseles las tareas que pertenecen a ese proyecto") y evita datos inconsistentes; es prioridad menor porque la interfaz ya ofrece en su mayoría solo miembros del proyecto y es principalmente un refuerzo de validación.

**Independent Test**: Puede probarse de forma independiente: abrir el formulario de tarea de un proyecto y verificar que el selector de responsable contiene exactamente al propietario y a los colaboradores de ese proyecto (ni más, ni menos); e intentar asignar a un externo por la vía que sea y verificar que el sistema lo rechaza.

**Acceptance Scenarios**:

1. **Given** un usuario con permisos crea o edita una tarea de un proyecto, **When** abre el selector de responsable, **Then** las opciones son exactamente el propietario y los colaboradores actuales de ese proyecto.
2. **Given** una tarea de un proyecto, **When** se intenta asignar a un usuario que no es colaborador del proyecto (por cualquier medio), **Then** el sistema rechaza la asignación y la tarea conserva su estado anterior.
3. **Given** una tarea sin proyecto, **When** se crea o edita, **Then** puede asignarse al usuario actual como responsable, igual que hoy.
4. **Given** un colaborador eliminado del proyecto que tenía tareas asignadas en él, **When** se elimina, **Then** sus tareas quedan sin responsable (sin romperse) y el modal de gestión lo comunica al confirmar la eliminación.

---

### Edge Cases

- **Proyecto sin colaboradores** (solo propietario): el modal lista únicamente al propietario y su estado vacío de "miembros" es honesto.
- **Agregar al propietario como colaborador**: el sistema lo trata como miembro ya existente (mensaje de duplicado), no crea una segunda entrada.
- **Eliminar a un colaborador con tareas asignadas**: las tareas quedan sin responsable; el modal lo advierte antes de confirmar.
- **Correo con mayúsculas/espacios o de usuario ya registrado pero con variación**: se normaliza y detecta como duplicado si aplica.
- **Correos o nombres muy largos**: se truncan con elipsis sin romper la fila del miembro.
- **Sin conexión o error del servidor al agregar/eliminar**: el modal muestra un mensaje de error claro y permanece abierto con sus datos.
- **Pantallas pequeñas**: el modal ocupa el ancho disponible y su contenido es desplazable sin scroll horizontal.
- **Usuario sin permiso (no propietario)**: no ve la opción de gestionar colaboradores (comportamiento actual del sistema, sin cambios).
- **Miembro eliminado mientras el modal está abierto en otra sesión**: la lista se actualiza al reabrir/reintentar, sin errores visibles.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: La página de detalle del proyecto MUST abrir el modal "Gestionar colaboradores" como forma única de gestionar miembros; el formulario inline actual MUST eliminarse por completo.
- **FR-002**: El modal MUST listar todos los miembros del proyecto: el propietario con la etiqueta "Propietario" (sin opción de eliminar) y cada colaborador con avatar (o iniciales como sustituto), nombre y correo electrónico.
- **FR-003**: El modal MUST permitir agregar un colaborador existente introduciendo su correo electrónico, actualizando la lista y la sección de equipo de la página sin recarga manual.
- **FR-004**: Al agregar por correo, el sistema MUST validar: correo con formato válido, usuario registrado existente y que no sea ya miembro; cada caso de error MUST mostrar un mensaje claro en español sin cerrar el modal.
- **FR-005**: El modal MUST permitir eliminar colaboradores (nunca al propietario) mediante una acción con confirmación, y MUST advertir cuando el colaborador tenga tareas asignadas en el proyecto (quedarán sin responsable).
- **FR-006**: Las acciones de agregar y eliminar MUST conservar las reglas de permisos actuales del sistema (gestión restringida al propietario del proyecto).
- **FR-007**: Las funciones del diseño que no existen en el sistema MUST renderizarse deshabilitadas con el indicador "Próximamente" y no ejecutar ninguna acción ni navegación: selector de roles/permisos por miembro, enlace de invitación rápida ("Copiar enlace"), invitaciones pendientes con reenvío/cancelación, y cualquier texto que prometa notificaciones por correo o menciones no implementadas.
- **FR-008**: Al crear o editar una tarea de un proyecto, el selector de responsable MUST ofrecer únicamente al propietario y a los colaboradores de ese proyecto, y el sistema MUST rechazar (también del lado del servidor) cualquier asignación a un usuario que no sea colaborador de ese proyecto.
- **FR-009**: Los textos informativos del modal MUST describir únicamente funciones que el sistema realmente cumple (sin prometer envío de correos, menciones ni permisos diferenciados).

### Key Entities _(include if feature involves data)_

- **Proyecto**: el espacio de trabajo cuyos colaboradores se gestionan; tiene exactamente un propietario y cero o más colaboradores.
- **Usuario**: persona registrada; puede ser propietario de proyectos, colaborador de proyectos (mediante la relación de membresía) y responsable de tareas.
- **Membresía**: relación entre un colaborador y un proyecto; en el sistema actual no lleva rol ni permisos asociados (todos los colaboradores tienen las mismas capacidades).
- **Tarea**: unidad de trabajo que pertenece a un proyecto (o es independiente); su responsable debe ser colaborador del proyecto cuando la tarea pertenece a uno.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Agregar, consultar o eliminar un colaborador se completa desde el modal en un máximo de 3 interacciones por acción (abrir modal incluido para consultar).
- **SC-002**: El 100% de las funciones no implementadas del diseño aparecen deshabilitadas con "Próximamente" y el 0% de ellas ejecuta acciones ante interacciones del usuario.
- **SC-003**: El 100% de las tareas de proyectos quedan asignadas (o sin asignar) de forma coherente: ninguna tarea de proyecto tiene como responsable a un no-colaborador del proyecto.
- **SC-004**: Los miembros mostrados en el modal coinciden al 100% con los miembros reales del proyecto, incluido el propietario.
- **SC-005**: El tiempo percibido de apertura del modal y de cada acción (agregar/eliminar) es menor de 1 segundo en condiciones normales de escritorio.

## Assumptions

- **Fuente visual**: la referencia de diseño es la pantalla "Friday — Modal Gestión de Colaboradores & Equipo del Proyecto" del proyecto Stitch `3835093330700486424`, en modo claro e idioma español, consistente con el design system del workspace.
- **Membresía actual**: propietario (único) + colaboradores mediante la relación de membresía existente; no existe columna de rol ni permisos diferenciados, por lo que todos los colaboradores se muestran con el rol genérico del sistema.
- **Invitación por correo**: agrega únicamente usuarios ya registrados en la plataforma (comportamiento actual); el flujo de invitación pendiente para correos no registrados no existe y se muestra deshabilitado.
- **Funciones deshabilitadas** (instrucción explícita): roles/permisos por miembro, enlace de invitación rápida, invitaciones pendientes con reenvío/cancelación y notificaciones por correo.
- **Permisos sin cambios**: la gestión de colaboradores sigue restringida al propietario del proyecto, como en el sistema actual.
- **Tareas de colaboradores eliminados**: quedan sin responsable al eliminar al colaborador (comportamiento actual de la relación).
- **Alcance**: página de detalle del proyecto y formulario de tareas existentes; no se crean nuevas pantallas ni capacidades de invitación por correo electrónico.
