# Feature Specification: Correos transaccionales con Postmark

**Feature Branch**: `027-postmark-transactional-email`

**Created**: 2026-10-16

**Status**: Draft

**Input**: User description: "Vamos a usar postmark para el envio de email trasaccionales, ayudame a implementarlo y si fuese necesario remover otras librerias si es que las hubiera relacionadas al envio de emails. https://postmarkapp.com/developer, vamos a usar las herramientas nativas de Laravel para el envio de emails pero usando postmark como proveedor"

## Contexto

Hoy Eunox (codename Friday) envía dos tipos de correo transaccional:

1. **Invitación a proyecto**: se envía cuando una persona colaboradora invita a un correo sin cuenta. Si el envío falla, la invitación queda pendiente y se avisa: "No pudimos enviar el correo. Puedes reenviar la invitación."
2. **Restablecer contraseña**: se envía cuando alguien pide recuperar su contraseña.

En ningún entorno hay un proveedor real de correo configurado: los correos solo se escriben en el log de la aplicación y la dirección remitente es un valor de ejemplo (`hello@example.com`). Por eso, en producción ningún destinatario recibe estos correos.

El proyecto **no tiene instaladas bibliotecas de terceros para enviar correo**. Las únicas referencias a otros proveedores (Amazon SES, Resend, Mailgun) son entradas de configuración que trae el framework por defecto y que no se usan. Esta feature adopta **Postmark** como único proveedor, sin dejar de usar el sistema de correo nativo del framework.

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Las personas invitadas reciben el correo de invitación (Priority: P1)

Una persona colaboradora invita a alguien sin cuenta a un proyecto. El correo llega de verdad a la bandeja del destinatario, enviado por Postmark desde una dirección real de Eunox.

**Why this priority**: Sin este correo, invitar personas nuevas no funciona en producción. Es el flujo de crecimiento principal del producto.

**Independent Test**: En un entorno con Postmark configurado, invitar a un buzón real y confirmar que el correo llega, con remitente "Eunox <dirección verificada>", y que aparece en la actividad de Postmark.

**Acceptance Scenarios**:

1. **Given** Postmark está configurado como proveedor, **When** se envía una invitación a un correo sin cuenta, **Then** el destinatario la recibe y el envío aparece en la actividad de Postmark.
2. **Given** Postmark rechaza el envío (credencial inválida, remitente no verificado o destinatario bloqueado), **When** se envía una invitación, **Then** la invitación queda pendiente, se muestra el mensaje actual "No pudimos enviar el correo. Puedes reenviar la invitación." y el error queda registrado para el equipo.
3. **Given** una invitación pendiente cuyo envío falló, **When** se pulsa "reenviar" y Postmark ya acepta el envío, **Then** el correo llega.

---

### User Story 2 - Recuperar la contraseña funciona de punta a punta (Priority: P1)

Una persona que olvidó su contraseña la solicita y recibe el enlace en su correo, enviado por Postmark.

**Why this priority**: Sin este correo, una persona que olvida su contraseña (y no usa Google) pierde el acceso a su cuenta.

**Independent Test**: Solicitar el restablecimiento para una cuenta con buzón real, recibir el correo, abrir el enlace y cambiar la contraseña.

**Acceptance Scenarios**:

1. **Given** Postmark está configurado, **When** una persona pide restablecer su contraseña, **Then** recibe el correo con un enlace válido.
2. **Given** Postmark no está disponible, **When** se pide el restablecimiento, **Then** la persona no ve una página de error: se queda en el formulario con un mensaje claro ("No pudimos enviar el correo. Intenta de nuevo en unos minutos.") y el error queda registrado. Hoy, un fallo de envío aquí produce una página de error.

---

### User Story 3 - El equipo distingue y controla los envíos por entorno (Priority: P2)

El equipo puede elegir el proveedor por entorno, sin cambiar código: en producción, Postmark; en desarrollo local, correos capturados localmente (log o buzón local); en tests, nunca se envía nada real. En la actividad de Postmark, cada correo se identifica por su tipo (invitación o restablecimiento).

**Why this priority**: Evita enviar correos reales desde desarrollo o tests, y permite diagnosticar entregas por tipo sin revisar mensajes uno a uno.

**Independent Test**: Correr la suite de tests sin credenciales de Postmark y confirmar que no se hace ningún envío real. En el entorno con Postmark, filtrar su actividad por tipo de correo.

**Acceptance Scenarios**:

1. **Given** el entorno de desarrollo local sin configuración de Postmark, **When** se dispara cualquier correo, **Then** se captura localmente y no sale a internet.
2. **Given** la suite de tests, **When** se ejecuta, **Then** no se intenta ningún envío real y no se necesitan credenciales.
3. **Given** Postmark en producción, **When** se envían una invitación y un restablecimiento, **Then** en Postmark cada uno aparece con una etiqueta que identifica su tipo.

---

### User Story 4 - Configuración de correo limpia y con un solo proveedor (Priority: P3)

Quien mantiene el proyecto ve una configuración de correo que solo menciona a Postmark como proveedor externo (más las opciones locales de desarrollo y prueba), sin entradas de proveedores que no se usan.

**Why this priority**: Reduce la confusión y el riesgo de activar por error un proveedor no contratado. No cambia nada para las personas usuarias.

**Independent Test**: Revisar la configuración de correo y de servicios externos, y las dependencias del proyecto: Postmark es el único proveedor externo y no queda ninguna biblioteca de envío de correo sin uso.

**Acceptance Scenarios**:

1. **Given** la configuración del proyecto, **When** se revisa, **Then** no hay entradas activas para Amazon SES, Resend ni Mailgun, y la plantilla de variables de entorno documenta las de Postmark.
2. **Given** las dependencias del proyecto, **When** se revisan, **Then** solo se agregaron las piezas que exige el framework para hablar con Postmark y no hay otras bibliotecas de correo.

---

### Edge Cases

- **Credencial ausente o inválida en producción**: el envío falla de forma controlada (US1, escenario 2) y no rompe la página. El error se registra con suficiente detalle para diagnosticarlo.
- **Remitente no verificado en Postmark**: Postmark rechaza el envío. Se comporta igual que un fallo de envío y el error registrado indica la causa.
- **Destinatario dado de baja o con rebote previo** (lista de supresión de Postmark): el envío se rechaza para ese destinatario. La invitación queda pendiente con el mensaje de reenvío.
- **Cuenta de Postmark en modo de prueba / aprobación pendiente**: solo puede enviar a direcciones del mismo dominio. Se documenta como prerrequisito operativo.
- **Correos ya registrados en el log antes del cambio**: no se reenvían.
- **Respuestas del destinatario**: si alguien responde al correo, la respuesta llega a la dirección de respuesta configurada (por defecto, la remitente). Procesar respuestas entrantes queda fuera de alcance.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: Todos los correos transaccionales existentes (invitación a proyecto, restablecer contraseña) y los que se agreguen después MUST enviarse a través de Postmark en los entornos donde esté configurado como proveedor.
- **FR-002**: El envío MUST seguir usando el sistema de correo nativo del framework (mensajes y notificaciones existentes), sin llamadas directas al servicio de Postmark desde la lógica de negocio.
- **FR-003**: El proveedor de correo MUST poder elegirse por entorno solo con configuración: Postmark en producción, captura local en desarrollo y captura en memoria en tests.
- **FR-004**: La credencial de Postmark MUST guardarse solo en la configuración del entorno y MUST NOT escribirse en el código ni en el repositorio. La plantilla de variables de entorno MUST documentar la variable con un valor vacío.
- **FR-005**: La dirección remitente MUST ser configurable por entorno y, en producción, MUST ser una dirección o dominio verificado en Postmark. El nombre del remitente sigue siendo el nombre público del producto ("Eunox").
- **FR-006**: Los correos MUST enviarse por el canal (*message stream*) transaccional de Postmark, separado de cualquier canal de envíos masivos.
- **FR-007**: Cada tipo de correo MUST llevar una etiqueta que lo identifique en Postmark (`project-invitation`, `password-reset`).
- **FR-008**: Si Postmark rechaza un envío o no responde, MUST NOT mostrarse una página de error y el error MUST quedar registrado. La invitación conserva su comportamiento actual (queda pendiente con "No pudimos enviar el correo. Puedes reenviar la invitación."). El restablecimiento de contraseña, que hoy produce una página de error, MUST volver al formulario con el mensaje "No pudimos enviar el correo. Intenta de nuevo en unos minutos."
- **FR-009**: El proyecto MUST NOT incluir bibliotecas de envío de correo que no se usen. Solo se agregan las piezas mínimas que el framework requiere para usar Postmark.
- **FR-010**: La configuración MUST eliminar las entradas de proveedores de correo que no se usan (Amazon SES, Resend y la combinación que los mezcla), y conservar Postmark y las opciones locales (log, memoria y SMTP local para un buzón de desarrollo).
- **FR-011**: La documentación del proyecto MUST explicar cómo configurar Postmark: variables requeridas, verificación del remitente o dominio, y cómo probar un envío.

### Key Entities

- **Proveedor de correo**: el servicio que entrega los mensajes. Es Postmark en producción y la captura local o en memoria fuera de ella. Se elige por entorno.
- **Credencial de servidor de Postmark**: token secreto que autoriza los envíos. Es propio de cada entorno y nunca se versiona.
- **Remitente**: dirección y nombre que ve el destinatario ("Eunox <dirección verificada>").
- **Etiqueta de tipo de correo**: identifica en Postmark la categoría de cada envío (`project-invitation`, `password-reset`).

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: El 100% de las invitaciones y solicitudes de restablecimiento enviadas en producción aparecen en la actividad de Postmark.
- **SC-002**: En una prueba en producción con un buzón real, el correo de invitación y el de restablecimiento llegan a la bandeja de entrada (no a spam) en menos de 1 minuto.
- **SC-003**: Un fallo del proveedor no genera ninguna página de error: el 100% de los fallos simulados en invitaciones y restablecimientos muestran un mensaje claro de "no pudimos enviar el correo" y quedan registrados.
- **SC-004**: La suite de tests completa pasa sin credenciales de Postmark y sin realizar envíos reales.
- **SC-005**: 0 bibliotecas o entradas de configuración de proveedores de correo que no se usan quedan en el proyecto.
- **SC-006**: Una persona del equipo puede activar Postmark en un entorno nuevo en menos de 15 minutos siguiendo la documentación.

## Assumptions

- Postmark es el único proveedor; no se configura un proveedor de respaldo automático. Si Postmark falla, aplica el manejo de errores actual (FR-008).
- Los correos se siguen enviando en el momento de la acción, sin cola en segundo plano, para conservar el aviso inmediato de "no pudimos enviar el correo". Pasar a cola queda fuera de alcance.
- La cuenta de Postmark, el servidor, la credencial y la verificación del dominio o remitente los gestiona el equipo fuera de la aplicación. Esta feature solo consume esa configuración.
- Se usa el canal transaccional por defecto de Postmark (`outbound`) salvo que se configure otro.
- No hay correos de verificación de cuenta activos hoy. Si se activan en el futuro, pasarán por Postmark automáticamente gracias a FR-001.
- El seguimiento de aperturas y clics no se activa, por privacidad y porque son correos transaccionales.
- Los webhooks de Postmark (rebotes, quejas, entregas) y el procesamiento de respuestas entrantes quedan fuera de alcance.
- Los mensajes que hoy da el formulario de restablecimiento (incluido el aviso de que un correo no tiene cuenta) no cambian. Evitar que revele qué correos tienen cuenta queda fuera de alcance.
- En desarrollo local se mantiene la captura por log (o un buzón local vía SMTP, como Herd/Mailpit).
