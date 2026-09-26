---
description: 'Task list for 007-workspace-look-feel'
---

# Tasks: Look & Feel del workspace autenticado

**Input**: Design documents from `/specs/007-workspace-look-feel/` (plan.md, spec.md, research.md, data-model.md, contracts/shell-ui.md, quickstart.md)

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/shell-ui.md

**Tests**: Incluye tests de feature Pest (acordado en plan.md / Constitución III para la nueva prop compartida y el render del shell). La suite existente (113 tests) debe permanecer verde en todo momento (SC-002).

**Organization**: Tasks grouped by user story para implementación y prueba independiente.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: Maps to user stories from spec.md (US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- Web app monolítico: `app/` (PHP), `resources/js/` (Vue/TS), `resources/css/` (Tailwind v4), `tests/Feature/` (Pest)
- Rutas tipadas generadas por Wayfinder en `resources/js/routes/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Tokens, fuente de iconos y tipos compartidos que usan todos los componentes del shell (decisiones D1–D3 de research.md)

- [x] T001 [P] Extender `resources/css/app.css`: agregar a `@theme` la escala de radios de `DESIGN.md` (`--radius-*`: DEFAULT 0.5rem, md 0.75rem, lg 1rem, xl 1.5rem), las elevaciones (`--shadow-card: 0 1px 2px rgba(16,24,40,0.04)`, `--shadow-popover: 0 4px 6px -1px rgba(16,24,40,0.08), 0 2px 4px -2px rgba(16,24,40,0.04)`, `--shadow-modal: 0 20px 25px -5px rgba(16,24,40,0.1), 0 8px 10px -6px rgba(16,24,40,0.04)`) y la clase utilitaria `.material-symbols-outlined` (font-family, font-weight normal, font-style normal, line-height 1, letter-spacing normal, text-transform none, display inline-block, white-space nowrap, direction ltr, -webkit-font-smoothing antialiased) según contracts/shell-ui.md §4
- [x] T002 [P] Agregar la fuente **Material Symbols Outlined** a la lista `fonts` del plugin `laravel()` en `vite.config.ts` (mismo mecanismo Bunny Fonts que Inter/Instrument Sans; pesos 400; verificar que el build self-hostea el woff2 sin requests externos en runtime)
- [x] T003 [P] Crear `resources/js/types/shell.ts` con las interfaces del contrato: `ProjectSummary { id: number; title: string }`, `NavItem { label: string; icon: string; to: Route | null; match: string; disabled?: boolean; badge?: number }`, `CommandAction { label: string; icon: string; shortcut?: string; keywords: string[]; run: () => void }` y tipo de región activa para popovers (`'notifications' | 'profile' | null`), según contracts/shell-ui.md §1 y §3
- [x] T004 [P] Crear `resources/js/Components/AppShell/AppIcon.vue`: wrapper que renderiza el ligature de Material Symbols por prop `name`, con `size?: number` (default 18) y `filled?: boolean` (font-variation-settings `'FILL' 1`), según contracts/shell-ui.md §3

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Prop compartida `projects` que necesitan el selector de contexto (y cualquier página autenticada)

**⚠️ CRITICAL**: Sin esta fase no puede validarse ninguna user story (el shell lee `page.props.projects`)

- [x] T005 En `app/Http/Middleware/HandleInertiaRequests.php` agregar a `share()`, solo para usuarios autenticados, la prop `projects`: resumen ligero `[{ id, title }]` de los proyectos del usuario (misma relación/base de query que `ProjectController`, orden alfabético por `title`, máximo 50); usuarios invitados no reciben la clave; ejecutar `vendor/bin/pint --dirty --format agent` (decisión D6, data-model.md)

**Checkpoint**: Foundation ready — arranca US1

---

## Phase 3: User Story 1 - Navegación dentro del nuevo shell del workspace (Priority: P1) 🎯 MVP

**Goal**: Shell completo de tres regiones (sidebar, topbar, contenido) con tokens de `DESIGN.md` en modo claro, navegación con estado activo, colapso de barra, responsive drawer, y página de inicio con lienzo de bienvenida (FR-001–FR-007, FR-010). Las secciones sin destino se ven deshabilitadas con "Próximamente" (FR-011).

**Independent Test**: Login → shell renderiza como `screen.png`; navegar a Proyectos marca activo; colapsar/expandir funciona; `/` muestra el lienzo "Espacio listo para crear"; direct URL a `/projects` activa el item correcto; en <1024px la barra es drawer. Ver quickstart.md §2, §5, §7.

### Tests for User Story 1 ⚠️

> **NOTE: Escribir PRIMERO, debe FALLAR antes de la implementación del layout**

- [x] T006 [US1] Crear `tests/Feature/Shell/ShellRenderingTest.php` (Pest): usuario autenticado ve `AppHome` y `Projects/Index` con 200 y la prop compartida `projects` presente (con y sin proyectos del usuario, incluido el caso lista vacía `[]`); usuario invitado NO recibe `projects`; usar factories existentes de `User` y `Project` según convención de tests/Pest.php

### Implementation for User Story 1

- [x] T007 [P] [US1] Crear `resources/js/Components/AppShell/AppShellSidebar.vue`: estructura de la barra (logo "Friday" + botón colapsar, slot para ContextSwitcher, slot para CTA, slot para nav, slot para tarjeta de usuario), `bg-surface-container-low`, ancho 256px, animación de ocultar/mostrar 200ms según referencia
- [x] T008 [P] [US1] Crear `resources/js/Components/AppShell/SidebarNav.vue`: render de items `NavItem` con `AppIcon`, estados hover (`bg-surface-container-high`) y activo (exactamente uno, derivado del URL actual vs `match`: `bg-surface-container-lowest`, semibold, `shadow-card`); items con `disabled: true` o `to: null` se renderizan con estilo deshabilitado + indicador "Próximamente" y no navegan ni disparan peticiones; truncado con ellipsis en labels largos (FR-004, FR-011)
- [x] T009 [P] [US1] Crear `resources/js/Components/AppShell/ContextSwitcher.vue`: prop `projects: ProjectSummary[]` + `current?: { id: number; title: string } | null`; dropdown con la lista alfabética navegando a la ficha del proyecto (`projects.show`); distintivo con inicial del título; estado vacío con llamada a crear el primer proyecto cuando `projects` está vacío; truncado con ellipsis (data-model.md)
- [x] T010 [P] [US1] Crear `resources/js/Components/AppShell/SidebarUserCard.vue`: prop `user: { name: string; email: string; avatar?: string | null }`; avatar con imagen o fallback de iniciales sobre `bg-primary-container text-on-primary`; nombre y correo truncados con ellipsis; botón "more_horiz" visual
- [x] T011 [P] [US1] Crear `resources/js/Components/AppShell/AppShellTopbar.vue`: `bg-surface/85 backdrop-blur`, botón toggle de barra, trigger de búsqueda "Buscar o teclear comando..." con hint `⌘K`, botón campana (sin punto de no leídos), botón ayuda, avatar de usuario; emite eventos (`toggle-sidebar`, `open-search`, `toggle-notifications`, `toggle-profile`) — el cableado de notificaciones/perfil/paleta llega en US2/US3 (contrato §2)
- [x] T012 [P] [US1] Crear `resources/js/Components/AppShell/HomeCanvas.vue`: lienzo de bienvenida FR-010 — barra de contexto (migas "Mi Espacio / Lienzo en Blanco", chip "Borrador", botón "Comandos rápidos" con hint `⌘K`, botón "Añadir bloque" `bg-primary-container`), lienzo `bg-surface-container-lowest rounded-xl` con retícula radial sutil y gradientes difusos, hero "Espacio listo para crear" con `text-headline-lg`, tres tarjetas de acción sugerida (Nueva tarea / Planear sprint / Integrar git), indicador inferior "Sincronizado en la nube • Friday v2.4"; los botones emiten eventos (`open-palette`) o quedan inertes según FR-012
- [x] T013 [US1] Reescribir `resources/js/Layouts/AuthenticatedLayout.vue`: componer las tres regiones con los componentes T007–T011; estado `sidebarCollapsed` (desktop, en memoria) y `sidebarOpen` (drawer móvil con velo <1024px); cerrar drawer/popovers con `ESC` y clic fuera; aplicar `.friday-focus-scope` en la raíz; `aria-label` en regiones (`Navegación principal`, `Barra superior`); consumir `page.props.auth.user` y `page.props.projects` (FR-001, FR-006, FR-007; contrato §2)
- [x] T014 [US1] Reescribir `resources/js/pages/AppHome.vue`: usar `HomeCanvas` dentro del layout con el contenido del lienzo; eliminar el contenido Breeze viejo (`#header` + tarjeta gris)

**Checkpoint**: US1 funciona de forma independiente — shell + navegación + inicio. El avatar/campana/paleta aún sin cablear (US2/US3)

---

## Phase 4: User Story 2 - Gestión de cuenta desde el shell (Priority: P2)

**Goal**: Menú de perfil con identidad del usuario, opciones "Próximamente" deshabilitadas y cierre de sesión real (FR-011, FR-013).

**Independent Test**: Clic en avatar → menú con nombre/correo y opciones; clic fuera o `ESC` cierra; "Cerrar sesión" hace POST y redirige a `/login`; usuario sin foto ve iniciales. Ver quickstart.md §3.

### Implementation for User Story 2

- [x] T015 [P] [US2] Crear `resources/js/Components/AppShell/ProfileMenu.vue`: sección de identidad (avatar/iniciales, nombre, correo), items "Detalles del perfil" y "Ajustes de cuenta" deshabilitados con "Próximamente", divisor, "Cerrar sesión" en `text-error` que hace POST a la ruta `logout` de Wayfinder; tarjeta blanca `shadow-popover rounded-xl` con animación de apertura (contrato §3)
- [x] T016 [US2] Cablear en `resources/js/Layouts/AuthenticatedLayout.vue` (o `AppShellTopbar.vue` según quedó T013) el menú de perfil: `aria-expanded` en el avatar, cierre con clic fuera/`ESC`, retorno de foco al avatar, y estado activo de popover único (abrir perfil cierra notificaciones y viceversa) (FR-008)

**Checkpoint**: US1 + US2 funcionan independientemente; flujos de autenticación existentes siguen verdes

---

## Phase 5: User Story 3 - Acciones rápidas y superficie de comandos (Priority: P3)

**Goal**: Paleta de comandos `⌘K`/`Ctrl+K` con filtrado y navegación por teclado, atajo `C` y botón "Nueva Tarea" abren la paleta, campana con popover de estado vacío (FR-009, FR-012).

**Independent Test**: `⌘K`/`Ctrl+K`/botón abren la paleta; filtrar reduce la lista; Enter navega; `ESC`/velo cierran; `C` fuera de inputs abre la paleta (dentro de inputs no); campana abre "Sin notificaciones" sin datos simulados. Ver quickstart.md §4.

### Implementation for User Story 3

- [x] T017 [P] [US3] Crear `resources/js/Components/AppShell/CommandPalette.vue`: estado `open` controlado por props/emits; lista `CommandAction` con acciones iniciales (Crear nueva tarea → navega a `projects.index`; Ir al Panel → `home`; Ir a Proyectos → `projects.index`); filtrado case-insensitive por `label` + `keywords`; teclado `↑`/`↓` seleccionan, `Enter` ejecuta `run()`, `ESC` cierra y devuelve foco; velo `bg-inverse-surface/20 backdrop-blur-sm`, tarjeta centrada `shadow-modal rounded-xl`, autofocus en el input (FR-009, contrato §3)
- [x] T018 [P] [US3] Crear `resources/js/Components/AppShell/NotificationsPopover.vue`: popover anclado a la campana mostrando únicamente el estado vacío "Sin notificaciones" (sin lista, sin "marcar leídas", sin punto de no leídos) según FR-012 y contrato §3
- [x] T019 [US3] Cablear atajos y paleta en `resources/js/Layouts/AuthenticatedLayout.vue`: listener global `⌘K`/`Ctrl+K` con `preventDefault` (abre/cierra), tecla `C` solo cuando el foco no está en input/textarea/contenteditable, botón "Nueva Tarea" del sidebar y trigger de búsqueda del topbar abren la paleta; el botón "Comandos rápidos" de `HomeCanvas.vue` abre la paleta (FR-009, FR-012)
- [x] T020 [US3] Cablear la campana en `resources/js/Layouts/AuthenticatedLayout.vue`: clic abre `NotificationsPopover`, `aria-expanded`, cierre con clic fuera/`ESC`, exclusividad con el menú de perfil (FR-008)

**Checkpoint**: Las tres historias funcionan de forma independiente; el diseño de referencia queda completo en modo claro

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Calidad cruzada sobre las tres historias (SC-001–SC-006)

- [x] T021 [P] Quality gates: `vendor/bin/pint --dirty --format agent`, `npm run types:check` y `npm run build` sin errores; el ordenamiento de clases Tailwind del formatter aplicado en los archivos tocados
- [x] T022 [P] Pase de accesibilidad FR-007 sobre el shell: labels `aria-*` de regiones y botones, foco visible con anillo en todos los controles, contraste AA sobre `surface`/`surface-container-lowest`, cierre `ESC` en paleta/menús/drawer, y recorrido completo solo con teclado (documentar hallazgos en la PR)
- [x] T023 [P] Pase responsive FR-006/SC-004: verificar 1280px (barra fija 256px), 800px (drawer con velo) y 375px/320px (un columnado, sin scroll horizontal ni superposición de regiones) con las herramientas de dispositivo del navegador; capturar evidencia
- [x] T024 Validar `quickstart.md` completo (§1–§7) contra la implementación real y actualizar el archivo si algún paso difiere; verificar `browser-logs` de Boost sin errores de frontend durante el recorrido
- [x] T025 `php artisan test --compact` completo en verde (incluye `ShellRenderingTest.php` y los 113 tests del baseline — SC-002) y commit final con los artefactos de la spec

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Sin dependencias — T001–T004 en paralelo
- **Foundational (Phase 2)**: Depende de Setup (usa convenciones de `shell.ts`); BLOQUEA las validaciones de las user stories pero no su escritura
- **User Stories (Phase 3–5)**: US1 depende de Setup + Foundational; US2 y US3 dependen de US1 (componentes del layout ya existen; el cableado toca archivos de US1)
- **Polish (Phase 6)**: Depende de las tres historias completas

### User Story Dependencies

- **US1 (P1)**: Después de Foundational. Sin dependencias de otras historias. 🎯 MVP
- **US2 (P2)**: Después de US1 (el menú se ancla al avatar creado en T011/T013). Independiente en comportamiento.
- **US3 (P3)**: Después de US1 (paleta y campana se anclan a topbar/layout de US1). Independiente en comportamiento.

### Within Each User Story

- T006 (test) escrito primero y en rojo antes de T013
- Componentes [P] antes de la integración (T013 / T016 / T019–T020)
- Checkpoint al final de cada historia para validarla de forma independiente

### Parallel Opportunities

- **Setup**: T001–T004 en paralelo total (archivos distintos: app.css, vite.config.ts, types/shell.ts, AppIcon.vue)
- **US1**: T007–T012 en paralelo tras Setup+Foundational (un archivo por componente; dependen solo de T003/T004/T005); luego T013 integra y T014 cierra
- **US2**: T015 en paralelo con cualquier trabajo pendiente de US1 que no toque `AuthenticatedLayout.vue`
- **US3**: T017 y T018 en paralelo (archivos distintos); T019–T020 secuenciales sobre el layout
- **Polish**: T021–T023 en paralelo

---

## Parallel Example: User Story 1

```bash
# Tras Setup + Foundational, lanzar juntos los componentes del shell:
Task: "Crear resources/js/Components/AppShell/AppShellSidebar.vue (T007)"
Task: "Crear resources/js/Components/AppShell/SidebarNav.vue (T008)"
Task: "Crear resources/js/Components/AppShell/ContextSwitcher.vue (T009)"
Task: "Crear resources/js/Components/AppShell/SidebarUserCard.vue (T010)"
Task: "Crear resources/js/Components/AppShell/AppShellTopbar.vue (T011)"
Task: "Crear resources/js/Components/AppShell/HomeCanvas.vue (T012)"
# Luego, en orden: T013 (layout) → T014 (AppHome)
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1 Setup + Phase 2 Foundational
2. Phase 3 US1 (test T006 primero → componentes → layout → home)
3. **STOP and VALIDATE**: quickstart §2, §5, §7 + `php artisan test --compact`
4. Valor entregado: toda la app autenticada vive en el nuevo shell con página de inicio renovada

### Incremental Delivery

1. Setup + Foundational → base lista
2. US1 → validar → el workspace se ve y navega como el diseño (MVP)
3. US2 → validar → cuenta funcional desde el shell
4. US3 → validar → comandos y notificaciones (estado vacío)
5. Polish → gates de calidad y validación completa de quickstart.md

### Parallel Team Strategy

Con dos o más desarrolladores tras Setup+Foundational:

- Dev A: US1 (T006–T014)
- Dev B: puede adelantar T015/T017/T018 (componentes aislados) integrando después

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to user story for traceability
- Los archivos `resources/js/pages/Projects/*` NO se modifican en esta feature (entran al shell vía layout); su reestilo es trabajo posterior (Q3)
- Los componentes Breeze legacy de `resources/js/Components/` (Dropdown, NavLink, etc.) quedan intactos; su eliminación no es parte de esta spec
- `resources/js/types/shell.ts` usa el tipo `Route` de Wayfinder según exista exportado; si no, tipar `to` como `{ url: string } & Record<string, unknown>` compatible con las rutas generadas
- Commit after each task or logical group; detenerse en cualquier checkpoint para validar la historia de forma independiente
