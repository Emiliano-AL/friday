# Implementation Plan: Gestión de Sprints (CRUD y fechas)

**Branch**: `003-sprint-crud` | **Date**: 2026-09-21 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/003-sprint-crud/spec.md`

## Summary

CRUD de sprints anidado en proyectos sobre las features 001–002: el propietario
de un proyecto **activo** crea sprints con nombre obligatorio y fechas de
inicio/fin (`date`, validación `end_date >= start_date`), los consulta
ordenados por fecha de inicio ascendente en la ficha del proyecto y los
edita/elimina con la misma validación. Fuera del estado activo (y para
colaboradores) la superficie queda en solo lectura con bloqueo en servidor y
mensaje claro (FR-006/SC-003). Sin estado propio del sprint, sin páginas
propias, sin paquetes nuevos, sin API: `ProjectSprintController` plano
(store/update/destroy), `SprintPolicy` (miembro lee / propietario+activo
escribe), sección de UI dentro de `Projects/Show.vue` y suites Pest en
`tests/Feature/Sprints/`.

## Technical Context

**Language/Version**: PHP 8.4 (constraint `^8.3`) + TypeScript / Vue 3.5 en el frontend

**Primary Dependencies**: Laravel 13.x, Inertia Laravel 3 + `@inertiajs/vue3`, Tailwind 4, Wayfinder, Pest 5. **Sin dependencias nuevas** — se apoya en el scaffolding de las features 001–002.

**Storage**: PostgreSQL (desarrollo/producción); SQLite `:memory:` en tests (phpunit.xml)

**Testing**: Pest 5 feature tests con `SprintFactory`; TDD obligatorio (Principio III)

**Target Platform**: Aplicación web monolítica (Laravel Herd local); despliegue web estándar

**Project Type**: web application (Inertia monolith, sin API desacoplada — Principio I)

**Performance Goals**: Lista de hasta 50 sprints del proyecto en < 2 s (SC-002) — trivial con índice compuesto `(project_id, start_date)` + eager load en la ficha; sin requisitos adicionales

**Constraints**: Mensaje claro al bloquear escrituras en proyecto no activo (FR-006/SC-003: redirect con `errors.project`); 404 indistinguible para no propietarios y sprints ajenos; fechas `date` calendario sin horas (FR-001); CI exige `vp check`, `vue-tsc`, `pint --test` y `phpstan`

**Scale/Scope**: MVP — sin límite de sprints por proyecto (FR-008), sin restricción de solapamiento ni de fechas pasadas (FR-009), sin estado/métricas/tareas del sprint en esta iteración

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio                                | Evaluación pre-diseño                                                                                                                              | Resultado |
| ---------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- | --------- |
| I. Monolith-First con Inertia            | Controlador + sección dentro de `Projects/Show`; cero endpoints de API                                                                             | PASS      |
| II. Alineación con el Ecosistema         | Artisan (`make:model -f`, `make:policy`, `make:migration`), Eloquent, Form Requests, Policy auto-discovery; cero dependencias nuevas               | PASS      |
| III. Test-First con Pest                 | Suites `SprintCrudTest` / `SprintAccessTest` por historia (US1–US4) escritas antes de la implementación                                            | PASS      |
| IV. Tipado Estricto y Estados como Enums | El sprint no tiene estado propio (spec; research R-3); única máquina de estados involucrada: `ProjectStatus` con `isActive()`; sin strings sueltos | PASS      |
| V. Simplicidad y YAGNI                   | Sin `SprintStatus`, sin páginas/rutas de listado propias, sin resource completo, sin restricción de solapamiento, sin vistas de error nuevas       | PASS      |

**Post-design re-check (tras Phase 1)**: el diseño mantiene los 5 PASS — la
lista viaja en el payload existente de la ficha (sin rutas muertas), el
bloqueo de estado se resuelve con redirect + errores de sesión (precedente
`ProjectController::status()`, sin vistas nuevas), la autorización vive en
`SprintPolicy` convencional y la validación en Form Requests. Sin violaciones
que justificar.

## Project Structure

### Documentation (this feature)

```text
specs/003-sprint-crud/
├── plan.md              # This file (/skill:speckit-plan command output)
├── research.md          # Phase 0 output (/skill:speckit-plan command)
├── data-model.md        # Phase 1 output (/skill:speckit-plan command)
├── quickstart.md        # Phase 1 output (/skill:speckit-plan command)
├── contracts/           # Phase 1 output (/skill:speckit-plan command)
│   └── web-sprints-surface.md
└── tasks.md             # Phase 2 output (/skill:speckit-tasks command - NOT created by /skill:speckit-plan)
```

### Source Code (repository root)

Estructura single-project (web app). Rutas reales; todo lo nuevo es aditivo
sobre las features 001–002:

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── ProjectSprintController.php            # NUEVO: store/update/destroy (research R-6)
│   └── Requests/
│       ├── StoreSprintRequest.php                 # NUEVO: name/start_date/end_date + after_or_equal
│       └── UpdateSprintRequest.php                # NUEVO: idéntico a Store
├── Models/
│   ├── Project.php                                # MODIFICADO: + sprints() HasMany, payload con sprints ordenados
│   └── Sprint.php                                 # NUEVO: + factory, BelongsTo project, casts date
├── Policies/
│   └── SprintPolicy.php                           # NUEVO: viewAny/view = miembro; create/update/delete = propietario + activo
database/
├── factories/
│   └── SprintFactory.php                          # NUEVO
├── migrations/
│   └── 2026_09_21_000001_create_sprints_table.php # NUEVO: FK cascade + índice (project_id, start_date)
resources/js/
├── pages/
│   └── Projects/
│       └── Show.vue                               # MODIFICADO: sección Sprints (alta inline, edición modal, baja confirmada, solo lectura fuera de activo)
routes/
└── web.php                                        # MODIFICADO: projects.sprints.store/update/destroy (grupo auth)
tests/
└── Feature/
    └── Sprints/                                   # NUEVO (suites Pest por historia)
        ├── SprintCrudTest.php                     # US1–US3: alta, lista ordenada, validación, edición, eliminación
        └── SprintAccessTest.php                   # US4: solo lectura, colaborador, no miembro → 404, mensaje FR-006
```

**Structure Decision**: se conserva la estructura single-project existente.
Convenciones heredadas de la feature 002: controlador plano anidado por
convención de nombre (`ProjectSprintController`, como `ProjectMemberController`),
rutas explícitas nombradas `projects.sprints.*` dentro del grupo `auth`,
Form Requests con `authorize()` solo de autenticación (propiedad y estado se
resuelven en controlador/policy), payload de la ficha como arrays en
camelCase, Policy auto-discovery por convención (Laravel 11+), páginas Inertia
TS con imports de Wayfinder (`@/routes/projects/sprints`) y componentes de
formulario Breeze existentes. La gestión de sprints no tiene páginas propias:
vive en `Projects/Show.vue` según la assumption de la spec (research R-5/R-7).
