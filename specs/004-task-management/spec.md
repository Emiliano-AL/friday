# Feature Specification: Gestión de Tareas (Tasks)

**Feature Branch**: `004-task-management`

**Created**: 2026-09-23

**Status**: Draft

**Input**: User description: "Gestión de Tareas (Tasks) — Tareas vinculadas a Sprint: asignadas a iteraciones temporales activas o planificadas. Tareas aisladas / Pendientes rápidos: tareas fuera de sprint para resolución inmediata o backlog general. Campos requeridos por tarea: Título & Descripción (soporte para markdown o rich text). Proyecto: project_id (obligatorio). Sprint: sprint_id (opcional/nullable para tareas aisladas). Responsable: assignee_id (usuario asignado). Tipo: bug, feature, test, other. Prioridad: low, medium, high, urgent. Status: backlog, todo, in_progress, in_review, done. Comentarios: sistema relacional para registrar notas y actualizaciones de avance."

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Crear y listar tareas en un proyecto (Priority: P1)

Un miembro del proyecto crea una tarea con un título obligatorio y, de forma
opcional, una descripción con formato markdown, un responsable, un tipo y una
prioridad; la tarea queda asociada al proyecto y, si no se indica sprint, es
una tarea aislada (pendiente rápido o backlog general). La ve en la lista de
tareas del proyecto junto a sus datos principales. Una tarea siempre pertenece
a un proyecto; el sprint es opcional.

**Why this priority**: Sin altas y listado no existe gestión de tareas; es la
mitad mínima del CRUD y la base sobre la que se apoyan edición, estados,
vinculación a sprints, comentarios y las futuras vistas Backlog y Kanban.

**Independent Test**: Crear una tarea válida en un proyecto activo y comprobar
que aparece en su lista con título, tipo, prioridad, estado y responsable (si
lo tiene); crearla sin sprint y comprobar que figura como tarea aislada.

**Acceptance Scenarios**:

1. **Given** un miembro de un proyecto activo, **When** crea una tarea con título, descripción, tipo, prioridad y responsable válidos, **Then** la tarea se guarda asociada al proyecto y aparece en su lista de tareas.
2. **Given** un miembro de un proyecto activo, **When** crea una tarea sin sprint, **Then** la tarea se guarda como aislada y aparece en la lista identificada como pendiente fuera de sprint.
3. **Given** un miembro de un proyecto activo, **When** crea una tarea sin título, con un responsable que no es miembro del proyecto o con un sprint que pertenece a otro proyecto, **Then** el sistema rechaza la operación mostrando los errores correspondientes y no se guarda nada.

---

### User Story 2 - Editar tareas y avanzar su estado (Priority: P2)

Un miembro corrige el título o la descripción de una tarea, le cambia el tipo,
la prioridad o el responsable, o mueve la tarea por el flujo de estados
(backlog → todo → in_progress → in_review → done). El sistema acepta el cambio
a cualquiera de los estados del flujo definido, de modo que una tarea puede
reabrirse o regresar al backlog cuando el equipo lo necesite.

**Why this priority**: El valor de una tarea está en su seguimiento; sin
edición ni estados, las tareas son solo una lista estática y no reflejan el
avance del proyecto.

**Independent Test**: Editar los campos de una tarea existente y cambiar su
estado, comprobando que la lista refleja los nuevos valores y que un título
vacío es rechazado sin alterar la tarea.

**Acceptance Scenarios**:

1. **Given** una tarea existente en un proyecto activo, **When** un miembro cambia su título, descripción, tipo, prioridad o responsable por valores válidos, **Then** la tarea actualizada aparece en la lista con los nuevos datos.
2. **Given** una tarea existente, **When** un miembro guarda un título vacío o un responsable que no es miembro del proyecto, **Then** el sistema rechaza la operación y la tarea conserva sus datos anteriores.
3. **Given** una tarea en estado "todo", **When** un miembro la pasa a "in_progress" y luego a "done", **Then** el sistema registra cada estado y la lista muestra el estado actual.

---

### User Story 3 - Vincular y desvincular tareas de sprints (Priority: P3)

Un miembro asigna una tarea a un sprint del mismo proyecto para planificar la
iteración, o la retira del sprint para devolverla al backlog general de
tareas aisladas. El sprint debe pertenecer al mismo proyecto que la tarea.

**Why this priority**: Convierte las tareas en unidades de planificación por
iteraciones, que es uno de los objetivos del producto; depende de US1/US2 pero
es independiente en valor.

**Independent Test**: Asignar una tarea aislada a un sprint y comprobar que
aparece vinculada; retirarla del sprint y comprobar que vuelve a ser aislada;
un sprint de otro proyecto es rechazado.

**Acceptance Scenarios**:

1. **Given** una tarea aislada y un sprint del mismo proyecto, **When** un miembro la asigna al sprint, **Then** la tarea queda vinculada a ese sprint y se muestra como tal.
2. **Given** una tarea vinculada a un sprint, **When** un miembro la retira del sprint, **Then** la tarea vuelve a ser aislada sin afectar al sprint ni a sus demás tareas.
3. **Given** un miembro creando o editando una tarea, **When** indica un sprint que pertenece a otro proyecto, **Then** el sistema rechaza la operación con un error en el campo sprint.

---

### User Story 4 - Comentarios en las tareas (Priority: P4)

Cualquier miembro del proyecto registra notas y actualizaciones de avance en
una tarea mediante comentarios de texto. Los comentarios quedan asociados a la
tarea y a su autor, y se muestran en orden cronológico junto a la tarea,
visibles para todos los miembros.

**Why this priority**: Es el mecanismo de colaboración sobre la tarea; aporta
contexto e historial, pero el flujo de trabajo ya es funcional sin él.

**Independent Test**: Añadir un comentario a una tarea y comprobar que aparece
en su historial con autor y contenido; un comentario vacío es rechazado.

**Acceptance Scenarios**:

1. **Given** una tarea de un proyecto, **When** un miembro añade un comentario con texto, **Then** el comentario se guarda con su autor y aparece en el listado cronológico de la tarea.
2. **Given** un miembro redactando un comentario, **When** intenta guardarlo vacío o solo con espacios, **Then** el sistema rechaza la operación pidiendo texto válido.
3. **Given** los comentarios de una tarea, **When** cualquier miembro del proyecto consulta la tarea, **Then** ve todos los comentarios en orden cronológico con su autor.

---

### User Story 5 - Eliminar una tarea (Priority: P5)

El propietario del proyecto elimina una tarea que ya no aplica. La eliminación
pide confirmación, elimina también sus comentarios y no afecta al proyecto, al
sprint ni a las demás tareas.

**Why this priority**: Complementa el CRUD; es menos frecuente que crear o
editar, pero sin ella las tareas erróneas quedarían para siempre.

**Independent Test**: Eliminar una tarea con comentarios tras confirmar y
comprobar que desaparece junto con sus comentarios, sin afectar al proyecto ni
a otras tareas.

**Acceptance Scenarios**:

1. **Given** un proyecto activo con varias tareas, **When** el propietario elimina una de ellas confirmando la acción, **Then** la tarea y sus comentarios desaparecen y el proyecto, sus sprints y sus demás tareas no se ven afectados.
2. **Given** un proyecto activo, **When** el propietario inicia una eliminación pero no la confirma, **Then** no se elimina nada.

---

### User Story 6 - Solo lectura fuera del estado activo y permisos (Priority: P6)

Cuando el proyecto está archivado o completado, sus tareas pasan a solo
lectura: cualquier miembro puede seguir viéndolas y sus comentarios, pero el
sistema bloquea la creación, edición, eliminación y comentarios con un mensaje
claro, y la interfaz no ofrece esas acciones. Quien no es miembro del proyecto
no puede acceder a sus tareas, ni siquiera de solo lectura.

**Why this priority**: Protege la integridad histórica del proyecto terminado
y delimita acceso; es regla transversal que acompaña a las demás historias.

**Independent Test**: Con un proyecto archivado o completado, comprobar que la
lista de tareas sigue visible pero las operaciones de escritura son
rechazadas; con un usuario no miembro, comprobar que no encuentra nada.

**Acceptance Scenarios**:

1. **Given** un proyecto archivado o completado con tareas, **When** un miembro consulta la lista, **Then** ve las tareas sin opciones de crear, editar, eliminar o comentar.
2. **Given** un proyecto archivado o completado, **When** un miembro intenta crear, editar, eliminar o comentar una tarea (por ejemplo manipulando la petición), **Then** el sistema rechaza la operación con un mensaje que indica que el proyecto no admite cambios en su estado actual.
3. **Given** un usuario que no es miembro del proyecto, **When** intenta acceder a la lista de tareas o a cualquier operación, **Then** no encuentra nada (acceso denegado de forma indistinguible de un proyecto inexistente).

---

### Edge Cases

- ¿Qué pasa si el título está vacío o contiene solo espacios? Se rechaza la operación y se pide un título válido.
- ¿Qué pasa si el responsable indicado no es miembro del proyecto? Se rechaza la operación con un error en el campo responsable.
- ¿Qué pasa si el sprint indicado pertenece a otro proyecto? Se rechaza la operación con un error en el campo sprint.
- ¿Qué pasa si no se indica sprint? La tarea se crea aislada, como pendiente fuera de sprint (backlog general).
- ¿Qué pasa si no se indica tipo o prioridad? Se aplican los valores por omisión: tipo "other" y prioridad "medium"; el estado inicial es siempre "backlog".
- ¿Qué pasa si el proyecto está archivado o completado? Las operaciones de escritura se bloquean (servidor) y la interfaz no las ofrece; la lectura sigue disponible.
- ¿Qué pasa si un usuario que no es miembro del proyecto intenta acceder a sus tareas? No encuentra nada (acceso denegado de forma indistinguible de un proyecto inexistente).
- ¿Qué pasa si la descripción contiene formato markdown largo? Se acepta y se conserva tal cual; no se exige editor visual en esta iteración.
- ¿Qué pasa si un comentario está vacío o contiene solo espacios? Se rechaza la operación pidiendo texto válido.
- ¿Qué pasa al eliminar una tarea con comentarios? La tarea y todos sus comentarios se eliminan; el proyecto, el sprint y las demás tareas no se ven afectados.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: El sistema debe permitir a cualquier miembro de un proyecto activo crear tareas en él. Cada tarea tiene un título obligatorio de hasta 255 caracteres y una descripción opcional que admite contenido en formato markdown.
- **FR-002**: El sistema debe permitir asignar a la tarea un responsable opcional; cuando se indique, debe ser un usuario miembro del proyecto (propietario o colaborador).
- **FR-003**: El sistema debe permitir asignar a la tarea un sprint opcional; cuando se indique, debe pertenecer al mismo proyecto que la tarea. Si no se indica sprint, la tarea se crea aislada (pendiente fuera de sprint).
- **FR-004**: Cada tarea tiene un tipo con valores `bug`, `feature`, `test` u `other` (por omisión `other`) y una prioridad con valores `low`, `medium`, `high` u `urgent` (por omisión `medium`).
- **FR-005**: El sistema debe listar las tareas de un proyecto, visibles para todo miembro, ordenadas por recientemente actualizadas, mostrando al menos título, tipo, prioridad, estado, responsable y vinculación a sprint.
- **FR-006**: El sistema debe permitir a cualquier miembro editar el título, la descripción, el tipo, la prioridad, el responsable y el sprint de una tarea mientras el proyecto esté activo, con la misma validación de la creación.
- **FR-007**: El sistema debe permitir a cualquier miembro cambiar el estado de una tarea entre los valores `backlog`, `todo`, `in_progress`, `in_review` y `done`, aceptando el cambio a cualquiera de ellos en cualquier momento.
- **FR-008**: El sistema debe permitir a cualquier miembro añadir comentarios de texto a una tarea mientras el proyecto esté activo; el comentario tiene texto obligatorio y queda asociado a la tarea y a su autor. Los comentarios se muestran en orden cronológico a todo miembro.
- **FR-009**: El sistema debe permitir al propietario del proyecto eliminar una tarea tras una confirmación explícita; la eliminación suprime también sus comentarios y no afecta al proyecto, a sus sprints ni a sus demás tareas.
- **FR-010**: El sistema debe impedir la creación, edición, eliminación y comentarios de tareas cuando el proyecto no esté activo (archivado o completado), rechazando la operación en el servidor con un mensaje que indique que el proyecto no admite cambios en su estado actual; la interfaz no debe mostrar dichas acciones en ese estado.
- **FR-011**: Cualquier miembro del proyecto puede ver y gestionar tareas y comentarios. Quien no es miembro del proyecto no puede acceder a sus tareas ni a sus comentarios, ni siquiera de solo lectura.

### Key Entities _(include if data involved)_

- **Tarea (Task)**: unidad de trabajo del proyecto. Atributos: título (obligatorio, corto), descripción (opcional, markdown), proyecto (obligatorio), sprint (opcional), responsable (opcional, miembro del proyecto), tipo (bug/feature/test/other), prioridad (low/medium/high/urgent) y estado (backlog/todo/in_progress/in_review/done). Relaciones: pertenece a un proyecto, puede pertenecer a un sprint, puede tener un responsable y tiene cero o más comentarios.
- **Comentario (Comment)**: nota o actualización de avance sobre una tarea. Atributos: texto (obligatorio), tarea (obligatoria) y autor (obligatorio, miembro del proyecto). Relaciones: pertenece a una tarea y a un usuario.
- **Proyecto** (existente): contenedor de sus tareas. Su estado (activo, archivado o completado) determina si las tareas admiten gestión o solo lectura.
- **Sprint** (existente): iteración temporal a la que una tarea puede vincularse. Las tareas aisladas tienen sprint sin valor.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Un miembro puede crear una tarea completa (título, descripción, tipo, prioridad y responsable) en menos de 1 minuto.
- **SC-002**: La lista de tareas de un proyecto con hasta 100 tareas carga en menos de 2 segundos.
- **SC-003**: El 100% de los intentos de crear, editar, eliminar o comentar tareas en un proyecto archivado o completado son bloqueados y muestran un mensaje comprensible.
- **SC-004**: Al menos el 90% de los miembros completa el flujo de crear, asignar responsable, cambiar estado y comentar una tarea sin necesitar ayuda.

## Assumptions

- La gestión de tareas ocurre dentro de la vista del proyecto (lista de tareas accesible desde el proyecto), sin una sección global separada; las vistas Backlog y Kanban quedan fuera del alcance de esta iteración y se construirán sobre este modelo.
- Tanto el propietario como los colaboradores del proyecto pueden crear, editar, cambiar estado, vincular sprints y comentar tareas; solo el propietario puede eliminarlas, siguiendo la convención de permisos de las acciones destructivas del proyecto.
- El estado inicial de toda tarea es `backlog`. El flujo backlog → todo → in_progress → in_review → done define la progresión canónica, pero en esta iteración el sistema acepta el cambio a cualquiera de los cinco estados en cualquier momento (una tarea puede reabrirse o volver al backlog); una máquina de transiciones restrictiva queda para una iteración futura.
- El responsable y el autor de comentarios deben ser miembros del proyecto; el sistema lo valida en cada operación.
- "Soporte para markdown" significa que la descripción (y los comentarios) aceptan y conservan contenido en formato markdown; no se requiere editor visual (WYSIWYG) ni renderizado especial en esta iteración.
- Las tareas no tienen fechas límite, etiquetas, adjuntos, estimaciones ni ordenación manual en esta iteración; la activación y cierre de sprints tampoco forman parte de esta iteración.
- La regla de solo lectura fuera del estado activo se aplica igual que en los sprints: los proyectos archivados o completados bloquean toda escritura en servidor y ocultan las acciones en la interfaz.
