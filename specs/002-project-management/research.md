# Research: Gestión de Proyectos

**Feature**: 002-project-management | **Date**: 2026-09-20

## R-1: Cálculo del porcentaje de avance — columna cacheada vs computada

**Contexto**: El boceto del README lista `progress_percentage` como
"computed or cached". FR-006 fija la fórmula (completadas/totales, entero).
El módulo de tareas no existe todavía (feature posterior), por lo que no hay
tabla `tasks` sobre la que calcular.

**Decision**: sin columna cacheada. Un único método en el modelo
(`progressPercentage(): int`) es el punto acotado del cálculo; mientras no
exista el módulo de tareas devuelve 0 (documentado en el propio método y en
data-model.md), y cuando el módulo de tareas aterrice la fórmula se implementa
en ese mismo método consultando la relación. Las vistas consumen siempre el
mismo método, así el cambio no toca la UI.

**Rationale**: una columna cacheada exige invalidarla en cada cambio de
tareas (escrituras futuras en otra feature) y en MVP la escala hace gratis el
cálculo en lectura. YAGNI (Principio V): no construir sincronización de caché
antes de que el volumen la justifique.

**Alternatives considered**:

- _Columna `progress_percentage` actualizada por eventos_: rechazada — peor
  relación complejidad/valor en MVP; staleness inevitable.
- _Crear la tabla `tasks` ya_: rechazada — scope creep; las tareas son su
  propia feature (README 2.4) con sprints, estados y prioridades.

## R-2: Propiedad y membresía — owner explícito + pivote

**Contexto**: FR-001/FR-005/FR-007/FR-008: un propietario exacto, cero o más
colaboradores, sin roles intermedios (spec, Assumptions).

**Decision**: columna `projects.owner_id` (FK a `users`, cascade) para el
propietario — exactamente uno, consultas simples, `Project::owner()` directo.
Colaboradores en pivote `project_user` (project_id, user_id, timestamps, único
compuesto). "Es miembro" = `owner_id === user->id` o existe fila en el pivote
(`$project->members()->whereKey($user->id)->exists()`).

**Rationale**: el propietario no es "uno más" en el pivote: tiene permisos
distintos y no puede retirarse (edge case de la spec). Una columna explícita
hace las autorizaciones de una sola comparación y evita estados inválidos
(proyecto sin propietario). El pivote cubre N-N de colaboradores sin tabla
adicional ni campos de rol vacíos.

**Alternatives considered**:

- _Todo en el pivote con `role` (owner/member)_: rechazada — exige restricción
  de "exactamente un owner" por software y complica cada comprobación de
  propiedad; el rol único de owner se modela mejor como FK.
- _Tabla `memberships` propia con id_: rechazada — sin atributos propios más
  allá del par (YAGNI); el pivote basta.

## R-3: Autorización — Policy convencional y 404 para no miembros

**Contexto**: FR-009: los no miembros no pueden ver ni listar el proyecto
"sin revelar la existencia". FR-004/005/007/008: solo el propietario edita,
cambia estado, gestiona colaboradores y elimina.

**Decision**: `app/Policies/ProjectPolicy.php` con `view` (miembro: owner o
pivote), `update`/`delete`/`manageMembers` (owner), `transition` (owner).
`ProjectController::show/edit/update/destroy` resuelven el modelo y llaman a
`$this->authorize(...)`; ante no miembros el controlador responde **404**
(abort(404) tras la comprobación de membresía, no 403) para no revelar
existencia. `index` filtra por membresía en consulta (projects owned OR en
pivote), nunca devuelve ajenos.

**Rationale**: Policy es el mecanismo idiomático (Principio II), centraliza
las reglas y se prueba en tests de feature de forma directa. 404 para
no-miembro iguala el comportamiento de "recurso inexistente", cumpliendo la
spec sin filtrar nada en las vistas.

**Alternatives considered**:

- _403 para no miembros_: rechazada — confirma la existencia del proyecto a
  usuarios ajenos, contradiciendo FR-009.
- _Gates sueltos sin Policy_: rechazada — misma lógica dispersa, peor
  testabilidad.

## R-4: Transiciones de estado — reglas en el enum

**Contexto**: FR-005: activo ↔ archivado y activo ↔ completado.

**Decision**: `ProjectStatus` (backed string) con el caso y método
`transitions(): array` que devuelve los estados destino permitidos:
active ⇒ [archived, completed]; archived ⇒ [active]; completed ⇒ [active].
La acción de transición valida que el destino esté permitido (422/redirect con
error si no). El controlador expone un único endpoint de actualización de
estado (PUT con `status`) reutilizado por los botones de la ficha.

**Rationale**: las reglas de máquina de estado viven junto al tipo
(Principio IV), son comprobables en tests unitarios del enum y la Policy no se
infla con lógica de dominio.

## R-5: Estructura de páginas y rutas

**Contexto**: US1–US4 requieren listado, alta, ficha (con miembros y acciones
de estado) y edición.

**Decision**: recurso completo `Route::resource('projects', ProjectController)`
(menos create/show ajustados según convención) + `projects/{project}/members`
anidado (POST/DELETE) con `ProjectMemberController`. Páginas:
`Projects/Index` (lista con avance y estado, enlace de alta),
`Projects/Create` y `Projects/Edit` (formularios), `Projects/Show` (ficha con
miembros, formulario de alta de colaborador por correo, botones de transición
según `ProjectStatus::transitions()` y eliminación con confirmación). Todo en
`AuthenticatedLayout`, navegación con Wayfinder, UI en español (convención de
la feature 001). El índice añade un enlace "Proyectos" en la navegación del
layout (modificación puntual de `AuthenticatedLayout.vue`).

**Rationale**: resource routing es la convención del framework; cuatro páginas
cubren las cuatro historias sin modales complejos; mantener la gestión de
miembros en la ficha evita una página extra (YAGNI).

## Resolución de NEEDS CLARIFICATION

El Technical Context no requirió marcadores: stack y versiones verificadas en
el repo durante la feature 001 (Laravel 13.17, Inertia 3.3.4, Pest 5), sin
dependencias nuevas, y las decisiones de diseño quedan fijadas en este
documento. Todos los unknowns resueltos.
