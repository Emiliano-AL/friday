# Implementation Plan: Evitar el zoom automático al enfocar campos en móvil

**Branch**: `032-mobile-input-zoom` | **Date**: 2026-10-17 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/032-mobile-input-zoom/spec.md`

## Summary

Safari en iOS acerca la página al enfocar un campo cuyo texto computado mide menos de 16 px. La mayoría de los campos de la app usan `text-sm`/`text-body-md` (14 px) y el editor Tiptap fija 14 px en su estilo scoped. Solución: una regla CSS global, sin capa, dentro de `@media (max-width: 767px)`, que fija `font-size: 16px` en los campos de texto, más un override equivalente en el editor enriquecido. No se toca el meta viewport (ver [research.md](./research.md)).

## Technical Context

**Language/Version**: CSS (Tailwind CSS v4.1), Vue 3 SFC

**Primary Dependencies**: Tailwind CSS v4, Tiptap (ya instalados; sin dependencias nuevas)

**Storage**: N/A

**Testing**: Verificación manual en Safari iOS (dispositivo o simulador). Sin tests Pest: cambio solo de estilos (spec, Assumptions).

**Target Platform**: Navegadores móviles, principalmente Safari / WebKit en iOS

**Project Type**: Web (monolito Laravel + Inertia + Vue)

**Performance Goals**: N/A

**Constraints**: No bloquear el zoom manual (FR-003); escritorio sin cambios visuales (FR-004)

**Scale/Scope**: ~19 componentes con campos + 1 editor enriquecido; 3 archivos tocados

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Estado | Nota |
|-----------|--------|------|
| I. Monolith-First con Inertia | ✅ | Solo CSS en el frontend existente |
| II. Ecosistema Laravel / sin dependencias nuevas | ✅ | Tailwind y CSS nativo ya presentes |
| III. Test-First con Pest | ✅ (N/A) | No hay lógica de dominio; el Principio III aplica a lógica de dominio. La guía del proyecto exime cambios solo de estilos |
| IV. Tipado estricto / enums | ✅ (N/A) | Sin PHP |
| V. Simplicidad y YAGNI | ✅ | Una regla global + un override; sin componentes ni utilidades nuevas |

Re-check post-diseño: sin cambios, todos los gates pasan.

## Project Structure

### Documentation (this feature)

```text
specs/032-mobile-input-zoom/
├── plan.md        # Este archivo
├── research.md    # Fase 0
├── quickstart.md  # Fase 1: guía de verificación manual
└── tasks.md       # Fase 2 (/speckit-tasks)
```

`data-model.md` y `contracts/` se omiten: la feature no introduce entidades ni interfaces externas.

### Source Code (repository root)

```text
resources/css/app.css                                    # regla global móvil (sin capa, al final del archivo)
resources/js/Components/RichText/RichTextEditor.vue      # override móvil del font-size de .tiptap
resources/js/Components/Tasks/Board/TaskFormModal.vue    # input de título (20 px): text-headline-md → text-headline-md! para conservar su tamaño (FR-006)
```

**Structure Decision**: Sin carpetas nuevas. La regla vive en el CSS global existente para que cubra automáticamente los campos futuros (FR-005).

## Diseño

1. **Regla global** en `resources/css/app.css`, sin capa, al final del archivo:
   - Selector: `input:not([type=checkbox], [type=radio], [type=file], [type=range], [type=color], [type=button], [type=submit], [type=reset], [type=image], [type=hidden]), textarea, select`. Una lista de exclusión cubre también `search`, `url`, `date`, `time` y los inputs sin `type`, que la lista de la petición original omitía.
   - `font-size: 16px` dentro de `@media (max-width: 767px)` (por debajo del breakpoint `md` de Tailwind, que la app ya usa para la disposición móvil).
   - Por qué sin capa: los utilities de Tailwind v4 viven en `@layer utilities`; CSS sin capa les gana sin importar la especificidad, así que `text-sm` y similares quedan sobrescritos en móvil.
2. **Editor enriquecido**: el estilo scoped `.rich-text-editor__content :deep(.tiptap)` fija 14 px con mayor especificidad. Se agrega un `@media (max-width: 767px)` en el mismo bloque `<style scoped>` con `font-size: 16px; line-height: 24px`. Basta con la raíz contenteditable: iOS decide el zoom por el tamaño del elemento enfocado.
3. **Excepción FR-006**: el único campo con texto mayor a 16 px es el título de `TaskFormModal` (`text-headline-md`, 20 px). Como el CSS sin capa le gana a cualquier utility normal, se le agrega el modificador important (`text-headline-md!`), que sí le gana al CSS sin capa.

## Complexity Tracking

Sin violaciones.
