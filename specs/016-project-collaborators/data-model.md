# Data Model: Gestión de colaboradores del proyecto

**Feature**: `specs/016-project-collaborators`
**Date**: 2026-10-05

**Sin cambios de esquema** (research.md D7). La feature opera sobre el modelo existente y añade únicamente datos derivados al payload.

## Entidades existentes (relevantes)

### Proyecto
- Un propietario (`owner_id` → Usuario) y cero o más colaboradores (pivot `project_user`, sin rol).
- `isMember(User)` = propietario OR pertenece al pivot. `isOwnedBy(User)` = propietario.
- Regla de negocio: solo el propietario gestiona miembros (`manageMembers`).

### Usuario
- Puede ser: propietario de proyectos, colaborador (vía pivot) y responsable de tareas (`tasks.assignee_id`).
- Campos usados por el modal: `id`, `name`, `email`, `avatar`.

### Membresía (pivot `project_user`)
- Solo `project_id` + `user_id` + timestamps. **Sin rol ni permisos** (por eso el selector de roles del diseño queda deshabilitado).
- Invariantes: única por (proyecto, usuario); el propietario nunca está en el pivot (se detecta como duplicado si se intenta agregar).

### Tarea
- `assignee_id` nullable → usuario responsable; `project_id` nullable → proyecto al que pertenece.
- Al eliminar un colaborador del proyecto, sus tareas de ese proyecto quedan con `assignee_id = null` (FK `nullOnDelete`) — el modal lo advierte usando el conteo derivado `openTasksCount`.

## Reglas de validación (existentes, a conservar)

| Regla | Dónde | Mensaje (es) |
| --- | --- | --- |
| Agregar miembro: correo requerido, válido y de usuario registrado | `POST projects/{project}/members` | validación + "no encontrado" |
| Agregar miembro: no duplicados (incl. propietario) | mismo endpoint | "Este usuario ya es miembro del proyecto." |
| Eliminar: solo propietario; nunca el propietario a sí mismo; solo miembros del pivot | `DELETE projects/{project}/members/{user}` | 404 |
| Responsable de tarea de proyecto: debe ser colaborador (propietario incluido) | `StoreTaskRequest` / `UpdateTaskRequest` / `SaveTaskRequest` | "El responsable debe ser miembro del proyecto." |
| Tarea sin proyecto: responsable obligatorio = usuario actual | `SaveTaskRequest` | mensajes existentes |

## Dato derivado nuevo (sin persistencia)

### `openTasksCount` (por colaborador, por proyecto)
- Definición: número de tareas del proyecto con `assignee_id = colaborador` y `status != Done`.
- Cálculo: una consulta agregada por proyecto (`group by assignee_id`), mapeada sobre propietario y miembros en `ProjectController::projectPayload`.
- Uso: advertencia previa al eliminar ("N tareas quedarán sin responsable") y respaldo de SC-003/SC-004.

## Transición relevante

**Eliminar colaborador** (única transición con efectos):
1. Se confirma en el modal (con advertencia si `openTasksCount > 0`).
2. `DELETE` elimina la fila del pivot (solo el propietario puede, nunca el propietario mismo).
3. Las tareas del proyecto asignadas a esa persona quedan `assignee_id = null` (por la FK).
4. El redirect devuelve el proyecto con el payload actualizado (lista del modal + sección Equipo).
