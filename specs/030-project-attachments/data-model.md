# Data Model: Adjuntos y referencias del proyecto

## Enum `App\Enums\AttachmentType` (string)

| Case | Value | label() |
|------|-------|---------|
| `Document` | `document` | Documento |
| `Image` | `image` | Imagen |
| `Link` | `link` | Link |

## Tabla `project_attachments`

| Columna | Tipo | Nulos | Notas |
|---------|------|-------|-------|
| `id` | bigint PK | no | |
| `project_id` | FK → projects | no | `cascadeOnDelete` |
| `user_id` | FK → users | sí | autor; `nullOnDelete` (si se borra la cuenta, el adjunto queda) |
| `type` | string | no | `AttachmentType` |
| `title` | string(255) | no | título visible; por defecto nombre original (archivos) o dominio de la URL (links) |
| `url` | string(2048) | sí | solo `link` |
| `path` | string | sí | ruta en el disco; solo archivos |
| `thumbnail_path` | string | sí | solo imágenes |
| `original_name` | string(255) | sí | nombre de descarga; solo archivos |
| `mime_type` | string | sí | MIME detectado; solo archivos |
| `size` | unsignedBigInteger | sí | bytes almacenados (post-optimización); solo archivos |
| `width`, `height` | unsignedInteger | sí | solo imágenes, dimensiones finales |
| `created_at`, `updated_at` | timestamp | | |

Índice: `(project_id, created_at)` para listar.

Rutas en disco: `projects/{project_id}/attachments/{uuid}.{ext}` y `projects/{project_id}/attachments/{uuid}_thumb.{ext}`.

## Modelo `App\Models\ProjectAttachment`

- Casts: `type` → `AttachmentType`.
- Relaciones: `project(): BelongsTo<Project>`, `user(): BelongsTo<User>`.
- `Project::attachments(): HasMany<ProjectAttachment>` (sin orden global; listar con `latest()`).
- Factory con estados `document()`, `image()`, `link()`.

## Reglas de validación

| Campo | Regla |
|-------|-------|
| `file` (documento) | requerido; MIME real en pdf, doc, docx, xls, xlsx, ppt, pptx, txt, md, csv; ≤ 15 MB |
| `file` (imagen) | MIME real en jpg/jpeg, png, webp, gif; ≤ 5 MB; ≤ 40 MP |
| `url` | requerido; `url:http,https`; ≤ 2048 |
| `title` | opcional; string; ≤ 255 |
| Cupo proyecto | `count < 30` y `sum(size) + nuevo ≤ 200 MB` (209 715 200 bytes) |

Mensajes en español con el límite explícito (p. ej. "Las imágenes no pueden superar 5 MB.").

## Reglas de autorización (`ProjectAttachmentPolicy`)

| Acción | Quién | Estado del proyecto |
|--------|-------|---------------------|
| `viewAny` / `view` (listar, abrir, descargar) | miembro (propietario o colaborador) | cualquiera |
| `create` (subir archivo, agregar link) | miembro | `active` |
| `update` (renombrar) / `delete` | autor del adjunto o propietario | `active` |

No miembros → 404 (convención del proyecto: `abort_unless($project->isMember(...), 404)`). Adjunto de otro proyecto → 404.

## Ciclo de vida

Sin estados. Creado → (renombrado)* → eliminado. Eliminar borra fila + archivo + miniatura. Eliminar el proyecto borra filas (FK) y el directorio `projects/{id}/` del disco.
