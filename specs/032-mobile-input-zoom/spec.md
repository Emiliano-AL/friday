# Feature Specification: Evitar el zoom automático al enfocar campos en móvil

**Feature Branch**: `032-mobile-input-zoom`

**Created**: 2026-10-17

**Status**: Draft

**Input**: User description: "Necesito que en mobil, no se haga zoom en los inputs, en algunos navegadores cuando doy focus en un input hace un zoom en automatico y eso rompe el responsive, para esto debemos incluir alguna css rule como esta input[type="text"], input[type="number"], input[type="email"], input[type="tel"], input[type="password"], textarea, select { font-size: 16px; } puedes revizar este link como documentacion https://wsform.com/knowledgebase/why-forms-zoom-on-some-mobile-devices-and-browsers-and-how-to-control-it/"

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Escribir en un formulario sin que la página se acerque (Priority: P1)

Un usuario abre Eunox desde el navegador de su teléfono (por ejemplo Safari en iPhone) y toca un campo de texto: el buscador, el título de una tarea, la captura rápida del backlog, el inicio de sesión. Hoy el navegador acerca la página automáticamente al enfocar el campo porque el texto del campo es más pequeño que el mínimo que el navegador considera legible. Tras el zoom, la página queda desplazada y más ancha que la pantalla, y el usuario tiene que alejar con los dedos para volver a ver el diseño completo. Al tocar cualquier campo, la página debe permanecer a la escala actual.

**Why this priority**: Es el problema reportado. Afecta a todos los formularios de la aplicación en móvil y rompe el diseño responsive en cada interacción de escritura.

**Independent Test**: En un iPhone (o simulador iOS) con Safari, abrir cualquier pantalla con formularios, tocar cada tipo de campo y comprobar que la escala de la página no cambia y no aparece desplazamiento horizontal.

**Acceptance Scenarios**:

1. **Given** un usuario en un teléfono con la página a escala normal, **When** toca un campo de texto, correo, contraseña, número, teléfono, búsqueda o URL, **Then** la página no se acerca y el diseño sigue ajustado al ancho de la pantalla.
2. **Given** un usuario en un teléfono, **When** toca un área de texto de varias líneas o el editor de texto enriquecido (descripción de tareas, objetivo del sprint, comentarios), **Then** la página no se acerca.
3. **Given** un usuario en un teléfono, **When** abre un selector desplegable, **Then** la página no se acerca al cerrarlo ni al abrirlo.
4. **Given** un usuario que terminó de escribir y quitó el foco del campo, **When** vuelve a ver la página, **Then** la escala es la misma que antes de tocar el campo.

---

### User Story 2 - Conservar el zoom manual y el diseño de escritorio (Priority: P2)

Un usuario con baja visión necesita acercar la página con el gesto de pellizco en su teléfono. Un usuario de escritorio usa la aplicación en una pantalla grande. La corrección no debe impedir el zoom manual ni cambiar el aspecto compacto de los formularios en pantallas grandes.

**Why this priority**: Bloquear el zoom manual sería una regresión de accesibilidad; agrandar los campos en escritorio cambiaría un diseño que ya funciona.

**Independent Test**: En el teléfono, hacer pellizco para acercar en cualquier pantalla y comprobar que funciona. En escritorio, comparar los formularios antes y después y comprobar que el tamaño del texto de los campos no cambió.

**Acceptance Scenarios**:

1. **Given** un usuario en un teléfono, **When** hace el gesto de pellizco para acercar, **Then** la página se acerca normalmente.
2. **Given** un usuario en una pantalla de escritorio, **When** ve cualquier formulario, **Then** el texto de los campos se ve igual que antes del cambio.

---

### Edge Cases

- Campos dentro de modales y de la paleta de comandos: deben comportarse igual que los campos de las páginas.
- Campos que no reciben texto (casillas de verificación, botones de opción, selector de archivos, selector de color): no provocan zoom y no deben cambiar de aspecto.
- Campos con un tamaño de texto propio mayor al mínimo (por ejemplo un título grande editable): conservan su tamaño mayor; la regla solo eleva lo que está por debajo del mínimo.
- Texto de marcador de posición (placeholder): sigue siendo legible y no se desborda del campo con el tamaño mayor.
- Pantallas de autenticación (inicio de sesión, registro, restablecer contraseña): incluidas en el alcance, son la primera interacción en móvil.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: En pantallas de tamaño móvil, todo campo que recibe texto (texto, correo, contraseña, número, teléfono, búsqueda, URL, fecha, hora, áreas de texto, selectores desplegables y el editor de texto enriquecido) MUST mostrar su texto a un tamaño de al menos 16 px, el mínimo a partir del cual los navegadores móviles no hacen zoom automático al enfocar.
- **FR-002**: Al enfocar cualquiera de esos campos en móvil, la página MUST NOT cambiar de escala ni generar desplazamiento horizontal.
- **FR-003**: El usuario MUST poder seguir acercando la página manualmente con el gesto de pellizco; la solución MUST NOT bloquear el zoom del usuario.
- **FR-004**: En pantallas de escritorio, el tamaño del texto de los campos MUST quedar como está hoy.
- **FR-005**: La corrección MUST aplicarse de forma global, de modo que los campos nuevos que se agreguen en el futuro queden cubiertos sin cambios adicionales.
- **FR-006**: Los campos que ya tienen un tamaño de texto mayor a 16 px MUST conservar su tamaño.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: El 100% de los campos de texto, áreas de texto, selectores y editores de la aplicación se pueden enfocar en Safari para iPhone sin que la página cambie de escala.
- **SC-002**: Tras enfocar y salir de un campo en móvil, no aparece desplazamiento horizontal en ninguna pantalla.
- **SC-003**: El zoom manual por pellizco sigue funcionando en el 100% de las pantallas en móvil.
- **SC-004**: En escritorio, ningún formulario muestra diferencias visuales en el tamaño del texto de sus campos.

## Assumptions

- "Móvil" se refiere al punto de corte de pantalla pequeña que ya usa la aplicación para su diseño responsive.
- El navegador principal afectado es Safari en iOS (y los navegadores de iOS que usan su mismo motor); otros navegadores móviles se benefician del mismo cambio sin trabajo adicional.
- Desactivar el zoom desde la configuración de la ventana del navegador queda descartado porque impide el zoom manual y perjudica la accesibilidad, como advierte la documentación de referencia.
- No se requieren pruebas automatizadas: es un cambio solo de estilos; la verificación es manual en un dispositivo o simulador móvil.
