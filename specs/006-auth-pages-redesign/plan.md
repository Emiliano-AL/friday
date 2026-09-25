# Implementation Plan: Rediseño del layout de páginas de autenticación

**Branch**: `006-auth-pages-redesign` | **Date**: 2026-09-24 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/006-auth-pages-redesign/spec.md`

## Summary

Rediseño puramente visual de las 4 páginas de autenticación de invitado (login, registro, forgot-password, reset-password) para replicar el layout del diseño de referencia (`code.html` / `screen.png`): shell de marca con header/footer, tarjeta blanca centrada de ~440px con halo de acento, botón Google con distintivo "Recomendado", campos con iconos y toggle de visibilidad de contraseña, y tokens de `DESIGN.md` (Inter, indigo `#5B5BD6`, superficie `#F9F9FF`). Cero cambios en rutas, controladores, validaciones ni tests de comportamiento.

## Technical Context

**Language/Version**: PHP 8.4 / Vue 3 (`<script setup>`) / TypeScript

**Primary Dependencies**: Laravel 13 + Inertia.js v3 (existentes), Tailwind CSS v4 (existente, sin `tailwind.config.js`), Wayfinder (`@/routes/`), Laravel Breeze + Socialite (existentes, sin cambios)

**Storage**: N/A (sin cambios de datos; las páginas usan los props existentes: `canResetPassword`, `status`, `email`, `token`)

**Testing**: Pest — suite existente `tests/Feature/Auth/*` debe pasar sin modificación; verificación visual manual guiada por `quickstart.md` + Lighthouse accesibilidad

**Target Platform**: Navegador web, responsive 320px → escritorio (Herd local, macOS)

**Project Type**: web-application (monolito Laravel + Inertia, según Constitución I)

**Performance Goals**: Sin regresión de rendimiento perceptible; fuente Inter con `display=swap`; CSS adicional limitado a tokens + componentes auth (~pocos KB)

**Constraints**: Sin dependencias Composer/npm nuevas (Constitución II); sin cambios de comportamiento (FR-005); sin efectos colaterales en páginas autenticadas (FR-009); Pint en PHP modificado

**Scale/Scope**: 4 páginas Vue, 1 layout, ~6 componentes nuevos bajo `Components/Auth/`, tokens CSS en `app.css`, 1 `<link>` de fuente en `app.blade.php`

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| # | Principle | Status | Notes |
| --- | --------- | ------ | ----- |
| I | Monolith-First con Inertia | PASS | Solo páginas Vue servidas por Inertia; sin API desacoplada |
| II | Alineación ecosistema Laravel | PASS | Sin dependencias nuevas; Inter vía Google Fonts `<link>` (no es paquete). Tokens en `@theme` de Tailwind v4 (idiomático) |
| III | Test-First con Pest | PASS | No hay lógica de dominio nueva; los 4 tests de feature de Auth existentes actúan como red de regresión y deben seguir en verde sin cambios |
| IV | Tipado estricto / enums | PASS | Sin PHP nuevo; componentes Vue con `lang="ts"` y props tipadas |
| V | Simplicidad / YAGNI | PASS | Solo presentación de 4 páginas + shell compartido; sin tests Dusk ni snapshot visual (justificado en research.md §5) |

Re-evaluación post-Phase 1: sin violaciones que justificar. Complejidad Tracking: no aplica.

## Project Structure

### Documentation (this feature)

```text
specs/006-auth-pages-redesign/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output (N/A — sin entidades nuevas)
├── quickstart.md        # Phase 1 output
├── contracts/
│   └── auth-pages-ui.md # Phase 1 output — contrato UI por página
└── tasks.md             # Phase 2 output (no creado por este comando)
```

### Source Code (repository root)

```text
resources/
├── css/
│   └── app.css                    # + bloque @theme con tokens de DESIGN.md
├── views/
│   └── app.blade.php              # + <link> Google Fonts (Inter)
└── js/
    ├── Layouts/
    │   └── GuestLayout.vue        # reestilizado: header marca, tarjeta centrada, halo, footer
    ├── Components/Auth/           # NUEVO — acotado a páginas de auth
    │   ├── AuthCard.vue           # tarjeta blanca 440px, sombra suave, logo + título + subtítulo
    │   ├── AuthHeader.vue         # marca "Friday" + indicador SSL (desktop)
    │   ├── AuthFooter.vue         # enlaces legales + idioma + píldora estado
    │   ├── SocialGoogleButton.vue # botón Google + distintivo "Recomendado"
    │   ├── AuthTextInput.vue      # campo con icono, estados focus, slot acción (toggle ojo)
    │   └── AuthCheckbox.vue       # casilla estilo design system
    └── pages/Auth/
        ├── Login.vue              # reestructurado con componentes nuevos + toggle visibilidad
        ├── Register.vue           # idem (nombre, correo, password, confirmación)
        ├── ForgotPassword.vue     # idem (correo + status)
        └── ResetPassword.vue      # idem (nueva contraseña ×2 + toggle ×2 + token oculto)
```

**Estructura de archivos afectada**: únicamente frontend bajo `resources/`; ningún archivo PHP del dominio.

**Decision de estructura**: Componentes nuevos bajo `Components/Auth/` (no restilizar los Breeze globales) porque `TextInput`/`InputLabel`/`PrimaryButton`/`Checkbox` también se usan en `pages/Projects/*` y el alcance (FR-009) prohíbe efectos colaterales. `GuestLayout.vue` es seguro de reestilizar globalmente: solo lo consumen las 4 páginas Auth (verificado).

## Complexity Tracking

Sin violaciones constitucionales que justificar.
