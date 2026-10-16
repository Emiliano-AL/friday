# Implementation Plan: Rebranding público a Eunox

**Branch**: `026-rebrand-eunox` | **Date**: 2026-10-16 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/026-rebrand-eunox/spec.md`

## Summary

Reemplazar "Friday" (y el genérico "Laravel") por **Eunox** en toda superficie pública: UI, títulos de pestaña y correos. El nombre público vive en un solo lugar, `config('app.name')` (`APP_NAME`, por defecto `Eunox`). El backend ya lo comparte con Inertia como la prop `name`. Los textos de Vue y de correo dejan de tener el nombre escrito a mano y leen ese valor. El codename `friday` se conserva en los identificadores internos (claves `friday:*`, clase `friday-focus-scope`, repo) y en la documentación de ingeniería, a la que se agrega una nota de marca. No hay cambios de esquema, rutas ni dependencias.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13), TypeScript + Vue 3 (`<script setup>`)

**Primary Dependencies**: Laravel, Inertia.js v3, Tailwind CSS. No se agregan dependencias.

**Storage**: N/A (sin migraciones; el nombre público es configuración)

**Testing**: Pest (feature tests), `php artisan test --compact`

**Target Platform**: Web (monolito Laravel + Inertia, servido con Herd en local)

**Project Type**: Aplicación web monolítica

**Performance Goals**: N/A (cambio de textos; sin impacto medible)

**Constraints**: No romper preferencias guardadas en `localStorage` (`friday:kanban-done-collapsed`). No modificar contenido creado por personas usuarias. El texto visible no puede contener "Friday" ni "Laravel" como nombre del producto.

**Scale/Scope**: ~10 archivos Vue, 1 Mailable + 1 plantilla markdown, 4 archivos de configuración/entorno y 4 documentos (README, DESIGN.md, constitución, AGENTS/CLAUDE si aplica).

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Evaluación | Estado |
| --- | --- | --- |
| I. Monolith-First con Inertia | El nombre fluye servidor → cliente por la prop compartida `name` de Inertia. No se agrega API. | ✅ |
| II. Ecosistema Laravel | Se usa `config('app.name')` / `APP_NAME`, el mecanismo estándar. Sin dependencias nuevas. | ✅ |
| III. Test-First con Pest | Antes de cambiar código se escriben tests de feature: prop compartida, asunto/cuerpo/remitente del correo y un test de regresión que impide "Friday" en textos visibles. | ✅ |
| IV. Tipado estricto / Enums | Sin estados nuevos. `sharedPageProps.name: string` ya está tipado. | ✅ |
| V. Simplicidad / YAGNI | Se reutiliza la prop `name` existente. Sin composable, sin sistema de i18n ni tabla de "settings". | ✅ |
| Governance | La constitución conserva "Friday" como codename. Se agrega una nota de marca como enmienda **PATCH** (1.1.0 → 1.1.1) registrada en el Sync Impact Report. | ✅ |

**Resultado**: PASA. No hay violaciones que justificar.

**Re-check post-diseño**: PASA. El diseño (research.md, contracts/) no agrega capas, dependencias ni API nuevas.

## Project Structure

### Documentation (this feature)

```text
specs/026-rebrand-eunox/
├── plan.md              # Este archivo
├── research.md          # Phase 0
├── data-model.md        # Phase 1 (configuración, sin entidades persistidas)
├── quickstart.md        # Phase 1 (guía de validación)
├── contracts/
│   └── branding.md      # Contrato de la prop `name`, títulos y correos
└── tasks.md             # Phase 2 (/speckit-tasks)
```

### Source Code (repository root)

```text
config/
├── app.php                                  # default 'name' → 'Eunox'
└── mail.php                                 # default from.name → APP_NAME ?? 'Eunox'
.env.example                                 # APP_NAME=Eunox
.env                                         # APP_NAME=Eunox (local, no versionado)
phpunit.xml                                  # <env name="APP_NAME" value="Eunox"/>

app/Mail/ProjectInvitationMail.php           # asunto usa config('app.name')
resources/views/
├── app.blade.php                            # <title> fallback 'Eunox'
└── mail/project-invitation.blade.php        # cuerpo usa config('app.name')

resources/js/
├── app.ts                                   # fallback de appName → 'Eunox'
├── Components/
│   ├── Auth/AuthHeader.vue                  # "{name} Cloud OS"
│   ├── AppShell/AppShellSidebar.vue         # nombre en la barra lateral
│   ├── AppShell/HomeCanvas.vue              # "{name} v2.4"
│   └── Projects/MembersModal.vue            # texto de ayuda
└── pages/
    ├── Auth/Login.vue                       # "Iniciar sesión en {name}"
    ├── Auth/Register.vue                    # "Crear cuenta en {name}"
    ├── Auth/ResetPassword.vue               # subtítulo
    └── Invitations/Show.vue                 # "Ir a {name}"

tests/Feature/Branding/
├── SharedAppNameTest.php                    # prop Inertia `name` === 'Eunox'
├── InvitationMailBrandingTest.php           # asunto/cuerpo/remitente
└── NoLegacyBrandInUiTest.php                # guardia: sin "Friday" visible

README.md, DESIGN.md, .specify/memory/constitution.md   # nota "codename Friday / marca Eunox"
```

**Structure Decision**: Monolito existente. Solo se tocan archivos ya existentes, más una carpeta de tests `tests/Feature/Branding/`. Los identificadores internos con `friday` (`friday:open-palette`, `friday:open-task-form`, `friday:kanban-done-collapsed`, `.friday-focus-scope`) **no se tocan** (FR-008).

## Complexity Tracking

Sin violaciones. Sección no aplicable.
