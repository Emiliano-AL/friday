# Implementation Plan: Gestión de Proyectos (CRUD, métricas y colaboradores)

**Branch**: `002-project-management` | **Date**: 2026-09-20 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/002-project-management/spec.md`

## Summary

Implementar el módulo de proyectos de Friday sobre la base de la feature 001
(auth): CRUD completo de proyectos multidisciplinarios (título + descripción
opcional), ciclo de vida con estados activo/archivado/completado (enum
`ProjectStatus`, Principio IV), cálculo de porcentaje de avance (0% hasta que
el módulo de tareas exista; la fórmula completadas/totales queda acotada en el
modelo), membresía con propietario explícito (`owner_id`) y colaboradores
(pivote `project_user`), autorización con Policy (no miembros → 404) y páginas
Inertia en TypeScript con Wayfinder. Sin paquetes nuevos.

## Technical Context

**Language/Version**: PHP 8.4 (constraint `^8.3`) + TypeScript 5.2 / Vue 3.5 en el frontend

**Primary Dependencies**: Laravel 13.17, Inertia Laravel 3.3.4 + `@inertiajs/vue3` 3, Tailwind 4, Wayfinder, Pest 5. **Sin dependencias nuevas** — se apoya en el scaffolding de auth de la feature 001 (layouts, `User`, sesión).

**Storage**: PostgreSQL (desarrollo/producción); SQLite `:memory:` en tests (phpunit.xml)

**Testing**: Pest 5 feature tests con factories (`ProjectFactory` con estados de ciclo de vida y membresías); TDD obligatorio (Principio III)

**Target Platform**: Aplicación web monolítica (Laravel Herd local); despliegue web estándar

**Project Type**: web application (Inertia monolith, sin API desacoplada)

**Performance Goals**: Lista de hasta 50 proyectos por usuario en < 2 s (p95, SC-002) — trivial con índice en `owner_id`/`project_user` y conteos cargados de forma ansiosa; sin requisitos adicionales

**Constraints**: Monolito Inertia (Prohibida API REST); CI exige `vp check`, `vue-tsc`, `pint --test` y `phpstan`; estados del dominio como enums (Principio IV); sesión autenticada obligatoria (feature 001)

**Scale/Scope**: MVP — decenas de usuarios, hasta ~50 proyectos por usuario y un puñado de colaboradores por proyecto; sin roles intermedios ni permisos finos

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio                                | Evaluación pre-diseño                                                                                                                | Resultado |
| ---------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ | --------- |
| I. Monolith-First con Inertia            | Controladores + páginas Inertia en el mismo desplegable; sin endpoints de API                                                        | PASS      |
| II. Alineación con el Ecosistema         | Artisan (`make:model -f`, `make:policy`, `make:migration`), Eloquent, Form Requests, Policy auto-discovery; cero dependencias nuevas | PASS      |
| III. Test-First con Pest                 | Suites de feature por historia (CRUD, ciclo de vida, miembros, acceso, avance) escritas antes de la implementación                   | PASS      |
| IV. Tipado Estricto y Estados como Enums | `ProjectStatus` backed enum (única fuente de los estados); tipado explícito en controladores/requests/policies; casts en el modelo   | PASS      |
| V. Simplicidad y YAGNI                   | Sin roles, sin invitaciones por correo, sin campos visuales, sin caché de progreso, sin tablas de tareas; solo lo de la spec         | PASS      |

**Post-design re-check (tras Phase 1)**: el diseño mantiene los 5 PASS — el
cálculo de avance se acota a un método del modelo (sin caché ni columna
derivada), la autorización vive en una Policy convencional, y las páginas se
limitan a Index/Create/Show/Edit. Sin violaciones que justificar.

## Project Structure

### Documentation (this feature)

```text
specs/002-project-management/
├── plan.md              # This file (/skill:speckit-plan command output)
├── research.md          # Phase 0 output (/skill:speckit-plan command)
├── data-model.md        # Phase 1 output (/skill:speckit-plan command)
├── quickstart.md        # Phase 1 output (/skill:speckit-plan command)
├── contracts/           # Phase 1 output (/skill:speckit-plan command)
└── tasks.md             # Phase 2 output (/skill:speckit-tasks command - NOT created by /skill:speckit-plan)
```

### Source Code (repository root)

Estructura single-project (web app). Rutas reales; todo lo nuevo es aditivo
sobre la feature 001:

```text
app/
├── Enums/
│   └── ProjectStatus.php                        # NUEVO: backed enum + transiciones permitidas
├── Http/
│   ├── Controllers/
│   │   ├── ProjectController.php                # NUEVO: resource (index/create/store/show/edit/update/destroy)
│   │   └── ProjectMemberController.php          # NUEVO: store/destroy de colaboradores
│   ├── Requests/
│   │   ├── StoreProjectRequest.php              # NUEVO
│   │   └── UpdateProjectRequest.php             # NUEVO
│   └── Middleware/                              # existente (sin cambios)
├── Models/
│   ├── Project.php                              # NUEVO: + factory, relations, progressPercentage()
│   └── User.php                                 # MODIFICADO: relaciones ownedProjects()/projects()
├── Policies/
│   └── ProjectPolicy.php                        # NUEVO: view (miembro), update/delete/members (propietario), transition
database/
├── factories/
│   └── ProjectFactory.php                       # NUEVO: estados archived/completed, miembros
├── migrations/
│   ├── 2026_09_20_000001_create_projects_table.php        # NUEVO
│   └── 2026_09_20_000002_create_project_user_table.php    # NUEVO (pivote de membresía)
resources/js/
├── pages/
│   └── Projects/                                # NUEVO (páginas Inertia TS con Wayfinder)
│       ├── Index.vue                            # lista + enlace de alta
│       ├── Create.vue                           # formulario de alta
│       ├── Show.vue                             # ficha: datos, avance, miembros, acciones
│       └── Edit.vue                             # edición de título/descripción
routes/
├── web.php                                      # MODIFICADO: resource projects + members anidados (auth)
tests/
└── Feature/
    └── Projects/                                # NUEVO (suites Pest por historia)
        ├── ProjectCrudTest.php                  # US1/US2: alta, lista, edición, eliminación
        ├── ProjectLifecycleTest.php             # US2: estados y transiciones
        ├── ProjectMembersTest.php               # US4: altas/retiros/validaciones
        ├── ProjectAccessTest.php                # FR-009: no miembros → 404
        └── ProjectProgressTest.php              # US3: 0% y contrato de la fórmula
```

**Structure Decision**: se conserva la estructura single-project existente.
Convenciones heredadas de la feature 001: páginas bajo `resources/js/pages`
con `<script setup lang="ts">`, navegación vía imports de Wayfinder
(`@/routes`, alias por nombre de ruta), layouts `AuthenticatedLayout` (app) y
`GuestLayout`. La Policy `ProjectPolicy` se descubre automáticamente por
convención (Laravel 11+); si la convención fallara, se registraría en
`AppServiceProvider`. El controlador de miembros se anida bajo el recurso
(`projects/{project}/members`) en `routes/web.php` dentro del grupo `auth`.
