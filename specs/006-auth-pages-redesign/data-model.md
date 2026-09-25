# Data Model: Rediseño del layout de páginas de autenticación

**Feature**: `specs/006-auth-pages-redesign`
**Date**: 2026-09-24

## Resultado

**No hay entidades de datos nuevas.** Este feature es puramente de presentación (FR-005, FR-009).

## Datos existentes reutilizados (sin modificación)

Los contratos de datos entre servidor y página se mantienen exactamente igual; las páginas siguen consumiendo los mismos props de Inertia:

| Página               | Props de entrada                                          | Campos de formulario                                                    | Endpoint (POST)  |
| -------------------- | --------------------------------------------------------- | ----------------------------------------------------------------------- | ---------------- |
| `Login.vue`          | `canResetPassword?: boolean`, `status?: string`, `errors` | `email`, `password`, `remember: boolean`                                | `login`          |
| `Register.vue`       | `errors`                                                  | `name`, `email`, `password`, `password_confirmation`                    | `register`       |
| `ForgotPassword.vue` | `status?: string`, `errors`                               | `email`                                                                 | `password.email` |
| `ResetPassword.vue`  | `email: string`, `token: string`, `errors`                | `email` (oculto), `token` (oculto), `password`, `password_confirmation` | `password.store` |

## Reglas de validación (heredadas, sin cambios)

- Login: `email` requerido con formato email; `password` requerido.
- Registro: `name` requerido; `email` requerido/único/formato; `password` requerido + `password_confirmation` coincidente (reglas de Breeze).
- Recuperación: `email` requerido con formato email.
- Restablecimiento: `email`, `token`, `password` + confirmación (reglas de Breeze).

## Estado de UI nuevo (local, sin persistencia)

- `showPassword: boolean` en `Login.vue` y `ResetPassword.vue` — alterna `type` del input contraseña. Estado local efímero, sin entidad ni almacenamiento.

## Conclusión

Ninguna migración, modelo, factory ni seeder requerido. La red de regresión son los tests de feature existentes (`tests/Feature/Auth/*`).
