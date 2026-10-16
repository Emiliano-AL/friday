# Research: Correos transaccionales con Postmark

## R1. Transporte: API nativa de Laravel vs. SMTP

- **Decision**: Transporte `postmark` de Laravel (API HTTP vía Symfony Mailer). Requiere `composer require symfony/postmark-mailer symfony/http-client`.
- **Rationale**: Es el camino documentado por Laravel. `MailManager::createPostmarkTransport()` ya lee `services.postmark.key` (o `token`), `message_stream_id` y `client` (opciones de HTTP). La API devuelve errores precisos (remitente no verificado, destinatario suprimido) que quedan en el log con `report()`. Las etiquetas se mapean nativamente.
- **Alternatives considered**: (a) SMTP de Postmark con el mailer `smtp`: no requiere paquetes, pero es más lento, el error de entrega es menos claro y el canal se elige por header. (b) SDK `wildbit/postmark-php`: contradice FR-002 y el Principio II.

## R2. Versiones de paquetes

- **Decision**: `symfony/postmark-mailer:^8.1` y `symfony/http-client:^8.1`.
- **Rationale**: Deben coincidir con `symfony/mailer` v8.1.7, que ya está instalado por Laravel 13.

## R3. Credencial y canal

- **Decision**: `config/services.php` → `'postmark' => ['key' => env('POSTMARK_API_KEY')]` (ya existe). En `config/mail.php`, el mailer `postmark` se configura así:
  - `'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID', 'outbound')`
  - `'client' => ['timeout' => 5]`
- **Rationale**: Un stream explícito separa lo transaccional de envíos masivos futuros (FR-006). El timeout de 5 s acota la espera, porque el envío es síncrono.
- **Nota operativa**: Postmark ofrece el token de prueba `POSTMARK_API_TEST`, que acepta el envío sin entregarlo. Sirve para validar staging sin mandar correos reales.

## R4. Etiquetas por tipo (FR-007)

- **Decision**:
  - Invitación: `new Envelope(subject: …, tags: ['project-invitation'])`.
  - Restablecimiento: `App\Notifications\ResetPasswordNotification extends Illuminate\Auth\Notifications\ResetPassword` sobrescribe `protected function buildMailMessage($url): MailMessage` como `parent::buildMailMessage($url)->tag('password-reset')`. `User::sendPasswordResetNotification(string $token): void` hace `$this->notify(new ResetPasswordNotification($token))`.
- **Rationale**: Postmark admite **una sola** etiqueta por mensaje; el transporte lanza `TransportException` si hay más de una. Sobrescribir `buildMailMessage` conserva el contenido, la URL y la traducción estándar.
- **Alternatives considered**: `ResetPassword::toMailUsing()` obliga a reconstruir todo el mensaje. Un listener global de `MessageSending` que adivine el tipo es frágil e implícito.
- **Impacto**: `Notification::assertSentTo($user, ResetPassword::class)` compara la clase exacta, así que los tests que la usan (`tests/Feature/Auth/PasswordResetTest.php` y el caso de restablecimiento en `tests/Feature/Branding/InvitationMailBrandingTest.php`) pasan a `ResetPasswordNotification::class`.

## R5. Fallo de envío en el restablecimiento (FR-008)

- **Decision**: En `PasswordResetLinkController::store`, envolver `Password::sendResetLink()` en `try/catch (Symfony\Component\Mailer\Exception\TransportExceptionInterface $e)`. Ante un fallo, se hace `report($e)` y se lanza `ValidationException::withMessages(['email' => 'No pudimos enviar el correo. Intenta de nuevo en unos minutos.'])`.
- **Rationale**: Sigue el patrón ya usado en `ProjectInvitation::send()`. El formulario muestra `form.errors.email`, así que no hacen falta cambios de frontend. Capturar solo errores de transporte evita ocultar bugs ajenos.
- **Alternatives considered**: Capturar `Throwable` como hace `ProjectInvitation::send()`: es demasiado amplio para un flujo de autenticación. Mandar el error por flash: ForgotPassword.vue no lo muestra.
- **Duda resuelta**: ¿Atrapa `ProjectInvitation::send()` los errores de Postmark? Sí. Captura `Throwable`, y los errores HTTP de Postmark llegan como `HttpTransportException`, que implementa `TransportExceptionInterface`.

## R6. Limpieza de configuración (FR-009/FR-010)

- **Decision**: En `config/mail.php` se conservan los mailers `smtp` (buzón local Herd/Mailpit), `postmark`, `log` y `array`, y se eliminan `ses`, `resend`, `sendmail`, `failover` y `roundrobin`. En `config/services.php` se eliminan `resend` y `ses`. **Se conservan** las variables `AWS_*` de `.env(.example)`, porque `config/filesystems.php` las usa para S3.
- **Rationale**: El proyecto no tiene ninguna librería de correo de terceros que quitar (`composer.json` solo tiene paquetes del framework). Lo único "relacionado con correo" que sobra es configuración por defecto.
- **Verificación**: `composer show | grep -iE "mail|resend|aws|sendgrid|mailgun|brevo"` solo debe listar `symfony/mailer` y `symfony/postmark-mailer`.

## R7. Entornos

- **Decision**: Fuera de producción, `'default' => env('MAIL_MAILER', 'log')` se mantiene. Producción usa `MAIL_MAILER=postmark`. En tests, `phpunit.xml` ya fija `MAIL_MAILER=array`.
- **Rationale**: Si a un entorno le falta configuración, nunca envía correos reales por accidente (US3).

## R8. Seguimiento

- **Decision**: No se habilitan `TrackOpens` ni `TrackLinks`. Postmark los trae desactivados por defecto por servidor, y no se envían headers que los activen.

## R9. Hallazgo de implementación: merge de configuración del framework

- Laravel 11+ fusiona en tiempo de ejecución los arrays por defecto del framework (`mail.mailers`, `services`) con los archivos de `config/`. Por eso `config('mail.mailers')` sigue mostrando `ses`, `resend`, etc. aunque ya no estén en el proyecto. Son entradas inertes: nunca se usan mientras `MAIL_MAILER` no las elija.
- **Decision**: `MailConfigTest` verifica los archivos del proyecto (`require config_path(...)`) en lugar de `config()`. No se usa `dontMergeFrameworkConfiguration()`, porque afectaría a todos los archivos de configuración.
