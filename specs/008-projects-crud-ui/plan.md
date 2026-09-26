# Implementation Plan: Rediseño del CRUD de Proyectos

**Branch**: `[008-projects-crud-ui]` | **Date**: 2026-09-25 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/008-projects-crud-ui/spec.md`

## Summary

Rediseño integral del flujo de proyectos (directorio, creación, edición y detalle) dentro del shell construido en la feature 007, siguiendo las referencias visuales de Stitch (directorio minimalista + detalle completo) y el sistema de diseño de `DESIGN.md`. Todo con datos reales del dominio existente: progreso calculado desde tareas, sprint activo derivado por heurística de fechas, contadores y KPIs computados. Las propiedades que aún no existen (clave, color, icono, fecha objetivo, prioridad) y las secciones futuras (documentación, recursos, historial, compartir, métricas de salud) se documentan y se muestran en la UI como "Próximamente" deshabilitados, sin persistencia ni datos simulados. Sin cambios de esquema ni dependencias nuevas.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13) + Vue 3 Composition API con TypeScript (`<script setup lang="ts">`) e Inertia.js v3.

**Primary Dependencies**: Inertia v3 + Wayfinder (`@/routes/`, `@/actions/`), Tailwind CSS v4 (tokens `@theme` en `resources/css/app.css`), Pest (tests), componentes AppShell de la feature 007 (`AppIcon`, `Avatar`, `CommandPalette`, patrones de overlay). Sin dependencias nuevas (Composer/npm).

**Storage**: SQLite en desarrollo (PostgreSQL en producción, según constitución); esquema **sin cambios** — la feature solo añade derivaciones de lectura sobre entidades existentes.

**Testing**: Pest (`php artisan test --compact`), feature tests bajo `tests/Feature/Projects/`. Test-first: tests de payload/progreso actualizados antes de la implementación.

**Target Platform**: Web responsive (desktop primero, usable a 375px), modo claro únicamente.

**Project Type**: Web application (monolito Laravel + Inertia).

**Performance Goals**: Interacciones del directorio (búsqueda, filtros, orden) resueltas client-side de forma instantánea sobre el payload completo (los proyectos del usuario están acotados en la práctica; el shell ya limita el switcher a 50).

**Constraints**: Sin scroll horizontal a 375px; contraste AA sobre tokens `surface`; foco de teclado gestionado en diálogos; cero datos simulados (todo lo no disponible se marca "Próximamente").

**Scale/Scope**: 4 páginas afectadas (`Projects/Index`, `Projects/Show`, `Projects/Create`, `Projects/Edit`), ~12 componentes nuevos en `resources/js/Components/Projects/`, 1 componente compartido (`FlashMessage` en AppShell), 1 componente Breeze reestilizado (`Modal.vue`), ajustes en `ProjectController`, `HandleInertiaRequests`, `Project` (modelo), tipos en `resources/js/types/project.ts` (nuevo).

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio                                | Evaluación                                                                                                                                                                                                             |
| ---------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| I. Monolith-First con Inertia            | ✅ Cumple: server rendering vía `Inertia::render()`, estado por props, cero API desacoplada.                                                                                                                           |
| II. Alineación con el ecosistema         | ✅ Cumple: no se agregan ni cambian dependencias; se usan utilidades first-party (`useForm`, form requests, policy) y componentes existentes.                                                                          |
| III. Test-First con Pest                 | ✅ Cumple: `ProjectProgressTest` se amplía a la fórmula real; nuevo test de payload del directorio; tests de flash compartido. Tests → fallan → implementación → verde.                                                |
| IV. Tipado estricto y estados como enums | ✅ Cumple: `ProjectStatus`/`TaskStatus` existentes, sin estados nuevos; la heurística de "sprint activo" es una derivación de lectura documentada, no un estado de dominio. Tipos TS explícitos en `types/project.ts`. |
| V. Simplicidad y YAGNI                   | ✅ Cumple: sin migraciones, sin `SprintStatus`, sin librería de toasts, sin endpoints de búsqueda; confirmación de borrado con `confirm` nativo; KPIs limitados a los dos computables.                                 |

Sin violaciones → **Complexity Tracking** no aplica.

## Project Structure

### Documentation (this feature)

```text
specs/008-projects-crud-ui/
├── plan.md              # This file (/skill:speckit-plan command output)
├── research.md          # Phase 0 output (/skill:speckit-plan command)
├── data-model.md        # Phase 1 output (/skill:speckit-plan command)
├── quickstart.md        # Phase 1 output (/skill:speckit-plan command)
├── contracts/           # Phase 1 output (/skill:speckit-plan command)
└── tasks.md             # Phase 2 output (/skill:speckit-tasks command - NOT created by /skill:speckit-plan)
```

### Source Code (repository root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── ProjectController.php          # index/show payloads, create/edit → redirects, flash
│   └── Middleware/
│       └── HandleInertiaRequests.php      # + flash compartido
└── Models/
    └── Project.php                        # progressPercentage() real + activeSprint()

resources/js/
├── Components/
│   ├── Projects/                          # NUEVO — componentes del dominio proyecto
│   │   ├── ProjectFormModal.vue           # diálogo crear/editar + zona de peligro (solo líder)
│   │   ├── ProjectCard.vue                # tarjeta (vista cuadrícula)
│   │   ├── ProjectListRow.vue             # fila compacta (vista lista)
│   │   ├── ProjectContextMenu.vue         # menú ⋯ por tarjeta (acciones según estado/rol)
│   │   ├── ProjectsToolbar.vue            # búsqueda ⌘F, ordenamiento, conmutador vista, CTA
│   │   ├── FilterPills.vue                # píldoras de estado con contadores
│   │   ├── AvatarStack.vue                # avatares +N (líder + miembros)
│   │   ├── ProjectsKpiPanel.vue           # 2 KPIs reales + 2 "Próximamente"
│   │   ├── ProjectEmptyState.vue          # directorio vacío
│   │   ├── ProjectTabs.vue                # pestañas del detalle (3 futuras deshabilitadas)
│   │   └── UiBadge.vue                    # chip primitivo (estado / "Próximamente" / conteos)
│   ├── AppShell/
│   │   └── FlashMessage.vue               # banner de flash.success/error (layout-level)
│   └── Modal.vue                          # Breeze: reestilado a tokens (veil + shadow-modal)
├── types/
│   └── project.ts                         # NUEVO — DirectoryProject, counts, KPIs, payload Show
├── pages/
│   ├── Projects/
│   │   ├── Index.vue                      # reescrito: directorio completo
│   │   ├── Show.vue                       # reestructurado: metadatos + pestañas
│   │   ├── Create.vue                     # ELIMINADO (GET create → redirect ?new=1)
│   │   └── Edit.vue                       # ELIMINADO (GET edit → redirect show?edit=1)
│   └── AppHome.vue                        # sin cambios
└── Layouts/
    └── AuthenticatedLayout.vue            # + FlashMessage + acción paleta "Crear nuevo proyecto"

tests/
└── Feature/
    └── Projects/
        ├── ProjectProgressTest.php        # ampliado: fórmula real done/total
        ├── ProjectDirectoryTest.php       # NUEVO: payload del directorio (counts, KPIs, activeSprint, avatares, isOwner)
        └── ProjectFlashTest.php           # NUEVO: flash compartido en redirects de projects
```

**Structure Decision**: se crea un único directorio nuevo `resources/js/Components/Projects/` siguiendo la convención existente (`Components/Tasks/`, `Components/Auth/`, `Components/AppShell/`); los tipos viven en `resources/js/types/project.ts` como hizo la feature 007 con `types/shell.ts`. No se crean carpetas base nuevas.

## Complexity Tracking

> Sin violaciones de constitución — tabla no aplica.
