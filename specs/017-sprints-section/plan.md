# Implementation Plan: Sección Sprints del detalle de proyecto

**Branch**: `017-sprints-section` | **Date**: 2026-10-06 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/017-sprints-section/spec.md`

## Summary

La pestaña "Sprints y tareas" de `/projects/{id}` deja de mostrar una lista plana de sprints y pasa a la sección del diseño Stitch "Sección Sprints Pulida": encabezado con total y chips de filtro por estado (con conteos reales) + "Planificar un sprint"; tarjeta expandida del sprint activo (badge, fechas, días restantes laborables, meta, progreso "X de Y tareas" y %, "Ver tablero", "Completar sprint", y "Puntos de historia" como "Próximamente" deshabilitado); y lista "Otros Sprints" colapsable con filas por estado (fechas, tareas, % completado, meta resumida; Editar/Eliminar en planificados —solo propietario— y "Ver resumen" en completados).

**Enfoque técnico**: **cero cambios de backend** — todos los conteos, porcentajes y días restantes se derivan client-side con helpers puros sobre `project.sprints` / `project.activeSprint` / `project.tasks`, que ya viajan en el payload (mismo patrón que `activeSprintProgress` actual). Nuevo componente `ProjectSprintsSection.vue` que emite eventos hacia `Show.vue`, que conserva la propiedad de `SprintFormModal` y las acciones existentes.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13), Vue 3.5 + TypeScript (`<script setup>`)

**Primary Dependencies**: Inertia v3, Tailwind CSS v4 (tokens existentes), Wayfinder (`@/routes/projects/sprints`: store/show/update/destroy/start/complete), Pest, PostgreSQL. **Sin dependencias nuevas.**

**Storage**: PostgreSQL — sin cambios de esquema ni de payloads.

**Testing**: Pest (suite existente intacta — sin tests nuevos: no hay lógica de dominio nueva); `npm run check` (vue-tsc) + escenarios manuales de quickstart.md.

**Target Platform**: Web (monolito Inertia), escritorio priorizado, sección desplazable sin ruptura en pantallas pequeñas.

**Project Type**: web-service (monolito web full-stack)

**Performance Goals**: SC-005 — apertura de sección e interacciones (filtrar/colapsar/acciones) < 1 s percibidos; derivaciones puras O(n) sobre listas en memoria.

**Constraints**: modo claro únicamente; UI en español; payload de sprints congelado por tests existentes (solo añadir, nunca cambiar orden/campos actuales — esta feature no toca el backend); acciones de mutación solo propietario + proyecto activo; completados de solo lectura; datos 100% reales salvo "Puntos de historia" (Próximamente).

**Scale/Scope**: 1 componente nuevo (`ProjectSprintsSection.vue`, ~250-300 líneas), 1 página editada (Show.vue: reemplazar el contenido de la pestaña :711-884 y añadir wiring de eventos `complete`), tipos sin cambios (helpers puros locales), 0 tests PHP nuevos.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Evaluación | Resultado |
| --- | --- | --- |
| I. Monolith-First con Inertia | Sección Inertia sobre payload existente; sin API nueva. | PASS |
| II. Alineación ecosistema Laravel | Componente Vue por dominio (`Components/Sprints/`), Wayfinder, eventos hacia la página; sin paquetes nuevos. | PASS |
| III. Test-First con Pest | Sin lógica de dominio nueva (solo derivaciones client-side de datos existentes); tests de payload existentes protegen el contrato; verificación manual documentada. | PASS |
| IV. Tipado estricto + enums | Helpers puros con tipado (`sprintMetrics`, `weekdayDaysRemaining`); enums existentes (`SprintStatus`, `TaskStatus`) sin cambios. | PASS |
| V. Simplicidad / YAGNI | Cero migraciones, cero endpoints, cero cambios de payload; un solo componente nuevo; puntos de historia NO se implementan (Próximamente); estado de colapso solo en sesión. Sin desviaciones a justificar. | PASS |

**Re-check post-Phase 1**: D1–D9 mantienen backend intacto, props existentes, eventos hacia handlers existentes; el único "elemento nuevo" es el bloque deshabilitado de puntos de historia (FR-006), coherente con el precedente de la propia página de detalle de sprint. PASS.

## Project Structure

### Documentation (this feature)

```text
specs/017-sprints-section/
├── plan.md                  # This file
├── research.md              # Phase 0 output
├── data-model.md            # Phase 1 output
├── quickstart.md            # Phase 1 output
├── contracts/
│   └── sprints-section.md   # Phase 1 output
└── tasks.md                 # Phase 2 output (NOT created here)
```

### Source Code (repository root)

```text
resources/js/
├── Components/
│   └── Sprints/
│       └── ProjectSprintsSection.vue   # nuevo: sección completa (encabezado+chips, ActiveSprintCard, SprintRow[], colapso)
└── pages/
    └── Projects/
        └── Show.vue                      # editar: reemplazar contenido de la pestaña "Sprints y tareas" (:711-884)
                                              # por <ProjectSprintsSection>; añadir handler onSprintComplete (confirm+route complete)
```

**Structure Decision**: estructura monolítica existente. La pestaña General (con sus tarjetas Sprint Actual/Próximo/Backlog) **no se toca** — el rediseño actúa solo sobre la pestaña "Sprints y tareas" (research.md D1). `Show.vue` conserva `SprintFormModal`, `openSprintCreate/openSprintEdit/confirmSprintDestroy` y solo añade el wiring del evento `complete`.

## Complexity Tracking

> Sin violaciones del Principio V en esta feature: no hay migraciones, endpoints, payloads ni dependencias nuevas; las métricas se derivan de datos ya cargados (patrón existente `activeSprintProgress`); los puntos de historia se dejan explícitamente como "Próximamente" (FR-006) en lugar de construirse parcialmente.
