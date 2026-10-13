# Quickstart: validar la carga masiva

## Tests

```bash
php artisan test --compact tests/Feature/Tasks/TaskImportTest.php
php artisan test --compact tests/Feature/Tasks tests/Feature/Backlog
```

Fixture: `tests/Fixtures/task-import-example.md` (el ejemplo completo del formato: 3 tareas + 1 "No incluido").

## Escenarios manuales

| # | Pasos | Esperado | Spec |
| --- | --- | --- | --- |
| 1 | Proyecto → Sprints y tareas → Backlog → "Importar" → elegir el ejemplo | Vista previa: 3 tareas, 1 excluido, "Importar 3 de 3" | US1-1, US1-3 |
| 2 | Confirmar | 3 tareas en el backlog del proyecto, aviso "Se importaron 3 tareas." | US1-2 |
| 3 | Repetir 1 con el mismo archivo | Las 3 marcadas "Ya existe"; "Importar 0 de 3" deshabilitado | SC-006 |
| 4 | Archivo con Tipo "Mejora" y una tarea con 3 viñetas | Esas 2 en error con motivo; "Importar N de M" con las válidas | US3, FR-011 |
| 5 | `/backlog` → "Importar" | Tareas sin proyecto en el Backlog general | US2 |
| 6 | Responsable que no existe | Aviso y la tarea queda sin responsable | FR-005 |
| 7 | Descripción con `código` | Se ve con formato de código en la tarea | FR-009 |
| 8 | Cancelar en la vista previa | No se crea nada | FR-007 |
