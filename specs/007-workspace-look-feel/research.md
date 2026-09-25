# Research: Look & Feel del workspace autenticado

**Feature**: 007-workspace-look-feel
**Date**: 2026-09-25
**Fuente**: exploración del codebase (Tailwind v4 CSS-first, componentes existentes, middleware Inertia, Wayfinder, tests) + diseño de referencia (`code.html`/`screen.png`) + tokens de `DESIGN.md`.

No quedaron NEEDS CLARIFICATION pendientes en el Technical Context; las decisiones de alcance (Q1/Q2/Q3) ya fueron resueltas en el spec. Esta investigación cubre las elecciones técnicas y de patrón.

## D1. Tokens de diseño: extender `@theme` CSS-first de Tailwind v4

- **Decision**: Reutilizar y extender el bloque `@theme` ya existente en `resources/css/app.css` (introducido en 006): agregar la escala de radios y las elevaciones (sombras) de `DESIGN.md` como tokens de tema, y una clase utilitaria `.material-symbols-outlined` para la fuente de iconos.
- **Rationale**: Tailwind v4.1 no usa `tailwind.config.js`; todo vive en CSS. Los colores y la escala tipográfica de `DESIGN.md` ya están mapeados (`--color-*`, `--text-*` con line-height/peso) y generan utilidades (`bg-surface-container`, `text-headline-sm`, `p-space-md`). Faltan radios (escala propia: DEFAULT 0.5rem, md 0.75rem, lg 1rem, xl 1.5rem) y sombras (3 niveles de elevación) que hoy se escribirían como valores arbitrarios en cada clase.
- **Alternatives considered**: (a) Valores arbitrarios inline como en el HTML de referencia — rechazado: duplicación y deriva frente a `DESIGN.md`. (b) Crear `tailwind.config.js` híbrido — rechazado: contradice la configuración CSS-first ya adoptada en 006.

## D2. Estrategia de layout: reescribir `AuthenticatedLayout.vue` in-place

- **Decision**: Reescribir `resources/js/Layouts/AuthenticatedLayout.vue` componiéndolo con componentes de `Components/AppShell/`; conservar su nombre y ruta actuales.
- **Rationale**: Las 5 páginas consumidoras (`AppHome`, `Projects/{Index,Show,Create,Edit}`) lo importan; al no renombrar, el cambio es transparente para ellas (FR-001: "reemplaza al layout autenticado actual"). El layout actual es stock Breeze basado en grises, incompatible con el diseño de referencia.
- **Alternatives considered**: (a) Nuevo `AppShell.vue` + actualizar 5 imports — rechazado: churn innecesario, dos nombres para "el layout autenticado". (b) Envolver el layout viejo — rechazado: arrastra clases y estructura que el diseño no usa.

## D3. Iconografía: Material Symbols (Outlined) vía Bunny Fonts + wrapper

- **Decision**: Cargar la fuente **Material Symbols Outlined** agregándola a la lista `fonts` del plugin `laravel()` en `vite.config.ts` (mismo mecanismo con el que hoy se cargan Inter e Instrument Sans), exponer una clase `.material-symbols-outlined` en `app.css`, y crear `Components/AppShell/AppIcon.vue` que renderiza el ligature por nombre (`<AppIcon name="folder" />`).
- **Rationale**: El diseño de referencia usa Material Symbols en todos sus iconos (~25+); la fuente por ligatures garantiza paridad 1:1 sin dibujar paths SVG a mano. Bunny Fonts es el proveedor ya configurado (self-host vía build, sin dependencia npm externa en runtime). Constitución II se respeta: no hay paquetes npm nuevos.
- **Alternatives considered**: (a) SVGs inline dibujados a mano extendiendo `AuthIcon.vue` — rechazado: ~25 iconos que mantener, riesgo de deriva visual frente a la referencia. (b) Librería npm de iconos (lucide, etc.) — rechazado: requiere aprobación de dependencias (Constitución II) y no coincide con la familia del diseño. (c) Google Fonts CDN directo — rechazado: el proyecto ya self-hostea fuentes vía Bunny.

## D4. Paleta de comandos: implementación cliente-only propia

- **Decision**: `CommandPalette.vue` con estado local al layout (no global store): lista estática de `CommandAction` (etiqueta, icono, atajo, handler de navegación Wayfinder), filtrado por texto, navegación con flechas/enter, cierre con `ESC`/clic en velo, retorno de foco al disparador. Apertura con `⌘K`/`Ctrl+K` (listener global con `preventDefault`), botón "Comandos rápidos" y campo de búsqueda del topbar. El atajo `C` y el botón "Nueva Tarea" abren la paleta. Acciones iniciales: Panel, Proyectos, Crear nueva tarea (navega a Proyectos, donde vive la creación).
- **Rationale**: Cumple FR-009/FR-012 sin backend. No existe librería de paleta aprobada en el stack y añadir una violaría Constitución II/V; el comportamiento necesario (filtrar una lista corta, manejar teclado) es simple de implementar con Vue puro.
- **Alternatives considered**: (a) Añadir `cmdk`/headlessui — rechazado por II/V. (b) Pinia para estado global — rechazado: YAGNI, el estado vive y muere con el layout. (c) Atajo `C` navegando directo a creación de tarea — rechazado: la creación existe solo dentro de un proyecto; abrir la paleta es consistente con el diseño (la acción "Crear nueva tarea" vive en la paleta).

## D5. Notificaciones: popover en estado vacío (sin backend)

- **Decision**: Campana en el topbar con punto indicador solo si hubiera notificaciones reales (hoy nunca se muestra); el popover lista un estado vacío "Sin notificaciones". Botón "Marcar leídas" no se incluye (no hay datos).
- **Rationale**: FR-012/Q1: "sin datos simulados ni indicador de no leídos". Mantiene la superficie del diseño lista para conectar backend futuro.
- **Alternatives considered**: (a) Datos de ejemplo como en el HTML — rechazado explícitamente por el spec. (b) Ocultar la campana — rechazado en Q1 (opción B no elegida).

## D6. Datos del selector de contexto: compartir resumen de proyectos

- **Decision**: En `HandleInertiaRequests::share()`, para usuarios autenticados, agregar `projects` = lista ligera `[{ id, title }]` de sus proyectos (mismo query base que `ProjectController`, orden alfabético, tope razonable p. ej. 50).
- **Rationale**: El selector de contexto vive en el layout, que se renderiza en todas las páginas autenticadas; Inertia solo inyecta props desde el servidor. Es reutilización de datos existentes (User↔Project), sin endpoints, migraciones ni lógica nueva — coherente con la asunción "datos de usuario/proyectos existentes se reutilizan".
- **Alternatives considered**: (a) Endpoint aparte consumido con fetch desde el layout — rechazado: rompe el modelo Inertia (props desde servidor) y añade backend innecesario. (b) Pasar `projects` solo a páginas concretas — rechazado: el switcher necesita el dato en todo el shell.

## D7. Colapso de la barra lateral: estado en memoria

- **Decision**: `ref` en el layout; al colapsar, la barra se oculta y el contenido ocupa todo el ancho. No se persiste entre recargas.
- **Rationale**: El spec no exige persistencia (US1 habla solo de colapsar/expandir). YAGNI: persistir añade decisión de almacenamiento y sincronización multi-pestaña sin requisito.
- **Alternatives considered**: (a) `localStorage` — posible mejora futura; hoy fuera de alcance. (b) Estado "solo iconos" (mini-barra) — el diseño de referencia no la contempla; la animación de referencia es ocultar/mostrar completa.

## D8. Testing: suite Pest existente + tests de render del shell

- **Decision**: Mantener verde la suite completa (`php artisan test --compact`, incluye Auth/Projects/Sprints/Tasks). Agregar `tests/Feature/Shell/ShellRenderingTest.php` con aserciones de render: páginas autenticadas responden 200 para el usuario autenticado y exponen las props compartidas nuevas (`projects` presente/según contexto, `auth.user` intacto). Verificación visual manual vía `quickstart.md` (escenarios US1–US3) y `browser-logs` de Boost ante errores.
- **Rationale**: Constitución III exige Pest para lógica de dominio; esta feature es presentación y no tiene lógica de dominio, pero el render servidor (layout + props) sí es verificable con feature tests reales. No existe stack de browser tests en el proyecto; añadir Dusk/Laravel Cloud browser testing violaría Constitución II/V.
- **Alternatives considered**: (a) Dusk — rechazado por II/V. (b) Tests JS (vitest) — no hay infraestructura JS de tests en el repo; mantener la convención.

## D9. Responsive: breakpoints Tailwind, barra como drawer

- **Decision**: `lg:` (≥1024px) shell completo fijo; `<lg` la barra lateral es un drawer deslizante con velo (estado controlado desde el layout, botón hamburguesa en topbar), contenido a ancho completo. Transición de `left`/`translate` de 200ms como en la referencia.
- **Rationale**: Replica la arquitectura del diseño (`fixed` sidebar + contenido con `pl-64`) con las herramientas del stack. El spec fija los tres cortes (FR-006).
- **Alternatives considered**: (a) Barra siempre visible reducida en móvil — rechazado: roba ancho útil y contradice FR-006 (un columnado). (b) Breakpoints custom — innecesario, los estándar cubren los cortes del spec.

## D10. Accesibilidad: foco, teclado y nombres de región

- **Decision**: Reusar el patrón de anillo de foco ya existente en `app.css` (`.friday-focus-scope *:focus-visible`) aplicado en la raíz del shell; regiones con `aria-label`/roles (`navigation`, `banner`, `main` vía etiquetas semánticas); atajos globales ignorados cuando el foco está en campos de texto (el atajo `C` no dispara dentro de inputs); `ESC` cierra paleta/popovers devolviendo foco; popovers con atributos `aria-expanded` en sus disparadores.
- **Rationale**: Cumple FR-007/SC-005 reutilizando infraestructura de 006.
- **Alternatives considered**: Librería de focus-trap — rechazado: la paleta maneja su propio foco (input autofocus + ciclado simple de lista); trap genérico añade dependencia.

## Hechos de codebase verificados (exploración 2026-09-25)

- Tailwind **v4.1** CSS-first; tokens de `DESIGN.md` ya en `@theme` (`--color-*`, `--text-*`, `--spacing-*`, `--font-inter`); patrón de foco `.friday-focus-scope` en `app.css:121-127`.
- `HandleInertiaRequests` comparte `auth.user` completo (sin whitelist); `resources/js/types/auth.ts` ya declara `avatar?: string` opcional.
- Wayfinder genera `@/routes` (`home`, `logout` POST, `projects/*`); consumo por importación nombrada.
- Componentes Breeze legacy intactos en `Components/`; patrón de componente de iconos tipado en `Components/Auth/AuthIcon.vue`; `cn()` disponible en `lib/utils.ts`.
- Suite Pest: `tests/Feature/{Auth,Projects,Sprints,Tasks}` con `RefreshDatabase`; sin tests de navegador.
- `vite.config.ts`: plugins `laravel()` (con fuentes Bunny Inter/Instrument Sans), `inertia()`, `tailwindcss()`, `vue()`, `wayfinder()`; `fmt` con ordenamiento de clases Tailwind (`cn`, `clsx`).
