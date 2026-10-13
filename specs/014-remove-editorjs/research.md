# Research: Eliminación de EditorJS y adopción de Tiptap v3

**Fecha**: 2026-10-02 | **Fuentes**: documentación oficial de Tiptap v3 (tiptap.dev/docs) verificada por fetch directo, inventario del código del repo (exploración de agentes).

## D1. Versión de Tiptap y paquetes exactos

**Decision**: Tiptap **v3** con cuatro paquetes: `@tiptap/vue-3`, `@tiptap/pm`, `@tiptap/starter-kit`, `@tiptap/extensions`.

**Rationale**: Las docs actuales documentan v3 por defecto; empezar en v2 sería deuda inmediata. En v3 los paquetes granulares (`@tiptap/extension-placeholder`, `@tiptap/extension-character-count`, …) se consolidaron en `@tiptap/extensions` ([upgrade guide](https://tiptap.dev/docs/guides/upgrade-tiptap-v2)). `StarterKit` v3 incluye todo lo requerido por FR-005 — Bold, Italic, **Underline**, Code, **Link**, Heading, BulletList/OrderedList/ListItem, Blockquote, CodeBlock, UndoRedo, HardBreak, HorizontalRule — así que Link y Underline no requieren paquetes extra ([StarterKit](https://tiptap.dev/docs/editor/extensions/functionality/starterkit)).

**Alternatives considered**: v2 (rechazada: versión legacy, migración inmediata adicional); instalar extensiones granulares individuales (rechazada: en v3 el soporte granular se redujo a favor de StarterKit/extensions).

## D2. Formato de persistencia: JSON del documento (no HTML)

**Decision**: Persistir `JSON.stringify(editor.getJSON())` en las columnas `text` existentes. Contenido heredado migrado a texto plano; una utilidad `normalizeToTiptapJson(value)` distingue ambos (mismo patrón que el actual `isBlocksJson`/`normalizeToBlocks`).

**Rationale**: (1) El render de solo lectura se hace con una instancia Tiptap `editable: false` alimentada con `setContent(json)` — recomendación explícita de las docs ("achieve the exact same rendering… without duplicating your CSS") — y **no requiere `v-html` ni DOMPurify**. (2) El schema de ProseMirror descarta al parsear cualquier nodo/mark no declarado, lo que mitiga XSS estructuralmente ([output guide](https://tiptap.dev/docs/guides/output-json-html), [schema](https://tiptap.dev/docs/editor/core-concepts/schema)). (3) Evita una dependencia de sanitización que el usuario no aprobó. (4) Conserva la arquitectura previa "JSON en columna + renderer", mínima sorpresa para el equipo.

**Alternatives considered**: HTML (`getHTML()` + `v-html`) — rechazado: exige DOMPurify (dependencia no aprobada) y duplica pipeline de sanitización; `generateHTML` de `@tiptap/html` — rechazado: solo necesario para HTML embebible fuera de la app (emails/SSR), YAGNI.

## D3. Sanitización y seguridad de enlaces

**Decision**: Tres capas, sin librerías nuevas: (1) schema estricto de StarterKit al renderizar (setContent descarta lo desconocido); (2) `StarterKit.configure({ link: { isAllowedUri: …, shouldAutoLink: … } })` restringiendo protocolos a `https?:`, `mailto:` y `#` (replica la política actual `ALLOWED_HREF` de `blocks.ts`), con `HTMLAttributes: { target: '_blank', rel: 'noopener noreferrer nofollow' }` para mantener el comportamiento actual de enlaces (pestaña nueva protegida); (3) validación de servidor existente sin cambios (`nullable|string|max:50000`, FR-007), conforme a la advertencia oficial de las docs: *"You should always validate user input."*

**Rationale**: Las docs advierten que el schema **no es un sanitizer completo** (los atributos de extensiones permitidas pasan tal cual — caso real: `href` con `javascript:`), de ahí `isAllowedUri` ([Link](https://tiptap.dev/docs/editor/extensions/marks/link)).

**Alternatives considered**: DOMPurify en cliente — rechazado (dependencia no aprobada; innecesaria sin `v-html`); validación estructural del JSON en servidor (FormRequest custom que verifique shape del doc) — posible endurecimiento futuro, fuera de MVP: sin datos de producción y con schema+isAllowedUri el riesgo residual es mínimo.

## D4. Contrato del componente de edición (sin cambios externos)

**Decision**: Reescribir `resources/js/Components/RichText/RichTextEditor.vue` con Tiptap conservando **exactamente** el contrato externo actual: props `modelValue: string|null`, `placeholder?`, `error?`, `id?`, `minHeight?` (default 120), `maxHeightClass?` (default `''`); expose `save(): Promise<string>` (JSON del doc, `''` cuando el editor está vacío) y `focus(): void`. Se eliminan del expose los métodos `render()` e `isEmpty()` (hoy código muerto: ningún consumidor los llama).

**Rationale**: Inventario de consumidores: `ProjectFormModal`, `SprintFormModal` y `TaskFormModal` hacen exclusivamente `await editorRef.save()` en el submit (`form.description = (await ref.save()) ?? ''`). `Modal.vue` destruye el slot ~200 ms al cerrar y lo recrea al abrir → el componente se re-monta y `useEditor({ content: normalizeToTiptapJson(modelValue) })` siempre lee fresco. Con cero cambios en consumidores se reduce el blast radius a un solo archivo (SC-003/SC-005).

**Detalles Tiptap**: `useEditor` destruye el editor automáticamente en `onBeforeUnmount` (no hay que llamar `destroy()`). Vacío: `editor.isEmpty`. **Breaking change v3**: `setContent` emite `onUpdate` por defecto — irrelevante aquí porque el componente es *uncontrolled* (sin `onUpdate`; el contenido solo se lee en `save()`), y el `watch(modelValue)` → `setContent` no puede entrar en loop. El watch además corrige de paso el quirk del modo "crear otra" de TaskFormModal (hoy el contenido viejo persiste al resetear el formulario).

**Alternatives considered**: Rediseñar el contrato (v-model con onUpdate en vivo) — rechazado: tocaría los 3 formularios sin beneficio; mantener `render()`/`isEmpty()` expuestos — rechazado: código muerto, contradice FR-006.

## D5. Vista de solo lectura: instancia `editable: false`

**Decision**: Reimplementar `RichTextContent.vue` con `useEditor({ editable: false, extensions: [StarterKit(configuración idéntica)] })` + `<editor-content>`, misma prop `content: string|null`. Placeholder con `showOnlyWhenEditable: true` (default) para que nunca aparezca en vistas de detalle.

**Rationale**: Es la "Option 1" de las docs: renderizado idéntico al editor, sin duplicar CSS ([output guide](https://tiptap.dev/docs/guides/output-json-html)). Solo hay 2 consumidores (`Projects/Show.vue`, `Sprints/SprintHeader.vue`) y ambos guardan con `v-if="…"` → el valor vacío nunca se monta; se mantiene la robustez degradando a texto plano vía la misma utilidad de normalización. Nota de inventario: ninguna vista de Tasks muestra descripciones (solo el modal de edición), así que no hay tercer consumidor que cubrir.

**Alternatives considered**: `generateHTML` de `@tiptap/html` en browser — innecesario (instancia read-only ya renderiza); `ueberdosis/prosemirror-to-html` PHP server-side — innecesario (todo el render es frontend).

## D6. Migración de datos: bloques → texto plano (idempotente)

**Decision**: Nueva migración `2026_10_02_000000_convert_block_content_to_plain_text.php` que invierte la de 2026_09_30: para `projects.description`, `tasks.description`, `sprints.goal`, si el valor es JSON de bloques EditorJS (tiene clave `blocks` array), extrae el texto visible por bloque — paragraph/header → `data.text` con `strip_tags` (conserva el texto de enlaces/negritas, descarta markup), list → ítems unidos con salto de línea, quote → `data.text` (+ caption si existe), code → `data.code` — y guarda texto plano. Idempotente: tras convertir, el valor ya no parsea como bloques → re-ejecución segura. `down()` = no-op (el formato bloques muere). Resultado vacío → `''`.

**Rationale**: FR-004 (opción B elegida por el usuario: "no tenemos aun texto en produccion. No se pierde nada"). El texto visible se preserva igual que la migración original hacía en sentido inverso. La vieja migración y su test (`DescriptionMigrationTest`) quedan intactas como historia.

**Alternatives considered**: Modificar/eliminar la migración histórica — rechazado (rewriting history aplicada en entornos existentes); convertir directamente bloques → JSON Tiptap — rechazado: más lógica para preservar formato de contenido que según el usuario no existe en producción; texto plano es el formato puente más simple y el nuevo editor lo interpreta sin pérdida.

## D7. Toolbar y extensiones de UX

**Decision**: Toolbar propio en el editor (botones con `editor.chain().focus().toggleX().run()` + `editor.isActive()`), estilado con tokens Tailwind v4 del proyecto. Contenido: párrafo/H2/H3 (la config actual usa niveles 2–4; se estandariza a H2/H3 por uso real), negrita, cursiva, subrayado, código en línea, enlace (con prompt para URL), lista viñetas, lista numerada, cita, bloque de código, deshacer/rehacer. Placeholder vía `Placeholder` de `@tiptap/extensions` con el CSS obligatorio (`p.is-editor-empty:first-child::before`).

**Rationale**: Replica las capacidades actuales (FR-005) más Underline/Strike que vienen gratis en StarterKit v3. Los shortcuts de teclado estándar de StarterKit (Mod-B/I/U, Mod-Shift-8/7, Mod-Shift-B, Mod-Alt-C, headings Mod-Alt-2/3) quedan activos sin trabajo extra.

**Alternatives considered**: Editor sin toolbar (solo Markdown shortcuts) — rechazado: regresión de UX frente al editor actual; toolbar flotante tipo EditorJS — rechazado: más complejidad, el patrón fijo de Tiptap es suficiente y más simple.

## D8. Validación de servidor y tests

**Decision**: Form requests sin cambios (FR-007). Se acepta tanto texto plano como JSON Tiptap (el backend es pass-through, igual que hoy). Se documenta la inconsistencia preexistente: `StoreTaskRequest`/`UpdateTaskRequest` (rutas `/projects/{p}/tasks*`) no tienen `max` en `description` mientras `SaveTaskRequest` (rutas `/tasks*`) sí (`max:50000`) — **se deja como está** (fuera del alcance del spec) y se anota en el contrato.

**Rationale**: El spec fija comportamiento, no configuración interna; tocar validación ampliaría el alcance sin mandato.

**Tests**: (1) Nuevo `BlockContentToPlainTextMigrationTest` (misma estructura que `DescriptionMigrationTest`: conversión, idempotencia, JSON no-bloque intacto, null intacto). (2) `RichTextValidationTest:84-119` se actualiza: el caso "structured block content persists as-is" pasa a usar JSON con shape de doc Tiptap y texto plano (el comportamiento "el servidor persiste el string tal cual" no cambia, pero el fixture debe reflejar el formato vigente). (3) Los tests de límite `max:50000` y los CRUD con texto plano siguen pasando sin cambios (factories ya generan texto plano). (4) `DescriptionMigrationTest` no se toca.

## D9. Riesgos y notas

- **Breaking change v3 (`setContent` emite update)**: mitigado por diseño uncontrolled (D4). Documentado en el contrato para quien toque el componente después.
- **Contenido heredado con markup inline** (`<b>`, `<a>` dentro de `data.text` de EditorJS): la migración lo aplana con `strip_tags` → texto legible; el formato inline se pierde (aceptado: sin datos en producción).
- **Columnas `text`**: JSON Tiptap puede superar en tamaño al texto visible; el límite `max:50000` se aplica sobre el string almacenado (misma política actual con JSON de bloques).
- **Tiptap Cloud/Platform**: el core es MIT y funciona 100% local sin cuenta ni API key ([pricing](https://tiptap.dev/pricing)); colaboración/comments/history cloud quedan fuera de alcance (YAGNI).
- **Rama git**: `014-remove-editorjs` no ha sido creada — la creación de rama era responsabilidad del hook git (no instalado); el working tree tiene cambios sin commitear de specs 012/013, por lo que el cambio de rama se deja decisión del usuario.
