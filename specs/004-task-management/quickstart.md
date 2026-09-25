# Quickstart: validación end-to-end — Gestión de Tareas (Tasks)

**Feature**: 004-task-management | **Date**: 2026-09-23
**Contrato**: [contracts/web-tasks-surface.md](./contracts/web-tasks-surface.md) ·
**Modelo**: [data-model.md](./data-model.md)

Escenarios ejecutables que demuestran la feature de extremo a extremo. No
incluye código de implementación.

## Prerequisites

- Features 001 (auth), 002 (proyectos) y 003 (sprints) instaladas y
  funcionando.
- Dependencias y build al día: `composer install && npm install && npm run build`.
- Tests en SQLite `:memory:`; desarrollo con la DB del `.env` (PostgreSQL
  según constitución).

```bash
php artisan migrate        # aplica create_tasks_table + create_comments_table
npm run build              # regenera Wayfinder con projects.tasks.*
```

## Escenarios de validación

S1–S4 son manuales por navegador; S5–S7 son automáticos (Pest). Los escenarios
automáticos cubren las mismas reglas que los manuales.

### S1 — Alta y listado (US1)

1. Como miembro de un proyecto activo con sprints, abrir la ficha del
   proyecto → sección Tareas vacía con formulario de alta.
2. Crear una tarea con título, descripción, tipo, prioridad y responsable →
   aparece en la lista con estado "backlog" y los datos elegidos.
3. Crear una tarea sin sprint → aparece identificada como aislada (sin
   sprint); crear otra asignada a un sprint → aparece con el nombre del
   sprint.
4. Crear sin título, con un responsable que no es miembro o con un sprint de
   otro proyecto → error por campo visible; no se guarda nada.
5. No indicar tipo ni prioridad → se aplican los valores por omisión (other /
   medium) visibles en la lista.

### S2 — Edición y estados (US2)

1. Editar título/descripción/tipo/prioridad/responsable desde el modal → la
   lista refleja los cambios.
2. Cambiar el estado por el selector (p. ej. backlog → todo → in_progress →
   done) → el badge de estado se actualiza; regresar a un estado anterior
   (done → todo) también se acepta.
3. Guardar un título vacío → error y la tarea conserva sus datos.

### S3 — Vinculación a sprints (US3)

1. Editar una tarea aislada y asignarle un sprint del proyecto → aparece
   vinculada.
2. Quitarle el sprint (opción "sin sprint") → vuelve a aislada; el sprint y
   sus demás tareas no se ven afectados.
3. Intentar asignar un sprint de otro proyecto (petición manipulada) → error
   en el campo sprint.

### S4 — Comentarios y eliminación (US4/US5)

1. Abrir el modal de comentarios de una tarea, añadir una nota → aparece en
   el hilo con autor y orden cronológico; añadir otra y comprobar el orden.
2. Guardar un comentario vacío → error; no se crea nada.
3. Eliminar una tarea con comentarios confirmando → desaparece con sus
   comentarios; proyecto, sprints y demás tareas intactos.
4. Iniciar la eliminación y no confirmar → no se elimina nada.

### S5 — CRUD y estados automatizados

```bash
php artisan test --compact --filter=TaskCrudTest
```

Esperado: alta válida aislada y con sprint, orden por recientemente
actualizadas, validaciones (título, responsable ajeno, sprint de otro
proyecto, defaults de tipo/prioridad, estado inicial backlog), edición,
movimiento libre de estados, vinculación/desvinculación de sprint y
eliminación con cascada de comentarios.

### S6 — Comentarios automatizados

```bash
php artisan test --compact --filter=TaskCommentsTest
```

Esperado: alta de comentario con autor correcto, listado cronológico, rechazo
de cuerpo vacío y de no miembros.

### S7 — Acceso y estados automatizados

```bash
php artisan test --compact --filter=TaskAccessTest
```

Esperado: colaborador puede crear/editar/comentar pero no eliminar (404), no
miembro → 404 en toda la superficie, propietario en proyecto
archivado/completado recibe `errors.project` con el mensaje de bloqueo en
todas las escrituras, la lista de tareas sigue visible fuera del estado
activo.

### S8 — Calidad de contrato

```bash
composer ci:check
npm run types:check
```

Esperado: todos los gates pasan (phpstan puede requerir más memoria que el
CLI local de Herd; ver nota en el plan de la feature 003).

## Criterio de "feature funcionando"

S1–S4 verificados manualmente + S5–S7 en verde + S8 sin violaciones. Cubre
los escenarios de aceptación US1–US6 y habilita el paso a
`/skill:speckit-tasks`.
