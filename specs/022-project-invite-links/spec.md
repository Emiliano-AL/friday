# Feature Specification: Invitaciones a proyectos por enlace

**Feature Branch**: `022-project-invite-links`

**Created**: 2026-10-10

**Status**: Draft

**Input**: User description: "Actualmente un proyecto puede tener colaboradores y agregar o eliminarlos, pero tenemos pendiente la feature de magic link para compartir a un usuario que no está registrado, por lo que al mandar el link, el usuario recibe el link; si no está registrado en Friday, deberá registrarse y aceptar el proyecto; si ya está registrado únicamente aceptará el proyecto en el cual le invitan a colaborar."

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Invitar por correo a alguien que aún no usa Friday (Priority: P1)

El líder de un proyecto escribe en "Gestionar colaboradores" el correo de una
persona que todavía no tiene cuenta en Friday. En lugar del error actual
("No encontramos ningún usuario registrado con ese correo"), el sistema crea una
invitación pendiente y envía a ese correo un mensaje con un enlace único. Al
abrirlo, la persona ve a qué proyecto la invitan y quién la invita, crea su
cuenta y, al terminar el registro, queda como colaboradora del proyecto tras
aceptar la invitación.

**Why this priority**: Es el hueco que motiva la feature: hoy no hay forma de
sumar a un proyecto a alguien que aún no está registrado.

**Independent Test**: Invitar un correo no registrado, abrir el enlace recibido
en una sesión sin cuenta, registrarse con ese correo, aceptar y comprobar que el
proyecto aparece en su directorio y que el líder la ve en "Miembros actuales".

**Acceptance Scenarios**:

1. **Given** el líder en el modal de colaboradores, **When** agrega un correo sin cuenta en Friday, **Then** el sistema registra una invitación pendiente, envía el correo con el enlace y la muestra en el modal como "Invitación pendiente" (no como error).
2. **Given** una persona sin cuenta que abre un enlace de invitación vigente, **When** llega a Friday, **Then** ve el nombre del proyecto, quién la invita y la opción de crear su cuenta para aceptar.
3. **Given** esa persona completa el registro, **When** termina, **Then** se le presenta la invitación para aceptarla y, al aceptar, queda como colaboradora y entra al proyecto.
4. **Given** la persona se registra mediante el acceso con Google desde el enlace, **When** completa el acceso, **Then** el flujo de aceptación continúa igual que con registro por correo y contraseña.

---

### User Story 2 - Aceptar una invitación teniendo ya cuenta (Priority: P1)

Agregar a un usuario que ya tiene cuenta sigue funcionando como hoy: entra al
proyecto al instante, sin invitación. La aceptación con cuenta existente aplica
cuando la invitación se envió a un correo que entonces no estaba registrado y
la persona ya tiene cuenta con ese correo al abrir el enlace (por ejemplo, se
registró por su cuenta antes de abrirlo). Al abrir el enlace, iniciando sesión
si hace falta, ve la invitación y la acepta o la rechaza.

**Why this priority**: Completa el flujo descrito ("si ya está registrado
únicamente aceptará") y comparte toda la mecánica de enlace y aceptación con la
Historia 1.

**Independent Test**: Invitar un correo no registrado, registrar después una
cuenta con ese correo por la vía normal, abrir el enlace con esa sesión,
aceptar y verificar la membresía; repetir rechazando y verificar que no es
miembro. Por separado, agregar a un usuario ya registrado y verificar que entra
al instante, como hoy.

**Acceptance Scenarios**:

1. **Given** un usuario registrado sin sesión que abre un enlace vigente, **When** inicia sesión, **Then** vuelve directamente a la pantalla de la invitación.
2. **Given** un usuario con sesión frente a la invitación, **When** pulsa "Aceptar", **Then** queda como colaborador y es llevado al proyecto.
3. **Given** un usuario con sesión frente a la invitación, **When** pulsa "Rechazar", **Then** no se vuelve miembro, la invitación deja de estar pendiente y el líder lo ve reflejado.
4. **Given** el líder agrega el correo de un usuario ya registrado, **When** confirma, **Then** el usuario queda como miembro al instante, sin invitación ni aceptación (comportamiento actual).

---

### User Story 3 - Gestionar invitaciones pendientes (Priority: P2)

En el modal de colaboradores, el líder ve las invitaciones pendientes junto a
los miembros actuales, con el correo y cuándo se envió, y puede reenviarlas o
cancelarlas.

**Why this priority**: Sin visibilidad ni control, una invitación mal enviada o
perdida no tiene solución; pero el flujo principal funciona sin ello.

**Independent Test**: Crear una invitación, verla listada como pendiente,
reenviarla (llega un nuevo correo con enlace vigente) y cancelarla (el enlace
deja de funcionar y desaparece de la lista).

**Acceptance Scenarios**:

1. **Given** invitaciones pendientes, **When** el líder abre el modal, **Then** las ve separadas de los miembros, con correo, fecha de envío y estado.
2. **Given** una invitación pendiente, **When** el líder la reenvía, **Then** se envía un nuevo correo y el plazo de vigencia se renueva.
3. **Given** una invitación pendiente, **When** el líder la cancela, **Then** su enlace deja de ser válido y quien lo abra ve que la invitación ya no está disponible.

---

### Edge Cases

- **Enlace vencido**: quien lo abre ve que la invitación expiró y que debe pedir una nueva al líder; no se crea cuenta de miembro.
- **Enlace cancelado o ya usado**: mismo tratamiento que vencido; un enlace no puede aceptarse dos veces.
- **Correo distinto**: si la sesión activa tiene un correo diferente al invitado, la invitación no puede aceptarse; se indica a qué correo se envió y se ofrece cerrar sesión para entrar o registrarse con ese correo. Lo mismo aplica si se completa el acceso con Google usando otra dirección.
- **Ya es miembro**: si la persona ya es miembro del proyecto al aceptar, la invitación se marca como usada sin duplicar la membresía.
- **Invitación duplicada**: invitar de nuevo a un correo con invitación pendiente reenvía la existente en lugar de crear otra.
- **Proyecto no activo**: si el proyecto está archivado o completado, no se pueden crear invitaciones nuevas; las pendientes no pueden aceptarse y muestran el motivo.
- **Proyecto eliminado**: las invitaciones de un proyecto eliminado dejan de ser válidas.
- **Envío de correo fallido**: el líder ve que el envío no se completó y puede reenviar; la invitación sigue pendiente.
- **Usuario no autenticado que intenta aceptar**: siempre debe iniciar sesión o registrarse antes de aceptar.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: El líder del proyecto MUST poder invitar a un correo sin cuenta en Friday desde "Gestionar colaboradores"; el sistema crea una invitación pendiente en lugar de mostrar el error de "correo no encontrado".
- **FR-002**: El sistema MUST enviar al correo invitado un mensaje con el nombre del proyecto, quién invita y un enlace único de invitación.
- **FR-003**: Cada enlace de invitación MUST ser único, no adivinable y válido durante 7 días; tras vencer, ser cancelado o ser usado, MUST dejar de permitir la aceptación.
- **FR-004**: Al abrir un enlace vigente sin sesión, el sistema MUST mostrar el proyecto y quién invita, y ofrecer crear cuenta o iniciar sesión, conservando la invitación durante todo el proceso (incluido el acceso con Google).
- **FR-005**: Tras registrarse o iniciar sesión desde el enlace, el usuario MUST volver a la invitación para aceptarla o rechazarla.
- **FR-006**: Al aceptar, el usuario MUST quedar como colaborador del proyecto con los mismos permisos que un miembro agregado hoy, y ser llevado al proyecto.
- **FR-007**: Al rechazar, el usuario MUST no volverse miembro y la invitación MUST dejar de estar pendiente.
- **FR-008**: El modal de colaboradores MUST listar las invitaciones pendientes (correo, fecha de envío, estado) separadas de los miembros actuales.
- **FR-009**: El líder MUST poder reenviar una invitación pendiente (nuevo correo y vigencia renovada) y cancelarla (enlace inválido de inmediato).
- **FR-010**: Solo el líder del proyecto MUST poder crear, reenviar o cancelar invitaciones, y solo mientras el proyecto esté activo.
- **FR-011**: Invitar a un correo con invitación pendiente MUST reenviar la existente, no crear un duplicado; invitar a quien ya es miembro MUST mostrar el aviso actual de "ya es miembro".
- **FR-012**: Agregar el correo de un usuario ya registrado MUST seguir incorporándolo al instante como miembro, sin invitación (comportamiento actual); las invitaciones solo se crean para correos sin cuenta.
- **FR-015**: Solo una cuenta cuyo correo coincida exactamente con el correo invitado MUST poder aceptar o rechazar la invitación; cualquier otra cuenta ve el motivo y la opción de cambiar de cuenta.
- **FR-016**: Al registrarse desde un enlace de invitación, el formulario MUST mostrar el correo invitado ya escrito.
- **FR-013**: Las invitaciones pendientes con reenvío y cancelación MUST dejar de mostrarse como "Próximamente" en el modal; el "Enlace de invitación rápida" (enlace reutilizable) y los roles por miembro MUST conservar ese estado.
- **FR-014**: La creación de invitaciones MUST tener un límite razonable por líder y periodo para evitar envíos abusivos de correo.

### Key Entities _(include if feature involves data)_

- **Invitación a proyecto**: invitación de un líder a un correo para colaborar en un proyecto. Atributos: proyecto, correo invitado, quién invita, estado (pendiente, aceptada, rechazada, cancelada, vencida), fecha de envío, vencimiento y fecha de respuesta.
- **Proyecto**: sin cambios de estructura; gana una lista de invitaciones pendientes.
- **Usuario / Miembro**: al aceptar, el usuario se incorpora a los miembros del proyecto exactamente como hoy.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Una persona sin cuenta puede pasar de abrir el enlace a estar dentro del proyecto en menos de 3 minutos.
- **SC-002**: Una persona ya registrada puede aceptar una invitación en 2 pasos o menos desde que abre el enlace.
- **SC-003**: El 100% de los enlaces vencidos, cancelados o ya usados se rechazan con un mensaje que explica el motivo.
- **SC-004**: Ningún correo invitado acaba con membresía duplicada en el mismo proyecto.
- **SC-005**: El líder puede ver el estado de cada invitación desde el mismo modal donde gestiona a los miembros, sin pantallas adicionales.

## Assumptions

- Solo el líder (propietario) del proyecto gestiona colaboradores e invitaciones, igual que hoy con los miembros (feature 016).
- Los colaboradores invitados reciben los mismos permisos que los miembros actuales; los roles por miembro (Editor, Administrador, Solo lectura) siguen fuera de alcance y como "Próximamente".
- La invitación se envía por correo desde Friday; el envío de correo ya está configurado en la aplicación.
- El "Enlace de invitación rápida" (enlace reutilizable sin correo específico) queda fuera de alcance y sigue como "Próximamente".
- La vigencia de 7 días es un estándar habitual para invitaciones; puede ajustarse en la planificación.
- El registro existente (correo y contraseña, y acceso con Google) se reutiliza; esta feature no cambia cómo se crea una cuenta, solo conserva la invitación durante el proceso.
- El idioma de la interfaz y del correo es español, como el resto de la aplicación.
