# Feature Specification: Backlog General — Captura Rápida de Pendientes

**Feature Branch**: `018-backlog-quick-capture`

**Created**: 2026-10-08

**Status**: Draft

**Input**: User description: "vamos a trabajar en un pendiente, el backlog la ruta es /backlog, la idea es que se puedan capturar tareas/penientes de manera rapida, para que no se olviden y mas adelante hacer la asignacion a un proyecto, definir prioridad o entrega, etc. usando el MCP de stitch puedes ver la UI el poryecto es 3835093330700486424 y el item es Backlog home"

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Captura rápida de un pendiente (Priority: P1)

Un usuario autenticado entra a la ruta `/backlog` y encuentra al inicio de la
página un único campo de captura. Escribe el título de una idea o pendiente y
presiona Enter: el pendiente se guarda de inmediato y aparece al inicio de la
lista, con un identificador legible asignado automáticamente. No es necesario
elegir proyecto, responsable, prioridad ni ningún otro dato en el momento de
capturar; la idea queda a salvo para ser organizada después.

**Why this priority**: El propósito central de la funcionalidad es que ninguna
idea se pierda en el momento en que surge. Si solo se implementara esta
historia, ya se tendría una bandeja de captura mínima y valiosa.

**Independent Test**: Escribir un título en el campo de captura, presionar
Enter y comprobar que el pendiente aparece de inmediato al inicio de la lista
con su identificador; recargar la página y comprobar que sigue ahí.

**Acceptance Scenarios**:

1. **Given** un usuario autenticado en `/backlog`, **When** escribe un título y presiona Enter, **Then** el pendiente se guarda y aparece de inmediato al inicio de la lista con un identificador legible.
2. **Given** el campo de captura, **When** el usuario intenta guardar sin título o con solo espacios en blanco, **Then** el sistema muestra un error junto al campo y no guarda nada.
3. **Given** un pendiente recién capturado, **When** el usuario recarga la página o vuelve a entrar a `/backlog`, **Then** el pendiente sigue visible e intacto.
4. **Given** un usuario en cualquier parte de la aplicación, **When** usa el acceso "Nueva Tarea" de la navegación, **Then** llega al campo de captura del backlog.

---

### User Story 2 - Bandeja organizada con resumen, secciones y filtros (Priority: P2)

El backlog muestra todos los pendientes del usuario en una lista ordenada por
prioridad, agrupada en secciones visibles (Alta y urgente, Media, Baja e
ideas). Un encabezado resume el total de pendientes y sus puntos, y unos
filtros permiten ver "Todas", "Sin proyecto" o "Con proyecto", cada uno con su
conteo. Cuando no hay nada que mostrar, la bandeja invita a capturar el primer
pendiente.

**Why this priority**: Una vez que las ideas fluyen, el usuario necesita
revisar y procesar la bandeja sin fricción; el orden por prioridad y los
filtros distinguen lo que ya está vinculado a un proyecto de lo que aún no.

**Independent Test**: Con varios pendientes de distintas prioridades y con y
sin proyecto, comprobar que las secciones y los conteos de los filtros
coinciden exactamente con lo listado, y que el resumen del encabezado es
correcto.

**Acceptance Scenarios**:

1. **Given** pendientes con distintas prioridades, **When** el usuario ve la lista, **Then** cada pendiente aparece en la sección que corresponde a su prioridad y dentro de ella ordenado por fecha de captura.
2. **Given** pendientes con y sin proyecto, **When** el usuario filtra por "Sin proyecto" o "Con proyecto", **Then** solo se listan los que cumplen el filtro y los conteos de los filtros son exactos.
3. **Given** cualquier estado de la bandeja, **When** el usuario mira el encabezado, **Then** ve el total de pendientes y la suma de sus puntos.
4. **Given** una bandeja vacía o un filtro sin resultados, **When** el usuario la observa, **Then** ve un estado vacío claro que le indica qué hacer a continuación.

---

### User Story 3 - Triage: completar y asignar desde el panel de detalle (Priority: P3)

Al hacer clic en un pendiente de la lista se abre un panel de detalle donde el
usuario puede editar su título, descripción (con formato), tipo, prioridad,
puntos y responsable, y —lo más importante— asignarlo a uno de sus proyectos.
Al cambiar la prioridad, el pendiente se reubica en la sección correspondiente
de la lista; al asignarlo a un proyecto, deja de contar como "sin proyecto".
El usuario también puede eliminar un pendiente, con una confirmación previa.

**Why this priority**: La captura sin organizar rápidamente se convierte en
ruido; el triage desde el detalle es el segundo acto natural del flujo
"capturar ahora, organizar después" que pide esta funcionalidad.

**Independent Test**: Abrir el detalle de un pendiente sin proyecto, cambiarle
la prioridad y asignarlo a un proyecto existente; comprobar que la lista lo
mueve de sección y de filtro, y que un proyecto inválido es rechazado con
error.

**Acceptance Scenarios**:

1. **Given** un pendiente existente, **When** el usuario edita título, descripción, tipo, prioridad, puntos o responsable con valores válidos, **Then** los cambios se guardan y la lista refleja los nuevos valores.
2. **Given** un pendiente sin proyecto, **When** el usuario lo asigna a uno de sus proyectos, **Then** el pendiente queda vinculado al proyecto, aparece bajo el filtro "Con proyecto" y pasa a ser visible para los miembros del proyecto.
3. **Given** un pendiente, **When** el usuario intenta asignarlo a un proyecto del que no es miembro o deja el título vacío, **Then** el sistema rechaza el cambio, muestra el error y el pendiente conserva sus datos anteriores.
4. **Given** un pendiente en prioridad media, **When** el usuario lo cambia a urgente, **Then** se mueve a la sección de alta prioridad de la lista.
5. **Given** un pendiente, **When** el usuario confirma su eliminación, **Then** desaparece de la lista y deja de ser accesible.

---

### User Story 4 - Definir entrega: asignar un pendiente a un sprint (Priority: P4)

Desde el panel de detalle, un pendiente que ya tiene proyecto puede definir su
entrega asignándose a un sprint activo o próximo de ese proyecto. Un aviso en
la bandeja recuerda cuál es el próximo sprint, para arrastrar trabajo hacia la
planificación. Una vez asignado a un sprint, el pendiente deja de listarse en
el backlog y pasa a formar parte de la planificación del sprint; si el sprint
se cierra y el pendiente sigue sin terminar, regresa al backlog.

**Why this priority**: Completa el ciclo "capturar → organizar → planificar",
pero no es necesaria para que la bandeja de captura aporte valor, por lo que se
prioriza al final.

**Independent Test**: Con un pendiente ya asignado a un proyecto y un sprint
próximo existente, asignarlo al sprint y comprobar que sale del backlog y
aparece en la planificación del sprint; cerrar el sprint y comprobar que
regresa.

**Acceptance Scenarios**:

1. **Given** un pendiente con proyecto y un sprint activo o próximo de ese proyecto, **When** el usuario lo asigna al sprint, **Then** el pendiente deja de listarse en el backlog y pasa al sprint.
2. **Given** un pendiente sin proyecto, **When** el usuario intenta definir su entrega, **Then** el sistema le pide primero asignar un proyecto.
3. **Given** un pendiente asignado a un sprint que se cierra sin completarse, **When** el sprint se cierra, **Then** el pendiente regresa al backlog conservando su proyecto y prioridad.

---

### Edge Cases

- **Título vacío o solo espacios**: se rechaza con un mensaje junto al campo; nada se guarda.
- **Doble envío rápido**: presionar Enter dos veces seguidas no crea pendientes duplicados.
- **Título extremadamente largo**: se acepta hasta un límite razonable y se trunca su visualización en la lista sin perder información.
- **Guardado fallido** (p. ej., pérdida de conexión): se informa del error y el texto capturado se conserva para reintentar sin reescribir.
- **Filtro sin resultados**: se muestra un estado vacío específico ("No hay pendientes sin proyecto") en lugar de una lista vacía genérica.
- **Proyecto inválido en la asignación**: un proyecto inexistente o del que el usuario no es miembro se rechaza y el pendiente queda como estaba.
- **Sprint cerrado con pendientes sin terminar**: los pendientes vuelven al backlog conservando proyecto, prioridad y puntos.
- **Visibilidad de lo no asignado**: un pendiente "sin proyecto" solo lo ve su autor; al asignarse a un proyecto, su visibilidad pasa a regirse por las reglas del proyecto.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: El sistema MUST ofrecer una pantalla de backlog general en la ruta `/backlog`, accesible desde la navegación principal para usuarios autenticados.
- **FR-002**: La pantalla MUST presentar al inicio un campo de captura rápida que permita guardar un pendiente únicamente con su título, confirmando con la tecla Enter.
- **FR-003**: El título MUST ser el único dato obligatorio para capturar un pendiente; proyecto, responsable, prioridad, tipo, puntos y descripción son opcionales y pueden definirse después.
- **FR-004**: El campo de captura MAY permitir definir en línea, sin salir de él, la prioridad, el tipo y el proyecto del pendiente antes de guardar.
- **FR-005**: Al guardar, el pendiente MUST aparecer de inmediato al inicio de su sección, sin recargar la página, con un identificador legible generado automáticamente.
- **FR-006**: Los valores por defecto al capturar deben ser: sin proyecto, sin sprint, sin responsable, prioridad media y estado de pendiente sin planificar.
- **FR-007**: La lista MUST agrupar los pendientes en secciones por prioridad (alta y urgente, media, baja e ideas) y ordenar dentro de cada sección por fecha de captura, de más reciente a más antiguo.
- **FR-008**: El sistema MUST ofrecer los filtros "Todas", "Sin proyecto" y "Con proyecto", cada uno mostrando su conteo exacto de pendientes.
- **FR-009**: El encabezado de la bandeja MUST resumir el total de pendientes visibles y la suma de sus puntos.
- **FR-010**: Al seleccionar un pendiente de la lista, el sistema MUST abrir un panel de detalle con edición de título, descripción con formato, tipo, prioridad, puntos, responsable y proyecto.
- **FR-011**: La asignación de proyecto MUST limitarse a proyectos donde el usuario es miembro; al asignarse, el pendiente pasa a ser visible según las reglas de visibilidad del proyecto.
- **FR-012**: El panel de detalle MUST permitir definir la entrega asignando el pendiente a un sprint activo o próximo de su proyecto, y el pendiente asignado MUST dejar de listarse en el backlog.
- **FR-013**: El sistema MUST permitir eliminar un pendiente únicamente después de una confirmación explícita, respetando la autoría: el autor siempre puede eliminarlo y, una vez asignado a un proyecto, rigen las reglas del proyecto.
- **FR-014**: El sistema MUST mostrar estados vacíos claros cuando la bandeja está vacía o cuando un filtro no tiene resultados, indicando la acción a seguir.
- **FR-015**: Si el guardado de una captura o edición falla, el sistema MUST informar del error y conservar el texto introducido para que el usuario pueda reintentar.
- **FR-016**: Los pendientes sin proyecto MUST ser visibles únicamente para su autor.

### Key Entities _(include if feature involves data)_

- **Pendiente (ítem de backlog)**: una idea o tarea capturada antes de planificarla. Atributos: identificador legible único, título (obligatorio), descripción opcional con formato, tipo, prioridad, puntos opcionales, proyecto (opcional), sprint (opcional), responsable (opcional), autor, estado y fechas de creación y actualización.
- **Proyecto**: entidad existente; el pendiente puede vincularse a uno de los proyectos del usuario, momento a partir del cual hereda sus reglas de visibilidad.
- **Sprint**: entidad existente; define la "entrega" del pendiente. Un pendiente solo puede asignarse a un sprint de su mismo proyecto.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Un usuario puede capturar un pendiente en menos de 5 segundos usando solo el teclado (un campo de texto y Enter), sin necesidad de completar ningún otro dato.
- **SC-002**: El 100% de los pendientes capturados aparecen en la lista de inmediato y permanecen accesibles tras recargar la página, hasta ser eliminados explícitamente.
- **SC-003**: Los conteos de los filtros ("Todas", "Sin proyecto", "Con proyecto") coinciden exactamente con los pendientes efectivamente listados en todo momento.
- **SC-004**: Un usuario puede asignar un pendiente a un proyecto y definir su prioridad desde el panel de detalle en menos de 3 pasos por campo.
- **SC-005**: Un usuario encuentra un pendiente existente en la bandeja en menos de 10 segundos gracias al agrupamiento por prioridad y a los filtros.
- **SC-006**: Ninguna captura se pierde por errores de guardado: cuando falla, el usuario puede reintentar con su texto intacto en el 100% de los casos.

## Assumptions

- Un pendiente puede existir sin proyecto: esta funcionalidad introduce la excepción a la regla actual de que "toda tarea pertenece siempre a un proyecto"; el proyecto es opcional al capturar y se define después en el triage.
- "Entrega" se interpreta como la asignación del pendiente a un sprint activo o próximo de su proyecto.
- Los pendientes que tienen proyecto pero aún no tienen sprint también se listan en el backlog bajo el filtro "Con proyecto".
- Un pendiente sin proyecto es de visibilidad personal (solo su autor); al asignarse a un proyecto, su visibilidad pasa a regirse por las reglas del proyecto.
- Los criterios de aceptación tipo checklist que aparecen en el diseño de referencia quedan fuera del alcance de esta funcionalidad.
- Los puntos son opcionales y editables; su suma se muestra como referencia en el encabezado.
- El identificador legible (p. ej., FRN-104) se genera automáticamente sin intervención del usuario.
- El estado vacío y los mensajes de error se muestran en el idioma de la interfaz actual de la aplicación.
