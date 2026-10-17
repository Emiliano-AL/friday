---
description: 'Task list for 030-project-attachments'
---

# Tasks: Adjuntos y referencias del proyecto

**Input**: Design documents from `/specs/030-project-attachments/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/routes.md, quickstart.md

**Tests**: Incluidos. La constitución (Principio III, Test-First con Pest) los exige: se escriben primero y deben fallar antes de implementar.

**Organization**: Tareas agrupadas por historia de usuario (US1–US4 de spec.md).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede correr en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: Historia de usuario a la que pertenece (US1, US2, US3, US4)

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Dependencia y entorno

- [X] T001 Instalar (aprobado por el usuario el 2026-10-17, Principio II) el driver S3 con `composer require league/flysystem-aws-s3-v3 "^3.0" --with-all-dependencies` (actualiza `composer.json` y `composer.lock`)
- [X] T002 [P] Subir los límites de PHP en el php.ini de PHP 8.4 de Herd: `upload_max_filesize = 16M` y `post_max_size = 20M`. Reiniciar Herd, verificar con `php -i | grep -E "upload_max|post_max"` y anotar en `.env.example` (comentario sobre `FILESYSTEM_DISK`) que producción usa `FILESYSTEM_DISK=s3` con las variables `AWS_*`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Modelo, tabla, policy y rutas compartidas por todas las historias

**⚠️ CRITICAL**: Ninguna historia puede empezar hasta completar esta fase

- [X] T003 [P] Crear el enum `app/Enums/AttachmentType.php` (string) con los casos `Document = 'document'`, `Image = 'image'` y `Link = 'link'`, y `label()` → "Documento" / "Imagen" / "Link" (seguir el estilo de `app/Enums/ProjectStatus.php`)
- [X] T004 [P] Crear la migración con `php artisan make:migration create_project_attachments_table --no-interaction` en `database/migrations/`. Columnas:
  - `id`
  - `project_id` (foreignId constrained `cascadeOnDelete`)
  - `user_id` (foreignId nullable constrained('users') `nullOnDelete`)
  - `type` (string)
  - `title` (string 255)
  - `url` (string 2048 nullable)
  - `path` (string nullable)
  - `thumbnail_path` (string nullable)
  - `original_name` (string 255 nullable)
  - `mime_type` (string nullable)
  - `size` (unsignedBigInteger nullable)
  - `width` y `height` (unsignedInteger nullable)
  - timestamps
  - índice `(project_id, created_at)`
- [X] T005 Crear el modelo `app/Models/ProjectAttachment.php` con `php artisan make:model ProjectAttachment --factory --no-interaction`:
  - Atributo `#[Fillable([...])]` como en `Project.php` y cast `type` → `AttachmentType`
  - Relaciones `project(): BelongsTo` y `user(): BelongsTo`
  - PHPDoc `@property` para todas las columnas
- [X] T006 Agregar la relación `attachments(): HasMany<ProjectAttachment, $this>` a `app/Models/Project.php` (sin orden global; listar con `latest()`) y su `@property-read` en el PHPDoc
- [X] T007 [P] Crear la factory `database/factories/ProjectAttachmentFactory.php`:
  - Por defecto, tipo link con `url` de faker y título igual al dominio
  - Estados `document()`: `path` `projects/{project_id}/attachments/{uuid}.pdf`, `mime_type` `application/pdf`, `size` 1024 y `original_name`
  - Estado `image()`: lo mismo con `thumbnail_path`, `width` y `height`
  - Estado `link()`
- [X] T008 Crear la policy `app/Policies/ProjectAttachmentPolicy.php`:
  - `view(User, ProjectAttachment)`: `$attachment->project->isMember($user)`
  - `create(User, Project)`: `$project->isMember($user) && $project->status->isActive()`
  - `update` y `delete(User, ProjectAttachment)`: (`$attachment->user_id === $user->id` o `$attachment->project->isOwnedBy($user)`) y `$attachment->project->status->isActive()`
  - Imitar `app/Policies/ProjectPolicy.php`
- [X] T009 Registrar en `routes/web.php`, dentro del grupo auth y junto a las rutas `projects/{project}/...`, las 5 rutas de `contracts/routes.md` apuntando a `ProjectAttachmentController`:
  - `projects.attachments.files.store` (POST `projects/{project}/attachments/files`)
  - `projects.attachments.links.store` (POST `projects/{project}/attachments/links`)
  - `projects.attachments.update` (PUT `projects/{project}/attachments/{attachment}`)
  - `projects.attachments.destroy` (DELETE `projects/{project}/attachments/{attachment}`)
  - `projects.attachments.show` (GET `projects/{project}/attachments/{attachment}`)
- [X] T010 Crear `app/Http/Controllers/ProjectAttachmentController.php` con `php artisan make:controller --no-interaction`, con métodos stub y los helpers privados:
  - `ensureAttachmentBelongsToProject` (`abort_unless($attachment->project_id === $project->id, 404)`)
  - `ensureProjectIsActive` (copiar el de `ProjectSprintController.php:278`, que redirige back con el error `project`)
  - Todas las acciones empiezan con `abort_unless($project->isMember($request->user()), 404)`
- [X] T011 Ejecutar `php artisan migrate` y `php artisan wayfinder:generate`

**Checkpoint**: Tabla, modelo, policy y rutas listos

---

## Phase 3: User Story 1 - Subir y consultar documentos e imágenes (Priority: P1) 🎯 MVP

**Goal**: Un miembro sube documentos o imágenes desde la pestaña "Archivos", los ve listados y los abre o descarga. Un usuario que no es miembro recibe 404.

**Independent Test**: Abrir un proyecto sin adjuntos, ver el estado vacío, subir un PDF y una imagen, verlos listados, previsualizar la imagen y descargar el PDF con su nombre original.

### Tests for User Story 1 ⚠️

> Escribirlos primero y confirmar que fallan

- [X] T012 [P] [US1] Crear `tests/Feature/Projects/ProjectAttachmentsTest.php` con `php artisan make:test --pest ProjectAttachmentsTest` (después moverlo a `tests/Feature/Projects/`). Usar `Storage::fake()` y casos para:
  - Propietario y colaborador suben un PDF (`UploadedFile::fake()->create('brief.pdf', 500, 'application/pdf')`): existe la fila con `type=document`, `original_name='brief.pdf'` y `user_id`, el archivo existe en `projects/{id}/attachments/` y la respuesta redirige a `projects.show`
  - Un miembro sube una imagen PNG (`UploadedFile::fake()->image('mock.png', 800, 600)`): fila `type=image` con `thumbnail_path` y los dos archivos en disco
  - Un no miembro recibe 404 en store y en show
  - Un adjunto de otro proyecto devuelve 404 en show
  - `show` responde 302 a una URL temporal; con `?download=1` la URL incluye el nombre original; con `?thumbnail=1` apunta a la miniatura; sobre un link devuelve 404
  - En un proyecto archivado, el store devuelve el error `project` y no se crea nada, pero `show` sigue funcionando
- [X] T013 [P] [US1] En `tests/Feature/Projects/ProjectShowPayloadTest.php`, agregar casos para la prop `attachments`:
  - No llega en la carga normal y sí llega con la recarga parcial (headers `X-Inertia-Partial-Data: attachments` y `X-Inertia-Partial-Component: projects/Show`)
  - `items` viene en orden de más reciente a más antiguo
  - Incluye `usage` con `{count, maxCount: 30, bytes, maxBytes: 209715200}`, `canCreate` y `limits`
  - `canManage` vale `true` para el autor y el propietario y `false` para otro colaborador

### Implementation for User Story 1

- [X] T014 [P] [US1] Crear `app/Http/Requests/StoreProjectAttachmentFileRequest.php` (estilo de `StoreSprintRequest.php`). Regla de `file`: required, `File::types(['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','md','csv','jpg','jpeg','png','webp','gif'])` (valida el MIME real con fileinfo) y máximo de 15 MB. Mensajes en español. El límite específico de imagen y el cupo se agregan en US2.
- [X] T015 [US1] Implementar `store` de archivos en `app/Http/Controllers/ProjectAttachmentController.php`:
  - Validar con la request de T014, ejecutar `ensureProjectIsActive` y `authorize('create', [ProjectAttachment::class, $project])`
  - Clasificar como imagen si el MIME detectado empieza con `image/` y si no como documento
  - Guardar con `Storage::putFileAs("projects/{$project->id}/attachments", $file, Str::uuid().'.'.$file->extension())`
  - En imágenes, guardar provisionalmente el mismo archivo como miniatura en `{uuid}_thumb.{ext}` (US2 lo reemplaza por la versión optimizada)
  - Crear la fila con `title = original_name`, `mime_type`, `size`, `width` y `height` (de `getimagesize`)
  - Responder `redirect()->route('projects.show', $project)->with('success', 'Archivo subido.')`
- [X] T016 [US1] Implementar `show` en `app/Http/Controllers/ProjectAttachmentController.php`:
  - Autorizar `view`; si es link, `abort(404)`
  - Si viene `thumbnail`, usar `thumbnail_path` y si no, `path`
  - Responder `redirect(Storage::temporaryUrl($path, now()->addMinutes(5), ['ResponseContentDisposition' => ($request->boolean('download') ? 'attachment' : 'inline').'; filename="'.addslashes($attachment->original_name).'"']))`
  - Confirmar con `search-docs` que el driver local acepta este array de opciones
- [X] T017 [US1] En `app/Http/Controllers/ProjectController.php@show`, agregar la prop `'attachments' => Inertia::optional(fn () => $this->attachmentsPayload($request, $project))`. Implementar `attachmentsPayload`:
  - `$project->attachments()->with('user:id,name,avatar')->latest()->get()`, mapeado a la forma de `contracts/routes.md` (`openUrl`, `downloadUrl` y `thumbnailUrl` generados con `route('projects.attachments.show', ...)`)
  - `usage`: count y `sum('size')`
  - `canCreate` y `canManage` con `$request->user()->can(...)`
  - `limits`: `imageMaxBytes` 5242880, `documentMaxBytes` 15728640 y `accept` con la lista de extensiones
- [X] T018 [P] [US1] Agregar a `resources/js/Components/Projects/ProjectTabs.vue` la pestaña `{ value: 'files', label: 'Archivos', icon: 'attach_file' }`:
  - Ampliar `ProjectTabValue` a `'general' | 'sprints' | 'files'`
  - Ajustar la guarda de `activate()` para aceptar `'files'`
- [X] T019 [US1] Crear `resources/js/Components/Projects/ProjectAttachments.vue` (`<script setup lang="ts">`, Tailwind, mismo estilo visual que los demás componentes de `Components/Projects`). Props: `projectId` y `attachments` (tipo de `contracts/routes.md`, o `undefined` mientras carga). Debe incluir:
  - Skeleton animado mientras `attachments` es `undefined`
  - Estado vacío con la acción "Subir archivos"
  - Lista con miniatura en imágenes (`<img :src="thumbnailUrl" loading="lazy">`) o un ícono por tipo de documento
  - En cada fila: título, tamaño legible, autor y fecha
  - Acciones abrir (`openUrl`, `target="_blank"`) y descargar (`downloadUrl`)
  - Vista ampliada de imágenes con `resources/js/Components/Modal.vue`
  - Input `type="file" multiple :accept="limits.accept"` con una cola secuencial: cada archivo se envía con `useForm({ file }).post(store.url({ project }), { forceFormData: true, preserveScroll: true, only: ['attachments', 'flash', 'errors'] })` usando la ruta de Wayfinder `@/actions/App/Http/Controllers/ProjectAttachmentController`
  - Por archivo, mostrar `form.progress.percentage` y el resultado (éxito o el mensaje de `form.errors.file`), con 10 archivos como máximo por selección
  - Ocultar los controles de subida cuando `canCreate` es `false`
- [X] T020 [US1] Montar la pestaña en `resources/js/pages/projects/Show.vue`:
  - Agregar la prop `attachments?` y ampliar `activeTab` a `'files'`
  - Agregar el panel `v-if="activeTab === 'files'" role="tabpanel"` con `<ProjectAttachments>`
  - Con un `watch` sobre `activeTab`, la primera vez que valga `'files'` ejecutar `router.reload({ only: ['attachments'] })`
- [X] T021 [US1] Ejecutar `php artisan test --compact tests/Feature/Projects/ProjectAttachmentsTest.php tests/Feature/Projects/ProjectShowPayloadTest.php` hasta que pasen

**Checkpoint**: US1 es funcional y demostrable (MVP)

---

## Phase 4: User Story 2 - Límites de tamaño y optimización de imágenes (Priority: P1)

**Goal**: Rechazar archivos demasiado grandes, tipos no permitidos y adjuntos fuera de cupo. Optimizar las imágenes y generar su miniatura.

**Independent Test**:
- Subir una imagen de 6 MB, un documento de 16 MB y un `.exe` renombrado: los tres se rechazan con un mensaje claro
- Subir una imagen de 4000×3000: queda guardada con lado mayor de 2560 px, miniatura de 400 px y un peso igual o menor al original

### Tests for User Story 2 ⚠️

- [X] T022 [P] [US2] Crear `tests/Feature/Projects/ImageOptimizerTest.php` con imágenes reales generadas con GD en un directorio temporal. Casos:
  - Un JPG de 4000×3000 sale con lado mayor de 2560 px, conserva la proporción y pesa igual o menos que el original
  - Un PNG con transparencia sigue siendo PNG y conserva el canal alfa
  - Un WebP sigue siendo WebP
  - Un JPG ya pequeño (200×200, calidad baja) conserva sus bytes originales si re-codificarlo no reduce el peso
  - La miniatura tiene un lado mayor de 400 px
  - Un JPG con orientación EXIF 6 queda rotado (ancho y alto intercambiados); si generar el fixture con EXIF no es viable, marcar el test con `->todo()` y cubrirlo en la validación manual
  - Un GIF se devuelve sin cambios
  - Una imagen de más de 40 MP lanza una excepción de validación
  - Un archivo corrupto con extensión `.jpg` lanza una excepción de validación
- [X] T023 [P] [US2] En `tests/Feature/Projects/ProjectAttachmentsTest.php`, agregar casos para:
  - Imagen de 5121 KB rechazada con el error `file` "no puede superar 5 MB"
  - Documento de 15361 KB rechazado con el error "no puede superar 15 MB"
  - Tipos `.zip` y `.mp4` rechazados
  - `UploadedFile::fake()->createWithContent('malware.pdf', "MZ\x90\x00...")` rechazado
  - El adjunto número 31 rechazado, con 30 links ya creados por la factory
  - Un archivo que llevaría el total por encima de 200 MB (adjuntos de factory con `size` grande) rechazado
  - En todos los rechazos no queda ninguna fila ni archivo en `Storage::fake()`
  - Una imagen de 4000×3000 subida queda en disco con lado mayor de 2560 px

### Implementation for User Story 2

- [X] T024 [US2] Crear `app/Support/ImageOptimizer.php` (carpeta nueva aprobada por el usuario). Método `optimize(UploadedFile $file): array{contents: string, thumbnail: string, width: int, height: int, size: int}` con GD nativo:
  1. `getimagesize`: si falla, `ValidationException` con "La imagen no se pudo procesar."; si `w*h > 40_000_000`, `ValidationException` con "La imagen supera 40 megapíxeles."
  2. `ini_set('memory_limit', '256M')`
  3. GIF: devolver los bytes originales como contenido y como miniatura
  4. Decodificar con `imagecreatefromjpeg`, `imagecreatefrompng` o `imagecreatefromwebp`. En JPG, aplicar la orientación EXIF (valores 3, 6 y 8) leída con `@exif_read_data`
  5. Reducir el lado mayor a 2560 px con `imagescale` y conservar el alfa (`imagealphablending(false)` y `imagesavealpha(true)`)
  6. Re-codificar en el mismo formato con `ob_start`: `imagejpeg` calidad 82, `imagepng` nivel 9, `imagewebp` calidad 80
  7. Si no hubo redimensión ni rotación y el resultado pesa más que el original, devolver los bytes originales
  8. Generar la miniatura con lado mayor de 400 px en el mismo formato
  9. Liberar con `imagedestroy`
  - Todo con PHPDoc y tipos estrictos
- [X] T025 [US2] En `app/Http/Requests/StoreProjectAttachmentFileRequest.php`:
  - Validación condicional: si el MIME detectado empieza con `image/`, aplicar `max:5120`; si no, `max:15360`
  - Mensajes "Las imágenes no pueden superar 5 MB." y "Los documentos no pueden superar 15 MB."
  - En `after()`, validar el cupo: `$project->attachments()->count() >= 30` da "El proyecto alcanzó el máximo de 30 adjuntos."; `sum('size') + file size > 209715200` da "El proyecto alcanzó el máximo de 200 MB de archivos."
- [X] T026 [US2] En `ProjectAttachmentController@storeFile`:
  - En imágenes, reemplazar la copia provisional de T015 por `ImageOptimizer::optimize()` y guardar `contents` y `thumbnail` con `Storage::put`
  - Guardar en la fila el `size`, `width` y `height` finales
  - Envolver la verificación del cupo y la creación de la fila en `DB::transaction` con `Project::whereKey($project->id)->lockForUpdate()->first()`, y volver a comprobar el cupo con el tamaño final
  - Si algo falla después de escribir en disco, borrar los archivos escritos para que no quede nada a medias
- [X] T027 [US2] En `resources/js/Components/Projects/ProjectAttachments.vue`:
  - Validación previa en el navegador con `limits`: tipo según `accept` y tamaño según `imageMaxBytes` o `documentMaxBytes`. Rechazar sin enviar, mostrando el límite
  - Bloquear la selección si `usage.count` llegó a `maxCount`
  - Mostrar la barra de uso "{count} de 30 adjuntos · {MB} de 200 MB"
- [X] T028 [US2] Ejecutar `php artisan test --compact tests/Feature/Projects/ImageOptimizerTest.php tests/Feature/Projects/ProjectAttachmentsTest.php`

**Checkpoint**: US1 y US2 completas. Límites e imágenes optimizadas funcionando.

---

## Phase 5: User Story 3 - Links de referencia (Priority: P2)

**Goal**: Agregar links con URL y título opcional que se abren en una pestaña nueva.

**Independent Test**: Agregar un link con título y otro sin título, verlos con su dominio y abrirlos en una pestaña nueva. Una URL inválida se rechaza.

### Tests for User Story 3 ⚠️

- [X] T029 [P] [US3] En `tests/Feature/Projects/ProjectAttachmentsTest.php`, agregar casos para:
  - Un miembro agrega `https://www.figma.com/file/abc` con título: fila `type=link` con `url` y `title`
  - Sin título, el `title` queda en `figma.com` (dominio sin `www.`)
  - Se rechazan `javascript:alert(1)`, `ftp://x.com`, `not a url` y URLs de más de 2048 caracteres
  - Un título de más de 255 caracteres se rechaza
  - El link número 31 se rechaza por cupo
  - Un no miembro recibe 404
  - En un proyecto archivado se rechaza

### Implementation for User Story 3

- [X] T030 [P] [US3] Crear `app/Http/Requests/StoreProjectAttachmentLinkRequest.php` con las reglas `url: required|url:http,https|max:2048` y `title: nullable|string|max:255`, mensajes en español y la misma verificación de cupo (30 adjuntos) en `after()`
- [X] T031 [US3] Implementar `storeLink` en `app/Http/Controllers/ProjectAttachmentController.php`: autorizar `create`, ejecutar `ensureProjectIsActive` y crear la fila con `type=Link` y `title = $validated['title'] ?: Str::after(parse_url($url, PHP_URL_HOST), 'www.')`. Responder con redirect y el flash "Link agregado."
- [X] T032 [US3] En `resources/js/Components/Projects/ProjectAttachments.vue`:
  - Formulario "Agregar link" (URL y título opcional) con `useForm` hacia `storeLink` y errores con `InputError.vue`
  - En la lista, los links muestran el título, el dominio y un ícono `link`, y se abren con `target="_blank" rel="noopener noreferrer"`
  - Filtros Todos / Documentos / Imágenes / Links reutilizando `resources/js/Components/Projects/FilterPills.vue`
- [X] T033 [US3] Ejecutar `php artisan test --compact tests/Feature/Projects/ProjectAttachmentsTest.php`

**Checkpoint**: US3 funcional

---

## Phase 6: User Story 4 - Renombrar y eliminar (Priority: P3)

**Goal**: El autor de un adjunto o el propietario del proyecto pueden renombrarlo o eliminarlo. Al eliminarlo, sus archivos se borran del almacenamiento.

**Independent Test**:
- El autor renombra y elimina un adjunto propio
- Otro colaborador recibe 403
- El propietario elimina el adjunto de un colaborador
- Al eliminar el proyecto, sus archivos desaparecen del disco

### Tests for User Story 4 ⚠️

- [X] T034 [P] [US4] En `tests/Feature/Projects/ProjectAttachmentsTest.php`, agregar casos para:
  - El autor renombra con PUT `{title: 'Nuevo'}` y el título cambia; un título vacío o de más de 255 caracteres se rechaza
  - El propietario renombra y elimina el adjunto de un colaborador
  - Otro colaborador recibe 403 en update y en destroy
  - Al eliminar, la fila desaparece y los archivos `path` y `thumbnail_path` dejan de existir en el disco
  - En un proyecto archivado, update y destroy se rechazan
  - Al eliminar el proyecto (`DELETE /projects/{id}`) desaparecen sus filas y el directorio `projects/{id}/`
  - Al quitar a un colaborador del proyecto, sus adjuntos se quedan y él recibe 404 al intentar abrirlos

### Implementation for User Story 4

- [X] T035 [US4] Implementar `update` en `app/Http/Controllers/ProjectAttachmentController.php`: validar `title: required|string|max:255` inline, ejecutar `ensureProjectIsActive` y `authorize('update', $attachment)`, actualizar y responder con redirect y el flash "Adjunto actualizado."
- [X] T036 [US4] Implementar `destroy` en `app/Http/Controllers/ProjectAttachmentController.php`: ejecutar `ensureProjectIsActive` y `authorize('delete', $attachment)`, luego `Storage::delete(array_filter([$attachment->path, $attachment->thumbnail_path]))` y `$attachment->delete()`. Responder con redirect y el flash "Adjunto eliminado."
- [X] T037 [US4] En `app/Http/Controllers/ProjectController.php@destroy`, después de `$project->delete()`, ejecutar `Storage::deleteDirectory("projects/{$project->id}")`
- [X] T038 [US4] En `resources/js/Components/Projects/ProjectAttachments.vue`:
  - Si `canManage` es `true`, mostrar renombrar inline (input con Enter para guardar y Esc para cancelar) y eliminar con confirmación usando `Modal.vue` y `DangerButton.vue`, como en otras confirmaciones del proyecto
  - Si `canManage` es `false`, ocultar ambas acciones
- [X] T039 [US4] Ejecutar `php artisan test --compact tests/Feature/Projects/ProjectAttachmentsTest.php tests/Feature/Projects/ProjectCrudTest.php`

**Checkpoint**: Las cuatro historias funcionan

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T040 [P] Ejecutar `vendor/bin/pint --dirty --format agent`
- [X] T041 [P] Ejecutar `npm run build` (o `vue-tsc` si existe en `package.json`) y corregir los errores de tipos de los componentes nuevos
- [ ] T042 Hacer la validación manual de `specs/030-project-attachments/quickstart.md` (pasos 1–7), incluida una foto de celular con orientación EXIF, y revisar `browser-logs` de Boost
- [ ] T043 Pedir al usuario que ejecute el suite completo con `php artisan test --compact`

---

## Dependencies & Execution Order

- **Setup (T001–T002)**: T001 bloquea el uso de S3 en producción, pero no los tests (usan `Storage::fake()`), así que puede correr en paralelo con la Phase 2.
- **Foundational (T003–T011)**: bloquea todas las historias. T003, T004 y T007 son [P]. T005 depende de T003; T006, T008 y T010 dependen de T005.
- **US1 (T012–T021)**: depende de la Phase 2. Es el MVP.
- **US2 (T022–T028)**: depende de US1 (extiende `storeFile` y la request de T014). T022 y T024 pueden avanzar en paralelo con US1 porque el optimizador está aislado.
- **US3 (T029–T033)**: depende solo de la Phase 2 en el backend; la UI requiere el componente de T019.
- **US4 (T034–T039)**: depende de la Phase 2 en el backend; la UI requiere T019.
- **Polish**: al final.

```text
Phase 2 ─► US1 ─► US2
       ├─► US3 (backend) ──┐
       └─► US4 (backend) ──┴─► UI (requiere T019) ─► Polish
```

## Parallel Example: User Story 1

```text
T012 ProjectAttachmentsTest.php       ┐
T013 ProjectShowPayloadTest.php       ├─ en paralelo
T014 StoreProjectAttachmentFileRequest│
T018 ProjectTabs.vue                  ┘
→ T015, T016, T017 (mismo controller, en secuencia) → T019 → T020 → T021
```

## Parallel Example: User Story 2

```text
T022 ImageOptimizerTest.php  ┐ en paralelo (incluso durante US1)
T024 ImageOptimizer.php      ┘
T023 tests de límites → T025 → T026 → T027 → T028
```

## Implementation Strategy

1. **MVP**: Phase 1, Phase 2 y US1. Ya se pueden subir y ver archivos de forma privada, con el límite general de 15 MB.
2. **US2** antes de liberar a usuarios reales: límites por tipo, cupo y optimización (requisito explícito del usuario).
3. **US3** (links) y **US4** (gestión) como incrementos independientes.
4. Cada checkpoint se valida con sus tests antes de avanzar (Principio III).

---

## Phase 8: Convergence

- [X] T044 Aceptar archivos de Office antiguos (`.doc`, `.xls`, `.ppt`) cuyo contenido libmagic reporta como `application/x-ole-storage` o `application/CDFV2`. Esos MIME no tienen extensión asociada y hoy la regla `mimes:` los rechaza. En `app/Http/Requests/StoreProjectAttachmentFileRequest.php`, si la extensión es una de esas tres y el MIME detectado es uno de esos dos, aceptar el archivo y seguir rechazando cualquier otro contenido. Agregar a `tests/Feature/Projects/ProjectAttachmentsTest.php` un caso con un archivo OLE real (cabecera `D0 CF 11 E0 A1 B1 1A E1`) nombrado `.xls`, y comprobar que el mismo contenido nombrado `.pdf` se sigue rechazando, per FR-010 (partial)
- [X] T045 Hacer que las descargas usen el nombre original también con el disco `local`, que ignora `ResponseContentDisposition` y hoy descarga con el nombre UUID. En `ProjectAttachmentController@show`, cuando el disco no respete esa opción (driver local), responder con `Storage::download($path, $attachment->original_name)` (o `Storage::response(...)` para `inline`) en lugar del redirect. Cubrirlo en `tests/Feature/Projects/ProjectAttachmentsTest.php` revisando el header `Content-Disposition` con `?download=1`, per FR-017 (partial)
- [X] T046 Reconciliar FR-020 con lo implementado: las imágenes con metadatos EXIF siempre guardan la versión re-codificada aunque pese más, porque FR-021 tiene prioridad. Documentar esa excepción en Edge Cases de `specs/030-project-attachments/spec.md` y en el PHPDoc de `app/Support/ImageOptimizer.php`, per FR-020 (contradicts)
- [X] T047 Documentar en el tipo `ProjectAttachments` de `specs/030-project-attachments/contracts/routes.md` el campo `limits.imageExtensions` (`string[]`, que usa la validación previa del frontend). El payload ya lo envía pero el contrato no lo menciona, per plan: contracts/routes.md (unrequested)
- [X] T048 Mostrar el tipo del adjunto como texto (Documento / Imagen / Link) en los metadatos de cada fila de `resources/js/Components/Projects/ProjectAttachments.vue`; hoy solo se ve como ícono. Exponerlo como `typeLabel` desde `AttachmentType::label()` en el payload de `ProjectController::attachmentsPayload`, per FR-003 (partial)
