# Implementation Plan: Look & Feel del workspace autenticado

**Branch**: `007-workspace-look-feel` | **Date**: 2026-09-25 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/007-workspace-look-feel/spec.md`

## Summary

Rediseñar la capa de presentación del área autenticada de Friday: un nuevo shell de aplicación (barra lateral de 256px + encabezado fijo + área de contenido fluida), paleta de comandos cliente-only (⌘K), popovers de perfil y notificaciones (estado vacío), y una página de inicio con lienzo de bienvenida, todo fiel al diseño de referencia de Stitch y a los tokens de `DESIGN.md` en modo claro. No hay cambios de esquema ni de lógica de dominio: se reutilizan sesión, usuario y proyectos existentes; las secciones sin destino se muestran deshabilitadas con indicador "Próximamente".

Enfoque técnico: se reescribe `resources/js/Layouts/AuthenticatedLayout.vue` componiéndolo con componentes nuevos en `resources/js/Components/AppShell/`, se extienden los tokens CSS-first de Tailwind v4 ya existentes (`@theme` en `app.css`), se carga la fuente de iconos Material Symbols (Outlined) mediante el patrón Bunny Fonts ya usado en `vite.config.ts`, y se comparte un resumen de proyectos del usuario vía `HandleInertiaRequests` para alimentar el selector de contexto. Las cinco páginas que usan el layout no cambian de estructura; solo `AppHome.vue` se reescribe con el lienzo de bienvenida.

## Technical Context

**Language/Version**: PHP 8.4 (sin lógica nueva de dominio) y TypeScript + Vue 3.5 (`<script setup lang="ts">`).

**Primary Dependencies**: Laravel 13 + Inertia.js v3 (`@inertiajs/vue3 ^3.0`) con SSR habilitado; Tailwind CSS v4.1 CSS-first (sin `tailwind.config`); Wayfinder (`@laravel/vite-plugin-wayfinder`) para rutas tipadas (`@/routes`); `clsx` + `tailwind-merge` vía `cn()` en `resources/js/lib/utils.ts`. **Sin paquetes npm nuevos.**

**Storage**: Sin cambios. SQLite en desarrollo / PostgreSQL por constitución; solo lectura de modelos existentes (`User`, `Project`).

**Testing**: Pest (tests de feature PHP). Suite existente en `tests/Feature/{Auth,Projects,Sprints,Tasks}` debe seguir en verde; se agregan tests de render del shell. No hay pruebas de navegador en el stack (no se añade Dusk — violaría Constitución II/V); la verificación visual es manual vía `quickstart.md` y logs de navegador de Boost.

**Target Platform**: Navegadores web modernos, escritorio primero (≥1024px), con adaptaciones tablet (768–1023px) y móvil (<768px).

**Project Type**: Web application monolítica (Laravel + Inertia).

**Performance Goals**: Interacciones del shell con percepción instantánea (animaciones ≤200ms según `DESIGN.md`); primera carga sin regresión perceptible (fuentes e iconos vía preload de Bunny).

**Constraints**: Modo claro únicamente (modo oscuro fuera de alcance); sin cambios de backend funcionales (solo compartir datos existentes); accesibilidad WCAG AA (foco visible, `ESC`, contraste); ningún dato simulado (campana en estado vacío, sin contadores falsos).

**Scale/Scope**: 1 layout reescrito, ~10 componentes Vue nuevos, 1 página reescrita (`AppHome`), ~30 líneas de cambio en `HandleInertiaRequests`, extensiones de tokens en `app.css`, 1 archivo de tipos, tests de feature nuevos pequeños.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| #   | Principio                            | Resultado             | Notas                                                                                                                                                                                                                                                                              |
| --- | ------------------------------------ | --------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| I   | Monolith-First con Inertia           | ✅ PASS               | El shell es un layout Inertia compuesto por componentes Vue; la paleta de comandos es cliente-only. Nada de API desacoplada.                                                                                                                                                       |
| II  | Alineación con el ecosistema Laravel | ✅ PASS               | Solo herramientas del stack fijado (Tailwind v4, Inertia v3, Wayfinder, Vite). Material Symbols se carga por Bunny Fonts, el mismo mecanismo ya configurado para Inter/Instrument Sans — sin paquetes npm nuevos ni cambios de dependencias.                                       |
| III | Test-First con Pest                  | ✅ PASS (justificado) | La feature no introduce lógica de dominio (la paleta y las notificaciones son presentación cliente-only). Cobertura: suite existente completa en verde + tests de feature de render del shell (páginas autenticadas responden con las props compartidas nuevas). No se añade Dusk. |
| IV  | Tipado estricto y enums              | ✅ PASS               | N/A en PHP (sin estados de dominio nuevos). En frontend: props tipadas (`NavItem`, `CommandAction`, `ProjectSummary`) y tipos compartidos en `resources/js/types/shell.ts`.                                                                                                        |
| V   | Simplicidad y YAGNI                  | ✅ PASS               | Paleta cliente-only sin backend, notificaciones en estado vacío, secciones inexistentes deshabilitadas. Se reutilizan tokens y el patrón de iconos de 006. Nada de abstracciones anticipadas.                                                                                      |

Re-evaluación post-diseño (Phase 1): los gates se mantienen; el diseño no introdujo desviaciones. Sin violaciones → sin tabla de Complejidad.

## Project Structure

### Documentation (this feature)

```text
specs/007-workspace-look-feel/
├── plan.md              # This file (/skill:speckit-plan command output)
├── research.md          # Phase 0 output (/skill:speckit-plan command)
├── data-model.md        # Phase 1 output (/skill:speckit-plan command)
├── quickstart.md        # Phase 1 output (/skill:speckit-plan command)
├── contracts/           # Phase 1 output (/skill:speckit-plan command)
│   └── shell-ui.md
├── checklists/
│   └── requirements.md
└── spec.md
```

### Source Code (repository root)

```text
app/
└── Http/Middleware/
    └── HandleInertiaRequests.php     # + prop compartida `projects` (id, title) solo para usuarios autenticados

resources/
├── css/
│   └── app.css                       # @theme: + radius/shadow tokens (escala DESIGN.md), clase .material-symbols-outlined
├── js/
│   ├── Components/
│   │   └── AppShell/                 # componentes nuevos del shell
│   │       ├── AppIcon.vue           # wrapper de Material Symbols (Outlined) por nombre
│   │       ├── AppShellSidebar.vue   # región lateral completa
│   │       ├── ContextSwitcher.vue   # selector de proyecto/contexto (estado vacío incluido)
│   │       ├── SidebarNav.vue        # items de navegación (activo, hover, "Próximamente")
│   │       ├── SidebarUserCard.vue   # tarjeta de usuario al fondo
│   │       ├── AppShellTopbar.vue    # encabezado (toggle, búsqueda ⌘K, campana, ayuda, avatar)
│   │       ├── NotificationsPopover.vue  # popover estado vacío
│   │       ├── ProfileMenu.vue       # menú de perfil (logout real; resto "Próximamente")
│   │       ├── CommandPalette.vue    # paleta ⌘K cliente-only
│   │       └── HomeCanvas.vue        # lienzo de bienvenida de la página de inicio
│   ├── Layouts/
│   │   └── AuthenticatedLayout.vue   # reescrito: compone las regiones del shell (mismo nombre: 0 churn en páginas)
│   ├── pages/
│   │   ├── AppHome.vue               # reescrito: HomeCanvas dentro del shell
│   │   └── Projects/                 # sin cambios de estructura (Index/Show/Create/Edit usan el nuevo layout)
│   ├── types/
│   │   └── shell.ts                  # NavItem, CommandAction, ProjectSummary, SharedShellProps
│   └── lib/utils.ts                  # cn() existente (composición de clases)
└── views/                             # sin cambios

tests/
└── Feature/
    └── Shell/
        └── ShellRenderingTest.php    # render de páginas autenticadas + props compartidas
```

**Structure Decision**: estructura única de monolito (Option 1). Los componentes del shell viven en `resources/js/Components/AppShell/` — un solo directorio nuevo, explícito y descubrible — sin crear `components/ui/` (la reserva en `vite.config.ts` queda para primitives estilo shadcn si en el futuro se adoptan). El layout conserva su nombre y ruta actuales para no tocar las 5 páginas consumidoras; el layout actual (Breeze, basado en grises) es reemplazado por completo, y los componentes legacy de Breeze quedan intactos salvo que otra spec los elimine.
