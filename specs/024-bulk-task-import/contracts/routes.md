# Contract: rutas de importación

| Método | URI | Nombre | Autorización | Efecto |
| --- | --- | --- | --- | --- |
| POST | `tasks/import/preview` | `tasks.import.preview` | `auth` | Analiza `content`; destino = tareas sueltas; `back()->with('importPreview', ImportPreview)` |
| POST | `tasks/import` | `tasks.import.store` | `auth` | Re-analiza `content` y crea las importables sin proyecto; `back()->with('success', 'Se importaron N tareas.')` |
| POST | `projects/{project}/tasks/import/preview` | `projects.tasks.import.preview` | `TaskPolicy::create` (miembro + activo), 404 si no es miembro | Igual, destino = proyecto |
| POST | `projects/{project}/tasks/import` | `projects.tasks.import.store` | igual | Crea en el backlog del proyecto |

Body (ambas): `content` — string, requerido, ≤ 1 048 576 bytes.

## Errores globales (validación de `content`)

| Caso | Mensaje |
| --- | --- |
| vacío | "Selecciona o pega un archivo de tareas." |
| > 1 MB | "El archivo no puede superar 1 MB." |
| > 200 tareas | "El archivo tiene más de 200 tareas; divídelo en varios." |
| 0 tareas `###` | "No encontramos tareas con el formato esperado." |
| confirmar con 0 importables | "No hay tareas válidas para importar." |

## Mensajes por tarea (vista previa)

| Estado | Ejemplos |
| --- | --- |
| error | "Falta el campo Tipo." · "Tipo «Mejora» no es válido (Tarea, Feature, Bug, Test)." · "El nombre supera 255 caracteres." |
| warning | "La prioridad Alta no coincide con la sección Media; se usará Alta." · "No encontramos a «Emiliano» en el proyecto; quedará sin responsable." · "La descripción no incluye (Origen: ...)." |
| duplicate | "Ya existe una tarea con este nombre." |
| repeated | "Nombre repetido en el archivo; se importa solo la primera." |

## Shared prop

`flash.importPreview` (añadido a `HandleInertiaRequests::share`, `null` si no hay).

## UI

`TaskImportModal.vue` (`projectId: number | null`): paso 1 seleccionar archivo `.md` o pegar texto → "Revisar"; paso 2 lista con estado por tarea, excluidos y botón "Importar N de M" (deshabilitado si N = 0) + "Cancelar".
