# Implementation Plan: Logo de Eunox en favicon y puntos de marca

**Branch**: `029-brand-logo-favicon` | **Date**: 2026-10-16 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/029-brand-logo-favicon/spec.md`

## Summary

Reemplazar los íconos de Laravel por el isotipo de Eunox (cuadro índigo
`#5B5BD6` con el símbolo blanco) en el favicon, el ícono de iOS y un
manifiesto web nuevo. Unificar el logo de la UI en `AppLogo.vue` (con una
prop `size`) para las pantallas de auth, y mostrar el logo como PNG en el
encabezado de los correos. Los íconos se generan con un script reproducible
(Chrome headless + Pillow) y los binarios se versionan
([research.md](./research.md) D1–D6).

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13), TypeScript + Vue 3, Blade; Python 3 solo para el script de generación (no es dependencia del runtime)

**Primary Dependencies**: Inertia v3, Tailwind v4, Wayfinder, Mail markdown de Laravel. Herramientas locales de una sola vez: Google Chrome headless y Pillow. Sin dependencias nuevas en Composer ni npm.

**Storage**: archivos estáticos en `public/`

**Testing**: Pest (feature tests de archivos, `<head>` y HTML de correos), `vue-tsc`

**Target Platform**: navegadores de escritorio y móviles, iOS/Android (pantalla de inicio), clientes de correo (Gmail, Outlook, Apple Mail)

**Project Type**: monolito web Laravel + Inertia

**Performance Goals**: favicon SVG < 1 KB y PNG optimizados (< 20 KB el de 512 px)

**Constraints**: sin transparencia en apple-touch-icon; correos sin SVG ni CSS externo; nada de "Friday" en archivos ni metadatos

**Scale/Scope**: unos 7 archivos de marca, 1 script, 4 archivos Vue/Blade modificados, 1 vista de correo publicada, 2 archivos de tests

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Evaluación | Estado |
| --------- | ---------- | ------ |
| I. Monolith-First con Inertia | Solo activos estáticos, Blade y componentes Vue existentes. | ✅ |
| II. Ecosistema Laravel / dependencias | No hay dependencias nuevas. El script usa herramientas locales y sus salidas se versionan. Se usa el mecanismo estándar de override de vistas `vendor/mail`. | ✅ |
| III. Test-First con Pest | `BrandIconsTest` y `MailBrandHeaderTest` se escriben antes y fallan con los íconos actuales de Laravel. | ✅ |
| IV. Tipado estricto | La prop `size` está tipada como unión literal en TS. Sin PHP de dominio nuevo. | ✅ N/A |
| V. Simplicidad / YAGNI | Manifiesto mínimo (sin PWA ni service worker). Se extiende `AppLogo` en vez de crear otro componente. Se publica solo `header.blade.php`. | ✅ |
| Quality gates | Pint (si aplica), Pest y `vue-tsc`. Validación end-to-end en quickstart. | ✅ |

**Re-check post-diseño (Phase 1)**: sin violaciones. Complexity Tracking vacío.

## Project Structure

### Documentation (this feature)

```text
specs/029-brand-logo-favicon/
├── plan.md
├── research.md          # D1–D7
├── data-model.md        # Activos de marca
├── quickstart.md
├── contracts/
│   └── brand-assets.md  # <head>, manifiesto, AppLogo, encabezado de correo
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
scripts/brand/generate-icons.py                     # NUEVO: genera todos los íconos desde el maestro

public/
├── favicon.svg                                     # REEMPLAZAR (Laravel → Eunox)
├── favicon.ico                                     # REEMPLAZAR (16/32/48)
├── apple-touch-icon.png                            # REEMPLAZAR (180, full-bleed)
├── icon-192.png                                    # NUEVO
├── icon-512.png                                    # NUEVO
├── site.webmanifest                                # NUEVO
└── images/eunox-mail-logo.png                      # NUEVO (96 px)

resources/views/app.blade.php                       # sizes del .ico, manifest, theme-color
resources/views/vendor/mail/html/header.blade.php   # NUEVO: logo + nombre en correos

resources/js/Components/AppShell/AppLogo.vue        # prop size: 'sm' | 'lg'
resources/js/Components/Auth/AuthCard.vue           # usar <AppLogo size="lg" />
resources/js/Components/Auth/AuthHeader.vue         # logo + nombre con enlace a home

tests/Feature/Branding/BrandIconsTest.php           # NUEVO
tests/Feature/Mail/MailBrandHeaderTest.php          # NUEVO
```

**Structure Decision**: monolito existente. Los activos van en `public/`
(servidos tal cual por nginx), el script en `scripts/brand/` (fuera del
runtime) y el override de correo en la ruta estándar `resources/views/vendor/mail`.

## Complexity Tracking

Sin violaciones; no aplica.
