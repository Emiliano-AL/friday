# Data Model: Rebranding público a Eunox

Esta feature **no agrega ni modifica tablas, columnas ni migraciones**. Las "entidades" del spec son valores de configuración.

## Nombre público del producto

| Atributo | Valor |
| --- | --- |
| Fuente | `config('app.name')` ← `APP_NAME` |
| Default | `'Eunox'` (en `config/app.php`) |
| Consumidores | Prop Inertia compartida `name`; `VITE_APP_NAME` (título de pestaña); `<title>` del layout Blade; `MAIL_FROM_NAME`; asunto y cuerpo de `ProjectInvitationMail`; layout de correos markdown |
| Validación | Cadena no vacía. Si `APP_NAME` falta, se usa `'Eunox'`, nunca `'Laravel'` ni `'Friday'` |
| Cambio futuro | Editar `APP_NAME` y recompilar assets (SC-005) |

## Codename

| Atributo | Valor |
| --- | --- |
| Valor | `friday` / "Friday" |
| Dónde vive | Nombre del repo, documentación de ingeniería, prefijos internos `friday:*` (provide/inject y `localStorage`), clase CSS `.friday-focus-scope` |
| Regla | Nunca se muestra como nombre del producto en UI ni en correos |

## Datos de usuario

Títulos de proyectos, tareas, comentarios y nombres que contengan "Friday" **no se transforman** (FR-007).
