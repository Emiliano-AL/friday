# Implementation Plan: Adjuntos y referencias del proyecto

**Branch**: `030-project-attachments` | **Date**: 2026-10-17 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/030-project-attachments/spec.md`

## Summary

Los miembros de un proyecto pueden adjuntar documentos, imágenes y links desde una nueva pestaña "Archivos" en el detalle del proyecto. Una sola tabla `project_attachments` guarda los tres tipos. Los archivos se suben al servidor de uno en uno, se validan por su contenido real y por tamaño (imágenes 5 MB, documentos 15 MB) y se guardan en el disco por defecto, que en producción es S3. Las imágenes se optimizan con GD nativo antes de guardarse: lado mayor de 2560 px como máximo, mismo formato, sin metadatos EXIF y con miniatura de 400 px. Cada proyecto tiene un cupo de 30 adjuntos y 200 MB. Para abrir o descargar un archivo, una ruta autorizada redirige a una URL temporal.

## Technical Context

**Language/Version**: PHP 8.4, TypeScript (Vue 3.5)

**Primary Dependencies**: Laravel 13.32, Inertia v3 (laravel + vue3), Wayfinder; **nueva**: `league/flysystem-aws-s3-v3 ^3.0` (driver S3, aprobada); extensión PHP GD (ya instalada)

**Storage**: PostgreSQL (tabla `project_attachments`, links incluidos); disco por defecto de Laravel para archivos (`local` privado en dev/test, `s3` en producción)

**Testing**: Pest 5 feature tests con `Storage::fake()` y `UploadedFile::fake()`

**Target Platform**: Web (Herd en local, Laravel Cloud en producción)

**Project Type**: Monolito web Laravel + Inertia/Vue

**Performance Goals**: subida + optimización de una imagen de 5 MB en menos de 3 s en el servidor; pestaña con 30 adjuntos visible en menos de 2 s (SC-005)

**Constraints**: PHP `upload_max_filesize ≥ 16M` y `post_max_size ≥ 20M` (hoy 2M en local); `memory_limit` de 512M dentro del optimizador; imágenes de 40 MP como máximo

**Scale/Scope**: hasta 30 adjuntos y 200 MB por proyecto; 1 tabla, 1 modelo, 1 policy, 1 controller, 1 clase de optimización, 1 componente Vue nuevo y 2 modificados

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Estado | Nota |
|-----------|--------|------|
| I. Monolith-First con Inertia | ✅ | Las rutas web devuelven redirects de Inertia y la lista llega como prop opcional de `projects/Show`. No hay API REST. |
| II. Ecosistema Laravel / dependencias | ✅ Aprobado (2026-10-17) | Se necesita `league/flysystem-aws-s3-v3`, que es el requisito oficial del driver S3. La optimización usa GD nativo y no agrega dependencias. |
| III. Test-First con Pest | ✅ | Tests de feature de rutas, policy, límites y optimizador, escritos antes de implementar. |
| IV. Tipado estricto y enums | ✅ | `AttachmentType` es un enum y todo el código lleva tipos. |
| V. Simplicidad / YAGNI | ✅ con justificación | La funcionalidad extiende el alcance del MVP por petición explícita del usuario. Se mantiene mínima: una tabla, procesamiento síncrono sin colas, sin librería de imágenes y sin vista previa de documentos. |

**Post-design re-check**: todo en verde. El usuario aprobó la dependencia S3 y la carpeta `app/Support/` el 2026-10-17.

## Project Structure

### Documentation (this feature)

```text
specs/030-project-attachments/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/routes.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
app/
├── Enums/AttachmentType.php                      # nuevo
├── Http/Controllers/ProjectAttachmentController.php  # nuevo (store file/link, update, destroy, show)
├── Http/Controllers/ProjectController.php        # prop optional `attachments`; destroy borra projects/{id}/
├── Http/Requests/StoreProjectAttachmentFileRequest.php  # nuevo
├── Http/Requests/StoreProjectAttachmentLinkRequest.php  # nuevo
├── Models/Project.php                            # attachments()
├── Models/ProjectAttachment.php                  # nuevo
├── Policies/ProjectAttachmentPolicy.php          # nuevo
└── Support/ImageOptimizer.php                    # nuevo (carpeta nueva aprobada)

database/
├── migrations/xxxx_create_project_attachments_table.php
└── factories/ProjectAttachmentFactory.php

resources/js/
├── Components/Projects/ProjectTabs.vue           # pestaña "Archivos"
├── Components/Projects/ProjectAttachments.vue    # nuevo
└── pages/projects/Show.vue                       # monta la pestaña y hace reload only:['attachments']

routes/web.php                                    # 5 rutas (contracts/routes.md)

tests/Feature/Projects/
├── ProjectAttachmentsTest.php
└── ImageOptimizerTest.php
```

**Structure Decision**: Se sigue la estructura existente: un controller por sub-recurso del proyecto (como `ProjectSprintController`), Form Requests para validar, una policy y un componente en `Components/Projects`.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|--------------------------------------|
| Nueva dependencia `league/flysystem-aws-s3-v3` (aprobada) | El driver `s3` de Laravel no funciona sin ella, y S3 es requisito explícito del usuario | No hay alternativa para escribir en S3 desde Laravel |
| Nueva carpeta `app/Support/` (aprobada) | Aísla unas 80 líneas de GD (EXIF, redimensión, re-codificación y miniatura) para poder probarlas solas | Como método privado del controller, este quedaría mezclado con procesamiento de imágenes y sería difícil de probar. |
