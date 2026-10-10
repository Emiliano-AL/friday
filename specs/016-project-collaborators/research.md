# Research: Gestión de colaboradores del proyecto

**Feature**: `specs/016-project-collaborators`
**Date**: 2026-10-05
**Input**: spec.md + exploración del codebase (patrones de modal, endpoints de miembros, validación de asignados)

## Contexto técnico confirmado

- Stack: PHP 8.4 (Laravel 13), Inertia v3, Vue 3.5 + TS, Tailwind v4 (tokens en `resources/css/app.css`, solo claro), Wayfinder (`@/routes/`), Pest, PostgreSQL. Sin dependencias nuevas.
- **Endpoints de miembros ya existen y su contrato está fijado por tests** (`tests/Feature/Projects/ProjectMembersTest.php`, 7 tests verdes):
  - `POST /projects/{project}/members` con `{email}` → redirect `projects.show`; errores de sesión key `email` (duplicado, no registrado, propietario).
  - `DELETE /projects/{project}/members/{user}` → redirect `projects.show`; 404 si es el propietario o quien actúa no es propietario.
  - `ProjectPolicy::manageMembers` = solo propietario (`app/Policies/ProjectPolicy.php:45-48`).
- **La validación "el responsable debe ser colaborador del proyecto" YA EXISTE del lado del servidor** en los tres form requests (`StoreTaskRequest.php:37-47`, `UpdateTaskRequest.php:39-49`, `SaveTaskRequest.php:43-49` + `validateAssignee()`), con mensaje "El responsable debe ser miembro del proyecto." Usa `Project::isMember()` (propietario OR pivot), así que el propietario ya es asignable válido.
- **Gap real detectado (FR-008)**: `TaskController@index` construye el payload `projects[].members` **sin el propietario** (`TaskController.php:35-57`, solo pivot `members:id,name,avatar`); el frontend compensa inyectando `currentUser` (`TaskFormModal.vue:76-87`), pero el propietario ajeno no aparece como opción de asignación en la página Mis Tareas. `Projects/Show.vue` y `Sprints/Show.vue` sí arman opciones con owner+members.
- `Projects/Show.vue`: sección Equipo (`:666-753`) + **formulario inline a eliminar** (`:724-752`, `memberForm.post(storeMember.url(...))`); remover miembro hoy es un `router.delete` directo **sin confirmación** (`:191-195`); precedente de confirmación con `window.confirm` en sprints (`:197-207`). Flag de permiso: `project.isOwner` (no hay canManageMembers).
- Convenciones de modal: `Components/Modal.vue` (props `show`, `maxWidth` default `2xl`, `closeable`; emit `close`). Patrón canónico `SprintFormModal.vue`: props `{open, ...}`, `watch(() => props.open)` resetea form y foca, `useForm` + `preserveScroll`, `onSuccess` cierra. **Precedente "Próximamente" dentro de modal**: `SprintFormModal.vue:193-220` (contenedor `aria-disabled="true"` + icono lock + `UiBadge label="Próximamente" tone="outline"`); y botón disparador deshabilitado con badge en `Show.vue:338-347`.
- Payload actual de miembros (`ProjectController::projectPayload`, `:249-254`): `{id, name, email, avatar}` — **sin conteo de tareas abiertas** (necesario para la advertencia de FR-005).
- Diseño Stitch ("Friday — Modal Gestión de Colaboradores & Equipo del Proyecto"): header con nombre de proyecto; sección "Enlace de invitación rápida" + "Copiar enlace"; input "Invitar nuevos colaboradores" + "Enviar invitación"; tabla "Miembros actuales (N)" con columna "Permisos del proyecto" (Propietario / selector Editor·Administrador·Solo Lectura / icono eliminar); fila de invitación pendiente con "Reenviar"/"Cancelar"; footer informativo + botón "Listo".

## Decisiones

### D1 — Reutilizar los endpoints de miembros existentes sin cambios
- **Decisión**: El modal consume `projects.members.store` / `projects.members.destroy` exactamente como hoy el formulario inline (Inertia `post`/`delete` con `preserveScroll`; el redirect a `projects.show` refresca props). Cero cambios de backend en los endpoints.
- **Rationale**: los 7 tests del contrato siguen pasando sin tocarse; la funcionalidad ya es server-rendered Inertia (Principio I). El modal es una capa de presentación.
- **Alternativas**: nuevos endpoints JSON (rechazada: duplica contrato y rompe tests existentes); mutate sin redirect (rechazada: rompe el contrato de redirect que los tests validan).

### D2 — Componente nuevo `MembersModal.vue` siguiendo el patrón SprintFormModal
- **Decisión**: `resources/js/Components/Projects/MembersModal.vue` con props `{open, project}` (project = ProjectDetail ya cargado), emit `close`; `Show.vue` elimina el formulario inline y abre el modal con un botón "Gestionar colaboradores" en la tarjeta Equipo (visible solo `isOwner`, como hoy el formulario).
- **Rationale**: convención establecida del proyecto (un modal por dominio en `Components/<Dominio>/`), máxima reutilización de tokens y composables existentes.
- **Alternativas**: lógica embebida en Show.vue (rechazada: Show.vue ya tiene ~950 líneas); modal genérico dinámico (rechazada: sobre-diseño YAGNI).

### D3 — Conteo de tareas abiertas por miembro en el payload (para la advertencia FR-005)
- **Decisión**: `ProjectController::projectPayload` añade `openTasksCount` a `owner` y a cada `members[]`, calculado con **una sola consulta agregada** por proyecto (`tasks` del proyecto agrupadas por `assignee_id` con `status != Done`, excluyendo standalone) mapeada en PHP. Sin N+1, sin relación nueva en `User`.
- **Rationale**: FR-005 exige advertir "X tareas quedarán sin responsable" antes de eliminar; el dato es derivado (no se persiste). Una query agregada por proyecto es O(1) en miembros.
- **Alternativas**: `withCount` anidado por miembro (rechazada: restricción extra por miembro y requiere relación `assignedTasks` en User que solo serviría a esto); sin conteo (rechazada: incumple FR-005).

### D4 — Funciones no existentes: deshabilitadas con "Próximamente" (patrón existente)
- **Decisión**: siguiendo `SprintFormModal.vue:193-220`, el modal incluye las secciones del diseño no implementadas como bloques `aria-disabled="true"` con icono lock + `UiBadge "Próximamente"`: (a) selector de rol por miembro — se muestra el rol genérico actual del sistema ("Editor") con el dropdown deshabilitado; (b) "Enlace de invitación rápida" con "Copiar enlace" deshabilitado; (c) acciones "Reenviar"/"Cancelar" de invitación pendiente (la fila de pendiente solo aparece como ejemplo deshabilitado del diseño... — **corrección**: no se muestra fila de pendiente real porque el sistema no la tiene; el bloque de invitación pendiente se omite por completo y solo quedan (a) y (b) como secciones deshabilitadas más el footer honesto).
- **Rationale**: FR-007/FR-009; mostrar el diseño completo pero honesto. Omitir la fila "Pendiente" evita sugerir un estado que no existe; el campo de email ya comunica "usuario registrado" vía su mensaje de error.
- **Alternativas**: implementar roles/invitaciones (rechazada: fuera de alcance explícito del spec y del MVP); ocultar las secciones no implementadas (rechazada: el usuario pidió "si la función no existe la marcas deshabilitada").

### D5 — Lista de miembros: propietario primero, sin eliminar; confirmación con advertencia
- **Decisión**: el modal lista primero al propietario (badge "Propietario", sin acciones de eliminar) y después los colaboradores del pivot (avatar/iniciales, nombre, correo, conteo de tareas abiertas, botón retirar). Eliminar usa `window.confirm` (precedente sprints) y el texto incluye la advertencia cuando `openTasksCount > 0` ("Esta persona tiene N tareas abiertas en el proyecto que quedarán sin responsable."). Tras eliminar, el modal permanece abierto; la lista se actualiza por el redirect de Inertia (props nuevas). "Listo" cierra.
- **Rationale**: FR-002/FR-005; `window.confirm` es el patrón de confirmación existente en el proyecto (no hay componente de confirmación dedicado — crearlo violaría YAGNI).
- **Alternativas**: componente ConfirmDialog nuevo (rechazada: YAGNI, un solo uso); cierre automático tras eliminar (rechazada: gestión multi-miembro es más fluida con el modal abierto).

### D6 — Cierre del gap de asignación: propietario incluido en las opciones
- **Decisión**: (1) `TaskController@index` incluye al propietario en `projects[].members` (payload colaboradores = owner + pivot, deduplicado); (2) verificar e igualar `Sprints/Show.vue` taskContext para que `members` incluya al propietario; (3) `TaskFormModal` queda sin cambios (ya deduplica). Tests: el propietario aparece en las opciones de asignación disponibles.
- **Rationale**: completa FR-008/SC-003 con cambio mínimo (la validación de servidor ya acepta al propietario); elimina el workaround asimétrico de inyectar solo `currentUser`.
- **Alternativas**: dejar el workaround (rechazada: el propietario seguiría no siendo asignable desde Mis Tareas, incumple FR-008); validación nueva en servidor (rechazada: ya existe).

### D7 — Sin cambios de esquema ni enums nuevos
- **Decisión**: ninguna migración. No se añade columna de rol, tabla de invitaciones ni sistema de notificaciones: todo eso está deshabilitado por diseño (D4).
- **Rationale**: Principio V; las funciones deshabilitadas son declaraciones de intención, no implementaciones parciales.

### D8 — Tipos TypeScript
- **Decisión**: `resources/js/types/project.ts`: `ProjectDetail.owner` y `members[]` ganan `openTasksCount: number`; props del modal tipadas desde `ProjectDetail`. `TaskProjectOption` no cambia.
- **Rationale**: contrato tipado extremo a extremo, consistente con `resources/js/types/*` existentes.

### D9 — Verificación
- **Decisión**: Pest feature tests nuevos en `tests/Feature/Projects/`: `ProjectMembersModalPayloadTest` (payload incluye owner+members con `openTasksCount` correcto incluyendo conteo de tareas no-Done del proyecto) y `ProjectAssigneeOptionsTest` (el payload de opciones de tareas incluye al propietario; la validación de servidor rechaza asignado externo — regresión del comportamiento existente). Tests existentes de miembros (7) deben seguir verdes intactos. QA frontend: `npm run check` (vue-tsc).
- **Rationale**: Principio III; se cubren los comportamientos nuevos (payload y gap de asignación), no se duplican los tests del contrato de endpoints.

### D10 — Footer honesto
- **Decisión**: el pie del modal dice únicamente: "Los colaboradores podrán recibir tareas asignadas de este proyecto." — sin promesas de correos ni menciones.
- **Rationale**: FR-009.

## NEEDS CLARIFICATION
Ninguno pendiente.
