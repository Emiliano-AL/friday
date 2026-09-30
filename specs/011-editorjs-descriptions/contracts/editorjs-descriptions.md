# Contrato UI: Editor de contenido enriquecido en descripciones (011)

Contrato entre backend (Inertia/validación) y frontend (Vue + EditorJS).
Referencias: [data-model.md](./data-model.md) (formato y normalización) y
[research.md](./research.md) (decisiones D1–D10).

## 1. Dependencia aprobada

Paquetes npm (aprobación explícita del usuario, spec FR-007):
`@editorjs/editorjs`, `@editorjs/header`, `@editorjs/list`, `@editorjs/quote`,
`@editorjs/code`, `@editorjs/inline-code`, `@editorjs/marker`. Cero cambios en
Composer. Negrita/cursiva/enlaces son inline tools del núcleo.

## 2. Utilidades compartidas (`resources/js/utils/blocks.ts`)

```ts
export interface EditorBlock {
    type: 'paragraph' | 'header' | 'list' | 'quote' | 'code';
    data: Record<string, unknown>;
}

export interface EditorBlocks {
    time?: number;
    blocks: EditorBlock[];
    version?: string;
}

export function isBlocksJson(value: unknown): value is EditorBlocks;
export function plainTextToBlocks(text: string): EditorBlocks; // párrafo único
export function normalizeToBlocks(
    value: string | null | undefined,
): EditorBlocks; // FR-005
export function blocksToHtml(blocks: EditorBlocks): string; // escape total + whitelist inline
export function blocksToPlainText(blocks: EditorBlocks): string; // excerpt/debug
```

`blocksToHtml` garantiza (SC-004): todo texto escapado; solo se re-habilitan
`b, i, strong, em, code, mark, br` y `a` con `href` en `http:`, `https:`,
`mailto:` o `#`; bloques desconocidos se omiten; `data.text`/`code`/`caption`
se tratan como texto escapado salvo la whitelist anterior.

## 3. `RichTextEditor.vue` (edición)

Props:

```ts
{
    modelValue: string | null;   // valor crudo del campo (JSON o texto plano); null = vacío
    placeholder?: string;        // default "Escribe la descripción…"
    error?: string;              // mensaje de validación (borde + mensaje inline)
    id?: string;                 // para <label for>
}
```

Expone vía `defineExpose`:

```ts
{
    save(): Promise<string>;   // JSON serializado de bloques (siempre; vacío = '{"blocks":[]}' o string vacío según acuerdo de vacío)
    isEmpty(): boolean;        // true si no hay bloques con contenido
    focus(): void;
}
```

Comportamiento: monta el editor en `onMounted` con `normalizeToBlocks(modelValue)`;
herramientas: paragraph (default), header (H2–H4), list, quote, code, inlineCode,
marker, inline bold/italic/link; tema claro con tokens de `DESIGN.md`;
`sanitize: true` en la configuración de pegado. El padre **no** usa `v-model` en
el submit: llama `await editorRef.save()` y asigna al `useForm`.

Vacío: `save()` devuelve `''` (string vacío) cuando `isEmpty()` — los formularios
envían `''` → el servidor guarda NULL como hoy con textarea vacío (regla
`nullable` + normalización del modelo si aplica; mantener comportamiento actual).

## 4. `RichTextContent.vue` (lectura)

```ts
props: { content: string | null; class?: string };
```

Renderiza `normalizeToBlocks(content)` → `blocksToHtml` dentro de un contenedor
tipografiado (prosa: `text-body-md`, espaciado entre bloques). `content` nulo o
sin bloques → no renderiza nada (el padre decide mostrar sección o no).
Implementado con `v-html` **solo** sobre el HTML ya saneado por `blocksToHtml`.

Uso: `pages/Projects/Show.vue` (descripción) y `pages/Sprints/Show.vue`
(objetivo). Listas/tarjetas de tareas no lo usan.

## 5. Formularios (integración)

- `TaskFormModal.vue`: textarea descripción → `<RichTextEditor ref="descriptionEditor" v-model="form.description" :error="form.errors.description" />`; en `submit()` hacer `form.description = await descriptionEditor.save()` antes de post/put; en modo "crear otra" el remontaje limpia el editor. **Eliminar** la nota "La barra de herramientas de marcado… próximamente" (FR-006).
- `ProjectFormModal.vue` y `SprintFormModal.vue`: misma sustitución en su campo (descripción / objetivo), preservando labels y errores inline.

Reglas de validación servidor (todas con mensaje ES "El contenido no puede superar los 50000 caracteres."):

```php
// SaveTaskRequest, StoreProjectRequest, UpdateProjectRequest
'description' => ['nullable', 'string', 'max:50000'],
// StoreSprintRequest, UpdateSprintRequest
'goal' => ['nullable', 'string', 'max:50000'],
```

La API acepta texto plano o JSON (permissiva por compatibilidad, FR-005).

## 6. Migración de datos

`database/migrations/2026_09_30_000000_convert_long_text_fields_to_block_content.php`
aplica la normalización de data-model §3 a las tres columnas en el `up()`
(leer → `isBlocksJson` equivalente PHP → envolver en párrafo si aplica →
escribir solo los cambios). `down()`: sin reversa de contenido (la conversión
conserva el texto visible; documentar en el propio archivo).

## 7. Tests de contrato (Pest)

- `DescriptionMigrationTest`: texto plano → párrafo (3 campos), JSON válido intacto, string raro → párrafo, NULL intacto, idempotencia.
- `RichTextValidationTest`: 50001 caracteres → error en los 3 campos; 50000 → ok; ausente → ok; regresión de guardado JSON en task/project/sprint y del test previo de goal>1000 (actualizar a >50000).
- Regresión: suites existentes de Tasks/Projects/Sprints siguen verdes (payloads `description`/`goal` como string sin cambio de shape).

## 8. Non-goals (explícitos)

Sin imágenes/adjuntos, sin import/export markdown, sin columnas nuevas, sin
edición colaborativa, sin render en listas, sin modo oscuro, sin soporte SSR
(la app no usa SSR).
