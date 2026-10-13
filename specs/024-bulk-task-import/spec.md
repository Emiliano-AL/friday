# Feature Specification: Carga masiva de tareas desde archivo Markdown

**Feature Branch**: `024-bulk-task-import`

**Created**: 2026-10-12

**Status**: Draft

**Input**: User description: "Friday recibirá tareas para un proyecto o tareas de backlog de manera masiva mediante un archivo md, para que se carguen en el sistema; estas tareas deberán tener un formato específico (Formato de Tareas — Especificación para IA). El objetivo es hacer una carga masiva de tareas para un proyecto determinado o bien tareas sueltas."

## Formato de entrada (resumen)

El archivo sigue el formato "Formato de Tareas — Especificación para IA":

- `# Título` + una línea de origen.
- Secciones `## Prioridad Urgente|Alta|Media|Baja`.
- Cada tarea: encabezado `### Nombre` seguido de exactamente 4 viñetas, en orden: `**Tipo:**`, `**Prioridad:**`, `**Responsable:**`, `**Descripción:**`.
- Sección opcional `## No incluidos (resueltos o sin acción)`, que **no** genera tareas.

### Correspondencia con Friday

| Campo del archivo | Valores | Campo en Friday |
| --- | --- | --- |
| `### Nombre` | texto, máx. ~80 caracteres (se acepta hasta 255) | Título |
| Tipo | `Tarea`, `Feature`, `Bug`, `Test` | Tipo (Tarea = "otro") |
| Prioridad | `Baja`, `Media`, `Alta`, `Urgente` | Prioridad |
| Responsable | nombre propio | Responsable (se busca entre las personas del proyecto) |
| Descripción | 1–3 oraciones + `(Origen: ...)`, con `código` entre backticks | Descripción (se conserva el formato de código) |

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Importar tareas a un proyecto (Priority: P1)

Un miembro de un proyecto activo abre "Importar tareas" desde el proyecto,
elige un archivo `.md` con el formato, ve una vista previa con las tareas
detectadas (nombre, tipo, prioridad, responsable resuelto) y los problemas
encontrados, y confirma. Las tareas se crean en el backlog de ese proyecto.

**Why this priority**: Es el caso principal: convertir notas de code review o
pendientes en tareas de un proyecto sin capturarlas una por una.

**Independent Test**: Importar el ejemplo del formato (3 tareas + 1 "No
incluido") en un proyecto → la vista previa muestra 3 tareas y 1 excluida →
al confirmar, el backlog del proyecto tiene 3 tareas nuevas con tipo,
prioridad, responsable y descripción correctos.

**Acceptance Scenarios**:

1. **Given** un miembro en un proyecto activo, **When** sube un archivo válido, **Then** ve una vista previa con cada tarea detectada y su tipo, prioridad, responsable y el inicio de la descripción, antes de crear nada.
2. **Given** la vista previa, **When** confirma, **Then** se crean todas las tareas en el backlog del proyecto y se le indica cuántas se crearon.
3. **Given** un archivo con sección "No incluidos", **When** se procesa, **Then** sus elementos se muestran como excluidos y no se crean como tareas.
4. **Given** un Responsable que coincide con una persona del proyecto, **When** se importa, **Then** la tarea queda asignada a esa persona; **Given** que no coincide con nadie, **Then** la tarea queda sin responsable y la vista previa lo avisa.
5. **Given** la vista previa, **When** el usuario cancela, **Then** no se crea ninguna tarea.

---

### User Story 2 - Importar tareas sueltas al backlog general (Priority: P1)

Desde el Backlog general, el usuario importa un archivo y las tareas se crean
sin proyecto, como pendientes del backlog, asignadas según el Responsable
(solo puede ser el propio usuario).

**Why this priority**: Es la segunda mitad pedida ("o bien tareas sueltas").

**Independent Test**: Importar un archivo válido desde el Backlog general →
las tareas aparecen en el Backlog sin proyecto, con el usuario como
responsable cuando el nombre coincide con el suyo.

**Acceptance Scenarios**:

1. **Given** el usuario en el Backlog general, **When** importa un archivo válido y confirma, **Then** las tareas se crean sin proyecto como pendientes del backlog.
2. **Given** un Responsable distinto del usuario, **When** se importa una tarea suelta, **Then** queda sin responsable y la vista previa lo avisa (las tareas sueltas solo pueden asignarse a uno mismo).

---

### User Story 3 - Errores de formato claros (Priority: P2)

Si el archivo no cumple el formato, la vista previa señala qué tarea y qué
campo fallan, con un mensaje en español, para corregir el archivo.

**Why this priority**: El archivo suele generarse con IA; los errores deben
ser fáciles de localizar y corregir.

**Independent Test**: Subir un archivo con un Tipo inválido, una tarea con 3
viñetas y una prioridad que no coincide con su sección → la vista previa
señala las tres con su nombre y motivo.

**Acceptance Scenarios**:

1. **Given** una tarea con Tipo o Prioridad fuera de los valores permitidos, **When** se procesa, **Then** se marca con error indicando el valor recibido y los permitidos.
2. **Given** una tarea sin alguno de los 4 campos o sin nombre, **When** se procesa, **Then** se marca con error indicando el campo faltante.
3. **Given** una tarea cuya Prioridad no coincide con su sección `##`, **When** se procesa, **Then** se usa la Prioridad de la viñeta y se muestra un aviso (no un error).
4. **Given** un archivo sin ninguna tarea reconocible o que no es Markdown, **When** se sube, **Then** se informa que no se encontraron tareas y no se ofrece confirmar.
5. **Given** un archivo con tareas con error y tareas válidas, **When** se muestra la vista previa, **Then** las tareas con error aparecen marcadas como "No se importarán" con su motivo, y el usuario decide: confirmar para importar solo las válidas (el botón indica cuántas, p. ej. "Importar 12 de 15") o cancelar para corregir el archivo.
6. **Given** un archivo en el que ninguna tarea es válida, **When** se muestra la vista previa, **Then** no se ofrece confirmar.

---

### Edge Cases

- **Descripción sin `(Origen: ...)`**: se importa igual con un aviso (no bloquea).
- **Nombre de más de 255 caracteres**: error en esa tarea.
- **Tarea ya existente**: si en el destino (el proyecto, o las tareas sueltas del usuario) ya hay una tarea con el mismo nombre (sin distinguir mayúsculas ni espacios sobrantes), se omite y la vista previa la marca como "Ya existe"; también se omiten los nombres repetidos dentro del mismo archivo (se importa solo la primera aparición). Reimportar el mismo archivo no crea duplicados.
- **Responsable con mayúsculas o acentos distintos** ("emiliano", "EMILIANO"): coincide sin distinguir mayúsculas.
- **Varias personas con el mismo nombre en el proyecto**: queda sin responsable con aviso de ambigüedad.
- **Proyecto archivado o completado**: no se ofrece importar.
- **Archivo grande**: máximo 200 tareas y 1 MB por archivo; más allá, se rechaza con mensaje.
- **Marcado Markdown extra** (negritas en el nombre, líneas en blanco, `---`): se tolera.
- **Codificación**: archivos en UTF-8 con acentos y ñ se leen correctamente.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: Los miembros de un proyecto activo MUST poder importar un archivo `.md` desde la página del proyecto; las tareas se crean en el backlog de ese proyecto, sin sprint.
- **FR-002**: Cualquier usuario MUST poder importar un archivo `.md` desde el Backlog general; las tareas se crean sin proyecto, como pendientes del backlog.
- **FR-003**: El sistema MUST reconocer el formato descrito: tareas `###` con las 4 viñetas Tipo, Prioridad, Responsable y Descripción, agrupadas en secciones `## Prioridad ...`, e ignorar la sección "No incluidos".
- **FR-004**: El sistema MUST mapear Tipo (`Tarea`→otro, `Feature`, `Bug`, `Test`) y Prioridad (`Baja`, `Media`, `Alta`, `Urgente`) a los valores de Friday sin distinguir mayúsculas.
- **FR-005**: El sistema MUST resolver el Responsable buscando por nombre (sin distinguir mayúsculas ni acentos) entre las personas del proyecto; en tareas sueltas, solo el propio usuario; si no hay coincidencia única, la tarea queda sin responsable con aviso.
- **FR-006**: Antes de crear tareas, el sistema MUST mostrar una vista previa con cada tarea (nombre, tipo, prioridad, responsable resuelto, extracto de descripción), los excluidos, los avisos y los errores por tarea.
- **FR-007**: Nada se crea hasta que el usuario confirma; cancelar no deja rastro.
- **FR-008**: La confirmación MUST crear las tareas en una sola operación: o se crean todas las seleccionadas, o ninguna si ocurre un fallo.
- **FR-009**: La descripción MUST conservar el texto completo, incluido `(Origen: ...)`, y mostrar como código lo escrito entre backticks.
- **FR-010**: El sistema MUST validar por tarea los valores permitidos y los campos obligatorios, con mensajes en español que indiquen la tarea, el campo y el motivo.
- **FR-011**: Ante tareas inválidas mezcladas con válidas, la vista previa MUST mostrar qué tareas no se importarán y por qué, y permitir confirmar solo las válidas (indicando el número a importar) o cancelar; si ninguna es válida, no se ofrece confirmar.
- **FR-012**: El sistema MUST omitir las tareas cuyo nombre ya existe en el destino o se repite dentro del mismo archivo (sin distinguir mayúsculas ni espacios sobrantes), marcándolas como "Ya existe" o "Repetida" en la vista previa.
- **FR-013**: El sistema MUST rechazar archivos de más de 1 MB o con más de 200 tareas, y archivos que no sean texto Markdown.
- **FR-014**: Las tareas importadas MUST registrar al usuario que importó como su autor.

### Key Entities _(include if feature involves data)_

- **Tarea**: entidad existente; se crea con título, tipo, prioridad, estado "backlog", responsable opcional, descripción, proyecto opcional y autor.
- **Importación (vista previa)**: resultado temporal del análisis del archivo: tareas detectadas con su estado (válida, con aviso, con error), excluidos y totales. No se guarda tras confirmar o cancelar.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Un usuario importa 20 tareas desde un archivo válido en menos de 1 minuto, frente a capturarlas una por una.
- **SC-002**: El 100% de las tareas de un archivo que cumple el formato se crean con el tipo, la prioridad y la descripción exactos del archivo.
- **SC-003**: El 100% de los errores de formato se muestran antes de crear nada e identifican la tarea y el campo afectados.
- **SC-004**: Ninguna importación deja tareas a medias: o se crean todas las confirmadas o ninguna.
- **SC-005**: Los elementos de "No incluidos" nunca se crean como tareas.
- **SC-006**: Importar dos veces el mismo archivo en el mismo destino no crea ninguna tarea duplicada.

## Assumptions

- Las tareas importadas entran con estado "backlog" y sin sprint; el usuario las planifica después.
- La línea de origen del archivo (`# Título` y la línea siguiente) no se guarda como tarea; cada tarea ya trae su `(Origen: ...)` en la descripción.
- Importar a un proyecto requiere el mismo permiso que crear una tarea en él (miembro y proyecto activo).
- El Responsable por defecto del formato ("Emiliano") se resuelve como cualquier otro nombre; no hay un responsable fijo en el sistema.
- La importación es manual desde la interfaz (subir archivo o pegar contenido); no hay importación automática ni por correo.
