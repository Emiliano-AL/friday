# Contract: Superficie web de proyectos

**Feature**: 002-project-management | **Date**: 2026-09-20
**Modelo**: [data-model.md](./data-model.md) · **Decisiones**: [research.md](./research.md)

Contrato de UI/rutas para la aplicación Inertia monolítica. Navegación web
estándar con cookies de sesión; sin API desacoplada (Principio I). Páginas
Inertia resueltas con `Inertia::render()`; errores de validación viajan en el
prop `errors`. Todas las rutas exigen sesión autenticada (grupo `auth`).

## Convenciones del contrato

- **Autorización**: `ProjectPolicy` — `view` = miembro (propietario o pivote);
  `update`/`delete`/`transition`/`manageMembers` = propietario.
- **No miembro** (o proyecto inexistente): **404** en show/edit/update/destroy
  y en gestión de miembros — nunca 403, para no revelar existencia (FR-009).
- **Éxito en formulario**: redirect 302 a la ficha (`projects/{project}`), salvo
  alta y baja de miembros, que vuelven a la ficha; eliminación → `projects.index`.
- **Error de validación**: redirect de vuelta con prop `errors` (campo → mensaje).
- **Frontend**: imports de Wayfinder (`@/routes`, alias por nombre de ruta).
- **Índice**: solo proyectos donde el usuario es miembro (propietario o
  colaborador), nunca ajenos (FR-002/FR-009).

## Rutas (middleware `auth`)

| Método | Ruta                                 | Nombre                     | Resultado éxito                                                                       | Resultado error                                                                                    |
| ------ | ------------------------------------ | -------------------------- | ------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| GET    | `/projects`                          | `projects.index`           | Página `Projects/Index`: lista (título, estado, avance) de mis proyectos              | —                                                                                                  |
| GET    | `/projects/create`                   | `projects.create`          | Página `Projects/Create` con formulario                                               | —                                                                                                  |
| POST   | `/projects`                          | `projects.store`           | Proyecto creado (creador = propietario, estado `active`, avance 0%); redirect a ficha | Vuelta con `errors` (título requerido/max 255)                                                     |
| GET    | `/projects/{project}`                | `projects.show`            | Página `Projects/Show`: ficha con datos, avance, miembros y acciones permitidas       | 404 si no miembro                                                                                  |
| GET    | `/projects/{project}/edit`           | `projects.edit`            | Página `Projects/Edit`                                                                | 404 si no propietario                                                                              |
| PUT    | `/projects/{project}`                | `projects.update`          | Cambios persistidos; redirect a ficha                                                 | 404 si no propietario; `errors` de validación                                                      |
| PUT    | `/projects/{project}/status`         | `projects.status`          | Transición aplicada; redirect a ficha                                                 | 404 si no propietario; `errors` si la transición no está permitida (p. ej. archivado → completado) |
| DELETE | `/projects/{project}`                | `projects.destroy`         | Proyecto y membresías eliminados; redirect al índice                                  | 404 si no propietario                                                                              |
| POST   | `/projects/{project}/members`        | `projects.members.store`   | Colaborador añadido; redirect a ficha                                                 | 404 si no propietario; `errors` si el correo no existe o ya es miembro                             |
| DELETE | `/projects/{project}/members/{user}` | `projects.members.destroy` | Colaborador retirado; redirect a ficha                                                | 404 si no propietario o no es colaborador                                                          |

## Props y comportamiento de las páginas

- **`Projects/Index`**: `projects` — array de `{ id, title, status, progress }`
  de mis proyectos, ordenados por recientemente actualizados.
- **`Projects/Show`**: `project` — `{ id, title, description, status,
progress, owner: {id, name, email}, members: [{id, name, email}],
allowedTransitions: string[] }`. Las acciones de estado solo se muestran
  para el propietario y según `allowedTransitions`; la gestión de miembros
  solo para el propietario; el proyecto archivado/completado se muestra en
  modo solo lectura (research R-5).
- **Avance**: siempre entero 0–100, idéntico en índice y ficha (SC-003);
  0% mientras no exista el módulo de tareas.

## Restricciones de contrato

- No existe ruta pública de proyectos: fuera de sesión todo redirige a login
  (feature 001).
- El propietario no puede retirarse a sí mismo ni ser retirado por la ruta de
  miembros (FR-008).
- No hay rutas de tareas: llegan con el módulo de tareas.
