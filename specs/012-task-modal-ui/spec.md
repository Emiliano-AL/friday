# Feature Specification: Modal de Tarea Mejorado (Secciones Colapsables y Acciones Alineadas)

**Feature Branch**: `012-task-modal-ui`

**Created**: 2026-09-30

**Status**: Draft

**Input**: User description: "Necesitamos mejorar la UI del modal de nueva tarea, usando el mcp de stitch en el proyecto 3835093330700486424, busca el item "Friday — Modal Tarea Mejorado (Colapsable & Acciones Alineadas)" y aplica las mejoras, las secciones colapsables, el alto del input del campo de descripcion, etc."

**Design source**: Stitch, proyecto `3835093330700486424`, pantalla "Friday — Modal Tarea Mejorado (Colapsable & Acciones Alineadas)" (referencia visual de este spec).

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Secciones secundarias colapsables (Priority: P1)

Un miembro del equipo abre el diálogo para crear o editar una tarea y se
encuentra un formulario compacto: el título, los datos principales (tipo,
estado, prioridad y responsable) y la descripción están siempre visibles,
mientras que las secciones opcionales "Vinculación y alcance" (proyecto y
sprint) y "Estimación y fechas" aparecen colapsadas como acordeones. Con un
clic en el encabezado de cada sección (o en su control "Mostrar"/"Ocultar")
la sección se despliega o repliega con una transición suave, sin perder el
contenido que se hubiera seleccionado.

**Why this priority**: Es el cambio con mayor impacto en la experiencia: hoy
el diálogo muestra todas las secciones desplegadas y obliga a recorrer un
formulario largo para llegar a la descripción y a las acciones. Componer la
tarea (título + datos principales + descripción + guardar) es el flujo más
frecuente y queda completo y visible de un vistazo.

**Independent Test**: Abrir el diálogo de crear tarea, verificar que ambas
secciones opcionales aparecen colapsadas, expandir "Vinculación y alcance",
seleccionar un proyecto y un sprint, colapsarla de nuevo y comprobar que al
guardar la tarea queda vinculada correctamente; repetir el ida y vuelta del
acordeón sin que el contenido seleccionado se pierda.

**Acceptance Scenarios**:

1. **Given** el diálogo de crear tarea recién abierto, **When** el miembro lo examina, **Then** las secciones "Vinculación y alcance" y "Estimación y fechas" aparecen colapsadas y los campos siempre visibles (título, tipo, estado, prioridad, responsable, descripción) se muestran sin necesidad de hacer scroll.
2. **Given** una sección colapsada, **When** el miembro pulsa sobre su encabezado (o su control Mostrar/Ocultar), **Then** el contenido se despliega con una transición suave y el control pasa a indicar "Ocultar" con el indicador orientado hacia arriba.
3. **Given** una sección expandida, **When** el miembro vuelve a pulsar su encabezado, **Then** el contenido se repliega de forma suave y el control vuelve a indicar "Mostrar".
4. **Given** la sección "Vinculación y alcance" expandida con un proyecto y un sprint seleccionados, **When** el miembro la colapsa y vuelve a expandirla (o guarda directamente), **Then** el proyecto y el sprint seleccionados se conservan.
5. **Given** el diálogo de editar una tarea que ya tiene proyecto o sprint, **When** el miembro abre el diálogo, **Then** la sección "Vinculación y alcance" aparece expandida para dejar visibles los valores ya asignados; si la tarea no tiene proyecto ni sprint, aparece colapsada.
6. **Given** cualquier modo de apertura del diálogo, **When** se abre, **Then** el estado expandido/colapsado se restablece al valor por defecto (no persiste entre aperturas).
7. **Given** un intento de guardar con un error de validación en un campo dentro de una sección colapsada, **When** el sistema rechaza el guardado, **Then** esa sección se expande automáticamente y el mensaje de error queda visible junto al campo correspondiente.
8. **Given** un miembro que usa teclado o lector de pantalla, **When** navega por el encabezado de una sección colapsable, **Then** el control es un botón enfocable que expone si el contenido está expandido o colapsado.

---

### User Story 2 - Área de descripción con altura cómoda (Priority: P2)

El miembro redacta la descripción de la tarea en un área de edición con una
altura mínima generosa (del orden de 10 líneas o 240 píxeles): puede escribir
descripciones y listas largas sin que el área crezca desmedidamente ni que el
diálogo supere la altura de la pantalla, porque el contenido excedente se
recorre con un desplazamiento interno propio del área. La barra de formato
existente sigue disponible junto al área.

**Why this priority**: Hoy el área de descripción es baja y se queda corta
justo donde más se escribe; un área alta y estable mejora directamente la
redacción de criterios de aceptación y notas. Es independiente de US1 (el
área de descripción permanece siempre visible, no es colapsable).

**Independent Test**: Abrir el diálogo, enfocar la descripción y pegar un
texto de unas 20 líneas: comprobar que el área muestra alrededor de 10 líneas
completas sin crecer fuera del diálogo, que el texto excedente se recorre con
scroll interno, y que la barra de formato del editor sigue accesible.

**Acceptance Scenarios**:

1. **Given** el diálogo abierto, **When** el miembro examina el área de descripción, **Then** su altura mínima permite ver del orden de 10 líneas de texto (aproximadamente 240 píxeles) sin escribir nada.
2. **Given** un texto de descripción más largo que el área visible, **When** el miembro continúa escribiendo, **Then** el área no se desborda ni empuja las acciones fuera del diálogo: el contenido excedente se recorre con un desplazamiento interno del propio área.
3. **Given** el diálogo en un viewport de altura estándar de escritorio, **When** todas las secciones están expandidas y el área de descripción tiene contenido largo, **Then** el cuerpo del diálogo se recorre con scroll propio sin superar la altura de la pantalla y las acciones del pie permanecen visibles/ancladas.
4. **Given** el área de descripción, **When** el miembro usa su barra de formato, **Then** las opciones de formato existentes (negrita, cursiva, código, listas) siguen funcionando como antes.

---

### User Story 3 - Barra de acciones unificada y alineada (Priority: P3)

El pie del diálogo concentra todas las acciones en una sola fila alineada: a
la izquierda, la acción destructiva "Eliminar tarea" (solo al editar, con su
confirmación) y, en la creación, la casilla "Crear otra tarea al guardar"; a
la derecha, "Cancelar" y la acción primaria ("Crear Tarea" o "Guardar
cambios"). Ya no existe una caja independiente de "Eliminar tarea" flotando
por encima del pie.

**Why this priority**: Refuerza la jerarquía de acciones y elimina el bloque
de eliminación actual, que rompe el ritmo visual antes del pie. Depende de
las mejoras anteriores para lucir su efecto (el pie queda siempre al final),
pero el formulario sigue siendo completamente usable aunque solo se entregue
este cambio junto al comportamiento actual de las secciones.

**Independent Test**: Abrir el diálogo en modo edición y comprobar que
"Eliminar tarea", "Cancelar" y "Guardar cambios" conviven en la misma fila
del pie, que la eliminación sigue pidiendo confirmación y que, en modo
creación, la casilla "Crear otra tarea al guardar" ocupa el lado izquierdo
del pie y sigue funcionando.

**Acceptance Scenarios**:

1. **Given** el diálogo en modo edición, **When** el miembro mira el pie, **Then** "Eliminar tarea" está a la izquierda y "Cancelar" y "Guardar cambios" a la derecha, todos en la misma fila, y ya no aparece la caja independiente de eliminación sobre el pie.
2. **Given** el diálogo en modo creación, **When** el miembro mira el pie, **Then** no se ofrece "Eliminar" y la casilla "Crear otra tarea al guardar" está en el lado izquierdo.
3. **Given** el modo edición, **When** el miembro pulsa "Eliminar tarea", **Then** el sistema pide confirmación antes de eliminar y, tras confirmar, cierra el diálogo y elimina la tarea.
4. **Given** cualquier modo, **When** el guardado está en curso, **Then** la acción primaria refleja el estado de procesamiento (deshabilitada/indicador) y el resto de acciones del pie no interrumpen el envío.
5. **Given** cualquier modo, **When** el miembro pulsa "Cancelar", **Then** el diálogo se cierra sin guardar cambios.

---

### Edge Cases

- ¿Qué pasa si una sección colapsada contiene un campo con error de validación? La sección se expande automáticamente al mostrar el error, jamás se deja el error oculto dentro de una sección colapsada.
- ¿Qué pasa al abrir el diálogo de edición desde un proyecto o sprint concreto? Los valores preseleccionados se conservan y la sección "Vinculación y alcance" se muestra expandida (regla de la US1); los campos bloqueados por contexto (proyecto fijo) siguen deshabilitados.
- ¿Qué pasa si cambio de proyecto con la sección colapsada? El comportamiento actual se conserva: el sprint seleccionado se limpia si ya no pertenece al nuevo proyecto, sin necesidad de expandir la sección.
- ¿Qué pasa con la sección "Estimación y fechas" si su funcionalidad llega a habilitarse? El patrón acordeón ya aplicado se reutiliza y los campos seguirán deshabilitados mientras su funcionalidad no exista (convención "Próximamente" del proyecto).
- ¿Qué pasa con atajos de teclado existentes (por ejemplo, enviar el formulario con Ctrl/Cmd + Enter)? Se conservan intactos con la nueva estructura.
- ¿Qué pasa con títulos o descripciones muy largos? Se comportan como hoy (límite de 255 caracteres en el título); el área de descripción mantiene su altura y recorre el excedente con scroll interno.
- ¿Qué pasa en viewports pequeños? El diálogo se adapta a una sola columna como ya lo hace hoy; el pie puede apilar sus grupos (izquierda/derecha) en dos filas manteniendo el orden lógico.
- ¿Qué pasa con el estado de las secciones al reabrir el diálogo? Se restablece al valor por defecto: colapsadas, salvo "Vinculación y alcance" cuando la tarea ya tiene proyecto o sprint.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: El diálogo de crear y de editar tarea debe organizarse en tres grupos: (a) siempre visibles — título, datos principales (tipo, estado, prioridad, responsable) y descripción; (b) colapsables — "Vinculación y alcance" y "Estimación y fechas"; (c) barra de acciones del pie.
- **FR-002**: Cada sección colapsable debe ofrecer un control único en su encabezado (botón enfocable) que despliegue/repliegue su contenido con una transición suave, con indicadores "Mostrar"/"Ocultar" y una orientación visual que refleje el estado (por ejemplo, un indicador que rota o apunta hacia arriba/al lado).
- **FR-003**: Las secciones colapsables deben iniciar colapsadas, salvo "Vinculación y alcance" al editar (o crear desde) una tarea con proyecto o sprint ya asignados, que debe iniciar expandida.
- **FR-004**: Colapsar y reexpandir una sección nunca debe perder ni modificar los valores seleccionados en sus campos; guardar con una sección colapsada debe enviar sus valores igualmente.
- **FR-005**: El estado expandido/colapsado de las secciones no debe persistir: cada apertura del diálogo restablece el valor por defecto.
- **FR-006**: Si un error de validación afecta a un campo dentro de una sección colapsada, el sistema debe expandir esa sección y mostrar el mensaje junto al campo correspondiente.
- **FR-007**: El área de edición de la descripción debe tener una altura mínima que permita ver del orden de 10 líneas de texto (aproximadamente 240 píxeles) y debe mantener el texto excedente accesible mediante un desplazamiento interno propio del área, sin crecer de forma ilimitada ni desplazar las acciones fuera del diálogo.
- **FR-008**: La barra de formato del editor de descripción debe seguir disponible junto al área de descripción y conservar sus opciones actuales.
- **FR-009**: El pie del diálogo debe concentrar todas las acciones en una sola fila: a la izquierda, "Eliminar tarea" en modo edición (con confirmación previa) o la casilla "Crear otra tarea al guardar" en modo creación; a la derecha, "Cancelar" y la acción primaria ("Crear Tarea" / "Guardar cambios"). La caja independiente de "Eliminar tarea" actual debe desaparecer.
- **FR-010**: Deben conservarse íntegramente los comportamientos existentes del diálogo: atajo de teclado para guardar (Ctrl/Cmd + Enter), estado de procesamiento en la acción primaria, preselección de proyecto/sprint/estado según el contexto de apertura, reglas de dependencia entre proyecto y sprint (al cambiar de proyecto se limpia el sprint incompatible) y listado de miembros disponibles como responsables.
- **FR-011**: Las secciones y sus encabezados deben mantener las convenciones visuales del proyecto: "Vinculación y alcance" conserva su indicación "Proyecto opcional" y "Estimación y fechas" conserva su indicación "Próximamente" con sus campos deshabilitados, sin datos simulados.

### Key Entities _(include if feature involves data)_

- **Tarea** (existente): no cambia su modelo ni sus reglas de validación; esta feature solo reorganiza su diálogo de creación/edición.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Al abrir el diálogo en un viewport de escritorio estándar (por ejemplo, 1440×900), los campos título, tipo, estado, prioridad, responsable y descripción, junto con las acciones del pie, son visibles sin hacer scroll.
- **SC-002**: El número de interacciones necesarias para pasar de "abrir el diálogo" a "título + datos principales + descripción listos para guardar" se reduce respecto al estado actual: ninguna acción de scroll o de colapso es necesaria en ese flujo principal.
- **SC-003**: El área de descripción muestra al menos 10 líneas de texto completas (aproximadamente 240 píxeles de altura) en su estado inicial y recorre el contenido excedente con scroll interno.
- **SC-004**: El 100 % de los flujos existentes (crear, crear-otra, editar, eliminar con confirmación, validaciones, preselecciones por contexto) sigue funcionando sin regresiones tras el rediseño.
- **SC-005**: Las dos secciones colapsables despliegan y repliegan su contenido en menos de 0,5 segundos con transición percibida como suave por el usuario.

## Assumptions

- El alcance es únicamente la presentación del diálogo de tarea existente: no se añaden ni eliminan campos del dominio, ni se cambian reglas de validación, permisos ni flujos del servidor.
- Las mejoras aplican por igual a los modos crear y editar; la pantalla de referencia en Stitch muestra el modo edición, pero el patrón (acordeones, descripción alta, pie unificado) es el mismo para ambos.
- Se mantiene el editor de texto enriquecido actual para la descripción; la mejora sobre él es su altura y comportamiento de desplazamiento, no su sustitución por otro tipo de campo. La micro-barra de formato y las acciones rápidas del diseño de referencia ya están cubiertas por la barra existente del editor.
- Las secciones colapsables son "Vinculación y alcance" y "Estimación y fechas"; la descripción permanece siempre visible (no colapsable), tal como se aprecia en el diseño de referencia.
- Estado por defecto: ambas secciones colapsadas al crear una tarea independiente; "Vinculación y alcance" expandida cuando la tarea ya tiene proyecto o sprint (edición o creación con contexto). El estado no persiste entre aperturas.
- "Estimación y fechas" sigue siendo informativa/deshabilitada ("Próximamente"); colapsarla adelanta el patrón acordeón para cuando sus funciones se habiliten.
- En modo creación se conserva la casilla "Crear otra tarea al guardar" en el lado izquierdo del pie (en modo edición ese espacio lo ocupa "Eliminar tarea", como en el diseño de referencia).
- El ancho del diálogo puede ajustarse ligeramente hacia la referencia (alrededor de 730 píxeles) si es necesario para que los cuatro campos principales y la descripción respiren; no es un objetivo en sí mismo.
