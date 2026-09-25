# Contract: Superficie web de sprints

**Feature**: 003-sprint-crud | **Date**: 2026-09-21
**Modelo**: [data-model.md](./data-model.md) · **Decisiones**: [research.md](./research.md)

Contrato de UI/rutas del módulo de sprints para la aplicación Inertia
monolítica. Sesión por cookies; sin API desacoplada (Principio I). La gestión
ocurre dentro de la ficha del proyecto (spec, Assumptions); no existen páginas
ni rutas propias de listado/alta/edición de sprints. Todas las rutas exigen
sesión autenticada (grupo `auth`).

## Convenciones del contrato

- **Autorización**: `SprintPolicy` — `viewAny`/`view` = miembro del proyecto;
  `create`/`update`/`delete` = propietario y proyecto `active` (FR-006/FR-007).
- **No propietario** (colaborador o usuario ajeno) en escritura: **404**,
  indistinguible de inexistente (precedente `ProjectController`).
- **Propietario con proyecto archivado/completado**: redirect de vuelta con
  `errors.project` = "El proyecto no admite cambios en su estado actual."
  (mensaje claro exigido por FR-006/SC-003; research R-4).
- **Pertenencia**: un sprint de otro proyecto se trata como inexistente → 404
  (research R-5).
- **Éxito**: redirect 302 a la ficha del proyecto (`projects.show`).
- **Validación**: redirect de vuelta con `errors` por campo (`name`,
  `start_date`, `end_date` cuando viola `after_or_equal:start_date`).
- **Frontend**: imports de Wayfinder (`@/routes/projects/sprints`).

## Rutas (middleware `auth`)

| Método | Ruta                                   | Nombre                     | Resultado éxito                                                | Resultado error                                                                               |
| ------ | -------------------------------------- | -------------------------- | -------------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| POST   | `/projects/{project}/sprints`          | `projects.sprints.store`   | Sprint creado asociado al proyecto; redirect a ficha           | 404 si no propietario; `errors` de validación; `errors.project` si el proyecto no está activo |
| PUT    | `/projects/{project}/sprints/{sprint}` | `projects.sprints.update`  | Cambios persistidos; redirect a ficha                          | 404 si no propietario o sprint ajeno; mismas reglas de validación y de estado                 |
| DELETE | `/projects/{project}/sprints/{sprint}` | `projects.sprints.destroy` | Sprint eliminado (proyecto y demás intactos); redirect a ficha | 404 si no propietario o sprint ajeno; `errors.project` si el proyecto no está activo          |

## Props y comportamiento de las páginas

- **`Projects/Show`** (única superficie):
    - `project.sprints` — array de `{ id, name, startDate, endDate }` en formato
      `YYYY-MM-DD`, ordenados por `startDate` ascendente (FR-003).
    - Acciones de escritura solo si `project.isOwner && project.status ===
'active'` (FR-006/FR-007): alta inline (nombre + inicio + fin), edición en
      modal y baja con confirmación explícita. En cualquier otro caso la sección
      es solo lectura.
    - El error de bloqueo de estado queda visible tras un intento forzado a
      través de `errors.project` (compartido por sesión, como los errores de
      formulario).
- Sin páginas `Sprints/*` independientes.

## Restricciones de contrato

- No hay rutas de listado propias de sprints: la lista viaja siempre con la
  ficha del proyecto.
- Fechas siempre `date` calendario (`YYYY-MM-DD`); horas no soportadas
  (FR-001, research R-1).
- Sin límite de sprints por proyecto ni restricción de solapamiento
  (FR-008/FR-009): el servidor jamás rechaza por esas causas.
- Un sprint no es accesible fuera de su proyecto: no existe ruta
  `/sprints/{sprint}`.
- La eliminación exige confirmación en la UI; sin ella no se emite petición
  (US3, escenario 2).
