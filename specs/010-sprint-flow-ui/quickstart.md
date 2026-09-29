# Quickstart: Flujo de Sprint en la UI (010)

Guía de validación end-to-end. Detalles de shapes y endpoints en
[contracts/sprint-flow-ui.md](./contracts/sprint-flow-ui.md) y
[data-model.md](./data-model.md).

## 0. Preparación

```bash
composer install && npm install
php artisan migrate            # aplica goal + status en sprints (con backfill)
php artisan serve &            # o Herd
npm run dev                    # vite (otra terminal)
```

Usa un proyecto activo propio con varios sprints y tareas en distintos estados
(factories disponibles: `Sprint::factory()`, `Task::factory()` con sprint).

## 1. Suite en verde

```bash
php artisan test --compact     # completa; nuevos: SprintLifecycle, SprintGoal, SprintDetail, SprintStoreStartNow
npm run check:fix && npm run types:check && npm run build
```

## 2. Crear sprint con el nuevo diálogo (US2 + start_now)

1. Proyecto → pestaña Sprints → "Planificar un sprint".
2. Diálogo con nombre (autofocus), objetivo, inicio/fin. Validar: fin anterior a
   inicio → error en línea; nombre vacío → error; objetivo > 1000 → error.
3. Con "Comenzar inmediatamente" activado → el sprint aparece con chip **Activo**;
   sin activar → **Planificado**.
4. Editar el sprint desde la lista → diálogo precargado (nombre, objetivo, fechas);
   guardar refleja cambios en lista y detalle.

## 3. Detalle del sprint (US1)

1. Desde la pestaña Sprints, el nombre del sprint enlaza a
   `projects/{project}/sprints/{sprint}`.
2. Encabezado: nombre, chip de estado, "Iteración N de M", fechas, días laborables
   restantes y % transcurrido coherentes con las fechas (acotados 0–100, días ≥ 0).
3. Métricas: cobertura y desglose por estado **exactos** respecto a las tareas
   creadas (crea tareas en todo/in_progress/in_review/done y compara a mano).
4. Lista: buscar (⌘F enfoca), filtrar por responsable/tipo, agrupar por
   estado/prioridad con contadores correctos; click en tarea abre el diálogo en
   edición; "Añadir tarea a este sprint" abre el diálogo con proyecto y sprint
   preseleccionados → al guardar aparece en la lista.
5. Sprint sin tareas: métricas en cero comprensibles + empty state.

## 4. Ciclo de vida (US3)

1. Sprint planificado → "Activar" → chip **Activo** en detalle y lista.
2. Sprint activo → "Completar Sprint" (confirmación) → chip **Completado**;
   desaparecen Editar/Eliminar/Activar/Completar y "Añadir tarea".
3. Crear con `start_now` (§2) equivale al paso 1.
4. Forzar transiciones inválidas (reactivar completado, completar un planificado)
   vía petición manual → rechazo con mensaje claro y sin cambios.

## 5. Permisos y solo lectura

- Colaborador (no propietario): ve detalle y métricas, sin acciones de escritura.
- Proyecto archivado/completado: detalle visible, todo lo demás bloqueado.
- No miembro / id inexistente: 404 indistinguible.
- Sprint completado: solo lectura (servidor rechaza update/destroy/transición).

## 6. Regresión y polish

- `Projects/Show` (panel "Sprint activo" ahora por status) y `Mis Tareas` siguen
  verdes y funcionando.
- Detalle a 375px: sin scroll horizontal de página; consola sin errores
  (`browser-logs`); recorrido de teclado (⌘F, ESC, modal, confirmación completa).
- Columna lateral muestra las tarjetas "Próximamente" deshabilitadas (sin foco,
  sin datos de mentira).

## 7. Checklist de cierre

- [ ] Suite completa en verde + gates (`check:fix`, `types:check`, `build`, Pint).
- [ ] §2–§6 validados a mano en el navegador.
- [ ] Sin elementos "Próximamente" navegables ni enfocables.
- [ ] Commit final de la feature.
