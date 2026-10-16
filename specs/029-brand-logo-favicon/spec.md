# Feature Specification: Logo de Eunox en favicon y puntos de marca

**Feature Branch**: `029-brand-logo-favicon`

**Created**: 2026-10-16

**Status**: Draft

**Input**: User description: "ahora ayudame a colocar en favicon y donde sea necesario este logo: `<span class="bg-primary-container text-on-primary flex h-7 w-7 shrink-0 items-center justify-center rounded-lg"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h7a4 4 0 0 1 4 4v1H8a1 1 0 0 0-1 1v10H4V4z"></path><path d="M13 10h6a1 1 0 0 1 1 1v2a4 4 0 0 1-4 4h-3v-7z" opacity="0.6"></path><circle cx="16.5" cy="18.5" r="2.5"></circle></svg></span>`"

> **Logo de referencia**: isotipo blanco (dos bloques y un punto) sobre un
> círculo color índigo (el `rounded-lg` de la app, `1rem` sobre 28 px, se ve como círculo) de la marca (`#5B5BD6`). Es el mismo que ya
> usan la barra lateral de la app y la landing (feature 028).

## Contexto actual (verificado)

- El **favicon** de la pestaña (`.ico` y `.svg`) y el **ícono de la pantalla
  de inicio en iOS** (apple-touch-icon) todavía son el **logo de Laravel** (rojo).
- La barra lateral de la app y el header y footer de la landing ya muestran
  el logo correcto.
- La tarjeta de login, registro y recuperación de contraseña muestra el
  isotipo, pero en otra variante (índigo sobre fondo índigo tenue), que no es
  la versión de referencia.
- El encabezado de las pantallas públicas de auth no muestra logo, solo el
  texto "Eunox Cloud OS".
- Los **correos** (invitación a proyecto, restablecer contraseña) muestran
  solo el nombre "Eunox" en texto en el encabezado, sin logo.

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Favicon con el logo de Eunox (Priority: P1)

Una persona abre Eunox en el navegador y, en la pestaña, en los marcadores y
en el historial, ve el logo de Eunox en lugar del de Laravel.

**Why this priority**: el favicon de Laravel es la inconsistencia de marca
más visible. Aparece en todas las páginas, para invitados y para usuarios
con sesión, y hace que el producto se vea sin terminar.

**Independent Test**: abre cualquier página de Eunox (landing, login,
dashboard) y confirma que la pestaña muestra el isotipo índigo de Eunox.

**Acceptance Scenarios**:

1. **Given** cualquier página de Eunox, **When** se carga en un navegador de
   escritorio, **Then** la pestaña muestra el isotipo de Eunox (cuadro índigo
   con el símbolo blanco) y no el logo de Laravel.
2. **Given** un navegador que solo soporta el formato de ícono clásico,
   **When** se carga la página, **Then** también muestra el isotipo de Eunox.
3. **Given** la pestaña en un tema de navegador claro u oscuro, **When** se ve
   el favicon, **Then** sigue siendo reconocible (el cuadro índigo da su
   propio fondo).

---

### User Story 2 - Ícono al agregar Eunox a la pantalla de inicio (Priority: P2)

Una persona agrega Eunox a la pantalla de inicio de su teléfono (iOS o
Android) o lo instala como acceso directo, y el ícono es el logo de Eunox
con buena resolución.

**Why this priority**: pasa menos que ver la pestaña, pero hoy muestra el
logo de Laravel y queda en el teléfono del usuario de forma permanente.

**Independent Test**: en un iPhone o Android, usa "Agregar a pantalla de
inicio" desde `www.eunox.app` y confirma que el ícono es el de Eunox, nítido
y sin bordes blancos raros.

**Acceptance Scenarios**:

1. **Given** Safari en iOS, **When** se agrega Eunox a la pantalla de inicio,
   **Then** el ícono es el isotipo de Eunox en alta resolución, con el
   nombre "Eunox".
2. **Given** Chrome en Android, **When** se agrega a la pantalla de inicio,
   **Then** el ícono y el nombre son los de Eunox, y el color de la barra del
   navegador es el índigo de la marca.

---

### User Story 3 - Logo consistente en las pantallas públicas (Priority: P2)

Una persona que entra a login, registro o recuperación de contraseña ve el
mismo logo que en la landing y en la app, lo que refuerza que está en el
sitio correcto.

**Why this priority**: las pantallas de auth son la puerta de entrada. Hoy
usan una variante distinta del isotipo y el encabezado no tiene logo.

**Independent Test**: abre `/login`, `/register` y `/forgot-password` y
confirma que el logo es idéntico al del header de la landing.

**Acceptance Scenarios**:

1. **Given** la pantalla de login, registro o recuperación de contraseña,
   **When** se carga, **Then** la tarjeta muestra el logo en la versión de
   referencia (cuadro índigo sólido con el símbolo blanco).
2. **Given** el encabezado de las pantallas públicas de auth, **When** se
   carga, **Then** muestra el logo junto al nombre "Eunox" y lleva a la
   landing al hacer clic.

---

### User Story 4 - Logo en los correos de Eunox (Priority: P3)

Una persona recibe un correo de Eunox (invitación a un proyecto o
restablecer contraseña) y ve el logo de Eunox junto al nombre en el
encabezado del correo.

**Why this priority**: refuerza la confianza en correos transaccionales
(menos sospecha de phishing), pero el correo ya funciona y muestra el nombre,
así que es mejora y no corrección.

**Independent Test**: envía una invitación de prueba y confirma, en Gmail
web y en un cliente móvil, que el encabezado muestra el logo y "Eunox".

**Acceptance Scenarios**:

1. **Given** un correo de invitación o de restablecer contraseña, **When** se
   abre en un cliente de correo común (Gmail, Outlook, Apple Mail), **Then**
   el encabezado muestra el logo de Eunox y el nombre "Eunox".
2. **Given** un cliente de correo que bloquea imágenes, **When** se abre el
   correo, **Then** el encabezado sigue mostrando el nombre "Eunox" en texto
   y la imagen tiene texto alternativo "Eunox".

---

### Edge Cases

- **Caché del favicon**: los navegadores guardan el favicon anterior. El
  cambio debe verse en una ventana privada o después de recargar sin caché, y
  no debe requerir que los usuarios borren datos para verlo con el tiempo.
- **Tamaños pequeños (16 × 16 px)**: el isotipo debe seguir leyéndose. Si el
  punto o los bloques se pierden, se puede simplificar levemente solo en el
  tamaño más chico, conservando la silueta.
- **Correos con imágenes bloqueadas**: el nombre en texto se mantiene como
  respaldo (US4, escenario 2).
- **Clientes de correo que no muestran imágenes vectoriales**: el logo del
  correo debe ser una imagen compatible (no vectorial) alojada en el dominio
  público de Eunox.
- **Rastros del logo de Laravel**: ningún archivo de ícono público debe
  seguir siendo el de Laravel.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: Todas las páginas de Eunox MUST declarar como favicon el
  isotipo de referencia (cuadro índigo `#5B5BD6` redondeado con el símbolo
  blanco), en formato vectorial y en formato de ícono clásico (16 y 32 px).
- **FR-002**: El sitio MUST ofrecer un ícono para la pantalla de inicio de
  iOS de 180 × 180 px con el isotipo de Eunox, sin transparencias que iOS
  rellene de negro.
- **FR-003**: El sitio MUST ofrecer un manifiesto de aplicación web con el
  nombre "Eunox", íconos de 192 y 512 px y el color de tema índigo de la
  marca, para que Android lo muestre correctamente al agregarlo a la pantalla
  de inicio.
- **FR-004**: Las páginas MUST declarar el color de tema índigo de la marca
  para la barra del navegador en móvil.
- **FR-005**: Ningún archivo de ícono público (favicon, apple-touch-icon)
  MUST seguir mostrando el logo de Laravel.
- **FR-006**: La tarjeta de las pantallas de auth (login, registro,
  recuperación y restablecimiento de contraseña, verificación de correo)
  MUST mostrar el logo en la versión de referencia, la misma que la barra
  lateral y la landing.
- **FR-007**: El encabezado de las pantallas públicas de auth MUST mostrar el
  logo y el nombre "Eunox", con enlace a la landing (`/`).
- **FR-008**: El logo en la interfaz MUST salir de una sola fuente
  reutilizable, para que cualquier cambio futuro del logo se haga en un solo
  lugar. Las copias sueltas del símbolo se eliminan.
- **FR-009**: El encabezado de los correos transaccionales MUST mostrar el
  logo de Eunox como imagen compatible con clientes de correo, servida desde
  el dominio público, con texto alternativo "Eunox", junto al nombre en
  texto.
- **FR-010**: El texto "Friday" MUST NOT aparecer en ningún nombre de
  archivo de ícono, manifiesto, texto alternativo ni metadato agregado por
  esta feature.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: 100 % de las páginas (landing, auth y app) muestran el favicon
  de Eunox en una ventana privada de Chrome, Safari y Firefox.
- **SC-002**: Hay 0 archivos de ícono públicos con el logo de Laravel.
- **SC-003**: Al agregar Eunox a la pantalla de inicio en iOS y en Android, el
  ícono es el de Eunox y se ve nítido (sin pixelado visible) en las 2
  plataformas.
- **SC-004**: En una revisión visual, el logo de las pantallas de auth, la
  landing y la barra lateral es idéntico (misma forma, colores y
  proporciones) en el 100 % de los lugares donde aparece.
- **SC-005**: Los 2 correos transaccionales (invitación y restablecer
  contraseña) muestran el logo en Gmail web y en Apple Mail.
- **SC-006**: El isotipo sigue siendo reconocible a 16 × 16 px en la pestaña
  del navegador.

## Assumptions

- **Logo fuente**: se usa exactamente el SVG proporcionado: cuadro
  con `rounded-lg` en `#5B5BD6` (token
  `primary-container`) con el símbolo en blanco (token `on-primary`). Para
  favicons y PNG se mantienen esas proporciones (símbolo de unos 16/28 del
  lado).
- **Sin logotipo con texto**: no hay versión del logo con el nombre dibujado.
  Donde se necesite el nombre, se pone "Eunox" en texto junto al isotipo.
- **Imagen para compartir en redes (og:image)**: queda fuera de alcance. La
  landing (028) ya tiene título y descripción para compartir.
- **Correos**: se cambia solo el encabezado de la plantilla compartida de
  correos, lo que cubre invitación y restablecer contraseña. El cuerpo de
  los correos no cambia.
- **Variantes**: no se crea versión monocroma ni modo oscuro del logo. El
  cuadro índigo funciona sobre fondos claros y oscuros.
- **Dominio público**: las imágenes del correo se sirven desde
  `https://www.eunox.app`, que ya tiene HTTPS.
- **Sin dependencias nuevas**: los archivos de ícono se generan una sola vez
  y se versionan en el repositorio. No hace falta un paso de build nuevo.
