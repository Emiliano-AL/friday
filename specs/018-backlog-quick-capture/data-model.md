# Data Model: Backlog General — Captura Rápida de Pendientes

**Fecha**: 2026-10-08 | **Feature**: [spec.md](spec.md) | **Research**: [research.md](research.md)

## Entidades existentes (sin cambios estructurales)

### Project
- Representa un proyecto del workspace. Sin cambios en esta feature.
- Relaciones relevantes: `owner()` BelongsTo User, `members()` BelongsToMany
  User (pivot `project_user`), `sprints()` HasMany, `tasks()` HasMany.
- Reglas usadas: `isMember(User)` = propietario o miembro del pivot; `isActive()`
  para permitir mutaciones.

### Sprint
- Iteración temporal de un proyecto (siempre con proyecto).
- Campos: `project_id`, `name`, `goal`, `status` (`SprintStatus`), `start_date`,
  `end_date`.
- Transiciones (forward-only, existentes): `Planned → Active → Completed`.

### Task (cambios)
Pendiente/tarea capturable en el backlog. Se extiende la tabla `tasks`.

#### Columnas nuevas (migración `2026_10_08_000000_add_backlog_fields_to_tasks_table`)

| Columna | Tipo | Reglas | Propósito |
| --- | --- | --- | --- |
| `author_id` | FK → users, indexado | nullable en BD; backfill `COALESCE(assignee_id, project.owner_id)` | Autoría de la captura; gobierna la bandeja personal (FR-016, FR-013) |
| `code` | string(16) | nullable; único compuesto `(author_id, code)` | Identificador legible `FRN-###` (FR-005) |
| `points` | unsigned smallint | nullable; validación `integer`, `min:0`, `max:99` | Puntos del pendiente, sumados en el encabezado (FR-009) |

#### Columnas existentes relevantes
`project_id` (nullable), `sprint_id` (nullable), `assignee_id` (nullable),
`title`, `description` (texto plano, max 50000), `type` (`TaskType`,
default `other`), `priority` (`TaskPriority`, default `medium`), `status`
(`TaskStatus`), `completed_at`.

#### Relaciones nuevas
- `author()` BelongsTo User (via `author_id`).

#### Casts nuevos
- `points` → `integer`.

#### Reglas de validación (ajustes)
- `SaveTaskRequest` (global):
  - `project_id`: `nullable|exists:projects,id` (sin cambio).
  - `assignee_id`: con proyecto → miembro del proyecto (sin cambio); sin
    proyecto → **opcional** y, si se envía, debe ser el propio usuario (regla
    actual mantenida). Antes era obligatorio sin proyecto.
  - `points`: añadir `sometimes|integer|min:0|max:99`.
  - `status`: permitir `TaskStatus::Backlog` explícito en captura (el enum ya lo
    soporta; `TaskController@store` deja de forzar `todo` cuando la petición
    declara `backlog`).
- `UpdateTaskRequest` (project-scoped): añadir `points` con la misma regla.
- `title`: `required|max:255` (sin cambio; rechaza vacío/espacios — edge case).

#### Scopes nuevos (Eloquent)
- `scopeVisibleTo(Builder $q, User $user)`: autor O responsable O
  (propietario/miembro del proyecto asociado).
  ```php
  $q->where(function ($inner) use ($user) {
      $inner->where('author_id', $user->id)
          ->orWhere('assignee_id', $user->id)
          ->orWhereHas('project', fn ($p) => $p->where('owner_id', $user->id)
              ->orWhereHas('members', fn ($m) => $m->whereKey($user->id)));
  });
  ```
- `scopeBacklog(Builder $q)`: `whereNull('sprint_id')->where('status', '!=',
  TaskStatus::Done)` (pendiente sin planificar — D6).

#### Generación de `code` (TaskObserver::created)
- Dentro de la transacción de creación: `n = max(número de códigos FRN-{n} del
  author_id) + 1`; `code = sprintf('FRN-%d', n)`.
- Resolución de carrera: `INSERT ... ON CONFLICT (author_id, code)` no aplica a
  un UPDATE post-insert; estrategia: reintento con el siguiente número ante
  violación de unique compuesto (try/catch en el observer sobre el update del
  código). En la práctica la ventana es una sola request por usuario; el unique
  compuesto es la salvaguarda.

#### Transiciones de estado relevantes
- Captura rápida → `status = TaskStatus::Backlog`, `sprint_id = null`,
  `project_id` según composer (null por defecto), `assignee_id = null`,
  `type = other`, `priority` según composer (`medium` por defecto).
- Triage (assignación a sprint, FR-012): `sprint_id` pasa a un sprint
  `Active`/`Planned` del mismo proyecto → sale del backlog (scope D6).
- Rollover de sprint (D8): al completar un sprint, tareas del sprint con
  `status != Done` → `sprint_id = null` (vuelven al backlog). `completed_at` no
  se toca (no están completadas). Se implementa como batch update en
  `SprintObserver` al detectar transición a `Completed`.

#### Políticas (TaskPolicy)
- `view` / `update`, tarea sin proyecto: `author_id === user || assignee_id ===
  user` (antes solo responsable).
- `delete`, tarea sin proyecto: `author_id === user || assignee_id === user`.
- Resto de reglas sin cambio (con proyecto: miembro + activo; delete:
  propietario + activo).

## Reglas de negocio consolidadas

| Regla | Fuente |
| --- | --- |
| Un pendiente puede existir sin proyecto y sin responsable | FR-003, FR-006 |
| Un pendiente sin proyecto es visible solo para autor y responsable | FR-016 |
| Asignar a proyecto exige membresía y proyecta la visibilidad del proyecto | FR-011 |
| Definir entrega exige proyecto previo y sprint del mismo proyecto | US4 esc. 2, FR-012 |
| Cerrar un sprint devuelve sus pendientes al backlog | US4 esc. 3 |
| El encabezado resume pendientes y suma de puntos | FR-009 |
| Filtros: Todas / Sin proyecto / Con proyecto con conteos exactos | FR-008, SC-003 |
| Doble envío rápido no duplica | Edge case (garantizado en UI con `form.processing`; el servidor sigue siendo idempotente por naturaleza: cada POST crea, la UI bloquea) |

## Payload Inertia (`BacklogController@index`)

- `tasks: BacklogTask[]` — `{ id, code, title, description, type, priority,
  status, points, completedAt, createdAt, project: {id,title}|null, sprint:
  {id,name,status}|null, assignee: {id,name,avatar}|null, authorId, canUpdate,
  canDelete }`.
- `summary: { total, totalPoints, withoutProject, withProject }`.
- `nextSprint: { id, name, projectId, projectTitle, status, startsAt, endsAt } |
  null` — sprint activo o planificado más próximo (por `start_date`) entre los
  proyectos del usuario.
- `projects: TaskProjectOption[]` — reutiliza la forma ya entregada por
  `TaskController@index` (id, título, miembros, sprints) para el composer y el
  triage.
