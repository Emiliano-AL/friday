# Data Model: Rediseño del CRUD de Proyectos (008)

**Sin cambios de esquema.** Esta feature consume las entidades existentes y añade únicamente _campos derivados de lectura_ (calculados, no persistidos) y propiedades futuras documentadas. Fuente de verdad: migraciones `create_projects_table`, `create_project_user_table`, `create_sprints_table`, `create_tasks_table` y modelos en `app/Models/`.

## Entidades existentes (reutilizadas)

### Project

| Campo                      | Tipo                 | Notas                                    |
| -------------------------- | -------------------- | ---------------------------------------- |
| `id`                       | int                  |                                          |
| `owner_id`                 | FK → users           | "Líder de Proyecto" en UI                |
| `title`                    | string(255)          | obligatorio                              |
| `description`              | string\|null         | opcional                                 |
| `status`                   | enum `ProjectStatus` | `Active` / `Archived` / `Completed`      |
| `created_at`, `updated_at` | datetime             | "Recientes" ordena por `updated_at` desc |

Relaciones: `owner` BelongsTo(User), `members` BelongsToMany(User vía `project_user`; **excluye al líder**), `sprints` HasMany, `tasks` HasMany.

### Sprint

| Campo        | Tipo                    | Notas |
| ------------ | ----------------------- | ----- |
| `id`         | int                     |       |
| `project_id` | FK → projects (cascade) |       |
| `name`       | string                  |       |
| `start_date` | date                    |       |
| `end_date`   | date                    |       |

Sin estado de dominio. Relación con tareas vive en `Task::sprint()`.

### Task

Estados (`TaskStatus`): `Backlog`, `Todo`, `InProgress`, `InReview`, `Done`. Para esta feature solo importa `Done` (progreso) y `Backlog` (banner del detalle).

### User

`name`, `email`, `avatar` (string\|null, OAuth Google) — el avatar alimenta tarjetas, franja de metadatos y `AvatarStack`.

## Campos derivados de lectura (NO persistidos)

### Progreso del proyecto — `Project::progressPercentage(): int`

- Fórmula: `round(tareas Done / tareas totales * 100)`.
- `0` cuando no hay tareas. Rango garantizado 0–100.
- Se acompaña en los payloads de `taskDoneCount` / `taskTotalCount` para renderizar el conteo "(24/35)".

### Sprint activo — `Project::activeSprint(): HasOne|null`

- Heurística: sprint con mayor `start_date` tal que `start_date <= hoy` (`orderByDesc('start_date')`).
- Eager-loadable (`with('activeSprint')`) para el directorio; `null` cuando ningún sprint ha iniciado → la UI muestra "Sin sprint activo".

### Contadores del directorio (payload `counts`)

- `active`, `completed`, `archived`: conteo de proyectos del usuario por estado, sobre el universo completo (independiente de búsqueda/filtro).

### KPIs (payload `kpis`)

- `activeProjects`: proyectos del usuario en estado `Active`.
- `completedSprintsThisQuarter`: sprints de los proyectos del usuario con `end_date < hoy` dentro del trimestre calendario actual.

## Máquina de estados (existente, sin cambios)

```
Active ──→ Archived
Active ──→ Completed
Archived ──→ Active
Completed ──→ Active
```

- Reglas: transiciones solo por el **líder** (owner); proyectos `Archived`/`Completed` son de solo lectura en contenido; reactivación devuelve a `Active`.
- Errores: transición ilegal → redirect con error (`status`); estado inválido → 422.

## Validación (existente, sin cambios)

- `StoreProjectRequest` / `UpdateProjectRequest`: `title` required string max:255; `description` nullable string. Autorización: usuario autenticado; ownership en controller/policy (404 fuera).

## Propiedades FUTURAS documentadas (sin persistencia en 008)

Pendientes para una feature posterior con su migración; en 008 aparecen solo como controles deshabilitados con "Próximamente":

| Propiedad                   | Tipo propuesto          | Reglas propuestas                                                   | Uso en UI (referencia)                                                                    |
| --------------------------- | ----------------------- | ------------------------------------------------------------------- | ----------------------------------------------------------------------------------------- |
| `key` (clave)               | string(4) uppercase     | único por workspace; autogenerable desde título                     | chip "PAY" en tarjeta/detalle, prefijo de tareas "PAY-101", búsqueda "por nombre o clave" |
| `accent_color`              | enum de 7 valores       | paleta fija: índigo, azul, esmeralda, ámbar, rosa, violeta, pizarra | barra superior/borde de tarjeta, barra de progreso, chips                                 |
| `icon`                      | enum (Material Symbols) | conjunto fijo curado                                                | icono identidad 40×40 tarjeta / 48×48 detalle                                             |
| `due_date` (fecha objetivo) | date                    | ≥ start del proyecto                                                | fila meta "15 Dic 2025", métrica "Fecha Límite"                                           |
| `priority`                  | enum (baja/media/alta)  | —                                                                   | insignia "Alta Prioridad" del detalle                                                     |
| `area/team`                 | string\|null            | libre o catálogo                                                    | subtítulo "Core Platform" de tarjeta                                                      |

## Secciones futuras del detalle (documentadas)

- **Documentación & Specs**: entidad `Document` propuesta (título, categoría, autor, timestamps, relación a project).
- **Recursos & Enlaces**: entidad `ProjectLink` (título, URL, tipo, relación a project).
- **Historial & Auditoría**: feed de eventos de dominio (tarea completada, sprint iniciado, etc.) — requiere emisión/registro de eventos.
- **Compartir / gestión visual de miembros**: la capacidad de datos ya existe (`projects.members.*`); la UI dedicada se propone para esta feature futura.
- **Métricas de salud**: velocidad, puntualidad de entregas — requieren motor de métricas sobre sprints/tareas cerradas.
- **Estado de sprint**: enum `SprintStatus` propuesto (planificado/activo/completado) para reemplazar la heurística de fechas cuando exista ciclo de vida real.
