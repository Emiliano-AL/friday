# Feature Specification: Gestión de Sprints (CRUD y fechas)

**Feature Branch**: `003-sprint-crud`

**Created**: 2026-09-21

**Status**: Draft

**Input**: User description: "CRUD de sprints, gestion de sprints fechas de inicio, fechas de fin, los sprint pertenecen a un proyecto, cada proyecto puede tener n cantidad de sprints, siempre y cuando no este completado"

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Crear y listar sprints en un proyecto (Priority: P1)

El propietario de un proyecto activo crea un sprint con un nombre obligatorio,
una fecha de inicio y una fecha de fin, y lo ve en la lista de sprints del
proyecto ordenada por fecha de inicio. Los sprints organizan el trabajo del
proyecto en periodos cerrados; un proyecto puede tener tantos sprints como
necesite.

**Why this priority**: Sin altas y listado no existe gestión de sprints; es la
mitad mínima del CRUD y la base sobre la que se apoyan edición, eliminación y
futuros módulos (tareas y métricas por sprint).

**Independent Test**: Crear un sprint válido en un proyecto activo y comprobar
que aparece en la lista del proyecto con sus fechas: si solo esto existiera,
ya sería una planificación por sprints funcional.

**Acceptance Scenarios**:

1. **Given** el propietario de un proyecto activo, **When** crea un sprint con nombre, fecha de inicio y fecha de fin válidas, **Then** el sprint se guarda asociado a ese proyecto y aparece en su lista de sprints.
2. **Given** el propietario de un proyecto activo, **When** crea un sprint sin nombre o con fecha de fin anterior a la de inicio, **Then** el sistema rechaza la operación mostrando los errores correspondientes y no se guarda nada.
3. **Given** un proyecto con varios sprints, **When** cualquier miembro del proyecto consulta su lista de sprints, **Then** los ve ordenados por fecha de inicio ascendente.

---

### User Story 2 - Editar un sprint (Priority: P2)

El propietario corrige el nombre o las fechas de un sprint existente: adelanta
la fecha de inicio, extiende la fecha de fin o renombra el periodo. La misma
validación de fechas del alta se aplica a la edición.

**Why this priority**: Los planes cambian; sin edición, cualquier error de
fechas o de nombre obligaría a borrar y recrear el sprint, perdiendo
continuidad.

**Independent Test**: Editar nombre y fechas de un sprint existente en un
proyecto activo y comprobar que la lista refleja los nuevos valores y que una
fecha de fin inválida es rechazada.

**Acceptance Scenarios**:

1. **Given** un sprint existente en un proyecto activo, **When** el propietario cambia su nombre o sus fechas por valores válidos, **Then** el sprint actualizado aparece en la lista con los nuevos datos.
2. **Given** un sprint existente, **When** el propietario guarda una fecha de fin anterior a la fecha de inicio, **Then** el sistema rechaza la operación y el sprint conserva sus datos anteriores.

---

### User Story 3 - Eliminar un sprint (Priority: P3)

El propietario elimina un sprint que ya no aplica (por ejemplo, un periodo
planificado que se canceló). La eliminación pide confirmación y no afecta al
proyecto ni a sus demás sprints.

**Why this priority**: Complementa el CRUD; es menos frecuente que crear o
editar, pero sin ella los sprints erróneos quedarían para siempre en el
proyecto.

**Independent Test**: Eliminar un sprint tras confirmar y comprobar que
desaparece de la lista mientras el proyecto y sus otros sprints permanecen
intactos.

**Acceptance Scenarios**:

1. **Given** un proyecto activo con varios sprints, **When** el propietario elimina uno de ellos confirmando la acción, **Then** el sprint desaparece de la lista y los demás sprints y el proyecto no se ven afectados.
2. **Given** un proyecto activo, **When** el propietario inicia una eliminación pero no la confirma, **Then** no se elimina nada.

---

### User Story 4 - Solo lectura fuera del estado activo y colaboración (Priority: P4)

Cuando el proyecto está archivado o completado, sus sprints pasan a solo
lectura: cualquier miembro puede seguir viéndolos, pero el sistema bloquea la
creación, edición y eliminación con un mensaje claro, y la interfaz no ofrece
esas acciones. Los colaboradores del proyecto (no propietarios) ven los
sprints en cualquier estado, pero nunca pueden crearlos, editarlos ni
eliminarlos.

**Why this priority**: Protege la integridad histórica del proyecto terminado
y delimita permisos; es regla transversal que acompaña a las demás historias.

**Independent Test**: Con un proyecto archivado o completado, comprobar que la
lista de sprints sigue visible pero las operaciones de escritura son
rechazadas; con un colaborador, comprobar que solo puede ver.

**Acceptance Scenarios**:

1. **Given** un proyecto completado o archivado con sprints, **When** un miembro consulta la lista, **Then** ve los sprints sin opciones de crear, editar o eliminar.
2. **Given** un proyecto completado o archivado, **When** el propietario intenta crear, editar o eliminar un sprint (por ejemplo manipulando la petición), **Then** el sistema rechaza la operación con un mensaje que indica que el proyecto no admite cambios en su estado actual.
3. **Given** un proyecto activo, **When** un colaborador (miembro no propietario) accede a los sprints, **Then** puede ver la lista pero no encuentra acciones de creación, edición ni eliminación.

---

### Edge Cases

- ¿Qué pasa si el nombre del sprint está vacío o contiene solo espacios? Se rechaza la operación y se pide un nombre válido.
- ¿Qué pasa si la fecha de fin es anterior a la fecha de inicio? Se rechaza la operación con un error en la fecha de fin.
- ¿Qué pasa si el proyecto está archivado o completado? Las operaciones de escritura se bloquean (servidor) y la interfaz no las ofrece; la lectura sigue disponible.
- ¿Qué pasa si un usuario que no es miembro del proyecto intenta acceder a sus sprints? No encuentra nada (acceso denegado de forma indistinguible de un proyecto inexistente).
- ¿Qué pasa si las fechas del sprint están en el pasado? Son válidas: se permite la carga histórica de periodos ya cerrados.
- ¿Qué pasa si dos sprints del mismo proyecto se solapan en fechas? Se permite: el sistema no impone restricción de solapamiento.
- ¿Qué pasa al eliminar un sprint? El proyecto y el resto de sprints no se ven afectados.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: El sistema debe permitir al propietario de un proyecto activo crear sprints en él. Cada sprint tiene un nombre obligatorio de hasta 255 caracteres, una fecha de inicio obligatoria y una fecha de fin obligatoria.
- **FR-002**: El sistema debe validar que la fecha de fin de un sprint no sea anterior a su fecha de inicio, tanto en la creación como en la edición.
- **FR-003**: El sistema debe listar los sprints de un proyecto ordenados por fecha de inicio ascendente, visible para todo miembro del proyecto.
- **FR-004**: El sistema debe permitir al propietario editar el nombre y las fechas de un sprint existente mientras el proyecto esté activo.
- **FR-005**: El sistema debe permitir al propietario eliminar un sprint de un proyecto activo tras una confirmación explícita, sin afectar al proyecto ni a sus demás sprints.
- **FR-006**: El sistema debe impedir la creación, edición y eliminación de sprints cuando el proyecto no esté activo (archivado o completado), rechazando la operación en el servidor con un mensaje que indique que el proyecto no admite cambios en su estado actual; la interfaz no debe mostrar dichas acciones en ese estado.
- **FR-007**: Solo el propietario del proyecto puede crear, editar o eliminar sprints. Los colaboradores pueden verlos. Quien no es miembro del proyecto no puede acceder a sus sprints, ni siquiera de solo lectura.
- **FR-008**: Un proyecto activo puede contener un número ilimitado de sprints; el sistema no debe imponer un máximo.
- **FR-009**: El sistema debe aceptar fechas de sprint en el pasado (carga histórica) y sprints cuyas fechas se solapen entre sí.

### Key Entities _(include if feature involves data)_

- **Sprint**: periodo de trabajo planificado dentro de un proyecto. Atributos: nombre (obligatorio, corto), fecha de inicio (obligatoria), fecha de fin (obligatoria) y pertenencia a un proyecto (obligatoria). No tiene estado propio, métricas ni tareas asociadas en esta iteración.
- **Proyecto** (existente): contenedor de sus sprints. Su estado (activo, archivado o completado) determina si los sprints admiten gestión o solo lectura.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: El propietario puede crear un sprint completo (nombre, fecha de inicio y fecha de fin) en menos de 1 minuto.
- **SC-002**: La lista de sprints de un proyecto con hasta 50 sprints carga en menos de 2 segundos.
- **SC-003**: El 100% de los intentos de crear, editar o eliminar sprints en un proyecto archivado o completado son bloqueados y muestran un mensaje comprensible.
- **SC-004**: Al menos el 90% de los propietarios completa el flujo completo de crear, editar y eliminar un sprint sin necesitar ayuda.

## Assumptions

- La condición "siempre y cuando no esté completado" se aplica como regla general de solo lectura: los sprints solo se gestionan mientras el proyecto está activo; los proyectos archivados o completados quedan en solo lectura, coherente con la regla de edición del proyecto existente.
- La gestión de sprints (crear, editar, eliminar) es exclusiva del propietario, siguiendo la convención de permisos del proyecto; los colaboradores acceden de solo lectura.
- El sprint tiene nombre y fechas de inicio y fin; no se incluyen estado propio, descripción, objetivos ni tareas en esta iteración.
- No hay límite de sprints por proyecto ni restricción de solapamiento de fechas; las fechas históricas son válidas.
- La gestión de sprints ocurre dentro de la vista del proyecto (lista de sprints accesible desde el proyecto), sin una sección global separada.
