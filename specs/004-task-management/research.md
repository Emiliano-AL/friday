# Research: Gestión de Tareas (Tasks)

**Feature**: 004-task-management | **Date**: 2026-09-23
**Spec**: [spec.md](./spec.md)

Phase 0. El stack está fijado por la constitución y las features 002–003 fijaron
las convenciones del dominio (exploración del código real y del módulo de
sprints implementado en esta misma base). No quedó ningún `NEEDS CLARIFICATION`
en el Technical Context; las decisiones de diseño se consolidan aquí con sus
alternativas evaluadas.

## R-1 — Enums de dominio (Principio IV): `TaskType`, `TaskPriority`, `TaskStatus`

- **Decision**: tres backed enums de string con `label()` en español, al estilo
  de `ProjectStatus`:
    - `TaskType`: `Bug = 'bug'`, `Feature = 'feature'`, `Test = 'test'`, `Other = 'other'` (default `Other`).
    - `TaskPriority`: `Low = 'low'`, `Medium = 'medium'` (default), `High = 'high'`, `Urgent = 'urgent'`.
    - `TaskStatus`: `Backlog = 'backlog'` (default/inicial), `Todo = 'todo'`, `InProgress = 'in_progress'`, `InReview = 'in_review'`, `Done = 'done'`.
- **Rationale**: la constitución exige estados del dominio como enums; la spec
  fija los valores por tarea. Las columnas se guardan como string con cast al
  enum (mismo patrón que `projects.status`). Movimiento libre entre estados
  (spec, Assumptions): sin método `transitions()`, el enum solo valida valores.
- **Alternativas**: enum de PHP único con tres "grupos" — rechazada (mezcla
  conceptos); strings sueltos — viola Principio IV.

## R-2 — Tablas: `tasks` y `comments` (semántica de borrado)

- **Decision** (`tasks`): `project_id` FK cascade (borrar proyecto borra sus
  tareas); `sprint_id` FK **nullable + `nullOnDelete`** (borrar un sprint
  deja sus tareas aisladas — no deben desaparecer); `assignee_id` FK **nullable
    - `nullOnDelete`** (borrar un usuario deja la tarea sin responsable);
      `title` string(255), `description` text nullable, `type`/`priority`/`status`
      string con default; timestamps; `index('project_id')`.
- **Decision** (`comments`): `task_id` FK **cascade** (FR-009: borrar la tarea
  suprime sus comentarios); `user_id` FK cascade (autor); `body` text;
  timestamps; `index('task_id')`.
- **Rationale**: cada relación responde a una regla de la spec; el borrado de
  sprint nunca debe destruir trabajo (tareas), y los comentarios sin tarea no
  tienen sentido.
- **Alternativas**: `restrictOnDelete` en sprint_id — rechazada (impediría
  borrar sprints con tareas, comportamiento no pedido); cascade en assignee —
  rechazada (borrar un usuario no debería borrar tareas).

## R-3 — Autorización y contrato de errores (FR-010/FR-011)

- **Decision**:
    - Lectura (ver proyecto y sus tareas/comentarios): miembro; no miembro → **404**.
    - Escritura (crear/editar/estado/comentar): **cualquier miembro** y proyecto
      activo; fuera de activo → redirect de vuelta con `errors.project` =
      "El proyecto no admite cambios en su estado actual." (mismo mensaje
      verbatim que sprints, research R-4 de 003).
    - Eliminar: **solo propietario** y activo; colaborador que intenta eliminar → **404**.
    - Tarea o comentario de otro proyecto (URL cruzada) → **404** (verificación
      `task.project_id === project.id` en el controlador, precedente
      `ProjectMemberController`/`ProjectSprintController`).
    - `TaskPolicy`: `viewAny`/`view` = `isMember`; `create`/`update` = `isMember &&
active`; `delete` = `isOwnedBy && active`.
- **Rationale**: la spec (Assumptions) hace colaborativa la gestión de tareas a
  diferencia de sprints; la destrucción queda solo en propietario, coherente
  con proyecto/sprints. Se reutiliza el patrón de controlador ya validado:
  `abort_unless` + helper `ensureProjectIsActive` + `authorize`.
- **Alternativas**: escritura solo propietario — rechazada (contradice la
  assumption de colaboración); eliminación por autor de la tarea — rechazada
  (rompe la convención de acciones destructivas).

## R-4 — Rutas anidadas explícitas (sin resource, sin GET de detalle)

- **Decision** (grupo `auth`, estilo members/sprints):
    - `POST projects/{project}/tasks` → `projects.tasks.store`
    - `PUT projects/{project}/tasks/{task}` → `projects.tasks.update`
    - `DELETE projects/{project}/tasks/{task}` → `projects.tasks.destroy`
    - `POST projects/{project}/tasks/{task}/comments` → `projects.tasks.comments.store`
- **Rationale**: la gestión ocurre dentro de la vista del proyecto (spec,
  Assumptions); no hay páginas de detalle ni listado propio. Los comentarios se
  crean con la misma forma anidada; su lectura viaja en el payload (R-6), así
  que no hace falta ruta GET.
- **Alternativas**: `Route::resource` — rechazada (rutas muertas); ruta GET de
  comentarios — rechazada (payload ya los incluye, YAGNI).

## R-5 — Comentarios en el payload de la ficha (sin endpoint de lectura)

- **Decision**: cada tarea del payload `project.tasks` incluye
  `comments: [{ id, body, author: { id, name }, createdAt }]` ordenados
  cronológicamente asc, cargados con eager loading
  (`loadMissing('tasks.assignee', 'tasks.sprint', 'tasks.comments.author')`).
- **Rationale**: SC-002 (100 tareas < 2 s) se mantiene con índice +
  eager loading; evita una ruta y una petición extra por apertura de
  comentarios. Si en el futuro el volumen de comentarios lo justifica, se
  puede paginar por tarea sin cambio de contrato en escritura.
- **Alternativas**: fetch bajo demanda por modal — rechazada (añade ruta de
  lectura y latencia por apertura para un MVP).

## R-6 — Form Requests y defaults (FR-004/FR-006)

- **Decision**: `StoreTaskRequest`/`UpdateTaskRequest` (reglas idénticas salvo
  estado): `title` required string max:255; `description` nullable string;
  `type` y `priority` con `Rule::enum(...)` en modo `sometimes` (el controlador
  fusiona los defaults `Other`/`Medium` cuando no vienen); `assignee_id`
  nullable + exists users + regla a medida de membresía del proyecto;
  `sprint_id` nullable + exists sprints + regla a medida de pertenencia al
  proyecto. `UpdateTaskRequest` añade `status` con `Rule::enum(TaskStatus::class)`
  (movimiento libre, sin whitelist de transiciones). `StoreCommentRequest`:
  `body` required string. Todos con `authorize(): $this->user() !== null` y
  mensajes en español (mismo patrón que los requests de sprints).
- **Rationale**: la spec fija defaults por omisión y validaciones cruzadas
  (responsable miembro, sprint del mismo proyecto); `sometimes`+defaults evita
  duplicar reglas y mantiene el contrato tolerante a formas parciales.
- **Alternativas**: defaults solo en frontend — rechazada (el servidor es
  fuente de verdad); status con máquina de transiciones — rechazada por la
  spec (movimiento libre en esta iteración).

## R-7 — UI: sección en `Projects/Show.vue` con componentes locales

- **Decision**: sección "Tareas" en la ficha del proyecto (spec, Assumptions),
  apoyada en tres componentes locales nuevos bajo `resources/js/Components/Tasks/`:
    - `TaskList.vue` — lista (título, badges de tipo/prioridad/estado,
      responsable, sprint) con acciones por fila (editar, comentarios,
      eliminar según permiso).
    - `TaskModal.vue` — formulario de alta/edición compartido (título,
      descripción, tipo, prioridad, estado en edición, responsable, sprint).
    - `TaskCommentsModal.vue` — hilo cronológico + formulario de comentario.
      La sección en `Show.vue` solo orquesta: `canWriteTasks =
project.status === 'active'` (miembro implícito: la página ya es 404 para no
      miembros), create form inline y render del `TaskList`.
- **Rationale**: `Show.vue` ya supera las 500 líneas con sprints+miembros;
  la superficie de tareas (dos modales + lista + formulario) justifica
  componentes locales sin crear páginas nuevas ni sección global (ambos fuera
  de la spec). Se reutilizan `TextInput`, `InputLabel`, `InputError`,
  `PrimaryButton`, `SecondaryButton`, `DangerButton`, `Modal`.
- **Alternativas**: todo inline en `Show.vue` (como sprints) — rechazada por
  tamaño resultante; página `/projects/{project}/tasks` separada — rechazada
  (la spec acota "dentro de la vista del proyecto" y sin sección global).

## R-8 — Payload y selects de la ficha

- **Decision**: `projectPayload` añade `tasks` ordenadas por `updated_at` desc
  (FR-005), cada una con `{ id, title, description, type, typeLabel, priority,
priorityLabel, status, statusLabel, assignee: {id, name} | null, sprint: {id,
name} | null, comments: [...] }`. Las opciones de los selects ya existen en
  el payload: responsables = `owner` + `members`; sprints = `sprints`. Sin
  datos nuevos adicionales.
- **Rationale**: un solo punto de datos mantiene el contrato simple; los
  labels vienen del enum (`label()`), no se calculan en la vista.

## R-9 — Factorías y tests

- **Decision**: `TaskFactory` (title, nullable description, type/priority con
  estados `bug()`/`high()`/… o estados por enum, `for(Project)`, `for(Sprint)`
  nullable, assignee) y `CommentFactory`. Suites en `tests/Feature/Tasks/`:
    - `TaskCrudTest.php` — US1–US3 y US5: alta/lista/orden, validaciones
      (título, responsable ajeno, sprint de otro proyecto), edición, cambio de
      estado libre, vinculación/desvinculación de sprint, eliminación con
      cascada de comentarios.
    - `TaskCommentsTest.php` — US4: alta/listado cronológico, cuerpo vacío
      rechazado, autor correcto.
    - `TaskAccessTest.php` — US6: colaborador escribe, no elimina (404),
      no miembro 404, propietario bloqueado fuera de activo con
      `errors.project`, lectura visible fuera de activo.
- **Rationale**: espejo de `tests/Feature/Sprints/` y `Projects/`.
