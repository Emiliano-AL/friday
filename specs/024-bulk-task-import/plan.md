# Implementation Plan: Carga masiva de tareas desde archivo Markdown

**Branch**: `024-bulk-task-import` | **Date**: 2026-10-12 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/024-bulk-task-import/spec.md`

## Summary

Importar tareas desde un `.md` con el "Formato de Tareas" a un proyecto o como
tareas sueltas. Flujo en dos pasos sin estado: el cliente lee el archivo y
envía su texto; el servidor lo analiza (parser por líneas en un FormRequest),
resuelve responsables, detecta duplicados y devuelve una vista previa por flash;
al confirmar se re-analiza y se crean las tareas importables en una
transacción. Decisiones en [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13) + Vue 3 / TypeScript.

**Primary Dependencies**: Inertia v3, Wayfinder. Sin dependencias nuevas (parser propio por líneas; `Str::ascii` para normalizar nombres).

**Storage**: tabla `tasks` existente; sin migraciones.

**Testing**: Pest feature tests con fixture Markdown; `npm run types:check`; escenarios de [quickstart.md](quickstart.md).

**Target Platform**: web monolítica Inertia.

**Project Type**: web-app monolito.

**Performance Goals**: ≤ 200 tareas por archivo; inserción en una transacción.

**Constraints**: nada se crea sin confirmación; todo o nada al confirmar; mensajes en español.

**Scale/Scope**: 1 FormRequest con parser, 1 controlador (4 acciones), 1 modal Vue, 2 puntos de entrada, 1 prop compartido.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Evaluación |
| --- | --- |
| I. Monolith-First con Inertia | PASS — Formularios Inertia + flash; sin API JSON. |
| II. Ecosistema Laravel | PASS — FormRequest, Policies, transacciones, Wayfinder; sin dependencias. |
| III. Test-First con Pest | PASS — `TaskImportTest` (parser vía endpoints: mapeo, errores, excluidos, responsables, duplicados, permisos, transacción) antes de implementar. |
| IV. Tipado y Enums | PASS — Mapeo a `TaskType`/`TaskPriority`/`TaskStatus`; shapes PHPDoc. |
| V. Simplicidad / YAGNI | PASS — Sin tablas temporales ni colas; parser en carpeta existente (D3). |

**Post-design re-check**: PASS.

## Project Structure

### Documentation (this feature)

```text
specs/024-bulk-task-import/
├── plan.md · research.md · data-model.md · quickstart.md
├── contracts/routes.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/Http/Requests/ImportTasksRequest.php          # NUEVO: valida content + parsedTasks() (parser por líneas, mapeo, Tiptap)
app/Http/Controllers/TaskImportController.php     # NUEVO: preview/store (sueltas) y projectPreview/projectStore
app/Http/Middleware/HandleInertiaRequests.php     # flash.importPreview
routes/web.php                                     # 4 rutas
resources/js/Components/Tasks/TaskImportModal.vue # NUEVO
resources/js/pages/Backlog/Index.vue               # botón "Importar"
resources/js/pages/Projects/Show.vue               # botón "Importar" en el panel de tareas
resources/js/types/task.ts                         # ImportPreview
tests/Fixtures/task-import-example.md              # NUEVO
tests/Feature/Tasks/TaskImportTest.php             # NUEVO
```

**Structure Decision**: sin carpetas base nuevas; el parser vive en el FormRequest (D3).

## Complexity Tracking

> Sin violaciones.
