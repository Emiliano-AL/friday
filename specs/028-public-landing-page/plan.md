# Implementation Plan: Landing page pública en la ruta raíz

**Branch**: `028-public-landing-page` | **Date**: 2026-10-16 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/028-public-landing-page/spec.md`

## Summary

Mostrar en `/` una landing minimalista de Eunox a los visitantes sin sesión,
basada en la pantalla de Stitch "Friday — Landing Page (Minimalista & Simple)"
y el design system del proyecto Stitch. Los usuarios autenticados siguen
viendo su dashboard. Enfoque técnico: quitar `auth` de la ruta `home` y
bifurcar en `HomeController` (invitado → `Inertia::render('Landing')`;
autenticado → flujo actual). La página `Landing.vue` es estática, se compone
de secciones en `components/Landing/` y usa los tokens de Tailwind ya mapeados
desde Stitch ([research.md](./research.md) D1–D3).

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13), TypeScript + Vue 3 (`<script setup>`)

**Primary Dependencies**: Inertia.js v3, Tailwind CSS v4 (tokens `@theme` en `resources/css/app.css`), Wayfinder (`@/routes`). Sin dependencias nuevas.

**Storage**: N/A (página estática, sin consultas)

**Testing**: Pest (feature tests con aserciones Inertia), `npm run check`

**Target Platform**: Navegadores web modernos; desplegado en Laravel Forge (`www.eunox.app`)

**Project Type**: Monolito web Laravel + Inertia

**Performance Goals**: titular y CTA visibles en < 2 s (SC-004); sin imágenes rasterizadas pesadas (la vista previa del producto es markup/SVG)

**Constraints**: solo modo claro (igual que la app, D4); español; nada de "Friday" en textos visibles (guardia `NoLegacyBrandInUiTest`); 375 px → ≥1280 px sin scroll horizontal

**Scale/Scope**: 1 página, unos 6 componentes de sección, 1 cambio de ruta/controlador, 1 archivo de tests

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio                     | Evaluación                                                                                                  | Estado |
| ----------------------------- | ----------------------------------------------------------------------------------------------------------- | ------ |
| I. Monolith-First con Inertia | La landing es una página Inertia renderizada por `HomeController`. No hay SPA ni API nuevas.                | ✅     |
| II. Ecosistema Laravel        | Ruta y controlador existentes, Vite y Tailwind. Sin dependencias Composer/npm nuevas.                       | ✅     |
| III. Test-First con Pest      | `LandingPageTest` se escribe primero (invitado → `Landing`, auth → `AppHome`) y se actualiza `ExampleTest`. | ✅     |
| IV. Tipado estricto / enums   | No hay estados de dominio nuevos. El controlador mantiene los type hints.                                   | ✅ N/A |
| V. Simplicidad / YAGNI        | Página estática, sin menú móvil, precios, newsletter ni modo oscuro. Se reutilizan los tokens y `AppLogo`.  | ✅     |
| Stack fijo                    | Vue 3 + Inertia v3 + Tailwind + Wayfinder.                                                                  | ✅     |
| Quality gates                 | Pint (`--dirty`), Pest y `npm run check`. Escenario end-to-end en quickstart.                               | ✅     |

**Re-check post-diseño (Phase 1)**: sin violaciones. El diseño no agrega
entidades, dependencias ni capas. Complexity Tracking vacío.

## Project Structure

### Documentation (this feature)

```text
specs/028-public-landing-page/
├── plan.md              # Este archivo
├── research.md          # Phase 0: decisiones D1–D7
├── data-model.md        # Phase 1: sin entidades (página estática)
├── quickstart.md        # Phase 1: guía de validación
├── contracts/
│   └── root-route.md    # Phase 1: contrato HTTP/UI de GET /
├── design/              # Exportación de Stitch (HTML + captura), pendiente del usuario
└── tasks.md             # Phase 2 (/speckit-tasks)
```

### Source Code (repository root)

```text
routes/web.php                                  # quitar middleware auth de la ruta home
app/Http/Controllers/HomeController.php         # invitado → Inertia::render('Landing')

resources/js/pages/
├── Landing.vue                                 # NUEVO: compone las secciones + <Head> SEO
└── Welcome.vue                                 # ELIMINAR (scaffolding sin uso, D6)

resources/js/components/Landing/                # NUEVO
├── LandingHeader.vue
├── LandingHero.vue                             # incluye la vista previa estática del producto
├── LandingFeatures.vue                         # id="caracteristicas"
├── LandingWorkflow.vue                         # id="como-funciona"
├── LandingCta.vue
└── LandingFooter.vue

tests/Feature/
├── Landing/LandingPageTest.php                 # NUEVO
└── ExampleTest.php                             # actualizar (ya no hay redirección al login)
```

**Structure Decision**: monolito Laravel + Inertia existente. La landing vive
como página Inertia más componentes de sección bajo `resources/js/components/Landing/`,
siguiendo la convención por dominio de `components/Home`, `components/Auth`,
etc.

## Riesgos y dependencias

- **Diseño de Stitch no descargable sin credenciales** (research D2): exportar
  el HTML y la captura a `design/` antes de maquetar, o aceptar una pasada de
  fidelidad posterior.
- **Caché de rutas en producción**: el deploy de Forge ejecuta
  `route:cache`/`config:cache`, así que no requiere pasos manuales.

## Complexity Tracking

Sin violaciones; no aplica.
