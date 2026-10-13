# Implementation Plan: Vista previa legible e identificable del proyecto

**Branch**: `021-fix-project-card-preview` | **Date**: 2026-10-10 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/021-fix-project-card-preview/spec.md`

## Summary

Corrección de presentación en la tarjeta del directorio de proyectos
(`/projects`, `resources/js/components/Projects/ProjectCard.vue`). Dos fallas:
(1) la descripción se interpola cruda (`{{ project.description }}`) y, como el
formulario re-guarda la descripción en formato de texto enriquecido (Tiptap
JSON), la tarjeta imprime estructuras JSON; (2) el azulejo tiene icono y clases
fijas, ignorando el `color`/`icon` que ya viajan en `DirectoryProject`. La
corrección añade un extractor de texto plano a `resources/js/utils/tiptapContent.ts`
(con passthrough para texto legado) y pinta el distintivo en el azulejo con
fallbacks idénticos a los de ficha/navegación. Sin cambios backend ni de datos.

## Technical Context

**Language/Version**: Vue 3 (`<script setup>`) + TypeScript; sin cambios PHP.

**Primary Dependencies**: `resources/js/utils/tiptapContent.ts` (utilidad
existente con `isTiptapJson`/`normalizeToTiptap` — se le añade el extractor);
`ProjectCard.vue`; tipos existentes `DirectoryProject` (ya trae `description`,
`color`, `icon`).

**Storage**: sin cambios.

**Testing**: sin runner de tests unitarios frontend en el proyecto (no vitest);
verificación vía `npm run build` + `npm run types:check` + escenarios de
navegador del quickstart. La suite Pest existente no se toca (SC-003).

**Target Platform**: web monolítica Inertia.

**Project Type**: web-app monolito (Constitución I).

**Performance Goals**: extractor recursivo sobre JSON ya parseado en cliente;
volumen de descripciones ≤ 50 000 chars — coste despreciable.

**Constraints**: Constitución V (corrección mínima: presentación, no
almacenamiento; no tocar la ficha ni el formulario, que ya funcionan).

**Scale/Scope**: 1 utilidad ampliada + 1 componente; 2 computeds nuevos y 1
helper; 0 cambios backend; 0 migraciones.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Evaluación |
| --- | --- |
| I. Monolith-First con Inertia | PASS — Solo componentes Vue; sin API nueva. |
| II. Alineación Ecosistema Laravel | PASS — Sin dependencias; utilidad TS del propio proyecto. |
| III. Test-First con Pest | PASS (acotado) — No hay lógica de dominio server-side nueva; la verificación es build/types + escenarios de navegador documentados en quickstart.md. |
| IV. Tipado Estricto y Enums | PASS — El extractor tipa su entrada/salida; los valores de color/icon son literales ya tipados en `DirectoryProject`. |
| V. Simplicidad y YAGNI | PASS — Corrección mínima en el único punto roto (`ProjectCard.vue`); passthrough de texto plano sin migración de datos. |

Sin violaciones. Complexity Tracking: no aplica.

**Post-design re-check (tras Phase 1)**: el diseño toca un solo componente y una
utilidad compartida; la ficha completa y el formulario quedan intactos. PASS.

## Project Structure

### Documentation (this feature)

```text
specs/021-fix-project-card-preview/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output (sin cambios de datos; contrato del extractor)
├── contracts/           # Phase 1 output
└── quickstart.md        # Phase 1 output
```

### Source Code (repository root)

```text
resources/js/
├── utils/
│   └── tiptapContent.ts              # EXISTENTE: + tiptapToPlainText() extractor
└── components/Projects/
    └── ProjectCard.vue               # EXISTENTE: descriptionExcerpt + azulejo con icon/color
```

**Structure Decision**: la corrección vive en los dos únicos puntos rotos; no se
crean archivos nuevos.

## Complexity Tracking

> Sin violaciones constitucionales. Alternativa rechazada (cambiar el
> almacenamiento a texto plano) en [research.md](research.md).
