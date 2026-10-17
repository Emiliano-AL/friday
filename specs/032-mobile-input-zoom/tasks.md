---
description: 'Task list for 032-mobile-input-zoom'
---

# Tasks: Evitar el zoom automático al enfocar campos en móvil

**Input**: Design documents from `/specs/032-mobile-input-zoom/`

**Prerequisites**: plan.md, spec.md, research.md, quickstart.md

**Tests**: Sin tests automatizados. El cambio es solo de estilos (spec, Assumptions); la verificación es manual con [quickstart.md](./quickstart.md).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2)

---

## Phase 1: Setup / Phase 2: Foundational

Sin tareas. No hay estructura, dependencias ni prerrequisitos nuevos. Antes de empezar, crear la rama `032-mobile-input-zoom` desde `dev`.

---

## Phase 3: User Story 1 - Escribir en un formulario sin que la página se acerque (Priority: P1) 🎯 MVP

**Goal**: Ningún campo de texto, área de texto, select ni editor enriquecido provoca zoom automático al enfocarlo en móvil.

**Independent Test**: En Safari de iOS (dispositivo o simulador), con un ancho menor a 768 px, tocar cada campo de los escenarios 1–6 de quickstart.md y comprobar que la página no cambia de escala ni aparece scroll horizontal.

### Implementation for User Story 1

- [X] T001 [P] [US1] Al final de `resources/css/app.css`, fuera de cualquier `@layer` y después de todo lo demás, agregar `@media (max-width: 767px) { input:not([type=checkbox], [type=radio], [type=file], [type=range], [type=color], [type=button], [type=submit], [type=reset], [type=image], [type=hidden]), textarea, select { font-size: 16px; } }`. Debe quedar sin capa para ganarle a los utilities `text-*` de Tailwind v4 (research.md §3). Encima, un comentario de una línea que explique que evita el zoom automático de iOS en campos con texto < 16 px.
- [X] T002 [P] [US1] En `resources/js/Components/RichText/RichTextEditor.vue`, dentro del `<style scoped>` existente y justo después del bloque `.rich-text-editor__content :deep(.tiptap) { ... }`, agregar `@media (max-width: 767px) { .rich-text-editor__content :deep(.tiptap) { font-size: 16px; line-height: 24px; } }`. No tocar los estilos de `h2`/`h3`.

**Checkpoint**: Los escenarios 1, 2, 3, 5 y 6 de quickstart.md pasan.

---

## Phase 4: User Story 2 - Conservar el zoom manual y el diseño de escritorio (Priority: P2)

**Goal**: Los campos con texto mayor a 16 px conservan su tamaño, el pellizco sigue funcionando y escritorio queda sin cambios.

**Independent Test**: Escenarios 4, 7, 8 y 9 de quickstart.md.

### Implementation for User Story 2

- [X] T003 [US2] En `resources/js/Components/Tasks/Board/TaskFormModal.vue` (input del título, ~línea 297), cambiar la clase `text-headline-md` por `text-headline-md!` (modificador important de Tailwind v4) para que le gane a la regla sin capa de T001 y siga en 20 px en móvil (FR-006). Depende de T001.
- [X] T004 [US2] Confirmar que `<meta name="viewport">` en `resources/views/app.blade.php` sigue siendo `width=device-width, initial-scale=1`, sin `maximum-scale` ni `user-scalable=no` (FR-003). Solo verificar, sin editar.

**Checkpoint**: Los escenarios 4, 7, 8 y 9 de quickstart.md pasan.

---

## Phase 5: Polish & Cross-Cutting Concerns

- [X] T005 Ejecutar `npm run build` y el snippet de DevTools de `specs/032-mobile-input-zoom/quickstart.md` con un ancho < 768 px. Debe devolver solo checkboxes, radios o archivos.
- [ ] T006 Recorrer los 9 escenarios de `specs/032-mobile-input-zoom/quickstart.md` en Safari de iOS y anotar el resultado.

---

## Dependencies & Execution Order

- T001 y T002 son independientes (archivos distintos) → [P].
- T003 depende de T001 (solo tiene sentido una vez que la regla global existe).
- T004 es independiente.
- T005 y T006 van al final.

US2 depende de US1 solo en T003. No hay dependencias en la otra dirección.

## Parallel Example: User Story 1

```text
T001 resources/css/app.css
T002 resources/js/Components/RichText/RichTextEditor.vue
```

## Implementation Strategy

1. **MVP**: T001 + T002 resuelven el bug reportado. Ya se puede validar.
2. T003 + T004 cierran las regresiones (título de 20 px, zoom manual).
3. T005 + T006: verificación final.
