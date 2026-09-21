# Data Model: Gestión de Proyectos

**Feature**: 002-project-management | **Date**: 2026-09-20
**Base**: `users` existente (feature 001, con columnas OAuth). Aditivo.

## Entity: Project (nuevo)

Unidad de trabajo multidisciplinaria. Pertenece a un propietario y tiene
cero o más colaboradores.

| Campo                       | Tipo                          | Reglas                                                                                                |
| --------------------------- | ----------------------------- | ----------------------------------------------------------------------------------------------------- |
| `id`                        | bigint PK                     | —                                                                                                     |
| `owner_id`                  | FK → `users.id` (cascade)     | requerido; el creador queda fijado en el alta y no es editable                                        |
| `title`                     | string(255)                   | requerido, max 255 (FR-001); no vacío tras `trim`                                                     |
| `description`               | text nullable                 | opcional (FR-001/004)                                                                                 |
| `status`                    | string (cast `ProjectStatus`) | requerido, default `active`; únicos valores: `active`, `archived`, `completed` (FR-005, Principio IV) |
| `created_at` / `updated_at` | timestamps                    | —                                                                                                     |

**Índices**: `index(owner_id)`. (Búsqueda por membresía cubierta por la
unicidad del pivote + FK.)

**Relaciones**:

- `owner(): BelongsTo` → User (propietario)
- `members(): BelongsToMany` → User vía `project_user` (colaboradores, sin el
  propietario)
- (futura) `tasks(): HasMany` → la define el módulo de tareas

**Reglas de dominio**:

- `progressPercentage(): int` — punto único del cálculo (research R-1):
  devuelve `0` mientras no exista el módulo de tareas; cuando aterrice, la
  fórmula es `round(done / total * 100)` con `total = 0 ⇒ 0`, siempre entre 0
  y 100 (FR-006).
- `isOwnedBy(User $user): bool`, `isMember(User $user): bool` — helperes usados
  por la Policy y las vistas.
- Borrado del proyecto → cascade a `project_user` (FR-011).

## Entity: ProjectStatus (nuevo enum)

Backed enum de PHP 8.4 (Principio IV — estados del dominio como enums).

```php
enum ProjectStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
    case Completed = 'completed';

    /** @return array<int, ProjectStatus> */
    public function transitions(): array { ... }
}
```

**Transiciones permitidas** (FR-005): `Active ⇒ [Archived, Completed]`,
`Archived ⇒ [Active]`, `Completed ⇒ [Active]`. Cualquier otra transición es
rechazada con error de validación (redirect a ficha con mensaje, 422 en la
respuesta de formulario).

## Entity: project_user (pivote de membresía, nuevo)

| Campo                       | Tipo                         | Reglas    |
| --------------------------- | ---------------------------- | --------- |
| `project_id`                | FK → `projects.id` (cascade) | requerido |
| `user_id`                   | FK → `users.id` (cascade)    | requerido |
| `created_at` / `updated_at` | timestamps                   | —         |

**Unicidad**: único compuesto `(project_id, user_id)` — sin membresías
duplicadas (FR-010, edge case). El propietario **no** tiene fila aquí: su
pertenencia es `projects.owner_id` (research R-2).

## Entity: User (existente, solo relaciones nuevas)

- `ownedProjects(): HasMany` → Project (donde es propietario)
- `memberProjects(): BelongsToMany` → Project vía `project_user` (donde es
  colaborador)
- Acceso combinado para listados: owned ∪ memberProjects (FR-002).

## Validation rules (Form Requests)

- **Store/Update project**: `title` required string max:255; `description`
  nullable string.
- **Transition**: `status` required, valor del enum, y destino ∈
  `current->transitions()`.
- **Add member**: `email` required email, debe existir en `users` y no ser ya
  miembro (ni propietario) del proyecto (FR-010).
- **Remove member**: el usuario objetivo debe ser colaborador actual; el
  propietario no puede retirarse a sí mismo (FR-008).

## Política de borrado

- Proyecto: hard delete por el propietario con confirmación en UI; disuelve
  el pivote por cascade (FR-011). Las tareas futuras definirán su política.
- Membresía: eliminar fila del pivote (retirar colaborador).
