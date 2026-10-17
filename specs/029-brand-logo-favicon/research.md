# Research: Logo de Eunox en favicon y puntos de marca (029)

## Contexto existente (verificado)

- `resources/views/app.blade.php` declara `/favicon.ico` (32 × 32), `/favicon.svg`
  y `/apple-touch-icon.png` (166 × 166). Los tres son el **logo de Laravel**
  (`#FF2D20`). No hay manifiesto web ni `theme-color`.
- `resources/js/Components/AppShell/AppLogo.vue` es el logo de referencia (cuadro
  `h-7 w-7 rounded-lg bg-primary-container` con el SVG en `text-on-primary`
  de `h-4 w-4`). Se usa en `AppShellSidebar.vue`, `LandingHeader.vue` y
  `LandingFooter.vue`.
- `resources/js/Components/Auth/AuthCard.vue` tiene una **copia en línea** del
  símbolo en otra variante (`h-12 w-12 bg-primary-container/10
  text-primary-container`).
- `resources/js/Components/Auth/AuthHeader.vue` muestra un punto pulsante y
  "{name} Cloud OS", sin logo.
- Correos: `ProjectInvitationMail` (`mail/project-invitation.blade.php`,
  `<x-mail::message>`) y `ResetPasswordNotification` (`MailMessage`) usan el
  layout markdown del framework. Su `header.blade.php` imprime el slot
  (`config('app.name')`) y solo muestra un `<img>` si el nombre es "Laravel".
  No hay vistas de correo publicadas en `resources/views/vendor/mail`.
- Herramientas disponibles en la máquina de desarrollo: Google Chrome
  (headless) y Python 3 con Pillow 12. No hay ImageMagick, rsvg ni Inkscape.

## Decisiones

### D1. Geometría del logo maestro

- **Decision**: un único SVG maestro de 28 × 28 unidades (las mismas
  proporciones que `AppLogo`): `<rect width="28" height="28" rx="14"
  fill="#5B5BD6"/>` y el símbolo blanco (`#FFFFFF`) en
  `<g transform="translate(6 6) scale(0.6667)">`, con los paths del usuario
  sin cambios (24 → 16 unidades, centrado).
- Para el **apple-touch-icon** y los íconos del manifiesto se usa una
  variante **full-bleed** (cuadro índigo sin esquinas redondeadas que ocupa
  todo el lienzo, con el símbolo al mismo 16/28 de proporción). iOS y Android
  aplican su propia máscara, y las transparencias en iOS se rellenan de
  negro (FR-002).
- **Rationale**: es fiel al SVG proporcionado, y un solo maestro evita
  desviaciones entre formatos.

- **Ajuste durante la implementación (T019)**: en la app, `rounded-lg` vale
  `1rem` (16 px) y en un cuadro de 28 px el navegador lo limita a la mitad
  del lado, así que `AppLogo` se ve como un **círculo** (igual con `lg`:
  `rounded-xl` = 24 px sobre 48 px). La variante redondeada usa `rx="14"`
  (círculo) para coincidir con lo que se ve en la UI (SC-004).

### D2. Generación de PNG e ICO

- **Decision**: un script único `scripts/brand/generate-icons.py` (Python 3,
  ejecutado con `python3 -I`) que:
  1. escribe `public/favicon.svg` (maestro redondeado) y SVG temporales
     full-bleed;
  2. renderiza cada tamaño con **Chrome headless**
     (`--screenshot --window-size=N,N --default-background-color=00000000`)
     a PNG;
  3. con **Pillow** genera `public/favicon.ico` (16, 32 y 48 px, con el
     maestro redondeado y fondo transparente) y verifica las dimensiones.
- Salidas versionadas en el repo: `public/favicon.svg`, `public/favicon.ico`,
  `public/apple-touch-icon.png` (180), `public/icon-192.png`,
  `public/icon-512.png` y `public/images/eunox-mail-logo.png` (96 × 96,
  redondeado, mostrado a 32 px para pantallas retina).
- **Rationale**: no agrega dependencias al proyecto (Principio II): el script
  se corre una vez y los binarios se versionan, sin paso de build nuevo.
  Chrome renderiza el SVG con fidelidad exacta (paths con arcos), y Pillow ya
  sabe escribir `.ico` multitamaño.
- **Alternatives considered**: dibujar con Pillow a mano (los arcos de los
  paths se aproximan mal; se rechaza). Usar el paquete npm
  `sharp`/`favicons` (dependencia nueva; se rechaza). Usar un generador web
  (no es reproducible; se rechaza).

### D3. Tamaño pequeño (16 px)

- **Decision**: se usa el mismo maestro. Si al revisar el 16 × 16 el punto
  desaparece, el script admite un `stroke`/escala levemente mayor solo para
  16 px (edge case del spec). Se valida a ojo en quickstart §2.

### D4. Declaraciones en `<head>`

- **Decision**: en `resources/views/app.blade.php`:
  - `<link rel="icon" href="/favicon.ico" sizes="32x32">` (en lugar de
    `sizes="any"`, para que los navegadores modernos prefieran el SVG);
  - `<link rel="icon" href="/favicon.svg" type="image/svg+xml">` (se queda);
  - `<link rel="apple-touch-icon" href="/apple-touch-icon.png">` (se queda,
    archivo nuevo de 180 px);
  - **nuevo** `<link rel="manifest" href="/site.webmanifest">`;
  - **nuevo** `<meta name="theme-color" content="#5B5BD6">`.
- `public/site.webmanifest`: `name`/`short_name` "Eunox", `icons` 192 y 512
  (`purpose: "any maskable"`; el full-bleed con el símbolo al 57 % cabe en la
  zona segura del 80 %), `theme_color` y `background_color` "#5B5BD6",
  `display: "browser"`. No se busca una PWA instalable completa (YAGNI).
- **Caché**: los nombres `favicon.ico` y `favicon.svg` se conservan
  (convención). Los navegadores refrescan el favicon con el tiempo, y para
  verificar se usa una ventana privada (edge case).

### D5. Fuente única del logo en la UI (FR-008)

- **Decision**: extender `AppLogo.vue` con una prop `size?: 'sm' | 'lg'`
  (default `sm` = `h-7 w-7 rounded-lg` con símbolo `h-4 w-4`; `lg` =
  `h-12 w-12 rounded-xl` con símbolo `h-7 w-7`, que mantiene la proporción
  16/28). `AuthCard.vue` reemplaza su copia en línea por
  `<AppLogo size="lg" />`. `AuthHeader.vue` reemplaza el punto pulsante y
  "Cloud OS" por un `<Link :href="home()">` con `<AppLogo />` y
  `{{ $page.props.name }}`. "Conexión segura SSL" se queda.
- **Rationale**: un solo componente, y cambiar el logo en el futuro toca un
  archivo (más el script de íconos).
- **Alternatives considered**: un componente nuevo `BrandLogo` en
  `Components/Brand/` (mover `AppLogo` obligaría a tocar 3 imports sin
  beneficio; se rechaza).

### D6. Logo en correos (FR-009)

- **Decision**: publicar **solo** el componente de encabezado:
  `resources/views/vendor/mail/html/header.blade.php`. Laravel resuelve los
  componentes `mail::` buscando primero en `resources/views/vendor/mail` y
  cae al framework para el resto. El encabezado muestra
  `<img src="{{ asset('images/eunox-mail-logo.png') }}" width="32" height="32" alt="{{ config('app.name') }}" class="logo">`
  seguido del slot (nombre en texto), alineado en línea y sin depender de
  CSS externo (estilos inline para Outlook).
- **Rationale**: un solo archivo cubre invitación y restablecer contraseña.
  El PNG es compatible con Gmail y Outlook (no muestran SVG). `asset()` usa
  `APP_URL` (`https://www.eunox.app` en producción) y el `alt` mantiene la
  marca si se bloquean imágenes.
- **Alternatives considered**: publicar todas las vistas con
  `vendor:publish --tag=laravel-mail` (copia unos 20 archivos que hay que
  mantener; se rechaza). Usar un logo embebido como CID adjunto (más
  complejo y mal soportado en vistas previas; se rechaza).

### D7. Pruebas

- `tests/Feature/Branding/BrandIconsTest.php` (Pest):
  - `public/favicon.svg` contiene `#5B5BD6` y **no** contiene `#FF2D20`
    (FR-005);
  - `public/favicon.ico`, `public/apple-touch-icon.png`, `public/icon-192.png`,
    `public/icon-512.png` y `public/images/eunox-mail-logo.png` existen con
    las dimensiones esperadas (`getimagesize`: 180, 192, 512 y 96);
  - `public/site.webmanifest` es JSON válido con `name` "Eunox" y los íconos
    declarados existen;
  - `GET /login` muestra `rel="manifest"`, `name="theme-color"` y
    `/favicon.svg`.
- `tests/Feature/Mail/MailBrandHeaderTest.php`: el HTML renderizado de
  `ProjectInvitationMail` y de `ResetPasswordNotification` contiene
  `images/eunox-mail-logo.png` y `alt="Eunox"`.
- La guardia `NoLegacyBrandInUiTest` ya cubre FR-010 en Vue y vistas.
