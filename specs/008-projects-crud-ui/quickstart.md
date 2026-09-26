# Quickstart: Rediseño del CRUD de Proyectos (008)

Guía de validación end-to-end. Los shapes exactos de props e interacciones viven en [contracts/projects-ui.md](./contracts/projects-ui.md) y las derivaciones de datos en [data-model.md](./data-model.md).

## 0. Prerrequisitos

```bash
composer install && npm install
npm run dev            # o: composer run dev
php artisan migrate:fresh --seed   # opcional; basta con migraciones
```

Servir con Herd (`friday.test`) o `php artisan serve`. Sesión iniciada con un usuario de prueba (registro en `/register`).

## 1. Gates automáticos (antes de tocar el navegador)

```bash
vendor/bin/pint --dirty --format agent
npm run check:fix && npm run types:check
php artisan test --compact --filter=Project     # suite del dominio
php artisan test --compact                      # suite completo
npm run build
```

Esperado: Pint passed; tipos sin errores; tests del dominio proyectos en verde (incluidos `ProjectDirectoryTest` y `ProjectProgressTest` ampliados); build OK.

## 2. Directorio (US1)

1. Con el usuario A, crea 3 proyectos desde el directorio (botón, tarjeta "Iniciar un nuevo proyecto" o tecla `N`).
2. Verifica: chip "N Activos" junto al título; píldoras con contadores correctos; orden "Recientes" por última actividad.
3. Escribe en el buscador (`⌘F` debe enfocarlo): solo coinciden por nombre; con término imposible aparece el estado vacío con acción de limpiar.
4. Alterna píldoras (Activos/Completados/Archivados): el set filtra y los contadores no cambian.
5. Cambia orden a Alfabético y Progreso; alterna vista cuadrícula/lista y recarga: la preferencia persiste.
6. Revisa una tarjeta: progreso con conteo "(x/y)", sprint activo (o "Sin sprint activo"), avatares con "+N", menú ⋯ con acciones según estado.

## 3. Crear, editar, archivar, eliminar (US2)

1. `N` abre el diálogo con foco en Nombre; la sección "Identidad visual" muestra Clave/Color/Icono/Fecha deshabilitados con "Próximamente".
2. Envía vacío: error inline en Nombre, diálogo permanece abierto, foco al campo.
3. Crea con nombre + descripción: diálogo cierra, aparece flash "Proyecto creado", el proyecto encabeza "Recientes".
4. Menú ⋯ → Editar: el diálogo abre precargado; guarda cambios y verifica flash + tarjeta actualizada.
5. Menú ⋯ → Archivar: desaparece de "Activos" y suma en "Archivados"; desde la píldora Archivados, el menú ofrece Reactivar.
6. Diálogo de edición → zona de peligro → Eliminar → `confirm()`: vuelve al directorio con flash de eliminación y el proyecto desaparece.
7. Deep-links: `/projects/create` abre el directorio con el diálogo; `/projects/{id}/edit` (líder) abre el detalle con diálogo de edición.
8. Doble envío: con red lenta (throttling) el botón queda deshabilitado mientras procesa.

## 4. Permisos (SC-005)

1. Cuenta B: añádela como miembro de un proyecto de A (Equipo → añadir por email, en la pestaña General del detalle).
2. Como B: el menú ⋯ solo ofrece "Ver detalle"; `/projects/{id}/edit` responde 404; PUT de update/status es rechazado (404/403 según corresponda).
3. El KPI "Proyectos en marcha" de B cuenta los proyectos donde B participa.

## 5. Detalle con pestañas (US3)

1. Abre un proyecto con sprints y tareas: franja de metadatos con líder, equipo, progreso "(x/y)" e insignia de estado con sprint activo.
2. Pestaña "General y resumen": tarjeta del sprint activo, sprint siguiente, banner de backlog y tarjeta Equipo (gestión de miembros operativa).
3. Pestaña "Sprints y tareas": la funcionalidad previa (crear/editar sprint, crear tarea, vistas lista/backlog/kanban, comentarios) opera igual que antes del rediseño.
4. Pestañas Documentación / Recursos / Historial: deshabilitadas con "Próximamente".
5. Header: "Ajustes" abre edición; "Compartir" está deshabilitado con tooltip "Próximamente"; "Nuevo Sprint"/"Nueva Tarea" cambian a la pestaña operativa.
6. Crea una tarea y márcala Done: el progreso del detalle y del directorio refleja el cambio (mismo valor, SC-003).

## 6. Progreso real y KPIs

1. Proyecto sin tareas → 0% y sin conteo de fracción.
2. Proyecto con tareas Done/parciales → porcentaje = redondeo(Done/total×100), consistente entre tarjeta y detalle.
3. KPI "Sprints completados este trimestre" cuenta solo sprints con `end_date` pasado dentro del trimestre actual.

## 7. Responsive y accesibilidad

1. A 375px: una columna, sin scroll horizontal, diálogo a ancho completo, menús adaptados.
2. Teclado: recorre directorio → tarjeta → menú ⋯ → diálogo completo sin ratón; anillos de foco visibles; ESC cierra menús/diálogo devolviendo foco.
3. `mcp__laravel-boost__browser-logs` / consola del navegador: sin errores durante todo el recorrido.

## Troubleshooting

- **Diálogo no abre por query param**: verifica que Index/Show lean el query y hagan `router.replace` al abrir (§2 contrato).
- **Flash no aparece**: `HandleInertiaRequests` debe compartir `flash` y el redirect llevar `->with('success', ...)`.
- **Progreso siempre 0**: `progressPercentage()` real debe estar en `Project` y el índice debe usar `withCount` de tareas totales/Done.
