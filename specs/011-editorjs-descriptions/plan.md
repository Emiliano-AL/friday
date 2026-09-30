# Implementation Plan: Editor de contenido enriquecido en descripciones (011)

**Branch**: `011-editorjs-descriptions` | **Date**: 2026-09-30 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/011-editorjs-descriptions/spec.md`

## Summary

Sustituir el área de texto plano de los campos de texto largo (descripción de
tarea, descripción de proyecto y objetivo de sprint) por un editor de
contenido por bloques (EditorJS: núcleo + herramientas oficiales de texto),
guardando la estructura de bloques (JSON) en las columnas existentes.
Componentes compartidos: `RichTextEditor` (envoltorio del editor para los
tres diálogos) y `RichTextContent` (renderizado seguro en lectura), con
utilidades de normalización (texto plano ↔ bloques) y saneamiento anti-XSS.
Migración única que convierte el contenido previo en texto plano a un bloque
de párrafo. Validación ampliada con tamaño máximo; el objetivo de sprint deja
de tener tope de 1000 caracteres para admitir el JSON de bloques.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13) + Vue 3 (`<script setup>`) con Inertia v3 y TypeScript estricto.

**Primary Dependencies**: EditorJS (núcleo + herramientas oficiales de texto, ver research D1) — **nueva dependencia npm aprobada explícitamente por el usuario (spec FR-007)**; sin cambios en Composer ni en ninguna otra dependencia.

**Storage**: PostgreSQL; columnas existentes `tasks.description`, `projects.description`, `sprints.goal` (todas `text nullable`); el contenido pasa a ser JSON de bloques (misma columna).

**Testing**: Pest — migración de datos, validación de límites y passthrough de payloads; gates `npm run check:fix`, `npm run types:check`, `npm run build`, Pint.

**Target Platform**: Web (Herd/artisan en desarrollo); renderizado 100% cliente (Inertia), sin SSR.

**Project Type**: Web application (monolito Inertia).

**Performance Goals**: Render de lectura < 1 s para contenidos de ~10 000 palabras (SC-003); el editor inicializa < 500 ms en los diálogos.

**Constraints**: Seguridad anti-XSS en el renderizado (SC-004); el editor se integra sin alterar el ciclo de los diálogos (ESC, click-fuera, foco, doble envío, "Crear otra"); solo modo claro.

**Scale/Scope**: 2 componentes compartidos + 1 utilidad TS, 3 diálogos modificados, 2 páginas con render de lectura, 1 migración de datos, 5 archivos de validación, ~3 archivos de test.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principle | Verdict | Notes |
| --- | --- | --- |
| I. Monolith-First con Inertia | ✅ PASS | Editor 100% cliente dentro de páginas Inertia; sin API desacoplada. |
| II. Alineación Ecosistema | ✅ PASS (justificado) | Se añade **una** dependencia npm (EditorJS + herramientas oficiales) con **aprobación explícita del usuario** registrada en spec FR-007. Cero cambios de Composer. Cumple la regla "sin aprobación no se agregan". |
| III. Test-First con Pest | ✅ PASS | Tests de migración, validación y passthrough planeados antes de la implementación. |
| IV. Tipado Estricto y Enums | ✅ PASS | Tipos TS para bloques/props del editor; PHP sin cambios de dominio (mismos campos). |
| V. Simplicidad y YAGNI | ✅ PASS | Herramientas de texto solamente (sin imágenes/adjuntos, que quedan "Próximamente"); sin columnas nuevas; renderizado cliente sin dependencias PHP extra. |

_Post-design re-check (Phase 1): sin desviaciones introducidas._

## Project Structure

### Documentation (this feature)

```text
specs/011-editorjs-descriptions/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
└── contracts/           # Phase 1 output
```

### Source Code (repository root)

```text
resources/js/
├── Components/RichText/
│   ├── RichTextEditor.vue     # NUEVO: envoltorio del editor (montaje, datos, save async)
│   └── RichTextContent.vue    # NUEVO: render de lectura seguro (blocks → HTML saneado)
├── utils/
│   └── blocks.ts              # NUEVO: tipos de bloque, isBlocksJson, normalizeToBlocks, blocksToHtml, plainTextToBlocks
├── Components/Tasks/Board/TaskFormModal.vue      # EDIT: textarea → RichTextEditor; retira nota "Próximamente" de la barra de marcado
├── Components/Projects/ProjectFormModal.vue      # EDIT: textarea → RichTextEditor
├── Components/Sprints/SprintFormModal.vue        # EDIT: textarea → RichTextEditor
├── pages/Projects/Show.vue                       # EDIT: render de la descripción con RichTextContent
└── pages/Sprints/Show.vue                        # EDIT: render del objetivo con RichTextContent

database/migrations/
└── 2026_09_30_000000_convert_long_text_fields_to_block_content.php  # NUEVO: texto plano → bloques

app/Http/Requests/
├── SaveTaskRequest.php        # EDIT: description max:50000
├── StoreProjectRequest.php    # EDIT: description max:50000
├── UpdateProjectRequest.php   # EDIT: description max:50000
├── StoreSprintRequest.php     # EDIT: goal max:50000 (hoy max:1000)
└── UpdateSprintRequest.php    # EDIT: goal max:50000

tests/Feature/RichText/
├── DescriptionMigrationTest.php   # NUEVO
└── RichTextValidationTest.php     # NUEVO
```

**Structure Decision**: Monolito único. Los componentes viven en `Components/RichText/` (convención por dominio, como `Components/Sprints/`) y las utilidades en `utils/blocks.ts` (convención existente `@/utils` si aplica, o junto a RichText — se confirma en tasks). Toda la lógica de renderizado es cliente; PHP solo valida tamaño y persiste el string.

## Complexity Tracking

> Sin violaciones constitucionales. La dependencia nueva se ampara en la aprobación explícita del usuario (spec FR-007); no se registran desviaciones adicionales.
