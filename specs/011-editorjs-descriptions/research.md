# Research: Editor de contenido enriquecido en descripciones (011)

**Fecha**: 2026-09-30 · **Fase**: 0 (Outline & Research)

Sin NEEDS CLARIFICATION en el Technical Context (stack fijo; la única
incógnita real era el conjunto exacto de paquetes y el patrón de
integración, resueltos con la documentación oficial base concepts del editor
y el estado actual del código).

## D1. Paquetes exactos del editor

- **Decision**: `@editorjs/editorjs` (núcleo) + herramientas oficiales de texto: `@editorjs/header` (encabezados), `@editorjs/list` (viñetas/numeradas), `@editorjs/quote` (citas), `@editorjs/code` (fragmentos de código), `@editorjs/inline-code` (código en línea) y `@editorjs/marker` (resaltado). Párrafo es el bloque por defecto del núcleo y **negrita, cursiva y enlaces vienen integrados en el núcleo** (inline toolbar), por lo que no requieren paquete.
- **Rationale**: cubre exactamente FR-001 con el mínimo de paquetes; todo es first-party del mismo ecosistema del editor (mantenimiento y API coherentes).
- **Alternativas**: paquetes de terceros para bold/italic (innecesarios: ya están en el núcleo); `@editorjs/image`/`@editorjs/attaches` (rechazados: imágenes/adjuntos fuera de alcance, FR-006); `@editorjs/table` (rechazado: YAGNI).

## D2. Almacenamiento y migración

- **Decision**: mismas columnas (`tasks.description`, `projects.description`, `sprints.goal`), ahora con JSON de bloques. Migración única: para cada fila con valor no vacío, si `json_decode` no produce un objeto con `blocks` (array), se reemplaza por `{"blocks":[{"type":"paragraph","data":{"text":"<texto escapado>"}}]}`. Los valores JSON válidos se dejan intactos.
- **Rationale**: spec FR-002 (misma columna, sin esquema nuevo) y SC-002 (100 % del contenido previo visible sin pasos manuales). La migración eager mantiene el invariante "todo lo guardado es bloques" desde el día uno.
- **Alternativas**: normalización perezosa solo en lectura (rechazada: deja el invariante roto y duplica lógica en cada superficie); columna nueva (rechazada: contradice FR-002).

## D3. Renderizado de lectura (seguridad anti-XSS)

- **Decision**: componente `RichTextContent.vue` que convierte bloques a HTML **escapando todo el texto y re-habilitando solo una lista blanca de etiquetas en línea** (`b`, `i`, `strong`, `em`, `code`, `mark`, `br` y `a` con `href` limitado a `http://`, `https://`, `mailto:` y `#`). Valores que no sean bloques válidos se escapan completos y se muestran como párrafo de texto plano (FR-005).
- **Rationale**: SC-004 (cero ejecución de contenido). El editor ya sanea al pegar, pero el renderizado no confía en el dato almacenado: whitelist + escape total es la defensa en profundidad.
- **Alternativas**: `v-html` directo del JSON (rechazada: insegura); generar HTML en PHP con una librería de render (rechazada: añadiría dependencia Composer y el proyecto renderiza en cliente).

## D4. Componente editor (`RichTextEditor.vue`)

- **Decision**: envoltorio Vue que monta el editor en `onMounted` (los diálogos usan `v-if`, así que cada apertura remonta el componente), convierte el valor inicial con `normalizeToBlocks` (texto plano → párrafo), y expone `save(): Promise<string>` (JSON serializado) y `isEmpty(): boolean` vía `defineExpose`. Los formularios llaman `await editor.save()` en el submit, antes del `useForm.post/put`.
- **Rationale**: el guardado del editor es asíncrono y el ciclo de vida debe atarse a los diálogos existentes (foco inicial, ESC, click-fuera, doble envío bloqueado con `form.processing`).
- **Alternativas**: `v-model` sincrónico (rechazada: `editor.save()` es async por diseño); instancia global reutilizada (rechazada: estados entre diálogos).

## D5. Integración en los tres diálogos

- **Decision**: en `TaskFormModal`, `ProjectFormModal` y `SprintFormModal` se sustituye el `<textarea>` de descripción/objetivo por `<RichTextEditor>` (misma etiqueta/estructura de campo, errores inline con `form.errors`). En el diálogo de tarea se **elimina la nota** "La barra de herramientas de marcado… llegará próximamente" (FR-006 retira esa promesa). El patrón "Crear otra al guardar" re-monta el editor tras guardar (reset por remontaje).
- **Rationale**: UX homogénea y mínimo diff por formulario.
- **Alternativas**: mantener textarea con toggle "modo avanzado" (rechazada: convivencia que la spec descarta en Assumptions).

## D6. Validación

- **Decision**: reglas `nullable|string|max:50000` en descripción de tarea y proyecto (hoy sin tope) y en objetivo de sprint (hoy `max:1000`, incompatible con JSON de bloques). Mensaje en español compartido: "El contenido no puede superar los 50000 caracteres."
- **Rationale**: FR-004 (tamaño máximo razonable + errores inline). 50 k cubre ~10 000 palabras (SC-003) con margen para marcado de bloques.
- **Alternativas**: validar estructura JSON estricta en el servidor (rechazada: el API permanece permisivo a texto plano por compatibilidad y FR-005; el editor siempre emite JSON).

## D7. Superficies de lectura

- **Decision**: `RichTextContent` se usa en `pages/Projects/Show.vue` (descripción del proyecto) y `pages/Sprints/Show.vue` (objetivo del sprint). En tareas, la "lectura" es el propio diálogo de edición; las listas/tarjetas (Mis Tareas, tableros, filas) **no** renderizan descripción (spec, edge case final).
- **Rationale**: FR-003 y el edge case de listas.

## D8. Tests (test-first, Pest)

- **Decision**: `DescriptionMigrationTest`: texto plano → párrafo (los tres campos), JSON válido intacto, JSON inválido/absurdo envuelto como párrafo, nulos intactos; idempotencia (correr la migración dos veces no cambia nada). `RichTextValidationTest`: 50001 caracteres rechazados en los tres campos, 50000 aceptados, campos ausentes aceptados; regresión de guardado con contenido JSON (task/project/sprint). Ajuste de tests existentes que usen `goal` de 1000+ (ninguno: el tope anterior solo se usaba en el test de >1000 de SprintGoalTest — se actualiza a >50000).
- **Rationale**: constitución III y cobertura de FR-002/FR-004/FR-005 en servidor. El renderizado cliente se verifica en navegador (quickstart §4–§6).
- **Alternativas**: tests de componentes Vue (rechazada: no hay infraestructura Dusk/component-test en el repo; verificación manual + types).

## D9. Tipos compartidos

- **Decision**: `utils/blocks.ts` exporta `EditorBlock` / `EditorBlocks` (tipos TS del JSON), `isBlocksJson(value: unknown): value is EditorBlocks`, `plainTextToBlocks(text)`, `normalizeToBlocks(value: string | null)` (pasa por `isBlocksJson`, si no, párrafo; null → bloques vacíos) y `blocksToHtml(blocks): string` (con la sanitización de D3).
- **Rationale**: contrato único entre editor, formularios y lectura; tipado estricto (constitución IV).

## D10. Renderizado del editor en modo claro

- **Decision**: estilos del editor alineados a tokens de `DESIGN.md` (surface/fondo, bordes hairline, tipografía Inter, anillos de foco), encapsulados en la hoja del componente. Sin modo oscuro (spec Assumptions).
- **Rationale**: coherencia visual del producto.

## Estado

Sin NEEDS CLARIFICATION pendientes. Listo para Phase 1 (data-model, contracts, quickstart).
