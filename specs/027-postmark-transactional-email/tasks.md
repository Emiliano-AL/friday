---
description: 'Task list for 027-postmark-transactional-email'
---

# Tasks: Correos transaccionales con Postmark

**Input**: Design documents from `/specs/027-postmark-transactional-email/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/mail-delivery.md, quickstart.md

**Tests**: Incluidos. La constitución (Principio III) exige escribirlos primero y verlos fallar antes de implementar.

**Organization**: Agrupado por historia: US1 invitaciones, US2 restablecer contraseña, US3 entornos y etiquetas, US4 configuración limpia.

## Format: `[ID] [P?] [Story] Description`

## Convenciones para todas las tareas

- Ninguna llamada directa a la API de Postmark. Todo pasa por `Mail`/`Notification` de Laravel (FR-002).
- Postmark admite **exactamente una etiqueta por mensaje**. Nunca agregar más de una.
- Los tests nunca hacen envíos reales: `phpunit.xml` ya fija `MAIL_MAILER=array`. Usar `Mail::fake()`, `Notification::fake()` o mocks.
- Mensajes de error exactos:
  - Invitación (existente): `No pudimos enviar el correo. Puedes reenviar la invitación.`
  - Restablecimiento (nuevo): `No pudimos enviar el correo. Intenta de nuevo en unos minutos.`
- Tras cada fase PHP: `vendor/bin/pint --dirty`.

---

## Phase 1: Setup (Shared Infrastructure)

- [X] T001 Instalar las dependencias del transporte nativo con `composer require symfony/postmark-mailer:^8.1 symfony/http-client:^8.1` (actualiza composer.json y composer.lock). Dependencias aprobadas por la persona usuaria; ver la sección Complexity Tracking de plan.md.
- [X] T002 [P] En .env.example, debajo del bloque `MAIL_*`, agregar `POSTMARK_API_KEY=` (vacío) y `POSTMARK_MESSAGE_STREAM_ID=outbound`, más un comentario de una línea: `# Producción: MAIL_MAILER=postmark y MAIL_FROM_ADDRESS verificado en Postmark`. Mantener `MAIL_MAILER=log` como default.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Mailer `postmark` funcional; US1 y US2 dependen de esto.

- [X] T003 Crear tests/Feature/Mail/PostmarkTransportTest.php con dos casos. (a) Con `config(['services.postmark.key' => 'POSTMARK_API_TEST'])`, `Mail::mailer('postmark')->getSymfonyTransport()` es `instanceof Symfony\Component\Mailer\Bridge\Postmark\Transport\PostmarkApiTransport`. (b) `config('mail.mailers.postmark.message_stream_id')` vale `'outbound'` y `config('mail.mailers.postmark.client.timeout')` vale `5`. Ejecutar: (b) debe **fallar**.
- [X] T004 En config/mail.php, reemplazar el bloque `'postmark'` (hoy con líneas comentadas) por `'postmark' => ['transport' => 'postmark', 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID', 'outbound'), 'client' => ['timeout' => 5]]`. Ejecutar T003: debe pasar.

**Checkpoint**: Con `MAIL_MAILER=postmark` y un token, la app envía por Postmark.

---

## Phase 3: User Story 1 - Las personas invitadas reciben el correo (Priority: P1) 🎯 MVP

**Goal**: Las invitaciones salen por Postmark con la etiqueta `project-invitation`. Un fallo de Postmark deja la invitación pendiente con el mensaje de reenvío.

**Independent Test**: quickstart.md §4.2 y §5.2. En tests: etiqueta presente y fallo de transporte HTTP manejado.

### Tests for User Story 1 ⚠️

- [X] T005 [P] [US1] Crear tests/Feature/Mail/MailTagsTest.php con el caso "invitation is tagged project-invitation": construir `new ProjectInvitationMail(ProjectInvitation::factory()->withToken('t')->create(), 't')` y verificar `$mail->envelope()->tags === ['project-invitation']` (exactamente una etiqueta). Debe **fallar**.
- [X] T006 [P] [US1] En tests/Feature/Projects/ProjectInvitationsTest.php, agregar un caso como el existente "a failed delivery keeps the invitation…" pero lanzando `new Symfony\Component\Mailer\Exception\HttpTransportException('Postmark: sender signature not confirmed', new Symfony\Component\HttpClient\Response\MockResponse('', ['http_code' => 422]))`. Se crea la respuesta con `MockResponse::fromRequest(...)`, o con un `ResponseInterface` de Mockery si el constructor lo exige. Verificar el flash `error` `No pudimos enviar el correo. Puedes reenviar la invitación.` y la invitación en `pending`. Debe pasar sin cambios de código, porque `ProjectInvitation::send()` captura `Throwable`; documenta FR-008 para Postmark.

### Implementation for User Story 1

- [X] T007 [US1] En app/Mail/ProjectInvitationMail.php, en `envelope()`, agregar `tags: ['project-invitation'],` al `new Envelope(...)` y conservar el asunto actual. Ejecutar T005: debe pasar.

**Checkpoint**: Invitaciones listas para Postmark.

---

## Phase 4: User Story 2 - Recuperar la contraseña funciona de punta a punta (Priority: P1)

**Goal**: El correo de restablecimiento sale por Postmark con la etiqueta `password-reset`. Un fallo de envío no produce un 500.

**Independent Test**: quickstart.md §4.3 y §5.3. En tests: etiqueta presente; un fallo devuelve un error de validación en `email`.

### Tests for User Story 2 ⚠️

- [X] T008 [P] [US2] En tests/Feature/Mail/MailTagsTest.php, agregar el caso "password reset is tagged password-reset". Con `Notification::fake()`, `post(route('password.email'), ['email' => $user->email])` y luego `Notification::assertSentTo($user, App\Notifications\ResetPasswordNotification::class, fn ($n) => $n->toMail($user)->tags === ['password-reset'])`. Debe **fallar** porque la clase aún no existe.
- [X] T009 [P] [US2] Crear tests/Feature/Mail/PasswordResetDeliveryFailureTest.php. Primero `Password::shouldReceive('sendResetLink')->andThrow(new Symfony\Component\Mailer\Exception\TransportException('postmark down'))`. Luego `post(route('password.email'), ['email' => 'a@example.com'])` debe hacer `assertRedirect()` (sin 500) y `assertSessionHasErrors(['email' => 'No pudimos enviar el correo. Intenta de nuevo en unos minutos.'])`. Agregar un segundo caso con `Exceptions::fake()` y `Exceptions::assertReported(TransportException::class)`. Debe **fallar**.

### Implementation for User Story 2

- [X] T010 [US2] Crear app/Notifications/ResetPasswordNotification.php con `php artisan make:notification ResetPasswordNotification --no-interaction` y reemplazar su contenido por una clase que `extends Illuminate\Auth\Notifications\ResetPassword`, con un único método `protected function buildMailMessage($url): MailMessage { return parent::buildMailMessage($url)->tag('password-reset'); }` y su docblock de una línea. Sin constructor propio.
- [X] T011 [US2] En app/Models/User.php, agregar `public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void { $this->notify(new ResetPasswordNotification($token)); }` con el `use App\Notifications\ResetPasswordNotification;`. Copiar la firma exacta del trait `Illuminate\Auth\Passwords\CanResetPassword` en vendor para que sea compatible.
- [X] T012 [US2] En app/Http/Controllers/Auth/PasswordResetLinkController.php (`store`), envolver `Password::sendResetLink(...)` en `try { … } catch (Symfony\Component\Mailer\Exception\TransportExceptionInterface $exception) { report($exception); throw ValidationException::withMessages(['email' => ['No pudimos enviar el correo. Intenta de nuevo en unos minutos.']]); }` y conservar el resto del flujo. No capturar `Throwable` (research R5).
- [X] T013 [US2] Actualizar las aserciones a la nueva clase. En tests/Feature/Auth/PasswordResetTest.php, cambiar las 5 ocurrencias de `Notification::assertSentTo($user, ResetPassword::class` por `ResetPasswordNotification::class` y el import por `App\Notifications\ResetPasswordNotification`. Hacer lo mismo en tests/Feature/Branding/InvitationMailBrandingTest.php (import, `assertSentTo` y el type-hint del closure).
- [X] T014 [US2] Ejecutar `php artisan test --compact tests/Feature/Mail tests/Feature/Auth tests/Feature/Branding`: todo en verde.

**Checkpoint**: US1 y US2 completas. Ambos correos están listos para Postmark y no hay 500 ante fallos.

---

## Phase 5: User Story 3 - Control por entorno y etiquetas (Priority: P2)

**Goal**: El proveedor se elige por entorno, los tests nunca envían de verdad y el equipo sabe cómo activar Postmark.

**Independent Test**: La suite pasa sin credenciales. Siguiendo el README, se activa Postmark en menos de 15 min (SC-006).

### Tests for User Story 3 ⚠️

- [X] T015 [P] [US3] Crear tests/Feature/Mail/MailConfigTest.php con el caso "tests never use a real provider": `config('mail.default')` vale `'array'`. Agregar el caso "the default mailer outside configuration is log": leer `config/mail.php` como texto y verificar que contiene `env('MAIL_MAILER', 'log')`.

### Implementation for User Story 3

- [X] T016 [US3] En README.md, agregar la sección `## Correo transaccional (Postmark)` con:
  - variables (`MAIL_MAILER=postmark`, `POSTMARK_API_KEY`, `POSTMARK_MESSAGE_STREAM_ID`, `MAIL_FROM_ADDRESS` verificado);
  - verificación del dominio (DKIM + Return-Path) o del Sender Signature;
  - prueba con `POSTMARK_API_TEST` (enlazar specs/027-postmark-transactional-email/quickstart.md §3);
  - etiquetas existentes (`project-invitation`, `password-reset`) y la regla de una sola etiqueta por mensaje;
  - desarrollo local con `log` o `smtp` (Herd/Mailpit).

---

## Phase 6: User Story 4 - Configuración limpia con un solo proveedor (Priority: P3)

**Goal**: Solo Postmark como proveedor externo, sin configuración muerta.

**Independent Test**: quickstart.md §2: los `grep` y `composer show` no muestran otros proveedores.

### Tests for User Story 4 ⚠️

- [X] T017 [US4] En tests/Feature/Mail/MailConfigTest.php, agregar el caso "only supported mailers are configured": `array_keys(config('mail.mailers'))` ordenadas valen `['array', 'log', 'postmark', 'smtp']`. Agregar también que `config('services')` no tiene las claves `ses` ni `resend` y sí tiene `postmark.key`. Debe **fallar**.

### Implementation for User Story 4

- [X] T018 [P] [US4] En config/mail.php, eliminar los bloques de mailers `ses`, `resend`, `sendmail`, `failover` y `roundrobin`. Ajustar el comentario de cabecera del array `mailers` para que liste solo los soportados: "smtp", "postmark", "log", "array".
- [X] T019 [P] [US4] En config/services.php, eliminar las entradas `'resend'` y `'ses'`. Conservar `postmark`, `slack` y `google`. **No** eliminar `AWS_*` de `.env`/`.env.example` (los usa `config/filesystems.php`).
- [X] T020 [US4] Ejecutar `php artisan config:clear` y `php artisan test --compact tests/Feature/Mail`: todo en verde.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T021 Verificar SC-005: `composer show | grep -iE "mail|resend|sendgrid|mailgun|brevo"` solo lista `symfony/mailer` y `symfony/postmark-mailer`. Además, `grep -nE "'(ses|resend|roundrobin|failover|sendmail)'" config/mail.php config/services.php` no devuelve nada.
- [X] T022 Ejecutar `vendor/bin/pint --dirty` y la suite completa `php artisan test --compact` (SC-004).
- [ ] T023 Smoke test sin entrega real (quickstart.md §3). Solo si la persona usuaria provee un token y un remitente verificado; si no, dejarlo como pendiente en el reporte final. **No** escribir el token en archivos versionados. — **Pendiente**: no se proporcionó token de Postmark.

---

## Dependencies & Execution Order

- **Phase 1 → Phase 2**: T004 necesita los paquetes de T001 para que T003(a) pase.
- **US1 (Phase 3)** y **US2 (Phase 4)**: dependen de Phase 2 y son independientes entre sí. T005 y T008 comparten MailTagsTest.php: crear el archivo en T005 y agregar el caso en T008, en ese orden.
- **US3 (Phase 5)**: T015 crea MailConfigTest.php y T017 (US4) le agrega casos, así que va después.
- **US4 (Phase 6)**: independiente de US1 y US2.
- **Polish**: al final.

### Parallel Opportunities

- T002 junto con T001.
- T005 y T006 (archivos distintos).
- T008 y T009 (archivos distintos). Después de T010, T011 y T012 tocan archivos distintos, pero T011 depende de T010.
- T018 y T019.

## Parallel Example: User Story 2

```bash
Task: "T008 MailTagsTest → caso password-reset"
Task: "T009 PasswordResetDeliveryFailureTest"
# luego
Task: "T010 ResetPasswordNotification" → "T011 User::sendPasswordResetNotification"
Task: "T012 PasswordResetLinkController try/catch"   # en paralelo con T010
```

## Implementation Strategy

### MVP

Phases 1 + 2 + 3 (US1): las invitaciones salen por Postmark con etiqueta. Con solo esto ya se puede activar `MAIL_MAILER=postmark` en producción. El restablecimiento también saldría por Postmark, aunque todavía sin etiqueta ni manejo de fallo.

### Incremental

1. MVP (US1).
2. US2: etiqueta para el restablecimiento y sin 500.
3. US3: documentación y garantías por entorno.
4. US4: limpieza de configuración.
5. Polish: verificación de dependencias, suite completa y smoke test con token.
