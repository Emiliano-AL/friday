# Data Model: Gestión de Sprints (CRUD y fechas)

**Feature**: 003-sprint-crud | **Date**: 2026-09-21
**Base**: `projects` (feature 002, con `ProjectStatus` y pivote `project_user`). Aditivo.

## Entity: Sprint (nuevo)

Periodo de trabajo planificado dentro de un proyecto. Sin estado propio,
métricas ni tareas en esta iteración (spec, Key Entities; research R-3).

| Campo                       | Tipo                         | Reglas                                                                               |
| --------------------------- | ---------------------------- | ------------------------------------------------------------------------------------ |
| `id`                        | bigint PK                    | —                                                                                    |
| `project_id`                | FK → `projects.id` (cascade) | requerido; pertenencia obligatoria (FR-001)                                          |
| `name`                      | string(255)                  | requerido, max 255 (FR-001); no vacío tras `trim`                                    |
| `start_date`                | date                         | requerida; pasado válido (FR-009)                                                    |
| `end_date`                  | date                         | requerida; `>= start_date` (FR-002); pasado válido y solapamiento permitido (FR-009) |
| `created_at` / `updated_at` | timestamps                   | —                                                                                    |

**Índices**: `index(['project_id', 'start_date'])` — listado por proyecto
ordenado (FR-003, SC-002; research R-9).

**Relaciones**:

- `project(): BelongsTo` → Project
- (desde Project) `sprints(): HasMany` → Sprint, **sin orden global**; el orden
  `start_date` asc se aplica al construir el payload de la ficha.

**Reglas de dominio**:

- Número ilimitado de sprints por proyecto (FR-008): sin constraint ni
  contador.
- Sin restricción de solapamiento ni de fechas pasadas (FR-009).
- El sprint se gestiona solo mientras el proyecto está `active` (FR-006): la
  regla vive en `SprintPolicy` + chequeo del controlador (research R-4); el
  sprint no la duplica en el modelo.
- Borrado del proyecto → cascade a sprints (FK). Borrado del sprint → hard
  delete; el proyecto y sus demás sprints quedan intactos (FR-005).

## Entity: Project (existente, cambios mínimos)

- `sprints(): HasMany` → Sprint (nueva relación).
- Payload de `Projects/Show` gana `sprints: [{id, name, startDate, endDate}]`
  ordenados por `startDate` ascendente (FR-003; research R-6/R-9). Las banderas
  ya existentes (`isOwner`, `status`) gobiernan la UI de la sección (research
  R-7).
- `ProjectStatus` se reutiliza tal cual: `isActive()` es la llave del bloqueo
  de escritura de sprints (FR-006).

## Validation rules (Form Requests)

- **Store/Update sprint**: `name` required string max:255; `start_date`
  required `date`; `end_date` required `date` + `after_or_equal:start_date`
  (FR-002; mensaje asociado a `end_date`).
- Sin reglas de unicidad ni de solapamiento (FR-008/FR-009).

## Política de borrado

- Sprint: hard delete por el propietario con confirmación en UI (FR-005);
  efecto limitado al sprint.
- Proyecto: su borrado disuelve los sprints por cascade (mismo contrato que el
  pivote `project_user`).
