# Feature Specification: Vista previa legible e identificable del proyecto

**Feature Branch**: `021-fix-project-card-preview`

**Created**: 2026-10-10

**Status**: Draft

**Input**: User description: "cuando ajusto un proyecto y le asigno un distintivo, como icono o color, la descripcion del preview se pierde y veo algo como json objects, ademas el color y el icono seleccionado, no se muestran en el preview"

## User Scenarios & Testing _(mandatory)_

### User Story 1 - La vista previa del proyecto muestra su descripción legible (Priority: P1)

Al editar un proyecto —por ejemplo para asignarle un icono o color— y guardar, la
tarjeta del proyecto en el directorio (`/projects`) deja de mostrar su descripción
como texto legible y en su lugar imprime el contenido crudo con forma de objetos
JSON. A partir de esta corrección, la vista previa de la tarjeta muestra siempre
un extracto de texto plano legible de la descripción, sin importar cuántas veces
se edite el proyecto ni qué distintivos se le asignen. Los proyectos con
descripciones antiguas en texto plano siguen viéndose igual.

**Why this priority**: Es la parte principal del reporte: el guardado del
formulario rompe la vista previa, dejando la pantalla del directorio con
contenido ilegible después de una acción tan común como editar un proyecto.

**Independent Test**: Editar un proyecto asignándole solo un color (lo que
re-guarda la descripción) y comprobar que la tarjeta del directorio sigue
mostrando el texto de la descripción; repetir con una descripción larga, una
vacía y un proyecto nunca editado desde la migración de texto enriquecido.

**Acceptance Scenarios**:

1. **Given** un proyecto cuya descripción se guardó en formato de texto enriquecido, **When** un miembro ve la tarjeta en el directorio, **Then** la descripción aparece como texto plano legible (máximo dos líneas), no como estructuras JSON.
2. **Given** un proyecto con descripción antigua en texto plano que no ha vuelto a editarse, **When** se ve su tarjeta, **Then** la descripción se sigue mostrando igual que antes de esta corrección.
3. **Given** un proyecto cuya descripción solo contiene formato sin texto (por ejemplo un bloque vacío), **When** se ve su tarjeta, **Then** no aparece texto basura ni JSON: el párrafo de descripción se omite.
4. **Given** una descripción muy larga, **When** se ve la tarjeta, **Then** se muestra un extracto truncado a dos líneas con el mismo tratamiento visual actual.

---

### User Story 2 - La vista previa refleja el distintivo del proyecto (Priority: P2)

Cuando un proyecto tiene un color de acento o un icono asignados, su tarjeta en
el directorio los muestra: el azulejo del proyecto usa el icono elegido y un
fondo tenue del color elegido. Los proyectos sin distintivo conservan exactamente
la apariencia actual, y la franja de estado de la tarjeta sigue indicando el
estado del proyecto con su color actual.

**Why this priority**: La segunda parte del reporte: asignar el distintivo y no
verlo reflejado en la vista previa hace dudar de que el cambio se haya guardado
(aunque sí se guardó).

**Independent Test**: Asignar icono y color a un proyecto, guardar y comprobar
que la tarjeta del directorio los muestra; comprobar que un proyecto sin
distintivo y la franja/colores de estado se ven exactamente como antes.

**Acceptance Scenarios**:

1. **Given** un proyecto con icono y color asignados, **When** un miembro ve la tarjeta en el directorio, **Then** el azulejo muestra ese icono sobre un fondo tenue de ese color.
2. **Given** un proyecto sin icono ni color, **When** un miembro ve la tarjeta, **Then** el azulejo y la franja se ven exactamente como antes de esta corrección.
3. **Given** un proyecto con solo color (o solo icono) asignado, **When** se ve su tarjeta, **Then** se aplica la parte elegida y la otra conserva su valor por defecto.
4. **Given** un proyecto editado para quitarle el icono o el color, **When** se guarda y se ve la tarjeta, **Then** el azulejo vuelve a la apariencia por defecto.

---

### Edge Cases

- **Descripción legada en texto plano**: se muestra tal cual (sin pasar por el extractor).
- **Descripción con texto enriquecido sin contenido de texto**: se omite el párrafo (equivalente a descripción vacía).
- **Descripción nula**: la tarjeta no muestra el párrafo (comportamiento actual).
- **Solo color o solo icono**: se aplica lo elegido; el otro elemento usa su default.
- **Icono/color recién guardados vs. recargado**: la tarjeta refleja los valores tras el guardado y tras recargar la página.
- **Estado del proyecto (activo/archivado/completado)**: la franja superior y los colores de estado no cambian; el distintivo no los reemplaza.
- **Extracción de texto**: encabezados, listas y párrafos aportan su texto; las imágenes/enlaces aportan su texto visible; el resultado se normaliza con espacios simples.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: La tarjeta del proyecto en el directorio MUST mostrar la descripción como texto plano legible, convirtiendo el contenido de texto enriquecido a su texto visible cuando así esté almacenado, y pasando el texto plano sin transformación cuando sea legado.
- **FR-002**: La conversión a texto plano MUST preservar la legibilidad: bloques separados por espacios, sin llaves, comillas ni nombres de campo visibles, y resultado vacío cuando no hay texto visible.
- **FR-003**: El extracto de descripción MUST conservar su presentación actual (máximo dos líneas, tipografía y color actuales) y omitirse cuando el texto resultante está vacío.
- **FR-004**: El azulejo de la tarjeta MUST mostrar el icono del proyecto cuando está asignado (si no, el icono por defecto actual) y un fondo tenue del color de acento cuando está asignado (si no, los estilos actuales).
- **FR-005**: La franja superior y los indicadores de estado de la tarjeta MUST conservar su color de estado actual; el distintivo no los reemplaza.
- **FR-006**: Los cambios de distintivo o descripción guardados desde el formulario MUST reflejarse en la tarjeta inmediatamente tras guardar y tras recargar.
- **FR-007**: La ficha completa del proyecto (`/projects/{id}`) MUST seguir mostrando la descripción con su renderizado enriquecido actual; esta corrección no la altera.

### Key Entities _(include if feature involves data)_

- **Project (proyecto)**: sin cambios de datos; se consumen los campos existentes `description` (texto plano o contenido de texto enriquecido), `color` e `icon`.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: El 100% de los proyectos del directorio muestran una descripción legible (sin trazas de JSON visibles), incluidos los editados después de esta corrección.
- **SC-002**: Después de asignar o quitar un distintivo, la tarjeta refleja el cambio en el 100% de los casos, tanto tras el guardado inmediato como tras recargar.
- **SC-003**: Ninguna superficie que ya mostraba correctamente la descripción (ficha del proyecto) cambia su comportamiento: la suite de pruebas existente sigue pasando sin modificaciones.
- **SC-004**: Un usuario distingue a simple vista qué proyectos tienen distintivo asignado en el directorio, sin necesidad de abrirlos.

## Assumptions

- La "vista previa" del reporte es la tarjeta del proyecto en el directorio (`/projects`); la ficha completa ya renderiza la descripción correctamente con su componente enriquecido.
- La descripción se almacena en formato de texto enriquecido cuando se guarda desde el formulario actual; los proyectos antiguos pueden tener texto plano. La corrección es de presentación (extracción de texto visible), no de almacenamiento.
- Los campos `color` e `icon` ya viajan en el payload del directorio (`DirectoryProject`); esta corrección solo los pinta en la tarjeta.
- La franja superior de la tarjeta conserva su semántica de estado; el distintivo se aplica al azulejo del proyecto, no a la franja.
- No hay cambios de backend ni de base de datos en esta corrección.
