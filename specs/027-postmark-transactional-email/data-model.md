# Data Model: Correos transaccionales con Postmark

Sin cambios de base de datos. Las "entidades" son configuración y metadatos de mensaje.

## Configuración de entorno

| Variable | Requerida en | Default | Notas |
| --- | --- | --- | --- |
| `MAIL_MAILER` | todos | `log` | `postmark` en producción, `array` en tests (phpunit.xml) |
| `POSTMARK_API_KEY` | producción | vacío | Server API Token. Secreto, nunca se versiona |
| `POSTMARK_MESSAGE_STREAM_ID` | opcional | `outbound` | Stream transaccional |
| `MAIL_FROM_ADDRESS` | producción | `hello@example.com` | Debe ser un Sender Signature o dominio verificado en Postmark |
| `MAIL_FROM_NAME` | todos | `${APP_NAME}` = Eunox | Ya resuelto en la feature 026 |

**Validación**: Con `MAIL_MAILER=postmark` y `POSTMARK_API_KEY` vacío, el envío falla de forma controlada (FR-008). No se agrega validación al arrancar (YAGNI).

## Tipos de correo

| Tipo | Clase | Etiqueta Postmark | Manejo de fallo |
| --- | --- | --- | --- |
| Invitación a proyecto | `App\Mail\ProjectInvitationMail` | `project-invitation` | `ProjectInvitation::send()` → `false` → flash error de reenvío (existente) |
| Restablecer contraseña | `App\Notifications\ResetPasswordNotification` (extends `ResetPassword`) | `password-reset` | Controlador → error de validación en `email` (nuevo) |

**Regla**: exactamente **una** etiqueta por mensaje, porque es lo que admite Postmark.
