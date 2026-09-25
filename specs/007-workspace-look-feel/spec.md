# Feature Specification: Look & Feel del workspace autenticado

**Feature Branch**: `007-workspace-look-feel`

**Created**: 2026-09-25

**Status**: Draft

**Input**: User description: "Necesito que me ayudes a generar el look and feel de la aplicacion que el usuario que ya hizo login, ya tienes el @DESIGN.md y el layout de la aplicacion es este: /Users/elanda/Downloads/stitch_friday_workspace_app/code.html, por el momento ese es el light mode unicamente y ese sera el que vamos a trabajar."

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Navegación dentro del nuevo shell del workspace (Priority: P1)

Un usuario que ya inició sesión entra a la aplicación y encuentra un workspace con estructura de tres regiones persistentes: una barra lateral izquierda con marca, selector de contexto, acción principal, navegación principal y tarjeta de usuario; un encabezado superior con buscador de comandos, campana de notificaciones y menú de perfil; y un área de contenido amplia y limpia. Desde la barra lateral puede moverse entre las secciones de la aplicación, identificar en todo momento dónde está (estado activo visible) y colapsar/expandir la barra para ganar espacio de trabajo. Todo ello con el sistema visual de `DESIGN.md` en modo claro.

**Why this priority**: El shell es la estructura visible de todas las páginas autenticadas; sin él no hay forma de presentar ninguna otra pantalla con el nuevo look & feel. Es el cimiento sobre el que se montan dashboard, proyectos, sprints y tareas.

**Independent Test**: Puede probarse de forma independiente iniciando sesión y recorriendo el workspace: la barra lateral, el encabezado y el área de contenido renderizan como en el diseño de referencia; la navegación a las secciones existentes (Panel e Proyectos) funciona; el estado activo refleja la sección actual; la barra se colapsa y expande sin perder el contexto de página.

**Acceptance Scenarios**:

1. **Given** un usuario autenticado, **When** accede a la aplicación tras el login, **Then** ve la barra lateral con logotipo "Friday", botón para colapsar, selector de contexto, botón "Nueva Tarea", navegación (Panel, Proyectos, Mis Tareas, Sprints, Backlog, Ajustes) y al fondo su tarjeta de usuario con nombre y correo.
2. **Given** un usuario autenticado, **When** hace clic en cualquier elemento de navegación con destino disponible, **Then** navega a esa sección y el elemento queda marcado como activo con el estilo del design system.
3. **Given** un usuario autenticado, **When** hace clic en el botón de colapsar (barra lateral o encabezado), **Then** la barra se oculta/muestra de forma animada y el área de contenido se ajusta al ancho disponible.
4. **Given** un usuario autenticado que recarga o entra directo por un enlace a una sección, **When** la página carga, **Then** el elemento de navegación correspondiente aparece activo.
5. **Given** un usuario autenticado en una pantalla pequeña (tablet/móvil), **When** usa la aplicación, **Then** la barra lateral se comporta como panel deslizante y el contenido usa un solo columnado sin scroll horizontal.

---

### User Story 2 - Gestión de cuenta desde el shell (Priority: P2)

Un usuario autenticado puede ver su identidad en todo momento (tarjeta de usuario al fondo de la barra lateral y avatar en el encabezado) y, desde el menú de perfil, acceder a las opciones de su cuenta y cerrar sesión. El menú muestra su nombre, correo y opciones como "Detalles del perfil", "Ajustes de cuenta" y "Cerrar sesión" con estilo consistente con el design system.

**Why this priority**: La gestión de sesión es funcionalidad existente crítica (logout) que debe sobrevivir intacta al cambio visual; además, la identidad visible del usuario es parte central del diseño de referencia.

**Independent Test**: Puede probarse de forma independiente: el usuario abre el menú de perfil desde el avatar, ve sus datos, y "Cerrar sesión" cierra la sesión y lo regresa al login. Las opciones del menú sin destino aún disponible se muestran deshabilitadas con indicador "Próximamente" (FR-011).

**Acceptance Scenarios**:

1. **Given** un usuario autenticado, **When** hace clic en su avatar del encabezado, **Then** se abre el menú de perfil mostrando nombre, correo y las opciones "Detalles del perfil", "Ajustes de cuenta" y "Cerrar sesión".
2. **Given** un usuario con el menú de perfil abierto, **When** hace clic en "Cerrar sesión", **Then** su sesión se cierra y es redirigido a la página de login.
3. **Given** un usuario sin foto de perfil, **When** ve su avatar en la barra lateral o en el menú, **Then** se muestran sus iniciales como sustituto visual.
4. **Given** un usuario con el menú abierto, **When** hace clic fuera del menú o presiona `ESC`, **Then** el menú se cierra.

---

### User Story 3 - Acciones rápidas y superficie de comandos (Priority: P3)

Un usuario autenticado dispone de una superficie de comandos tipo "paleta" (atajo `⌘K` / `Ctrl+K`, botón "Comandos rápidos" y campo de búsqueda del encabezado) para buscar y disparar acciones sin salir del teclado: la paleta lista y filtra las acciones disponibles y navega a las secciones existentes. El botón "Nueva Tarea" de la barra lateral (y su atajo `C`) abre la paleta de comandos, y la campana de notificaciones abre su popover con un estado vacío honesto ("Sin notificaciones"). Todo funciona de forma exclusivamente de cliente, sin datos simulados y sin cambios de backend.

**Why this priority**: Las acciones rápidas aceleran el trabajo de usuarios frecuentes y son protagonistas del diseño de referencia, pero el valor del workspace ya existe sin ellas; por eso es prioridad menor que la estructura base y la gestión de cuenta.

**Independent Test**: Puede probarse de forma independiente: `⌘K` (o `Ctrl+K`) y el botón visible abren la paleta; teclear filtra la lista; seleccionar una acción navega a su sección; `ESC` o clic fuera cierra; el botón/atajo `C` abre la paleta; la campana muestra el estado vacío.

**Acceptance Scenarios**:

1. **Given** un usuario autenticado en cualquier página del workspace, **When** presiona `⌘K` (o `Ctrl+K`) o hace clic en "Comandos rápidos", **Then** se abre la paleta de comandos centrada con campo de búsqueda y lista de acciones sugeridas.
2. **Given** un usuario con la paleta abierta, **When** presiona `ESC` o hace clic fuera, **Then** la paleta se cierra devolviendo el foco a la página.
3. **Given** un usuario con la paleta abierta, **When** teclea para filtrar y selecciona una acción de navegación, **Then** la aplicación navega a la sección correspondiente.
4. **Given** un usuario autenticado, **When** hace clic en "Nueva Tarea" o presiona `C`, **Then** se abre la paleta de comandos.
5. **Given** un usuario autenticado, **When** hace clic en la campana de notificaciones, **Then** se abre su popover mostrando el estado vacío "Sin notificaciones", sin datos simulados ni indicador de no leídos.

---

### Edge Cases

- Usuario sin proyectos: el selector de contexto muestra un estado vacío con llamada a crear el primer proyecto, sin romper el layout.
- Nombres largos de usuario, correo o proyecto: se truncan con elipsis sin desbordar su contenedor.
- Usuario sin foto de perfil: avatar sustituido por iniciales con el color de acento del design system.
- Acceso directo por URL a una sección profunda: el elemento de navegación correcto aparece activo aunque no se haya navegado desde la barra lateral.
- Teclado: `ESC` cierra menús y paletas; el foco queda contenido de forma razonable dentro de la paleta mientras está abierta y vuelve al disparador al cerrarse.
- Redimensionar la ventana entre escritorio y tablet: la barra lateral cambia de fija a deslizante sin perder el estado de página.
- Zoom alto o viewports estrechos (desde 320px): ningún elemento del shell genera scroll horizontal ni superposición de regiones.
- Cierre de sesión desde menú con la barra lateral colapsada o la paleta abierta: el flujo completa igual y redirige al login.
- Clic en elementos de navegación u opciones de menú aún sin destino: se muestran deshabilitados con indicador "Próximamente", no navegan ni causan errores (FR-011).

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: Todas las páginas autenticadas deben compartir una estructura común de tres regiones: barra lateral fija (~256px), encabezado fijo superior y área de contenido fluida; la estructura reemplaza al layout autenticado actual sin cambiar la funcionalidad de las páginas existentes.
- **FR-002**: La barra lateral debe incluir, en orden: logotipo "Friday" con botón para colapsar; selector de contexto mostrando el proyecto/espacio actual con opción de cambiarlo; botón de acción principal "Nueva Tarea" con indicador de atajo `C`; navegación principal con iconos (Panel, Proyectos, Mis Tareas, Sprints, Backlog); sección separada con "Ajustes"; y al fondo, tarjeta de usuario con avatar, nombre, correo y botón de opciones de cuenta.
- **FR-003**: El encabezado debe incluir: botón para mostrar/ocultar la barra lateral; campo de búsqueda/comandos con placeholder "Buscar o teclear comando..." e indicador `⌘K`; botón de notificaciones que abre su popover (estado vacío cuando no hay notificaciones reales); botón de ayuda; y avatar de usuario que abre el menú de perfil.
- **FR-004**: Cada elemento de navegación debe reflejar estado activo (fondo elevado, texto/icono de alto énfasis y peso semibold) según la sección actual, con estado hover discreto para el resto.
- **FR-005**: Toda la superficie visual debe aplicar los tokens de `DESIGN.md` en modo claro: superficie base `#F9F9FF`, capas tonales para barra lateral y estados, acento eléctrico indigo (`#5B5BD6`), tarjetas blancas con sombra suave (`0 1px 2px rgba(16,24,40,0.04)`), tipografía Inter con la escala headline/body/label, radios (8px base, 12px contenedores, píldoras para estados) y anillos de foco de acento. Modo oscuro fuera de alcance.
- **FR-006**: El shell debe ser responsive: escritorio (≥1024px) multi-panel fijo; tablet (768–1023px) barra lateral como panel deslizante sobre el contenido; móvil (<768px) un solo columnado con la navegación accesible desde el encabezado.
- **FR-007**: El shell debe ser accesible: regiones con nombre accesible, botones con etiquetas descriptivas, foco visible con anillo de acento, contraste legible (WCAG AA), cierre con `ESC` en menús y paleta, y navegación completa por teclado.
- **FR-008**: Los menús flotantes (perfil, notificaciones) deben abrir con animación sutil, posicionarse anclados a su disparador, cerrarse al hacer clic fuera o con `ESC`, y usar tarjeta blanca con elevación nivel 2 del design system.
- **FR-009**: La paleta de comandos debe abrirse con `⌘K`/`Ctrl+K` y desde el botón "Comandos rápidos", presentarse como modal centrado con velo translúcido (elevación nivel 3), incluir campo de búsqueda y lista de acciones con sus atajos, y poder cerrarse con `ESC` o clic en el velo.
- **FR-010**: La página de inicio (Panel) debe presentar el lienzo de bienvenida del diseño de referencia: barra superior de contexto con migas ("Mi Espacio / Lienzo en Blanco"), chip de estado "Borrador", botones "Comandos rápidos" y "Añadir bloque", lienzo central con retícula sutil, mensaje "Espacio listo para crear", acciones sugeridas (Nueva tarea, Planear sprint, Integrar git) e indicador inferior de estado ("Sincronizado en la nube • Friday v2.4").
- **FR-011**: Los elementos de navegación y opciones de menú cuyas secciones aún no existen (Mis Tareas, Sprints, Backlog, Ajustes, "Detalles del perfil", "Ajustes de cuenta") deben mostrarse con la apariencia del diseño de referencia pero en estado deshabilitado, con indicador "Próximamente", sin navegar ni generar errores.
- **FR-012**: La paleta de comandos, el botón "Nueva Tarea", los atajos de teclado y la campana de notificaciones deben funcionar de forma exclusivamente de cliente, sin cambios de backend: la paleta lista y filtra acciones que navegan a las secciones existentes (Panel, Proyectos, etc.); "Nueva Tarea" (botón y atajo `C`) abre la paleta de comandos; la campana abre su popover con un estado vacío ("Sin notificaciones"), sin indicador de no leídos cuando no haya notificaciones reales; ningún dato simulado se muestra.
- **FR-013**: La funcionalidad existente debe conservarse sin cambios de comportamiento: cierre de sesión desde el menú, acceso protegido por autenticación, y todas las páginas de dominio actuales (proyectos y sus vistas) deben renderizar dentro del nuevo shell.

### Key Entities

- **Usuario (sesión)**: nombre, correo, foto de perfil (opcional) y rol; se muestra en tarjeta de usuario, avatar y menú de perfil.
- **Proyecto (contexto actual)**: nombre e identificador visual (inicial); el selector de contexto lo representa y permite cambiar entre los proyectos del usuario.
- **Elemento de navegación**: etiqueta, icono, destino (sección), distintivo opcional (p. ej. contador en "Mis Tareas") y estado (habilitado/deshabilitado "Próximamente"); su estado activo depende de la sección actual.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Las regiones del shell (barra lateral, encabezado, área de contenido, menús y paleta) y la página de inicio renderizan visualmente equivalentes al diseño de referencia en modo claro, verificadas por comparación con la pantalla de referencia en escritorio.
- **SC-002**: El 100% de los tests de feature existentes siguen pasando sin modificación de comportamiento (autenticación, logout, CRUD de proyectos/sprints/tareas).
- **SC-003**: Un usuario puede llegar a cualquier sección con destino disponible desde cualquier otra página en un máximo de 2 clics (o 1 con la paleta de comandos), y el estado activo de navegación siempre refleja la sección visible.
- **SC-004**: Ninguna página autenticada genera scroll horizontal ni superposición de regiones en viewports desde 320px de ancho hacia arriba, en escritorio, tablet y móvil.
- **SC-005**: Auditoría de accesibilidad sin violaciones críticas (contraste AA, labels, foco visible, cierre con `ESC`) sobre el shell completo.
- **SC-006**: Un usuario exclusivamente de teclado puede abrir y cerrar la paleta de comandos y los menús, filtrar acciones y navegar entre secciones.

## Assumptions

- El alcance es únicamente el modo claro definido en `DESIGN.md`; el modo oscuro queda explícitamente fuera de alcance para esta iteración.
- El diseño de referencia (`/Users/elanda/Downloads/stitch_friday_workspace_app/code.html` y su captura `screen.png`) y los tokens de `DESIGN.md` (ya presentes en el repositorio e idénticos a los del paquete de diseño) son la fuente visual autorizada; ajustes menores de copy son permitidos.
- El copy de la interfaz es en español, tomado del diseño de referencia (p. ej. "Nueva Tarea", "Comandos rápidos", "Cerrar sesión").
- Los iconos del diseño de referencia (Material Symbols) se implementan con iconografía SVG equivalente, siguiendo el patrón ya usado en el rediseño de autenticación.
- La fuente Inter ya está disponible en el proyecto.
- No se requieren cambios de backend para el shell: navegación, sesión y datos de usuario/proyectos existentes se reutilizan; los contadores de distintivos solo se muestran cuando exista el dato real.
- El selector de contexto lista los proyectos del usuario autenticado y navega al proyecto seleccionado; si el usuario no tiene proyectos, muestra estado vacío.
- Decisiones de alcance resueltas: (Q1) la paleta de comandos, "Nueva Tarea" y la campana funcionan de solo cliente, sin backend ni datos simulados; (Q2) las secciones inexistentes se muestran deshabilitadas con indicador "Próximamente"; (Q3) el alcance incluye el shell completo y la página de inicio con el lienzo de bienvenida, dejando el contenido interno de las páginas de Proyectos para specs posteriores.
