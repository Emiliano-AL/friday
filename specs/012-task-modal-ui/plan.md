# Implementation Plan: Modal de Tarea Mejorado (Secciones Colapsables y Acciones Alineadas)

**Branch**: `012-task-modal-ui` | **Date**: 2026-09-30 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/012-task-modal-ui/spec.md`

## Summary

Rediseño de presentación del diálogo de crear/editar tarea
(`resources/js/Components/Tasks/Board/TaskFormModal.vue`), siguiendo la
referencia visual "Friday — Modal Tarea Mejorado (Colapsable & Acciones
Alineadas)" (Stitch, proyecto 3835093330700486424):

1. **Secciones colapsables**: "Vinculación y alcance" y "Estimación y fechas"
   se convierten en acordeones mediante un nuevo componente local
   `CollapsibleSection.vue` (Vue `<Transition>` + técnica grid-rows, encabezado
   tipo botón con `aria-expanded` e indicadores Mostrar/Ocultar).
2. **Descripción más alta**: `RichTextEditor` gana dos props opcionales
   (`minHeight`, `maxHeightClass`); el formulario de tarea fija altura mínima
   ~240 px con scroll interno en `.codex-editor`.
3. **Acciones alineadas**: el pie concentra "Eliminar tarea" (edición) o
   "Crear otra" (creación) a la izquierda y Cancelar/Guardar a la derecha; el
   cuerpo del diálogo adopta scroll propio (`max-h-[85vh]`) para mantener el
   pie siempre visible.

Sin cambios de modelo de datos, endpoints ni dependencias. Verificación por
suíte Pest existente (regresión), `vue-tsc`, build y checklist manual.

## Technical Context

**Language/Version**: PHP 8.4 (sin cambios backend) / Vue 3.5 + TypeScript (`<script setup>`)

**Primary Dependencies**: Laravel 13.17, Inertia.js v3 (`@inertiajs/vue3`), Tailwind CSS v4 (tokens vía `@theme` en `resources/css/app.css` según `DESIGN.md`), EditorJS v2.31 (`@editorjs/editorjs` + plugins). **Sin dependencias nuevas.**

**Storage**: N/A — sin migraciones ni cambios de datos (PostgreSQL en prod / SQLite en desarrollo local).

**Testing**: Pest (feature tests existentes de `tests/Feature/Tasks` y `tests/Feature/RichText` como puerta de regresión; sin tests nuevos por ser cambio de UI puro — reglas Pest del proyecto).

**Target Platform**: Web desktop-first (modal responsivo apilado en viewports pequeños).

**Project Type**: Web application (monolito Laravel + Inertia, per Constitución I).

**Performance Goals**: Transiciones de colapso < 0,5 s percibidas como suaves (SC-005); apertura del diálogo sin saltos de layout.

**Constraints**: Sin dependencias nuevas (Constitución II); reutilizar tokens de diseño existentes (sin tokens/keyframes nuevos — animación con Vue `<Transition>`, house style); accesibilidad de los toggles (`aria-expanded`, foco por teclado).

**Scale/Scope**: 1 componente nuevo (`CollapsibleSection.vue`) + 2 modificados (`TaskFormModal.vue` ~613 líneas, `RichTextEditor.vue`); 2 modos del diálogo (crear/editar); 1 superficie (diálogo de tarea del board/backlog).

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Evaluación | Notas |
| --------- | ---------- | ----- |
| I. Monolith-First con Inertia | ✅ PASS | Cambio exclusivamente frontend dentro del diálogo Inertia; sin API desacoplada. |
| II. Alineación con el ecosistema (sin deps nuevas) | ✅ PASS | Solo Vue 3 + Tailwind v4 + EditorJS ya instalados; el acordeón es un componente propio mínimo (no se justifica librería). |
| III. Test-First con Pest | ✅ PASS (excepción justificada) | No hay lógica de dominio nueva; es cambio de UI puro y la regla del proyecto lo exime de tests nuevos. Puerta de regresión: suítes Pest existentes de Tasks/RichText deben seguir verdes. |
| IV. Tipado estricto y enums | ✅ PASS | Sin cambios PHP; el componente nuevo usa `<script setup lang="ts">` con props tipadas, igual que sus hermanos. |
| V. Simplicidad y YAGNI | ✅ PASS | Se reutiliza `Modal.vue` (sin slots nuevos), `UiBadge`, `AppIcon` y el editor existente; el estado de colapso es local y efímero. |

**Re-evaluación post-Fase 1**: el diseño (D1–D7 en [research.md](research.md)) no
introduce violaciones nuevas: cero dependencias, cero cambios de datos, un solo
componente nuevo acotado al alcance del spec.

## Project Structure

### Documentation (this feature)

```text
specs/012-task-modal-ui/
├── plan.md              # This file (/skill:speckit-plan command output)
├── research.md          # Phase 0 output (decisiones D1–D7)
├── data-model.md        # Phase 1 output (sin cambios de datos)
├── quickstart.md        # Phase 1 output (guía de validación)
├── contracts/
│   └── 012-task-modal-ui.md  # Phase 1 output (contrato de UI)
└── tasks.md             # Phase 2 output (/skill:speckit-tasks — NOT created here)
```

### Source Code (repository root)

```text
resources/js/
├── Components/
│   ├── CollapsibleSection.vue                 # NEW: acordeón accesible y animado
│   ├── Modal.vue                              # sin cambios
│   ├── RichText/
│   │   └── RichTextEditor.vue                 # + props minHeight / maxHeightClass
│   └── Tasks/
│       └── Board/
│           └── TaskFormModal.vue              # reestructuración (acordeones, scroll, pie)
└── (otros consumidores de RichTextEditor sin cambios: TaskModal, ProjectFormModal, SprintFormModal)

tests/
├── Feature/Tasks/                             # regresión (sin cambios en tests)
└── Feature/RichText/                          # regresión (sin cambios en tests)
```

**Structure Decision**: estructura de aplicación web única (monolito). El
componente nuevo se ubica en `Components/CollapsibleSection.vue`, junto a
`Modal.vue` y `Dropdown.vue` (convención actual de componentes compartidos en
la raíz de `Components/`). `Modal.vue` no se modifica (ver D3 en research.md).
No hay cambios backend.

## Complexity Tracking

Sin violaciones de la constitución que justificar: la tabla queda vacía a
propósito (sección reservada del template).
