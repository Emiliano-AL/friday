# Contratos: Tareas del Proyecto — Modal de Nueva Tarea y Listado Estilo Mis Tareas

**Fecha**: 2026-10-01
**Feature**: [spec.md](spec.md) · Decisiones: [research.md](research.md)

Contratos de interfaz entre las piezas afectadas. Tipos referenciados de `resources/js/types/task.ts` (`BoardTask`, `TaskProjectOption`, `TaskStatusValue`).

## 1. `TaskFormModal` — ampliación opcional de borrado (D2)

El componente existente (`resources/js/Components/Tasks/Board/TaskFormModal.vue`) se usa sin cambios de API salvo:

```ts
props: {
  // ...props actuales sin cambios...
  destroyUrl?: string; // default: undefined
}
```

- **Comportamiento**: en modo edición, el botón "Eliminar tarea" del pie hace `form.delete(destroyUrl ?? <ruta global tasks.destroy>)`.
- **Garantías**: `/tasks` no pasa la prop → comportamiento idéntico al actual. El proyecto pasa la ruta anidada `projects.tasks.destroy` → el borrado conserva la redirección a `projects.show` (sin sacar al usuario del proyecto).

### Uso en el detalle del proyecto (`Show.vue`)

```ts
<TaskFormModal
  :open="taskModalOpen"
  :mode="taskModalMode"                    // 'create' | 'edit'
  :task="editingBoardTask ?? undefined"    // BoardTask en edición
  :projects="[projectOption]"              // TaskProjectOption[] de un solo elemento
  :current-user="currentUser"
  :initial-project="{ id: project.id, title: project.title }"  // fija el select de proyecto
  :destroy-url="taskModalMode === 'edit' && editingBoardTask
      ? destroyTask.url([project.id, editingBoardTask.id])
      : undefined"
  @close="closeTaskModal"
/>
```

- `projectOption: TaskProjectOption` = `{ id, title, members: owner+members ({id,name,avatar}), sprints: project.sprints ({id,name}) }`.
- Al crear: el servidor responde `back()` → Inertia recarga el detalle con `project.tasks` fresco (FR-004).

## 2. `TaskListPanel` — componente nuevo reutilizable (D4)

Ubicación: `resources/js/Components/Tasks/Board/TaskListPanel.vue` (nuevo).

```ts
props: {
  tasks: BoardTask[];
  currentUser: { id: number; name: string; avatar: string | null };
  // Configuración de contexto (ambos default true → comportamiento /tasks):
  showProjectFilter?: boolean;
  showStandaloneTab?: boolean;
}
emits: {
  create: [];                                  // solicitud de abrir modal en modo create
  edit: [task: BoardTask];                     // abrir modal en modo edit
  status: [task: BoardTask, status: TaskStatusValue];
  comment: [task: BoardTask];
  remove: [task: BoardTask];
}
```

**Comportamiento interno** (extraído de `Tasks/Index.vue`, reglas de oro):

- Búsqueda por título, tabs de agrupación, filtro de responsable y (si `showProjectFilter`) filtro de proyecto.
- Agrupación: "Alta Prioridad & Urgentes" / "En Curso" / "Sin Proyecto Asignado" (omitido si `showStandaloneTab === false`) / "Próximas & Backlog" / "Completadas" (tab done).
- Cada grupo renderiza `TaskGroupSection` + `TaskRow` (`can-write = task.canUpdate`), reenviando todos los eventos.
- **Regla de oro**: con `showProjectFilter=true` y `showStandaloneTab=true` el render y comportamiento deben ser píxel-a-píxel los actuales de `/tasks` (regresión cero; validación en quickstart §5).

## 3. Payload `Projects/Show` — `project.tasks` en forma `BoardTask` (D3)

Contrato servidor→cliente (ver [data-model.md](data-model.md) para el detalle campo a campo). El frontend (`Show.vue`, `BacklogView`, `KanbanBoard`, `types/project.ts`) tipa `ProjectDetail.tasks: BoardTask[]`.

## 4. Comentarios en modo global (D5)

`Show.vue` invoca `TaskCommentsModal` exactamente con el patrón de `/tasks`:

```ts
<TaskCommentsModal
  :show="commentingTask !== null"
  :task="commentingTask ? { ...commentingTask, comments: [] } : null"
  :can-comment="commentingTask?.canUpdate ?? false"
  use-global
  :endpoints="commentingTask
      ? { show: taskShow.url(commentingTask.id), store: storeTaskComment.url(commentingTask.id) }
      : { show: '', store: '' }"
  @close="commentingTask = null"
/>
```

Los endpoints globales autorizan con `TaskPolicy::view` (miembro del proyecto o asignado).
