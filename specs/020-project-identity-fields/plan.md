# Implementation Plan: Identidad y fecha de término del proyecto

**Branch**: `020-project-identity-fields` | **Date**: 2026-10-09 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/020-project-identity-fields/spec.md`

## Summary

Activar los cuatro campos que el formulario de proyecto anuncia como "Próximamente":
**clave** (`key`), **color de acento** (`color`), **icono** (`icon`) y **fecha de
término** (`target_date`). Son greenfield: ni columnas, ni payloads, ni tipos los
conocen hoy. El plan añade una migración con 4 columnas **nulables** (cero backfill:
los proyectos existentes usan defaults a nivel de presentación), enums PHP para la
paleta y el conjunto de iconos (Constitución IV), validación en los Form Requests
existentes (`StoreProjectRequest`/`UpdateProjectRequest`), normalización de la clave
a mayúsculas con unicidad por propietario, y visualización en la ficha (clave junto
al título, icono+color en el tile del header, "Fecha Objetivo" real con estado
"Vencida") y en la navegación (`ContextSwitcher` vía la prop compartida `projects`).
Sin dependencias nuevas.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13.32), Vue 3 + Inertia v3, Tailwind v4,
Wayfinder, Pest, PostgreSQL. Sin cambios de stack.

**Primary Dependencies**: Eloquent + migración; enums PHP nuevos (`ProjectColor`,
`ProjectIcon`); Form Requests existentes; `ProjectController` (store/update/payloads);
`HandleInertiaRequests::projectSummaries()`; `ProjectFormModal.vue`,
`Projects/Show.vue`, `ContextSwitcher.vue`. Sin paquetes nuevos.

**Storage**: PostgreSQL. Migración `2026_10_09_000000_add_identity_fields_to_projects_table`:
`key` string(5) nullable, `color` string(7) nullable, `icon` string(32) nullable,
`target_date` date nullable. Nada de backfill obligatorio.

**Testing**: Pest. Tests nuevos en `tests/Feature/Projects/ProjectCrudTest.php`
(validación, normalización, unicidad, permisos) y aditivos en payload/shell tests.
Aserciones existentes son por campo (aditivo-seguro).

**Target Platform**: Web monolítica Inertia.

**Project Type**: web-app monolito (Constitución I).

**Performance Goals**: Estándar web. El payload compartido `projects` del sidebar
sube de 2 a 4 campos por fila (límite 50 filas) — impacto despreciable.

**Constraints**: Constitución IV (paleta e iconos como enums, no strings sueltos);
V (campos opcionales en conjunto, defaults presentacionales, sin migración de datos
del usuario). La unicidad por propietario se valida a nivel aplicación (volumen
personal, sin condiciones de carrera realistas).

**Scale/Scope**: 1 migración, 2 enums, 2 Form Requests, 1 controlador (store/update/
3 payloads), 1 modal, 2 páginas/componentes de visualización (Show, ContextSwitcher),
3 tipos TS, ~2 archivos de tests. Todo aditivo.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Evaluación |
| --- | --- |
| I. Monolith-First con Inertia | PASS — Todo fluye por rutas/payloads Inertia existentes; sin API nueva. |
| II. Alineación Ecosistema Laravel | PASS — `artisan make:migration`, enums PHP, Form Requests, casts. Sin dependencias nuevas. |
| III. Test-First con Pest | PASS — Tests nuevos para validación/normalización/unicidad y presencia en payloads; existentes intactos. |
| IV. Tipado Estricto y Enums | PASS — `ProjectColor` y `ProjectIcon` como string-backed enums (paleta cerrada y conjunto de iconos); `target_date` cast `date`; `key` string normalizado. |
| V. Simplicidad y YAGNI | PASS — Campos nulables con defaults presentacionales (sin backfill); paleta/iconos cerrados; la vista de directorio (`Projects/Index`) queda fuera (FR-009 habla de la navegación). |

Sin violaciones. Complexity Tracking: no aplica.

**Post-design re-check (tras Phase 1)**: el diseño mantiene el monolito, no añade
dependencias, cierra valores discretos en enums, y cada regla de validación tiene
prueba. PASS.

## Project Structure

### Documentation (this feature)

```text
specs/020-project-identity-fields/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── contracts/           # Phase 1 output
├── quickstart.md        # Phase 1 output
└── tasks.md             # Phase 2 output (speckit-tasks)
```

### Source Code (repository root)

```text
app/
├── Enums/
│   ├── ProjectColor.php               # NUEVO: 8 colores de la paleta (hex como valor)
│   └── ProjectIcon.php               # NUEVO: 8 iconos Material Symbols (name como valor)
├── Http/
│   ├── Controllers/
│   │   └── ProjectController.php     # EXISTENTE: store/update persisten 4 camos; payloads (show/index/summaries) los exponen
│   ├── Middleware/
│   │   └── HandleInertiaRequests.php # EXISTENTE: projectSummaries() añade color, icon
│   └── Requests/
│       ├── StoreProjectRequest.php   # EXISTENTE: reglas key/color/icon/target_date
│       └── UpdateProjectRequest.php  # EXISTENTE: idem + ignore(self) en unicidad de key
└── Models/
    └── Project.php                   # EXISTENTE: fillable + casts (color/icon enums, target_date date)

database/
└── migrations/
    └── 2026_10_09_000000_add_identity_fields_to_projects_table.php  # NUEVO

resources/js/
├── components/
│   ├── AppShell/
│   │   └── ContextSwitcher.vue       # EXISTENTE: tiles con icono/color del proyecto
│   └── Projects/
│       └── ProjectFormModal.vue      # EXISTENTE: pickers habilitados, sin "Próximamente"
├── pages/
│   └── Projects/
│       ├── Index.vue                 # EXISTENTE: pasa identidad al modal de edición
│       └── Show.vue                  # EXISTENTE: clave junto al título, tile con icono/color, Fecha Objetivo real
└── types/
    ├── project.ts                    # EXISTENTE: ProjectDetail/DirectoryProject + identity
    └── shell.ts                      # EXISTENTE: ProjectSummary + color/icon

tests/
└── Feature/
    ├── Projects/
    │   └── ProjectCrudTest.php       # EXISTENTE: validación/normalización/unicidad/permisos
    ├── Shell/
    │   └── ShellRenderingTest.php    # EXISTENTE: + color/icon en prop projects (aditivo)
    └── ...payload tests aditivos
```

**Structure Decision**: estructura existente; los enums viven en `app/Enums` junto a
los demás; las listas cerradas se duplican de forma controlada en TS (con tests
pinchando el lado servidor, ver research D2).

## Complexity Tracking

> Sin violaciones constitucionales. Alternativas rechazadas en
> [research.md](research.md).
