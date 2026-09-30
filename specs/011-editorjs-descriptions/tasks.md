---
description: 'Task list for feature 011: Editor de contenido enriquecido en descripciones'
---

# Tasks: Editor de contenido enriquecido en descripciones (011)

**Input**: Design documents from `/specs/011-editorjs-descriptions/` (plan.md, research.md, data-model.md, contracts/editorjs-descriptions.md, quickstart.md)

**Prerequisites**: plan.md ✅ spec.md ✅ research.md ✅ data-model.md ✅ contracts/ ✅

**Tests**: Incluidos — constitución III (Test-First con Pest). Tests de migración/validación primero en rojo, luego implementación en verde.

**Organization**: Por historia de usuario (US1 tareas / US2 proyectos / US3 sprints); la base compartida (editor + render + utilidades) se construye en Foundacional y US1.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: paralelizable (archivos distintos, sin dependencias pendientes)
- **[Story]**: US1, US2, US3 (trazabilidad con spec.md)
- Rutas exactas en cada descripción

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Dependencia aprobada del editor (contrato §1, spec FR-007)

- [x] T001 Instalar los paquetes exactos del editor con `npm install --save @editorjs/editorjs @editorjs/header @editorjs/list @editorjs/quote @editorjs/code @editorjs/inline-code @editorjs/marker` y verificar que los 7 aparecen en `package.json`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Utilidades compartidas, migración de datos y validación — bloquean a las tres historias

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T002 Crear `tests/Feature/RichText/DescriptionMigrationTest.php` y `tests/Feature/RichText/RichTextValidationTest.php` (RED, con `RefreshDatabase`): migración — texto plano de los TRES campos (`tasks.description`, `projects.description`, `sprints.goal`) queda como `{"blocks":[{"type":"paragraph","data":{"text":...}}]}` con el texto exacto (incluye caracteres especiales), JSON válido con `blocks` queda intacto, string no-JSON se envuelve en párrafo, `NULL` intacto, correr la migración dos veces no cambia nada (idempotencia); validación — 50001 caracteres rechazados con error en `description` (task y project) y `goal` (sprint), 50000 aceptados, campo ausente aceptado, y guardado de contenido JSON en los tres endpoints persiste
- [x] T003 [P] Crear `resources/js/utils/blocks.ts` con la API del contrato §2: tipos `EditorBlock`/`EditorBlocks`, `isBlocksJson`, `plainTextToBlocks`, `normalizeToBlocks` (FR-005: null → bloques vacíos; no-JSON → párrafo), `blocksToPlainText` y `blocksToHtml` con **escape total del texto y whitelist inline** (`b, i, strong, em, code, mark, br` y `a` solo con `href` en `http:`, `https:`, `mailto:` o `#`; bloques desconocidos omitidos)
- [x] T004 Crear migración con `php artisan make:migration convert_long_text_fields_to_block_content --no-interaction` (`database/migrations/2026_09_30_000000_convert_long_text_fields_to_block_content.php`): normalización eager de data-model.md §"Migración de datos" sobre las tres columnas (json_decode con validación de `blocks`; envolver en párrafo solo lo que no cumpla; escribir solo cambios), `down()` documentado como sin reversa de contenido, y correr `php artisan migrate`
- [x] T005 [P] Editar reglas en los 5 form requests — `description` en `app/Http/Requests/SaveTaskRequest.php`, `app/Http/Requests/StoreProjectRequest.php` y `app/Http/Requests/UpdateProjectRequest.php`, y `goal` en `app/Http/Requests/StoreSprintRequest.php` y `app/Http/Requests/UpdateSprintRequest.php` — a `['nullable', 'string', 'max:50000']` (sprint `goal` reemplaza su `max:1000`) con el mensaje `'El contenido no puede superar los 50000 caracteres.'` en `messages()` de cada uno
- [x] T006 Actualizar `tests/Feature/Sprints/SprintGoalTest.php` (regresión del tope anterior): el caso `goal` gigante pasa de `str_repeat('a', 1001)` con error esperado a 50001 caracteres con error esperado, y añadir aserción de que 1001 caracteres (viejo límite) ahora se aceptan; correr `php artisan test --compact tests/Feature/RichText tests/Feature/Sprints` en verde

**Checkpoint**: `DescriptionMigrationTest` y `RichTextValidationTest` en verde; contenido previo migrado; `blocks.ts` disponible

---

## Phase 3: User Story 1 - Descripción enriquecida de tareas (Priority: P1) 🎯 MVP

**Goal**: El diálogo de tarea usa el editor de bloques; el contenido se guarda y reabre igual; el texto plano previo se muestra como párrafo (spec US1)

**Independent Test**: Crear tarea con descripción de bloques (encabezado+lista+enlace) y reabrirla intacta; tarea previa de texto plano editable como párrafo (quickstart §2)

### Implementation for User Story 1

- [ ] T007 [P] [US1] Crear `resources/js/Components/RichText/RichTextEditor.vue` según contrato §3: props `modelValue: string|null`, `placeholder?`, `error?`, `id?`; monta EditorJS en `onMounted` con `normalizeToBlocks(modelValue)`; herramientas paragraph/header(H2–H4)/list/quote/code + inlineCode/marker y bold/italic/link del núcleo; `sanitize: true` al pegar; `defineExpose({ save(): Promise<string>, isEmpty(): boolean, focus(): void })` donde `save()` devuelve `''` cuando está vacío; borde de error + mensaje inline cuando `error`; tema claro con tokens de `DESIGN.md`
- [ ] T008 [P] [US1] Crear `resources/js/Components/RichText/RichTextContent.vue` según contrato §4: props `content: string|null`; renderiza `blocksToHtml(normalizeToBlocks(content))` con `v-html` solo sobre el HTML saneado, contenedor tipografiado (prosa `text-body-md`); `null`/sin bloques → no renderiza nada
- [ ] T009 [US1] Integrar el editor en `resources/js/Components/Tasks/Board/TaskFormModal.vue`: sustituir el `<textarea>` de descripción por `<RichTextEditor ref="descriptionEditor" v-model="form.description" :error="form.errors.description" />` conservando label y estructura de campo; en `submit()` hacer `form.description = await descriptionEditor.save()` antes de post/put (y resetear `createAnother` correctamente por remontaje); **eliminar** la nota "La barra de herramientas de marcado con vista previa y más tipos de tarea… llegará próximamente" (FR-006); doble envío bloqueado como hoy (`form.processing`)

**Checkpoint**: US1 funcional de forma independiente — quickstart §2 validado a mano

---

## Phase 4: User Story 2 - Descripción enriquecida de proyectos (Priority: P2)

**Goal**: Diálogo del proyecto con editor y ficha que renderiza la descripción formateada (spec US2)

**Independent Test**: Editar descripción con lista+enlace y verla formateada en la ficha; proyecto previo de texto plano legible (quickstart §3)

### Implementation for User Story 2

- [ ] T010 [US2] Integrar el editor en `resources/js/Components/Projects/ProjectFormModal.vue`: sustituir el textarea de descripción por `<RichTextEditor>` (mismo patrón que T009: ref, `save()` asíncrono en submit, label y errores inline conservados)
- [ ] T011 [US2] Renderizar la descripción en `resources/js/pages/Projects/Show.vue`: en la sección de descripción del proyecto, mostrar `<RichTextContent :content="project.description" />` en lugar del texto plano (ocultar la sección cuando sea `null`)

**Checkpoint**: US1 + US2 — tareas y proyectos con contenido enriquecido

---

## Phase 5: User Story 3 - Objetivo enriquecido de sprints (Priority: P3)

**Goal**: Diálogo de sprint con editor y detalle que renderiza el objetivo formateado (spec US3)

**Independent Test**: Editar objetivo con negritas+lista y verlo formateado en el detalle; sprint previo de texto plano legible (quickstart §3)

### Implementation for User Story 3

- [ ] T012 [US3] Integrar el editor en `resources/js/Components/Sprints/SprintFormModal.vue`: sustituir el textarea del objetivo por `<RichTextEditor>` (mismo patrón: ref, `save()` asíncrono, label "Objetivo del sprint (opcional)" y errores inline conservados)
- [ ] T013 [US3] Renderizar el objetivo en `resources/js/pages/Sprints/Show.vue`: el bloque de objetivo bajo el encabezado usa `<RichTextContent :content="sprint.goal" />` (ya oculto cuando `null`)

**Checkpoint**: Las tres historias operan de forma independiente

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Calidad transversal y cierre

- [ ] T014 [P] Pase de estilos del editor a tokens de `DESIGN.md` (fondo surface, bordes hairline, tipografía Inter, anillos de foco), responsive 375px sin overflow de página, y recorrido de teclado del diálogo intacto (Tab dentro del editor, ESC y click-fuera cierran, foco inicial en el título)
- [ ] T015 [P] Validar `quickstart.md` (§0–§8) contra la implementación real incluidas las pruebas anti-XSS de §4 (pegado de `"><img src=x onerror=alert(1)>` y enlace `javascript:` sin ejecución); revisar `browser-logs` y consola sin errores
- [ ] T016 Quality gates y cierre: `vendor/bin/pint --dirty --format agent`, `npm run check:fix`, `npm run types:check`, `npm run build` sin errores; `php artisan test --compact` completo en verde (regresión de Tasks/Projects/Sprints/Auth/Shell); commit final de la feature

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias
- **Foundational (Phase 2)**: bloquea todas las historias (T002 tests → T003–T005 implementación → T006 regresión → verde)
- **User Stories (Phase 3–5)**: dependen de Foundacional; en orden P1 → P2 → P3 (T007/T008 son la base reutilizada por US2/US3)
- **Polish (Phase 6)**: depende de US1–US3 completas

### User Story Dependencies

- **US1 (P1)**: tras Foundacional; construye `RichTextEditor`/`RichTextContent` — MVP
- **US2 (P2)**: tras US1 (reutiliza los componentes; integración puntual en ProjectFormModal + Projects/Show)
- **US3 (P3)**: tras US1 (misma base; integración puntual en SprintFormModal + Sprints/Show)

### Within Each User Story

- Componentes [P] entre sí (T007∥T008); integración del formulario después (mismo archivo que referencia el componente)
- Lectura (T011/T013) puede ir en paralelo con la integración de su formulario (archivos distintos)

### Parallel Opportunities

- T003 ∥ T002 (utilidad cliente vs tests servidor)
- T004 ∥ T005 (migración vs reglas de validación, tras T002)
- T007 ∥ T008 (editor vs render de lectura)
- T010 ∥ T011 y T012 ∥ T013 (formulario vs página, mismas historias)
- T014 ∥ T015 (polish)

---

## Parallel Example: User Story 1

```bash
# Utilidad + componentes en paralelo (tras Phase 2):
Task: "Crear resources/js/utils/blocks.ts"
Task: "Crear resources/js/Components/RichText/RichTextEditor.vue"
Task: "Crear resources/js/Components/RichText/RichTextContent.vue"

# Luego, integración secuencial:
Task: "Integrar editor en resources/js/Components/Tasks/Board/TaskFormModal.vue"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1 + Phase 2 (T001–T006) — base y dominio
2. Phase 3 (T007–T009) — editor en tareas
3. **STOP y VALIDA**: quickstart §2; demo si se desea

### Incremental Delivery

1. Setup + Foundacional → base verde
2. US1 → tareas → validar (MVP)
3. US2 → proyectos → validar (§3)
4. US3 → sprints → validar (§3)
5. Polish → gates finales

### Parallel Team Strategy

- Desarrollador A: T002 + T004 + T005 + T006 (tests, migración, validación)
- Desarrollador B: T003 + T007 + T008 (utilidades y componentes)
- Desarrollador C: T009–T013 (integraciones por historia) en cuanto T007/T008 existen

---

## Notes

- [P] tasks = archivos distintos, sin dependencias
- Restricciones citadas verbatim de data-model.md/contrato: `max:50000`, whitelist inline (`b,i,strong,em,code,mark,br` y `a[href]` en http/https/mailto/#), migración idempotente, `save()` devuelve `''` cuando está vacío
- "Próximamente" sigue siendo un estado deshabilitado real; con esta feature se retira la nota del diálogo de tarea (FR-006) pero NO se habilita nada de imágenes/adjuntos
- La API permanece permisiva con texto plano (FR-005): los tests existentes que envían strings siguen verdes
- Verificar tests en rojo antes de cada implementación y en verde después
- Listas y tarjetas nunca renderizan la descripción (solo títulos/metadatos)
