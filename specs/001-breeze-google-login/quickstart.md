# Quickstart: validación end-to-end — Autenticación correo/contraseña + Google

**Feature**: 001-breeze-google-login | **Date**: 2026-09-19
**Contrato**: [contracts/web-auth-surface.md](./contracts/web-auth-surface.md) ·
**Modelo**: [data-model.md](./data-model.md)

Guía de escenarios ejecutables que demuestran la feature funcionando de
extremo a extremo. No incluye código de implementación.

## Prerequisites

- PHP 8.4 + Composer, Node + npm, Laravel Herd sirviendo el sitio (`friday.test`).
- Google OAuth app (opcional para exploración manual; los tests usan
  `Socialite::fake` y no requieren credenciales reales).
- Servicio de correo transaccional configurado en `.env` (p. ej. Mailhog/Mailpit
  local vía `MAIL_MAILER=log` para desarrollo sin SMTP).

```bash
composer install && npm install
cp .env.example .env   # si aplica
php artisan key:generate
php artisan migrate
```

Variables para Google (solo exploración manual real):

```env
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

## Correr la aplicación

```bash
composer run dev        # servidor + vite (o: npm run dev en otra terminal)
```

## Escenarios de validación

S1–S4 son manuales por navegador; S5–S10 son automáticos (Pest). Los escenarios
manual de Google requieren credenciales válidas; el escenario automatizado
(S8) cubre el flujo completo sin red.

### S1 — Registro local (FR-002, US1)

1. Abrir `/register` → se muestra formulario con nombre, correo, contraseña y
   confirmación.
2. Completar con contraseña de 7 caracteres → error "mínimo 8".
3. Completar válido → sesión iniciada, landing en `/` (AppHome).
4. Repetir el correo → error de correo ya registrado, sin cuenta duplicada.

### S2 — Login local y errores genéricos (FR-004, US1)

1. Cerrar sesión; en `/login` entrar con contraseña incorrecta → mensaje
   genérico idéntico para correo inexistente y contraseña mala.
2. Entrar con credenciales correctas → acceso a `/`.
3. Volver a `/login` estando autenticado → redirect a `/` (FR-011).

### S3 — Rate limiting (FR-007)

1. En `/login`, fallar 5 veces seguidas con la misma cuenta.
2. Al sexto intento → mensaje de bloqueo temporal; nuevos intentos dentro del
   minuto son rechazados aunque la contraseña sea correcta.

### S4 — Control de acceso y cierre de sesión (FR-009/010)

1. Sin sesión, abrir `/` → redirect a `/login`.
2. Tras autenticarse, se regresa automáticamente a `/`.
3. `POST /logout` → sesión cerrada; `/` vuelve a exigir login.

### S5 — Registro automatizado

```bash
php artisan test --compact --filter=RegistrationTest
```

Esperado: alta válida crea usuario y sesión; correo duplicado y contraseña
corta/deconfirmada son rechazados con sus errores.

### S6 — Login/logout/throttle automatizado

```bash
php artisan test --compact --filter=AuthenticationTest
```

Esperado: credenciales válidas autentican; inválidas no; el bloqueo tras 5
intentos se activa; logout termina la sesión.

### S7 — Restablecimiento de contraseña (FR-008)

```bash
php artisan test --compact --filter=PasswordResetTest
```

Esperado: se envía notificación con el enlace; con el token se cambia la
contraseña y el acceso con la nueva funciona; token usado/expirado es
rechazado y no altera la contraseña.

### S8 — Flujo Google completo sin red (FR-005/006, US2)

```bash
php artisan test --compact --filter=GoogleAuthenticationTest
```

Esperado con `Socialite::fake('google')`:

- callback con cuenta nueva → usuario creado con `oauth_provider=google`,
  `email_verified_at` fijado, sesión iniciada.
- segundo acceso → misma cuenta (sin duplicado).
- callback con correo de una cuenta local existente → vinculación: mismo
  `users.id`, `oauth_*` rellenados, acceso a esa cuenta.
- redirect a ruta protegida previa → `intended` preservado.

### S9 — Callback de Google con error

Esperado (cubierto en GoogleAuthenticationTest): consentimiento cancelado y
state inválido redirigen a `/login` con error genérico y **sin** crear cuenta.

### S10 — Calidad de contrato

```bash
composer lint:check        # pint --test
composer types:check       # phpstan analyse
npm run types:check        # vue-tsc --noEmit
npm run check              # vp check
```

Esperado: los cuatro comandos pasan (quality gates del repo).

## Criterio de "feature funcionando"

S1–S4 verificados manualmente + suites S5–S9 en verde + S10 sin violaciones.
Esto satisface los escenarios de aceptación US1–US4 de la spec y habilita el
paso a `/skill:speckit-tasks`.
