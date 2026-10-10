# Contract: Vistas de tareas unificadas del proyecto (`/projects/{id}`)

**Fecha**: 2026-10-09 | **Feature**: [spec.md](spec.md) | **Data model**: [data-model.md](data-model.md)

Contrato UI de la sección Tareas de la ficha de proyecto tras la unificación.
Las vistas consumen los mismos componentes que las páginas globales; este
contrato fija el payload y las interacciones que los alimentan.

## 1. Payload Inertia (`GET /projects/{id}` → `Projects/Show`)

`project.tasks: ProjectTask[]` donde cada tarea incluye los campos existentes
(`id, title, description, type, typeLabel, priority, priorityLabel, status,
statusLabel, project, sprint, assignee, commentsCount, canUpdate, canDelete`)
**más** los campos aditivos:

```ts
type ProjectTask = {
  // ...campos existentes...
  code: string | null;            // 'FRN-7'
  points: number | null;
  createdAt: string;              // ISO 8601
  completedAt: string | null;     // ISO 8601
  authorId: number | null;
  sprint: { id: number; name: string; status: 'planned' | 'active' | 'completed' } | null;
};
```

El objeto es asignable a `BacklogTask` (feature 018) y satisfacible por
`BoardTaskShape` (`TaskFormModal`).

## 2. Vista Backlog (paridad con `/backlog`)

- **Componentes**: `BacklogSectionList` (secciones Alta y urgente / Media /
  Baja e ideas; sort `priority | recent`) + `BacklogTaskRow`.
- **Conjunto base**: `project.tasks` filtrado a `sprint === null && status !== 'done'`.
- **Filtro de sprint de sección**: `none` (default) → conjunto base; sprint
  concreto → tareas de ese sprint (cualquier estado no completado); `all` →
  `status !== 'done'` completo.
- **Evento `select`** (solo cuando `task.canUpdate`) → `TaskFormModal` en modo
  edición (ya montado por la página).
- **Estado vacío**: mensaje propio de la página cuando la lista filtrada es
  vacía (el componente solo agrupa, no renderiza vacío).
- Diferencias legítimas vs `/backlog`: sin `QuickCapture`, sin
  `BacklogFilters` (Todas/Sin/Con proyecto), sin header de resumen, sin chip de
  proyecto en la fila (contexto implícito).

## 3. Vista Kanban (paridad con `/tasks` modo kanban)

- **Componentes**: `TaskBoard` + `TaskBoardCard` (columnas `todo`,
  `in_progress`, `in_review`, `done` + columna `blocked` deshabilitada).
- **Conjunto**: tareas del proyecto que caen en esas columnas
  (`status ∈ {todo, in_progress, in_review, done}`), sujeto al filtro de sprint
  de sección (`none` → excluye todas las que tienen sprint; `all` → todas).
  Las tareas con `status = 'backlog'` no se muestran (igual que en `/tasks`).
- **Cambio de estado**: drag & drop → `PUT /tasks/{id}` con body `{status}`
  (Wayfinder `update` de `@/routes/tasks`), `preserveScroll`. La tarjeta solo es
  arrastrable si `canWrite = project.status === 'active'`.
- **Evento `open`** → `TaskFormModal` edición; **evento `add`** (botón de
  columna) → modal creación con `initialStatus` de la columna y
  `initialProject` del proyecto.

## 4. Vista Lista

Sin cambios: `TaskListPanel` con las mismas props y eventos de hoy
(FR-007). El filtro de sprint de sección no se muestra en esta vista.

## 5. Invariantes verificables

1. Los 6 campos nuevos están presentes en cada `project.tasks` (test de payload).
2. El kanban del proyecto realiza exactamente un `PUT /tasks/{id}` `{status}`
   por movimiento y persiste tras recargar.
3. El filtro de sprint produce los mismos conjuntos en vista Backlog y Kanban
   para la misma opción (FR-006).
4. `Projects/Show.vue` no importa `BacklogView`, `KanbanBoard` ni `TaskCard`
   (verificable por grep) y esos archivos no existen tras la migración.
5. `TaskBoardTest` y `ProjectShowPayloadTest` pasan sin modificaciones.
