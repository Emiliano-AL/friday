# Implementation Plan: Página de Configuración (/settings)

**Branch**: `025-settings-page` | **Date**: 2026-10-13 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/025-settings-page/spec.md`

## Summary

Página Inertia `Settings/Index` en `/settings` con tres secciones que se guardan por
separado: perfil (solo nombre; correo de solo lectura), contraseña (cambiar o definir
si la cuenta es de Google) y zona de peligro (eliminar cuenta, bloqueada mientras la
persona sea dueña de proyectos). Un solo `SettingsController` con validación inline,
`auth.session` en el grupo autenticado para invalidar otras sesiones tras cambiar la
contraseña, y un rate limiter para los endpoints que verifican la contraseña actual.
Las entradas «Próximamente» de la barra lateral y del menú de perfil pasan a enlazar
a la página. Decisiones en [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13) + Vue 3 / TypeScript.

**Primary Dependencies**: Inertia v3, Wayfinder. Sin dependencias nuevas.

**Storage**: tabla `users` existente; sin migraciones.

**Testing**: Pest feature tests (`tests/Feature/Settings/SettingsTest.php`); `npm run types:check`; escenarios de [quickstart.md](quickstart.md).

**Target Platform**: web monolítica Inertia.

**Project Type**: web-app monolito.

**Performance Goals**: N/A (formularios simples).

**Constraints**: mensajes en español; el correo nunca cambia desde esta página; eliminación de cuenta imposible con proyectos propios (validado en servidor).

**Scale/Scope**: 1 controlador (4 acciones), 1 página Vue, 1 rate limiter, cambios en `AuthenticatedLayout` y `ProfileMenu`.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Evaluación |
| --- | --- |
| I. Monolith-First con Inertia | PASS — Página Inertia y formularios con redirect back + flash. |
| II. Ecosistema Laravel | PASS — Regla `current_password`, `Rules\Password`, `auth.session` + `Auth::logoutOtherDevices`, `RateLimiter`, Wayfinder; sin dependencias. |
| III. Test-First con Pest | PASS — `SettingsTest` (acceso, nombre, correo inmutable, contraseña cambiar/definir, sesiones, throttle, eliminación bloqueada/permitida) antes de implementar. |
| IV. Tipado y Enums | PASS — Sin estados nuevos; tipos explícitos y shapes PHPDoc en props. |
| V. Simplicidad / YAGNI | PASS — Sin FormRequests ni componentes extra; sin preferencias ni transferencia de proyectos (fuera de alcance por clarificación). |

**Post-design re-check**: PASS.

## Project Structure

### Documentation (this feature)

```text
specs/025-settings-page/
├── plan.md · research.md · data-model.md · quickstart.md
├── contracts/routes.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/Http/Controllers/SettingsController.php        # nuevo: edit, updateProfile, updatePassword, destroy
app/Providers/AppServiceProvider.php               # + RateLimiter 'account-security'
routes/web.php                                     # + rutas settings.*, + 'auth.session' en el grupo auth
resources/js/pages/Settings/Index.vue              # nuevo: 3 secciones
resources/js/Layouts/AuthenticatedLayout.vue       # «Ajustes» → settings.edit, match '/settings'
resources/js/Components/AppShell/ProfileMenu.vue   # «Detalles del perfil» y «Ajustes de cuenta» → Link
tests/Feature/Settings/SettingsTest.php            # nuevo
```

**Structure Decision**: monolito existente; `pages/Settings/` sigue el patrón de una carpeta por sección (`Backlog/`, `Tasks/`).

## Complexity Tracking

Sin violaciones.
