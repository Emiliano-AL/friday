# Implementation Plan: Accesos a "Nueva tarea" y "Acciones rápidas"

**Branch**: `023-new-task-entry-points` | **Date**: 2026-10-10 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/023-new-task-entry-points/spec.md`

## Summary

Todos los accesos etiquetados "Nueva tarea" (inicio, inicio vacío, paleta y
tecla C) abren el formulario de nueva tarea existente en la página actual; el
botón principal de la barra lateral pasa a "Acciones rápidas" y abre la paleta.
Se monta un único `TaskFormModal` global en el layout, alimentado por un prop
compartido *optional* (`taskFormProjects`) que solo se carga al abrir. Detalle
en [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13) + Vue 3 `<script setup>` / TypeScript.

**Primary Dependencies**: Inertia v3 (optional props, partial reload), Wayfinder. Sin dependencias nuevas.

**Storage**: sin cambios.

**Testing**: Pest feature test del prop compartido + regresión de Backlog; `npm run types:check`; escenarios de navegador en [quickstart.md](quickstart.md).

**Target Platform**: web monolítica Inertia.

**Project Type**: web-app monolito.

**Performance Goals**: ninguna consulta extra en navegaciones normales; una consulta al abrir el formulario por primera vez en la página.

**Constraints**: no romper el formulario propio de Mis Tareas (tecla C en captura) ni ⌘K.

**Scale/Scope**: 1 método de modelo, 1 prop compartido, 1 modal global; cambios en 5 componentes Vue y 1 controlador.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Evaluación |
| --- | --- |
| I. Monolith-First con Inertia | PASS — Prop compartido + partial reload de Inertia; sin endpoint JSON. |
| II. Ecosistema Laravel | PASS — `Inertia::optional`, Eloquent; sin dependencias. |
| III. Test-First con Pest | PASS — Test del prop compartido (ausente en visita completa, presente con `only`, solo proyectos del usuario) antes de implementar; regresión del Backlog tras la extracción. |
| IV. Tipado y Enums | PASS — Reutiliza `SprintStatus` y `TaskProjectOption`. |
| V. Simplicidad / YAGNI | PASS — Un modal global en vez de uno por página; sin registro de "creadores" por página (la fase de captura de Mis Tareas ya evita duplicados). |

**Post-design re-check**: PASS.

## Project Structure

### Documentation (this feature)

```text
specs/023-new-task-entry-points/
├── plan.md
├── research.md
├── data-model.md
├── contracts/ui.md
├── quickstart.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/
├── Models/Project.php                              # + static taskFormOptionsFor(User)
├── Http/Controllers/BacklogController.php          # usa Project::taskFormOptionsFor (quita projectOptions)
└── Http/Middleware/HandleInertiaRequests.php       # + 'taskFormProjects' => Inertia::optional(...)
resources/js/
├── Layouts/AuthenticatedLayout.vue                 # TaskFormModal global, provide open-task-form, tecla C, paleta, sidebar
├── Components/AppShell/AppShellSidebar.vue         # "Acciones rápidas" + ⌘K, emite quickActions
├── Components/Home/ActivityDashboard.vue           # inject open-task-form
└── Components/AppShell/HomeCanvas.vue              # tarjeta "Nueva tarea" → open-task-form
tests/Feature/Shell/TaskFormOptionsSharedPropTest.php  # NUEVO
```

**Structure Decision**: monolito existente; sin carpetas nuevas.

## Complexity Tracking

> Sin violaciones.
