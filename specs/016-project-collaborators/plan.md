# Implementation Plan: Gestión de colaboradores del proyecto

**Branch**: `016-project-collaborators` | **Date**: 2026-10-05 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/016-project-collaborators/spec.md`

## Summary

El formulario inline de colaboradores en `/projects/{id}` se elimina y se reemplaza por el modal "Gestionar colaboradores" del diseño Stitch (misma ruta/página): lista propietario primero (badge "Propietario") + colaboradores con avatar, nombre, correo y conteo de tareas abiertas; agregar por correo y eliminar con confirmación advertida, todo sobre los **endpoints existentes sin cambios** (contrato fijado por 7 tests). Las funciones del diseño que no existen (roles/permisos, enlace rápido, invitaciones pendientes, notificaciones por correo) se muestran deshabilitadas con "Próximamente". Se cierra el gap de asignación: el propietario pasa a incluirse en las opciones de responsable de tareas (la validación de servidor ya existía).

**Enfoque técnico**: cero migraciones y cero dependencias nuevas; un componente `MembersModal.vue` (patrón SprintFormModal); `ProjectController::projectPayload` añade `openTasksCount` derivado vía una sola consulta agregada; `TaskController@index` (y contexto de sprints) incluyen al propietario en `members`; tipos actualizados; tests Pest nuevos para payload y opciones de asignación.

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13), Vue 3.5 + TypeScript (`<script setup>`)

**Primary Dependencies**: Inertia v3, Tailwind CSS v4 (tokens existentes), Wayfinder (`@/routes/projects/members`), Pest, PostgreSQL. **Sin dependencias nuevas.**

**Storage**: PostgreSQL — sin cambios de esquema (solo datos derivados en payloads).

**Testing**: Pest (tests nuevos en `tests/Feature/Projects/`); los 7 tests del contrato de miembros deben seguir verdes sin tocarlos; `npm run check` para frontend.

**Target Platform**: Web (monolito Inertia — Principio I), escritorio priorizado, modal desplazable en pantallas pequeñas.

**Project Type**: web-service (monolito web full-stack)

**Performance Goals**: apertura del modal y acciones < 1 s percibidos (SC-005); la consulta agregada de `openTasksCount` es 1 query por proyecto (sin N+1).

**Constraints**: modo claro únicamente; UI en español; endpoints de miembros con contrato congelado (redirects + session errors `email`); funciones no implementadas deshabilitadas con "Próximamente" (sin endpoints fantasma).

**Scale/Scope**: 1 modal nuevo, 1 página editada (Show.vue), 1 componente nuevo, 2 controladores con mappers ampliados (ProjectController, TaskController) + verificación del payload de sprints, tipos en `types/project.ts`, 2 archivos de tests nuevos.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Evaluación | Resultado |
| --- | --- | --- |
| I. Monolith-First con Inertia | Modal consume endpoints Inertia existentes con redirect-re-render; sin API nueva. | PASS |
| II. Alineación ecosistema Laravel | Componente Vue por dominio, `useForm`, Wayfinder, mappers en controladores, Pest. Sin paquetes nuevos. | PASS |
| III. Test-First con Pest | Tests nuevos para payload/assignee; contrato existente protegido por sus 7 tests sin modificación. | PASS |
| IV. Tipado estricto + enums | Tipos TS extendidos (`openTasksCount`), mappers PHP tipados. Sin enums nuevos (roles deliberadamente fuera de alcance). | PASS |
| V. Simplicidad / YAGNI | Cero migraciones; roles/invitaciones/notificaciones NO se implementan (deshabilitadas); sin componente de confirmación nuevo (`window.confirm` como precedente). Sin desviaciones a justificar. | PASS |

**Re-check post-Phase 1**: D1–D10 mantienen 0 migraciones, 0 endpoints nuevos, 0 dependencias; el único cambio de comportamiento server-side es inclusión del propietario en payloads de opciones (D6), coherente con la validación existente. PASS.

## Project Structure

### Documentation (this feature)

```text
specs/016-project-collaborators/
├── plan.md                 # This file
├── research.md             # Phase 0 output
├── data-model.md           # Phase 1 output
├── quickstart.md           # Phase 1 output
├── contracts/
│   └── members-modal.md    # Phase 1 output
└── tasks.md                # Phase 2 output (NOT created here)
```

### Source Code (repository root)

```text
app/
├── Http/
│   └── Controllers/
│       ├── ProjectController.php      # editar: projectPayload añade openTasksCount (owner + members) vía query agregada
│       └── TaskController.php         # editar: projects[].members incluye al propietario (deduplicado)
resources/js/
├── Components/
│   └── Projects/
│       └── MembersModal.vue           # nuevo: modal Gestión de colaboradores (patrón SprintFormModal)
├── pages/
│   └── Projects/
│       └── Show.vue                   # editar: eliminar formulario inline (:724-752) y removeMember directo;
                                        #        botón "Gestionar colaboradores" (isOwner) + estado modal
└── types/
    └── project.ts                     # editar: owner/members ganan openTasksCount; tipar props del modal
tests/
└── Feature/
    └── Projects/
        ├── ProjectMembersModalPayloadTest.php   # nuevo: payload owner+members+openTasksCount correcto
        └── ProjectAssigneeOptionsTest.php       # nuevo: propietario en opciones de asignación; externo rechazado (regresión)
```

**Structure Decision**: estructura monolítica existente (Option 1). Modal en `Components/Projects/` junto a `ProjectFormModal.vue`; endpoints intactos en `ProjectMemberController` (sin tocar). La confirmación de eliminación usa `window.confirm` (precedente sprints `Show.vue:197-207`); las secciones deshabilitadas replican el patrón `SprintFormModal.vue:193-220` (`aria-disabled` + lock + `UiBadge tone="outline"`).

## Complexity Tracking

> Sin violaciones del Principio V en esta feature: no hay migraciones, endpoints nuevos, dependencias ni abstracciones; las capacidades no implementadas se dejan explícitamente deshabilitadas por instrucción del spec (FR-007) en lugar de construirse parcialmente.
