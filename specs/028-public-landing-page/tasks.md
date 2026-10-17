---
description: 'Task list for 028-public-landing-page'
---

# Tasks: Landing page pública en la ruta raíz

**Input**: Design documents from `/specs/028-public-landing-page/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/root-route.md, quickstart.md

**Tests**: Incluidos. La constitución (Principio III) exige escribirlos primero y verlos fallar antes de implementar.

**Organization**: Agrupado por historia: US1 el visitante ve la landing, US2 conversión a registro/login, US3 el usuario autenticado conserva su dashboard, US4 responsive.

## Format: `[ID] [P?] [Story] Description`

## Convenciones para todas las tareas

- Imports con alias en mayúscula, igual que el resto del código: `@/Components/...`, `@/Layouts/...`. Rutas con Wayfinder: `import { login, register } from '@/routes'`, y en plantillas `<Link :href="register()">`.
- Solo tokens de `resources/css/app.css` (`bg-surface`, `text-on-surface`, `text-on-surface-variant`, `bg-primary-container`, `text-on-primary`, `border-outline-variant`, `text-headline-xl`, `text-body-lg`, `px-margin-mobile lg:px-margin`, `gap-space-*`, `font-inter`). Nada de colores hex sueltos ni clases `dark:`, porque la app es solo de modo claro (research D4).
- Nunca escribir la palabra "Friday" en archivos `.vue`/`.ts`. `NoLegacyBrandInUiTest` falla si aparece. El nombre visible sale de `$page.props.name` ("Eunox").
- Textos en español. Solo describir capacidades existentes (FR-007): proyectos con métricas de avance, sprints con activación y cierre, tablero Kanban, backlog con captura rápida, tareas con comentarios, colaboradores e invitaciones, inicio de sesión con Google.
- Diseño de referencia: si existe `specs/028-public-landing-page/design/landing.html` o `landing.jpg` (exportado de Stitch), seguir su orden, textos (cambiando Friday por Eunox) y jerarquía. Si no existe, usar la estructura de research D3 y el estilo de `GuestLayout`/`Auth/*` (fondo `bg-surface`, halo `bg-primary-fixed/30 blur-3xl`, tarjetas `bg-surface-container-lowest` con borde `border-outline-variant/40`, `rounded-xl`).
- Después de cada fase PHP: `vendor/bin/pint --dirty`. Después de cada fase Vue: `npm run check`.

---

## Phase 1: Setup (Shared Infrastructure)

- [X] T001 Crear y cambiar a la rama `028-public-landing-page` desde `main` actualizado (`git switch main && git pull && git switch -c 028-public-landing-page`), llevando los archivos ya creados en `specs/028-public-landing-page/`.
- [X] T002 [P] Verificar si existe la exportación de Stitch en `specs/028-public-landing-page/design/` (`landing.html` y/o `landing.jpg`). Si existe, leerla y anotar en `specs/028-public-landing-page/research.md` (sección D3, subtítulo "Ajustes según exportación") el orden real de secciones y los textos, con Friday cambiado a Eunox. Si no existe, anotar "Exportación no disponible; se usa la estructura D3" y continuar.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: la ruta `/` debe aceptar invitados y despachar a `Landing`. Todas las historias dependen de esto.

- [X] T003 Crear `tests/Feature/Landing/LandingPageTest.php` (Pest) con: (a) `test('guests see the public landing page at the root route')`, donde `$this->get(route('home'))->assertOk()->assertInertia(fn ($page) => $page->component('Landing'))`; (b) `test('the landing page receives no dashboard data')`, que verifica con `AssertableInertia::fromTestResponse(...)->toArray()['props']` que no existen las claves `dashboard` ni `projects`. Ejecutar `php artisan test --compact tests/Feature/Landing`: debe **fallar** (hoy hay redirección al login).
- [X] T004 En `tests/Feature/ExampleTest.php`, borrar el test `guests are redirected to login from the home page`. Si el archivo queda vacío, eliminarlo, porque el caso ya está cubierto por T003(a).
- [X] T005 En `routes/web.php`, cambiar `Route::get('/', HomeController::class)->middleware(['auth'])->name('home');` por `Route::get('/', HomeController::class)->name('home');` (contrato: `contracts/root-route.md`).
- [X] T006 En `app/Http/Controllers/HomeController.php`, al inicio de `__invoke`, después de `$user = $request->user();`, agregar `if ($user === null) { return Inertia::render('Landing'); }`. Actualizar el docblock a "Display the public landing page for guests or the home dashboard for authenticated users." Mantener el tipo de retorno `Response`.
- [X] T007 Crear `resources/js/pages/Landing.vue` mínimo (`<script setup lang="ts">` con `Head` de `@inertiajs/vue3` y un `<main>` vacío) para que el componente exista. Ejecutar T003: debe pasar. Ejecutar `php artisan test --compact tests/Feature/Home`: todo verde (los usuarios autenticados siguen viendo `AppHome`).

**Checkpoint**: invitados reciben `Landing` (200). Usuarios autenticados, `AppHome`.

---

## Phase 3: User Story 1 - El visitante entiende qué es Eunox (Priority: P1) 🎯 MVP

**Goal**: landing con header, hero, características, cómo funciona, CTA final y footer, con marca Eunox y metadatos propios.

**Independent Test**: quickstart.md §2. En una ventana privada, `/` muestra todas las secciones y la pestaña dice "… - Eunox".

### Tests for User Story 1 ⚠️

- [X] T008 [US1] Ejecutar `php artisan test --compact tests/Feature/Branding` antes de empezar (línea base en verde). Se repite en T017.

### Implementation for User Story 1

- [X] T009 [P] [US1] Crear `resources/js/components/Landing/LandingHeader.vue`: `<header>` con contenedor `mx-auto max-w-6xl`, a la izquierda `AppLogo` (`@/Components/AppShell/AppLogo.vue`) más `{{ $page.props.name }}` en `text-headline-sm font-semibold`, enlazados a `#top`. En el centro (`hidden md:flex`), anclas `<a href="#caracteristicas">Características</a>` y `<a href="#como-funciona">Cómo funciona</a>`. A la derecha, ranuras para los botones (se cablean en US2): por ahora `<Link>`s de texto "Iniciar sesión" y "Comenzar gratis" con `href="#"`.
- [X] T010 [P] [US1] Crear `resources/js/components/Landing/LandingHero.vue`: `<section>` centrada con una etiqueta tipo chip (`rounded-full bg-surface-container-low text-label-sm`, por ejemplo "Gestión ágil, sin ruido"), `<h1>` en `text-headline-xl-mobile md:text-[48px] md:leading-[56px] font-semibold tracking-tight` (por ejemplo "Planea, ejecuta y termina tus sprints con calma"), un subtítulo `text-body-lg text-on-surface-variant max-w-2xl` y dos CTA ("Comenzar gratis" primario, `bg-primary-container text-on-primary rounded-lg`; "Iniciar sesión" ghost con borde), con `href="#"` por ahora. Debajo, una vista previa estática del producto: una tarjeta `bg-surface-container-lowest border rounded-xl shadow-sm` con una mini-barra de ventana (3 puntos) y un tablero simplificado de 3 columnas ("Por hacer", "En progreso", "Hecho") con 2–3 tarjetas de tarea falsas cada una (título, chip de tipo, punto de prioridad). Solo markup y tokens, sin imágenes. `aria-hidden="true"` en la vista previa, y el halo decorativo como en `GuestLayout`.
- [X] T011 [P] [US1] Crear `resources/js/components/Landing/LandingFeatures.vue`: `<section id="caracteristicas" class="scroll-mt-24">` con título "Todo lo que tu equipo necesita, nada que sobre" y una cuadrícula `grid gap-space-lg sm:grid-cols-2 lg:grid-cols-3` de 6 tarjetas, definidas en un arreglo `const features = [...]` con `{ icon, title, description }`: Proyectos con métricas de avance · Sprints con activación y cierre · Tablero Kanban · Backlog con captura rápida · Tareas con comentarios · Colaboradores e invitaciones. Íconos SVG inline de 20 px, en un cuadro `bg-primary-fixed/40 text-primary rounded-lg`.
- [X] T012 [P] [US1] Crear `resources/js/components/Landing/LandingWorkflow.vue`: `<section id="como-funciona" class="scroll-mt-24">` con título "Cómo funciona" y 3 pasos numerados (`ol`, en fila en `md:` y en columna en móvil): 1 "Crea un proyecto" (invita a tu equipo), 2 "Planea el sprint" (mueve tareas del backlog), 3 "Avanza en el tablero" (Kanban y métricas en vivo).
- [X] T013 [P] [US1] Crear `resources/js/components/Landing/LandingCta.vue`: un bloque `rounded-xl bg-inverse-surface text-inverse-on-surface` (o `bg-primary-container text-on-primary` si el diseño exportado lo indica) con el titular "Empieza hoy, es gratis", un subtítulo con "Crea tu cuenta con correo o con Google", y un CTA "Comenzar gratis" (`href="#"` por ahora).
- [X] T014 [P] [US1] Crear `resources/js/components/Landing/LandingFooter.vue`: `<footer>` con borde superior `border-outline-variant/40`, `AppLogo` + `{{ $page.props.name }}` y `© {{ new Date().getFullYear() }} {{ $page.props.name }}. Todos los derechos reservados.` en `text-body-sm text-on-surface-variant`. Sin enlaces legales ni de precios (spec, Assumptions).
- [X] T015 [US1] Completar `resources/js/pages/Landing.vue`: `<Head title="Gestión de proyectos ágil para equipos">` con `<meta name="description" content="Eunox organiza proyectos, sprints, backlog y tablero Kanban en un solo lugar, sin ruido.">`, `<meta property="og:title" content="Eunox — Gestión de proyectos ágil para equipos">`, `<meta property="og:description" …>` (mismo texto) y `<meta property="og:type" content="website">`. Raíz `<div id="top" class="bg-surface font-inter text-on-surface min-h-screen selection:bg-primary-container selection:text-on-primary-container">` con `LandingHeader`, `<main>` (`LandingHero`, `LandingFeatures`, `LandingWorkflow`, `LandingCta`) y `LandingFooter`. Agregar `scroll-smooth` con `class="scroll-smooth"` en el contenedor, o `html { scroll-behavior: smooth }` solo si ya existe una regla global. No usar layout (ni `GuestLayout` ni `AuthenticatedLayout`).
- [X] T016 [US1] _(N/A: no había exportación de Stitch en `design/`; se usó la estructura D3)_ Si T002 encontró la exportación de Stitch, ajustar T009–T015 al orden, textos y jerarquía del diseño (agregar o eliminar secciones según corresponda, siempre respetando FR-007 y la ausencia de precios y legales).
- [X] T017 [US1] Ejecutar `npm run check` y `php artisan test --compact tests/Feature/Branding tests/Feature/Landing`: todo verde. Ningún "Friday" (SC-005).

**Checkpoint**: la landing completa es visible para invitados (MVP).

---

## Phase 4: User Story 2 - Del visitante a registro/login (Priority: P1)

**Goal**: todos los CTA llevan a `register()` y "Iniciar sesión" a `login()`, en 1 clic (SC-003).

**Independent Test**: quickstart.md §3.

### Tests for User Story 2 ⚠️

- [X] T018 [US2] En `tests/Feature/Landing/LandingPageTest.php`, agregar `test('the login and register pages stay reachable for guests')`: `$this->get(route('login'))->assertOk()` y `$this->get(route('register'))->assertOk()`. Agregar también `test('logging in from the landing still lands on the dashboard')`: `User::factory()->create()`, `post(route('login'), ['email' => …, 'password' => 'password'])` → `assertRedirect(route('home', absolute: false))`, y luego `actingAs` + `get(route('home'))` con componente `AppHome`. Deben pasar (son regresiones de FR-013).

### Implementation for User Story 2

- [X] T019 [P] [US2] En `resources/js/components/Landing/LandingHeader.vue`, importar `{ login, register } from '@/routes'` y `Link` de `@inertiajs/vue3`. "Iniciar sesión" → `<Link :href="login()">` (estilo ghost), "Comenzar gratis" → `<Link :href="register()">` (primario `bg-primary-container text-on-primary rounded-lg px-space-md py-space-sm text-label-md`).
- [X] T020 [P] [US2] En `resources/js/components/Landing/LandingHero.vue`, cambiar el CTA primario a `<Link :href="register()">` y el secundario a `<Link :href="login()">`.
- [X] T021 [P] [US2] En `resources/js/components/Landing/LandingCta.vue`, cambiar el CTA a `<Link :href="register()">`.
- [X] T022 [US2] Ejecutar `npm run check` y `php artisan test --compact tests/Feature/Landing`. Verificar en el navegador (`https://friday.test/`, ventana privada) que los 3 "Comenzar gratis" abren `/register`, "Iniciar sesión" abre `/login` y las anclas desplazan a su sección.

**Checkpoint**: la conversión funciona desde todos los puntos de entrada.

---

## Phase 5: User Story 3 - El usuario autenticado conserva su dashboard (Priority: P2)

**Goal**: `/` con sesión sigue mostrando `AppHome` sin cambios. Al cerrar sesión, `/` vuelve a mostrar la landing.

**Independent Test**: quickstart.md §4.

### Tests for User Story 3 ⚠️

- [X] T023 [US3] En `tests/Feature/Landing/LandingPageTest.php`, agregar `test('authenticated users keep seeing the dashboard at the root route')`: `actingAs(User::factory()->create())->get(route('home'))` → `assertOk()` y componente `AppHome` (no `Landing`). Agregar también `test('after logging out the root route shows the landing again')`: `actingAs($user)->post(route('logout'))`, y luego `get(route('home'))` con componente `Landing`.
- [X] T024 [US3] Agregar `test('invitation links keep working for guests')`: crear `ProjectInvitation::factory()->withToken('tok')->create()` y verificar que `get(route('invitations.show', 'tok'))` responde igual que antes (`assertOk()`). Revisar en `tests/Feature/Projects/` el componente esperado y reutilizar la misma aserción.
- [X] T025 [US3] Ejecutar `php artisan test --compact tests/Feature/Landing tests/Feature/Home tests/Feature/Auth tests/Feature/Projects`: todo verde. Si algún test existente dependía de la redirección `/ → /login` para invitados, actualizarlo para que espere `Landing`.

**Checkpoint**: sin regresiones para usuarios existentes.

---

## Phase 6: User Story 4 - Responsive (Priority: P3)

**Goal**: sin scroll horizontal y con los CTA visibles en 375, 768 y ≥1280 px (SC-007).

**Independent Test**: quickstart.md §5.1.

- [X] T026 [P] [US4] En `resources/js/components/Landing/LandingHeader.vue`, en móvil (`< md`): ocultar las anclas, reducir "Iniciar sesión" a texto y mantener "Comenzar gratis" compacto. Sin menú hamburguesa (YAGNI). Usar `px-margin-mobile lg:px-margin`.
- [X] T027 [P] [US4] En `resources/js/components/Landing/LandingHero.vue`, apilar los CTA en columna en móvil (`flex-col sm:flex-row`, `w-full sm:w-auto`). En la vista previa del tablero, mostrar 1 columna en `< sm`, 2 en `sm` y 3 en `md+`. Agregar `overflow-hidden` al contenedor de la vista previa y al halo decorativo para evitar scroll horizontal.
- [X] T028 [US4] Revisar con DevTools en 375, 768 y 1280 px que `document.documentElement.scrollWidth === window.innerWidth` en cada ancho y corregir cualquier desborde (halos, grids, textos largos con `break-words`).

**Checkpoint**: landing usable en móvil, tablet y escritorio.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T029 [P] Eliminar `resources/js/pages/Welcome.vue` (scaffolding sin uso, research D6). Antes, confirmar con `grep -rn "Welcome" app routes resources/js` que no tiene referencias.
- [X] T030 [P] Accesibilidad: un solo `<h1>` (hero) y `<h2>` por sección; `aria-label="Principal"` en el `<nav>` del header; foco visible en enlaces y botones (`focus-visible:ring-2 ring-primary-container`); vista previa y halos con `aria-hidden="true"`; íconos SVG decorativos con `aria-hidden="true"`.
- [ ] T031 _(Pendiente: requiere la exportación de Stitch en `design/`)_ Comparar lado a lado con la captura de Stitch (si está en `design/`) y ajustar espaciados, tamaños y orden (SC-006). Documentar en `specs/028-public-landing-page/research.md` cualquier diferencia intencional.
- [X] T032 Ejecutar `vendor/bin/pint --dirty`, `npm run check` y el suite completo `php artisan test --compact`: todo verde.
- [ ] T033 _(§2–§5 verificados con tests y capturas a 375/768/1280 px; falta §6 tras el deploy)_ Validar `specs/028-public-landing-page/quickstart.md` §2–§5 en `https://friday.test/` y marcar el resultado. Después del deploy, §6: `curl -sI https://www.eunox.app/` debe devolver `200`.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de Setup. **Bloquea** todas las historias (la ruta y el componente `Landing` deben existir).
- **US1 (Phase 3)**: depende de Foundational.
- **US2 (Phase 4)**: depende de US1 (cablea los CTA de componentes creados en US1).
- **US3 (Phase 5)**: depende solo de Foundational. Se puede hacer en paralelo con US1 y US2 (solo tests de backend).
- **US4 (Phase 6)**: depende de US1 (ajusta sus componentes) y se recomienda después de US2.
- **Polish (Phase 7)**: depende de todas las historias.

### Within Each Story

- Primero los tests (T003, T018, T023–T024), y deben fallar cuando aplique.
- Primero los componentes de sección (en paralelo), luego la composición en `Landing.vue`, luego la verificación.

### Parallel Opportunities

- T002 se puede hacer junto con el resto de Setup.
- US1: T009–T014 son 6 archivos distintos y se pueden hacer en paralelo.
- US2: T019–T021 en paralelo.
- US3 (T023–T025) en paralelo con US1 y US2.
- US4: T026 y T027 en paralelo.
- Polish: T029 y T030 en paralelo.

## Parallel Example: User Story 1

```text
Task: "T009 Crear LandingHeader.vue en resources/js/components/Landing/"
Task: "T010 Crear LandingHero.vue en resources/js/components/Landing/"
Task: "T011 Crear LandingFeatures.vue en resources/js/components/Landing/"
Task: "T012 Crear LandingWorkflow.vue en resources/js/components/Landing/"
Task: "T013 Crear LandingCta.vue en resources/js/components/Landing/"
Task: "T014 Crear LandingFooter.vue en resources/js/components/Landing/"
```

## Implementation Strategy

### MVP First (User Story 1)

1. Phase 1 + Phase 2: `/` ya despacha `Landing` a invitados.
2. Phase 3: landing completa con marca Eunox.
3. **STOP y validar**: quickstart §2.

### Incremental Delivery

1. Foundational, luego US1 (la landing es visible), y deploy opcional.
2. US2: CTA funcionales. **Esta es la mínima entrega pública recomendada.**
3. US3: blindaje de regresiones con tests.
4. US4: pulido responsive, luego Polish, y deploy final.
