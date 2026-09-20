# Contract: Superficie web de autenticación

**Feature**: 001-breeze-google-login | **Date**: 2026-09-19
**Tipo**: contrato de UI/rutas para aplicación Inertia monolítica. Todas las
interacciones son navegación web estándar (GET/POST con cookies de sesión);
no existe API desacoplada (Principio I). Las páginas Inertia se resuelven en
el servidor con `Inertia::render()` y propagan errores de validación vía
prop `errors`.

## Convenciones del contrato

- **Éxito en formulario local**: redirect 302 (Inertia) al destino indicado.
- **Error de validación**: 302 de vuelta al formulario con prop `errors`
  (campo → mensaje) y entrada previa rehidratada (`old`).
- **Error de estado/negocio**: redirect a `/login` con mensaje flash de error
  (`errors`/`flash`), nunca dump de excepción.
- **Intención**: rutas `guest` guardan la URL solicitada; tras autenticar,
  `redirect()->intended('/')` devuelve al usuario a donde iba (FR-010).
- **Frontend**: las páginas consumen las rutas mediante funciones Wayfinder
  generadas en build (`@/routes`, `@/actions`); los nombres de ruta del
  contrato son la referencia canónica.

## Rutas

### Invitado (middleware `guest`)

| Método | Ruta                      | Nombre             | Resultado éxito                                                                                | Resultado error                                                                                                                                                                                                  |
| ------ | ------------------------- | ------------------ | ---------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| GET    | `/login`                  | `login`            | Página `auth/Login` (formulario correo/contraseña + botón "Continuar con Google")              | —                                                                                                                                                                                                                |
| POST   | `/login`                  | —                  | Sesión iniciada; redirect a `/` (o intención)                                                  | Vuelta a `auth/Login` con `errors` (mensaje genérico que no revela si el correo existe, FR-004); tras 5 intentos/min (por correo+IP): bloqueo temporal con mensaje de espera (FR-007)                            |
| GET    | `/register`               | `register`         | Página `auth/Register`                                                                         | —                                                                                                                                                                                                                |
| POST   | `/register`               | —                  | Cuenta creada + sesión iniciada; redirect a `/`                                                | Vuelta a `auth/Register` con `errors` (correo duplicado: mensaje "correo ya registrado"; contraseña: min 8 + confirmación)                                                                                       |
| GET    | `/forgot-password`        | `password.request` | Página `auth/ForgotPassword`                                                                   | —                                                                                                                                                                                                                |
| POST   | `/forgot-password`        | `password.email`   | Redirect de vuelta con estado "si el correo existe, enviamos el enlace" (sin enumerar cuentas) | Vuelta con `errors` de validación de correo                                                                                                                                                                      |
| GET    | `/reset-password/{token}` | `password.reset`   | Página `auth/ResetPassword` con email precargado (query `email`)                               | Token inválido/expirado: redirect a `password.request` con error                                                                                                                                                 |
| POST   | `/reset-password`         | `password.store`   | Contraseña actualizada; redirect a `/login` con estado "contraseña restablecida"               | Vuelta a `auth/ResetPassword` con `errors`; token usado/expirado no cambia la contraseña                                                                                                                         |
| GET    | `/auth/google/redirect`   | `google.redirect`  | Redirect 302 a la URL de consentimiento de Google (state firmado en sesión)                    | —                                                                                                                                                                                                                |
| GET    | `/auth/google/callback`   | `google.callback`  | Cuenta creada/vinculada + sesión iniciada (regenerada); redirect a `/` (o intención)           | Consentimiento cancelado → `/login` sin cuenta creada; state inválido/fallo de comunicación con Google → `/login` con error genérico de "intento de acceso fallido, inténtalo de nuevo". Throttle: 10/min por IP |

### Autenticado (middleware `auth`)

| Método | Ruta      | Nombre   | Resultado                                                                        |
| ------ | --------- | -------- | -------------------------------------------------------------------------------- |
| POST   | `/logout` | `logout` | Sesión terminada (regeneración de token); redirect a `/login`                    |
| GET    | `/`       | `home`   | Página `AppHome` (placeholder MVP). Invitado → redirect a `/login` con intención |

### Restricciones de contrato (FR-011, FR-012)

- Usuario autenticado que visita cualquier ruta `guest` es redirigido a `/`.
- La interfaz no muestra enlace, botón ni ruta hacia ningún proveedor distinto
  de Google; `config/services.php` solo define la entrada `google`.
