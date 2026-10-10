# Research: Tareas del Proyecto — Modal de Nueva Tarea y Listado Estilo Mis Tareas

**Fecha**: 2026-10-01
**Feature**: [spec.md](spec.md)

Contexto explorado: `resources/js/pages/Projects/Show.vue`, `resources/js/pages/Tasks/Index.vue`, `resources/js/Components/Tasks/Board/*` (TaskFormModal, TaskRow, TaskGroupSection, TaskBoard), `resources/js/Components/Tasks/*` (TaskList, TaskModal, TaskCommentsModal, types), `resources/js/types/task.ts`, `resources/js/types/project.ts`, `app/Http/Controllers/{TaskController,ProjectTaskController,ProjectController}.php`, `app/Http/Requests/SaveTaskRequest.php`, `app/Policies/TaskPolicy.php`.

## Hallazgos clave del código existente

- **El botón "Nueva Tarea" del header del proyecto** (`Show.vue:378-385`) hoy solo ejecuta `goToSprintsTab` (cambia de tab). No abre ningún modal.
- **La vista "Lista" de la sección Tareas** (`Show.vue:895-1040`) usa el `TaskList` antiguo (listado básico) seguido de un formulario inline (`taskForm` + `submitTask`) que publica en la ruta anidada `projects.tasks.store`.
- **`TaskFormModal` (spec 012)** publica en las rutas globales `tasks.store` / `tasks.update`, que devuelven `back()` → al usarse desde el detalle del proyecto, Inertia recargaría esa misma página con props frescos (cumple FR-004 sin rutas nuevas). `SaveTaskRequest` ya valida pertenencia al proyecto, sprint del mismo proyecto y responsable miembro.
- **Excepción: borrado.** `TaskFormModal.removeTask` usa la ruta global `tasks.destroy`, cuyo controlador redirige a `tasks.index` — sacaría al usuario del proyecto. El patrón de solución ya existe en `TaskCommentsModal`: props opcionales (`useGlobal`, `endpoints`).
- **`TaskController@show` / `storeComment`** (JSON, usados por el modal de comentarios en modo global) autorizan con `can('view', $task)` — válido para miembros del proyecto.
- **Shape de tareas**: `/tasks` consume `BoardTask` (con `project`, `commentsCount`, `canUpdate`, `canDelete`, `assignee.avatar`). El payload de `ProjectController::projectPayload` devuelve otra forma (`TaskItem`: sin `project`, sin contadores, con `comments` embebidos — payload pesado). `BacklogView`, `KanbanBoard`, `TaskList` y `TaskModal` están tipados con `TaskItem` y solo los usa `Show.vue`.
- **Permisos** (`TaskPolicy`): update = miembro de proyecto activo (o asignado si standalone); delete = dueño de proyecto activo (o asignado si standalone). `canWriteTasks` en `Show.vue` = `project.status === 'active'`.

## Decisiones

### D1 — Botón "Nueva Tarea" abre el modal (FR-001, FR-002, FR-003)
- **Decision**: El botón del header abre `TaskFormModal` en modo `create` con `initialProject` = `{id, title}` del proyecto (el modal ya bloquea el select de proyecto cuando `initialProject !== null`, comportamiento añadido en spec 012). Se elimina `goToSprintsTab` como acción del botón. El botón se muestra solo si `canWriteTasks` (FR-009).
- **Rationale**: Reutilización total del modal existente; cero lógica nueva de formulario.
- **Alternatives considered**: Mantener navegación a tab + formulario inline (status quo, rechazado por el spec); crear un modal propio del proyecto (duplicación).

### D2 — Reutilización del modal con una sola ampliación opcional (FR-004, FR-007)
- **Decision**: `TaskFormModal` se usa tal cual desde `Show.vue` para crear y editar (las rutas globales `back()` recargan el detalle del proyecto). Se añade un único prop opcional `destroyUrl?: string`: cuando se informa, `removeTask` hace `form.delete(props.destroyUrl)` en lugar de la ruta global. En el proyecto se pasa la ruta anidada `projects.tasks.destroy`. En `/tasks` no se pasa (comportamiento actual intacto).
- **Rationale**: Evita la redirección a `tasks.index` del borrador global sin tocar el servidor ni el comportamiento de `/tasks`. Patrón `endpoints` ya probado en `TaskCommentsModal`.
- **Alternatives considered**: Cambiar `TaskController@destroy` a `back()` (rechazado: toca servidor y el redirect explícito es intencional en el contexto global); emitir el formulario al padre (rechazado: refactor grande del modal con riesgo en spec 012 ya validado).

### D3 — Alineación del payload de tareas del proyecto al shape `BoardTask` (FR-005)
- **Decision**: `ProjectController::projectPayload` devuelve las tareas en forma `BoardTask`: añade `project: {id, title}`, `commentsCount` (vía `withCount('comments')`), `canUpdate` y `canDelete` (mismas reglas que `TaskController::taskPayload`) y elimina los `comments` embebidos. En cliente: `ProjectDetail.tasks` pasa a `BoardTask[]`; `BacklogView` y `KanbanBoard` actualizan su prop a `BoardTask[]` (usan un subconjunto de campos, sin cambios de plantilla).
- **Rationale**: Una sola fuente de verdad de permisos (servidor), payload más liviano (hoy embebe todos los comentarios de todas las tareas), y el listado reutiliza `TaskRow`/grupos de `/tasks` sin adaptadores. **Esto enmienda el supuesto del spec de "cero cambios de servidor"**: es un cambio de forma de respuesta, sin rutas ni capacidades nuevas.
- **Alternatives considered**: Adaptador cliente `TaskItem → BoardTask` (rechazado: duplicar en cliente las reglas de `canUpdate`/`canDelete` riesgo de deriva con `TaskPolicy`; mantiene payload pesado).

### D4 — Extracción del listado en componente reutilizable (FR-005, FR-006, FR-008)
- **Decision**: Extraer de `Tasks/Index.vue` la experiencia de lista (búsqueda, tabs, filtro de responsable, grupos `TaskGroupSection` + `TaskRow`, línea de resumen) en un componente `resources/js/Components/Tasks/Board/TaskListPanel.vue` con props de configuración: tareas, usuario actual, y flags `showProjectFilter` / `showStandaloneTab` (por defecto `true`, para `/tasks`). `Index.vue` se refactoriza a usarlo sin cambios de comportamiento; `Show.vue` lo usa con ambos flags `false` sobre `project.tasks`. En el proyecto, el grupo "Sin Proyecto Asignado" queda siempre vacío y el tab "Sin Proyecto" se omite.
- **Rationale**: Evita duplicar la lógica de agrupación/filtrado entre dos páginas; `/tasks` queda igual (regresión controlada por types/build/tests).
- **Alternatives considered**: Copiar la lógica en `Show.vue` (rechazado: duplicación y deriva futura); reutilizar `TaskList` antiguo ampliándolo (rechazado: su base visual difiere del spec 009/012).

### D5 — Comentarios en modo global desde el proyecto
- **Decision**: `Show.vue` pasa `TaskCommentsModal` a `use-global` con `endpoints {show: tasks.show, store: tasks.comments.store}` — idéntico a `/tasks`. `commentingTask` pasa a ser `BoardTask | null` (sin necesidad de `commentingTaskFresh`).
- **Rationale**: El payload ya no embebe comentarios (D3) y los endpoints globales ya autorizan a miembros del proyecto.
- **Alternatives considered**: Añadir endpoint `show` JSON anidado (rechazado: cambio de servidor innecesario).

### D6 — Eliminación del formulario inline (FR-006)
- **Decision**: Se eliminan de `Show.vue` el `taskForm`, `submitTask` y el bloque `<form>` inline de la sección Tareas, junto con el import de `storeTask`. La vista "Lista" queda: `TaskListPanel` sobre `project.tasks` y nada más.
- **Rationale**: El spec lo exige explícitamente; la creación queda canalizada por el modal (D1).

### D7 — Limpieza de componentes huérfanos
- **Decision**: `TaskList.vue` y `TaskModal.vue` quedan sin usos tras esta feature → se eliminan. `TaskItem` en `Components/Tasks/types.ts` se conserva (lo usa `TaskCommentsModal`); `SprintTaskList.vue`/`SprintHeader.vue` no se tocan.
- **Rationale**: Menos superficie de mantenimiento; el proyecto no referencia estos archivos tras el cambio.
- **Alternatives considered**: Mantenerlos "por si acaso" (rechazado: código muerto).

### D8 — Cambio de estado y borrado desde el listado del proyecto (FR-007)
- **Decision**: En el panel del proyecto, `@status` usa la ruta global `tasks.update` (solo `{status}`, `back()` con `preserveScroll` — igual que `/tasks`); `@remove` replica `/tasks` (abre el modal de edición, cuyo footer "Eliminar tarea" usa `destroyUrl` anidado de D2). `@open`/`@edit` abren `TaskFormModal` en modo edición con la tarea.
- **Rationale**: Mismo comportamiento que `/tasks` (FR-007) con autorizaciones ya existentes; el borrado anidado conserva la redirección a `projects.show`.

### D9 — Sin nuevas dependencias ni tests de frontend
- **Decision**: Todo el cambio usa componentes y rutas existentes. No se añaden paquetes. Tests: suite Pest existente (posibles ajustes si algún test de Projects afirma el shape antiguo de tareas — verificar en fase de tareas); gates `npm run types:check`, `npm run build`, `php artisan test --compact`; validación manual por quickstart.md.
- **Rationale**: Reglas del proyecto (cambio de UI sin lógica de dominio nueva; D5 de spec 012 estableció el precedente).
