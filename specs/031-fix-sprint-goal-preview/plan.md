# Implementation Plan: Corregir la vista previa del objetivo del sprint

**Branch**: `031-fix-sprint-goal-preview` | **Date**: 2026-10-17 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/031-fix-sprint-goal-preview/spec.md`

## Summary

Tres vistas compactas pintan `sprint.goal` tal cual. Desde que el objetivo usa el editor enriquecido (feature 011), ese valor es JSON de Tiptap. La corrección pasa el objetivo por el helper existente `tiptapToPlainText` (`resources/js/utils/tiptapContent.ts`), que ya usa `ProjectCard.vue` para la descripción del proyecto. Ese helper aplana el JSON, deja pasar el texto plano antiguo y devuelve `''` cuando no hay texto visible. No cambian el backend, los datos ni la edición.

## Technical Context

**Language/Version**: TypeScript (Vue 3.5, `<script setup>`)

**Primary Dependencies**: ninguna nueva; se reutiliza `tiptapToPlainText`

**Storage**: N/A (sin cambios de datos)

**Testing**: no hay test runner de JavaScript en el proyecto (agregar uno requiere aprobación, Principio II). La verificación es `vue-tsc`, `vp check` y la validación manual de [quickstart.md](./quickstart.md). La suite de Pest de sprints se vuelve a correr para confirmar que los payloads no cambian.

**Target Platform**: Web

**Project Type**: Monolito Laravel + Inertia/Vue

**Performance Goals**: N/A (conversión de un string corto por sprint)

**Constraints**: no tocar `SprintHeader.vue` (detalle con `RichTextContent`) ni `SprintFormModal.vue` (edición)

**Scale/Scope**: 2 componentes Vue, 3 puntos de render

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Estado | Nota |
|-----------|--------|------|
| I. Monolith-First con Inertia | ✅ | Solo cambia el render de props existentes. |
| II. Ecosistema / dependencias | ✅ | Sin dependencias nuevas. |
| III. Test-First con Pest | ✅ justificado | No hay lógica de dominio nueva ni cambios en PHP. El helper reutilizado ya está en producción en `ProjectCard.vue`. No hay runner JS y agregar uno estaría fuera de alcance y requeriría aprobación. La verificación es manual (quickstart) más `vue-tsc`. |
| IV. Tipado estricto | ✅ | `computed` tipados en TS. |
| V. Simplicidad / YAGNI | ✅ | Reutiliza un helper existente; sin componente ni campo nuevo. |

**Post-design re-check**: sin cambios.

## Project Structure

### Documentation (this feature)

```text
specs/031-fix-sprint-goal-preview/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
└── tasks.md          # /speckit-tasks
```

Sin `contracts/`: no cambia ninguna ruta, prop ni payload.

### Source Code (repository root)

```text
resources/js/Components/Sprints/ProjectSprintsSection.vue   # tarjeta del sprint activo (≈L223) y filas de "Otros sprints" (≈L314, texto + :title)
resources/js/Components/Home/SprintPanel.vue                # panel del sprint en el inicio (≈L44)
```

**Structure Decision**: Se edita in situ con el patrón de `ProjectCard.vue`: un `computed` o una llamada a `tiptapToPlainText(goal)`, y un `v-if` sobre el resultado en lugar de sobre `goal`, para cumplir FR-005 (ocultar si no hay texto visible).

## Complexity Tracking

Sin violaciones.
