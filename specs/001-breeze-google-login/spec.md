# Feature Specification: Autenticación con correo/contraseña + Google

**Feature Branch**: `001-breeze-google-login`

**Created**: 2026-09-19

**Status**: Draft

**Input**: User description: (comando invocado sin texto; feature inferida de la intención diferida registrada en la enmienda constitucional v1.1.0 — habilitar login tradicional con correo y contraseña junto a Socialite con Google como único proveedor)

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Registro e inicio de sesión con correo y contraseña (Priority: P1)

Un usuario nuevo crea su cuenta con nombre, correo y contraseña desde la página de acceso y, en visitas posteriores, entra escribiendo su correo y contraseña.

**Why this priority**: Es el flujo de acceso base; sin él, cualquier usuario sin cuenta de Google quedaría fuera de la plataforma.

**Independent Test**: Se prueba de forma aislada registrando una cuenta nueva con correo/contraseña y accediendo con ella: si solo esto existiera, ya sería un acceso funcional completo.

**Acceptance Scenarios**:

1. **Given** un visitante no registrado en la página de acceso, **When** completa el registro con nombre, correo válido, contraseña y confirmación de contraseña, **Then** se crea su cuenta y accede automáticamente a la aplicación.
2. **Given** un usuario registrado que cerró sesión, **When** ingresa su correo y contraseña correctos, **Then** accede a la aplicación.
3. **Given** un usuario que ingresa una contraseña incorrecta, **When** intenta entrar, **Then** ve un mensaje de error genérico y permanece fuera, sin que el sistema revele si el correo está registrado.

---

### User Story 2 - Inicio de sesión con Google (Priority: P1)

Un usuario con cuenta de Google pulsa "Continuar con Google" y accede sin crear ni recordar una contraseña.

**Why this priority**: Es el segundo flujo de acceso fijado por gobernanza y la vía de menor fricción; comparte prioridad máxima con el acceso tradicional porque ambos habilitan el mismo valor: entrar a la plataforma.

**Independent Test**: Se prueba de forma aislada entrando con una cuenta de Google no registrada previamente (crea cuenta) y repitiendo el acceso (entra a la misma cuenta): si solo esto existiera, ya sería un acceso funcional completo.

**Acceptance Scenarios**:

1. **Given** un visitante no registrado en la página de acceso, **When** pulsa "Continuar con Google" y autoriza el acceso, **Then** se crea su cuenta con los datos de su perfil de Google y accede a la aplicación.
2. **Given** un usuario que ya entró antes con Google, **When** vuelve a entrar con Google, **Then** accede a la misma cuenta, sin duplicados.
3. **Given** un usuario que ya tiene una cuenta local con ese mismo correo, **When** entra con Google, **Then** ambos métodos quedan vinculados a la misma cuenta y accede a ella.

---

### User Story 3 - Recuperación de contraseña olvidada (Priority: P2)

Un usuario local que olvidó su contraseña solicita un enlace de restablecimiento por correo y define una contraseña nueva.

**Why this priority**: Sin recuperación, olvidar la contraseña equivale a perder la cuenta, y el soporte manual tendría que intervenir en un caso tan común como este.

**Independent Test**: Se prueba de forma aislada solicitando el restablecimiento desde la página de acceso y entrando después con la contraseña nueva.

**Acceptance Scenarios**:

1. **Given** un usuario registrado que olvidó su contraseña, **When** solicita restablecerla indicando su correo, **Then** recibe un correo con un enlace de un solo uso que le permite definir una contraseña nueva y acceder con ella.
2. **Given** un enlace de restablecimiento usado, expirado o inválido, **When** intenta utilizarlo, **Then** ve un mensaje claro indicando que solicite un nuevo enlace, y su contraseña actual no cambia.

---

### User Story 4 - Cierre de sesión y control de acceso (Priority: P2)

Un usuario autenticado cierra sesión; los visitantes no autenticados no pueden ver páginas internas de la plataforma.

**Why this priority**: Protege la información de los proyectos del equipo y completa el ciclo de vida de la sesión; sin él, "cerrar sesión" no tendría efecto real.

**Independent Test**: Se prueba de forma aislada cerrando sesión y comprobando que las páginas internas exigen volver a entrar.

**Acceptance Scenarios**:

1. **Given** un usuario autenticado, **When** cierra sesión, **Then** su sesión termina y es redirigido a la página de acceso.
2. **Given** un visitante no autenticado, **When** intenta abrir una página interna, **Then** es redirigido a la página de acceso y, tras entrar, vuelve a la página que había solicitado.
3. **Given** un usuario ya autenticado, **When** abre la página de acceso, **Then** es redirigido directamente a la aplicación.

---

### Edge Cases

- **Correo ya registrado**: al registrarse con un correo existente (cuenta local o vinculada a Google), el sistema lo indica claramente y no crea una cuenta duplicada.
- **Consentimiento de Google cancelado**: si el usuario cancela la autorización en Google, regresa a la página de acceso sin cuenta creada y sin mensajes de error confusos.
- **Correo compartido entre métodos**: un correo verificado por Google que coincide con una cuenta local vincula ambos métodos a una sola cuenta; nunca se crean dos cuentas con el mismo correo.
- **Fuerza bruta**: tras varios intentos fallidos seguidos de contraseña, el acceso por contraseña se bloquea temporalmente de forma progresiva.
- **Sesión expirada**: un usuario con sesión vencida que abre una página interna es enviado a acceso y regresa a su destino original después de entrar.
- **Contraseña débil o sin confirmar**: el registro rechaza contraseñas menores a 8 caracteres o con confirmación que no coincide, indicando el motivo.
- **Proveedores no habilitados**: la interfaz no ofrece ningún proveedor de identidad distinto de Google (p. ej., GitHub).

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: La página de acceso MUST ofrecer en una misma pantalla el formulario de correo/contraseña y la opción "Continuar con Google".
- **FR-002**: El registro MUST exigir nombre, correo con formato válido y único, contraseña de mínimo 8 caracteres y confirmación de contraseña que coincida.
- **FR-003**: El sistema MUST almacenar las contraseñas únicamente mediante hash criptográfico fuerte; la contraseña en texto plano nunca se persiste ni se registra.
- **FR-004**: El inicio de sesión con correo/contraseña MUST validar las credenciales y, ante un fallo, mostrar un mensaje genérico que no revele si el correo está registrado.
- **FR-005**: El sistema MUST implementar el flujo de identidad federada con Google como único proveedor social, creando la cuenta automáticamente en el primer acceso con los datos disponibles del perfil (nombre, correo, avatar e identificador del proveedor).
- **FR-006**: Cuando el correo verificado por Google coincida con una cuenta local existente, el sistema MUST vincular ambos métodos a la misma cuenta e iniciar sesión en ella, sin crear duplicados.
- **FR-007**: El sistema MUST limitar los intentos fallidos de contraseña con un bloqueo temporal progresivo (por correo y por origen de la solicitud) para impedir fuerza bruta.
- **FR-008**: El sistema MUST permitir restablecer la contraseña mediante un enlace de un solo uso enviado al correo registrado, que queda inválido tras su uso o tras expirar.
- **FR-009**: El cierre de sesión MUST terminar la sesión activa y redirigir a la página de acceso.
- **FR-010**: Las páginas internas de la plataforma MUST exigir sesión autenticada; los visitantes no autenticados son redirigidos a acceso y, tras autenticarse, regresan a la página que habían solicitado.
- **FR-011**: Un usuario con sesión activa que visite la página de acceso MUST ser redirigido a la aplicación.
- **FR-012**: El sistema MUST NOT ofrecer proveedores de identidad distintos de Google ni métodos de registro adicionales.

### Key Entities _(include if feature involves data)_

- **Usuario**: persona con acceso a la plataforma. Atributos clave: nombre, correo (único), avatar (opcional), hash de contraseña (solo para cuentas con acceso local), proveedor de identidad federada (opcional, único valor permitido: Google), identificador del proveedor (opcional), marca de correo verificado y fechas de creación/actualización.
- **Métodos de acceso de un Usuario**: una misma cuenta puede combinar acceso local (correo/contraseña) y acceso federado (Google) tras la vinculación; el correo actúa como clave única global que impide cuentas duplicadas.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Un usuario nuevo completa el registro con correo y contraseña en menos de 2 minutos en su primer intento.
- **SC-002**: Un usuario con cuenta de Google accede a la plataforma en un flujo de 2 interacciones (botón + autorización) que toma menos de 30 segundos.
- **SC-003**: El 100% de los intentos de acceso con credenciales válidas (correo/contraseña o Google) finalizan con acceso exitoso, y el 100% de los intentos con credenciales inválidas son rechazados.
- **SC-004**: El 100% de las contraseñas almacenadas están protegidas con hash criptográfico fuerte (0 contraseñas en texto plano, verificable por auditoría).
- **SC-005**: Tras superar el umbral de intentos fallidos configurado, el acceso por contraseña queda bloqueado temporalmente (verificable con una prueba de fuerza bruta simulada).
- **SC-006**: Se crean 0 cuentas duplicadas para un mismo correo, independientemente del método o combinación de métodos usados para entrar.
- **SC-007**: El 100% de los accesos de visitantes no autenticados a páginas internas terminan en redirección a la página de acceso.

## Assumptions

- El comando se invocó sin descripción textual; la feature corresponde a la intención diferida registrada al amendar la constitución a v1.1.0: habilitar el login tradicional con correo y contraseña junto a Socialite con Google como único proveedor.
- La herramienta de implementación del acceso local y la limitación del proveedor social están fijadas por gobernanza (constitución v1.1.0, sección "Stack Tecnológico Fijo"); esta spec describe el comportamiento sin dictar detalles de implementación.
- Google es el único proveedor de identidad federada; GitHub y cualquier otro proveedor quedan fuera de alcance.
- Las cuentas locales no requieren verificación de correo en el MVP; el correo solo se considera verificado de origen cuando proviene de Google.
- No existen roles ni permisos diferenciados en el MVP: todo usuario autenticado comparte el mismo nivel de acceso a la plataforma.
- La opción de mantener la sesión activa ("recuérdame") sigue el comportamiento estándar de la herramienta base.
- El restablecimiento de contraseña depende del servicio de correo transaccional ya disponible en el proyecto.
- El idioma de la interfaz de acceso es el español, coherente con el resto de la plataforma.
