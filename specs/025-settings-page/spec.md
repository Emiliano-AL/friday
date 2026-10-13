# Feature Specification: Página de Configuración (/settings)

**Feature Branch**: `025-settings-page`

**Created**: 2026-10-13

**Status**: Draft

**Input**: User description: "Ayúdame a implementar la página de /settings (Configuración); actualmente en la UI lo marca pendiente."

## Contexto

Hoy la entrada **Ajustes** del pie de la barra lateral y las opciones **Detalles del perfil** y **Ajustes de cuenta** del menú de perfil aparecen deshabilitadas con la etiqueta «Próximamente». La persona usuaria no tiene forma de corregir su nombre, su correo ni su contraseña desde la app. Las cuentas pueden haberse creado con correo y contraseña o con Google (en cuyo caso pueden no tener contraseña).

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Editar mi perfil (Priority: P1)

Como persona usuaria autenticada, abro Configuración desde la barra lateral o el menú de perfil y actualizo mi nombre para que mis compañeros me identifiquen correctamente en proyectos, tareas e invitaciones.

**Why this priority**: Es la razón principal de la página y lo que hoy está bloqueado; el nombre se usa en todo el sistema (responsables, comentarios, importación de tareas por nombre).

**Independent Test**: Iniciar sesión, abrir Configuración, cambiar el nombre, guardar y comprobar que el nuevo nombre aparece en el menú de perfil y en las tareas asignadas.

**Acceptance Scenarios**:

1. **Given** una persona autenticada, **When** pulsa «Ajustes» en la barra lateral o «Detalles del perfil»/«Ajustes de cuenta» en el menú de perfil, **Then** llega a la página Configuración y la entrada ya no muestra «Próximamente».
2. **Given** la página Configuración, **When** cambia su nombre y guarda, **Then** ve una confirmación y el nombre actualizado se refleja de inmediato en toda la app.
3. **Given** la página Configuración, **When** intenta guardar un nombre vacío, **Then** ve un mensaje de error junto al campo y no se guarda nada.
4. **Given** la página Configuración, **When** la consulta, **Then** ve su correo como dato de solo lectura, sin opción de editarlo.

---

### User Story 2 - Cambiar o definir mi contraseña (Priority: P2)

Como persona usuaria, cambio mi contraseña desde Configuración; si entré con Google y no tengo contraseña, puedo definir una para poder entrar también con correo y contraseña.

**Why this priority**: Es la segunda necesidad básica de seguridad de cuenta; sin ella, quien olvida su contraseña depende del flujo de recuperación por correo.

**Independent Test**: Con una cuenta con contraseña, cambiarla indicando la actual; cerrar sesión e iniciar con la nueva. Con una cuenta de Google sin contraseña, definir una y entrar con correo y contraseña.

**Acceptance Scenarios**:

1. **Given** una cuenta con contraseña, **When** introduce la contraseña actual correcta y una nueva confirmada, **Then** la contraseña se actualiza y ve una confirmación.
2. **Given** una cuenta con contraseña, **When** introduce una contraseña actual incorrecta, **Then** ve un error y la contraseña no cambia.
3. **Given** cualquier cuenta, **When** la nueva contraseña y su confirmación no coinciden o no cumplen el mínimo de seguridad, **Then** ve un error junto al campo.
4. **Given** una cuenta creada con Google sin contraseña, **When** abre la sección de contraseña, **Then** se le ofrece «Definir contraseña» sin pedir la actual.

---

### User Story 3 - Eliminar mi cuenta (Priority: P3)

Como persona usuaria, puedo eliminar mi cuenta de forma definitiva desde una «zona de peligro» que pide confirmación explícita.

**Why this priority**: Es una expectativa estándar de una página de cuenta, pero no bloquea el uso diario.

**Independent Test**: Con una cuenta de prueba, eliminarla confirmando; comprobar que la sesión se cierra y que ya no se puede entrar con esas credenciales.

**Acceptance Scenarios**:

1. **Given** la zona de peligro, **When** pulsa «Eliminar cuenta», **Then** se le pide confirmación (contraseña, o escribir su correo si no tiene contraseña) antes de proceder.
2. **Given** la confirmación correcta, **When** confirma, **Then** la cuenta se elimina, la sesión se cierra y llega a la pantalla de inicio de sesión.
3. **Given** que es dueña de uno o más proyectos, **When** abre la zona de peligro, **Then** la eliminación está bloqueada y ve la lista de esos proyectos con un enlace a cada uno, indicando que debe eliminarlos antes de poder eliminar su cuenta.
4. **Given** que es dueña de proyectos, **When** intenta eliminar la cuenta igualmente (por ejemplo, enviando la solicitud directamente), **Then** el sistema la rechaza y no elimina nada.

---

### Edge Cases

- Alguien escribe directamente `/settings` sin sesión: se le lleva al inicio de sesión.
- Guardar el perfil sin cambios: se confirma sin errores y no altera nada.
- Espacios al inicio o al final del nombre: se recortan antes de validar.
- Una solicitud que intente cambiar el correo se ignora: el correo no cambia.
- Si la persona elimina su último proyecto propio, la zona de peligro se desbloquea sin recargar manualmente.
- Varios intentos fallidos de contraseña actual: se limitan los intentos para evitar fuerza bruta.
- Tras cambiar la contraseña, las demás sesiones abiertas de la misma cuenta se cierran; la sesión actual se mantiene.
- Pantalla de teléfono: las secciones se apilan y siguen siendo usables sin desplazamiento horizontal.

## Clarifications

### Session 2026-10-13

- Q: ¿Cómo se gestiona el cambio de correo? → A: No es editable; solo el nombre (C).
- Q: ¿Qué pasa con los proyectos propios al eliminar la cuenta? → A: Se bloquea la eliminación hasta que la persona los elimine (B).
- Q: ¿La página incluye preferencias? → A: Solo cuenta y seguridad (A).

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: El sistema MUST ofrecer una página Configuración en `/settings`, accesible solo para personas autenticadas.
- **FR-002**: La entrada «Ajustes» de la barra lateral y las opciones «Detalles del perfil» y «Ajustes de cuenta» del menú de perfil MUST llevar a Configuración y dejar de mostrarse como «Próximamente»; la entrada de la barra lateral MUST marcarse como activa en esa página.
- **FR-003**: La página MUST mostrar el nombre, el correo y el método de acceso de la cuenta (correo y contraseña, Google, o ambos).
- **FR-004**: La persona MUST poder actualizar su nombre (obligatorio, máximo 255 caracteres).
- **FR-005**: El correo MUST mostrarse como solo lectura; el sistema MUST NOT permitir cambiarlo desde Configuración.
- **FR-006**: Una cuenta con contraseña MUST poder cambiarla indicando la contraseña actual, una nueva y su confirmación; la nueva MUST cumplir la política de contraseñas vigente en el registro.
- **FR-007**: Una cuenta sin contraseña (creada con Google) MUST poder definir una contraseña sin indicar una actual.
- **FR-008**: Tras cambiar o definir la contraseña, el sistema MUST cerrar las demás sesiones de esa cuenta y mantener la actual.
- **FR-009**: Los intentos de verificación de la contraseña actual MUST estar limitados por tiempo para evitar fuerza bruta.
- **FR-010**: La persona MUST poder eliminar su cuenta tras una confirmación explícita (contraseña actual, o su correo si no tiene contraseña); al eliminarla se cierra la sesión.
- **FR-013**: El sistema MUST impedir la eliminación de la cuenta mientras la persona sea dueña de al menos un proyecto (en cualquier estado), y MUST listar esos proyectos con un enlace a cada uno.
- **FR-011**: Cada sección (perfil, contraseña, eliminar cuenta) MUST guardarse de forma independiente y mostrar errores junto a cada campo y una confirmación al éxito.
- **FR-012**: Los cambios de nombre MUST reflejarse en toda la app sin necesidad de recargar manualmente.

### Key Entities

- **Usuario**: nombre, correo, contraseña (opcional), avatar (opcional), proveedor de acceso externo (opcional). No se agregan entidades nuevas.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Una persona puede cambiar su nombre en menos de 30 segundos desde cualquier pantalla (2 clics para llegar a Configuración).
- **SC-002**: El 100 % de las entradas de navegación que hoy muestran «Próximamente» para ajustes o perfil quedan funcionales.
- **SC-003**: Tras cambiar la contraseña, la nueva funciona en el siguiente inicio de sesión y la anterior es rechazada en el 100 % de los casos.
- **SC-004**: Ningún cambio inválido (nombre vacío, contraseña actual incorrecta, eliminación con proyectos propios) se guarda.

## Assumptions

- La página sigue el estilo y el layout autenticado del resto de la app (barra lateral, encabezado de página).
- El avatar sigue viniendo de Google cuando existe; subir una foto propia queda fuera de alcance.
- La página cubre solo cuenta y seguridad (perfil, contraseña, eliminar cuenta); las preferencias de interfaz (tema, vista por defecto, notificaciones) quedan fuera de alcance. El idioma de la app sigue siendo español.
- El cambio de correo queda fuera de alcance; quien lo necesite debe contactar a un administrador.
- La transferencia de propiedad de proyectos no existe y queda fuera de alcance; para eliminar la cuenta, la persona elimina antes sus proyectos propios.
- La política de contraseñas es la misma que se aplica en el registro.
- Vincular o desvincular la cuenta de Google desde Configuración queda fuera de alcance.
- La app hoy no verifica correos al registrarse.
