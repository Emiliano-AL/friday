# Data Model: Modal de Tarea Mejorado (spec 012)

**Fecha**: 2026-09-30 | **Feature**: [spec.md](spec.md)

## Resumen

**No hay cambios de modelo de datos.** Esta feature es puramente de presentación
del diálogo de crear/editar tarea: reorganiza secciones, añade acordeones,
ajusta la altura del editor de descripción y unifica la barra de acciones. No se
crean ni modifican migraciones, enums, validaciones ni endpoints.

## Entidades existentes (referencia, sin cambios)

### Tarea (existente)

- **Representa**: unidad de trabajo del equipo, aislada o vinculada a proyecto y
  sprint.
- **Atributos relevantes para este diálogo** (todos ya existentes, solo lectura
  en esta feature): `title` (obligatorio, ≤ 255), `description` (JSON de
  EditorJS, ≤ 50 000 chars, nullable), `type`, `priority`, `status` (enums del
  dominio), `project_id` / `sprint_id` (opcionales, relación), `assignee_id`
  (opcional).
- **Validaciones**: se conservan íntegras; ninguna cambia por este spec.
- **Relaciones**: pertenece opcionalmente a Proyecto y Sprint; responsable
  opcional (Usuario miembro del proyecto).

### Estado efímero de UI (nuevo, no persistido)

- **Representa**: expanded/colapsado de las secciones "Vinculación y alcance" y
  "Estimación y fechas" dentro del diálogo.
- **Ciclo de vida**: refs locales del componente; se reinician en cada apertura
  del diálogo (`FR-005`). No se persiste ni se sincroniza con el servidor.
- **Reglas**: "Vinculación y alcance" inicia expandida si la tarea tiene
  proyecto o sprint asignado; si un error de validación afecta a un campo de una
  sección colapsada, la sección se expande (`FR-006`).

## Reglas derivadas del spec

- Colapsar/reexpandir no altera los valores de los campos (`FR-004`); guardar
  con secciones colapsadas envía sus valores igualmente.
- La dependencia proyecto → sprint existente se conserva: cambiar de proyecto
  limpia el sprint incompatible (`FR-010`).
