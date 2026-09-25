# Feature Specification: Rediseño del layout de páginas de autenticación

**Feature Branch**: `006-auth-pages-redesign`

**Created**: 2026-09-24

**Status**: Draft

**Input**: User description: "Ayudame a mejorar el layout del login, create accout, recover pass, page, tenemos @DESIGN.md y usa como referencia este layout html /Users/elanda/Downloads/stitch_friday_workspace_app (1)/code.html"

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Inicio de sesión con el nuevo layout (Priority: P1)

Un usuario registrado llega a la página de login y encuentra una experiencia visual moderna, centrada y coherente con el design system de Friday: marca visible arriba, tarjeta blanca centrada con el logotipo, título "Iniciar sesión en Friday", botón prominente de "Continuar con Google" (marcado como recomendado), divisor "o continuar con correo", campos de correo y contraseña con iconos, opción de mostrar/ocultar contraseña, casilla "Recordar este dispositivo", botón de envío con flecha, y un pie de página con enlaces de confianza (Soporte, Privacidad, Términos).

**Why this priority**: El login es la puerta de entrada a la aplicación y la primera impresión de marca; es la página con más tráfico del flujo de autenticación y donde el diseño de referencia está completamente definido.

**Independent Test**: Puede probarse de forma independiente visitando la ruta de login: se verifica que el layout renderiza como en el diseño de referencia y que el inicio de sesión con correo/contraseña y con Google siguen funcionando igual que antes (misma validación, mismos errores, mismo comportamiento de "remember").

**Acceptance Scenarios**:

1. **Given** un usuario no autenticado, **When** accede a la página de login, **Then** ve la tarjeta centrada con marca, botón de Google con distintivo "Recomendado", divisor, formulario de correo/contraseña, casilla "Recordar este dispositivo", enlace "¿Olvidaste tu contraseña?" y enlace a registro.
2. **Given** un usuario en la página de login, **When** hace clic en el icono de ojo del campo contraseña, **Then** la contraseña pasa de oculta a visible (y viceversa al segundo clic).
3. **Given** un usuario que envía credenciales inválidas, **When** el servidor responde con error, **Then** el mensaje de error se muestra dentro del nuevo layout sin romper la estructura visual.
4. **Given** un usuario que inicia sesión correctamente, **When** el envío es exitoso, **Then** es redirigido al dashboard igual que antes del rediseño.
5. **Given** un usuario en pantallas pequeñas (móvil), **When** accede a la página de login, **Then** la tarjeta se adapta a un ancho útil con márgenes laterales reducidos y sin scroll horizontal.

---

### User Story 2 - Registro de cuenta con el nuevo layout (Priority: P2)

Un nuevo usuario accede a "Crear cuenta" y encuentra una tarjeta centrada consistente con el login: logotipo, título orientado a registro, botón "Continuar con Google" como opción destacada, divisor, formulario con nombre, correo, contraseña y confirmación de contraseña, y enlace de retorno al login.

**Why this priority**: El registro es el segundo flujo más crítico para la conversión de nuevos equipos; comparte la estructura del login y la reutiliza con variaciones mínimas.

**Independent Test**: Puede probarse de forma independiente visitando la ruta de registro: se verifica el nuevo layout y que la creación de cuenta (correo/contraseña y Google) sigue funcionando con la misma validación y reglas de contraseña existentes.

**Acceptance Scenarios**:

1. **Given** un usuario no autenticado, **When** accede a la página de registro, **Then** ve una tarjeta centrada con el mismo sistema visual del login adaptada a creación de cuenta (título, campos nombre/correo/contraseña/confirmación, botón de registro y enlace "¿Ya tienes cuenta? Inicia sesión").
2. **Given** un usuario que envía datos inválidos (correo duplicado, contraseñas distintas), **When** el servidor responde, **Then** los errores se muestran por campo dentro del nuevo layout.

---

### User Story 3 - Recuperación de contraseña con el nuevo layout (Priority: P3)

Un usuario que olvidó su contraseña accede a "¿Olvidaste tu contraseña?" y a la página de restablecer contraseña, y encuentra tarjetas centradas con el mismo sistema visual: mensaje explicativo, campo de correo (solicitud) o campos de nueva contraseña (restablecimiento), y enlace de retorno al login.

**Why this priority**: Es un flujo de menor frecuencia pero crítico para retener usuarios bloqueados; el esfuerzo es bajo porque reutiliza la estructura base.

**Independent Test**: Puede probarse de forma independiente visitando las rutas de forgot-password y reset-password: se verifica el layout y que el envío del enlace por correo y el restablecimiento con token siguen funcionando como antes.

**Acceptance Scenarios**:

1. **Given** un usuario no autenticado, **When** accede a "¿Olvidaste tu contraseña?", **Then** ve una tarjeta centrada con título, mensaje explicativo, campo de correo con icono y botón de envío, más enlace de retorno al login.
2. **Given** un usuario con un enlace de restablecimiento válido, **When** accede a la página de restablecer contraseña, **Then** ve una tarjeta centrada con campos de nueva contraseña y confirmación, con toggle de visibilidad, y el restablecimiento funciona igual que antes.

---

### Edge Cases

- Pantallas muy estrechas (< 375px): la tarjeta debe mantener márgenes legibles y ningún elemento debe desbordar horizontalmente.
- Mensajes de estado del servidor (p. ej. "te hemos enviado el enlace") deben integrarse visualmente dentro de la tarjeta sin romper el layout.
- Errores de validación por campo deben aparecer junto a su campo correspondiente, no como bloques genéricos.
- Usuario ya autenticado que visita páginas de invitado: el comportamiento de redirección existente se mantiene.
- El botón de Google debe seguir mostrándose aunque la configuración del proveedor falle; el error se maneja en el flujo existente.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: Las cuatro páginas de autenticación (login, registro, solicitud de recuperación de contraseña y restablecimiento de contraseña) deben compartir una estructura visual común consistente con el design system definido en `DESIGN.md`.
- **FR-002**: La página de login debe presentar: encabezado de marca, tarjeta centrada blanca con logotipo, título "Iniciar sesión en Friday", subtítulo "Tu espacio de trabajo ágil y productivo", botón "Continuar con Google" con distintivo "Recomendado", divisor "o continuar con correo", campos de correo y contraseña con iconos, alternador de visibilidad de contraseña, casilla "Recordar este dispositivo", botón "Iniciar sesión" con icono de flecha, enlace "¿Olvidaste tu contraseña?", enlace "Regístrate gratis" y pie de página con estado del sistema y enlaces legales.
- **FR-003**: La paleta, tipografía (Inter), espaciados, radios y elevaciones deben tomarse de los tokens definidos en `DESIGN.md` (acento eléctrico indigo `#5B5BD6`, superficie `#F9F9FF`, tarjeta blanca con sombra suave y radio elevado, foco con anillo de acento).
- **FR-004**: El layout debe ser responsive: en escritorio la tarjeta tiene un ancho máximo de aproximadamente 440px centrada con halo de acento difuso detrás; en móvil usa márgenes laterales reducidos y tipografía de titular escalada.
- **FR-005**: Toda la funcionalidad existente se conserva sin cambios de comportamiento: autenticación con correo/contraseña, OAuth con Google como único proveedor, validaciones, mensajes de error por campo, mensajes de estado, "remember", envío de enlace de recuperación y restablecimiento con token.
- **FR-006**: Los mensajes de error de validación deben mostrarse junto al campo correspondiente con estilo consistente con el design system (color de error definido en `DESIGN.md`).
- **FR-007**: Las páginas deben ser accesibles: labels asociados a campos, foco visible con anillo de acento, contraste legible, y navegación completa por teclado.
- **FR-008**: El pie de página común debe incluir enlaces (Privacidad, Términos, Soporte), indicador de idioma y píldora de estado "Todos los sistemas operativos" como en el diseño de referencia.
- **FR-009**: El diseño se limita a las cuatro páginas de autenticación de invitado; el layout de aplicación autenticada (dashboard, tableros) queda fuera de alcance.

### Key Entities

No hay entidades de datos nuevas; el rediseño afecta únicamente a la capa de presentación de los flujos de autenticación existentes (sesión de usuario, solicitud de restablecimiento de contraseña).

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Las cuatro páginas renderizan visualmente equivalentes al diseño de referencia en escritorio y móvil (verificación por comparación visual con la pantalla de referencia).
- **SC-002**: El 100% de los flujos de autenticación existentes (login, registro, Google, recuperación, restablecimiento) siguen pasando sus tests de feature sin modificación de comportamiento.
- **SC-003**: Los usuarios pueden identificar y usar el botón "Continuar con Google" en menos de 3 segundos al llegar a la página de login.
- **SC-004**: Ninguna página genera scroll horizontal ni desbordamiento en viewports desde 320px de ancho hacia arriba.
- **SC-005**: Auditoría de accesibilidad sin violaciones críticas (contraste, labels, foco visible) en las cuatro páginas.

## Assumptions

- El alcance se limita a las páginas de invitado: login, registro, forgot-password y reset-password; páginas de verificación de correo o confirmación quedan fuera salvo que compartan el mismo layout base.
- El flujo funcional actual (rutas, controladores, validaciones, OAuth con Google único) no cambia; solo la presentación.
- El contenido textual en español del diseño de referencia se adopta como copy final (ajustes menores permitidos, p. ej. nombre de marca "Friday").
- Los iconos usados en el diseño de referencia (Google, candado, correo, ojo, flecha) pueden implementarse con iconos SVG equivalentes ya disponibles en el proyecto.
- El enlace de "Seguridad corporativa" y los enlaces legales del pie pueden apuntar a rutas existentes o marcadores de posición si no existen páginas legales aún.
- La fuente Inter ya está disponible en el proyecto (design system `DESIGN.md`).
