# Feature Specification: Rebranding público a Eunox

**Feature Branch**: `026-rebrand-eunox`

**Created**: 2026-10-16

**Status**: Draft

**Input**: User description: "Vamos a cambiar el nombre del proyecto el codename seguira siendo friday, pero en la UI y para el publico en general sera eunox"

## Contexto

El producto se ha desarrollado bajo el nombre "Friday". A partir de esta feature, **Eunox** es el nombre comercial: el único que ven las personas usuarias, invitadas o cualquier público externo. **Friday** se conserva como _codename_ interno: el nombre del repositorio, la documentación de ingeniería (constitución, specs, DESIGN.md), los identificadores técnicos internos y la jerga del equipo no cambian.

Hoy el nombre "Friday" aparece en superficies visibles como: la barra lateral de la aplicación, los encabezados de las páginas de autenticación ("Iniciar sesión en Friday", "Crear cuenta en Friday", "Friday Cloud OS"), el pie del lienzo de inicio ("Friday v2.4"), el modal de miembros del proyecto, la página de aceptación de invitaciones ("Ir a Friday") y el correo de invitación a proyectos (asunto y cuerpo). Además, el título de la pestaña del navegador y el remitente de los correos muestran actualmente el nombre genérico del framework ("Laravel") en lugar de un nombre de producto.

## User Scenarios & Testing _(mandatory)_

### User Story 1 - La aplicación se presenta como Eunox (Priority: P1)

Una persona que usa la aplicación (autenticada o no) ve el nombre "Eunox" en todos los lugares donde antes veía "Friday" o el nombre genérico del framework: barra lateral, encabezados de autenticación, títulos de pestaña del navegador, pie de versión y textos de ayuda.

**Why this priority**: Es el núcleo del cambio de marca; la interfaz es la superficie más vista y cualquier mención residual de "Friday" contradice la nueva identidad pública.

**Independent Test**: Recorrer login, registro, recuperación de contraseña, inicio, proyectos, tareas y ajustes, y confirmar que en ningún texto visible ni título de pestaña aparece "Friday" o "Laravel", y que aparece "Eunox" donde corresponde nombrar el producto.

**Acceptance Scenarios**:

1. **Given** una persona no autenticada, **When** abre la página de inicio de sesión, **Then** el encabezado dice "Iniciar sesión en Eunox" y el título de la pestaña incluye "Eunox".
2. **Given** una persona no autenticada, **When** abre la página de registro o la de restablecer contraseña, **Then** los títulos y subtítulos mencionan "Eunox" y no "Friday".
3. **Given** una persona autenticada, **When** navega por cualquier página de la aplicación, **Then** la barra lateral muestra "Eunox" como nombre del producto y el título de la pestaña sigue el formato "<Página> - Eunox".
4. **Given** una persona autenticada en el inicio, **When** observa el pie de versión, **Then** dice "Eunox v<versión>".

---

### User Story 2 - Las comunicaciones externas usan Eunox (Priority: P1)

Una persona invitada a un proyecto (que puede no tener cuenta aún) recibe un correo y abre una página de invitación; ambos la presentan a Eunox, y el remitente del correo se identifica como Eunox.

**Why this priority**: Es el primer contacto de personas nuevas con el producto; un nombre incorrecto o genérico ("Laravel") en el correo genera desconfianza y puede parecer spam.

**Independent Test**: Invitar a un correo nuevo a un proyecto y revisar el correo recibido (remitente, asunto, cuerpo, pie) y la página de aceptación de la invitación.

**Acceptance Scenarios**:

1. **Given** una persona colaboradora invita a alguien a un proyecto, **When** se envía el correo de invitación, **Then** el asunto dice "<Nombre> te invitó a <Proyecto> en Eunox", el cuerpo menciona "Eunox" y el nombre del remitente es "Eunox".
2. **Given** una persona abre un enlace de invitación, **When** ve la página de invitación, **Then** el botón de acción dice "Ir a Eunox" y ningún texto menciona "Friday".
3. **Given** el modal de miembros de un proyecto, **When** se muestra el texto de ayuda para correos sin cuenta, **Then** dice "Si el correo no tiene cuenta en Eunox…".
4. **Given** cualquier otro correo transaccional del sistema (p. ej. restablecer contraseña, verificación), **When** se envía, **Then** el remitente y cualquier mención del producto dicen "Eunox".

---

### User Story 3 - El codename Friday se preserva internamente (Priority: P2)

El equipo de desarrollo sigue refiriéndose al proyecto como "Friday": el repositorio, la documentación de ingeniería y los identificadores técnicos internos no se renombran, y el cambio de marca no altera el comportamiento ni borra preferencias guardadas de las personas usuarias.

**Why this priority**: Evita un renombrado masivo y riesgoso del código, y garantiza que el cambio sea puramente de presentación.

**Independent Test**: Tras el cambio, las preferencias de interfaz guardadas previamente en el navegador (p. ej. columna "Hecho" colapsada en Kanban) siguen aplicándose, y la documentación interna sigue usando "Friday".

**Acceptance Scenarios**:

1. **Given** una persona tenía colapsada la columna "Hecho" del Kanban antes del cambio, **When** vuelve a abrir el tablero después del cambio, **Then** la columna sigue colapsada.
2. **Given** la documentación de ingeniería (constitución, specs, documento de diseño), **When** se revisa tras el cambio, **Then** sigue usando "Friday" como nombre del proyecto, con una nota que aclara que el nombre público es "Eunox".

---

### Edge Cases

- **Datos de usuario que contienen "Friday"**: nombres de proyectos, tareas o comentarios creados por personas usuarias que incluyan la palabra "Friday" NO se modifican; el cambio solo afecta textos del producto.
- **Correos ya enviados**: las invitaciones enviadas antes del cambio conservan su texto original; sus enlaces deben seguir funcionando.
- **Nombre de configuración ausente**: si el nombre del producto no está configurado en un entorno, la interfaz y los correos deben mostrar "Eunox" por defecto, nunca "Laravel" ni "Friday".
- **Pestañas sin título de página**: una página sin título propio muestra solo "Eunox" en la pestaña.
- **Logotipo/ícono**: si el ícono actual (favicon, ícono de la barra lateral) contiene la letra o forma de "Friday", se señala como pendiente; los textos se cambian aunque el recurso gráfico nuevo aún no exista.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: Toda mención visible del nombre del producto en la interfaz (barra lateral, encabezados de autenticación, pie de versión, textos de ayuda, botones, mensajes) MUST decir "Eunox" en lugar de "Friday".
- **FR-002**: El título de la pestaña del navegador MUST usar "Eunox" como nombre del producto, con el formato "<Título de página> - Eunox" o solo "Eunox" cuando la página no tenga título.
- **FR-003**: Todos los correos que envía el sistema MUST identificar al remitente como "Eunox" y MUST usar "Eunox" en asunto, cuerpo y pie al nombrar el producto.
- **FR-004**: El nombre público del producto MUST definirse en un único lugar configurable, de modo que la interfaz y los correos lo tomen de ahí, con "Eunox" como valor por defecto.
- **FR-005**: La etiqueta descriptiva de las páginas de autenticación ("Friday Cloud OS") MUST reemplazarse por su equivalente con el nuevo nombre ("Eunox Cloud OS").
- **FR-006**: Tras el cambio, ningún texto visible para personas usuarias ni para destinatarios de correos MUST contener "Friday" ni el nombre genérico del framework ("Laravel") como nombre del producto.
- **FR-007**: El sistema MUST NOT modificar contenido creado por personas usuarias (proyectos, tareas, comentarios, nombres) como parte del cambio de marca.
- **FR-008**: Los identificadores técnicos internos que llevan el codename (claves de preferencias guardadas en el navegador, nombres internos de estilos y de comunicación entre componentes, nombre del repositorio) MUST conservarse sin cambios, para no perder preferencias de las personas usuarias ni introducir regresiones.
- **FR-009**: La documentación de ingeniería (constitución, README, documento de diseño) MUST conservar "Friday" como codename y MUST incluir una nota que indique que el nombre público del producto es "Eunox".
- **FR-010**: Los metadatos públicos de la aplicación que identifiquen el producto ante navegadores o al instalarla (p. ej. nombre de la aplicación para accesos directos, si existen) MUST usar "Eunox".

### Key Entities

- **Nombre público del producto**: valor único "Eunox" usado en interfaz, títulos de pestaña y correos; configurable por entorno con "Eunox" como valor por defecto.
- **Codename**: "Friday", usado exclusivamente en contextos internos de ingeniería (repositorio, documentación técnica, identificadores de código).

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: 0 apariciones de "Friday" o "Laravel" como nombre del producto en las pantallas y correos de la aplicación, verificado recorriendo el 100% de las páginas públicas y autenticadas y los correos transaccionales.
- **SC-002**: El 100% de los títulos de pestaña del navegador terminan en "Eunox".
- **SC-003**: El 100% de los correos enviados tras el cambio muestran "Eunox" como remitente.
- **SC-004**: 0 preferencias de interfaz guardadas por personas usuarias se pierden por el cambio (p. ej. estado de columnas colapsadas).
- **SC-005**: Cambiar el nombre público en el futuro requiere modificar un único valor de configuración, sin tocar pantallas ni plantillas de correo individuales.
- **SC-006**: La suite completa de tests pasa sin regresiones tras el cambio.

## Assumptions

- La forma escrita del nombre en textos corridos es "Eunox" (primera letra en mayúscula), aunque la descripción original lo escriba en minúsculas; si se desea un estilo de marca distinto (p. ej. "eunox" siempre en minúsculas), solo cambia el valor configurado.
- El cambio es solo de presentación: no hay cambio de dominio, URLs, nombre de base de datos ni rutas.
- No se entrega un logotipo o favicon nuevo en esta feature; si los recursos gráficos actuales contienen marca de "Friday", se registran como pendiente para cuando exista el arte de Eunox.
- La etiqueta de versión ("v2.4") se mantiene; solo cambia el nombre que la acompaña.
- La interfaz permanece en español; solo cambia el nombre del producto dentro de los textos existentes.
- Las specs históricas (001–025) no se reescriben; conservan "Friday" como registro del momento en que se escribieron.
