# Quickstart: Validación de vistas de tareas unificadas

**Fecha**: 2026-10-09 | **Feature**: [spec.md](spec.md) | **Contrato**: [contracts/project-task-views.md](contracts/project-task-views.md)

Guía para validar la feature de extremo a extremo. Prerequisitos: entorno con
`composer install` + `npm install`, BD migrada, `npm run dev` (o build) para
Wayfinder. Usa un proyecto de prueba con tareas en varios estados, puntos y
prioridades variadas, y al menos un sprint planificado/activo.

## 1. Validación automática

```bash
php artisan test --compact tests/Feature/Projects tests/Feature/Tasks
vendor/bin/pint --dirty
npm run build          # tipos + Wayfinder
php artisan test --compact   # suite completa antes de cerrar
```

Tests clave (creados en la fase de implementación):
- `tests/Feature/Projects/ProjectShowPayloadTest.php` (+): los 6 campos nuevos
  (`code`, `points`, `createdAt`, `completedAt`, `authorId`, `sprint.status`)
  presentes por tarea.
- `tests/Feature/Tasks/TaskCrudTest.php` (+): un miembro mueve una tarea de
  proyecto con `PUT /tasks/{id}` y solo `{status}`; visitante/no-miembro
  rechazado.

## 2. Escenarios manuales (navegador)

| # | Escenario | Pasos | Resultado esperado |
| --- | --- | --- | --- |
| 1 | Paridad visual Backlog | Abrir `/backlog` y `/projects/{id}` (vista Backlog) lado a lado | Mismas filas (chip FRN, prioridad, puntos, responsable), mismas secciones por prioridad y mismo orden; sin chip de proyecto en la del proyecto. |
| 2 | Secciones y orden | Ver la vista Backlog del proyecto | "Alta y urgente / Media / Baja e ideas"; dentro de cada sección, más recientes primero; selector Prioridad/Recientes funciona. |
| 3 | Filtro de sprint | En la sección Tareas, alternar el filtro: Sin sprint → sprint concreto → Todos | Backlog y Kanban muestran los mismos conjuntos para la misma opción; default = Sin sprint. |
| 4 | Estado vacío | Proyecto cuyas tareas sin sprint están completadas → vista Backlog | Estado vacío adaptado (invitación a crear), no lista vacía. |
| 5 | Paridad visual Kanban | Abrir `/tasks` (modo kanban) y la vista Kanban del proyecto lado a lado | Mismas columnas (Por Hacer/En Curso/En Revisión/Terminado + Bloqueado próximamente) y mismas tarjetas. |
| 6 | Mover tarjeta | Arrastrar una tarjeta a otra columna (miembro, proyecto activo) | Un solo `PUT /tasks/{id}` `{status}`; tarjeta en la nueva columna tras recargar; persistido. |
| 7 | Sin permisos | Entrar como no miembro (o proyecto archivado) a Backlog/Kanban | Vista idéntica en solo lectura: sin abrir edición al click, sin drag. |
| 8 | Edición desde vistas | Click en fila (Backlog) o tarjeta (Kanban) | Abre el mismo modal de edición que en las páginas globales; guardar refleja cambios. |
| 9 | Lista intacta | Alternar a la vista Lista | Igual que antes del cambio (mismo panel, mismos filtros, sin filtro de sprint). |
| 10 | Métricas | Revisar la ficha del proyecto (progreso, contadores, sprint actual) | Sin cambios: mismos cálculos sobre el mismo conjunto de tareas. |
| 11 | Crear tarea | "Nueva Tarea" del header del proyecto | Modal de creación con el proyecto preseleccionado (como antes); la tarea nueva aparece en las vistas. |
| 12 | Código muerto | `grep -r "BacklogView\|KanbanBoard\|Tasks/TaskCard" resources/js` | Sin resultados: los tres componentes legacy eliminados. |

## 3. Criterios de aceptación rápidos

- [ ] Suite completa en verde sin modificar tests existentes.
- [ ] Los 12 escenarios manuales pasan.
- [ ] `vendor/bin/pint --dirty` y `npm run build` limpios.
- [ ] SC-001/SC-002: las comparaciones lado a lado no permiten distinguir filas ni tablero de sus páginas globales equivalentes.
