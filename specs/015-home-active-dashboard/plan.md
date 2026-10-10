# Implementation Plan: Panel de inicio con actividad del usuario

**Branch**: `015-home-active-dashboard` | **Date**: 2026-10-03 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/015-home-active-dashboard/spec.md`

## Summary

La ruta `/` deja de ser siempre un lienzo vacío: cuando el usuario autenticado tiene actividad (pertenece a ≥ 1 proyecto o tiene ≥ 1 tarea asignada), verá un panel de resumen personalizado según la pantalla de referencia "Friday — Inicio / Panel con Datos Activos (Minimalista)" (Stitch, proyecto 3835093330700486424): saludo con subtítulo contextual, tarjetas de resumen (sprint activo, mis tareas, proyectos), lista "Mis tareas para hoy & prioritarias", panel del sprint activo con gráfico de avance (ideal vs real en SVG), proyectos activos, insight semanal y feed "Actividad del equipo". Los usuarios sin actividad siguen viendo `HomeCanvas` intacto.

**Enfoque técnico**: `HomeController` invocable que decide en servidor y pasa una prop `dashboard` nullable a la misma página `AppHome` (condicional de componente en cliente). Persistencia nueva mínima para soportar FR-006/008/009: tabla `activities` + enum `ActivityType` + observers Eloquent (Task/Comment/Sprint) y columna `tasks.completed_at` con su índice. Gráficos en SVG a mano (sin dependencias). Todo con datos reales (FR-012), scope por membresía existente (`Project::isMember`), verificado con Pest.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13), Vue 3.5 + TypeScript (`<script setup>`)

**Primary Dependencies**: Inertia v3 (`inertiajs/inertia-laravel` + `@inertiajs/vue3`), Tailwind CSS v4 (tokens en `resources/css/app.css`), Wayfinder (`@laravel/vite-plugin-wayfinder`), Eloquent + PostgreSQL. **Sin dependencias nuevas.**

**Storage**: PostgreSQL (`pgsql`). Migraciones nuevas: tabla `activities` + `tasks.completed_at` + índices.

**Testing**: Pest (feature tests en `tests/Feature/Home/`), `npm run check` (vue-tsc + eslint) para frontend.

**Target Platform**: Web (Laravel monolítico Inertia — Principio I constitucional), escritorio priorizado, layout responsive a 1 columna.

**Project Type**: web-service (monolito web full-stack)

**Performance Goals**: panel visible < 2 s (SC-005); consultas con eager loading y límites (6 tareas / 4 proyectos / 8 eventos); índices nuevos en `activities` y `tasks`.

**Constraints**: modo claro únicamente (no existe dark mode en la app); UI en español hardcodeado (convención del proyecto); datos 100% reales, prohibido simular (FR-012); preservar contrato de props compartidas del sidebar.

**Scale/Scope**: 1 ruta (`/`), 1 controlador invocable, 1 modelo nuevo (`Activity`) + 1 enum nuevo (`ActivityType`), 3 observers, ~6 componentes Vue nuevos en `Components/Home/`, payload único definido en `contracts/dashboard-payload.md`.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Evaluación | Resultado |
| --- | --- | --- |
| I. Monolith-First con Inertia | El panel se entrega como props Inertia desde `HomeController` sobre la misma página `AppHome`; cero API desacoplada. | PASS |
| II. Alineación ecosistema Laravel | Generadores `make:controller/migration/model/enum` (artisan), Eloquent + observers, migraciones, Pest, Vite. Sin paquetes Composer/npm nuevos. | PASS |
| III. Test-First con Pest | Tests feature nuevos en `tests/Feature/Home/` cubren cada FR; contrato del sidebar protegido por test de regresión existente. | PASS |
| IV. Tipado estricto + enums | `ActivityType` enum string-backed; property promotion, return types y type hints en controlador/observers/modelo; payload tipado en TS. | PASS |
| V. Simplicidad / YAGNI | Persistencia nueva limitada a lo que FR-006/008/009 exigen (ver Complexity Tracking). Sin capas extra (sin API resources, sin servicios), siguiendo el patrón de mappers existente en controladores. | PASS con justificación |

**Re-check post-Phase 1**: las decisiones D2–D4 (tabla `activities`, `completed_at`, burndown desde activities) se mantienen como mecanismo mínimo para los FRs P2/P3 del spec; no se añadió `due_date` (D5) ni librería de charts (D11). PASS.

## Project Structure

### Documentation (this feature)

```text
specs/015-home-active-dashboard/
├── plan.md              # This file (/skill:speckit-plan command output)
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   └── dashboard-payload.md  # Phase 1 output
└── tasks.md             # Phase 2 output (/skill:speckit-tasks — NOT created here)
```

### Source Code (repository root)

```text
app/
├── Enums/
│   └── ActivityType.php              # nuevo: TaskCreated|TaskCompleted|CommentCreated|SprintStarted|SprintCompleted + label() es
├── Models/
│   └── Activity.php                  # nuevo: fillable, casts (type=>ActivityType), relaciones user/project/task/sprint
├── Observers/
│   ├── TaskObserver.php              # nuevo: created + status→Done (completed_at + activity) / reopen (completed_at=null)
│   ├── CommentObserver.php           # nuevo: comment_created activity
│   └── SprintObserver.php            # nuevo: sprint_started / sprint_completed activities
├── Http/
│   └── Controllers/
│       └── HomeController.php        # nuevo (invocable): gate actividad + payload dashboard (mappers privados)
database/
├── migrations/
│   └── 2026_XX_XX_create_activities_table.php   # nuevo: activities + índices (project_id,created_at)(user_id,created_at)(sprint_id,type,created_at)
│   └── 2026_XX_XX_add_completed_at_to_tasks_table.php  # nuevo: tasks.completed_at + índice (assignee_id,status)
└── factories/
    └── ActivityFactory.php           # nuevo
resources/js/
├── pages/
│   └── AppHome.vue                   # editar: defineProps<{dashboard: Dashboard|null}> + condicional panel/canvas
├── Components/
│   └── Home/                         # nuevo: ActivityDashboard.vue, SummaryCards.vue, MyTasksPanel.vue,
│                                     #        SprintPanel.vue (+ BurndownChart.vue SVG), ProjectsPanel.vue,
│                                     #        WeeklyInsightCard.vue, ActivityFeed.vue, DonutProgress.vue (SVG)
└── types/
    └── dashboard.ts                  # nuevo: interfaces del contrato (importadas por AppHome/componentes)
routes/
└── web.php                           # editar: closure / → HomeController (mismo name 'home', mismo middleware auth)
tests/
└── Feature/
    └── Home/                         # nuevo: DashboardPayloadTest, DashboardEmptyStateTest, ActivityFeedTest,
                                      #        WeeklyInsightTest, SprintPanelTest, DashboardScopeTest, TaskCompletedAtTest
```

**Structure Decision**: estructura existente del monolito (Option 1). Backend sigue el patrón de controladores con mappers privados (`ProjectSprintController`), sin capa de servicios ni API resources — consistente con el codebase. Frontend: la página decide componente por prop nullable; componentes nuevos agrupados por feature en `Components/Home/` (igual que `Components/Projects/`, `Components/Tasks/`). Observers registrados en `AppServiceProvider` (estilo Laravel 11+).

## Complexity Tracking

> Justificación de las desviaciones mínimas respecto al Principio V (YAGNI).

| Violación | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| Nueva tabla `activities` + 3 observers (D2) | FR-009 exige un feed de eventos reales del equipo (completar tarea, comentar, abrir/cerrar sprint); no existe ningún historial en el dominio. | Derivar de `comments`/`updated_at`: incompleto (solo comentarios) e incorrecto (`updated_at` se corrompe con ediciones posteriores). Paquete spatie/activitylog: dependencia nueva prohibida por Principio II y excesiva para el MVP. |
| Columna `tasks.completed_at` (D3) | FR-008 (insight semanal: resueltas + tiempo medio de ciclo) necesita el instante real de resolución por tarea. | `updated_at` como proxy: falso tras cualquier edición posterior. Join con `activities` por tarea: posible pero más complejo que un dato propio de la tarea, y el observer ya existe por D2. |
| Gráfico burndown con serie temporal (D4) | FR-006 exige "un gráfico de avance del sprint" y la pantalla de referencia lo muestra (ideal vs real). | Barra de progreso sola: no cumple FR-006. Snapshots diarios: persistencia adicional sin valor MVP; con `activities` la serie real se obtiene sin estructura extra. |
| Componentes SVG de gráficos a mano (D11) | El diseño incluye donut y línea de avance; no hay librería de charts en el proyecto. | Añadir Chart.js/D3: requiere aprobación de dependencia (Principio II) y no se justifica para 2 gráficos simples. |
