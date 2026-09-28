# Contract: Tasks Board UI (Mis Tareas — lista, kanban y diálogo de tarea)

**Feature**: 009-tasks-board-ui
**Date**: 2026-09-26
**Type**: UI contract — define las rutas y payloads del servidor, las interfaces de los componentes de `Components/Tasks/Board/` y el comportamiento interactivo. Integra con el shell 007/008 (mismo layout, tokens y paleta).

## 1. Rutas globales (nuevas)

| Método | Ruta                     | Nombre                 | Uso                                                                                  |
| ------ | ------------------------ | ---------------------- | ------------------------------------------------------------------------------------ |
| GET    | `/tasks`                 | `tasks.index`          | página Mis Tareas (Inertia). `?new=1` abre el diálogo de creación                    |
| POST   | `/tasks`                 | `tasks.store`          | crear tarea (con o sin proyecto) → redirect back + flash                             |
| PUT    | `/tasks/{task}`          | `tasks.update`         | actualización parcial (`{status}` en drag/círculo; completa en modal) → back + flash |
| DELETE | `/tasks/{task}`          | `tasks.destroy`        | eliminar (diálogo, zona de peligro) → `tasks.index` + flash                          |
| GET    | `/tasks/{task}`          | `tasks.show`           | **JSON**: tarea con `comments[]` (para el modal de comentarios)                      |
| POST   | `/tasks/{task}/comments` | `tasks.comments.store` | comentar tarea global → JSON de la tarea actualizada                                 |

Las rutas anidadas `projects.tasks.*` existentes se conservan sin cambios (detalle de proyecto).

**Autorización**: visible = responsable, o miembro/líder de su proyecto. Escritura: con proyecto → reglas actuales (miembro y activo; borrar = líder); sin proyecto → responsable. Errores: 404/403 según regla existente; validación → redirect con errores (Inertia) o 422 JSON en `tasks.show`/comentarios.

## 2. Page props (`Tasks/Index`)

```ts
defineProps<{
    tasks: BoardTask[]; // visibles para el usuario, orderByDesc updated_at
    projects: { id: number; title: string }[]; // mis proyectos (filtro + modal)
}>();

interface BoardTask {
    id: number;
    title: string;
    description: string | null;
    type: 'bug' | 'feature' | 'test' | 'other';
    typeLabel: string;
    priority: 'low' | 'medium' | 'high' | 'urgent';
    priorityLabel: string;
    status: 'backlog' | 'todo' | 'in_progress' | 'in_review' | 'done';
    statusLabel: string;
    project: { id: number; title: string } | null; // null = Sin Proyecto
    sprint: { id: number; name: string } | null;
    assignee: { id: number; name: string; avatar: string | null } | null;
    commentsCount: number; // withCount; sin cuerpos
}
```

Apertura del diálogo por query: `?new=1` → crear (se limpia con `history.replaceState`).

## 3. Component interfaces (`resources/js/Components/Tasks/Board/`)

### `TaskFilters.vue`

```ts
defineProps<{
    search: string;
    tab: 'all' | 'mine' | 'standalone' | 'urgent' | 'done';
    projectId: number | 'all' | 'none';
    assigneeId: number | 'all';
    counts: {
        all: number;
        mine: number;
        standalone: number;
        urgent: number;
        done: number;
    };
    view: 'list' | 'kanban';
}>();
// emite update:* y create; buscador con kbd ⌘F (focus global en la página), pills Proyecto/Responsable (menús custom),
// pestañas rápidas con contadores reales, conmutador vista lista/kanban persistido en localStorage por la página
```

### `TaskGroupSection.vue`

```ts
defineProps<{ title: string; icon: string; count: number; hint?: string }>();
// sección agrupada: header con contador + slot de filas/tarjetas
```

### `TaskRow.vue`

```ts
defineProps<{ task: BoardTask; canWrite: boolean }>();
// emite: edit(task), status(task, status), comment(task), remove(task), open(task)
// fila: botón circular de estado (menú con los 5 estados), icono de tipo, título (truncate),
// pill Proyecto (título; tono neutral) o "Sin Proyecto", chip Sprint o "Sin Sprint",
// chip prioridad (tono por nivel), responsable (Avatar) o "Sin asignar", menú ⋯ con acciones según canWrite
```

### `TaskBoard.vue` / `TaskBoardColumn.vue` / `TaskBoardCard.vue`

```ts
// TaskBoard: props { tasks: BoardTask[]; canWrite: boolean }; emite moved(task, status), add(status), open(task), edit(task), comment(task)
// Columnas: Por Hacer (todo), En Curso (in_progress), En Revisión (in_review), Terminado (done, tarjetas atenuadas),
//           BLOQUEADO (deshabilitada: chip "Próximamente", sin drop ni add)
// DnD nativo (dragstart/dragover/drop); drop válido → moved; botón "Añadir tarea rápida" por columna (no en Terminado/Bloqueado)
// TaskBoardCard: type icon, ID futuro omitido, título, pill Proyecto/"Sin Proyecto", sprint/"Sin Sprint",
//                prioridad, conteo de comentarios (dato real), Avatar del responsable
```

### `TaskFormModal.vue` (crear + editar)

```ts
defineProps<{
    open: boolean;
    mode: 'create' | 'edit';
    task?: BoardTask;
    projects: { id: number; title: string }[];
    initialStatus?: 'todo' | 'in_progress' | 'in_review'; // desde columna kanban
}>();
// emite close
// campos: Título (autofocus, hint redacción imperativa), Tipo (4 valores reales), Estado inicial/actual (4 no-backlog),
//         Prioridad segmentada (4), Responsable (yo + miembros del proyecto elegido; solo "yo" sin proyecto),
//         Proyecto (mis proyectos + "Sin Proyecto"), Sprint (opciones del proyecto elegido; deshabilitado sin proyecto),
//         Descripción (textarea, nota "compatible con marcado")
// "Próximamente" (deshabilitados + nota): Story Points, Fecha de vencimiento, barra de herramientas de marcado/vista previa,
//         tipos extra de tarea, "Crear/Nuevo Sprint" inline
// modo edit: zona de peligro "Eliminar tarea" → confirm() → DELETE
// footer: "Crear otra al guardar" (checkbox, ⌘⇧↵), Cancelar (Esc), Crear/Guardar (⌘↵)
```

### `TaskCommentsModal.vue` (cambio retrocompatible)

```ts
defineProps<{
    show: boolean;
    task: TaskItem | BoardTask;
    projectId?: number; // modo anidado (Show) — comportamiento actual
    canComment: boolean;
    useGlobal?: boolean; // modo global: carga GET tasks.show al abrir y postea a tasks.comments.store
}>();
```

## 4. Tokens visuales (mismos que 007/008)

- Superficies: filas/tarjetas `bg-surface-container-lowest shadow-card rounded-xl/rounded-lg`; columnas kanban `bg-surface-container-low/70 rounded-xl`; diálogo `shadow-modal rounded-xl` sobre veil `bg-inverse-surface/20 backdrop-blur-sm` (panel `relative z-10`, ver fix 008).
- Chips: prioridad → Baja `text-on-surface-variant`, Media `text-primary`, Alta `bg-tertiary-fixed text-on-tertiary-fixed`, Urgente `bg-error-container text-on-error-container`; proyecto → pill neutra `bg-surface-container-low` con dot `bg-primary` (colores por proyecto = futuro); estado → dot/colores por estado (todo `bg-outline-variant`, in_progress `bg-primary`, in_review `bg-tertiary`, done `bg-outline` atenuado).
- Tipografía: título de página `text-headline-xl`; títulos de fila `text-body-md font-medium`; metas `text-label-xs`.
- Iconos Material Symbols vía `AppIcon`; avatares vía `Avatar`/`AvatarStack` (AppShell).

## 5. Behavior contract (interactions)

| Interacción                         | Comportamiento                                                                                                              |
| ----------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| `C` fuera de inputs (en Mis Tareas) | abre diálogo crear (listener capture de la página; el listener global del layout NO dispara)                                |
| `⌘F` / `Ctrl+F`                     | enfoca el buscador (preventDefault)                                                                                         |
| Búsqueda/filtros/pestañas           | client-side, combinan; contadores de pestañas sobre el conjunto filtrado por proyecto/responsable                           |
| Botón circular de estado            | menú con los 5 estados → `PUT tasks.update {status}`; la fila migra de grupo al re-render                                   |
| Drag kanban                         | drop en columna válida → mismo PUT; columna Bloqueado rechaza                                                               |
| `Añadir tarea rápida` (columna)     | diálogo con `initialStatus` de la columna                                                                                   |
| Click fila/tarjeta                  | abre el diálogo en modo edit                                                                                                |
| Diálogo                             | autofocus título; `⌘↵` guardar; `Esc`/click-fuera/X cancelar; doble envío bloqueado; "Crear otra" limpia y mantiene abierto |
| Eliminación                         | `confirm()` nativo → DELETE → flash en Mis Tareas                                                                           |
| Paleta ⌘K → "Crear nueva tarea"     | navega a `tasks.index?new=1` y abre el diálogo (reemplaza el destino anterior "Proyectos")                                  |
| Sidebar "Mis Tareas"                | item habilitado → `tasks.index`                                                                                             |
| Pestaña Completadas                 | lista terminadas (agrupadas igual); por defecto ocultas en grupos y kanban atenuadas                                        |
| Atajos con foco en input/textarea   | ningún atajo global dispara                                                                                                 |

## 6. Non-goals (explicit)

- Sin estados, tipos ni prioridades nuevas; sin columna Bloqueado funcional; sin subtareas, adjuntos, story points, due dates, clave legible, WIP, previsualización ni colores por proyecto (todo "Próximamente" + documentado en `data-model.md`).
- Sin paginación/virtualización server-side (client-side hasta varios cientos de tareas; futuro si aplica).
- Detalle de proyecto (Show) y sus componentes heredados sin cambios funcionales (solo la prop opcional del modal de comentarios).
- Rutas anidadas `projects.tasks.*` intactas.
- Sin dark mode; sin dependencias nuevas.
