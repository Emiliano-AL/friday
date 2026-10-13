# Quickstart: validar accesos a "Nueva tarea"

## Tests automáticos

```bash
php artisan test --compact tests/Feature/Shell/TaskFormOptionsSharedPropTest.php
php artisan test --compact tests/Feature/Backlog   # regresión tras extraer projectOptions
npm run types:check
```

## Escenarios manuales (navegador)

| # | Pasos | Esperado | Spec |
| --- | --- | --- | --- |
| 1 | Inicio → "Nueva Tarea" | Se abre el formulario de nueva tarea, no la paleta | US1-1, FR-001 |
| 2 | Crear una tarea desde el inicio | Formulario se cierra, sigue en el inicio, aviso "Tarea creada." | US1-4, FR-006 |
| 3 | En Proyectos, pulsar C | Se abre el formulario | US1-3, FR-004 |
| 4 | ⌘K → "Crear nueva tarea" | Paleta se cierra, formulario se abre, sin navegar | US1-2, FR-003 |
| 5 | En Mis Tareas, pulsar C | Un solo formulario (el de la página) | FR-007 |
| 6 | Escribir "c" en el buscador de la paleta o en un campo | No se abre el formulario | Edge case |
| 7 | Barra lateral → botón principal | Dice "Acciones rápidas ⌘K" y abre la paleta sin navegar | US2, FR-005 |
| 8 | Inicio sin actividad → tarjeta "Nueva tarea" | Se abre el formulario | US1-6, FR-002 |
| 9 | Network: navegar entre páginas | `taskFormProjects` no viaja hasta abrir el formulario | D2 |
