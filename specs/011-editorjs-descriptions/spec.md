# Feature Specification: Editor de contenido enriquecido en descripciones (EditorJS)

**Feature Branch**: `011-editorjs-descriptions`

**Created**: 2026-09-30

**Status**: Draft

**Input**: User description: "Necesito que me ayudes a incluir editorsjs para la descripcion de una task, proyecto o sprint. te dejo la documentacion https://editorjs.io/base-concepts/"

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Descripción enriquecida de tareas (Priority: P1)

Quien crea o edita una tarea escribe su descripción en un editor de
contenido por bloques (párrafos, encabezados, listas, citas y fragmentos de
código, con negritas, cursivas y enlaces en línea) en lugar del área de texto
plano actual. El contenido se guarda con la tarea y se muestra formateado
donde corresponda, sin perder lo que ya estaba escrito en tareas existentes.

**Why this priority**: Es la superficie de descripción más usada del producto
y además cumple la promesa "Próximamente" que la app ya hace en el diálogo de
tarea (barra de herramientas de marcado con vista previa). Entregar el editor
compartido con las tareas habilita a proyecto y sprint con cambios mínimos.

**Independent Test**: Crear una tarea con descripción con encabezado, lista y
un enlace; guardar y volver a abrir el diálogo: el contenido aparece igual;
una tarea creada antes de la feature sigue mostrando su descripción de texto
plano sin pasos manuales.

**Acceptance Scenarios**:

1. **Given** el diálogo de tarea en modo crear, **When** el usuario escribe una descripción con varios bloques (encabezado, lista, cita) y guarda, **Then** la tarea persiste el contenido estructurado y al reabrir el diálogo el editor muestra exactamente esos bloques.
2. **Given** una tarea existente con descripción de texto plano, **When** el usuario abre su edición tras el despliegue, **Then** el editor muestra ese texto como un párrafo normal, sin pasos de conversión manuales y sin pérdida de contenido.
3. **Given** una tarea cuyo contenido guardado no es contenido estructurado válido, **When** se presenta en el editor o en pantallas de solo lectura, **Then** se muestra como texto plano sin romper la pantalla ni bloquear la edición.
4. **Given** el diálogo de tarea, **When** el usuario deja la descripción vacía, **Then** la tarea se guarda igualmente (la descripción sigue siendo opcional).

---

### User Story 2 - Descripción enriquecida de proyectos (Priority: P2)

El propietario edita la descripción del proyecto con el mismo editor de
bloques desde el diálogo del proyecto, y la ficha del proyecto muestra la
descripción ya formateada (encabezados, listas y enlaces renderizados) en
lugar del texto plano con etiquetas visibles.

**Why this priority**: Reutiliza el editor de US1; la descripción del
proyecto es visible para todo colaborador y es de las primeras superficies
que un nuevo miembro lee.

**Independent Test**: Editar la descripción de un proyecto con una lista y un
enlace, guardar y ver la ficha: el contenido aparece formateado; un proyecto
con descripción previa de texto plano sigue legible.

**Acceptance Scenarios**:

1. **Given** el diálogo de edición del proyecto, **When** el propietario guarda una descripción con bloques, **Then** la ficha del proyecto renderiza el contenido formateado para cualquier miembro que la consulte.
2. **Given** un proyecto con descripción de texto plano existente, **When** se abre su ficha tras el despliegue, **Then** la descripción se muestra como párrafo normal sin pasos manuales.

---

### User Story 3 - Objetivo enriquecido de sprints (Priority: P3)

El propietario redacta el objetivo del sprint con el editor de bloques desde
el diálogo de sprint, y el detalle del sprint muestra el objetivo formateado
bajo el encabezado.

**Why this priority**: Completa las tres superficies pedidas; el objetivo del
sprint es el campo más corto de los tres y el de menor frecuencia de edición.

**Independent Test**: Editar el objetivo de un sprint con negritas y una
lista, guardar y ver el detalle: el objetivo aparece formateado; un sprint
con objetivo previo de texto plano sigue legible.

**Acceptance Scenarios**:

1. **Given** el diálogo de sprint en modo crear o editar, **When** el propietario guarda un objetivo con bloques, **Then** el detalle del sprint renderiza el objetivo formateado.
2. **Given** un sprint con objetivo de texto plano existente, **When** se abre su detalle tras el despliegue, **Then** el objetivo se muestra como párrafo normal.

---

### Edge Cases

- ¿Qué pasa con las descripciones guardadas antes de la feature? Se convierten automáticamente a un único bloque de párrafo con el mismo texto; ningún dato existente se pierde ni requiere edición manual.
- ¿Qué pasa si el contenido guardado no es contenido estructurado válido (dato corrupto o escrito por medios externos)? Se muestra como texto plano en edición y lectura, sin errores en pantalla.
- ¿Qué pasa con el contenido vacío o con bloques vacíos? Equivale a "sin descripción": los formularios permiten guardar y las vistas no muestran secciones vacías.
- ¿Qué pasa si el usuario pega texto desde un procesador de textos o una página web? El editor normaliza el pegado a bloques de texto con formato básico, sin romper la estructura.
- ¿Qué pasa con contenido potencialmente hostil (scripts en el texto)? El renderizado es seguro: el contenido de terceros nunca se ejecuta como código en la aplicación.
- ¿Qué pasa si la descripción supera el tamaño máximo permitido? El formulario lo rechaza con un mensaje claro junto al campo, igual que las demás validaciones.
- ¿Qué pasa en las listas (tableros, filas de tarea, tarjetas)? Siguen mostrando únicamente título y metadatos; nunca renderizan el contenido completo de la descripción.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: El sistema debe sustituir el área de texto plano por un editor de contenido por bloques en los tres formularios: descripción de tarea (diálogo de tarea), descripción de proyecto (diálogo de proyecto) y objetivo de sprint (diálogo de sprint). El editor debe ofrecer como mínimo: párrafos, encabezados, listas con viñetas y numeradas, citas y fragmentos de código, además de formato en línea (negrita, cursiva y enlaces).
- **FR-002**: El contenido debe guardarse como estructura de bloques (JSON) en los campos existentes (descripción de tarea y proyecto, objetivo de sprint), sin añadir columnas ni tablas nuevas. El contenido previo en texto plano debe migrarse automáticamente a un único bloque de párrafo conservando el texto exacto.
- **FR-003**: Las vistas de lectura deben renderizar el contenido formateado: la ficha del proyecto (descripción), el detalle del sprint (objetivo) y los contextos de visualización de la tarea; el renderizado debe ser seguro, de modo que el contenido escrito por usuarios nunca se ejecute como código. Las listas y tarjetas de tareas no deben renderizar el contenido, solo título y metadatos.
- **FR-004**: Los tres campos siguen siendo opcionales; debe existir un tamaño máximo razonable para el contenido guardado y los errores de validación deben mostrarse en línea junto al campo, con el mismo patrón de validación actual del resto de formularios.
- **FR-005**: El sistema debe degradar con elegancia: cuando un valor guardado no sea contenido estructurado válido, debe tratarse y mostrarse como texto plano en edición y lectura, sin errores visibles ni bloqueos.
- **FR-006**: Las capacidades que el editor no cubre en esta iteración deben mostrarse como "Próximamente" deshabilitadas donde tengan un lugar natural (imágenes y adjuntos, importación/exportación de marcado, herramientas adicionales) y quedar documentadas como propiedades futuras. Con esto se retira la nota "Próximamente" de la barra de herramientas de marcado del diálogo de tarea que la app muestra hoy.
- **FR-007**: La feature incorpora como dependencia el editor de bloques solicitado (núcleo y sus herramientas oficiales de texto), aprobación que el usuario otorga explícitamente al pedir esta funcionalidad; no se añade ni cambia ninguna otra dependencia del proyecto.

### Key Entities _(include if feature involves data)_

- **Tarea / Proyecto / Sprint** (existentes): sus campos de texto largo (descripción, descripción y objetivo respectivamente) pasan de almacenar texto plano a almacenar la estructura de bloques en formato JSON; la semántica del campo (opcional, quién puede editarlo, dónde se muestra) no cambia. Los metadatos existentes (título, estado, fechas, etc.) no se ven afectados.
- **Contenido estructurado**: valor JSON con una lista de bloques (tipo y datos), producido por el editor; sustituye al texto plano en los mismos campos y se migra desde el contenido previo.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: El usuario completa la descripción de una tarea con formato (encabezado + lista + enlace) en menos de 1 minuto, incluida la apertura del diálogo y el guardado.
- **SC-002**: El 100 % de las descripciones y objetivos existentes se muestran correctamente tras el despliegue sin intervención manual y sin pérdida de texto.
- **SC-003**: Las vistas de lectura con contenido formateado cargan en menos de 1 segundo para contenidos de hasta 10 000 palabras.
- **SC-004**: Cero incidentes de ejecución de contenido no autorizado (scripts) a partir del contenido guardado por usuarios.

## Assumptions

- El usuario aprueba explícitamente la incorporación de la dependencia del editor de bloques y sus herramientas oficiales de texto (única dependencia nueva; coherente con la regla del proyecto de no agregar dependencias sin aprobación).
- El área de texto plano se sustituye por completo (no conviven dos modos); el contenido previo migra automáticamente a un bloque de párrafo.
- El alcance es texto estructurado: imágenes, adjuntos y subida de archivos quedan fuera (mostrados como "Próximamente" donde aplique e incluidos en las propiedades futuras documentadas).
- La edición colaborativa en tiempo real no está incluida: si dos personas editan el mismo campo, gana el último guardado (comportamiento actual del resto de formularios).
- La app sigue siendo solo modo claro y la experiencia se evalúa en desktop y móvil según las reglas de diseño existentes.
- La sincronización del editor con el ciclo de apertura/cierre de los diálogos existentes (foco inicial, ESC, click-fuera, doble envío) mantiene el comportamiento actual de cada formulario.
