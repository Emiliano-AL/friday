# Implementation Plan: Autenticación con correo/contraseña + Google

**Branch**: `001-breeze-google-login` | **Date**: 2026-09-19 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/001-breeze-google-login/spec.md`

## Summary

Implementar el acceso a Friday con dos métodos en la misma pantalla: (1) login
tradicional con correo y contraseña mediante Laravel Breeze (stack Vue, con
tests en Pest) y (2) inicio de sesión federado con Google como único proveedor
OAuth mediante Laravel Socialite, con vinculación automática cuando el correo
verificado por Google coincide con una cuenta local. Incluye recuperación de
contraseña por enlace de un solo uso, cierre de sesión, control de acceso a
páginas internas, rate limiting anti fuerza bruta y redirección de usuarios ya
autenticados. Las páginas de Breeze se adaptan al estándar TypeScript del
proyecto (CI corre `vue-tsc`).

## Technical Context

**Language/Version**: PHP 8.4 (constraint `^8.3` en composer.json) + TypeScript 5.2 en el frontend

**Primary Dependencies**: Laravel 13.17 (framework), Inertia Laravel 3 + `@inertiajs/vue3` 3, Tailwind CSS 4, Wayfinder, Vite Plus (`vite-plus` 0.3). A añadir (aprobados por constitución v1.1.0): `laravel/breeze` ^2.4 (stack vue, flag `--pest`), `laravel/socialite` ^5

**Storage**: PostgreSQL (producción/Herd); SQLite en `database/database.sqlite` para tests locales

**Testing**: Pest 5 + `pest-plugin-laravel`; `Socialite::fake()` / `User::fake()` para OAuth; `Notification::fake()` para restablecimiento

**Target Platform**: Aplicación web monolítica servida por Laravel Herd (macOS dev); despliegue web estándar

**Project Type**: web application (Inertia monolith, sin API desacoplada)

**Performance Goals**: Estándar web para auth: páginas de acceso con respuesta < 1 s; el flujo OAuth depende del proveedor (objetivo de spec: < 30 s totales)

**Constraints**: Monolito Inertia (Prohibida API REST desacoplada); CI exige `pint --test`, `phpstan analyse` (larastan) y `vue-tsc --noEmit`; Google es el único proveedor OAuth; sesiones en base de datos (tabla `sessions` existente)

**Scale/Scope**: MVP — decenas de usuarios, un único método de acceso por cuenta con posibilidad de vincular ambos; sin roles, sin verificación de correo obligatoria, sin 2FA

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio                                | Evaluación pre-diseño                                                                                                                                                           | Resultado |
| ---------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------- |
| I. Monolith-First con Inertia            | Breeze stack Vue renderiza páginas Inertia desde el backend; Socialite opera dentro del mismo monolito                                                                          | PASS      |
| II. Alineación con el Ecosistema         | `laravel/breeze` y `laravel/socialite` son paquetes first-party; dependencias pre-aprobadas por enmienda v1.1.0                                                                 | PASS      |
| III. Test-First con Pest                 | Todos los flujos (registro, login, Google con `Socialite::fake`, restablecimiento, throttling, redirecciones) tienen tests de feature planeados; Breeze se instala con `--pest` | PASS      |
| IV. Tipado Estricto y Estados como Enums | `OAuthProvider` es enum backed `string` (único caso `Google`); contraseña con cast `hashed`; PHP 8.4 con tipado explícito en controllers/requests nuevos                        | PASS      |
| V. Simplicidad y YAGNI                   | Solo Google como proveedor; sin verificación de correo, 2FA, equipos ni roles; se reutiliza el scaffolding de Breeze sin reescribir auth a mano                                 | PASS      |

**Post-design re-check (tras Phase 1)**: el diseño mantiene los 5 PASS — sin
API nueva, sin dependencias fuera de las aprobadas, contratos cubiertos por
tests Pest, enum para el proveedor, y el alcance se limita a lo especificado.
Sin violaciones que justificar.

## Project Structure

### Documentation (this feature)

```text
specs/001-breeze-google-login/
├── plan.md              # This file (/skill:speckit-plan command output)
├── research.md          # Phase 0 output (/skill:speckit-plan command)
├── data-model.md        # Phase 1 output (/skill:speckit-plan command)
├── quickstart.md        # Phase 1 output (/skill:speckit-plan command)
├── contracts/           # Phase 1 output (/skill:speckit-plan command)
└── tasks.md             # Phase 2 output (/skill:speckit-tasks command - NOT created by /skill:speckit-plan)
```

### Source Code (repository root)

Estructura single-project (web app). Rutas reales tras la instalación de Breeze
(stack vue) y la feature de Google:

```text
app/
├── Enums/
│   └── OAuthProvider.php                      # NUEVO: backed enum, caso Google
├── Http/
│   ├── Controllers/
│   │   └── Auth/                              # NUEVO (scaffold Breeze, adaptado)
│   │       ├── AuthenticatedSessionController.php
│   │       ├── RegisteredUserController.php
│   │       ├── PasswordResetLinkController.php
│   │       ├── NewPasswordController.php
│   │       └── GoogleAuthController.php       # NUEVO (redirect/callback Google)
│   ├── Requests/
│   │   └── Auth/
│   │       └── LoginRequest.php               # NUEVO (scaffold Breeze: throttle 5 intentos)
│   └── Middleware/                            # existente
├── Models/
│   └── User.php                               # MODIFICADO: password nullable, oauth_*, avatar
database/
├── migrations/
│   └── 2026_09_19_000000_add_oauth_to_users_table.php  # NUEVO
resources/js/
├── layouts/                                   # NUEVO (scaffold Breeze, convertido a TS)
│   ├── GuestLayout.vue
│   └── AppLayout.vue
├── pages/
│   ├── Welcome.vue                            # existente
│   ├── AppHome.vue                            # NUEVO: destino post-login (placeholder MVP)
│   └── auth/                                  # NUEVO (scaffold Breeze, convertido a TS)
│       ├── Login.vue
│       ├── Register.vue
│       ├── ForgotPassword.vue
│       └── ResetPassword.vue
└── routes/                                    # Wayfinder regenerado (build)
routes/
├── web.php                                    # MODIFICADO: home con middleware auth
└── auth.php                                   # NUEVO (scaffold Breeze + rutas Google)
tests/
└── Feature/
    └── Auth/                                  # NUEVO (tests Pest por flujo)
        ├── RegistrationTest.php
        ├── AuthenticationTest.php             # login/logout/throttle
        ├── GoogleAuthenticationTest.php       # redirect/callback/vinculación (Socialite::fake)
        └── PasswordResetTest.php
config/
├── services.php                               # MODIFICADO: entrada google
```

**Structure Decision**: se conserva la estructura single-project existente del
`blank-vue-starter-kit`. Breeze añade `app/Http/Controllers/Auth` +
`app/Http/Requests/Auth` + `routes/auth.php` + páginas Vue; la feature suma
únicamente `GoogleAuthController`, el enum `OAuthProvider`, una migración de
columnas OAuth y la conversión de los stubs de Breeze a TypeScript para
satisfacer `vue-tsc` en CI. Páginas de Breeze no usadas (verificación de
correo, confirmación de contraseña) no se registran ni se adaptan (YAGNI).
