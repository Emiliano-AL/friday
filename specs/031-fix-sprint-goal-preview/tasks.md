---
description: 'Task list for 031-fix-sprint-goal-preview'
---

# Tasks: Corregir la vista previa del objetivo del sprint

**Input**: Design documents from `/specs/031-fix-sprint-goal-preview/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, quickstart.md

**Tests**: Sin tests nuevos. El cambio es solo de presentación en Vue, el proyecto no tiene runner de tests JS y en PHP no cambia nada. La justificación del Principio III está en plan.md. La verificación es `vue-tsc`, `vp check`, la regresión de Pest y el quickstart manual.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede correr en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: Historia de usuario (US1, US2)

---

## Phase 1: Setup

- [X] T001 Crear la rama `031-fix-sprint-goal-preview` desde `dev` (`git switch dev && git switch -c 031-fix-sprint-goal-preview`) y traer ahí `specs/031-fix-sprint-goal-preview/` (hoy sin commit en la rama `030-project-attachments`)

---

## Phase 2: Foundational

Sin tareas: el helper `tiptapToPlainText` ya existe en `resources/js/utils/tiptapContent.ts`.

---

## Phase 3: User Story 1 - Ver el objetivo del sprint como texto legible en la lista (Priority: P1) 🎯 MVP

**Goal**: Las filas de "Otros sprints" muestran el objetivo como texto legible, también en la ayuda emergente.

**Independent Test**: Un sprint planificado con objetivo enriquecido (párrafos, negrita, lista) se ve como una línea de texto en "Sprints y tareas"; al pasar el cursor se ve el texto completo. No aparece `{"type":"doc"`.

- [X] T002 [US1] En `resources/js/Components/Sprints/ProjectSprintsSection.vue`, importar `tiptapToPlainText` de `@/utils/tiptapContent`. En la fila de cada sprint de "Otros sprints" (el `<p v-if="sprint.goal" … :title="sprint.goal">{{ sprint.goal }}</p>`, ≈L314):
  - Reemplazar el texto y el `:title` por `tiptapToPlainText(sprint.goal)`.
  - Usar ese mismo resultado en el `v-if`, para que un objetivo sin texto visible no muestre la línea (FR-001, FR-002, FR-003, FR-005).
  - Mantener las clases `truncate` existentes.

**Checkpoint**: el bug del screenshot está corregido.

---

## Phase 4: User Story 2 - Mismo resumen legible en todas las vistas compactas (Priority: P1)

**Goal**: La tarjeta del sprint activo y el panel del sprint en el inicio muestran el objetivo como texto legible. El detalle del sprint no cambia.

**Independent Test**: Un sprint activo con objetivo enriquecido se ve legible en la tarjeta "Meta:" del proyecto y en el panel del inicio (dos líneas como máximo). En `/projects/{id}/sprints/{sprint}`, el objetivo conserva negritas y listas.

- [X] T003 [US2] En `resources/js/Components/Sprints/ProjectSprintsSection.vue`, en la tarjeta del sprint activo (`<p v-if="active.goal" …>` con "Meta:", ≈L223), reemplazar `{{ active.goal }}` por un `computed` `activeGoalPreview = tiptapToPlainText(active?.goal)` y usarlo también en el `v-if`. Seguir el patrón de `descriptionExcerpt` en `resources/js/Components/Projects/ProjectCard.vue`.
- [X] T004 [P] [US2] En `resources/js/Components/Home/SprintPanel.vue` (`<p v-if="sprint.goal" … line-clamp-2>{{ sprint.goal }}</p>`, ≈L44):
  - Importar `tiptapToPlainText` de `@/utils/tiptapContent`.
  - Agregar un `computed` `goalPreview` y usarlo en el `v-if` y en el texto.
  - Mantener `line-clamp-2`.
- [X] T005 [US2] Confirmar que no hay cambios en `resources/js/Components/Sprints/SprintHeader.vue` (detalle con `RichTextContent`, FR-006) ni en `resources/js/Components/Sprints/SprintFormModal.vue` (edición, FR-007): `git diff --stat` solo debe listar `ProjectSprintsSection.vue` y `SprintPanel.vue`

---

## Phase 5: Polish & Cross-Cutting Concerns

- [X] T006 Ejecutar `npm run types:check` y `npx vp check --fix resources/js/Components/Sprints/ProjectSprintsSection.vue resources/js/Components/Home/SprintPanel.vue`
- [X] T007 Ejecutar la regresión `php artisan test --compact tests/Feature/Sprints tests/Feature/Home` y `npm run build`
- [ ] T008 Validación manual de `specs/031-fix-sprint-goal-preview/quickstart.md` (pasos 1–6) en el navegador

---

## Dependencies & Execution Order

- T001 → todo lo demás.
- US1 (T002) y US2 (T003–T005) son independientes. T002 y T003 tocan el mismo archivo, así que van en secuencia. T004 es otro archivo [P].
- Polish (T006–T008) al final.

## Parallel Example

```text
T002 → T003 (ProjectSprintsSection.vue, en secuencia)
T004 (SprintPanel.vue) en paralelo con T002/T003
```

## Implementation Strategy

1. **MVP**: T001 + T002, que corrige exactamente lo del screenshot.
2. T003–T005, para el mismo bug en las otras vistas.
3. T006–T008, la verificación.
