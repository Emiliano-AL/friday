---
description: 'Task list for 024-bulk-task-import'
---

# Tasks: Carga masiva de tareas desde archivo Markdown

**Input**: Design documents from `/specs/024-bulk-task-import/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/routes.md, quickstart.md

**Tests**: INCLUIDOS (Constitución III, Test-First con Pest). Escribir cada bloque de tests antes de su implementación y verlos fallar.

**Organization**: por historia de usuario (US1 proyecto, US2 sueltas, US3 errores de formato).

## Format: `[ID] [P?] [Story] Description`

---

## Phase 1: Setup

- [X] T001 Crear `tests/Fixtures/task-import-example.md` con el ejemplo completo de la sección 4 del formato ("Pendientes — Sprint 12"): 3 tareas (Alta: "Hacer atómica la generación de PIN" Bug; Media: "Escapar comodines LIKE en las búsquedas" Bug; Baja: "Tests de `viewAny` para los roles purchasing y warehouse" Test), todas con Responsable `Emiliano`, y la sección "No incluidos" con 1 elemento (`Truncamiento de StockLevel.quantity`).

---

## Phase 2: Foundational (bloquea todas las historias)

- [X] T002 Crear `app/Http/Requests/ImportTasksRequest.php` (`php artisan make:request ImportTasksRequest --no-interaction`): `authorize()` = true (la autorización de proyecto va en el controlador); `rules()`: `content` → `['required', 'string', 'max:1048576']` con mensajes "Selecciona o pega un archivo de tareas." y "El archivo no puede superar 1 MB."; método `parsedTasks(): array` que implementa el parser por líneas descrito en `research.md` D3: normaliza saltos de línea; `## Prioridad (Urgente|Alta|Media|Baja)` fija la sección; `## No incluidos...` activa modo excluidos (cada `- **Nombre** (origen): motivo.` → `['name', 'reason']`); `### Nombre` abre tarea (quita `**`, guarda número de línea); viñetas `- **Tipo:**`, `- **Prioridad:**`, `- **Responsable:**`, `- **Descripción:**` (sin distinguir mayúsculas, tolera espacios); ignora `#`, `---` y líneas vacías. Devuelve `array{tasks: list<array{line:int,title:string,fields:array<string,string>,section:?string}>, excluded: list<array{name:string,reason:string}>}`. Si hay más de 200 tareas → `ValidationException` en `content`: "El archivo tiene más de 200 tareas; divídelo en varios."; si hay 0 → "No encontramos tareas con el formato esperado.". PHPDoc con array shapes.
- [X] T003 En el mismo `ImportTasksRequest`, añadir `analyze(?Project $project, User $user): array` que devuelve el `ImportPreview` de `data-model.md`: mapea Tipo (`Tarea`→`other`, `Feature`, `Bug`, `Test`) y Prioridad (`Baja`→`low`, `Media`→`medium`, `Alta`→`high`, `Urgente`→`urgent`) sin distinguir mayúsculas; marca `error` con los mensajes exactos de `contracts/routes.md` (campo faltante, valor inválido con permitidos, nombre > 255); `warning` si la prioridad difiere de la sección, si falta `(Origen:` o si el Responsable no resuelve; resuelve Responsable según D5 (`Str::ascii` + minúsculas, nombre completo o primera palabra; personas = propietario + miembros del proyecto, o solo `$user` si `$project` es null; 0 o >1 coincidencias → null + aviso "No encontramos a «X» en el proyecto; quedará sin responsable." / "Hay varias personas llamadas «X»; quedará sin responsable."); detecta duplicados según D6 (`duplicate` contra títulos existentes del destino; `repeated` dentro del archivo, se conserva la primera); `excerpt` = descripción en texto plano ≤ 140; `counts.importable` = estados `ok` + `warning`. Añadir también `toTiptap(string $text): string` (D4: párrafo con nodos `text` y marca `code` para los tramos entre backticks; `json_encode` con `JSON_UNESCAPED_UNICODE`).
- [X] T004 [P] Crear `app/Http/Controllers/TaskImportController.php` (`php artisan make:controller TaskImportController --no-interaction`) con un método privado `createTasks(array $preview, ?Project $project, User $user): int` que, en `DB::transaction`, crea en `tasks` cada tarea con estado `ok`/`warning`: `title`, `type`, `priority`, `status = backlog`, `assignee_id`, `description = toTiptap(...)`, `project_id`, `sprint_id = null`, `author_id = $user->id`; devuelve el número creado.
- [X] T005 [P] En `app/Http/Middleware/HandleInertiaRequests.php`, añadir `'importPreview' => $request->session()->get('importPreview')` dentro de `flash`; en `resources/js/types/task.ts` añadir el tipo `ImportPreview` exacto de `data-model.md`.
- [X] T006 Registrar en `routes/web.php` (grupo `auth`) las 4 rutas de `contracts/routes.md`: `tasks.import.preview`, `tasks.import.store`, `projects.tasks.import.preview`, `projects.tasks.import.store` (declarar las de `tasks/import*` antes de cualquier ruta `tasks/{task}` para que no las capture el parámetro); luego `php artisan wayfinder:generate`.

**Checkpoint**: rutas registradas (`php artisan route:list --name=import`).

---

## Phase 3: User Story 1 - Importar tareas a un proyecto (Priority: P1) 🎯 MVP

**Goal**: un miembro importa el `.md` desde el proyecto, ve la vista previa y confirma; las tareas quedan en el backlog del proyecto.

**Independent Test**: escenarios 1–3 y 6–8 de `quickstart.md`.

### Tests for User Story 1 (escribir primero)

- [X] T007 [P] [US1] Crear `tests/Feature/Tasks/TaskImportTest.php` con: (a) preview del fixture en un proyecto → `flash.importPreview` con 3 tareas (títulos, `type` `bug`/`bug`/`test`, `priority` `high`/`medium`/`low`), 1 excluido y `counts.importable = 3`, y **ninguna** tarea creada; (b) store del fixture → 3 tareas con `status = backlog`, `project_id`, `sprint_id = null`, `author_id` = usuario, descripción JSON de Tiptap con marca `code` en `` `generatePin()` ``, y flash "Se importaron 3 tareas."; (c) Responsable "Emiliano" con un miembro "Emiliano Landa" → asignado; sin coincidencia → `assignee_id` null y aviso; (d) segundo store del mismo archivo → 0 tareas nuevas, preview con estado `duplicate` y `importable = 0`; (e) no miembro → 404; proyecto archivado → 403 y nada creado; (f) si falla la creación de una tarea (p. ej. forzar excepción) no queda ninguna (transacción).

### Implementation for User Story 1

- [X] T008 [US1] En `app/Http/Controllers/TaskImportController.php` implementar `projectPreview(ImportTasksRequest $request, Project $project)` y `projectStore(...)`: `abort_unless($project->isMember($user), 404)`; `$this->authorize('create', [Task::class, $project])`; preview → `back()->with('importPreview', $request->analyze($project, $user))`; store → re-analiza, si `importable = 0` → `back()->withErrors(['content' => 'No hay tareas válidas para importar.'])`, si no `createTasks()` y `back()->with('success', "Se importaron {$n} tareas.")`.
- [X] T009 [US1] Crear `resources/js/Components/Tasks/TaskImportModal.vue` (props `open`, `projectId: number | null`, `destinationLabel: string`; emite `close`): paso 1 → input `type="file" accept=".md,text/markdown"` que lee con `await file.text()` + textarea alternativa "o pega el contenido"; botón "Revisar" envía `useForm({ content })` a la ruta de preview (Wayfinder; proyecto o sueltas según `projectId`) con `preserveScroll`; paso 2 → lee `page.props.flash.importPreview` y lista cada tarea con estado (iconos/colores: ok, aviso ámbar, error rojo, "Ya existe"/"Repetida" atenuadas), tipo, prioridad, responsable resuelto, extracto y mensajes; sección "No incluidos"; footer con "Cancelar" (texto) y primario "Importar {importable} de {total}" (deshabilitado si 0) que envía el mismo `content` a la ruta store y cierra al éxito. Seguir el estilo de `ProjectFormModal.vue`/`TaskFormModal.vue` (encabezado, footer con borde, botones). Activar el skill `inertia-vue-development`.
- [X] T010 [US1] En `resources/js/pages/Projects/Show.vue`, añadir un botón secundario "Importar" (icono `upload_file`) en el encabezado de la sección "Tareas" junto al selector Lista/Backlog/Kanban, visible solo si `canWriteTasks`; abre `TaskImportModal` con `projectId = project.id` y `destinationLabel = project.title`.

**Checkpoint**: T007 en verde; escenarios 1–3 y 6–8 pasan.

---

## Phase 4: User Story 2 - Importar tareas sueltas al Backlog general (Priority: P1)

**Goal**: importar desde `/backlog`; tareas sin proyecto, responsable solo el propio usuario.

**Independent Test**: escenario 5 de `quickstart.md`.

### Tests for User Story 2 (escribir primero)

- [X] T011 [P] [US2] Añadir a `tests/Feature/Tasks/TaskImportTest.php`: (a) store sin proyecto → tareas con `project_id` null y `status = backlog`; (b) Responsable igual al nombre del usuario → asignado a él; otro nombre → null con aviso; (c) duplicado contra tareas sueltas propias (mismo título, `project_id` null, autor o responsable = usuario) → `duplicate`; una tarea suelta de otro usuario con el mismo título **no** cuenta como duplicado.

### Implementation for User Story 2

- [X] T012 [US2] En `app/Http/Controllers/TaskImportController.php` implementar `preview(ImportTasksRequest $request)` y `store(...)` (destino sin proyecto) con el mismo flujo que T008, usando `analyze(null, $user)`.
- [X] T013 [US2] En `resources/js/pages/Backlog/Index.vue`, añadir el botón secundario "Importar" (icono `upload_file`) en el `<header>` junto a la tarjeta del sprint; abre `TaskImportModal` con `projectId = null` y `destinationLabel = 'Backlog general'`.

**Checkpoint**: T011 en verde; escenario 5 pasa.

---

## Phase 5: User Story 3 - Errores de formato claros (Priority: P2)

**Goal**: errores por tarea y globales, en español, antes de crear nada; se puede importar solo lo válido.

**Independent Test**: escenario 4 de `quickstart.md`.

### Tests for User Story 3 (escribir primero)

- [X] T014 [P] [US3] Añadir a `tests/Feature/Tasks/TaskImportTest.php`, con un dataset de contenidos: Tipo "Mejora" → `error` "Tipo «Mejora» no es válido (Tarea, Feature, Bug, Test)."; tarea sin viñeta Responsable → `error` "Falta el campo Responsable."; prioridad Alta bajo sección Media → `warning` y se usa `high`; sin `(Origen:` → `warning`; título > 255 → `error`; nombre repetido en el archivo → la segunda `repeated`; archivo sin `###` → error de validación "No encontramos tareas con el formato esperado."; 201 tareas → error "más de 200 tareas"; store de un archivo mixto (2 válidas + 1 error) crea exactamente 2.

### Implementation for User Story 3

- [X] T015 [US3] Ajustar `ImportTasksRequest::analyze()` si algún caso de T014 falla, y en `TaskImportModal.vue` mostrar los errores globales de `form.errors.content` en el paso 1 y agrupar en el paso 2 un resumen "N con error no se importarán" encima de la lista.

**Checkpoint**: T014 en verde; escenario 4 pasa.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T016 Ejecutar `vendor/bin/pint --dirty --format agent`.
- [X] T017 [P] Ejecutar `npm run types:check` y corregir errores.
- [X] T018 Ejecutar `php artisan test --compact tests/Feature/Tasks tests/Feature/Backlog tests/Feature/Projects` y luego la suite completa.
- [ ] T019 Validar en navegador los escenarios 1–8 de `specs/024-bulk-task-import/quickstart.md` (desktop y móvil para el modal).

---

## Dependencies & Execution Order

- **Setup (T001)** → **Foundational (T002–T006)** → historias.
- **US1 (T007–T010)**: MVP; incluye el modal compartido.
- **US2 (T011–T013)**: depende de T009 (modal) para la UI; el backend es independiente de US1.
- **US3 (T014–T015)**: depende de T002–T003 y T009.
- **Polish (T016–T019)** al final.

### Parallel Opportunities

- T004 y T005 en paralelo tras T002–T003.
- Los bloques de tests T007, T011 y T014 se escriben en el mismo archivo: hacerlos en secuencia o coordinar.
- T010 y T013 (páginas distintas) en paralelo una vez hecho T009.

```text
# Tras T003:
T004 TaskImportController.createTasks   |   T005 shared prop + tipo ImportPreview
# Tras T009:
T010 [US1] Projects/Show.vue   |   T013 [US2] Backlog/Index.vue
```

## Implementation Strategy

### MVP (US1)

Setup + Foundational + US1 → importar el ejemplo del formato a un proyecto (escenarios 1–3).

### Entrega incremental

1. MVP → validar.
2. US2 (sueltas en Backlog general) → validar.
3. US3 (errores claros, importar solo lo válido) → validar.
4. Polish.

## Phase 7: Convergence

- [X] T020 Validar el tamaño de `content` en bytes (`strlen($content) > 1048576` → error en `content` "El archivo no puede superar 1 MB.") en `app/Http/Requests/ImportTasksRequest.php`, porque la regla `max:1048576` sobre un string cuenta caracteres; añadir un test en `tests/Feature/Tasks/TaskImportTest.php` con un contenido multibyte de menos de 1 048 576 caracteres pero más de 1 MB per FR-013 (partial)
- [X] T021 Quitar el campo `description` completo de las tareas del `importPreview` que se guarda en flash en `app/Http/Controllers/TaskImportController.php` (mantener `excerpt`; `store` vuelve a analizar el contenido), o documentarlo en `specs/024-bulk-task-import/data-model.md` si se decide conservarlo per data-model `ImportPreview` (unrequested)
- [X] T022 Añadir un test en `tests/Feature/Tasks/TaskImportTest.php` que verifique que el Responsable coincide sin distinguir acentos (archivo "Lucia" con miembro "Lucía Pérez" → asignada) per FR-005 (partial)
