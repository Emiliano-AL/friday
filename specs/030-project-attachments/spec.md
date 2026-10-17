# Feature Specification: Adjuntos y referencias del proyecto

**Feature Branch**: `030-project-attachments`

**Created**: 2026-10-17

**Status**: Draft

**Input**: User description: "vamos a agregar la funcionalidad de adjuntar documentos, imagenes y links en cada proyecto, cada poryecto puede o no tenerlos y son documentos y referencias para el mismo proyecto. Estos documentos e imagenes viviran en S3 los links dentro de nuestra BD, al subir imagenes debemos optimizarlas para que no sean tan pesadas, tambien no se debe permitir subir imagenes y archivos tan pesados."

## Clarifications

### Session 2026-10-17

- Q: ¿Qué tamaño máximo debe tener cada archivo que se sube? → A: Imágenes 5 MB, documentos 15 MB.
- Q: ¿Quién puede eliminar o renombrar un adjunto del proyecto? → A: Quien subió el adjunto o el propietario del proyecto.
- Q: ¿Las imágenes optimizadas se guardan en su formato original o se convierten a WebP? → A: Se mantiene el formato original (JPG→JPG, PNG→PNG, WebP→WebP), comprimido y redimensionado.
- Q: ¿Dónde deben aparecer los adjuntos dentro de la página de detalle del proyecto? → A: En una pestaña nueva "Archivos" junto a General y Sprints.
- Q: ¿Qué límite de almacenamiento debe tener cada proyecto en total? → A: Máximo 30 adjuntos y máximo 200 MB de archivos por proyecto.

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Subir y consultar documentos e imágenes del proyecto (Priority: P1)

Un miembro del proyecto (propietario o colaborador), desde la página de detalle del proyecto, abre la pestaña "Archivos" (junto a General y Sprints) y sube uno o varios documentos (PDF, Word, Excel, PowerPoint, texto, CSV) o imágenes (JPG, PNG, WebP, GIF). Cada archivo aparece en la lista con su nombre, tipo, tamaño, quién lo subió y cuándo. Las imágenes se muestran con miniatura y pueden verse en grande; los documentos pueden abrirse o descargarse. Un proyecto sin adjuntos muestra un estado vacío que invita a agregar el primero.

**Why this priority**: Es el núcleo de la funcionalidad: tener en un solo lugar el material de referencia del proyecto (briefs, contratos, mockups, capturas). Sin esto, las demás historias no aportan valor.

**Independent Test**: Abrir un proyecto sin adjuntos, ver el estado vacío, subir un PDF y una imagen, verificar que ambos aparecen en la lista con sus datos, que la imagen se puede previsualizar y que el PDF se puede abrir/descargar.

**Acceptance Scenarios**:

1. **Given** un miembro en la página de detalle de un proyecto sin adjuntos, **When** abre la pestaña "Archivos", **Then** ve un estado vacío con la acción para subir archivos o agregar un link.
2. **Given** un miembro en un proyecto activo, **When** sube un documento permitido dentro del límite de tamaño, **Then** el documento aparece en la lista con nombre original, tipo, tamaño, autor y fecha, sin recargar manualmente la página.
3. **Given** un miembro, **When** sube una imagen permitida, **Then** la imagen aparece con miniatura y al pulsarla se abre una vista ampliada.
4. **Given** un miembro, **When** selecciona varios archivos a la vez, **Then** cada archivo se procesa de forma independiente y se informa el resultado de cada uno (éxito o motivo de rechazo).
5. **Given** un usuario que no es miembro del proyecto, **When** intenta ver, abrir o descargar un adjunto del proyecto (incluso con un enlace directo), **Then** el acceso es denegado.

---

### User Story 2 - Límites de tamaño y optimización de imágenes (Priority: P1)

Al subir archivos, el sistema rechaza los que superan el tamaño máximo permitido o tienen un tipo no admitido, con un mensaje claro que indique el límite. Las imágenes aceptadas se optimizan automáticamente (reducción de dimensiones y compresión) para que ocupen mucho menos espacio sin perder calidad visible, de modo que el usuario no tenga que preocuparse por ello.

**Why this priority**: Es un requisito explícito: controla el costo de almacenamiento y la velocidad de carga. Debe existir desde el primer día junto con la subida.

**Independent Test**: Intentar subir un archivo que excede el límite y uno de tipo no permitido y verificar el rechazo con mensaje claro; subir una foto grande (p. ej. 6000 px de ancho, 4.5 MB) y verificar que el archivo resultante es más pequeño y de dimensiones acotadas, y se ve correctamente.

**Acceptance Scenarios**:

1. **Given** un miembro, **When** intenta subir un documento mayor a 15 MB, **Then** el sistema lo rechaza indicando el límite de 15 MB y no se guarda nada.
2. **Given** un miembro, **When** intenta subir una imagen mayor a 5 MB, **Then** el sistema la rechaza indicando el límite de 5 MB y no se guarda nada.
3. **Given** un miembro, **When** intenta subir un tipo de archivo no permitido (p. ej. ejecutable, comprimido, video), **Then** el sistema lo rechaza indicando los tipos admitidos.
4. **Given** un miembro, **When** sube una imagen cuyo lado mayor supera 2560 px, **Then** la imagen almacenada se reduce para que su lado mayor sea como máximo 2560 px, conservando la proporción.
5. **Given** un miembro, **When** sube una imagen de cualquier tamaño permitido, **Then** la imagen almacenada se comprime y su tamaño final es menor o igual al original.
6. **Given** el límite de tamaño, **When** el usuario elige un archivo demasiado grande, **Then** el rechazo se muestra antes de esperar a que termine la transferencia completa, siempre que sea posible.

---

### User Story 3 - Agregar links de referencia (Priority: P2)

Un miembro agrega un link externo (p. ej. Figma, Google Drive, documentación, repositorio) con una URL y un título opcional. El link aparece en la misma pestaña "Archivos" junto a los archivos, se abre en una pestaña nueva y muestra su dominio para identificarlo rápidamente.

**Why this priority**: Muchas referencias de un proyecto viven fuera de la aplicación; agruparlas junto a los archivos completa la función de "referencias del proyecto". Es P2 porque es más simple y complementa a la P1.

**Independent Test**: Agregar un link con y sin título, verificar que aparece en la lista, que muestra el dominio y que se abre en una pestaña nueva; intentar agregar una URL inválida y verificar el rechazo.

**Acceptance Scenarios**:

1. **Given** un miembro en un proyecto activo, **When** agrega una URL válida con título, **Then** el link aparece en la lista con el título, el dominio, el autor y la fecha.
2. **Given** un miembro, **When** agrega una URL válida sin título, **Then** el link se muestra usando la URL (o su dominio) como título.
3. **Given** un miembro, **When** intenta agregar un texto que no es una URL válida http/https, **Then** el sistema lo rechaza con un mensaje claro.
4. **Given** un link en la lista, **When** el usuario lo pulsa, **Then** se abre en una pestaña nueva sin que el sitio destino pueda controlar la pestaña de origen.

---

### User Story 4 - Renombrar y eliminar adjuntos (Priority: P3)

Quien subió un adjunto o el propietario del proyecto puede renombrar su título visible o eliminarlo tras una confirmación. Al eliminar un archivo, también se elimina del almacenamiento.

**Why this priority**: Mantener la lista ordenada y corregir errores es necesario, pero el valor principal ya existe con las historias anteriores.

**Independent Test**: Como autor, renombrar y eliminar un adjunto propio; como colaborador, verificar que no puede eliminar el adjunto de otro; como propietario, eliminar el adjunto de un colaborador.

**Acceptance Scenarios**:

1. **Given** el autor de un adjunto, **When** cambia su título, **Then** el nuevo título se muestra en la lista.
2. **Given** el autor de un adjunto o el propietario del proyecto, **When** lo elimina y confirma, **Then** desaparece de la lista y el archivo deja de ser accesible.
3. **Given** un colaborador que no es autor del adjunto, **When** visualiza la lista, **Then** no tiene disponible la acción de eliminar ni renombrar ese adjunto, y el sistema rechaza el intento por cualquier otra vía.
4. **Given** un proyecto que se elimina, **When** se completa la eliminación, **Then** todos sus adjuntos y archivos almacenados se eliminan también.

---

### Edge Cases

- Proyecto archivado o completado: los adjuntos se pueden ver y descargar, pero no se pueden agregar, renombrar ni eliminar (coherente con que solo los proyectos activos admiten ediciones).
- Colaborador removido del proyecto: pierde acceso a los adjuntos; los adjuntos que subió permanecen en el proyecto, atribuidos a su nombre.
- Archivo con extensión permitida pero contenido que no corresponde (p. ej. un ejecutable renombrado a .pdf): se rechaza.
- Imagen corrupta o que no se puede procesar: se rechaza con un mensaje claro y no queda nada guardado a medias.
- GIF animado: se acepta y se conserva su animación; se aplica el límite de tamaño pero no se garantiza la reducción de peso.
- Imagen ya pequeña y optimizada: si la optimización no reduce el peso, se conserva la versión de menor tamaño.
- Imágenes con orientación en metadatos (fotos de celular): se muestran con la orientación correcta tras la optimización, y se eliminan los metadatos de ubicación.
- Imagen con metadatos EXIF cuya versión optimizada pesa más que el original: se guarda la versión optimizada de todos modos, porque eliminar la ubicación (FR-021) tiene prioridad sobre no aumentar el peso (FR-020).
- Nombres de archivo con caracteres especiales, espacios o muy largos: se conserva el nombre original para mostrarlo y descargarlo, sin afectar el almacenamiento.
- Dos archivos con el mismo nombre en el mismo proyecto: ambos se conservan como adjuntos distintos.
- Fallo de red o de almacenamiento a mitad de la subida: el usuario ve un error y no aparece un adjunto "fantasma" en la lista.
- Proyecto que alcanza 30 adjuntos o 200 MB: se rechazan nuevos adjuntos con un mensaje que indica el límite alcanzado. En una subida múltiple, se aceptan los archivos que caben y se rechazan los que exceden el límite, informando cada uno.
- Al eliminar un adjunto se libera su cupo de cantidad y de espacio.
- Link duplicado en el mismo proyecto: se permite (no se bloquea), ya que puede tener títulos distintos.

## Requirements _(mandatory)_

### Functional Requirements

**Adjuntos en general**

- **FR-001**: Cada proyecto MUST poder tener cero o más adjuntos de tres tipos: documento, imagen y link.
- **FR-002**: El sistema MUST mostrar los adjuntos del proyecto en una pestaña "Archivos" de la página de detalle del proyecto (junto a General y Sprints), cargando su contenido solo al abrir la pestaña, ordenados del más reciente al más antiguo, con la posibilidad de filtrar por tipo (todos, documentos, imágenes, links).
- **FR-003**: Cada adjunto MUST mostrar su título, tipo, autor y fecha de creación; los archivos además MUST mostrar su tamaño.
- **FR-004**: Solo los miembros del proyecto (propietario y colaboradores) MUST poder ver, abrir y descargar sus adjuntos; cualquier otro usuario MUST recibir acceso denegado, incluso con un enlace directo al archivo.
- **FR-005**: Los enlaces de acceso a archivos MUST ser temporales y no reutilizables indefinidamente por terceros.
- **FR-006**: Solo los miembros MUST poder agregar adjuntos, y solo mientras el proyecto esté activo.
- **FR-007**: Solo el autor de un adjunto o el propietario del proyecto MUST poder renombrarlo o eliminarlo, y solo mientras el proyecto esté activo. La eliminación MUST pedir confirmación.
- **FR-008**: Al eliminar un adjunto de tipo archivo o al eliminar el proyecto, el archivo almacenado MUST eliminarse también.
- **FR-009**: Un proyecto MUST admitir como máximo 30 adjuntos en total (documentos, imágenes y links) y como máximo 200 MB de archivos almacenados (suma del tamaño final de documentos e imágenes, ya optimizadas). Al superar cualquiera de los dos límites, el sistema MUST rechazar el nuevo adjunto con un mensaje que indique el límite alcanzado, y la pestaña "Archivos" MUST mostrar el uso actual (p. ej. "12 de 30 adjuntos · 85 de 200 MB").

**Documentos e imágenes**

- **FR-010**: El sistema MUST admitir documentos de tipo PDF, Word (doc, docx), Excel (xls, xlsx), PowerPoint (ppt, pptx), texto plano (txt), Markdown (md) y CSV.
- **FR-011**: El sistema MUST admitir imágenes de tipo JPG/JPEG, PNG, WebP y GIF.
- **FR-012**: El sistema MUST rechazar cualquier otro tipo de archivo, validando el contenido real del archivo y no solo su extensión.
- **FR-013**: El sistema MUST rechazar documentos de más de 15 MB e imágenes de más de 5 MB, con un mensaje que indique el límite aplicable.
- **FR-014**: La interfaz MUST validar tipo y tamaño antes de iniciar la transferencia cuando sea posible, y el servidor MUST volver a validarlos siempre.
- **FR-015**: Los miembros MUST poder subir varios archivos en una misma acción (hasta 10 por vez); cada archivo se acepta o rechaza de forma independiente y el usuario recibe el resultado de cada uno.
- **FR-016**: El sistema MUST mostrar el progreso de la subida mientras se transfiere un archivo.
- **FR-017**: El sistema MUST conservar el nombre original del archivo para mostrarlo y como nombre de descarga, independientemente del nombre interno de almacenamiento.
- **FR-018**: Los archivos MUST almacenarse en un almacenamiento de objetos externo y privado (no accesible públicamente sin autorización).

**Optimización de imágenes**

- **FR-019**: Toda imagen JPG, PNG o WebP aceptada MUST optimizarse antes de quedar disponible: su lado mayor se reduce a un máximo de 2560 px (conservando la proporción) y se comprime con una calidad visualmente equivalente, manteniendo su formato original (JPG→JPG, PNG→PNG con transparencia, WebP→WebP).
- **FR-020**: La versión almacenada de una imagen MUST pesar igual o menos que el original subido; si la optimización no reduce el peso, se conserva el original.
- **FR-021**: La optimización MUST respetar la orientación correcta de la imagen y MUST eliminar los metadatos sensibles (como la ubicación GPS).
- **FR-022**: El sistema MUST generar una miniatura de cada imagen para mostrarla en la lista sin cargar la imagen completa.
- **FR-023**: Los GIF MUST aceptarse conservando su animación; no se exige reducción de peso para ellos.
- **FR-024**: Si una imagen no puede procesarse, el sistema MUST rechazarla con un mensaje claro sin dejar archivos ni registros a medias.

**Links**

- **FR-025**: Los miembros MUST poder agregar un link con una URL (obligatoria, http o https, máximo 2048 caracteres) y un título opcional (máximo 255 caracteres).
- **FR-026**: Si no se proporciona título, el sistema MUST mostrar la URL o su dominio como título.
- **FR-027**: Los links MUST abrirse en una pestaña nueva, aislada de la pestaña de origen, y mostrar el dominio de destino.
- **FR-028**: Los links MUST guardarse en la base de datos de la aplicación (no en el almacenamiento de archivos).

### Key Entities _(include if feature involves data)_

- **Adjunto de proyecto**: Material de referencia asociado a un único proyecto. Atributos: tipo (documento, imagen, link), título visible, autor (usuario que lo agregó), fecha de creación y actualización. Para archivos: nombre original, tipo de contenido, tamaño en bytes, ubicación en el almacenamiento y, en imágenes, ubicación de la miniatura y dimensiones. Para links: URL.
- **Proyecto** (existente): Tiene cero o muchos adjuntos; su estado (activo, archivado, completado) determina si los adjuntos pueden modificarse. Al eliminarse, arrastra sus adjuntos.
- **Usuario** (existente): Autor de adjuntos; su pertenencia al proyecto determina el acceso.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Un miembro puede subir un documento o imagen y verlo en la lista en menos de 30 segundos para un archivo de 5 MB con una conexión de banda ancha típica.
- **SC-002**: Un miembro puede agregar un link de referencia en menos de 15 segundos.
- **SC-003**: El 100% de los archivos que exceden el límite o tienen un tipo no permitido son rechazados y ninguno queda almacenado.
- **SC-004**: Las fotos típicas de celular o cámara (más de 3 MB) quedan almacenadas con al menos un 50% menos de peso que el original, sin diferencia visible a tamaño normal de pantalla.
- **SC-005**: La lista de adjuntos de un proyecto con 30 adjuntos (incluyendo imágenes) se muestra completa en menos de 2 segundos.
- **SC-006**: El 0% de los adjuntos es accesible para usuarios que no son miembros del proyecto.
- **SC-007**: Al eliminar un adjunto o un proyecto, el 100% de los archivos asociados deja de existir en el almacenamiento.

## Assumptions

- Todos los miembros del proyecto (propietario y colaboradores) pueden agregar adjuntos; renombrar y eliminar queda limitado al autor y al propietario, siguiendo el modelo de permisos actual (sin roles adicionales).
- Las restricciones de edición siguen la regla existente: solo los proyectos activos admiten cambios; en archivados o completados los adjuntos son de solo lectura.
- Límites: imágenes 5 MB, documentos 15 MB, 30 adjuntos y 200 MB por proyecto (confirmados en Clarifications); 10 archivos por subida e imágenes optimizadas a 2560 px en su lado mayor (valores por defecto). Pueden ajustarse sin cambiar el alcance.
- Los adjuntos pertenecen al proyecto, no a tareas, sprints ni comentarios; adjuntar a tareas queda fuera de alcance.
- No se incluye vista previa embebida de documentos (Word, Excel, etc.); se abren o descargan. Los PDF pueden abrirse en el navegador.
- No se obtienen metadatos del link destino (título automático, favicon, vista previa); queda fuera de alcance.
- No hay versionado de archivos: reemplazar un archivo implica eliminarlo y subir uno nuevo.
- No se registra actividad (feed de actividad) por adjuntos en esta versión.
- No hay escaneo antivirus en esta versión; la mitigación es la restricción de tipos validando el contenido real y el acceso privado.
- El almacenamiento de objetos externo (S3) se configurará por entorno; en desarrollo y pruebas puede usarse un almacenamiento local equivalente.
- La dependencia para el almacenamiento externo (driver S3) fue aprobada por el usuario el 2026-10-17 (Principio II de la constitución); el procesamiento de imágenes usa capacidades nativas sin librerías nuevas.
