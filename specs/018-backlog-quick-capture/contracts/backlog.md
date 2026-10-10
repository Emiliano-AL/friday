# Contract: Página Backlog (`/backlog`)

**Fecha**: 2026-10-08 | **Feature**: [spec.md](spec.md) | **Data model**: [data-model.md](data-model.md)

Contrato de la interfaz de usuario de la aplicación web (monolito Inertia).
Toda mutación fluye por rutas web existentes con sesión; Wayfinder expone los
helpers tipados en `@/routes/*`.

## Rutas

| Método | URI | Nombre | Uso en esta feature |
| --- | --- | --- | --- |
| GET | `/backlog` | `backlog.index` | Renderiza `pages/Backlog/Index.vue` con las props de abajo. |
| POST | `/tasks` | `tasks.store` | Captura rápida: `{ title, status: 'backlog', priority?, type?, project_id? }`. Reutilizado; sin cambios de ruta. |
| PUT/PATCH | `/tasks/{task}` | `tasks.update` | Triage: proyecto, prioridad, puntos, responsable, sprint (entrega). Reutilizado. |
| DELETE | `/tasks/{task}` | `tasks.destroy` | Eliminación con confirmación. Redirige de vuelta a la página origen. |
| GET | `/tasks/{task}` | `tasks.show` | JSON del detalle (comentarios), si el panel lo necesita. |

Propiedad de los datos: `tasks.store` y `tasks.update` autorizan vía
`TaskPolicy` (autor/responsable para standalone; miembro+activo para
proyecto). `404` si el recurso no es visible (no filtra existencia).

## Props de la página `Backlog/Index` (GET /backlog)

```ts
type BacklogTask = {
  id: number;
  code: string | null;            // 'FRN-104'
  title: string;
  description: string | null;
  type: 'bug' | 'feature' | 'test' | 'other';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  status: 'backlog' | 'todo' | 'in_progress' | 'in_review' | 'done';
  points: number | null;
  completedAt: string | null;     // ISO 8601
  createdAt: string;              // ISO 8601
  project: { id: number; title: string } | null;
  sprint: { id: number; name: string; status: 'planned' | 'active' | 'completed' } | null;
  assignee: { id: number; name: string; avatar: string | null } | null;
  authorId: number | null;
  canUpdate: boolean;
  canDelete: boolean;
};

type BacklogPageProps = {
  tasks: BacklogTask[];                       // ya filtradas por visibilidad (scopeVisibleTo + scopeBacklog)
  summary: {
    total: number;
    totalPoints: number;
    withoutProject: number;                   // conteo filtro 'Sin proyecto'
    withProject: number;                      // conteo filtro 'Con proyecto'
  };
  nextSprint: {
    id: number;
    name: string;
    projectId: number;
    projectTitle: string;
    status: 'planned' | 'active';
    startsAt: string;                         // Y-m-d
    endsAt: string;                           // Y-m-d
  } | null;
  projects: TaskProjectOption[];              // misma forma que entrega TaskController@index
  flash?: { message?: string };
};
```

## Interacciones del contrato UI

### Captura rápida (FR-002…FR-006, SC-001)
- Componente `QuickCapture`: un campo de texto con foco automático; Enter
  dispara `tasks.store` con **solo** `title` + `status: 'backlog'` (+ `priority`
  y/o `project_id` si el usuario los eligió en los chips en línea).
- Respuesta: redirect Inertia a `backlog.index` con la lista actualizada; el
  nuevo pendiente encabeza su sección (orden por `createdAt` desc).
- Errores de validación (`title` vacío): `errors.title` renderizado junto al
  campo; nada se persiste.
- Doble Enter: `form.processing` bloquea el segundo envío.

### Lista, secciones y filtros (FR-007…FR-009)
- Agrupación client-side por prioridad en tres secciones fijas: **Alta y
  urgente** (`high`, `urgent`), **Media** (`medium`), **Baja e ideas** (`low`);
  dentro de cada una, `createdAt` descendente.
- Filtros con conteos desde `summary`: Todas (`total`) / Sin proyecto
  (`withoutProject`) / Con proyecto (`withProject`).
- Encabezado: "N pendientes · M pts" desde `summary`.
- Estado vacío: sin tareas → invitación a capturar; filtro sin resultados →
  mensaje específico del filtro.

### Triage (FR-010…FR-013)
- Click en fila → `TaskFormModal` (modo edición) con el payload `BacklogTask`.
- Guardar → `tasks.update`; la respuesta refresca props y la fila refleja los
  cambios (sección/filtro incluidos).
- `canDelete` habilita la acción de borrado con confirmación → `tasks.destroy`,
  redirect de vuelta al backlog.

### Entrega (FR-012)
- En el modal de edición, la vinculación a sprint solo lista sprints del
  proyecto asignado (activos o planificados).
- `NextSprintBanner` muestra `nextSprint` y enlaza a la planificación.

## Invariantes verificables

1. Toda tarea listada cumple: `sprint === null && status !== 'done'` y es
   visible para el usuario (autor/responsable/miembro).
2. `summary.total === tasks.length`, `summary.withoutProject +
   summary.withProject === summary.total`.
3. Toda tarea capturada desde el composer queda con `status === 'backlog'` y
   `authorId === usuario actual`.
4. Después de completar un sprint, ninguna tarea del mismo permanece con
   `sprint_id` apuntándolo salvo las `status === 'done'`.
