# Feature Specification: Accesos a "Nueva tarea" y "Acciones rápidas"

**Feature Branch**: `023-new-task-entry-points`

**Created**: 2026-10-10

**Status**: Draft

**Input**: User description: "Cuando estoy en el home (/) y doy click en nueva tarea, debería aparecer el modal de nueva tarea, actualmente sale el modal de acciones rápidas y cuando doy click en el botón de nueva tarea me lleva al backlog, este botón debe ser 'Acciones Rápidas' y mostrar el modal de acciones rápidas."

## Contexto (comportamiento actual)

| Punto de entrada | Etiqueta | Hoy hace |
| --- | --- | --- |
| Encabezado del panel de inicio | "Nueva Tarea" | Abre el modal de acciones rápidas |
| Inicio sin actividad (tarjeta) | "Nueva tarea · Presiona C" | Abre el modal de acciones rápidas |
| Barra lateral (todas las páginas) | "Nueva Tarea" | Navega al Backlog |
| Acciones rápidas → "Crear nueva tarea" | atajo C | Navega al Backlog |
| Tecla C (fuera de Mis Tareas) | — | Abre el modal de acciones rápidas |

Ninguna de estas acciones abre el formulario de nueva tarea, aunque su etiqueta lo promete.

## User Scenarios & Testing _(mandatory)_

### User Story 1 - "Nueva tarea" abre el formulario de nueva tarea (Priority: P1)

Desde el inicio, el usuario pulsa "Nueva Tarea" y aparece el formulario de
nueva tarea en el mismo lugar, sin cambiar de página. Lo mismo ocurre al elegir
"Crear nueva tarea" en las acciones rápidas, al pulsar la tarjeta "Nueva tarea"
del inicio sin actividad y al pulsar la tecla C.

**Why this priority**: Es el error reportado: la acción principal del inicio
lleva a otro lugar distinto del que anuncia.

**Independent Test**: En el inicio, pulsar "Nueva Tarea" → aparece el
formulario; crear la tarea → la página sigue en el inicio y la tarea aparece en
"Mis tareas para hoy" si corresponde. Repetir con la tecla C y con "Crear nueva
tarea" desde acciones rápidas en otra página (p. ej. Proyectos).

**Acceptance Scenarios**:

1. **Given** el usuario en el inicio, **When** pulsa "Nueva Tarea", **Then** se abre el formulario de nueva tarea y no se abre el modal de acciones rápidas.
2. **Given** el usuario en cualquier página con el modal de acciones rápidas abierto, **When** elige "Crear nueva tarea", **Then** el modal de acciones rápidas se cierra y se abre el formulario de nueva tarea en la misma página, sin navegar al Backlog.
3. **Given** el usuario en cualquier página sin un campo de texto enfocado, **When** pulsa la tecla C, **Then** se abre el formulario de nueva tarea.
4. **Given** el formulario abierto desde el inicio, **When** el usuario crea la tarea, **Then** se cierra, la página sigue siendo la misma y refleja la tarea nueva donde corresponda.
5. **Given** el formulario abierto, **When** el usuario cancela o cierra, **Then** vuelve a la página en la que estaba sin cambios.
6. **Given** el inicio sin actividad, **When** el usuario pulsa la tarjeta "Nueva tarea", **Then** se abre el formulario de nueva tarea.

---

### User Story 2 - El botón de la barra lateral se llama "Acciones rápidas" (Priority: P1)

El botón destacado de la barra lateral deja de llamarse "Nueva Tarea" y deja de
navegar al Backlog: se llama "Acciones rápidas" y abre el modal de acciones
rápidas, desde donde se puede crear una tarea, un proyecto o ir a otra sección.

**Why this priority**: Es la segunda mitad del reporte; hoy el botón dice una
cosa y hace otra.

**Independent Test**: En cualquier página, pulsar el botón de la barra lateral
→ se abre el modal de acciones rápidas; no hay navegación.

**Acceptance Scenarios**:

1. **Given** cualquier página, **When** el usuario mira la barra lateral, **Then** el botón principal dice "Acciones rápidas" y muestra el atajo ⌘K.
2. **Given** cualquier página, **When** pulsa "Acciones rápidas", **Then** se abre el modal de acciones rápidas y la página no cambia.
3. **Given** la barra lateral colapsada (solo iconos), **When** el usuario pasa el cursor o enfoca el botón, **Then** su nombre accesible y su indicación es "Acciones rápidas".

---

### Edge Cases

- **Mis Tareas (/tasks)**: ya tiene su propio "Nueva Tarea" y la tecla C abre su formulario; el comportamiento debe seguir siendo el mismo (sin abrir dos formularios a la vez).
- **Página de proyecto**: "Crear nueva tarea" desde acciones rápidas abre el formulario sin proyecto preseleccionado, salvo que la página ya lo haga con su propio botón (no cambia).
- **Formulario ya abierto**: pulsar C de nuevo o elegir "Crear nueva tarea" no abre un segundo formulario.
- **Campo de texto enfocado**: la tecla C no abre nada mientras el usuario escribe.
- **Modal de acciones rápidas abierto**: la tecla C no se interpreta como atajo mientras se escribe en su buscador.
- **Atajo ⌘K**: sigue abriendo y cerrando las acciones rápidas como hoy.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: El botón "Nueva Tarea" del encabezado del inicio MUST abrir el formulario de nueva tarea en la misma página.
- **FR-002**: La tarjeta "Nueva tarea" del inicio sin actividad MUST abrir el formulario de nueva tarea.
- **FR-003**: La acción "Crear nueva tarea" de acciones rápidas MUST cerrar el modal de acciones rápidas y abrir el formulario de nueva tarea en la página actual, sin navegar.
- **FR-004**: La tecla C (sin modificadores y fuera de campos de texto) MUST abrir el formulario de nueva tarea en cualquier página; en Mis Tareas conserva su comportamiento actual.
- **FR-005**: El botón principal de la barra lateral MUST llamarse "Acciones rápidas", mostrar el atajo ⌘K y abrir el modal de acciones rápidas sin navegar.
- **FR-006**: Crear una tarea desde el formulario MUST dejar al usuario en la misma página, con la información de esa página actualizada.
- **FR-007**: Solo puede haber un formulario de nueva tarea abierto a la vez.
- **FR-008**: El formulario abierto desde estos accesos MUST ser el mismo formulario de nueva tarea que el resto de la aplicación (mismos campos, valores por defecto y validaciones).

### Key Entities _(include if feature involves data)_

- **Tarea**: sin cambios; se crea con el formulario existente.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: El 100% de los controles etiquetados "Nueva tarea" (y su atajo C) abren el formulario de nueva tarea, sin navegar a otra página.
- **SC-002**: Crear una tarea desde el inicio requiere un clic para abrir el formulario, frente a los dos pasos y el cambio de página de hoy.
- **SC-003**: El botón principal de la barra lateral abre las acciones rápidas en el 100% de las páginas y nunca cambia de página.
- **SC-004**: Ningún control de la aplicación queda con una etiqueta que no corresponde a lo que hace en los accesos listados en el Contexto.

## Assumptions

- "Modal de nueva tarea" es el formulario de crear tarea existente (el de Mis Tareas y Backlog); no se diseña uno nuevo.
- El formulario abierto fuera de un proyecto crea la tarea sin proyecto preseleccionado, asignada al usuario actual, como en Mis Tareas (valores por defecto actuales).
- La tecla C pasa a significar "nueva tarea" en toda la aplicación, coherente con la etiqueta "Presiona C" del inicio; ⌘K queda como el atajo de acciones rápidas.
- El atajo N (nuevo proyecto) y su comportamiento no cambian.
