# Data Model: Autenticación con correo/contraseña + Google

**Feature**: 001-breeze-google-login | **Date**: 2026-09-19
**Base**: migración existente `0001_01_01_000000_create_users_table.php`
(Laravel 13 default: `users`, `password_reset_tokens`, `sessions`).

## Entity: User (modificado)

Persona con acceso a la plataforma. Una cuenta puede tener acceso local,
acceso federado (Google) o ambos tras vinculación. El correo es único global
(garantiza SC-006: 0 cuentas duplicadas).

| Campo                       | Tipo               | Reglas / Cambio                                                                                           |
| --------------------------- | ------------------ | --------------------------------------------------------------------------------------------------------- |
| `id`                        | bigint PK          | existente                                                                                                 |
| `name`                      | string             | requerido, max 255; en alta vía Google usa el nombre del perfil con fallback al prefijo del correo        |
| `email`                     | string unique      | requerido, formato email, max 255, único (regla `unique:users` en registro y vinculación)                 |
| `email_verified_at`         | timestamp nullable | **local MVP**: queda null; **Google** (alta o vinculación): se fija a `now()` si era null                 |
| `password`                  | string             | **cambio: nullable** — solo cuentas con acceso local; cast `hashed` existente (nunca texto plano, SC-004) |
| `avatar`                    | string nullable    | **nuevo**: URL del avatar; Google lo provee, opcional en local                                            |
| `oauth_provider`            | string nullable    | **nuevo**: enum backed `OAuthProvider` (cast en el modelo); null = sin federación                         |
| `oauth_id`                  | string nullable    | **nuevo**: identificador del proveedor; único junto a `oauth_provider`                                    |
| `remember_token`            | string nullable    | existente ("recuérdame")                                                                                  |
| `created_at` / `updated_at` | timestamp          | existente                                                                                                 |

**Índices/unicidades nuevos**: `unique(oauth_provider, oauth_id)`;
`index(oauth_provider)`. La unicidad de `email` ya existe.

**Validación (registro local, derivada de FR-002)**:

- `name`: required, string, max 255
- `email`: required, email, max 255, unique:users
- `password`: required, string, min 8, confirmed (`password_confirmation`)
- Fortaleza adicional: se aceptan las reglas por defecto que genere Breeze
  (mínimo 8) sin exigir complejidad extra — la spec solo fija longitud mínima.

## Entity: OAuthProvider (nuevo enum)

Backed enum de PHP 8.4 (Principio IV — estados del dominio como enums).

```php
enum OAuthProvider: string
{
    case Google = 'google';
}
```

Extensión futura (otros proveedores) requiere enmienda constitucional
(FR-012): añadir un caso aquí sería el único cambio de dominio necesario.

## Entity: password_reset_tokens (existente, sin cambios)

Tabla framework para FR-008: `email` (PK), `token`, `created_at`. El
restablecimiento invalida el token tras su uso o expiración (comportamiento
framework). Requiere servicio de correo transaccional (supuesto de spec).

## Entity: sessions (existente, sin cambios)

Driver de sesión por base de datos (default del skeleton). Reglas de
transición de sesión:

- `guest → authenticated`: login local exitoso o callback Google exitoso;
  **siempre con regeneración de ID de sesión** (Breeze lo hace; el callback de
  Google debe hacerlo explícitamente).
- `authenticated → guest`: `POST /logout` con regeneración de token.
- Sesión expirada por timeout: middleware `auth` redirige a `/login` con
  intención preservada (`redirect()->intended()`).

## Reglas de transición de identidad

| De → Para                        | Regla                                                                                                                                                         |
| -------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Sin cuenta → cuenta local        | Registro con correo único; `password` requerido; `oauth_*` null                                                                                               |
| Sin cuenta → cuenta Google       | Callback crea cuenta; `password` null; `email_verified_at = now()`                                                                                            |
| Cuenta local → cuenta vinculada  | Callback con email coincidente: rellena `oauth_*` + `avatar` si null + `email_verified_at` si null; no pide contraseña (el correo vino verificado por Google) |
| Cuenta Google → cuenta vinculada | El usuario define contraseña vía flujo de restablecimiento (FR-008) cuando lo desee; no es obligatorio                                                        |

**Invariantes**:

- `email` nunca se repite entre cuentas (unicidad + vinculación, no duplicación).
- `password` null ⟺ cuenta sin acceso local (solo Google).
- `oauth_provider`/`oauth_id` son ambos null o ambos valor (pareja).
