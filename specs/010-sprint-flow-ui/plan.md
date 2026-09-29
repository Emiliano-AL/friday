# Implementation Plan: Flujo de Sprint en la UI (010)

**Branch**: `010-sprint-flow-ui` | **Date**: 2026-09-28 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/010-sprint-flow-ui/spec.md`

## Summary

Entregar el flujo de sprint en la UI según el diseño de referencia: (1) página de
detalle del sprint con encabezado (nombre, estado, iteración, fechas, días
restantes, % transcurrido, objetivo), métricas reales (cobertura, desglose por
estado) y lista de tareas con búsqueda/filtros/agrupación; (2) diálogo de
creación/edición con nombre, objetivo (campo nuevo), fechas y opción "comenzar
inmediatamente"; (3) ciclo de vida real del sprint — `SprintStatus`
(planificado → activo → completado, solo hacia adelante) — con acciones de
activar y completar. Todo sobre el stack fijo del proyecto (Laravel + Inertia +
Vue + Pest), sin dependencias nuevas, reutilizando los componentes de tarea de
la feature 009 y el patrón "Próximamente" deshabilitado para lo que aún no
existe en los datos.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13) + Vue 3 (`<script setup>`) con Inertia v3 y TypeScript estricto.

**Primary Dependencies**: Eloquent, form requests, policies (backend); Tailwind CSS con tokens de `DESIGN.md`, Wayfinder `@/routes` (frontend). Sin dependencias nuevas.

**Storage**: PostgreSQL (default `pgsql`); tests con sqlite en memoria vía `phpunit.xml`.

**Testing**: Pest — tests de feature con factories (RefreshDatabase). Regla test-first: rojo → implementación → verde.

**Target Platform**: Aplicación web servida por Herd/artisan (desarrollo local), navegadores modernos (Chrome/Edge/Safari/Firefox).

**Project Type**: Web application (monolito Inertia: rutas → controladores → `Inertia::render`).

**Performance Goals**: Detalle del sprint con hasta 100 tareas visible en < 2 s (SC-002); listas filtran/agrupan en cliente al instante.

**Constraints**: Solo modo claro (DESIGN.md); "Próximamente" = control deshabilitado real sin foco ni datos simulados; Pint obligatorio en PHP modificado (`vendor/bin/pint --dirty`); gates `npm run check:fix`, `npm run types:check`, `npm run build`.

**Scale/Scope**: 1 página nueva (detalle), 1 diálogo nuevo (sprint), 1 enum + 2 columnas en `sprints`, 2 endpoints de transición + 1 de lectura, ~6 componentes Vue, ~4 archivos de test.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principle                     | Verdict | Notes                                                                                                                                                                                               |
| ----------------------------- | ------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| I. Monolith-First con Inertia | ✅ PASS | Página nueva vía `Inertia::render`; transiciones como rutas web con redirect+flash; cero API desacoplada.                                                                                           |
| II. Alineación Ecosistema     | ✅ PASS | `artisan make:*`, form requests, policy, enum; **sin dependencias nuevas** (Composer/npm).                                                                                                          |
| III. Test-First con Pest      | ✅ PASS | Tests de feature planeados para ciclo de vida, objetivo, detalle (métricas reales) y permisos; suite completo en verde antes de cerrar.                                                             |
| IV. Tipado Estricto y Enums   | ✅ PASS | `SprintStatus` enum PHP (valores `planned/active/completed`, coherentes con `ProjectStatus`/`TaskStatus`); tipos TS para props (reuso de `BoardTask`).                                              |
| V. Simplicidad y YAGNI        | ✅ PASS | Alcance = spec: sin puntos/burndown/velocity (quedan "Próximamente"), sin vista transversal de sprints, sin diálogo nuevo de tarea. Campos `goal`/`status` son FR-006/FR-007, no oro de ingeniería. |

_Post-design re-check (Phase 1): sin desviaciones introducidas._

## Project Structure

### Documentation (this feature)

```text
specs/010-sprint-flow-ui/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
└── contracts/           # Phase 1 output
```

### Source Code (repository root)

```text
app/
├── Enums/
│   └── SprintStatus.php                      # NUEVO: planned/active/completed + labels
├── Http/Controllers/
│   ├── ProjectSprintController.php           # EDIT: +show, +start, +complete, goal/start_now en store
│   └── TaskController.php                    # sin cambios (reuso desde detalle)
├── Http/Requests/
│   ├── StoreSprintRequest.php                # EDIT: +goal, +start_now
│   └── UpdateSprintRequest.php               # EDIT: +goal
├── Models/
│   ├── Sprint.php                            # EDIT: status cast + goal, helpers de transición
│   └── Project.php                           # EDIT: activeSprint() pasa a por-status
└── Policies/
    └── SprintPolicy.php                      # EDIT/VERIFICAR: bloqueo cuando sprint completado

database/migrations/
└── 2026_09_28_000000_add_goal_and_status_to_sprints.php   # NUEVO

resources/js/
├── pages/Sprints/
│   └── Show.vue                              # NUEVO: detalle del sprint
├── Components/Sprints/
│   ├── SprintFormModal.vue                   # NUEVO: crear/editar (nombre, objetivo, fechas, comenzar ya)
│   ├── SprintHeader.vue                      # NUEVO: breadcrumb, nombre, chip estado, fechas, acciones
│   ├── SprintMetricsPanel.vue                # NUEVO: cobertura + desglose + totales (datos reales)
│   ├── SprintTaskList.vue                    # NUEVO: buscador ⌘F, filtros, agrupación, estados vacíos
│   └── SprintComingSoon.vue                  # NUEVO: tarjeta deshabilitada con chip "Próximamente" (burndown, daily, bitácora, carga)
├── Components/Tasks/Board/TaskFormModal.vue  # EDIT: props initialProject/initialSprint (preselección)
└── pages/Projects/Show.vue                   # EDIT: modal inline → SprintFormModal; filas de sprint enlazan al detalle

routes/web.php                                # EDIT: projects.sprints.show + start + complete

tests/Feature/Projects/ (o Tests/Feature/Sprints/)
├── SprintLifecycleTest.php                   # NUEVO: transiciones, solo-adelante, permisos, proyecto activo
├── SprintGoalTest.php                        # NUEVO: validación goal/fechas en store/update
├── SprintDetailTest.php                      # NUEVO: acceso, payload, métricas reales, numeración de iteración
└── SprintStoreStartNowTest.php               # NUEVO: start_now → status activo
```

**Structure Decision**: Monolito único (Option 1). Los componentes Vue nuevos viven en `Components/Sprints/` (convención por dominio, como `Components/Tasks/Board/` y `Components/Projects/`) y la página en `pages/Sprints/Show.vue` (convención Inertia del proyecto: `pages/<Dominio>/Accion.vue`). El detalle reutiliza `BoardTask`, `TaskFormModal` y `TaskCommentsModal` de la feature 009 en vez de duplicar.

## Complexity Tracking

> Sin violaciones constitucionales; sección vacía.
