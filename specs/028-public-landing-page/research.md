# Research: Landing page pública en la ruta raíz (028)

## Contexto existente (verificado en el código)

- `routes/web.php`: `Route::get('/', HomeController::class)->middleware(['auth'])->name('home')`.
  Hoy los invitados se redirigen al login (`tests/Feature/ExampleTest.php` lo
  verifica).
- `HomeController::__invoke` renderiza `AppHome` con `dashboard` (null si no
  hay actividad).
- Login, registro, Google OAuth y aceptar invitación redirigen con
  `redirect()->intended(route('home'))`. Logout redirige a `route('login')`.
- `resources/js/pages/Welcome.vue` es el scaffolding de Laravel ("Let's get
  started"). No se usa en ninguna ruta.
- Design tokens de Stitch ya mapeados en `resources/css/app.css` (`@theme`):
  `surface*`, `on-surface*`, `primary*`, tipografía `headline-*`/`body-*`/`label-*`,
  espaciados `space-*`, `margin(-mobile)`, fuente `font-inter`. La app es
  **solo modo claro** (no hay variante `dark:` ni tokens oscuros).
- Componentes reutilizables: `AppShell/AppLogo.vue` (isotipo Eunox),
  `Auth/*` (estética de las pantallas públicas), `$page.props.name` = "Eunox".
- `app.ts`: título `"{title} - Eunox"`.
- Guardia de marca: `tests/Feature/Branding/NoLegacyBrandInUiTest.php` falla si
  aparece `\bFriday\b` en `resources/js`, `resources/views` o `app/Mail`.
- Rutas tipadas con Wayfinder (`@/routes`): `login()`, `register()`.

## Decisiones

### D1. Cómo servir landing y dashboard en la misma ruta `/`

- **Decision**: quitar el middleware `auth` de la ruta `home`. Al inicio de
  `HomeController::__invoke`: si `$request->user()` es `null`, devolver
  `Inertia::render('Landing')`; si no, sigue el flujo actual del dashboard.
- **Rationale**: se mantiene un único nombre de ruta (`home`), así que todas
  las redirecciones `intended(route('home'))`, los enlaces Wayfinder y las
  pruebas del dashboard siguen igual (FR-002, FR-013). Es el cambio más chico
  posible (Principio V).
- **Alternatives considered**:
    - _Mover el dashboard a `/dashboard` y dejar `/` solo para la landing_:
      obliga a cambiar redirecciones, enlaces del shell y pruebas existentes, y
      rompe la URL que los usuarios ya tienen guardada. Se rechaza.
    - _Middleware `guest` + ruta duplicada_: Laravel no permite dos `GET /` con
      distinto middleware sin lógica condicional. Se rechaza.
    - _Controlador invocable separado `LandingController` con dispatch en la
      ruta_: añade una indirección sin beneficio. Se rechaza.

### D2. Fuente del diseño de Stitch

- **Decision**: aplicar el design system del proyecto Stitch, obtenido con
  `mcp__stitch__get_project` (paleta indigo `#5B5BD6`, Inter, radios de 8 px,
  bordes hairline, estética "Linear / Things 3": minimalismo técnico, calma,
  cero ruido visual), que ya está mapeado a tokens de Tailwind en `app.css`.
  La estructura sigue la pantalla "Friday — Landing Page (Minimalista &
  Simple)" (`4801c2ac944c4a73bb5b7939073ffd44`; lienzo de 1280 × 3528 px, unas
  6–7 secciones de altura de viewport).
- **Bloqueo abierto**: el HTML y la captura de esa pantalla
  (`files/5300332354389292747`, `files/1309548839119580517`) devuelven
  **401 UNAUTHENTICATED** si se descargan sin credenciales de Stitch.
  **Antes de la tarea de maquetado**, el usuario debe exportar el HTML y la
  captura desde Stitch a `specs/028-public-landing-page/design/` (por ejemplo,
  `landing.html` y `landing.jpg`) o autorizar la descarga autenticada. Si no
  está disponible, se implementa con la estructura de referencia (D3) y el
  design system, y se ajusta en una pasada de fidelidad visual (SC-006).
- **Rationale**: los tokens ya garantizan coherencia con el resto de la app.
  Lo único que depende del archivo es el orden fino y los textos.

### D3. Estructura de secciones

- **Decision**: la página `Landing.vue` se compone de secciones en
  `resources/js/components/Landing/`:
    1. `LandingHeader`: logo + nombre (`$page.props.name`), anclas
       ("Características", "Cómo funciona") y botones "Iniciar sesión" (ghost) y
       "Comenzar gratis" (primario). En móvil, solo el logo y los dos botones
       compactos (sin menú hamburguesa, por YAGNI).
    2. `LandingHero`: titular, subtítulo, CTA primario (registro) y secundario
       (login), y una vista previa del producto hecha con markup estático (una
       maqueta simplificada de tablero/lista con los tokens de la app, sin
       imágenes rasterizadas).
    3. `LandingFeatures` (`id="caracteristicas"`): una cuadrícula de 6 tarjetas
       con capacidades reales: proyectos con métricas de avance, sprints con
       activación y cierre, Kanban, backlog con captura rápida, tareas con
       comentarios, colaboradores e invitaciones.
    4. `LandingWorkflow` (`id="como-funciona"`): 3 pasos (crea un proyecto →
       planea el sprint → avanza en el tablero).
    5. `LandingCta`: un bloque final con CTA de registro y una nota de inicio de
       sesión con Google.
    6. `LandingFooter`: logo, © año y nombre. Sin enlaces legales ni de precios.
- **Rationale**: cubre FR-003, FR-005, FR-006, FR-007 y FR-011. Las secciones
  que no existan en el diseño final se eliminan en la pasada de fidelidad.
- **Alternatives considered**: un solo archivo monolítico de unas 600 líneas
  (más difícil de ajustar contra el diseño; se rechaza). Copiar el HTML de
  Stitch tal cual (usa Tailwind CDN y clases ajenas a los tokens; se rechaza
  y se traduce a los tokens propios).

#### Ajustes según exportación

Exportación no disponible (`design/` vacío al iniciar la implementación); se usa la estructura D3.

### D4. Tema claro/oscuro

- **Decision**: solo modo claro, igual que el resto de la app. FR-009 dice
  "con el mismo criterio que el resto de la app", y hoy ese criterio es claro
  únicamente.
- **Rationale**: introducir modo oscuro solo en la landing la haría
  inconsistente con login y registro, que es justo a donde lleva.
- **Alternatives considered**: usar los tokens oscuros del Stitch
  (`colorMode: DARK`). Se descarta por ahora; sería una feature global de
  theming.

### D5. SEO y metadatos

- **Decision**: `<Head title="Gestión de proyectos ágil para equipos">` (el
  `title` callback produce "… - Eunox") con `<meta name="description">`,
  `og:title`, `og:description` y `og:type=website`, todo con el nombre Eunox.
- **Rationale**: FR-010 y el caso borde de compartir enlaces. Inertia SSR no
  está habilitado, así que los metadatos se aplican del lado del cliente
  (suficiente para Google). Las previsualizaciones de redes sociales
  dependerían de SSR, que queda fuera de alcance.

### D6. `Welcome.vue`

- **Decision**: eliminar `resources/js/pages/Welcome.vue` (scaffolding sin
  uso).
- **Rationale**: evita confundirlo con la landing nueva. No tiene referencias.

### D7. Pruebas

- **Decision**: crear `tests/Feature/Landing/LandingPageTest.php` (Pest):
    - un invitado en `GET /` recibe 200 con el componente Inertia `Landing`;
    - un usuario autenticado en `GET /` recibe `AppHome`;
    - los enlaces de invitación siguen respondiendo igual.
    - Reemplazar el test de `ExampleTest.php` que espera la redirección al login.
- La guardia de marca existente cubre SC-005. La validación visual y
  responsive (SC-006, SC-007) se hace a mano según `quickstart.md`, porque no
  hay pruebas de navegador en el proyecto.
