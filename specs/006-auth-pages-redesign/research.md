# Research: Rediseño del layout de páginas de autenticación

**Feature**: `specs/006-auth-pages-redesign`
**Date**: 2026-09-24

## Resolución de incógnitas técnicas

### 1. Fuente tipográfica: Inter vs Instrument Sans

- **Contexto**: `DESIGN.md` y el HTML de referencia usan Inter; el proyecto actualmente declara `Instrument Sans` en `resources/css/app.css` (default de Breeze, Tailwind v4).
- **Decision**: Cargar Inter vía Google Fonts (`<link>` en `resources/views/app.blade.php`, igual que el HTML de referencia) y establecerla como `--font-sans` en el bloque `@theme` de `app.css`.
- **Rationale**: Es lo que dicta el design system y el diseño de referencia; el `<link>` no es una dependencia Composer/npm (cumple Constitución II). Pesa poco con `display=swap`.
- **Alternatives considered**: `@fontsource/inter` (npm) — rechazado porque añade dependencia sin aprobación; mantener Instrument Sans — rechazado porque contradice `DESIGN.md`.

### 2. Tokens de `DESIGN.md` en Tailwind CSS v4

- **Contexto**: El proyecto usa Tailwind v4 (`@import 'tailwindcss'` + `@theme inline`), sin `tailwind.config.js`. Los tokens del HTML de referencia (colores `primary-container` `#5b5bd6`, `surface` `#f9f9ff`, escala tipográfica, espaciados `space-*`, radios) vienen de Material-theme variables.
- **Decision**: Declarar los tokens de `DESIGN.md` (paleta completa, tipografía Inter, radios, espaciados) como custom properties en un bloque `@theme` en `resources/css/app.css`, exponiéndolos como utilidades Tailwind (`bg-primary-container`, `text-on-surface-variant`, `text-headline-lg`, etc.), tal como hace el HTML de referencia.
- **Rationale**: Es el mecanismo idiomático de Tailwind v4 para design tokens; permite clases casi 1:1 con el HTML de referencia. `DESIGN.md` (YAML front-matter) es la fuente de verdad de los valores.
- **Alternatives considered**: `tailwind.config.js` legacy — rechazado, Tailwind v4 ya no lo usa por defecto; hardcodear hex en clases — rechazado, rompe la fuente única de tokens.

### 3. Alcance de componentes compartidos (Breeze)

- **Contexto**: `TextInput`, `InputLabel`, `PrimaryButton`, `Checkbox` se usan tanto en `pages/Auth/*` como en `pages/Projects/*`. Restilizarlos globalmente afectaría páginas fuera del alcance (FR-009).
- **Decision**: Crear componentes nuevos acotados al dominio de autenticación bajo `resources/js/Components/Auth/` (tarjeta, campo con icono, botón social, checkbox, header/footer de marca), y dejar `GuestLayout.vue` como shell compartido reestilizado (solo lo usan páginas de invitado). Los componentes Breeze existentes quedan intactos.
- **Rationale**: Cumple FR-009 (sin efectos colaterales en Projects) y YAGNI (solo lo que el rediseño necesita). `GuestLayout` solo es usado por las 4 páginas Auth — verificado con grep.
- **Alternatives considered**: Restilizar `TextInput`/`PrimaryButton` globales — rechazado, cambia Projects fuera de alcance; copiar todo a pages/Auth sin componentes — rechazado, duplicaría markup 4 veces.

### 4. Visibilidad de contraseña (toggle ojo)

- **Contexto**: El diseño de referencia incluye botón para alternar `type` password/text; Breeze no lo trae.
- **Decision**: Implementarlo con estado local (`ref` booleano) en `Login.vue` y `ResetPassword.vue`, con iconos (SVG inline o el set de iconos ya disponible en el proyecto — verificar cuál existe; si no hay, SVG inline `eye`/`eye-off`).
- **Rationale**: Estado puramente local de UI; cero lógica de dominio, no requiere test de comportamiento (ver nota de verificación abajo).
- **Alternatives considered**: Componente compartido `PasswordInput` — aceptable si se reutiliza en las 2 páginas; decisión de implementación, ambas válidas.

### 5. Verificación de la capa visual

- **Contexto**: SC-001/SC-004/SC-005 son criterios visuales; Pest no valida CSS.
- **Decision**: El contrato de comportamiento son los tests de feature existentes (`tests/Feature/Auth/*`) que deben pasar sin modificación (FR-005). La equivalencia visual se verifica manualmente con la guía de `quickstart.md` (comparación contra `screen.png` / `code.html` del diseño de referencia) y Lighthouse (accesibilidad) vía Boost/DevTools.
- **Rationale**: Constitución III cubre lógica de dominio con Pest; el rediseño no introduce lógica nueva. Los tests existentes son la red de seguridad de regresión.
- **Alternatives considered**: Tests Dusk/browser de snapshot visual — rechazado, YAGNI para un rediseño puntual.

## Otros hallazgos

- Rutas/controladores de auth (`routes/auth.php`) y tests (`AuthenticationTest`, `RegistrationTest`, `PasswordResetTest`, `GoogleAuthenticationTest`) ya existen y cubren todos los flujos; no se tocan.
- `GoogleAuthenticationTest` cubre el flujo Socialite; el botón "Continuar con Google" sigue apuntando a la ruta nombrada `google.redirect` vía Wayfinder (`@/routes/google`).
- El distintivo "Recomendado" sobre el botón de Google y la píldora de estado "Todos los sistemas operativos" son elementos decorativos de confianza del diseño de referencia — se implementan tal cual (FR-002/FR-008).
- El idioma del copy es español, consistente con el diseño de referencia y la app (títulos existentes ya están en español).
