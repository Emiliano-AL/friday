# Contrato UI: Flujo de Sprint en la UI (010)

Contrato entre backend (Inertia) y frontend (Vue) para el flujo de sprint.
Formato: rutas + shapes de props. Convenciones del proyecto: Wayfinder
(`@/routes/projects/sprints`), tokens Tailwind de `DESIGN.md`, textos en español.

## 1. Rutas

| Método | Ruta | Nombre | Acceso | Respuesta |
| --- | --- | --- | --- | --- |
| GET | `projects/{project}/sprints/{sprint}` | `projects.sprints.show` | miembro del proyecto (no miembro → 404) | página `Sprints/Show` |
| POST | `projects/{project}/sprints` | `projects.sprints.store` | propietario + proyecto activo | redirect `projects.show` + flash |
| PUT | `projects/{project}/sprints/{sprint}` | `projects.sprints.update` | propietario + proyecto activo + sprint no completado | redirect back + flash |
| DELETE | `projects/{project}/sprints/{sprint}` | `projects.sprints.destroy` | propietario + proyecto activo + sprint no completado | redirect `projects.show` + flash |
| POST | `projects/{project}/sprints/{sprint}/start` | `projects.sprints.start` | propietario + proyecto activo + sprint `planned` | redirect back + flash |
| POST | `projects/{project}/sprints/{sprint}/complete` | `projects.sprints.complete` | propietario + proyecto activo + sprint `active` | redirect back + flash |

Errores de transición/validación: mensaje en español claro (p. ej. "El sprint ya
está completado y es de solo lectura.", "Solo el propietario puede gestionar los
sprints.", "El proyecto no admite cambios en su estado actual.").

## 2. Props de `Sprints/Show`

```ts
SprintStatusValue = 'planned' | 'active' | 'completed';

interface SprintDetail {
    id: number;
    name: string;
    goal: string | null;
    status: SprintStatusValue;
    statusLabel: string;          // Planificado | Activo | Completado
    startDate: string;            // Y-m-d
    endDate: string;              // Y-m-d
    iteration: { current: number; total: number };
    daysRemaining: number;        // laborables, >= 0
    percentElapsed: number;       // 0..100
    canWrite: boolean;            // propietario && proyecto activo && status !== 'completed'
    project: { id: number; title: string };
}

interface SprintMetrics {
    total: number;
    done: number;
    coverage: number;             // 0..100
    breakdown: Array<{
        status: 'todo' | 'in_progress' | 'in_review' | 'done';
        label: string;
        count: number;
        percent: number;          // 0..100
    }>;                            // omite estados con count 0
}

interface SprintTaskContext {
    project: { id: number; title: string };
    sprints: Array<{ id: number; name: string }>; // sprints del proyecto (para el select del modal)
}

props: {
    sprint: SprintDetail;
    metrics: SprintMetrics;
    tasks: BoardTask[];           // shape existente de feature 009 (types/task.ts)
    taskContext: SprintTaskContext;
    canWrite: boolean;            // alias de sprint.canWrite (para slots)
    flash?: { success?: string };
}
```

## 3. Secciones de la página `Sprints/Show`

1. **Migas**: Proyectos / {proyecto} / Sprints / {sprint} (enlaces reales).
2. **SprintHeader**: nombre (headline), chip de estado (tono por status), "Iteración N de M", rango de fechas, días laborables restantes + % transcurrido, objetivo (si existe). Acciones según `canWrite` y `status`: Editar (modal), Añadir Tarea (modal de tarea con proyecto+sprint preseleccionados), Activar (solo `planned`), Completar Sprint (solo `active`, con confirmación). Sin `canWrite`: solo lectura, sin botones.
3. **SprintMetricsPanel**: cobertura (circular o barra + "X de Y"), desglose por estado (lista con contador y %), total. Datos exactos de `metrics`; sin tareas → ceros comprensibles + empty state.
4. **SprintTaskList**: buscador (placeholder + ⌘F/Ctrl+F enfoca), filtro Responsable, filtro Tipo, agrupación conmutada Estado/Prioridad; grupos con contador; filas reutilizando el patrón de fila de tarea (icono tipo, título, prioridad, estado, responsable, comentarios); estados vacíos (sin tareas / sin coincidencias con acción "limpiar"). Click en tarea → `TaskFormModal` en modo edit (mismo comportamiento que Mis Tareas); menú de acciones (editar/cambiar estado/comentarios/eliminar) coherente con Mis Tareas vía endpoints globales `tasks.*`.
5. **Columna lateral "Próximamente"**: tarjetas deshabilitadas (sin foco, sin datos simulados, chip "Próximamente"): Story Points / Burn-up / Velocidad, Burndown (línea ideal vs. real), Notas del Daily, Bitácora del Sprint, Carga por Miembro, grupo Bloqueadas, clave legible y % por tarea.

## 4. `SprintSummary` extendido (payload de proyecto)

`resources/js/types/project.ts`:

```ts
interface SprintSummary {
    id: number;
    name: string;
    startDate: string;
    endDate: string;
    status: SprintStatusValue;    // NUEVO
    statusLabel: string;          // NUEVO
    goal: string | null;          // NUEVO
}
```

Uso: pestaña Sprints de `Projects/Show.vue` (cada fila enlaza a
`projects.sprints.show`; chip de estado visible; Editar/Eliminar solo con permiso
y sprint no completado) y panel "Sprint activo" (ahora por status, mismo shape).

## 5. `SprintFormModal` (crear/editar)

Props: `project: {id,title}`, `sprint?: SprintSummary` (modo edit), `open`.
Campos: nombre (autofocus, obligatorio), objetivo (textarea opcional, hint de
"valor clave entregable"), fecha inicio, fecha fin (validación fin ≥ inicio en
línea), y **solo en modo crear**: switch "Comenzar inmediatamente" con nota
"Estado por defecto: Planificado". Footer: Cancelar / Crear Sprint (o Guardar
cambios). ESC y click-fuera cierran; doble envío bloqueado.

## 6. Extensión de `TaskFormModal` (reuso)

Props nuevos opcionales: `initialProject?: {id,title} | null`,
`initialSprint?: {id,name} | null`. Cuando se pasan, los selectores Proyecto/Sprint
arrancan preseleccionados y bloqueados al proyecto del contexto (el select de
sprint sigue permitiendo "Sin sprint"). Comportamiento por defecto inalterado
(Mis Tareas sigue igual).

## 7. Non-goals (explícitos)

Sin estados/tipos/prioridades nuevas de tarea; sin story points ni métricas de
puntos; sin burndown real; sin vista transversal de sprints (sidebar global
permanece "Próximamente"); sin diálogo nuevo de tarea; sin notificaciones. Detalle
de proyecto (Show) y Mis Tareas siguen verdes (regresión).
