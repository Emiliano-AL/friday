# Quickstart: Rediseño del flujo de tareas (009)

Guía de validación end-to-end. Shapes e interacciones en [contracts/tasks-board-ui.md](./contracts/tasks-board-ui.md); reglas de datos en [data-model.md](./data-model.md).

## 0. Prerrequisitos

```bash
composer install && npm install
npm run dev            # o: composer run dev
php artisan migrate    # aplica la migración de project_id nullable
```

Servir con Herd (`friday.test`) o `php artisan serve`. Sesión iniciada con un usuario de prueba.

## 1. Gates automáticos

```bash
vendor/bin/pint --dirty --format agent
npm run check:fix && npm run types:check
php artisan test --compact --filter=Task     # dominio tareas (incl. nuevos standalone/global/página)
php artisan test --compact
npm run build
```

Esperado: todo verde; suite de tareas incluye `TaskStandaloneTest`, `TaskGlobalRoutesTest` y `TasksPageTest`.

## 2. Mis Tareas — vista lista (US1)

1. Con el usuario A: crea desde Mis Tareas (`C`) 4 tareas: una urgente asignada a A, una "Sin Proyecto", una media en backlog con proyecto, y una terminada (márcala Hecho desde su fila).
2. Verifica: pestañas con contadores reales (Todas 4, Mis Tareas N, Urgentes incl. alta, Completadas 1); grupos correctos (la terminada NO aparece en grupos por defecto); pie "Mostrando N tareas".
3. Escribe en el buscador (`⌘F` lo enfoca): solo coinciden por título; sin resultados muestra estado vacío con limpiar.
4. Filtra por proyecto: solo tareas de ese proyecto; los contadores de pestañas se recalculan.
5. Cambia el estado con el botón circular de una tarea: migra de grupo y persiste tras recargar.
6. Click en la fila: diálogo en modo edición.

## 3. Kanban (US2)

1. Alterna a kanban (el conmutador persiste tras recargar).
2. Verifica columnas y contadores; la columna **Bloqueado** está deshabilitada con "Próximamente".
3. Arrastra una tarjeta de "Por Hacer" a "En Curso": al soltar se mueve y persiste tras recargar.
4. "Añadir tarea rápida" en "En Revisión": el diálogo abre con Estado inicial = En Revisión; al guardar, la tarjeta nace en esa columna.
5. Tarjetas: muestran proyecto (o "Sin Proyecto"), sprint, prioridad, conteo de comentarios real y avatar.

## 4. Diálogo crear/editar (US3)

1. `C` abre el diálogo con foco en el título.
2. Verifica los controles "Próximamente" deshabilitados: Story Points, Fecha de vencimiento, toolbar de marcado.
3. Selecciona un proyecto: el selector de sprint solo ofrece sus sprints; cambia a "Sin Proyecto": sprint se deshabilita y el responsable se limita a ti.
4. Envía vacío: error inline en título; diálogo permanece abierto.
5. Activa "Crear otra al guardar": guarda y permanece limpio; desactívala y cierra tras guardar.
6. Modo edición: zona de peligro → Eliminar → `confirm()` → flash y desaparición.
7. `Esc` y click-fuera cierran; doble envío bloqueado.

## 5. Tareas sin proyecto y permisos (FR-011/SC-005)

1. Crea una tarea "Sin Proyecto": aparece en el grupo "Sin Proyecto Asignado" y en la pestaña/filtro homónimos.
2. Cuenta B (segundo usuario): NO ve la tarea huérfana de A ni puede forzar sus rutas (404/403).
3. Con B como responsable de una huérfana (asigna desde el diálogo… no: huérfana solo se asigna a uno mismo) — verifica que A no pueda editarla si no es responsable.
4. Comentarios en una tarea huérfana: abre el modal desde la fila, comenta y verifica persistencia tras recargar.

## 6. Shell e integración

1. Sidebar: "Mis Tareas" navega y queda activo en `/tasks`.
2. Paleta ⌘K → "Crear nueva tarea" → abre Mis Tareas con el diálogo de creación.
3. Detalle de proyecto (regresión): sprints, tareas, backlog, kanban y comentarios funcionan como antes de la feature.

## 7. Responsive y accesibilidad

1. A 375px: lista en una columna sin scroll horizontal de página; kanban con scroll horizontal de columnas; diálogo a ancho completo.
2. Teclado: recorre pestañas → filas → menú ⋯ → diálogo completo sin ratón; `C`/`⌘F`/`Esc` con anillos de foco visibles.
3. Consola del navegador y `mcp__laravel-boost__browser-logs`: sin errores durante todo el recorrido.

## Troubleshooting

- **La tarea huérfana no aparece**: revisa que `project_id` quedó nullable (`migrate` aplicada) y que la query de visibilidad incluye `assignee_id = user`.
- **Drag sin efecto**: el PUT global debe aceptar actualización parcial (`status` solo) — ver D4 en research.
- **Modal de comentarios vacío en global**: `tasks.show` debe devolver la tarea con `comments` ordenados; el modal en modo `useGlobal` los carga al abrir.
- **`C` abre la paleta en vez del diálogo**: el listener de la página debe estar en fase capture y hacer `stopPropagation` (D6).
