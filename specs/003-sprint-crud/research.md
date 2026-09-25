# Research: Gestión de Sprints (CRUD y fechas)

**Feature**: 003-sprint-crud | **Date**: 2026-09-21
**Spec**: [spec.md](./spec.md)

Phase 0. El stack está fijado por la constitución y la feature 002 ya fijó las
convenciones del dominio (exploración del código real: modelos, policy,
controladores, rutas, tests). No quedó ningún `NEEDS CLARIFICATION` en el
Technical Context que requiera investigación externa; las decisiones de diseño
se consolidan aquí con sus alternativas evaluadas.

## R-1 — Columnas de fecha: `start_date` / `end_date` (tipo `date`)

- **Decision**: columnas `date` (no datetime) llamadas `start_date`/`end_date`.
- **Rationale**: la spec habla de "fechas de inicio/fin" (FR-001), no de
  instantes. En Laravel el sufijo `_at` implica timestamp y `_on` no es
  convención del ecosistema. Los inputs serán `type="date"` y la validación
  usa reglas `date`.
- **Alternativas consideradas**: `starts_at`/`ends_at` datetime (semántica
  incorrecta para fechas puras); `starts_on`/`ends_on` (convención Rails ajena
  al proyecto).

## R-2 — Nombre del sprint: columna `name`

- **Decision**: `name string(255)` — FR-001: "nombre obligatorio de hasta 255
  caracteres".
- **Alternativas consideradas**: `title` por simetría con `projects.title` —
  rechazada: la spec usa "nombre" de forma consistente y "sprint name" es el
  vocabulario del dominio; no merece forzar simetría.

## R-3 — Sin estado propio del sprint (sin enum en esta iteración)

- **Decision**: el sprint no tiene `status` (spec, Key Entities: "No tiene
  estado propio, métricas ni tareas asociadas en esta iteración").
- **Rationale**: Principio IV obliga a modelar estados del dominio como enums
  _cuando existen_. Como aquí no hay estado, crear un enum de caso único
  violaría YAGNI. La activación/cierre de sprints llegará con el módulo de
  sprint lifecycle del MVP, que introducirá `SprintStatus` con transiciones.
- **Alternativas consideradas**: enum `SprintStatus` de un solo caso — rechazada
  (complejidad sin requisito).

## R-4 — Contrato de autorización y mensaje de bloqueo (FR-006 / SC-003)

- **Decision**:
    - No propietario (colaborador o usuario ajeno) que escribe → **404**
      (`abort_unless($project->isOwnedBy($user), 404)`), indistinguible de
      inexistente (mismo contrato que `ProjectController`).
    - Propietario con proyecto **archivado/completado** → redirect de vuelta con
      `errors.project` = "El proyecto no admite cambios en su estado actual."
      (precedente: `ProjectController::status()` usa `back()->withErrors()` para
      violaciones de estado).
    - `SprintPolicy`: `viewAny`/`view` = `isMember`; `create`/`update`/`delete` =
      `isOwnedBy($user) && $project->status->isActive()` (red de seguridad; el
      controlador intercepta antes para dar el mensaje amable).
- **Rationale**: FR-006 exige "un mensaje que indique que el proyecto no admite
  cambios en su estado actual" y SC-003 "muestran un mensaje comprensible". Un
  403 de Policy renderiza la página de error genérica de Inertia sin el mensaje
  específico en producción. El redirect con errores comparte el mensaje por
  sesión y la ficha del proyecto lo muestra con el mismo componente de errores
  que ya usan los formularios.
- **Alternativas consideradas**: denegación pura por Policy → 403 sin mensaje
  visible (incumple FR-006/SC-003); vista de error 403 personalizada (amplía
  alcance, YAGNI).
- **Nota**: difiere del contrato 403 de `ProjectPolicy::update` porque la spec
  de sprints exige explícitamente el mensaje; `SprintAccessTest` asertará
  `assertSessionHasErrors('project')`.

## R-5 — Rutas anidadas explícitas (no `Route::resource`)

- **Decision**: tres rutas dentro del grupo `auth`, estilo members:
    - `POST projects/{project}/sprints` → `projects.sprints.store`
    - `PUT projects/{project}/sprints/{sprint}` → `projects.sprints.update`
    - `DELETE projects/{project}/sprints/{sprint}` → `projects.sprints.destroy`
- **Rationale**: la gestión ocurre dentro de la vista del proyecto (assumption
  de la spec), así que no existen páginas propias index/create/edit de sprints;
  solo hacen falta store/update/destroy. `Route::resource` generaría rutas
  muertas (YAGNI).
- **Pertenencia**: un sprint que no pertenece al proyecto de la URL se trata
  como inexistente → 404, verificado en el controlador (precedente:
  `ProjectMemberController::destroy` con `whereKey`).

## R-6 — Controlador `ProjectSprintController` (plano, 3 acciones)

- **Decision**: `app/Http/Controllers/ProjectSprintController.php` con
  `store`/`update`/`destroy` (convención de nombre del controlador de
  miembros).
- **Rationale**: sin `index` — la lista viaja en el payload de `Projects/Show`
  ordenada por `start_date` asc (FR-003); sin `show`/`create`/`edit` — la UI
  vive en la ficha del proyecto.
- **Alternativas consideradas**: `SprintController` con resource completo —
  rechazada por rutas y páginas muertas.

## R-7 — UI dentro de `Projects/Show.vue`

- **Decision**: sección "Sprints" en la ficha del proyecto: lista ordenada,
  formulario de alta inline (nombre + inicio + fin), edición en el componente
  `Modal` existente y baja con `window.confirm` (precedente en
  `Projects/Show.vue`). Las acciones de escritura solo se renderizan si
  `project.isOwner && project.status === 'active'` (FR-006/FR-007): proyecto
  archivado/completado → sección solo lectura; colaborador → nunca aparecen.
- **Rationale**: assumption explícita de la spec ("dentro de la vista del
  proyecto"). Se reutilizan `TextInput`, `InputLabel`, `InputError`,
  `PrimaryButton`, `SecondaryButton`, `DangerButton`, `Modal`.
- **Alternativas consideradas**: páginas `Sprints/Index|Create|Edit`
  independientes — rechazada (contradice la assumption y duplica navegación);
  extraer ya un componente `SprintManager` — aplazable sin cambio de contrato.

## R-8 — Validación de fechas y nombre

- **Decision**: `start_date`/`end_date` required + `date`; `end_date` con
  `after_or_equal:start_date` (FR-002; el error queda asociado a `end_date`).
  `name` required string max:255 (FR-001); `'   '` lo rechaza `required` +
  `ConvertEmptyStringsToNull` (mismo comportamiento que el título de proyecto).
  Fechas pasadas válidas y solapamiento permitido (FR-009): sin reglas
  adicionales ni constraints de BD.

## R-9 — Índice compuesto y carga

- **Decision**: `index(['project_id', 'start_date'])` en `sprints` — cubre el
  listado por proyecto ordenado (FR-003) y SC-002 (50 sprints < 2 s) con eager
  load en `Projects/Show`. La relación `Project::sprints()` no lleva orden
  global; el orden se aplica al construir el payload.

## R-10 — Borrado

- **Decision**: hard delete del sprint (FR-005; precedente: proyecto). FK
  `project_id` con `cascadeOnDelete`: borrar el proyecto disuelve sus sprints,
  igual que el pivote de miembros. En UI, la baja exige confirmación (US3
  escenario 2: sin confirmación no se elimina nada).

## R-11 — Factoría y tests

- **Decision**: `SprintFactory` (name, start_date, end_date = start + intervalo
  corto, `for(Project)`). Suites nuevas: `tests/Feature/Sprints/SprintCrudTest.php`
  (US1–US3: alta/lista/orden, validación, edición, eliminación, fechas pasadas
  y solapamiento) y `SprintAccessTest.php` (US4: solo lectura fuera de activo,
  colaborador solo lectura, no miembro → 404, mensaje de bloqueo FR-006).
- **Rationale**: espejo de `tests/Feature/Projects/`.
