# Research: Carga masiva de tareas desde archivo Markdown

**Fecha**: 2026-10-12 | **Feature**: [spec.md](spec.md) | **Plan**: [plan.md](plan.md)

Sin NEEDS CLARIFICATION (Q1=C, Q2=A resueltas en la spec).

## Hallazgos

- Enums: `TaskType` (`bug`, `feature`, `test`, `other`), `TaskPriority` (`low`, `medium`, `high`, `urgent`), `TaskStatus::Backlog`. "Backlog general" = tareas con estado `backlog`, con o sin proyecto (`BacklogController`).
- `TaskPolicy::create(User, Project)`: miembro + proyecto activo. Tareas sueltas: cualquier usuario autenticado (`TaskController@store`).
- `SaveTaskRequest`: `description` es string ≤ 50 000. El frontend guarda JSON de Tiptap (`{"type":"doc",...}`) y acepta texto plano legado (`normalizeToTiptap`). El editor usa StarterKit, que incluye la marca `code`.
- No existen `app/Support`, `app/Services` ni `app/Actions`; las guías del proyecto piden aprobación para nuevas carpetas base.
- `league/commonmark` llega como dependencia de Laravel, pero el formato es estructural por líneas; un parser por líneas es más simple y da errores por tarea con número de línea.
- Inertia comparte `flash.success/error` desde `HandleInertiaRequests`.
- Usuario demo `landa@mail.com` se llama "LandaE": el Responsable por defecto del formato ("Emiliano") no coincidirá por nombre → la tarea queda sin responsable con aviso (FR-005).

## Decisiones

### D1. Flujo en dos pasos sin estado persistido
- **Decision**: (1) `POST .../tasks/import/preview` con el contenido del archivo → el servidor analiza y responde `back()` con `flash.importPreview`; (2) `POST .../tasks/import` con el **mismo contenido** → el servidor vuelve a analizar, recalcula duplicados y crea las tareas válidas en una transacción.
- **Rationale**: FR-006/007/008; sin tablas temporales ni sesiones largas; el segundo análisis garantiza que lo creado coincide con lo validado en ese momento (p. ej. si alguien creó una tarea con el mismo nombre entre medias).
- **Alternativas**: guardar la vista previa en sesión/tabla → estado que caducar y limpiar; endpoint JSON con `useHttp` → contradice el patrón de props de Inertia del proyecto.

### D2. El navegador lee el archivo y envía su texto
- **Decision**: el modal usa `File.text()` en el cliente y envía `content` (string ≤ 1 MB). También permite pegar el texto.
- **Rationale**: evita multipart/almacenamiento; los mismos datos sirven para preview y confirmación.

### D3. Parser por líneas en `App\Http\Requests\ImportTasksRequest`
- **Decision**: el FormRequest valida `content` (requerido, string, ≤ 1 048 576 bytes) y expone `parsedTasks(): array` (un parser por líneas: `##` define sección/prioridad, `###` abre tarea, viñetas `- **Campo:** valor`, sección "No incluidos" ignorada). Sin carpeta nueva.
- **Rationale**: Constitución V; se mantiene en la estructura existente. Si el usuario aprueba `app/Support`, el parser puede extraerse sin cambiar comportamiento.

### D4. Descripción → JSON de Tiptap con marcas de código
- **Decision**: convertir la descripción en `{"type":"doc","content":[{"type":"paragraph","content":[...]}]}` donde los tramos entre backticks son nodos `text` con `marks: [{"type":"code"}]`.
- **Rationale**: FR-009; se ve igual que una descripción creada desde el editor.

### D5. Resolución de Responsable
- **Decision**: normalizar con `Str::ascii` + minúsculas + trim; coincide si es igual al nombre completo de una persona o a su primera palabra; si hay 0 o >1 coincidencias → sin responsable + aviso. Personas: propietario + miembros del proyecto; en sueltas, solo el usuario actual.
- **Rationale**: FR-005 y el caso "Emiliano" vs "Emiliano Landa".

### D6. Duplicados
- **Decision**: clave = `mb_strtolower(trim(preg_replace('/\s+/', ' ', título)))`; se compara contra títulos del destino (tareas del proyecto, o tareas sueltas del usuario: `project_id` null y `author_id` o `assignee_id` = usuario) y contra los anteriores del mismo archivo.
- **Rationale**: FR-012 / SC-006.

### D7. Puntos de entrada
- **Decision**: botón "Importar" (icono `upload_file`) en el panel de Backlog del proyecto (`Projects/Show`, tab Sprints y tareas → vista Backlog) y en el encabezado de `/backlog`. Un solo componente `TaskImportModal.vue` con prop `projectId: number | null`.
