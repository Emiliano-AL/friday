# Research: Backlog General — Captura Rápida de Pendientes

**Fecha**: 2026-10-08 | **Feature**: [spec.md](spec.md) | **Plan**: [plan.md](plan.md)

Todas las áreas técnicas que podían quedar como "NEEDS CLARIFICATION" se
resolvieron inspeccionando el código existente (Laravel 13.32, PHP 8.4,
PostgreSQL, Inertia v3 + Vue 3, Pest). No quedan clarificaciones pendientes.

## Baseline

- 2026-10-08: `php artisan test --compact` → 224 passed, 0 fallos (1556 assertions, ~6 s). Punto de partida verde.

## Hallazgos del código existente (base de las decisiones)

- `tasks.project_id` **ya es nullable** (`2026_09_28_125137_make_tasks_project_id_nullable.php`)
  y existe el flujo "standalone" completo: `TaskFactory::standalone()`,
  `tests/Feature/Tasks/TaskStandaloneTest.php`, policy para tareas sin proyecto.
- Las tareas standalone exigen hoy `assignee_id = usuario actual` en el POST
  (`SaveTaskRequest`) y no permiten sprint. No existe columna de autor.
- No hay columnas `code` (identificador legible), `points` ni `author` en `tasks`.
- `TaskController@store` fuerza `status = 'todo'` por defecto (anula el default
  `'backlog'` de la BD); el enum `TaskStatus::Backlog` ya existe.
- `TaskController@index` ya resuelve visibilidad: tareas donde el usuario es
  responsable **o** miembro/propietario del proyecto. Carga todo en una prop y la
  página `Tasks/Index.vue` filtra client-side (tabs Todas / Mis Tareas /
  Sin Proyecto / Urgentes / Completadas) — precedente directo para `/backlog`.
- `AuthenticatedLayout.vue:59` ya tiene el item de navegación **"Backlog"
  deshabilitado** (`to: null`) esperando ruta.
- `TaskFormModal.vue` ya soporta creación con solo título y edición de proyecto +
  sprint + prioridad + tipo + responsable (sección "vinculación" colapsable).
- Cerrar sprint **no toca sus tareas** hoy: `ProjectSprintController@complete` +
  `SprintObserver` no tienen lógica de retorno de pendientes.
- El tema vivo de la app es claro (tokens Material-3 en `resources/css/app.css`);
  el mock oscuro de Stitch es referencia de layout/UX, no de tokens.
- Las descripciones son texto plano tras la spec 014 (se eliminó Editor.js); la
  especificación dice "descripción con formato" de forma genérica — se respeta el
  comportamiento vigente de la app (texto plano, `max:50000`).

## Decisiones

### D1. Reutilizar `tasks.store` / `tasks.update` / `tasks.destroy` para mutaciones
- **Decision**: no se crea API ni endpoints nuevos de mutación. La captura rápida
  hace POST a la ruta existente `tasks.store` enviando explícitamente
  `status: 'backlog'`; el triage reutiliza `tasks.update`; el borrado
  `tasks.destroy`.
- **Rationale**: Constitución I (monolito Inertia) y V (YAGNI). Los endpoints
  existen, están autorizados con policies y probados.
- **Alternativas**: `BacklogController` con store/update propios → duplicaría
  validación y políticas; API JSON desacoplada → violaría Constitución I.

### D2. Relajar la regla "self-assign obligatorio" en la captura
- **Decision**: en `SaveTaskRequest`, cuando `project_id` es null, `assignee_id`
  pasa a ser opcional (antes obligatorio e igual al usuario). Si se envía, se
  mantiene la restricción de auto-asignación para tareas sin proyecto. En el
  triage, asignar responsable a una tarea sin proyecto solo permite
  auto-asignarse; con proyecto, cualquier miembro (regla actual).
- **Rationale**: FR-003/FR-006 del spec exigen capturar sin responsable; la app
  ya distingue ambos mundos.
- **Alternativas**: auto-asignar siempre al capturar → contradice FR-006;
  permitir responsables arbitrarios sin proyecto → rompe el modelo de
  visibilidad actual (solo responsable/autor ve la tarea).

### D3. Autoría: nueva columna `author_id` + policy "autor O responsable"
- **Decision**: migración añade `author_id` (FK a users, indexado, backfill:
  `COALESCE(assignee_id, owner del proyecto)`). `TaskPolicy::view`/`update` para
  tareas sin proyecto pasa a `author_id === user || assignee_id === user`;
  `delete` igual (hoy solo responsable).
- **Rationale**: FR-016 (bandeja personal) y FR-013 (el autor siempre puede
  eliminar) requieren saber quién capturó. Sin columna no es posible.
- **Alternativas**: inferir autoría por `created_at`/heurísticas → frágil;
  columna JSON de metadatos → sin integridad referencial y contrario a IV.

### D4. Código legible `FRN-###` por autor, único compuesto
- **Decision**: columna `code` (string). Secuencia por autor: el mayor código
  numérico del autor + 1, formateado `FRN-{n}`. Unique compuesto
  `(author_id, code)`. Generado en `TaskObserver::created` dentro de la misma
  transacción de creación; en la respuesta Inertia va incluido.
- **Rationale**: FR-005/SC visuales del diseño (FRN-104) y referencia rápida en
  conversaciones. Por autor evita fugas de volumen entre usuarios y mantiene
  números cortos y memorables.
- **Alternativas**: secuencia global → códigos largos y revela volumen total;
  UUID legible (nanoid) → no secuencial, peor para referencia humana;
  solo `id` numérico → el diseño y el spec piden identificador legible.

### D5. Puntos: columna `points` nullable
- **Decision**: `points` unsigned smallint nullable, editable en el triage
  (`UpdateTaskRequest`/`SaveTaskRequest` `sometimes|integer|min:0|max:99`), y la
  suma se expone en el encabezado (FR-009).
- **Rationale**: FR-009 y el diseño muestran "85 pts"; sin columna no hay suma
  persistente.
- **Alternativas**: almacenar en `description` → no agregable; tabla aparte →
  YAGNI.

### D6. Criterio de pertenencia al backlog: sin sprint y sin completar
- **Decision**: `Task::scopeBacklog()` = `whereNull('sprint_id')` +
  `status !== done` (equivale a `completed_at` null). Las "Con proyecto" son las
  que además tienen `project_id !== null` y el usuario es miembro/propietario
  (o autor/responsable). Todo se compone en `scopeVisibleTo($user)`:
  autor O responsable O miembro/propietario del proyecto.
- **Rationale**: US2/US4 y el edge case de visibilidad; reutiliza la lógica de
  `TaskController@index` extrayéndola a scopes.
- **Alternativas**: backlog solo de tareas standalone → contradice el filtro
  "Con proyecto" del spec; filtrar por `status === 'backlog'` → excluiría las
  tareas con proyecto en `todo` que el spec sí quiere listar.

### D7. Triage con `TaskFormModal` reutilizado (no panel lateral nuevo)
- **Decision**: US3 se implementa abriendo el `TaskFormModal` existente en modo
  edición (ya cubre título, descripción, tipo, prioridad, responsable y
  vinculación proyecto+sprint); se le añade el campo **puntos** y se ajusta el
  default de estado a `backlog` al capturar desde el backlog.
- **Rationale**: Constitución V y regla Boost "revisa componentes existentes
  antes de crear uno nuevo". El modal ya soporta todo el triage; un panel
  lateral nuevo sería ~600 líneas nuevas para el mismo resultado funcional.
- **Alternativas**: side-panel tipo mock de Stitch → mejor UX a futuro, pero
  duplica capacidades; se deja como evolución posterior si el triage en modal
  resulta insuficiente. El mock sigue siendo la referencia de layout para
  captura/lista/filtros.

### D8. Cierre de sprint con retorno automático al backlog
- **Decision**: en `SprintObserver`, al transicionar a `SprintStatus::Completed`,
  las tareas del sprint con `status !== done` reciben `sprint_id = null`
  (batch update). Conservan proyecto, prioridad y puntos (US4 escenario 3).
- **Rationale**: el edge case del spec exige el retorno; hoy no existe lógica y
  los pendientes quedarían huérfanos en un sprint cerrado.
- **Alternativas**: dejarlas en el sprint cerrado → viola US4; bloquear cierre
  con pendientes → fricción innecesaria y no pedida por el spec.

### D9. Una prop Inertia + filtros client-side (precedente `Tasks/Index`)
- **Decision**: `BacklogController@index` entrega `tasks` (payloads con
  `code`, `points`, `canUpdate`, `canDelete`, relaciones mínimas), `summary`
  (totales/puntos por filtro), `nextSprint` y `projects` (opciones de
  asignación). Agrupación por secciones, orden, filtros y conteos se calculan
  client-side, como hace `Tasks/Index.vue`.
- **Rationale**: consistencia con el código existente, cero endpoints extra;
  la escala (bandeja personal de cientos de ítems) lo tolera.
- **Alternativas**: props diferidas o polling → innecesario (YAGNI); filtros
  server-side → más endpoints y complejidad sin beneficio a esta escala.

### D10. Captura rápida: composer propio ligero (no el modal)
- **Decision**: componente nuevo `QuickCapture.vue`: un `<input>` con prioridad y
  proyecto opcionales en línea (chips), Enter para guardar, `useForm` con
  `preserveScroll`/`reset()` y bloqueo de doble submit vía `form.processing`.
  FR-002/SC-001 exigen teclado puro en <5 s; el modal completo no cumple esa
  velocidad.
- **Rationale**: es el corazón de la P1; no existe componente equivalente en la
  app (el modal es de formulario completo).
- **Alternativas**: abrir `TaskFormModal` con un solo campo visible → sigue
  siendo un modal (dos pasos, focu inicial en otro lugar); input inline sin
  opciones → incumple FR-004 (MAY) que pide prioridad/proyecto en línea.

### D11. Redirección tras borrado
- **Decision**: `TaskController@destroy` redirige actualmente a `tasks.index`;
  se ajusta a `redirect()->intended(...)`/`back()` con fallback a `tasks.index`
  para no sacar al usuario del backlog al borrar (US3 escenario 5).
- **Rationale**: comportamiento esperado en la bandeja; cambio mínimo.
- **Alternativas**: parámetro de redirect en la petición → superficie de
  open-redirect que hay que sanitizar; dejarlo como está → rompe el flujo.

### D12. Tema claro vigente vs mock oscuro
- **Decision**: la página usa los tokens existentes (`resources/css/app.css`,
  tema claro Material-3). El mock "Backlog home" de Stitch guía estructura
  (composer arriba, secciones por prioridad, filtros con conteo, banner de
  sprint, resumen) y jerarquía visual, no los colores.
- **Rationale**: Constitución V y consistencia con la app; un segundo tema oscuro
  es otra feature.
- **Alternativas**: replicar el mock pixel a pixel → cambio global de tema fuera
  de alcance.

## Resolución de clarificaciones

| # | Tema | Resolución |
| --- | --- | --- |
| 1 | ¿Reutilizar endpoints de tareas o crear API de backlog? | D1: reutilizar |
| 2 | ¿Cómo se gobierna la visibilidad sin proyecto? | D3: `author_id` + policy autor/responsable |
| 3 | ¿Cómo se genera el identificador FRN-###? | D4: secuencia por autor, unique compuesto |
| 4 | ¿Dónde viven los puntos? | D5: columna `points` nullable |
| 5 | ¿Qué define "estar en el backlog"? | D6: `sprint_id` null y no completada |
| 6 | ¿Triage en panel lateral nuevo o componente existente? | D7: `TaskFormModal` reutilizado + campo puntos |
| 7 | ¿Qué pasa al cerrar sprint con pendientes? | D8: retorno automático (`sprint_id = null`) |
| 8 | ¿Filtros y secciones server o client-side? | D9: una prop + client-side |
| 9 | ¿Cómo se implementa la captura <5 s? | D10: `QuickCapture.vue` propio |
| 10 | ¿Mock oscuro como tema? | D12: no, solo layout/UX |
