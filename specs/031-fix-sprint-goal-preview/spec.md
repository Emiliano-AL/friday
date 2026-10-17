# Feature Specification: Corregir la vista previa del objetivo del sprint

**Feature Branch**: `031-fix-sprint-goal-preview`

**Created**: 2026-10-17

**Status**: Draft

**Input**: User description: "Tengo un bug en la descripcion de los sprint como puedes ver en el screenshot. {"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Actualmente hay archivos que deben imprimir"

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Ver el objetivo del sprint como texto legible (Priority: P1)

Un miembro del proyecto abre la pestaña "Sprints y tareas" y, en la lista de sprints, ve debajo del nombre de cada sprint un resumen de una línea de su objetivo (descripción). Hoy, cuando el objetivo se escribió con el editor de texto enriquecido, el resumen muestra el contenido en su formato interno (`{"type":"doc","content":[...`) en lugar del texto que el usuario escribió. El resumen debe mostrar solo el texto legible, por ejemplo "Actualmente hay archivos que deben imprimir…".

**Why this priority**: Es el bug reportado. El contenido interno es ilegible, se ve roto y expone un detalle técnico en la interfaz.

**Independent Test**: Crear o editar un sprint con un objetivo de varios párrafos y algo de formato (negritas, lista). Abrir "Sprints y tareas" y comprobar que el resumen muestra solo el texto, sin llaves, comillas ni nombres de nodos.

**Acceptance Scenarios**:

1. **Given** un sprint cuyo objetivo se escribió con formato enriquecido, **When** el usuario ve la lista de sprints, **Then** el resumen muestra el texto del objetivo en una sola línea, truncado si es largo, sin marcas de formato interno.
2. **Given** ese mismo sprint, **When** el usuario pasa el cursor sobre el resumen, **Then** la ayuda emergente muestra el texto legible completo, no el contenido interno.
3. **Given** un sprint antiguo cuyo objetivo se guardó como texto plano, **When** el usuario ve la lista, **Then** el texto se muestra igual que antes.
4. **Given** un sprint con un objetivo que tiene formato pero ningún texto visible (por ejemplo, solo párrafos vacíos), **When** el usuario ve la lista, **Then** no se muestra ninguna línea de resumen.

---

### User Story 2 - Mismo resumen legible en todas las vistas compactas (Priority: P1)

El objetivo del sprint también aparece resumido en la tarjeta del sprint activo de la pestaña "Sprints y tareas" y en el panel del sprint del inicio (Home). Esas vistas deben mostrar el mismo texto legible que la lista. El detalle del sprint, que ya muestra el objetivo con su formato completo, no cambia.

**Why this priority**: Es el mismo defecto en otras vistas. Corregir solo la lista dejaría el bug visible en otras pantallas.

**Independent Test**: Con un sprint activo cuyo objetivo tiene formato, revisar la tarjeta del sprint activo en el proyecto y el panel del sprint en el inicio. En ambos debe verse solo texto legible.

**Acceptance Scenarios**:

1. **Given** un sprint activo con objetivo enriquecido, **When** el usuario abre "Sprints y tareas", **Then** la tarjeta del sprint activo muestra el objetivo como texto legible.
2. **Given** un sprint activo con objetivo enriquecido, **When** el usuario abre el inicio, **Then** el panel del sprint muestra el objetivo como texto legible, en un máximo de dos líneas.
3. **Given** cualquier sprint, **When** el usuario abre su página de detalle, **Then** el objetivo se sigue mostrando con su formato (negritas, listas, etc.) como hoy.

---

### Edge Cases

- Objetivo vacío o nulo: no se muestra resumen (comportamiento actual).
- Objetivo con varios párrafos o elementos de lista: el texto se une en una sola línea separada por espacios, sin saltos ni viñetas.
- Objetivo guardado como texto plano antes del editor enriquecido: se muestra tal cual.
- Texto que parece formato interno pero no es válido (por ejemplo, un usuario que escribió llaves a mano en un objetivo antiguo): se muestra tal cual como texto plano, sin romper la página.
- Objetivo muy largo: el resumen se recorta visualmente con puntos suspensivos y la ayuda emergente contiene el texto completo.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: Las vistas compactas del objetivo del sprint (lista de sprints del proyecto, tarjeta del sprint activo del proyecto y panel del sprint en el inicio) MUST mostrar únicamente el texto legible del objetivo, nunca su formato interno.
- **FR-002**: La ayuda emergente del resumen en la lista de sprints MUST mostrar el texto legible completo.
- **FR-003**: Los objetivos con varios bloques (párrafos, listas, encabezados) MUST mostrarse en el resumen como un solo texto continuo separado por espacios.
- **FR-004**: Los objetivos antiguos guardados como texto plano MUST seguir mostrándose sin cambios.
- **FR-005**: Si un objetivo no tiene texto visible, el resumen MUST ocultarse, igual que cuando el objetivo está vacío.
- **FR-006**: La página de detalle del sprint MUST seguir mostrando el objetivo con su formato enriquecido.
- **FR-007**: La corrección MUST NOT cambiar cómo se guarda ni cómo se edita el objetivo del sprint.

### Key Entities _(include if feature involves data)_

- **Sprint** (existente): su objetivo puede estar guardado como texto enriquecido (formato del editor) o como texto plano (sprints antiguos). Solo cambia cómo se presenta en vistas resumidas.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: En el 100% de las vistas compactas del objetivo del sprint no aparece ningún fragmento del formato interno (llaves, `"type"`, `"content"`).
- **SC-002**: Un usuario puede leer el objetivo de cualquier sprint desde la lista sin abrir su detalle.
- **SC-003**: Los objetivos antiguos en texto plano se muestran idénticos antes y después de la corrección.

## Assumptions

- El formato interno corresponde al contenido del editor de texto enriquecido usado para las descripciones desde la feature 011. El proyecto ya convierte ese contenido a texto plano para otras vistas resumidas (por ejemplo, la descripción en la tarjeta del proyecto); se reutiliza ese mismo criterio.
- No se requiere migrar datos: el defecto es solo de presentación.
- Fuera de alcance: rediseñar la lista de sprints o permitir formato enriquecido dentro del resumen de una línea.
