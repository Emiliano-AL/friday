# Feature Specification: Landing page pública en la ruta raíz

**Feature Branch**: `028-public-landing-page`

**Created**: 2026-10-16

**Status**: Draft

**Input**: User description: "usando el MCP de stitch ayudame a hacer un home page para este proyecto, esta estara en la ruta raiz, el proyecto es \"3835093330700486424\" y el item base es \"Friday — Landing Page (Minimalista & Simple)\""

> **Referencia de diseño**: proyecto Stitch `3835093330700486424`, pantalla
> "Friday — Landing Page (Minimalista & Simple)"
> (`screens/4801c2ac944c4a73bb5b7939073ffd44`, desktop 2560×7984). El diseño
> usa el codename "Friday"; en la página pública todo el texto visible debe
> decir **Eunox** (constitución v1.1.1, feature 026).

## User Scenarios & Testing _(mandatory)_

### User Story 1 - El visitante entiende qué es Eunox al entrar al dominio (Priority: P1)

Una persona sin sesión entra a `https://www.eunox.app/`. Hoy la redirigen
directo al login. Con esta feature ve una landing page minimalista que le
explica en pocos segundos qué es Eunox (gestión de proyectos, sprints, tareas,
backlog y tablero Kanban para equipos pequeños) y por qué le conviene.

**Why this priority**: es el primer contacto público con el producto después
del rebrand y de abrir el dominio. Sin esta página, el dominio raíz solo
muestra un formulario de login y no comunica ningún valor.

**Independent Test**: entra a la ruta raíz sin sesión y confirma que se
muestra la landing (no el login ni el dashboard) con el nombre Eunox, una
propuesta de valor y las secciones del diseño de referencia.

**Acceptance Scenarios**:

1. **Given** un visitante sin sesión, **When** abre la ruta raíz, **Then** ve
   la landing page con el nombre "Eunox", un titular con la propuesta de valor
   y una llamada a la acción principal, sin que lo redirijan al login.
2. **Given** un visitante sin sesión en la landing, **When** se desplaza por la
   página, **Then** ve las secciones del diseño de referencia (encabezado con
   navegación, hero, características principales del producto, llamada a la
   acción final y pie de página).
3. **Given** cualquier texto de la landing, **When** se revisa, **Then** no
   aparece el codename "Friday" en ningún texto visible ni en el título de la
   pestaña.

---

### User Story 2 - El visitante pasa de la landing a registrarse o iniciar sesión (Priority: P1)

Desde la landing, el visitante puede crear una cuenta (con correo o con
Google) o, si ya tiene una, iniciar sesión, con un solo clic desde el
encabezado o desde las llamadas a la acción.

**Why this priority**: la landing solo tiene valor si lleva a la conversión.
Si no hay un camino claro a registro o login, la página es un callejón sin
salida.

**Independent Test**: desde la landing, haz clic en cada llamada a la acción y
confirma que te lleva a la pantalla de registro o de inicio de sesión que ya
existe.

**Acceptance Scenarios**:

1. **Given** un visitante en la landing, **When** hace clic en la llamada a la
   acción principal ("Comenzar gratis" o equivalente), **Then** llega a la
   pantalla de registro existente.
2. **Given** un visitante en la landing, **When** hace clic en "Iniciar
   sesión" en el encabezado, **Then** llega a la pantalla de login existente.
3. **Given** un visitante que se registra o inicia sesión desde la landing,
   **When** termina el flujo, **Then** llega a su dashboard autenticado como
   hasta ahora.

---

### User Story 3 - Un usuario con sesión sigue viendo su dashboard en la raíz (Priority: P2)

Un usuario que ya inició sesión entra a la ruta raíz y ve su dashboard de
inicio como hoy, sin pasar por la landing.

**Why this priority**: la landing no debe romper el flujo diario de quienes ya
usan la app. Es P2 porque, si falla, el usuario todavía puede navegar a sus
proyectos, pero sería una regresión visible.

**Independent Test**: inicia sesión, abre la ruta raíz y confirma que se
muestra el dashboard actual con sus datos.

**Acceptance Scenarios**:

1. **Given** un usuario autenticado, **When** abre la ruta raíz, **Then** ve
   su dashboard de inicio (con o sin actividad) exactamente como antes de esta
   feature.
2. **Given** un usuario autenticado, **When** cierra sesión, **Then** al
   volver a la ruta raíz ve la landing pública.

---

### User Story 4 - La landing se ve bien en móvil y en modo oscuro (Priority: P3)

Un visitante entra desde el teléfono o con el sistema en modo oscuro y la
página sigue siendo legible, sin desbordes horizontales y con las llamadas a
la acción a la vista.

**Why this priority**: buena parte del tráfico de primera visita llega desde
móvil (enlaces compartidos, invitaciones). El diseño de referencia es solo de
escritorio, así que la versión móvil se adapta a partir de él.

**Independent Test**: abre la landing en un viewport de 375 px de ancho y en
modo oscuro, y confirma que no hay scroll horizontal, que el texto es legible
y que las llamadas a la acción funcionan.

**Acceptance Scenarios**:

1. **Given** un viewport de 375 px de ancho, **When** se carga la landing,
   **Then** el contenido se reacomoda en una sola columna, sin scroll
   horizontal y con la navegación accesible.
2. **Given** un sistema en modo oscuro, **When** se carga la landing, **Then**
   los colores siguen el mismo criterio de tema que el resto de la app y el
   contraste del texto es legible.

---

### Edge Cases

- **Visitante con enlace de invitación**: las rutas `invitations/{token}`
  siguen funcionando igual. La landing no las intercepta.
- **Usuario con sesión expirada**: al entrar a la raíz ve la landing (no un
  error) y puede volver a iniciar sesión desde ahí.
- **Dominio raíz sin `www`**: `eunox.app` ya redirige a `www.eunox.app`, así
  que la landing se ve igual en los dos.
- **Redirecciones existentes al home**: los flujos que hoy mandan a la ruta
  raíz después de login, registro o verificación siguen llevando al dashboard
  autenticado.
- **Imágenes o capturas del producto que no cargan**: la página sigue siendo
  legible y las llamadas a la acción siguen visibles. Las imágenes tienen
  texto alternativo.
- **Buscadores y enlaces compartidos**: la landing tiene título y descripción
  propios de Eunox para que se vea bien al compartirla o indexarla.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: La ruta raíz (`/`) MUST mostrar la landing page pública a
  visitantes sin sesión, en lugar de redirigirlos al login.
- **FR-002**: La ruta raíz MUST seguir mostrando el dashboard de inicio actual
  a usuarios autenticados, sin cambios de comportamiento ni de datos.
- **FR-003**: La landing MUST reproducir la estructura, jerarquía y estilo
  visual minimalista de la pantalla de referencia de Stitch "Friday — Landing
  Page (Minimalista & Simple)": encabezado con logo y navegación, hero con
  titular y llamada a la acción, sección de características del producto,
  llamada a la acción final y pie de página.
- **FR-004**: Todo texto visible, el título de la página y los metadatos para
  compartir MUST usar el nombre público "Eunox". El codename "Friday" no debe
  aparecer.
- **FR-005**: La landing MUST ofrecer acceso a la pantalla de registro
  existente desde al menos la llamada a la acción del hero y la llamada a la
  acción final.
- **FR-006**: La landing MUST ofrecer acceso a la pantalla de inicio de sesión
  existente desde el encabezado.
- **FR-007**: La sección de características MUST describir solo capacidades
  que el producto ya tiene (proyectos con métricas de avance, sprints, tareas
  con comentarios, backlog con captura rápida, tablero Kanban,
  colaboradores/invitaciones, inicio de sesión con Google). No debe prometer
  funciones que no existen.
- **FR-008**: El contenido de la landing MUST estar en español, igual que el
  resto de la interfaz.
- **FR-009**: La landing MUST adaptarse a pantallas desde 375 px de ancho sin
  scroll horizontal, y MUST respetar el tema claro/oscuro con el mismo
  criterio que el resto de la app.
- **FR-010**: La landing MUST incluir un título de página y una meta
  descripción propios de Eunox.
- **FR-011**: Los enlaces de navegación interna de la landing (por ejemplo,
  "Características") MUST llevar a la sección correspondiente dentro de la
  misma página.
- **FR-012**: La landing MUST ser estática. No muestra datos de usuarios,
  proyectos ni métricas reales, y no requiere consultas a datos de la
  aplicación.
- **FR-013**: Las rutas públicas existentes (login, registro, recuperación de
  contraseña, invitaciones, OAuth con Google) MUST mantener su comportamiento
  actual.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: El 100 % de las visitas sin sesión a la ruta raíz muestran la
  landing (0 redirecciones al login desde `/`).
- **SC-002**: El 100 % de las visitas con sesión a la ruta raíz muestran el
  dashboard, igual que antes de la feature.
- **SC-003**: Un visitante llega a la pantalla de registro o de login en 1
  clic desde la landing, desde el encabezado o desde cualquier llamada a la
  acción.
- **SC-004**: La landing muestra el contenido principal (titular y llamada a
  la acción) en menos de 2 segundos en una conexión de banda ancha típica.
- **SC-005**: Hay 0 apariciones del texto "Friday" en el contenido visible y
  en los metadatos de la landing.
- **SC-006**: En una revisión visual lado a lado con la pantalla de Stitch, la
  landing coincide en orden de secciones, jerarquía tipográfica y paleta, y
  las únicas diferencias son los cambios de marca (Friday → Eunox) y los
  ajustes responsive.
- **SC-007**: En viewports de 375 px, 768 px y 1280 px o más, la página no
  tiene scroll horizontal y todas las llamadas a la acción son visibles y
  usables.

## Assumptions

- **Visitantes vs. usuarios autenticados**: la landing es solo para
  visitantes sin sesión. Los usuarios autenticados siguen viendo su dashboard
  en `/`, así que no hace falta una ruta nueva para el dashboard ni cambiar
  las redirecciones posteriores al login.
- **Fuente del diseño**: el layout, los textos base y el estilo se toman de la
  pantalla de Stitch indicada. Los archivos de esa pantalla requieren
  autenticación de Stitch para descargarse, así que en la fase de planeación
  se obtendrán con el MCP de Stitch (o el usuario los exportará). Los textos
  que mencionen "Friday" se adaptan a "Eunox".
- **Contenido estático**: no hay testimonios, precios, formularios de contacto
  ni suscripción a newsletter, salvo que estén en el diseño de referencia. Si
  aparecen, se muestran solo como contenido estático (o se omiten si
  requieren funciones nuevas) para respetar el alcance MVP (Principio V).
- **Precios**: Eunox no tiene planes de pago. Si el diseño incluye una
  sección de precios, se omite o se reemplaza por un mensaje de acceso
  gratuito.
- **Enlaces legales**: si el diseño incluye "Privacidad" o "Términos" y esas
  páginas no existen, esos enlaces se omiten en esta feature.
- **Logo**: se usa el logotipo/isotipo de Eunox que ya tiene la app desde la
  feature 026.
- **Sin nuevas dependencias**: la landing se construye con el stack y los
  componentes actuales (Principio II).
