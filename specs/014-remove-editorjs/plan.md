# Implementation Plan: Eliminación del editor de bloques EditorJS

**Branch**: `014-remove-editorjs` | **Date**: 2026-10-02 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/014-remove-editorjs/spec.md`

## Summary

Eliminar por completo el ecosistema EditorJS (7 paquetes npm, componente de edición, tipos y utilidades de bloques) y sustituirlo por **Tiptap v3** (aprobación explícita del usuario en FR-005) en los formularios de proyecto (descripción), sprint (goal) y tarea (descripción). El contenido heredado en formato JSON de bloques se migra a texto plano con una migración idempotente (sin datos en producción, FR-004); el contenido nuevo se persiste como JSON del documento de Tiptap (`getJSON()`). La vista de solo lectura se reimplementa con una instancia Tiptap `editable: false` (recomendación oficial de las docs), lo que elimina la necesidad de sanitización HTML adicional (sin DOMPurify): el schema descarta lo que no cumple el modelo y el mark Link se configura con `isAllowedUri` para restringir protocolos. Los tres formularios consumidores y las dos vistas de solo lectura **no cambian su contrato externo**: props (`modelValue`, `placeholder`, `error`, `id`, `minHeight`, `maxHeightClass`) y método expuesto `save(): Promise<string>` se conservan.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13) + TypeScript 5.2 / Vue 3.5 (`<script setup>`) en el frontend.

**Primary Dependencies**: Tiptap v3 — `@tiptap/vue-3`, `@tiptap/pm`, `@tiptap/starter-kit`, `@tiptap/extensions` (Placeholder vive aquí en v3). Se eliminan los 7 paquetes `@editorjs/*`. StarterKit v3 incluye Bold, Italic, Underline, Strike, Code, Link, Heading, lists, Blockquote, CodeBlock, UndoRedo. **Sin DOMPurify ni ninguna dependencia de sanitización adicional** (ver research D2/D6).

**Storage**: PostgreSQL. Columnas existentes `projects.description`, `tasks.description`, `sprints.goal` (`text`, nullable) — **sin cambios de schema**. Contenido nuevo: string JSON del doc de Tiptap. Contenido heredado: texto plano tras migración.

**Testing**: Pest (feature tests PHP) para la migración y la validación; `vue-tsc`/`vite build` para tipado y compilación del frontend; validación manual guiada por `quickstart.md` (el proyecto no tiene test runner de frontend).

**Target Platform**: Aplicación web monolítica (Laravel + Inertia v3), servida por Herd local / Laravel Cloud.

**Project Type**: Web application (single monolith, no API).

**Performance Goals**: El editor debe montarse en < 200 ms al abrir un diálogo; el render de solo lectura no debe bloquear la vista de detalle (montaje diferido no requerido a este tamaño).

**Constraints**: Validaciones de servidor existentes sin cambios de comportamiento (FR-007); bundle de frontend sin código de EditorJS y sin crecer (SC-004); render sanitizado siempre (FR-009).

**Scale/Scope**: MVP — 3 formularios, 2 vistas de solo lectura, 3 columnas de datos, ≤ 50 000 caracteres por campo.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Evaluación | Resultado |
| --- | --- | --- |
| I. Monolith-First con Inertia | Sin API nueva; el cambio es interno (componentes Vue + migración de datos). Inertia props fluyen igual. | ✅ PASS |
| II. Alineación con el ecosistema / dependencias | Se eliminan 7 paquetes `@editorjs/*`; se añaden 4 paquetes Tiptap v3 (`@tiptap/vue-3`, `@tiptap/pm`, `@tiptap/starter-kit`, `@tiptap/extensions`) con **aprobación explícita del usuario registrada en FR-005** del spec. Ninguna otra dependencia nueva (sin DOMPurify). | ✅ PASS (justificado) |
| III. Test-First con Pest | La migración de datos (lógica de dominio) se cubre con tests de feature Pest nuevos; los tests existentes de validación se actualizan al nuevo formato. Frontend: build + typecheck + escenarios manuales en quickstart. | ✅ PASS |
| IV. Tipado estricto y enums | Migración PHP con tipado explícito (mismo estilo que la migración 2026_09_30); componentes Vue en TS con `lang="ts"`. | ✅ PASS |
| V. Simplicidad y YAGNI | Solo lo necesario: StarterKit + Placeholder. Sin Tiptap Cloud/Platform (cuenta, key, colaboración — fuera de alcance), sin `@tiptap/html`, sin sanitizador extra. Read-only con instancia `editable: false` en vez de pipeline HTML. | ✅ PASS |

**Re-evaluación post-diseño (Phase 1)**: el diseño confirma las mismas conclusiones — sin violaciones nuevas. Complexity Tracking: no aplica (sin violaciones).

## Project Structure

### Documentation (this feature)

```text
specs/014-remove-editorjs/
├── plan.md              # This file (/skill:speckit-plan command output)
├── research.md          # Phase 0 output (/skill:speckit-plan command)
├── data-model.md        # Phase 1 output (/skill:speckit-plan command)
├── quickstart.md        # Phase 1 output (/skill:speckit-plan command)
├── contracts/           # Phase 1 output (/skill:speckit-plan command)
│   └── tiptap-rich-text.md
└── tasks.md             # Phase 2 output (/skill:speckit-tasks command - NOT created by /skill:speckit-plan)
```

### Source Code (repository root)

```text
# Cambios sobre la estructura existente (sin carpetas nuevas en el root)
resources/js/
├── Components/
│   └── RichText/
│       ├── RichTextEditor.vue      # REWRITE: Tiptap v3 (mismo contrato externo)
│       └── RichTextContent.vue     # REWRITE: instancia Tiptap editable:false
├── utils/
│   └── blocks.ts                   # DELETE (sustituido por utils/tiptapContent.ts)
│   └── tiptapContent.ts            # NEW: normalizeToTiptapJson / isTiptapJson / docFromPlainText
├── types/
│   └── editorjs-marker.d.ts        # DELETE
└── (formularios Projects/Sprints/Tasks sin cambios de contrato)

database/migrations/
└── 2026_10_02_000000_convert_block_content_to_plain_text.php  # NEW

app/Http/Requests/                  # SIN cambios (validación intacta, FR-007)

tests/Feature/RichText/
├── DescriptionMigrationTest.php    # SIN cambios (migración histórica intacta)
├── BlockContentToPlainTextMigrationTest.php  # NEW
└── RichTextValidationTest.php      # UPDATE: aserciones al formato Tiptap

package.json                        # -7 @editorjs/*, +4 @tiptap/*
```

**Structure Decision**: estructura de aplicación web única (monolito Inertia). Se conservan las rutas existentes (`Components/RichText/`, `utils/`, `types/`, `tests/Feature/RichText/`); lo único nuevo es una migración y sus tests. No se crean carpetas base nuevas.

## Complexity Tracking

> Sin violaciones de la constitución — sección vacía intencionadamente.
