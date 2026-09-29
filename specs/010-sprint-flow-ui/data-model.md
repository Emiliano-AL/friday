# Data Model: Flujo de Sprint en la UI (010)

## Entidades

### Sprint (existente, ampliado)

Periodo de trabajo de un proyecto. **Cambios de esta feature** marcados con ➕.

| Campo | Tipo | Reglas |
| --- | --- | --- |
| `id` | int | PK autoincremental (existente) |
| `project_id` | int | FK obligatoria a `projects` (existente) |
| `name` | string(255) | obligatorio, máx. 255 (existente) |
| `start_date` | date | obligatoria (existente) |
| `end_date` | date | obligatoria; **no puede ser anterior a `start_date`** (existente) |
| `goal` ➕ | text, nullable | opcional; máx. 1000 caracteres; texto libre corto |
| `status` ➕ | enum `SprintStatus` | `planned` por defecto; ver máquina de estados |
| timestamps | datetime | existentes |

**Relaciones**: `belongsTo(Project)`; `hasMany(Task)` (existente, `tasks.sprint_id`).

### SprintStatus (enum nuevo)

| Caso | Valor | Label UI | Color chip (referencia) |
| --- | --- | --- | --- |
| `Planned` | `planned` | Planificado | neutral/outline |
| `Active` | `active` | Activo | primary |
| `Completed` | `completed` | Completado | outline/success (verde sobrio del sistema) |

**Máquina de estados (solo hacia adelante)**:

```text
            start                 complete
planned  ──────────▶  active  ─────────────▶  completed
   │                                              │
   └──────────────────────────────────────────────┘
        cualquier otra transición → RECHAZADA
```

- `planned → active`: acción "Activar" (detalle) o creación con `start_now=true`.
- `active → completed`: acción "Completar Sprint" (detalle, con confirmación).
- Prohibidas: `completed → *` (reactivación), `active → planned`, saltos de dos pasos.
- Sprint `completed` = **solo lectura**: bloquea update/destroy/transiciones en servidor; la UI oculta las acciones.
- Transiciones exigen siempre: propietario del proyecto + proyecto activo.

### Tarea (existente, sin cambios)

Se lista dentro del detalle vía `sprint_id`. El desglose de métricas usa `TaskStatus`
(todo, in_progress, in_review, done). Las tareas del diálogo existente se crean/editan
desde el detalle con proyecto y sprint preseleccionados.

### Proyecto (existente, ajuste de derivación)

`activeSprint` pasa de derivación por fechas a: `hasOne(Sprint)` con
`status = active`, ordenado por `start_date` desc. Shape de API se mantiene y se
extiende (`SprintSummary` gana `status` y `goal`).

## Métricas derivadas (sin persistencia — se calculan en servidor)

Con `T` = tareas del sprint:

- `total` = count(T)
- `done` = count(T where status = done)
- `coverage` = total = 0 → 0; si no round(done/total × 100)
- `breakdown` = para cada estado en [todo, in_progress, in_review, done]: `{status, label, count, percent}` (percent sobre total; estado con count 0 se omite del desglose visible)
- `daysRemaining` = días laborables (lun–vie, sin festivos) de hoy a `end_date`, mínimo 0
- `percentElapsed` = (hoy − start_date)/(end_date − start_date) acotado 0–100; sprint de un día → 100 si hoy ≥ start_date, si no 0
- `iteration` = `{current: ordinal del sprint entre los sprints del proyecto ordenados por start_date asc, total: count(sprints del proyecto)}`

## Validaciones

- **Store**: `name` required string max:255 · `goal` nullable string max:1000 · `start_date` required date · `end_date` required date ≥ start_date · `start_now` boolean (default false).
- **Update**: mismas reglas con `sometimes`; rechazado si el sprint está `completed` ("El sprint ya está completado y es de solo lectura") o si el proyecto no está activo.
- **Start/Complete**: rechazado si el sprint no está en el estado origen esperado, si el proyecto no está activo o si quien pide no es propietario (mensajes claros; 404/422 según el caso, coherente con el dominio).

## Propiedades FUTURAS documentadas (sin persistencia, UI "Próximamente")

- Story points de tarea y métricas derivadas: burn-up, velocidad, ritmo, capacidad, carga por miembro.
- Selección de tareas del backlog al crear el sprint.
- Gráfica de burndown (línea ideal vs. real).
- Notas de daily y bitácora/actividad del sprint.
- Estado `blocked` de tarea (grupo "Bloqueadas / Requiere Atención") y motivo de bloqueo.
- Clave legible de tarea (prefijo de proyecto + folio) y % de progreso individual.
- Vista transversal "Sprints" (item del sidebar global).
- Soporte de festivos en días laborables.
