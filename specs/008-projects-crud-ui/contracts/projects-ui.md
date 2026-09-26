# Contract: Projects UI (directorio, diálogo y detalle)

**Feature**: 008-projects-crud-ui
**Date**: 2026-09-25
**Type**: UI contract — define las props de Inertia, las interfaces de los componentes de `Components/Projects/` y el comportamiento interactivo del flujo de proyectos. Integra con el contrato del shell (007): mismo layout, mismos tokens, misma paleta de comandos.

## 1. Shared props contract (server → layout)

`HandleInertiaRequests` añade, para todas las respuestas:

```ts
page.props.flash = {
    success: string | null; // mensaje de confirmación (redirect ->with('success'))
    error: string | null;   // mensaje de error global
};
```

Las props existentes (`name`, `auth`, `projects: ProjectSummary[]`) no cambian.

## 2. Page contracts

### 2.1 `Projects/Index` — directorio

```ts
defineProps<{
    projects: DirectoryProject[]; // orden: updated_at desc ("Recientes")
    counts: { active: number; completed: number; archived: number };
    kpis: { activeProjects: number; completedSprintsThisQuarter: number };
}>();

interface DirectoryProject {
    id: number;
    title: string;
    description: string | null;
    status: 'active' | 'archived' | 'completed';
    statusLabel: string; // 'Activo' | 'Archivado' | 'Completado'
    progress: number; // 0-100, fórmula data-model.md §Progreso
    taskDoneCount: number;
    taskTotalCount: number;
    activeSprint: {
        id: number;
        name: string;
        startDate: string;
        endDate: string;
    } | null;
    owner: { id: number; name: string; avatar: string | null };
    members: { id: number; name: string; avatar: string | null }[]; // excluye owner
    isOwner: boolean;
}
```

Apertura del diálogo por query param: `?new=1` → crear; `?edit=<id>` → editar (usa el proyecto del payload). Al abrir, la página limpia el query con `router.replace`.

### 2.2 `Projects/Show` — detalle

Prop `project` existente **ampliada** (shape completo en `types/project.ts`):

- Añadidos: `activeSprint` (mismo tipo que arriba), `taskDoneCount`, `taskTotalCount`.
- `owner` y `members[]` ganan `avatar: string | null`.
- `tasks[].assignee` gana `avatar: string | null`.
- Sin cambios: `sprints[]`, `tasks[]` (TaskItem de `Components/Tasks/types.ts`), `allowedTransitions: string[]`, `isOwner`, `status`, `statusLabel`, `progress`.

Apertura del diálogo de edición con `?edit=1` (limpieza con `router.replace`).

### 2.3 Redirects (deep-linking)

| Ruta GET                   | Comportamiento                      |
| -------------------------- | ----------------------------------- |
| `projects.create`          | redirect → `projects.index?new=1`   |
| `projects.edit` (líder)    | redirect → `projects.show?edit=1`   |
| `projects.edit` (no líder) | 404 (invariante de tests existente) |

## 3. Component interfaces (`resources/js/Components/Projects/`)

### `UiBadge.vue`

```ts
defineProps<{
    label: string;
    tone?: 'primary' | 'neutral' | 'error' | 'tertiary' | 'outline'; // default 'neutral'
    dot?: boolean; // renders a leading status dot
}>();
// chip primitivo: contadores de píldora, estados, marca "Próximamente"
```

### `FilterPills.vue`

```ts
defineProps<{
    counts: { active: number; completed: number; archived: number };
    modelValue: 'all' | 'active' | 'completed' | 'archived';
}>();
// emite update:modelValue; "Todos" = suma de counts; pill activa = tono primary
```

### `ProjectsToolbar.vue`

```ts
defineProps<{ total: number; sort: SortKey; view: 'grid' | 'list' }>();
type SortKey = 'recent' | 'alpha' | 'progress';
// emite: update:sort, update:view, search(term), create
// buscador con kbd ⌘F (focus global preventDefault), select de ordenamiento (menú custom),
// conmutador vista segmentado, botón "Nuevo Proyecto" con kbd N
```

### `ProjectCard.vue` / `ProjectListRow.vue`

```ts
defineProps<{ project: DirectoryProject; view context actions }>();
// card (grid): borde superior h-1 tono primary, icono identidad neutro (folder), key placeholder "Próximamente",
//   título, descripción line-clamp-2, fila estado+progreso "(done/total)", barra h-1.5, fila sprint/fecha+AvatarStack, menú ⋯
// row (list): densidad compacta, mismos datos en una línea adaptable
// click en la tarjeta/fila → projects.show (la región del menú ⋯ no navega)
```

### `ProjectContextMenu.vue`

```ts
defineProps<{ project: DirectoryProject }>();
// items para líder: Editar Proyecto; Archivar o Completar (según estado); Reactivar (si no activo); Ver detalle
// items para miembro: Ver detalle (y Editar solo si isOwner — server reforzará)
// acciones de estado: PUT projects.status con transición elegida; feedback por flash/error
```

### `ProjectFormModal.vue` (create + edit)

```ts
defineProps<{
    open: boolean;
    mode: 'create' | 'edit';
    project?: { id: number; title: string; description: string | null };
}>();
// emite close (ESC, X, cancelar, click fuera, éxito)
// campos operativos: Nombre (requerido, max 255), Descripción breve (opcional)
// sección "Identidad visual (Próximamente)": Clave, Color, Icono, Fecha objetivo — disabled + nota
// modo edit + isOwner implícito (solo se monta para el líder): zona de peligro "Eliminar proyecto" → confirm() → DELETE
// useForm: post store.url() / put update.url(id); errores inline por campo; botón deshabilitado en processing
```

### `AvatarStack.vue`

```ts
defineProps<{
    people: { id: number; name: string; avatar: string | null }[];
    max?: number; // default 3
}>();
// avatares solapados -space-x-1.5, overflow "+N", fallback iniciales vía AppShell Avatar
```

### `ProjectsKpiPanel.vue`

```ts
defineProps<{
    kpis: { activeProjects: number; completedSprintsThisQuarter: number };
}>();
// 2 KPIs reales + 2 tarjetas "Próximamente" (Entregas a tiempo, Velocidad de equipo) — sin sparkline simulado
```

### `ProjectEmptyState.vue`

Sin props. Ilustración neutra + "Iniciar un nuevo proyecto" (abre diálogo). Se muestra cuando `projects.length === 0`.

### `ProjectTabs.vue` (detalle)

```ts
defineProps<{ modelValue: 'general' | 'sprints' }>();
// tabs: General y resumen | Sprints y tareas (14)   ← activas
//       Documentación | Recursos y enlaces | Historial ← disabled + chip "Próximamente"
```

### `FlashMessage.vue` (`Components/AppShell/`)

```ts
defineProps<{ message: string | null; tone: 'success' | 'error' }>();
// banner dismissible sobre <main>, role="status" (success) / role="alert" (error); auto-cierre NO (dismiss manual)
```

## 4. Visual contract (tokens — mismos que 007)

- Superficies: tarjetas/paneles `bg-surface-container-lowest shadow-card rounded-xl`; diálogo `shadow-modal rounded-xl` sobre veil `bg-inverse-surface/20 backdrop-blur-sm` (vía `Modal.vue` reestilizado).
- Énfasis: todo el acento en `primary` (sin color por proyecto en esta iteración).
- Mapa de estado: `Active` → `primary` (dot/badge); `Completed` → `tertiary`; `Archived` → `outline-variant`.
- Barra de progreso: track `bg-surface-container`, fill `bg-primary`, altos h-1.5 (tarjeta) / h-2 (detalle).
- Tipografía: `text-headline-xl` título de página; `text-headline-sm` títulos de tarjeta; `text-label-xs` metas/chips; `text-body-sm` descripciones.
- Marca "Próximamente": chip `UiBadge tone="outline"` junto al control/sección deshabilitada (`opacity` reducida, `cursor-not-allowed`, nunca enfocable).
- KPI "Próximamente": tarjeta con icono `schedule`/`bolt` en tono outline y label "Próximamente".

## 5. Behavior contract (interactions)

| Interacción                             | Comportamiento                                                                              |
| --------------------------------------- | ------------------------------------------------------------------------------------------- |
| `N` fuera de inputs (directorio)        | abre diálogo crear (`preventDefault`)                                                       |
| `⌘F` / `Ctrl+F` (directorio)            | enfoca el buscador (`preventDefault`)                                                       |
| Búsqueda                                | filtra por `title` (case-insensitive), inmediata                                            |
| Píldoras / orden / vista                | client-side; vista persistida en `localStorage('projects.view')`                            |
| Click tarjeta/fila                      | navega a `projects.show` (menú ⋯ excluido)                                                  |
| Menú ⋯                                  | acciones según `status` + `isOwner`; cierre con ESC/click fuera                             |
| Diálogo crear/editar                    | autofocus en Nombre; ESC/click-fuera/X cierra; éxito → close + flash; doble envío bloqueado |
| Eliminación                             | `confirm()` nativo → DELETE → redirect index + flash                                        |
| Detalle: "Ajustes"                      | abre diálogo editar                                                                         |
| Detalle: "Compartir"                    | botón deshabilitado, tooltip "Próximamente"                                                 |
| Detalle: "Nuevo Sprint" / "Nueva Tarea" | cambia a pestaña "Sprints y tareas"                                                         |
| Paleta ⌘K (global, layout)              | nueva acción "Crear nuevo proyecto" → `projects.index?new=1`                                |
| Proyecto archivado/completado           | contenido solo lectura; CTA de reactivación visible para el líder                           |

## 6. Non-goals (explicit)

- Sin persistencia de clave, color, icono, fecha objetivo, prioridad ni área (propiedades futuras documentadas en `data-model.md`).
- Sin secciones de Documentación, Recursos ni Historial (pestañas futuras, solo marca "Próximamente").
- Sin métricas de salud/velocidad/entregas a tiempo (KPIs "Próximamente").
- Sin color de acento por proyecto, sin sparkline simulado, sin indicador "Sincronizado con Friday Cloud".
- Sin cambios en componentes `Tasks/*` (sprints/tareas preservados); sin `SprintStatus`; sin toasts de librería; sin dark mode.
- Gestión de miembros existente se conserva (tarjeta Equipo en "General y resumen"); la experiencia "Compartir" nueva es futura.
