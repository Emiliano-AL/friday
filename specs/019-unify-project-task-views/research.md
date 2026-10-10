# Research: Unificar vistas de tareas del proyecto con las vistas globales

**Fecha**: 2026-10-09 | **Feature**: [spec.md](spec.md) | **Plan**: [plan.md](plan.md)

Sin NEEDS CLARIFICATION pendientes: todos los unknowns se resolvieron
inspeccionando el código actual (incluyendo el trabajo paralelo sin commit en el
árbol: migración a Tiptap, `Tasks/Index.vue` reescrito, `TaskList.vue` y
`TaskModal.vue` ya eliminados).

## Hallazgos del código actual

- `Projects/Show.vue` tiene el switcher `taskView: 'lista' | 'backlog' | 'kanban'`
  (pills, estado local no persistente). Lista → `TaskListPanel`; Backlog →
  `BacklogView.vue` **legacy** (estilos gray utilitarios pre-design-system);
  Kanban → `KanbanBoard.vue` **legacy** (5 columnas gray-50, DnD propio).
- El payload de `ProjectController@projectPayload` entrega por tarea: id, title,
  description, type(+Label), priority(+Label), status(+Label), project, sprint
  {id,name}, assignee, commentsCount, canUpdate, canDelete. **Le faltan**
  `code`, `points`, `createdAt`, `completedAt`, `authorId`, `sprint.status` —
  campos que `BacklogTask` requiere.
- El kanban global (`TaskBoard.vue` + `TaskBoardCard.vue`) usa columnas
  `todo / in_progress / in_review / done` (+ `blocked` deshabilitada
  "Próximamente"). **No tiene columna `backlog`**. Cambio de estado por
  drag & drop → `PUT /tasks/{id}` con solo `{status}` (global, validación
  `sometimes` de `SaveTaskRequest`).
- El kanban del proyecto hoy hace `PUT /projects/{project}/tasks/{task}` con el
  **payload completo** (forzado por `UpdateTaskRequest`, donde
  title/type/priority/status son `required`).
- El filtro de sprint hoy vive **dentro** del `BacklogView` legacy; el kanban
  legacy no tiene filtros; `TaskListPanel` (lista) tampoco tiene filtro de
  sprint (y la spec lo deja fuera: FR-007).
- Los tres componentes legacy (`BacklogView.vue`, `KanbanBoard.vue`,
  `TaskCard.vue`) **solo** los importa `Projects/Show.vue` — quedan huérfanos
  tras la migración y son eliminables sin afectar nada más.
- Tests: `ProjectShowPayloadTest` y `TaskBoardTest` usan aserciones a nivel
  campo (`->where()`), no de forma exacta → **añadir campos es seguro**.
  `TaskBoardTest` prueba la ruta de proyecto con body completo; como no se
  cambia `UpdateTaskRequest` ni esa ruta, sigue pasando sin tocarlo.
- `TaskFormModal` acepta `BoardTaskShape` (= BoardTask sin labels/counts/perms,
  con `points?` opcional): `BacklogTask` y el payload de proyecto extendido
  satisfacen esa forma → la edición desde ambas vistas reutiliza el modal de la
  página sin cambios.

## Decisiones

### D1. Reutilizar `BacklogSectionList` + `BacklogTaskRow` en la vista Backlog del proyecto
- **Decision**: importar los componentes de la feature 018 tal cual en
  `Projects/Show.vue`. Para satisfacer `BacklogTask`, el payload de proyecto se
  extiende con `code`, `points`, `createdAt`, `completedAt`, `authorId` y
  `sprint.status` (véase D2). El estado vacío lo renderiza la página (el
  componente solo agrupa), con el mensaje adaptado al proyecto.
- **Rationale**: Constitución V; el spec exige paridad visual
  (SC-001) — la vía más corta y mantenible es el mismo componente, no una
  copia. Agrupación por secciones y orden viven dentro del componente.
- **Alternativas**: clonar los componentes con prefijo `Project…` → duplicación
  permanente y deriva visual futura; reestilizar el `BacklogView` legacy →
  rehacer a mano lo que ya existe resuelto.

### D2. Extensión aditiva del payload de `ProjectController@projectPayload`
- **Decision**: añadir por tarea `code`, `points`, `createdAt` (ISO 8601),
  `completedAt` (ISO 8601|null), `authorId`, y en `sprint` el campo `status`.
  Sin tocar campos existentes ni su orden de cálculo.
- **Rationale**: los campos ya existen en la BD (feature 018); el tipo
  `BacklogTask` los requiere; las aserciones de tests son a nivel campo, así que
  es 100% compatible hacia atrás.
- **Alternativas**: aflojar `BacklogTask` (hacer opcionales `code`/`createdAt`)
  → degrada el contrato de la feature 018 para todos sus consumidores; crear un
  tipo intermedio `ProjectBacklogTask` → duplicación innecesaria, el payload
  extendido ya es asignable a `BacklogTask` (los campos extra `typeLabel` etc.
  no molestan al pasar una variable).

### D3. Conjunto de tareas de la vista Backlog del proyecto
- **Decision**: `sprint === null && status !== 'done'` (espejo del
  `scopeBacklog` global), filtrado client-side sobre `project.tasks`.
- **Rationale**: el edge case del spec ("proyecto cuyas tareas sin sprint están
  completadas → estado vacío") exige excluir completadas; el filtro de sprint
  propio de la vista legacy se conserva a nivel de sección (D5), donde
  "sin sprint" es la opción por defecto.
- **Alternativas**: solo `sprint === null` (incluir done) → contradice el edge
  case; replicar `scopeBacklog` en el controlador → el filtro dinámico por
  sprint queda en el cliente de todas formas.

### D4. Vista Kanban con `TaskBoard` y cambio de estado unificado al patrón global
- **Decision**: sustituir `KanbanBoard` legacy por `TaskBoard` +
  `TaskBoardCard`. El evento `moved` se resuelve con
  `router.put(update.url(task.id), { status }, { preserveScroll: true })`
  (ruta global `tasks.update`), igual que en `Tasks/Index.vue`. `canWrite` del
  tablero = `project.status === 'active'` (misma regla de hoy).
- **Rationale**: SC-002/SC-003 piden paridad de interacción, no solo de píxeles.
  `SaveTaskRequest` acepta updates parciales y `TaskPolicy::update` aplica las
  mismas reglas (miembro + proyecto activo) en la ruta global, así que no hay
  cambio de autorización efectivo.
- **Alternativas**: seguir con PUT completo a la ruta de proyecto → exige
  ensamblar el payload completo en el cliente (el tablero global no lo hace) y
  rompe la paridad pedida; relajar `UpdateTaskRequest` a `sometimes` → tocaría
  `TaskBoardTest` y la semántica de reemplazo completo de la ruta de proyecto,
  fuera del alcance mínimo.

### D5. Filtro de sprint a nivel de la sección Tareas (vistas Backlog y Kanban)
- **Decision**: mover el control de filtro de sprint del `BacklogView` legacy al
  header de la sección Tareas, visible solo en las vistas Backlog y Kanban.
  Opciones: "Sin sprint" (default), cada sprint del proyecto, "Todos".
  Backlog con "Todos" lista `status !== 'done'` agrupadas por prioridad; Kanban
  respeta el filtro igual (FR-006).
- **Rationale**: el spec (FR-006) exige que ambas vistas unificadas lo
  respeten, y hoy solo existe en el componente que se elimina. A nivel sección
  evita duplicarlo por vista.
- **Alternativas**: filtro solo en backlog (kanban siempre "todos") → incumple
  FR-006; filtro en las tres vistas → tocaría la vista Lista, que FR-007 deja
  fuera.

### D6. Tareas con estado `backlog` en el kanban unificado
- **Decision**: el kanban del proyecto muestra exactamente las mismas columnas
  que `/tasks` (`todo` en adelante); las tareas con estado `backlog` no aparecen
  en el kanban, igual que en el tablero global, y siguen visibles en las vistas
  Lista y Backlog.
- **Rationale**: SC-002 exige indistinguibilidad del tablero global; añadir una
  columna "Backlog" al componente compartido rompería esa paridad en `/tasks`.
- **Alternativas**: columna extra solo en el proyecto → divergencia del
  componente compartido y del criterio de SC-002; mapear `backlog`→`todo` en
  pantalla → confunde el estado real de la tarea.

### D7. Eliminación de componentes legacy huérfanos
- **Decision**: eliminar `Components/Tasks/BacklogView.vue`,
  `Components/Tasks/KanbanBoard.vue` y `Components/Tasks/TaskCard.vue` una vez
  `Projects/Show.vue` no los importe (verificado: son sus únicos consumidores).
- **Rationale**: Constitución V — código muerto que ya nadie usa; mantenerlo
  invita a usos accidentales del estilo viejo.
- **Alternativas**: dejarlos "por si acaso" → deuda permanente; la historia git
  los conserva si se necesitan.

### D8. Edición y creación: sin cambios en `TaskFormModal`
- **Decision**: ambas vistas unificadas emiten `select`/`open` hacia el
  `TaskFormModal` que `Projects/Show.vue` ya monta (creación con
  `initialProject` por el botón "Nueva Tarea" del header, edición con
  `destroyUrl` de la ruta de proyecto).
- **Rationale**: FR-008; el modal ya acepta ambas formas de tarea
  (`BoardTaskShape`), y mantener la ruta de proyecto para crear/borrar preserva
  las reglas actuales (`StoreTaskRequest`, `destroy` con redirect al proyecto).
- **Alternativas**: que el proyecto use también las rutas globales
  store/destroy → cambio de autorización y de redirects sin necesidad.

### D9. Tipos TypeScript
- **Decision**: extender `ProjectTask` en `resources/js/types/project.ts` con
  los campos nuevos (mismos tipos que `BacklogTask`); `backlog.ts` no cambia.
  `BoardTask` ya marca `code`/`points`/`authorId` como opcionales, así que
  `TaskBoardCard` sigue compilando con o sin ellos.
- **Rationale**: un solo tipo de tarea por página evita conversiones; la
  opcionalidad en `BoardTask` ya contempla payloads sin esos campos
  (`Tasks/Index`).
- **Alternativas**: un tipo nuevo por vista → duplicación.

## Resolución de clarificaciones

| # | Tema | Resolución |
| --- | --- | --- |
| 1 | ¿Qué componentes renderizan hoy las vistas del proyecto? | Hallazgo: legacy `BacklogView`/`KanbanBoard`; lista ya usa `TaskListPanel` |
| 2 | ¿Falta información en el payload del proyecto? | D2: sí, 6 campos aditivos |
| 3 | ¿Cómo se cambia estado en kanban global vs proyecto? | D4: unificar a status-only PUT global |
| 4 | ¿Dónde vive el filtro de sprint tras eliminar el BacklogView? | D5: header de sección, vistas backlog/kanban |
| 5 | ¿Qué pasa con tareas `status=backlog` en el kanban unificado? | D6: no se muestran, paridad con `/tasks` |
| 6 | ¿Se eliminan los componentes legacy? | D7: sí, huérfanos verificados |
| 7 | ¿El modal necesita cambios? | D8: no |
| 8 | ¿Riesgo de romper tests existentes? | No: aserciones a nivel campo; `TaskBoardTest` no se toca |

## Baseline

- 2026-10-09: `php artisan test --compact` → 258 passed, 0 fallos; `npm run build` → OK (incluye el trabajo paralelo sin commit: migración Tiptap, Tasks/Index reescrito).
