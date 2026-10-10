# Implementation Plan: Backlog General — Captura Rápida de Pendientes

**Branch**: `018-backlog-quick-capture` | **Date**: 2026-10-08 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/018-backlog-quick-capture/spec.md`

## Summary

Página global `/backlog` (bandeja de entrada) donde el usuario captura pendientes
en menos de 5 segundos (un campo de texto + Enter), los ve agrupados por prioridad
con filtros (Todas / Sin proyecto / Con proyecto) y conteos, y los organiza después
(asignación a proyecto, prioridad, puntos, responsable, entrega a sprint) desde el
formulario de tarea existente. Reutiliza el dominio de tareas ya existente:
`project_id` ya es nullable y las "tareas standalone" ya existen; el plan añade
**seguimiento de autoría** (`author_id`), **código legible** (`FRN-###`),
**puntos** (`points`), una consulta de backlog, relajación de la regla "self-assign
obligatorio" para la captura, y retorno automático de pendientes al backlog al
cerrar un sprint. Sin dependencias nuevas.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel Framework 13.32.0), tipado estricto con
enums para estados (Constitución IV).

**Primary Dependencies**: Inertia.js v3 + Vue 3 (`<script setup>`), Tailwind CSS v4
(`@theme` en `resources/css/app.css`), Wayfinder (`@/routes/`, `@/actions/`),
Eloquent, Form Requests, Policies. Sin dependencias nuevas (Constitución II/V).

**Storage**: PostgreSQL (única base de datos del proyecto). Migraciones nuevas:
`author_id` + `code` + `points` en `tasks`.

**Testing**: Pest, tests de feature con factories (`TaskFactory::standalone()` ya
existe). Suite completo con `php artisan test --compact` antes de cerrar.

**Target Platform**: Aplicación web monolítica servida por Laravel (Inertia SSR
vía `@inertiajs/vite`).

**Project Type**: web-service / web-app (monolito Inertia, sin API REST
desacoplada — Constitución I).

**Performance Goals**: Bandeja personal de trabajo; listas de cientos de ítems
como máximo. Precedente existente (`TaskController@index`, `Tasks/Index.vue`):
una sola prop Inertia con todas las tareas + filtros client-side. SC-001 exige
captura usable en <5 s; el render de la lista no tiene objetivo numérico especial.

**Constraints**: Constitución (monolito, enums, test-first, YAGNI). Convención
existente: no se introducen APIs JSON nuevas salvo necesidad (la edición/triage
fluye por rutas Inertia existentes de tareas).

**Scale/Scope**: 1 pantalla nueva (`/backlog`), 1 controlador nuevo
(`BacklogController@index`), reutilización de `tasks.store`/`tasks.update`/
`tasks.destroy`, 1 migración con 3 columnas, ~4-6 componentes Vue nuevos
(composer, secciones, banner), extensión puntual de `TaskFormModal` (campo
puntos) y de policies/requests. Cero dependencias nuevas.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Evaluación |
| --- | --- |
| I. Monolith-First con Inertia | PASS — Todo fluye por `Inertia::render()` y rutas web existentes; no hay API desacoplada. |
| II. Alineación Ecosistema Laravel | PASS — `artisan make:`, Eloquent scopes, Form Request, Policy, migración, Wayfinder. Sin paquetes nuevos. |
| III. Test-First con Pest | PASS — Tests de feature planeados para captura, visibilidad, triage, rollover de sprint y códigos. |
| IV. Tipado Estricto y Enums | PASS — Reutiliza `TaskPriority`, `TaskType`, `TaskStatus`, `SprintStatus`; nuevos campos con casts y validación estricta. |
| V. Simplicidad y YAGNI | PASS (con justificación registrada en research.md) — Se reutiliza `TaskFormModal` para el triage en lugar de construir un panel lateral nuevo; no se construye API de backlog. |

Sin violaciones que justificar. Complexity Tracking: no aplica.

**Post-design re-check (tras Phase 1)**: el diseño mantiene el monolito, no añade
dependencias, modela estados con enums existentes, y cada requisito funcional del
spec tiene prueba de feature asociada en quickstart.md. PASS.

## Project Structure

### Documentation (this feature)

```text
specs/018-backlog-quick-capture/
├── plan.md              # This file (/skill:speckit-plan command output)
├── research.md          # Phase 0 output (/skill:speckit-plan command)
├── data-model.md        # Phase 1 output (/skill:speckit-plan command)
├── quickstart.md        # Phase 1 output (/skill:speckit-plan command)
├── contracts/           # Phase 1 output (/skill:speckit-plan command)
└── tasks.md             # Phase 2 output (/skill:speckit-tasks - NOT created here)
```

### Source Code (repository root)

```text
app/
├── Enums/                        # existentes: TaskPriority, TaskType, TaskStatus, SprintStatus
├── Http/
│   ├── Controllers/
│   │   ├── BacklogController.php        # NUEVO: index() con payload del backlog
│   │   ├── TaskController.php           # EXISTENTE: store/update/destroy reutilizados; destroy redirige atrás
│   │   └── ProjectSprintController.php  # EXISTENTE: complete() dispara rollover vía observer
│   ├── Requests/
│   │   ├── SaveTaskRequest.php          # EXISTENTE: relajar assignee obligatorio cuando project_id es null
│   │   └── UpdateTaskRequest.php        # EXISTENTE: añadir points opcional
│   └── ...
├── Models/
│   ├── Task.php                         # EXISTENTE: scopes visibleTo()/backlog(); casts nuevos
│   ├── Sprint.php                       # EXISTENTE
│   └── Project.php                      # EXISTENTE
├── Observers/
│   ├── TaskObserver.php                 # EXISTENTE: genera Activity (proyecto) y code FRN-### (captura)
│   └── SprintObserver.php               # EXISTENTE: al completar sprint, pendientes sin terminar vuelven al backlog
└── Policies/
    └── TaskPolicy.php                   # EXISTENTE: standalone → autor O responsable

database/
└── migrations/
    └── 2026_10_08_000000_add_backlog_fields_to_tasks_table.php  # NUEVO: author_id, code, points

resources/js/
├── pages/
│   └── Backlog/
│       └── Index.vue                    # NUEVO: página /backlog
├── components/
│   ├── Backlog/                         # NUEVO
│   │   ├── QuickCapture.vue             # campo único + Enter, prioridad/proyecto opcionales en línea
│   │   ├── BacklogFilters.vue           # chips Todas / Sin proyecto / Con proyecto + conteos
│   │   ├── BacklogSectionList.vue       # secciones por prioridad (Alta & Urgente / Media / Baja e ideas)
│   │   └── NextSprintBanner.vue         # aviso del próximo sprint activo/planificado
│   └── Tasks/
│       └── Board/
│           ├── TaskFormModal.vue        # EXISTENTE: + campo puntos, default status backlog en captura
│           └── TaskRow.vue              # REUTILIZADO tal cual
├── types/
│   ├── task.ts                          # EXISTENTE: + code, points, author en payload
│   └── backlog.ts                       # NUEVO: tipos de la página
└── Layouts/
    └── AuthenticatedLayout.vue          # EXISTENTE: activar item "Backlog" (línea ~59, hoy disabled)

routes/
└── web.php                              # EXISTENTE: + GET /backlog → backlog.index

tests/
└── Feature/
    ├── Backlog/                         # NUEVO
    │   ├── BacklogPageTest.php          # payload, filtros, conteos, resumen
    │   ├── QuickCaptureTest.php         # captura título-único, defaults, validación, doble submit
    │   ├── BacklogTriageTest.php        # asignar proyecto/prioridad/puntos, permisos
    │   └── SprintRolloverTest.php       # cerrar sprint devuelve pendientes al backlog
    ├── Tasks/                           # EXISTENTE: ajustes por policy/request changes
    └── Sprints/                         # EXISTENTE: ajustes por rollover
```

**Structure Decision**: estructura existente del monolito (Opción web-app única).
Las piezas nuevas siguen las convenciones ya establecidas: controlador delgado con
payload mapeado, page Vue bajo `pages/`, componentes de dominio bajo
`components/Backlog/`, rutas nombradas consumidas con Wayfinder, tests de feature
por capacidad.

## Complexity Tracking

> Sin violaciones constitucionales que justificar. Decisiones de simplificación
> (reuso de `TaskFormModal`, scopes en Eloquent, filtros client-side) están
> documentadas con alternativas rechazadas en [research.md](research.md).
