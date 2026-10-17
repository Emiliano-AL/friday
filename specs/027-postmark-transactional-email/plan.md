# Implementation Plan: Correos transaccionales con Postmark

**Branch**: `027-postmark-transactional-email` | **Date**: 2026-10-16 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/027-postmark-transactional-email/spec.md`

## Summary

Activar el transporte `postmark` nativo de Laravel (`MAIL_MAILER=postmark`) instalando sus dos piezas requeridas, `symfony/postmark-mailer` y `symfony/http-client`. La credencial se lee de `services.postmark.key`, que viene de `POSTMARK_API_KEY`, y el canal de `POSTMARK_MESSAGE_STREAM_ID` (por defecto `outbound`). Postmark admite **una sola etiqueta por correo**: la invitación se etiqueta `project-invitation` desde su `Envelope`, y el restablecimiento `password-reset` mediante una subclase mínima de `ResetPassword`. Además, el controlador de restablecimiento deja de mostrar una página de error cuando falla el envío. Se eliminan de la configuración los mailers y servicios sin uso (SES, Resend, round-robin, failover, sendmail). Los mensajes siguen enviándose en el momento, sin cola.

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13.32

**Primary Dependencies**: Laravel Mail/Notifications (nativo). **Nuevas**: `symfony/postmark-mailer` ^8.1 y `symfony/http-client` ^8.1 (misma rama que `symfony/mailer` v8.1.7 instalado).

**Storage**: N/A (sin migraciones)

**Testing**: Pest (feature tests) con `Mail::fake()`/`Notification::fake()`; en `phpunit.xml`, `MAIL_MAILER=array`

**Target Platform**: Monolito Laravel + Inertia (Herd en local, servidor en producción)

**Project Type**: Aplicación web monolítica

**Performance Goals**: El envío síncrono no debe bloquear la petición más de ~5 s ante un Postmark lento (timeout del cliente HTTP).

**Constraints**: Sin llamadas a la API de Postmark desde la lógica de negocio (FR-002). Secretos solo en `.env` (FR-004). Una sola etiqueta por mensaje (limitación de Postmark). Sin envíos reales en tests (SC-004).

**Scale/Scope**: 2 tipos de correo; ~8 archivos tocados; 2 paquetes Composer.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Evaluación | Estado |
| --- | --- | --- |
| I. Monolith-First | Sin API nueva. El correo sale desde el mismo desplegable. | ✅ |
| II. Ecosistema Laravel / dependencias | Se usa el transporte nativo `postmark` de Laravel. **Se agregan 2 dependencias Composer**, que el propio framework exige para ese transporte. **Aprobación explícita**: la pidió la persona usuaria en el pedido de la feature 027 ("vamos a usar postmark… con las herramientas nativas de Laravel"). No se agregan SDKs propietarios. | ✅ (aprobado) |
| III. Test-First con Pest | Tests primero: resolución del transporte, etiquetas, manejo de fallos del restablecimiento y config limpia. | ✅ |
| IV. Tipado estricto | La subclase de notificación y los cambios del controlador llevan tipos de retorno explícitos. | ✅ |
| V. YAGNI | Sin cola, sin proveedor de respaldo, sin webhooks. La subclase de `ResetPassword` tiene 1 método y existe solo para la etiqueta (FR-007). | ✅ |
| Quality gates | `pint --dirty` y la suite completa. | ✅ |

**Resultado**: PASA. **Re-check post-diseño**: PASA. El diseño no agrega capas.

## Project Structure

### Documentation (this feature)

```text
specs/027-postmark-transactional-email/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── mail-delivery.md
└── tasks.md            # /speckit-tasks
```

### Source Code (repository root)

```text
composer.json / composer.lock                 # + symfony/postmark-mailer, symfony/http-client
config/mail.php                               # mailers: smtp, postmark (stream + timeout), log, array
config/services.php                           # se conserva postmark.key; se eliminan resend y ses
.env.example                                  # POSTMARK_API_KEY=, POSTMARK_MESSAGE_STREAM_ID=outbound, guía MAIL_*
phpunit.xml                                   # <env name="MAIL_MAILER" value="array"/> (ya existe)

app/Mail/ProjectInvitationMail.php            # Envelope(tags: ['project-invitation'])
app/Notifications/ResetPasswordNotification.php  # extends ResetPassword; buildMailMessage()->tag('password-reset')
app/Models/User.php                           # sendPasswordResetNotification() usa la subclase
app/Http/Controllers/Auth/PasswordResetLinkController.php  # captura fallo de transporte → error en el campo email

tests/Feature/Mail/
├── PostmarkTransportTest.php                 # el mailer 'postmark' resuelve PostmarkApiTransport con stream
├── MailTagsTest.php                          # etiquetas por tipo
├── PasswordResetDeliveryFailureTest.php      # fallo de envío → sin 500, mensaje claro, report()
└── MailConfigTest.php                        # sin ses/resend/roundrobin; default no es postmark en tests
tests/Feature/Auth/PasswordResetTest.php      # se ajusta a ResetPasswordNotification
tests/Feature/Branding/InvitationMailBrandingTest.php  # se ajusta a ResetPasswordNotification

README.md                                     # sección "Correo transaccional (Postmark)"
```

**Structure Decision**: Monolito existente. Archivos nuevos solo en `app/Notifications/` y `tests/Feature/Mail/`.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| +2 dependencias Composer (Principio II) | El transporte nativo `postmark` de Laravel no funciona sin `symfony/postmark-mailer` y `symfony/http-client` | SMTP de Postmark sin paquetes: pierde el canal por API, el detalle de errores de la API y las etiquetas nativas. La persona usuaria pidió explícitamente Postmark con las herramientas nativas de Laravel |
