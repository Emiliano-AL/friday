# Feature Specification: Identidad y fecha de término del proyecto

**Feature Branch**: `020-project-identity-fields`

**Created**: 2026-10-09

**Status**: Draft

**Input**: User description: "cuando se crea un proyecto tenemos algunas features marcadas como proximante, como identidad visual, clave y fecha de termino. vamos a trabajarlas."

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Identidad visual y clave del proyecto (Priority: P1)

Al crear o editar un proyecto, el propietario puede definir su identidad visual:
una clave corta que representa al proyecto (por ejemplo "FRID"), un color de
acento elegido de una paleta predefinida y un icono elegido de un conjunto
predefinido. Los campos dejan de estar deshabilitados: el formulario valida,
guarda y al reabrir el formulario de edición los valores aparecen precargados.
La clave se normaliza a mayúsculas y se valida para que no se repita entre los
proyectos del mismo propietario.

**Why this priority**: Es el corazón del pedido: hoy estos campos se anuncian
como "Próximamente" y no se pueden usar; sin ellos no hay identidad visual
ninguna que mostrar.

**Independent Test**: Crear un proyecto con clave, color e icono; verificar que
se guardan, que el formulario de edición los precarga, y que una clave repetida
del mismo propietario es rechazada con mensaje claro mientras la misma clave de
otro propietario es válida.

**Acceptance Scenarios**:

1. **Given** un propietario creando un proyecto, **When** completa nombre, clave "frid" (en minúsculas), elige un color de la paleta y un icono del conjunto, **Then** el proyecto se guarda con clave "FRID" (normalizada a mayúsculas), el color y el icono elegidos.
2. **Given** un proyecto con identidad definida, **When** el propietario abre el formulario de edición, **Then** clave, color e icono actuales aparecen seleccionados y puede cambiarlos guardando los cambios.
3. **Given** un propietario con un proyecto con clave "FRID", **When** crea o edita otro proyecto con la misma clave, **Then** el sistema rechaza el guardado mostrando un error junto al campo clave y no persiste nada.
4. **Given** dos propietarios distintos, **When** cada uno usa la misma clave en sus proyectos, **Then** ambos guardan sin conflicto.
5. **Given** una clave con caracteres no alfanuméricos o de longitud fuera del rango permitido, **When** el usuario guarda, **Then** el sistema muestra la validación correspondiente sin guardar nada.

---

### User Story 2 - Fecha de término del proyecto (Priority: P2)

Al crear o editar un proyecto, el propietario puede fijar una fecha de término
(objetivo) con un selector de fecha. El valor se guarda, se puede cambiar y
también dejar vacío de nuevo; si la fecha ya pasó, la ficha del proyecto lo
refleja como vencida en lugar de ocultarlo.

**Why this priority**: El campo existe en el formulario (deshabilitado) y tiene
un espacio reservado en la ficha ("Fecha Objetivo — Próximamente"); es la
segunda parte natural del pedido.

**Independent Test**: Crear un proyecto con fecha de término futura y verificar
que persiste y se muestra; editarla a otra fecha y luego quitarla; verificar el
estado "vencida" con una fecha pasada.

**Acceptance Scenarios**:

1. **Given** un propietario creando un proyecto, **When** elige una fecha de término futura y guarda, **Then** la fecha persiste y aparece precargada al editar.
2. **Given** un proyecto con fecha de término, **When** el propietario la cambia o la deja vacía en la edición, **Then** el nuevo valor (o la ausencia) persiste.
3. **Given** un proyecto cuya fecha de término ya pasó, **When** cualquier miembro ve la ficha, **Then** la fecha se muestra con un indicador de vencida.
4. **Given** un valor de fecha inválido (formato imposible), **When** el usuario guarda, **Then** el sistema rechaza con mensaje claro junto al campo.

---

### User Story 3 - La identidad se ve en la aplicación (Priority: P3)

La identidad definida se refleja donde hoy hay placeholders: en la ficha del
proyecto (el resumen muestra la fecha de término real en el espacio "Fecha
Objetivo", junto a la clave) y en la navegación (la lista de proyectos del
espacio de trabajo muestra el color e icono elegidos para cada proyecto). Los
proyectos existentes sin identidad definida se ven con los valores por defecto,
sin que el usuario tenga que hacer nada.

**Why this priority**: Sin visualización, la identidad guardada no aporta
valor; pero la app ya funciona mostrando valores por defecto, por lo que es la
capa final.

**Independent Test**: Crear un proyecto con identidad completa y verificar que
la ficha y la navegación muestran clave, color, icono y fecha; verificar que un
proyecto antiguo sin identidad muestra los valores por defecto coherentes.

**Acceptance Scenarios**:

1. **Given** un proyecto con clave, color, icono y fecha de término, **When** un miembro abre la ficha, **Then** ve la fecha real en el resumen (no "Próximamente"), la clave junto al nombre y el color/icono como identidad del proyecto.
2. **Given** la lista de proyectos en la navegación, **When** el usuario la observa, **Then** cada proyecto muestra su color e icono elegidos.
3. **Given** un proyecto creado antes de esta funcionalidad, **When** se ve en ficha o navegación, **Then** aparece con color, icono y clave por defecto y sin fecha, sin errores ni estados rotos.

---

### Edge Cases

- **Proyectos existentes sin identidad**: se muestran con valores por defecto (color e icono por omisión, sin clave ni fecha); ninguna pantalla se rompe.
- **Clave repetida del mismo propietario**: rechazo con error junto al campo; la comparación ignora mayúsculas/minúsculas.
- **Clave con caracteres inválidos** (espacios, guiones, símbolos, acentos): validación inline; el sistema normaliza lo normalizable (minúsculas → mayúsculas) antes de validar.
- **Fecha de término en el pasado**: se permite guardar (el usuario puede registrar proyectos ya cerrados o corregir fechas) pero la ficha la marca como vencida.
- **Quitar la fecha de término**: dejar el campo vacío en edición elimina la fecha; la ficha vuelve a mostrar el estado sin fecha.
- **Formato de fecha inválido** o imposible (por ejemplo día 31 de febrero): rechazo con mensaje junto al campo.
- **Permisos**: solo el propietario puede cambiar identidad, clave y fecha; los miembros solo las ven.
- **Clave vacía**: permitida (identidad opcional); el proyecto simplemente no muestra clave.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: El formulario de crear proyecto MUST habilitar los campos de identidad: clave, color de acento (paleta predefinida) e icono (conjunto predefinido), eliminando su estado deshabilitado y el aviso "Próximamente".
- **FR-002**: El formulario de editar proyecto MUST precargar clave, color, icono y fecha de término actuales y permitir modificarlos.
- **FR-003**: La clave MUST normalizarse a mayúsculas, tener una longitud mínima y máxima cortas (2–5 caracteres alfanuméricos ASCII) y ser única por propietario (insensible a mayúsculas); el error de duplicado debe mostrarse junto al campo.
- **FR-004**: El color de acento MUST elegirse de una paleta predefinida cerrada (los colores visibles hoy en el formulario más sus alternativas ocultas); si no se elige, se usa el color por defecto de la marca.
- **FR-005**: El icono MUST elegirse de un conjunto cerrado de iconos del sistema de diseño; si no se elige, se usa el icono por defecto de proyecto.
- **FR-006**: La fecha de término MUST ser opcional, seleccionable con un selector de fecha, validada como fecha real y persistida; dejarla vacía en edición MUST eliminarla.
- **FR-007**: La ficha del proyecto MUST mostrar la fecha de término real en el espacio reservado "Fecha Objetivo", con estado "vencida" cuando la fecha ya pasó y el estado sin fecha cuando no hay valor.
- **FR-008**: La ficha del proyecto MUST mostrar la clave junto al nombre cuando existe.
- **FR-009**: La lista de proyectos de la navegación MUST mostrar el color e icono de cada proyecto; los proyectos sin identidad definida MUST verse con los valores por defecto.
- **FR-010**: Solo el propietario del proyecto MUST poder modificar clave, color, icono y fecha de término; miembros y visitantes solo las visualizan.
- **FR-011**: Los proyectos existentes sin identidad MUST seguir funcionando y visualizándose con valores por defecto, sin migración de datos obligatoria por parte del usuario.

### Key Entities _(include if feature involves data)_

- **Project (proyecto)**: gana cuatro atributos opcionales de identidad — clave (texto corto único por propietario), color de acento (valor de paleta predefinida), icono (identificador de conjunto predefinido) y fecha de término (fecha). Sin cambios en sus relaciones (propietario, miembros, tareas, sprints).

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Un propietario puede crear un proyecto completo (nombre, clave, color, icono, fecha) en una sola pasada por el formulario, sin campos deshabilitados ni avisos de "Próximamente".
- **SC-002**: El 100% de los valores de identidad y fecha guardados se muestran correctamente en ficha y navegación tras recargar la página.
- **SC-003**: El 100% de los intentos de clave duplicada del mismo propietario son rechazados con un mensaje junto al campo, y ningún dato se persiste en esos casos.
- **SC-004**: Todos los proyectos existentes previos a la funcionalidad se siguen viendo y usando igual (con valores por defecto), sin acción requerida del usuario.
- **SC-005**: Los miembros no propietarios ven la identidad completa pero no pueden modificarla: el 100% de sus intentos de edición son rechazados.

## Assumptions

- "Identidad visual" se interpreta como el bloque que hoy anuncia el formulario: clave, color de acento e icono (la leyenda actual dice "Clave, color de acento, icono y fecha objetivo estarán disponibles próximamente").
- La clave es identificativa/visual; no se utiliza como prefijo de códigos de tarea ni como ruta en esta funcionalidad (la numeración de tareas sigue siendo la actual).
- La paleta de colores es la cerrada que ya muestra el formulario (4 visibles + 3 ocultas = 8 opciones); el color por defecto es el primero (el de marca actual).
- El conjunto de iconos es una selección cerrada de iconos ya usados en la aplicación; el icono por defecto es el de carpeta/proyecto usado hoy en la navegación.
- "Fecha de término" equivale al campo "Fecha objetivo" que ya tiene espacio reservado en la ficha del proyecto.
- La clave es única por propietario (no global): dos usuarios pueden usar la misma clave en sus propios proyectos.
- Los campos son opcionales en conjunto: un proyecto puede tener solo color, o solo fecha, o ninguno.
