# Quickstart: Validación — Tareas del Proyecto (spec 013)

**Fecha**: 2026-10-01
**Feature**: [spec.md](spec.md) · Contratos: [contracts/013-project-detail-tasks.md](contracts/013-project-detail-tasks.md)

Guía de validación manual end-to-end. Prerequisitos: app servida por Herd (`friday.test`) con `npm run dev` (o build vigente), usuario de prueba miembro de al menos un proyecto activo con tareas y sprints.

## 0. Preparación

**Baseline (T001, 2026-10-01)**: `npm run types:check` ✓ · `npm run build` ✓ · `php artisan test --compact` → 189 passed, 1274 assertions ✓. Inventario del impacto de D3: los únicos tests que afirman el shape embebido `project.tasks.N.comments` son `tests/Feature/Tasks/TaskAccessTest.php` (~línea 119), `tests/Feature/Tasks/TaskCrudTest.php` (~línea 53) y `tests/Feature/Tasks/TaskCommentsTest.php` (~líneas 30-33 y 53-55); se actualizan en T003.

1. `npm run types:check` y `npm run build` en verde.
2. `php artisan test --compact` en verde (suite completa).
3. Iniciar sesión en `http://friday.test` con un usuario miembro (no owner) de un proyecto activo que tenga ≥1 tarea y ≥1 sprint, y ser owner de otro (para validar permisos de borrado).

## 1. Botón "Nueva Tarea" abre el modal (US1 — FR-001/002/003/009)

1. Abrir el detalle del proyecto (`/projects/{id}`) estando en la tab "General y resumen".
2. Pulsar "Nueva Tarea" en el encabezado → **esperado**: se abre el modal "Crear nueva tarea" sin cambiar de tab; la sección "Vinculación y alcance" aparece **expandida** con el proyecto preseleccionado y su select **deshabilitado**.
3. El select "Sprint" solo lista los sprints de ese proyecto (más "Sin Sprint").
4. Escribir título → "Crear Tarea" → **esperado**: modal cerrado, mensaje de éxito, la tarea nueva aparece en la sección Tareas (vista Lista) agrupada según prioridad/estado, **sin recargar la página manualmente**.
5. Repetir con la casilla "Crear otra tarea al guardar" → **esperado**: el modal permanece abierto, el proyecto sigue fijo.
6. Proyecto archivado/completado → **esperado**: el botón "Nueva Tarea" no se ofrece como acción habilitada.

## 2. Listado estilo /tasks en la sección Tareas (US2 — FR-005/006)

1. En la tab "Sprints y tareas", vista "Lista" → **esperado**: grupos "Alta Prioridad & Urgentes" / "En Curso" / "Próximas & Backlog" / "Completadas" con filas tipo `TaskRow` (tipo, prioridad, estado, responsable con avatar, acciones), igual que `/tasks`.
2. **No debe existir** el formulario inline (campos Título/Descripción/Tipo/Prioridad/Responsable/Sprint ni botón "Añadir") bajo el listado, en ninguna vista (Lista, Backlog, Kanban).
3. Búsqueda y filtro de responsable filtran **solo** dentro de las tareas del proyecto; el filtro de proyecto **no** aparece; el tab "Sin Proyecto" **no** aparece.
4. Proyecto sin tareas → estado vacío equivalente al de `/tasks`.
5. Contador "Mostrando X de X tareas" coherente con el total del proyecto.

## 3. Acciones de fila en el contexto del proyecto (FR-007/008/009)

1. Cambiar estado desde la fila → **esperado**: la tarea se reubica de grupo sin salir de la página.
2. "Editar" → **esperado**: el mismo modal en modo edición, proyecto fijo; guardar actualiza la fila.
3. "Eliminar tarea" desde el pie del modal (solo owner) → confirmar → **esperado**: la tarea desaparece y **el usuario permanece en el detalle del proyecto** (no es redirigido a Mis Tareas).
4. "Comentarios (n)" → **esperado**: modal de comentarios con contenido actualizado; publicar uno actualiza el contador en la fila.
5. Miembro no-owner: no ve acciones de borrado (fila sin eliminar; en el modal, sin "Eliminar tarea" si no tiene `canDelete`).

## 4. Regresión de `/tasks` (cero cambios de comportamiento)

1. `/tasks`: vista Lista idéntica a antes (grupos, tabs "Todas/Mis Tareas/Sin Proyecto/Urgentes/Completadas", filtros de proyecto y responsable, atajo `C` abre el modal).
2. Crear/editar/eliminar/comentar desde `/tasks` funciona igual; el borrado desde el modal sigue terminando en `/tasks`.
3. Vista kanban de `/tasks` sin cambios.

## 5. Regresión del detalle de proyecto (resto de tabs)

1. Tab "General y resumen": miembros, añadir/retirar miembro, descripción con markdown — sin cambios.
2. Sprints: crear/editar/eliminar sprint, "Planificar un sprint" — sin cambios.
3. Vistas Backlog y Kanban del proyecto: renderizan las tareas del proyecto igual que antes (mismo contenido, nuevo tipo de dato interno).
4. Progreso global y "X de Y tareas concluidas" correctos tras crear/completar tareas.
5. Modal de editar/crear proyecto y de sprints — sin cambios.

## 6. Gates finales

1. `vendor/bin/pint --dirty --format agent` (hubo cambio PHP en `ProjectController`).
2. `npm run types:check && npm run build` en verde.
3. `php artisan test --compact` completo en verde.
