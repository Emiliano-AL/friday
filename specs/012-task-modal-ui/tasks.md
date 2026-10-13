---
description: 'Task list template for feature implementation'
---

# Tasks: Modal de Tarea Mejorado (Secciones Colapsables y Acciones Alineadas)

**Input**: Design documents from `/specs/012-task-modal-ui/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: No se generan tareas de tests nuevos — cambio de UI puro sin lógica de dominio (research D5 y reglas Pest del proyecto). La regresión la cubren `npm run types:check`, `npm run build` y las suítes Pest existentes (fase de pulido).

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- Proyecto único tipo web app monolito: frontend en `resources/js/`; tests backend en `tests/`
- Contrato de UI: [contracts/012-task-modal-ui.md](contracts/012-task-modal-ui.md)
- Guía de validación: [quickstart.md](quickstart.md)

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Baseline verificada antes de tocar código y confirmación de cero dependencias nuevas

- [X] T001 Verificar baseline del frontend: `npm run types:check` y `npm run build` pasan sin errores antes de cualquier cambio, y registrar el resultado en [quickstart.md](quickstart.md) §1 (sin archivos de código a modificar; si algo falla, detenerse y resolver antes de continuar)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Reestructura del layout del diálogo que MUST existir antes de que cualquier historia pueda cumplir sus criterios de visibilidad (SC-001, SC-002, G1, G10)

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T002 Reestructurar el interior de `resources/js/Components/Tasks/Board/TaskFormModal.vue` en una columna con altura máxima (`max-h-[85vh]`): encabezado fijo arriba, cuerpo del formulario envuelto en un contenedor `flex-1 overflow-y-auto min-h-0`, y zona de pie separada (`shrink-0`, `border-t`) — sin cambios visuales ni de comportamiento todavía, solo la estructura scroll/columna (decisión D3 en research.md)

**Checkpoint**: Foundation ready - el diálogo tiene cuerpo con scroll propio y pie anclado; las historias pueden avanzar

---

## Phase 3: User Story 1 - Secciones secundarias colapsables (Priority: P1) 🎯 MVP

**Goal**: "Vinculación y alcance" y "Estimación y fechas" funcionan como acordeones accesibles: colapsados por defecto, con conservación de valores, reinicio por apertura y auto-expand por errores (spec FR-001–FR-006)

**Independent Test**: Abrir el diálogo de crear tarea: ambas secciones aparecen colapsadas y los campos principales + acciones se ven sin scroll; expandir "Vinculación y alcance", elegir proyecto y sprint, colapsar y reexpandir sin perder valores; en modo edición con proyecto asignado la sección aparece expandida; un error de proyecto/sprint expande su sección. Equivale a quickstart.md §2, §3 y §5.

### Implementation for User Story 1

- [X] T003 [P] [US1] Crear el componente `resources/js/Components/CollapsibleSection.vue` según el contrato §1: props `title`, `icon`, `toggleHint` y `v-model:open` (componente controlado, `defineModel<boolean>`), slot `default` para el contenido y slot `badge` para insignias; encabezado como `<button>` único con `aria-expanded` + `aria-controls`; animación con `<Transition>` de Vue y técnica `grid-template-rows: 0fr → 1fr` (sin keyframes CSS); icono `expand_more` con rotación de 180° y hint "Mostrar"/"Ocultar"; tokens existentes (`bg-surface-container-low/80`, `border-outline-variant/30`, `rounded-xl`, `p-space-md`, `text-label-xs uppercase tracking-wider`)
- [X] T004 [US1] Integrar el acordeón "Vinculación y alcance" en `resources/js/Components/Tasks/Board/TaskFormModal.vue` (depende de T002 y T003): envolver la sección existente con `CollapsibleSection`, badge "Proyecto opcional" vía slot, `v-model:open` ligado a la ref `linkageOpen`, inicialización expandida cuando la tarea tiene proyecto o sprint (`form.project_id !== null || form.sprint_id !== null`, incluido `initialProject`/`initialSprint`) y reinicio de ambas refs de apertura en el `watch(() => props.open)` existente (FR-003, FR-005)
- [X] T005 [US1] Integrar el acordeón "Estimación y fechas" en `resources/js/Components/Tasks/Board/TaskFormModal.vue` (depende de T004, mismo archivo): `CollapsibleSection` con badge "Próximamente", siempre colapsado por defecto, conservando los campos deshabilitados sin datos simulados (FR-002, FR-011); verificar que colapsar/reexpandir conserva el estado de los demás campos (FR-004)
- [X] T006 [US1] Añadir en `resources/js/Components/Tasks/Board/TaskFormModal.vue` un watcher sobre `form.errors` que fije `linkageOpen = true` cuando aparezca un error en `project_id` o `sprint_id` (FR-006/G5), y comprobar que los mensajes de error de esos campos siguen mostrándose en línea junto a los selectores

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently (quickstart §2 checklist de apertura y acordeones)

---

## Phase 4: User Story 2 - Área de descripción con altura cómoda (Priority: P2)

**Goal**: El área de descripción muestra ~10 líneas (mín. ~240 px) con scroll interno y barra de formato conservada, sin empujar las acciones fuera del diálogo (spec FR-007, FR-008, SC-003)

**Independent Test**: Pegar ~20 líneas en la descripción: el área crece hasta su tope (~420 px), el excedente se recorre con scroll interno, el pie permanece visible y la barra de formato sigue operativa. Equivale a quickstart.md §2 (caso de altura de descripción).

### Implementation for User Story 2

- [X] T007 [P] [US2] Ampliar `resources/js/Components/RichText/RichTextEditor.vue` según el contrato §2 con las props opcionales `minHeight?: number` (default `120`, se inyecta en la config `minHeight` de EditorJS) y `maxHeightClass?: string` (default `''`); aplicar con `:deep()` sobre `.codex-editor` la clase de altura máxima junto a `overflow-y: auto` solo cuando la prop se informe; garantizar que los consumidores existentes (`TaskModal.vue`, `ProjectFormModal.vue`, `SprintFormModal.vue`) conservan el comportamiento actual sin pasar las props nuevas
- [X] T008 [US2] En `resources/js/Components/Tasks/Board/TaskFormModal.vue` (depende de T002 y T007), pasar al `RichTextEditor` de descripción `min-height` 240 y una clase de altura máxima (p. ej. `max-h-[420px]`), y verificar con contenido largo que el área muestra ~10 líneas, recorre con scroll interno y el pie no se desplaza (G6); si el toolbar flotante de EditorJS se posiciona mal con el scroll del holder, mover el max-height al contenedor raíz `.rich-text-editor` como fallback

**Checkpoint**: User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - Barra de acciones unificada y alineada (Priority: P3)

**Goal**: Pie único en una fila: "Eliminar tarea" (edición) o "Crear otra" (creación) a la izquierda; "Cancelar" + acción primaria a la derecha; desaparece la caja independiente de eliminación (spec FR-009, G7, G8)

**Independent Test**: Modo edición: "Eliminar tarea", "Cancelar" y "Guardar cambios" en la misma fila del pie, eliminar pide confirmación y cierra al confirmar; modo creación: casilla "Crear otra" a la izquierda, sin botón eliminar. Equivale a quickstart.md §3.

### Implementation for User Story 3

- [X] T009 [US3] Reestructurar el pie de `resources/js/Components/Tasks/Board/TaskFormModal.vue` (depende de T002): trasladar el bloque "Eliminar tarea" desde la caja independiente `border-error-container` (líneas ~542–565 actuales) al lado izquierdo del footer con estilo de botón de texto destructivo (tokens `error`), eliminando dicha caja; conservar la casilla "Crear otra tarea al guardar" a la izquierda solo en modo creación y "Cancelar"/acción primaria a la derecha en ambos modos, con el estado de procesamiento existente (G7)
- [X] T010 [US3] Verificar en `resources/js/Components/Tasks/Board/TaskFormModal.vue` los comportamientos del pie tras el movimiento (depende de T009): confirmación previa de eliminación con cierre del diálogo, atajo Ctrl/Cmd+Enter para guardar intacto, foco inicial en el título al abrir, y orden lógico apilado del pie en viewports estrechos (G8 y Edge Cases del spec)

**Checkpoint**: All user stories should now be independently functional

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Calidad y regresión de conjunto tras integrar las tres historias en el mismo componente

- [X] T011 [P] Ejecutar `npm run types:check` y `npm run build` en verde; corregir cualquier error de tipos o de build introducido por los cambios
- [X] T012 [P] Ejecutar `php artisan test --compact tests/Feature/Tasks tests/Feature/RichText` y confirmar que toda la regresión del dominio de tareas y del editor sigue en verde (sin tests nuevos — research D5)
- [X] T013 Recorrer la validación manual completa de [quickstart.md](quickstart.md) §2–§5 (apertura compacta, acordeones, altura de descripción, pie unificado en ambos modos, accesibilidad por teclado/`aria-expanded`, y edge cases: contexto preseleccionado, cambio de proyecto con sección colapsada, viewport estrecho, reinicio de estado) y resolver los ajustes visuales o de comportamiento que aparezcan

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - T001 valida el punto de partida
- **Foundational (Phase 2)**: T002 depende del repo sano (T001) y BLOQUEA todas las historias (las tres historias viven en el mismo archivo `TaskFormModal.vue` y comparten la estructura scroll/pie)
- **User Stories (Phase 3+)**: Dependen de T002; entre sí son secuenciales por compartir `TaskFormModal.vue` (no paralelizables entre historias; ver Parallel Opportunities para el paralelismo real por archivos)
- **Polish (Final Phase)**: Depende de T003–T010 completos

### User Story Dependencies

- **User Story 1 (P1)**: T003 → T004 → T005 → T006 (MVP)
- **User Story 2 (P2)**: T007 puede adelantarse en paralelo a US1; T008 requiere T002 + T007
- **User Story 3 (P3)**: T009 → T010, requiere T002 (y se integra mejor con T004–T006 ya hechas por compartir el pie con la casilla "Crear otra")

### Within Each User Story

- El componente nuevo (T003) antes que su integración (T004/T005)
- Integraciones en `TaskFormModal.vue` siempre secuenciales (mismo archivo)
- Verificación manual de la historia antes de pasar a la siguiente prioridad

### Parallel Opportunities

- T003 [P] (nuevo `CollapsibleSection.vue`) y T007 [P] (`RichTextEditor.vue`) tocan archivos distintos y pueden ejecutarse en paralelo (juntos o intercalados con la secuencia de US1)
- T011 [P] y T012 [P] (types/build/tests) pueden lanzarse juntos en Polish
- No marcar como paralelas tareas que modifiquen `TaskFormModal.vue` simultáneamente (T002, T004, T005, T006, T008, T009, T010)

---

## Parallel Example: User Story 1 + adelanto de User Story 2

```text
# Tras completar T002 (foundational), lanzar en paralelo:
Task T003 [P] [US1]: "Crear resources/js/Components/CollapsibleSection.vue"
Task T007 [P] [US2]: "Ampliar resources/js/Components/RichText/RichTextEditor.vue"

# Luego, secuencialmente sobre TaskFormModal.vue:
T004 → T005 → T006 (US1) → T008 (US2) → T009 → T010 (US3)
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. T001 (baseline) y T002 (estructura scroll/pie)
2. T003 + T004 + T005 + T006 (US1: acordeones)
3. **STOP and VALIDATE**: quickstart.md §2 y §5 → el diálogo compacto con secciones colapsables ya es una mejora entregable por sí sola

### Incremental Delivery

1. Setup + Foundational → base estable
2. US1 → validar → entregable (MVP)
3. US2 (T007 + T008) → validar altura de descripción → entregable
4. US3 (T009 + T010) → validar pie unificado → entregable
5. Polish (T011–T013) → quality gates y checklist manual completa

### Parallel Team Strategy

Con dos desarrolladores: uno ejecuta T003 + la cadena de `TaskFormModal.vue` (T004–T006, T008–T010) y otro adelanta T007 en `RichTextEditor.vue`; convergen en T008 y Polish. En la práctica, por ser un componente único, el modo más seguro es la secuencia incremental.

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable (verificación manual por quickstart.md)
- Cero dependencias nuevas y cero cambios backend: cualquier tarea que "necesite" una librería o un endpoint nuevo está mal planteada — volver a research.md (D1–D7)
- Commit after each task or logical group (rama `012-task-modal-ui`)
- Stop at any checkpoint to validate story independently
- Avoid: vague tasks, same file conflicts, cross-story dependencies that break independence
