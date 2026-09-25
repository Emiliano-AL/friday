# Quickstart: validación end-to-end — Vistas Backlog y Kanban

**Feature**: 005-task-board-views | **Date**: 2026-09-24
**Contrato**: [contracts/web-board-views.md](./contracts/web-board-views.md) ·
**Modelo**: [data-model.md](./data-model.md)

Escenarios que demuestran la feature de extremo a extremo. No incluye código de
implementación.

## Prerequisites

- Features 001–004 instaladas y funcionando (tareas con tipos, prioridades y
  estados; sprints).
- Dependencias y build al día: `composer install && npm install && npm run build`.
- Tests en SQLite `:memory:`; desarrollo con la DB del `.env` (PostgreSQL).

```bash
php artisan migrate        # sin migraciones nuevas; verifica el estado
npm run build              # regenera Wayfinder (sin rutas nuevas)
```

## Escenarios de validación

S1–S4 son manuales por navegador (el S3 se automatiza con eventos de
arrastre construidos por script); S5–S6 son automáticos (Pest).

### S1 — Vista Backlog ordenable (US1)

1. En un proyecto activo con tareas de varias prioridades y sprints (y alguna
   aislada), abrir la sección Tareas → pestaña Backlog.
2. Comprobar el orden por omisión: prioridad descendente (urgente primero) con
   título, prioridad, sprint, estado y responsable por fila.
3. Conmutar a ascendente → las de menor prioridad quedan arriba; pasar a
   orden por sprint → las tareas se agrupan por sprint en orden de fecha y la
   aislada queda al final identificada como "Sin sprint".
4. Filtrar por un sprint concreto → solo sus tareas; por "Sin sprint" → solo
   las aisladas; volver a "Todas" → reaparecen todas.

### S2 — Vista Kanban (US2)

1. Abrir la pestaña Kanban → cinco columnas (Backlog, Por hacer, En progreso,
   En revisión, Hecho), cada una con su contador.
2. Comprobar que cada tarjeta está en la columna de su estado y muestra
   título, prioridad, responsable y sprint.
3. Identificar una columna vacía → muestra su indicador de vacío.

### S3 — Drag-and-drop (US3)

1. Arrastrar una tarjeta de "Por hacer" a "En progreso" → la tarjeta aparece
   en la nueva columna, los contadores se actualizan y la pestaña Backlog/Lista
   refleja el nuevo estado.
2. Arrastrar dentro de la misma columna → no ocurre nada (sin petición).
3. Soltar fuera de cualquier columna → la tarjeta vuelve a su columna.
4. Repetir la cadena completa (backlog → todo → in_progress → in_review →
   done y de vuelta a backlog) y comprobar persistencia en cada paso.

### S4 — Solo lectura fuera de activo (US4)

1. Archivar el proyecto → ambas vistas siguen visibles y el ordenamiento y
   filtro del Backlog funcionan.
2. Comprobar que las tarjetas no son arrastrables.
3. Forzar la petición de cambio de estado → mensaje "El proyecto no admite
   cambios en su estado actual." y nada cambia.

### S5 — Contrato y transiciones automatizados

```bash
php artisan test --compact --filter=TaskBoardTest
```

Esperado: el prop `project.tasks` incluye los campos que consumen las vistas
(`status`, `priority`, `sprint`, `assignee`) y `project.sprints` incluye
`startDate`; la cadena completa de movimientos del tablero vía
`projects.tasks.update` persiste cada estado; el movimiento con proyecto
archivado devuelve `errors.project` con el mensaje de bloqueo.

### S6 — Calidad de contrato

```bash
composer ci:check
npm run types:check
```

Esperado: todos los gates pasan (phpstan puede requerir más memoria que el
CLI local de Herd; ver nota acumulada en los planes de features previas).

## Criterio de "feature funcionando"

S1–S4 verificados (en navegador) + S5–S6 en verde. Cubre US1–US4 y habilita
el paso a `/skill:speckit-tasks`.
