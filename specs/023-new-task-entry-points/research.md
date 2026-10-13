# Research: Accesos a "Nueva tarea" y "Acciones rápidas"

**Fecha**: 2026-10-10 | **Feature**: [spec.md](spec.md) | **Plan**: [plan.md](plan.md)

Sin NEEDS CLARIFICATION. Investigación sobre el código actual.

## Hallazgos

- `AuthenticatedLayout.vue`: `provide('friday:open-palette', openPalette)`; la
  barra lateral emite `newTask` → `goToQuickCapture()` = `router.visit(backlog)`;
  la acción de paleta "Crear nueva tarea" hace `router.visit(backlog)`; el
  keydown global abre la paleta con `C`.
- `ActivityDashboard.vue` (inicio con datos) y `HomeCanvas.vue` (inicio sin
  datos) inyectan `friday:open-palette` en sus botones "Nueva Tarea".
- `TaskFormModal.vue` necesita `projects: TaskProjectOption[]` (con miembros y
  sprints) y `currentUser`; hoy solo `Tasks/Index` y `Backlog/Index` reciben
  `projects`. `BacklogController::projectOptions()` (privado) ya construye
  exactamente esa forma (sprints activos/planificados).
- `TaskController@store` termina en `back()->with('success', ...)`: crear desde
  cualquier página la recarga con sus props al día (FR-006 sin cambios backend).
- `Tasks/Index.vue` escucha `keydown` en fase de **captura** y hace
  `stopPropagation()` para `C`; el listener del layout (burbuja) no se ejecuta
  en Mis Tareas → no habrá dos formularios (FR-004/FR-007).
- `HandleInertiaRequests::share()` ya comparte `projects` (resumen ligero del
  selector de contexto); el nombre `projects` también lo usan páginas como
  prop propia, así que el nuevo prop necesita otro nombre.
- Inertia v3: un prop *optional* no se resuelve en visitas completas, solo
  cuando un partial reload lo pide con `only` (docs v3, "Prop Evaluation Model").

## Decisiones

### D1. Un solo `TaskFormModal` global en el layout
- **Decision**: `AuthenticatedLayout` monta un `TaskFormModal` en modo `create`
  y expone `provide('friday:open-task-form', openTaskForm)`.
- **Rationale**: todos los accesos (inicio, tarjeta, paleta, tecla C) abren el
  mismo formulario (FR-008) sin duplicar estado por página.
- **Alternativas**: un modal por página → duplicación y riesgo de dos abiertos.

### D2. Opciones del formulario como prop compartido *optional*
- **Decision**: `HandleInertiaRequests` comparte
  `taskFormProjects => Inertia::optional(fn () => Project::taskFormOptionsFor($user))`;
  al abrir, si aún no está cargado, el layout hace
  `router.reload({ only: ['taskFormProjects'] })` y abre el modal en `onSuccess`.
- **Rationale**: cero costo en cada navegación; una consulta solo cuando se
  usa. Nombre distinto de `projects` para no chocar con props de página.
- **Alternativas**: compartir siempre (consulta con sprints y miembros en cada
  página); endpoint JSON aparte (contradice Constitución I).

### D3. Extraer las opciones a `Project::taskFormOptionsFor(User)`
- **Decision**: mover `BacklogController::projectOptions()` a un método
  estático del modelo `Project`, reutilizado por el Backlog y el prop
  compartido.
- **Rationale**: una sola fuente de la forma `TaskProjectOption`; sin carpetas
  nuevas.

### D4. Tecla C global abre el formulario
- **Decision**: en el keydown del layout, `C` → `openTaskForm()` (mismas
  guardas: sin modificadores, fuera de campos editables, sin `dialog[open]` ni
  paleta abierta). Mis Tareas conserva su propio manejador en captura.
- **Rationale**: FR-004 y la etiqueta "Presiona C" del inicio.

### D5. Barra lateral → "Acciones rápidas"
- **Decision**: el botón emite `quickActions` (antes `newTask`), etiqueta
  "Acciones rápidas", icono `bolt`, `<kbd>⌘K</kbd>`; el layout abre la paleta.
  Se elimina `goToQuickCapture`.
- **Rationale**: FR-005.

### D6. Paleta "Crear nueva tarea"
- **Decision**: su `run` cierra la paleta (ya lo hace `CommandPalette.run`) y
  llama `openTaskForm()`; conserva atajo `C`.
- **Rationale**: FR-003.
