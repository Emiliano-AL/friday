# Data Model: Eliminación de EditorJS (contenido largo en Tiptap)

**Fecha**: 2026-10-02 | **Spec**: [spec.md](./spec.md) | **Research**: [research.md](./research.md)

## Resumen del impacto

Cambio de **formato de contenido** en tres columnas existentes; **no hay cambios de schema** (ni tablas, ni tipos de columna, ni casts, ni relaciones). El único nuevo "tipo" es el formato del string almacenado.

## Entidades afectadas

### Project / Sprint / Task — campos de contenido largo

| Campo | Tabla.columna | Tipo DB | Nullable | Cast | Fillable | Validación |
| --- | --- | --- | --- | --- | --- | --- |
| `description` | `projects.description` | `text` | sí | — | sí | `nullable\|string\|max:50000` |
| `description` | `tasks.description` | `text` | sí | — | sí | `nullable\|string` (Store/UpdateTaskRequest) · `nullable\|string\|max:50000` (SaveTaskRequest) — ver nota V1 |
| `goal` | `sprints.goal` | `text` | sí | `string` | sí | `nullable\|string\|max:50000` |

Ningún modelo tiene accessor/mutator para estos campos (el backend los trata como strings opacos, pass-through a Inertia).

## Formato del valor almacenado

Dos representaciones coexistentes en la misma columna (distinguidas por `utils/tiptapContent.ts` en el frontend, de forma análoga al actual `isBlocksJson`):

1. **Contenido nuevo (formato vigente)**: `JSON.stringify` del documento de Tiptap — objeto ProseMirror con shape `{ "type": "doc", "content": [ … ] }`. Se produce en `RichTextEditor.save()` vía `editor.getJSON()`. Cadena vacía `''` cuando el editor está vacío (nunca se persiste un doc vacío).
2. **Contenido heredado (tras migración)**: texto plano, resultado de aplanar el JSON de bloques EditorJS (research D6). Se interpreta como un único párrafo al editar o visualizar.

**Invariantes**:
- `null` / `''` = sin contenido (las vistas de solo lectura guardan con `v-if`, igual que hoy).
- El servidor nunca transforma el valor: recibe un string y lo persiste (los Form requests solo validan tipo y longitud).
- El render sanitizado es responsabilidad exclusiva del frontend (schema Tiptap + `isAllowedUri`, research D3); no existe render backend.

## Migración de datos

`2026_10_02_000000_convert_block_content_to_plain_text.php` (nueva, idempotente):

- **Alcance**: las 3 columnas de la tabla anterior.
- **Regla**: si el valor parsea como JSON con clave `blocks` de array (formato EditorJS) → reemplazar por el texto visible aplanado (`strip_tags` sobre `data.text`; listas con ítems unidos por `\n`; quote incluye caption si existe; code → `data.code`). Valores que ya son texto plano o `null` → sin tocar. Resultado vacío → `''`.
- **`down()`**: no-op (el formato bloques desaparece; la pérdida de estructura es aceptada — sin datos en producción, FR-004).
- La migración histórica `2026_09_30_000000_convert_long_text_fields_to_block_content.php` **no se modifica** ni se elimina.

## Transiciones de estado

No aplica (los campos no tienen máquina de estados). El ciclo de vida del contenido es: *vacío → texto plano heredado → doc Tiptap (al primera edición)*; cualquier edición futura reemplaza el valor completo.

## Notas

- **V1 — asimetría de validación preexistente**: las rutas `/projects/{project}/tasks*` no limitan longitud de `description` y las `/tasks*` sí (`max:50000`). Se conserva tal cual (fuera de alcance; FR-007 manda "sin cambios de comportamiento"). Anotado para revisión futura.
- **Factories**: ya generan texto plano (`fake()->optional()->sentence()` en Project/Task; Sprint no genera `goal`) → no requieren cambios; sus valores se interpretan como contenido heredado.
- **Límite de columna**: `text` (no `longText`) es suficiente: la validación `max:50000` acota el string antes de persistir, igual que con el JSON de bloques actual.
