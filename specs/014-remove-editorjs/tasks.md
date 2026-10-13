---
description: 'Task list for feature implementation'
---

# Tasks: Eliminación del editor de bloques EditorJS

**Input**: Design documents from `/specs/014-remove-editorjs/` (plan.md, spec.md, research.md, data-model.md, contracts/tiptap-rich-text.md, quickstart.md)

**Prerequisites**: plan.md ✅ spec.md ✅ research.md ✅ data-model.md ✅ contracts/ ✅

**Tests**: INCLUIDAS. La constitución (Principio III, Test-First con Pest) exige cobertura para la lógica de dominio (migración) y el spec SC-005 exige suite verde; los tests existentes afectados se actualizan.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- Include exact file paths in descriptions

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Cambio de dependencias: salen los 7 paquetes EditorJS, entran los 4 de Tiptap v3.

- [X] T001 Ejecutar `npm uninstall @editorjs/editorjs @editorjs/code @editorjs/header @editorjs/inline-code @editorjs/list @editorjs/marker @editorjs/quote` y `npm install @tiptap/vue-3 @tiptap/pm @tiptap/starter-kit @tiptap/extensions`, y verificar en `package.json` que no queda ningún `@editorjs/*` y que aparecen los 4 paquetes `@tiptap/*` (aprobación explícita registrada en spec FR-005)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Utilidades compartidas, config de StarterKit y migración de datos. Ninguna historia de usuario puede cerrarse sin esta fase.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T002 [P] Crear `resources/js/utils/tiptapContent.ts` con `isTiptapJson`, `docFromPlainText` y `normalizeToTiptap` según contrato §2 (sustituto de `utils/blocks.ts`; `docFromPlainText` construye el doc programáticamente, sin parsear HTML)
- [X] T003 [P] Crear `resources/js/Components/RichText/starterKit.ts` que exporte el `StarterKit` configurado según contrato §4: `link: { isAllowedUri: (url, ctx) => ctx.defaultValidate(url) && /^(https?:|mailto:|#)/i.test(url), shouldAutoLink: (url) => /^(https?:|mailto:)/i.test(url), HTMLAttributes: { target: '_blank', rel: 'noopener noreferrer nofollow' } }` y `heading: { levels: [2, 3] }` (config compartida por editor y solo lectura)
- [X] T004 Escribir el test (debe FALLAR antes de T005) `tests/Feature/RichText/BlockContentToPlainTextMigrationTest.php` siguiendo el patrón de `DescriptionMigrationTest.php` (incluir el archivo de migración y llamar a `->up()`): JSON de bloques EditorJS → texto plano en `projects.description`, `tasks.description` y `sprints.goal`; markup inline de `data.text` eliminado con `strip_tags` (conserva texto de enlaces/negritas); listas con ítems unidos por `\n`; quote incluye `caption`; `code` → `data.code`; resultado vacío → `''`; `null` y texto plano intactos; segunda ejecución idempotente
- [X] T005 Crear la migración `database/migrations/2026_10_02_000000_convert_block_content_to_plain_text.php`: para las 3 columnas, si el valor es JSON con clave `blocks` de tipo array → reemplazar por el texto visible aplanado (reglas de data-model.md); chunkById como la migración 2026_09_30; `down()` no-op con comentario; verificar que T004 pasa en verde

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Edición de descripciones sin el editor de bloques (Priority: P1) 🎯 MVP

**Goal**: Los formularios de proyecto, sprint y tarea editan descripción/goal con Tiptap conservando el contrato externo (props + `save(): Promise<string>`); los 3 consumidores no cambian.

**Independent Test**: Abrir el modal de edición de una tarea, aplicar H2/negrita/lista/cita, guardar, reabrir: contenido idéntico; sin errores de consola. Cubre spec US1, FR-002, FR-005, FR-008; SC-002, SC-003.

### Tests for User Story 1 ⚠️

- [X] T006 [P] [US1] Actualizar `tests/Feature/RichText/RichTextValidationTest.php` (líneas 84-119): sustituir los fixtures JSON de EditorJS por JSON con shape de doc Tiptap (`{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"..."}]}]}`) y por texto plano, manteniendo la aserción de persistencia verbatim del string y los tests de límite `max:50000` existentes

### Implementation for User Story 1

- [X] T007 [US1] Reescribir el núcleo de `resources/js/Components/RichText/RichTextEditor.vue`: `useEditor({ editable: true, extensions: [starterKit, Placeholder.configure({ placeholder: props.placeholder })], content: normalizeToTiptap(props.modelValue) })` (Placeholder importado de `@tiptap/extensions`); props `modelValue: string|null`, `placeholder?`, `error?`, `id?`, `minHeight?` (default 120), `maxHeightClass?` (default `''`) sin cambios; `defineExpose({ save, focus })` donde `save(): Promise<string>` devuelve `''` si `editor.isEmpty` y si no `JSON.stringify(editor.getJSON())`; `watch(() => props.modelValue)` → `editor.commands.setContent(normalizeToTiptap(value))`; componente uncontrolled (sin `onUpdate`); sin llamada manual a `destroy()`
- [X] T008 [P] [US1] Crear `resources/js/Components/RichText/RichTextToolbar.vue` (prop `editor: Editor`) con botones según contrato §3: párrafo/H2/H3, negrita, cursiva, subrayado, código en línea, enlace (prompt para URL), lista viñetas, lista numerada, cita, bloque de código, deshacer/rehacer — usando `editor.chain().focus().toggleX().run()`, estado activo con `editor.isActive()` y deshabilitado con `editor.can()`
- [X] T009 [US1] Integrar el toolbar en `resources/js/Components/RichText/RichTextEditor.vue` y portar los estilos: wrapper conserva clases actuales (`bg-surface-container-lowest`, borde `border-outline-variant/40` o `border-error` con `error`, `rounded-lg shadow-xs`, `focus-within:ring-primary/20`), scroll interno cuando `maxHeightClass` se informa, tema sobre selectores `.tiptap` (eliminar todo `:deep(.ce-*)`/`.cdx-*`) con tokens `--color-*`/`--font-inter`, CSS obligatorio del Placeholder (`.tiptap p.is-editor-empty:first-child::before`)
- [X] T010 [US1] Verificar los flujos de los tres formularios sin cambios de código (`resources/js/Components/Projects/ProjectFormModal.vue`, `resources/js/Components/Sprints/SprintFormModal.vue`, `resources/js/Components/Tasks/Board/TaskFormModal.vue`): ejecutar `php artisan test tests/Feature/Projects tests/Feature/Sprints tests/Feature/Tasks tests/Feature/RichText --compact` y los escenarios manuales 1, 2, 3, 6, 7 y 9 de quickstart.md (formato, goal, proyecto, vaciado, errores inline, "crear otra"); consola del navegador limpia (SC-003)

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Visualización del contenido existente (Priority: P2)

**Goal**: Las vistas de detalle renderizan el formato Tiptap con formato y el contenido heredado migrado como texto plano, siempre sanitizado; los 2 consumidores no cambian.

**Independent Test**: Detalle de proyecto y cabecera de sprint muestran contenido con formato (escenarios 4 y 5 de quickstart.md); enlaces seguros en pestaña nueva. Cubre spec US2, FR-003, FR-009; SC-001.

### Implementation for User Story 2

- [X] T011 [US2] Reescribir `resources/js/Components/RichText/RichTextContent.vue`: `useEditor({ editable: false, extensions: [starterKit], content: normalizeToTiptap(props.content) })` + `<editor-content>`; misma única prop `content: string|null`; sin toolbar; Placeholder con `showOnlyWhenEditable: true` (default) para que nunca aparezca en detalle
- [X] T012 [P] [US2] Portar estilos de solo lectura (clases Tailwind arbitrarias actuales de `RichTextContent.vue` sobre `.tiptap`, p. ej. `text-body-sm`, `[&_h2]:...`, `[&_blockquote]:...`) y verificar que `resources/js/pages/Projects/Show.vue` (líneas 408-412) y `resources/js/Components/Sprints/SprintHeader.vue` (líneas 108-112) quedan sin cambios y renderizan correctamente contenido nuevo (doc Tiptap) y heredado (texto plano)
- [X] T013 [US2] Verificar escenarios de quickstart 4, 5 y 8: contenido heredado legible como texto simple, edición de heredado convierte a formato Tiptap, enlaces `javascript:` rechazados por `isAllowedUri` y pegado de HTML externo sin ejecución de scripts

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - Desinstalación completa y sin código muerto (Priority: P3)

**Goal**: Cero paquetes, componentes, tipos y utilidades del ecosistema EditorJS; build, typecheck y suite verdes.

**Independent Test**: `grep -ri "@editorjs" package.json resources/` sin coincidencias; `npm run build` y `npx vue-tsc --noEmit` sin errores; `php artisan test --compact` verde. Cubre spec US3, FR-001, FR-006; SC-004, SC-005.

### Implementation for User Story 3

- [X] T014 [US3] Eliminar `resources/js/utils/blocks.ts` y `resources/js/types/editorjs-marker.d.ts` (reemplazados por `tiptapContent.ts` y StarterKit según contrato §2/§4)
- [X] T015 [P] [US3] Barrido de referencias: `grep -rin "editorjs\|blocks.ts\|@/utils/blocks" resources/ package.json` (y `blocksToHtml|normalizeToBlocks|sanitizeInline|isBlocksJson|plainTextToBlocks|blocksToPlainText|escapeHtml` en `resources/js`) → corregir cualquier resto antes de continuar
- [X] T016 [US3] Ejecutar `npx vue-tsc --noEmit` y `npm run build` y corregir todos los errores de tipado/compilación resultantes en `resources/js/`
- [X] T017 [US3] Ejecutar `php artisan test --compact` (suite completo) y dejarlo verde, incluidos `tests/Feature/RichText/` completo

**Checkpoint**: All user stories should now be independently functional

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Calidad final y validación end-to-end de los criterios de éxito.

- [X] T018 [P] Ejecutar `vendor/bin/pint --dirty` para formatear los archivos PHP tocados (migración y tests)
- [ ] T019 Ejecutar la checklist completa de cierre de `quickstart.md` (suite verde, build/typecheck limpios, cero referencias `@editorjs`, escenarios manuales 1-10) y confirmar SC-001 a SC-005; revisar logs del navegador con las herramientas de Boost ante cualquier error de consola

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup (T001) — BLOCKS all user stories (T002 y T003 son [P] entre sí; T004→T005 en secuencia test-first)
- **User Stories (Phase 3-5)**: Dependen de la fase Foundational
- **Polish (Phase 6)**: Depends on all user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational — no dependencies on other stories. 🎯 MVP.
- **User Story 2 (P2)**: File-level puede empezar tras Foundational (solo necesita T002/T003), pero la coherencia end-to-end del formato (guardar en US1, leer en US2) requiere US1; orden recomendado US1 → US2.
- **User Story 3 (P3)**: Depends on US1 + US2 completos (elimina el código que aún usan durante la transición: el render viejo por bloques sigue sirviendo texto plano heredado hasta que US2 aterriza).

### Within Each User Story

- Tests MUST be written and FAIL before implementation (T004 antes que T005; T006 es actualización de fixtures de un comportamiento pass-through que ya pasa)
- T007 (núcleo del editor) antes que T009 (integración toolbar/estilos); T008 es [P] porque es archivo aparte
- Story complete antes de moverse a la siguiente prioridad

### Parallel Opportunities

- **Fase 2**: T002 (utils) ∥ T003 (starterKit) ∥ T004 (test migración) — archivos distintos; T005 cierra la secuencia
- **Fase 3**: T006 (fixtures de test) ∥ T008 (toolbar) mientras T007 avanza; T009 depende de T007+T008
- **Fase 4**: T012 (estilos/verificación de consumidores) ∥ T011 solo si se tocan archivos distintos — recomendado secuencial por ser el mismo componente
- **Fase 5**: T015 (barrido grep) ∥ T018 (pint, archivos PHP distintos)
- **US1 y US2 en paralelo por dos desarrolladores** tras Foundational, aceptando el riesgo de merge en `RichTextContent.vue`

---

## Parallel Example: User Story 1

```text
Task: "Actualizar fixtures en tests/Feature/RichText/RichTextValidationTest.php (T006)"
Task: "Crear resources/js/Components/RichText/RichTextToolbar.vue (T008)"
  → mientras avanza: "Reescribir núcleo de resources/js/Components/RichText/RichTextEditor.vue (T007)"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1: Setup (T001)
2. Phase 2: Foundational (T002-T005) — CRITICAL
3. Phase 3: User Story 1 (T006-T010)
4. **STOP and VALIDATE**: escenarios 1, 2, 3, 6, 7, 9 de quickstart.md + suites Pest afectadas
5. En este punto el editor nuevo ya guarda formato; la vista de detalle vieja muestra heredado como texto (legible) y JSON Tiptap como texto crudo temporal — se resuelve en US2

### Incremental Delivery

1. Setup + Foundational → migración aplicada, utilidades listas
2. US1 → edición con formato funcional (MVP)
3. US2 → lectura coherente del nuevo formato + heredado
4. US3 → remoción total verificada (grep/build/typecheck/suite)
5. Polish → SC-001 a SC-005 confirmados

### Parallel Team Strategy

1. Equipo completa Setup + Foundational
2. Desarrollador A: US1 (editor + toolbar) · Desarrollador B: US2 (solo lectura, partiendo del contrato) · pacto: nadie toca `starterKit.ts` sin avisar
3. US3 y Polish en conjunto al final

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Verify tests fail before implementing (constitución, Principio III)
- Commit after each task or logical group
- La rama git `014-remove-editorjs` no existe aún — crearla antes de empezar T001 si se desea aislar el trabajo
- Known issue fuera de alcance (documentado en data-model.md V1): `StoreTaskRequest`/`UpdateTaskRequest` sin `max` en `description` — NO se toca en esta feature
