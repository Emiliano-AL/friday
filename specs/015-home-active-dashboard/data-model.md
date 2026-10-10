# Data Model: Panel de inicio con actividad del usuario

**Feature**: `specs/015-home-active-dashboard`
**Date**: 2026-10-03

Entidades del dominio existente que alimentan el panel, más los cambios de esquema que la feature introduce. Convenciones del proyecto: claves primarias bigIncrements, enums string-backed en `app/Enums`, timestamps en todo modelo.

## Cambios de esquema (nuevos)

### `activities` (nueva tabla)

Evento de dominio generado por la actividad real de los usuarios. Fuente única del feed "Actividad del equipo" y del acumulado del burndown.

| Columna | Tipo | Reglas |
| --- | --- | --- |
| `id` | bigIncrements | PK |
| `user_id` | FK → users, cascade | quién (obligatorio) |
| `project_id` | FK → projects, cascade | ámbito obligatorio: todo evento pertenece a un proyecto |
| `task_id` | FK → tasks, `nullOnDelete` | referencia opcional (eventos de tarea/comentario) |
| `sprint_id` | FK → sprints, `nullOnDelete` | referencia opcional (eventos de sprint / burndown) |
| `type` | string(50) | valor de `ActivityType` (enum, índice) |
| `created_at` / `updated_at` | timestamps | |

**Índices**: `(project_id, created_at)`, `(user_id, created_at)`, `(sprint_id, type, created_at)`.

**Reglas de negocio**:
- Se crea únicamente desde observers de Eloquent (nunca en controladores) para garantizar que todo cambio de dominio quede registrado igual venga por web.
- `type` se persiste como enum string-backed `ActivityType` (Principio IV).
- Los eventos se insertan en la transacción del cambio que los origina (misma request → coherencia feed/comentarios).

#### `ActivityType` (nuevo enum, `app/Enums`)

| Case | Valor | label() (es) |
| --- | --- | --- |
| `TaskCreated` | `task_created` | creó la tarea |
| `TaskCompleted` | `task_completed` | completó la tarea |
| `CommentCreated` | `comment_created` | comentó en |
| `SprintStarted` | `sprint_started` | inició el sprint |
| `SprintCompleted` | `sprint_completed` | completó el sprint |

### `tasks` (columna nueva)

| Columna | Tipo | Reglas |
| --- | --- | --- |
| `completed_at` | timestamp nullable | se establece al pasar a `TaskStatus::Done`; vuelve a `null` si la tarea se reabre |

**Índice nuevo**: `(assignee_id, status)` — la consulta "mis tareas activas" filtra exactamente por ambas columnas.

## Entidades existentes (relevantes para el panel)

### User
- Atributos usados: `id`, `name` (primer nombre para el saludo), `avatar`, `email`.
- Relaciones usadas: `memberProjects()` (BelongsToMany vía `project_user`), `ownedProjects()`.
- Sin cambios.

### Project
- Atributos: `id`, `title`, `description`, `status` (`ProjectStatus`), timestamps.
- Relaciones: `owner()`, `members()` (owner NO está en el pivot), `sprints()`, `tasks()`, `activeSprint()` (HasOne `SprintStatus::Active`, `orderByDesc('start_date')`).
- Helpers existentes reutilizados: `isMember(User)`, `progressPercentage()` (done/total, redondeado).
- "Proyectos del usuario" (repite patrón canónico): `where('owner_id', $u)->orWhereHas('members', ...)`.

### Sprint
- Atributos: `id`, `name`, `goal` (nullable), `status` (`SprintStatus`: Planned → Active → Completed, forward-only), `start_date`/`end_date` (date).
- Relaciones: `project()`, `tasks()`.
- Sin cambios; el burndown deriva sus puntos de `activities` (type=`task_completed`, `sprint_id`).

### Task
- Atributos: `id`, `title`, `description`, `type` (`TaskType`), `priority` (`TaskPriority`: Low/Medium/High/Urgent), `status` (`TaskStatus`: Backlog/Todo/InProgress/InReview/Done), `created_at`, `completed_at` (nuevo).
- Relaciones: `project()` (nullable), `sprint()` (nullable), `assignee()` (nullable), `comments()`.

### Comment
- Atributos: `id`, `body`, timestamps.
- Relaciones: `task()`, `author()`.

## Transiciones de estado y efectos (reglas de la feature)

1. **Task → status cambia a `Done`** ⇒ `completed_at = now()` + activity `(TaskCompleted, user=actor, project, task, sprint?)`.
2. **Task → status sale de `Done`** ⇒ `completed_at = null` (sin activity: es una reapertura, no un evento de feed).
3. **Task creada** ⇒ activity `(TaskCreated, user=creador, project?, task)` — solo si tiene proyecto (las standalone no alimentan feed de proyecto; el spec define el feed por proyectos del usuario).
4. **Comment creado** ⇒ activity `(CommentCreated, user=autor, project de la tarea, task)`.
5. **Sprint start() exitoso** ⇒ activity `(SprintStarted, user=actor, project, sprint)`.
6. **Sprint complete() exitoso** ⇒ activity `(SprintCompleted, user=actor, project, sprint)`.
7. El actor es siempre el usuario autenticado de la request (observers usan `Auth::user()`; en tests, el acting user).

## Gate "tiene actividad" (FR-001/FR-002)

```text
existe Project donde (owner_id = user OR members contiene user)
   O existe Task donde assignee_id = user
```
⇒ `dashboard` prop presente; si no ⇒ `dashboard = null` y `AppHome` renderiza `HomeCanvas` (sin cambios).

## Volumen y límites de lectura (FRs de acotación)

| Sección | Fuente | Filtro / orden | Límite |
| --- | --- | --- | --- |
| Tarjetas resumen | counts (sprint, tasks, projects) | — | — |
| Mis tareas | `tasks` assignee=user, status≠Done | priority (Urgent>High>Medium>Low), updated_at desc | 6 |
| Sprint activo | `activeSprint` de proyectos del usuario | start_date desc | 1 |
| Burndown | `activities` type=task_completed, sprint_id=sprint | por día natural start→end | duración del sprint |
| Proyectos activos | proyectos del usuario, status=Active | updated_at desc | 4 |
| Insight semanal | `activities` type=task_completed, user=user, semana ISO actual + `tasks.completed_at` del usuario | — | — |
| Feed actividad | `activities` de proyectos del usuario | created_at desc | 8 |
