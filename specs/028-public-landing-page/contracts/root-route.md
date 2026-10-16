# Contract: Ruta raíz `GET /` (route name `home`)

## HTTP

| Estado de sesión                        | Middleware | Respuesta | Componente Inertia | Props                                               |
| --------------------------------------- | ---------- | --------- | ------------------ | --------------------------------------------------- |
| Invitado (sin sesión o sesión expirada) | `web`      | `200`     | `Landing`          | ninguna propia                                      |
| Autenticado                             | `web`      | `200`     | `AppHome`          | `dashboard` (sin cambios respecto a la feature 015) |

- La ruta conserva el nombre `home`. Las redirecciones existentes
  (`intended(route('home'))` después de login, registro, Google OAuth y
  aceptar invitación) no cambian.
- Ya **no** se aplica `auth` a esta ruta, así que no hay redirección al login
  para invitados.
- Las demás rutas públicas (`login`, `register`, `password.*`,
  `invitations.show`, `google.*`) no cambian.

## UI: página `Landing`

| Elemento                                    | Destino / comportamiento           | Requisito      |
| ------------------------------------------- | ---------------------------------- | -------------- |
| Logo + nombre en el header                  | Ancla al inicio de la página (`#`) | FR-003         |
| "Características" (header)                  | Scroll a `#caracteristicas`        | FR-011         |
| "Cómo funciona" (header)                    | Scroll a `#como-funciona`          | FR-011         |
| "Iniciar sesión" (header)                   | `login()` (Wayfinder)              | FR-006         |
| "Comenzar gratis" (header, hero, CTA final) | `register()` (Wayfinder)           | FR-005         |
| `<title>`                                   | `"<titular> - Eunox"`              | FR-010         |
| `<meta name="description">`, `og:*`         | Texto de Eunox, sin "Friday"       | FR-004, FR-010 |

- Los textos están en español (FR-008). Las capacidades descritas son las
  listadas en FR-007.
- Anchos objetivo: 375 px, 768 px y ≥1280 px, sin scroll horizontal (FR-009,
  SC-007).
