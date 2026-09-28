# Research: Rediseño del flujo de tareas (009)

**Fase 0** — hechos del codebase (exploración) y decisiones de diseño. Sin NEEDS CLARIFICATION pendientes; sin dependencias nuevas.

## Hechos que condicionan el diseño

- `tasks.project_id` es **NOT NULL** (migración, modelo, factory, form requests y tests) y las rutas de tareas son **solo anidadas** al proyecto; no existe ruta ni controlador global de tareas.
- `TaskPolicy` firma `(User, Project)` — no recibe la tarea; para tareas sin proyecto la política necesita re-anclarse al `Task`.
- `TaskModal.vue` actual es **solo edición** y está sin tokens (gris Breeze); la creación en el detalle de proyecto es un form inline. Los demás componentes de `Components/Tasks/` también son grises.
- El kanban existente usa **DnD nativo HTML5** (sin librería) y emite `moved`; el padre hace el PUT con todos los campos.
- `UpdateTaskRequest` exige `type/priority/status` (todos requeridos) — incómodo para cambios de estado por arrastre o círculo.
- `TaskCommentsModal` recibe la tarea con `comments` embebidos y postea a la ruta anidada; no sirve tal cual para tareas sin proyecto.
- No existe query reutilizable "tareas visibles del usuario"; los payloads se mapean a mano (sin API Resources).
- El shell tiene "Mis Tareas" en el nav con `to: null` (deshabilitado) y la acción de paleta "Crear nueva tarea" navega a Proyectos.

## D1. Tareas independientes: migración mínima

- **Decision**: `project_id` → `nullable()` en `tasks` (única columna). `Task::project()` pasa a `BelongsTo` nullable; PHPDoc/factory actualizados. El resto de la app (progreso de proyectos, listados, policies de proyecto) trata `project === null` como "no aplica".
- **Rationale**: decisión de alcance del usuario (Q1: A); es el cambio más pequeño que habilita toda la superficie "Sin Proyecto".
- **Alternativas**: tabla separada de tareas personales (rechazada: duplica modelo, rutas y UI); mantener obligatorio y simular (rechazada: viola SC-003).

## D2. Regla de permisos para tareas sin proyecto — sin columnas extra

- **Decision**: tarea sin proyecto puede **actualizarla su responsable** (`assignee_id === user`) y **eliminarla también su responsable**. El diálogo de creación de tareas independientes **solo permite comoignarse** (opción "Yo"), con `assignee` por defecto = usuario actual; así nunca queda una tarea independiente sin dueño. `TaskPolicy` se re-ancla a `(User, Task)`: si `task.project` existe → reglas actuales (miembro/owner + proyecto activo); si no → regla de responsable.
- **Rationale**: YAGNI (Principio V) — se descartó añadir `created_by`; la responsabilidad implícita cubre el flujo completo y el modal ya precisa un valor por defecto usable.
- **Alternativas**: columna `created_by` + regla creador/responsable (rechazada: columna extra sin otro consumidor hoy); cualquier usuario autenticado edita standalone (rechazada: rompe el modelo de permisos existente).

## D3. Rutas globales desacopladas (con coexistencia)

- **Decision**: nuevas rutas `Route::prefix('tasks')`: `GET /tasks` (página), `POST /tasks`, `PUT /tasks/{task}`, `DELETE /tasks/{task}`, `GET /tasks/{task}` (JSON con comentarios), `POST /tasks/{task}/comments`. Controlador nuevo `TaskController`. Las rutas anidadas existentes se conservan intactas para el detalle de proyecto (cero cambios en Show).
- **Rationale**: el detalle de proyecto no se toca (assumption de la spec); el alcance global exige endpoints no anidados.
- **Alternativas**: reescribir las anidadas (rechazada: rompería Show y sus tests sin beneficio); solo reutilizar anidadas con `project` opcional en URL (rechazada: no hay proyecto que poner).

## D4. Form request global con actualización parcial

- **Decision**: `SaveTaskRequest` única: en store exige `title`; `project_id` nullable + validación condicional (`when` existe → `assignee_id`/`sprint_id` deben pertenecer a ese proyecto; `when` null → `assignee_id` debe ser el usuario y `sprint_id` prohibido). En update todos los campos `sometimes` — el drag/círculo envía solo `{status}` y el modal envía todo.
- **Rationale**: evita duplicar requests; habilita cambios de estado ligeros (FR del círculo y el arrastre) sin pedir campos irrelevantes.
- **Alternativas**: dos requests (store/update) (rechazada: duplicación); seguir exigiendo todos los campos en update (rechazada: fuerza al frontend a enviar el task entero por un cambio de estado).

## D5. Comentarios sin proyecto: endpoint de detalle + modal retrocompatible

- **Decision**: `GET /tasks/{task}` devuelve JSON de la tarea con `comments` (autorizado si la tarea es visible). `TaskCommentsModal` gana **prop opcional** `comments`/`useGlobal?: { storeUrl }`; si está, lista los comentarios recibidos por prop y postea a la URL global; si no, comportamiento actual (Show intacto).
- **Rationale**: el payload de la página no incluye comentarios completos (peso); el modal los carga al abrir vía el endpoint JSON. Retrocompatibilidad sin tocar Show.
- **Alternativas**: embeber comentarios en el payload de la página (rechazada: peso innecesario y empeora con el volumen); modal nuevo duplicado (rechazada: dos componentes casi iguales).

## D6. Atajo `C`: el layout gana al page

- **Decision**: el listener global del layout convierte `C` en "abrir paleta". En Mis Tareas, la página registra su propio listener en **fase de captura** que, fuera de inputs y con el diálogo cerrado, hace `stopPropagation` y abre el diálogo de creación. La acción de paleta "Crear nueva tarea" pasa a navegar a `tasks.index?new=1` y la página auto-abre el diálogo (patrón `?new=1` de la feature 008).
- **Rationale**: cumple FR-009/`C` en la página y mantiene `C` = paleta en el resto de la app (comportamiento del shell heredado de 007).
- **Alternativas**: cambiar el shortcut global del layout (rechazada: rompe la paridad del shell); que `C` abra siempre la paleta también en Mis Tareas (rechazada: contradice la spec).

## D7. Agrupamiento y filtros client-side; terminadas ocultas por defecto

- **Decision**: el servidor entrega todas las tareas visibles (`BoardTask[]` con `commentsCount` vía `withCount`, sin cuerpos de comentario) y `projects` del usuario; agrupamiento, búsqueda, pestañas y filtros se resuelven **client-side** (patrón 008). Reglas exactas: grupos solo con tareas **no terminadas** (Alta/Urgentes; En Curso = in_progress/in_review; Sin Proyecto; Próximas & Backlog = resto con proyecto); la pestaña "Completadas" lista terminadas (agrupadas igual); la columna kanban "Terminado" las muestra atenuadas. Sin paginación server-side en esta feature (FR-015 queda satisfecho hasta varios cientos de tareas; se documenta la paginación como futuro si el volumen lo exige).
- **Rationale**: interactividad instantánea, sin endpoints nuevos; reglas deterministas y testables documentadas en el contrato.
- **Alternativas**: paginación/filtrado server-side (rechazada: complejidad sin beneficio a escala MVP).

## D8. Componentes nuevos en `Components/Tasks/Board/` (heredados intactos)

- **Decision**: la página global construye sus componentes con tokens (`TaskRow`, `TaskGroupSection`, `TaskBoard`, `TaskBoardColumn`, `TaskBoardCard`, `TaskFilters`, `TaskFormModal`). Los componentes grises heredados quedan para el detalle de proyecto esta feature.
- **Rationale**: bloquea el alcance (spec: detalle de proyecto "se mantiene como está"), evita regresiones en Show y permite iterar el diseño nuevo sin dobles responsabilidades.
- **Alternativas**: reescribir los heredados y migrar Show (rechazada: fuera de alcance, mayor riesgo); duplicar en otra carpeta raíz (rechazada: `Board/` como subcarpeta respeta la convención existente).

## D9. Kanban: columnas del dominio + "Bloqueado" deshabilitado

- **Decision**: columnas reales = Por Hacer, En Curso, En Revisión, Terminado (los 4 estados no-backlog; el backlog se gestiona en la vista lista). La columna "Bloqueado" del diseño se renderiza **deshabilitada** con chip "Próximamente" y no acepta drops. DnD nativo (dragstart/dragover/drop) copiando el patrón existente; drop → `PUT /tasks/{id}` con `{status}` (D4).
- **Rationale**: fidelidad al diseño sin inventar estados (constitución IV); la referencia lista tareas backlog en la vista lista, no en el kanban.
- **Alternativas**: añadir estado Blocked (rechazada: cambio de dominio no pedido); omitir la columna Bloqueado (rechazada: la spec pide mostrarla como "Próximamente").

## D10. Payload `BoardTask` y fuentes de filtros

- **Decision**: `BoardTask = { id, title, description|null, type, typeLabel, priority, priorityLabel, status, statusLabel, project: {id,title}|null, sprint: {id,name}|null, assignee: {id,name,avatar}|null, commentsCount }`. Page props: `tasks: BoardTask[]`, `projects: {id,title}[]`. Opciones del filtro "Responsable": responsables presentes en el payload + el usuario (dedup). Vista kanban/lista persistida en `localStorage('tasks.view')`.
- **Rationale**: datos suficientes para todas las vistas y filtros sin peso extra; los filtros por proyecto alimentan también el modal.
- **Alternativas**: incluir miembros de todos los proyectos para el filtro responsable (rechazada: el conjunto real de responsables visible es suficiente y más barato).
