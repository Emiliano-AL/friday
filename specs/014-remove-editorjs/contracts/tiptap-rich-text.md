# Contrato: Rich text con Tiptap v3 (sustitución de EditorJS)

**Fecha**: 2026-10-02 | **Spec**: [spec.md](./spec.md) | **Data model**: [data-model.md](./data-model.md)

Contrato entre backend (Inertia/validación), los componentes Vue (`RichTextEditor`, `RichTextContent`), la utilidad de normalización y la migración de datos. Reemplaza al contrato de `specs/011-editorjs-descriptions/contracts/editorjs-descriptions.md`.

## 1. Formato de datos (columnas `projects.description`, `tasks.description`, `sprints.goal`)

| Estado | Representación |
| --- | --- |
| Contenido nuevo | `JSON.stringify(editor.getJSON())` — doc ProseMirror con `"type": "doc"` |
| Contenido heredado post-migración | texto plano (un párrafo) |
| Sin contenido | `''` o `NULL` |

El **servidor es pass-through**: recibe un string y lo persiste sin transformación. Validación (sin cambios, FR-007): `nullable|string|max:50000` (excepción preexistente: `Store/UpdateTaskRequest` sin `max` — ver data-model V1).

## 2. Utilidad `resources/js/utils/tiptapContent.ts` (nueva, sustituye a `blocks.ts`)

- `isTiptapJson(value): boolean` — true si es string JSON con `"type": "doc"`.
- `docFromPlainText(text): object` — construye el doc ProseMirror `{type:'doc', content:[{type:'paragraph', content:[{type:'text', text}]}]}` programáticamente (sin parsear HTML → sin riesgo de interpretar `<` del texto).
- `normalizeToTiptap(value: string|null|undefined): object` — si `isTiptapJson` → doc parseado; si vacío → doc vacío (`{type:'doc', content:[]}`); si no → `docFromPlainText`.
- Se elimina `utils/blocks.ts` completo (`sanitizeInline`, `blocksToHtml`, `blocksToPlainText`, `normalizeToBlocks`, `isBlocksJson`, `plainTextToBlocks`, `escapeHtml`) y `types/editorjs-marker.d.ts`. La sanitización deja de ser manual: la garantiza el schema de Tiptap + la política de enlaces (§4).

## 3. Componente `RichTextEditor.vue` (reescrito con Tiptap)

**Props** (idénticas a las actuales — los 3 formularios no cambian):

| Prop | Tipo | Default | Uso |
| --- | --- | --- | --- |
| `modelValue` | `string \| null` | — | contenido inicial (formato §1) |
| `placeholder` | `string` | `'Escribe la descripción…'` | — |
| `error` | `string` (opcional) | — | borde `border-error` + mensaje inline |
| `id` | `string` (opcional) | — | — |
| `minHeight` | `number` | `120` | altura mínima del área de edición |
| `maxHeightClass` | `string` | `''` | si se informa, scroll interno (`.max-h-[420px]` en TaskFormModal) |

**Expone** (`defineExpose`):
- `save(): Promise<string>` — `''` si `editor.isEmpty`; en otro caso `JSON.stringify(editor.getJSON())`.
- `focus(): void` — `editor.commands.focus()` (paridad con el contrato anterior).

**Comportamiento**:
- `useEditor({ editable: true, extensions: [StarterKit(§4), Placeholder.configure({ placeholder: props.placeholder })], content: normalizeToTiptap(props.modelValue) })`. `useEditor` destruye el editor en `onBeforeUnmount` automáticamente (no llamar a `destroy()`).
- Componente **uncontrolled**: sin `onUpdate`; el contenido solo se lee en `save()`. El submit de los formularios sigue siendo `form.description = (await ref.save()) ?? ''`.
- `watch(() => props.modelValue)` → `editor.commands.setContent(normalizeToTiptap(value))` (sincroniza resets externos; no puede buclear porque no hay `onUpdate`).
- Placeholder: CSS obligatorio `.tiptap p.is-editor-empty:first-child::before { content: attr(data-placeholder) }`.
- Estilos: wrapper con las mismas clases/tokens actuales (`bg-surface-container-lowest`, borde `border-outline-variant/40` o `border-error`, `focus-within:ring-primary/20`), y tema sobre `.tiptap` (no más clases `.ce-*`/`.cdx-*`).
- Toolbar propio (Tailwind v4): párrafo / H2 / H3 · negrita · cursiva · subrayado · código en línea · enlace · lista viñetas · lista numerada · cita · bloque de código · deshacer/rehacer. Botones con `editor.chain().focus().toggleX().run()` y estado activo vía `editor.isActive()`.

## 4. Política de enlaces (sustituye a `ALLOWED_HREF`)

```ts
StarterKit.configure({
    link: {
        isAllowedUri: (url, ctx) => ctx.defaultValidate(url) && /^(https?:|mailto:|#)/i.test(url),
        shouldAutoLink: (url) => /^(https?:|mailto:)/i.test(url),
        HTMLAttributes: { target: '_blank', rel: 'noopener noreferrer nofollow' },
    },
    heading: { levels: [2, 3] },
})
```

- Protocolos permitidos: `https?:`, `mailto:`, `#` (igual que la política anterior). `javascript:` y datos enriquecidos quedan fuera.
- Enlaces abren en pestaña nueva con `noopener noreferrer nofollow` (paridad con el render anterior).
- `openOnClick: false` en el editor (el clic en edición posiciona cursor; el clic en solo lectura navega).

## 5. Componente `RichTextContent.vue` (solo lectura, reescrito)

- Prop única `content: string|null` (sin cambio).
- `useEditor({ editable: false, extensions: [StarterKit(§4)], content: normalizeToTiptap(props.content) })` + `<editor-content>`. Placeholder con `showOnlyWhenEditable: true` (default) → nunca visible en detalle.
- Los consumidores actuales (`Projects/Show.vue`, `Sprints/SprintHeader.vue`) quedan sin cambios.

## 6. Migración de datos (idempotencia)

- Nueva migración `2026_10_02_000000_convert_block_content_to_plain_text.php`: valores con JSON de bloques EditorJS → texto plano visible (reglas en data-model.md). Valores planos/`null` → intactos. `down()` no-op.
- Re-ejecutar la migración no altera filas ya migradas (el texto plano no parsea como bloques).

## 7. Advertencias operativas (breaking changes Tiptap v3)

1. **`setContent` emite `onUpdate` por defecto en v3** (cambio respecto a v2). Este diseño es inmune (sin `onUpdate`), pero quien añada binding en vivo en el futuro debe conocerlo.
2. **El schema descarta silenciosamente** lo que no cumple el modelo al hacer `setContent` (`enableContentCheck: true` + `onContentError` si algún día se necesita detectarlo).
3. **El schema no sustituye la validación de servidor** (cita de las docs: "You should always validate user input") — §1 se mantiene.
4. `@tiptap/extensions` reúne en v3 lo que era `@tiptap/extension-*` granular: importar `{ Placeholder }` desde ahí.
