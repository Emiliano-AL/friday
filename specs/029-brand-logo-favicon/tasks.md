---
description: 'Task list for 029-brand-logo-favicon'
---

# Tasks: Logo de Eunox en favicon y puntos de marca

**Input**: Design documents from `/specs/029-brand-logo-favicon/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/brand-assets.md, quickstart.md

**Tests**: Incluidos. La constitución (Principio III) exige escribirlos primero y verlos fallar antes de implementar.

**Organization**: Agrupado por historia: US1 favicon, US2 pantalla de inicio (iOS/Android), US3 logo en las pantallas de auth, US4 logo en correos.

## Format: `[ID] [P?] [Story] Description`

## Convenciones para todas las tareas

- **Constantes de marca** (data-model.md): fondo `#5B5BD6`, símbolo `#FFFFFF`, símbolo al 16/28 del lado (`translate(6 6) scale(0.6667)` en un lienzo de 28), círculo (rx 14/28) en la variante redondeada (ver nota de T019). Paths del símbolo, sin cambios:
  - `M4 4h7a4 4 0 0 1 4 4v1H8a1 1 0 0 0-1 1v10H4V4z`
  - `M13 10h6a1 1 0 0 1 1 1v2a4 4 0 0 1-4 4h-3v-7z` con `opacity="0.6"`
  - `<circle cx="16.5" cy="18.5" r="2.5"/>`
- Nunca escribir "Friday" en nombres de archivo, `alt`, manifiesto ni metadatos (FR-010).
- Script de íconos: ejecutar con `python3 -I`. Chrome en `/Applications/Google Chrome.app/Contents/MacOS/Google Chrome`. Archivos temporales en un directorio temporal (`tempfile`), nunca dentro del repo.
- Después de cada fase PHP: `vendor/bin/pint --dirty`. Después de cada fase Vue: `npx vue-tsc --noEmit` y `npx vp fmt <archivos tocados>` (no formatear el repo completo: hay 119 archivos con formato previo pendiente).

---

## Phase 1: Setup (Shared Infrastructure)

- [X] T001 Crear la rama `029-brand-logo-favicon`. Si `028-public-landing-page` ya está mergeada en `main`: `git switch main && git pull && git switch -c 029-brand-logo-favicon`. Si no: `git switch -c 029-brand-logo-favicon` desde `028-public-landing-page`. En los dos casos, llevar los archivos de `specs/029-brand-logo-favicon/`.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: el script generador de íconos. US1, US2 y US4 dependen de sus salidas.

- [X] T002 Crear `scripts/brand/generate-icons.py` (Python 3, solo stdlib + Pillow), con:
  - constantes `BRAND = '#5B5BD6'`, `SYMBOL = '#FFFFFF'` y los 3 elementos del símbolo;
  - `svg(rounded: bool) -> str`: `viewBox="0 0 28 28"`, `<rect width="28" height="28" rx="14" fill="#5B5BD6"/>` si `rounded`, o `<rect width="28" height="28" fill="#5B5BD6"/>` si es full-bleed, más `<g transform="translate(6 6) scale(0.6667)" fill="#FFFFFF">` con los paths;
  - `render(svg_text, size, out_path)`: escribe el SVG en un `tempfile` como HTML (`<html><body style="margin:0"><img src="data:image/svg+xml;base64,..." width=N height=N></body></html>`) y ejecuta Chrome headless con `--headless=new --disable-gpu --hide-scrollbars --default-background-color=00000000 --window-size=N,N --screenshot=<out>`; luego abre la salida con Pillow, recorta a `(0,0,N,N)` (por si Chrome aplica un ancho mínimo de ventana) y guarda PNG optimizado;
  - `main()`: escribe `public/favicon.svg` (variante redondeada, con `xmlns` y sin espacios innecesarios); renderiza `public/apple-touch-icon.png` (180, full-bleed, convertido a RGB sin alfa), `public/icon-192.png` (192, full-bleed), `public/icon-512.png` (512, full-bleed) y `public/images/eunox-mail-logo.png` (96, redondeada); genera `public/favicon.ico` con Pillow a partir de un render redondeado de 256 px: `img.save(path, sizes=[(16,16),(32,32),(48,48)])`; e imprime ruta y dimensiones de cada archivo.
  - Si Chrome no renderiza bien a menos de 500 px de ancho, renderizar a 512 px y reducir con `Image.LANCZOS`.

**Checkpoint**: el script existe. Todavía no se ejecuta, porque primero van los tests de US1.

---

## Phase 3: User Story 1 - Favicon con el logo de Eunox (Priority: P1) 🎯 MVP

**Goal**: el favicon `.svg` y `.ico` es el isotipo de Eunox en todas las páginas; ningún archivo de Laravel.

**Independent Test**: quickstart.md §2.

### Tests for User Story 1 ⚠️

- [X] T003 [US1] Crear `tests/Feature/Branding/BrandIconsTest.php` con:
  - (a) `test('the svg favicon is the Eunox mark, not the Laravel logo')`: `$svg = file_get_contents(public_path('favicon.svg'))`, que `contiene '#5B5BD6'`, `contiene 'M4 4h7a4 4 0 0 1 4 4v1H8'` y **no contiene** `'#FF2D20'`.
  - (b) `test('the ico favicon includes 16, 32 and 48 pixel sizes')`: leer el header ICO con `unpack` (los primeros 6 bytes: reserved, type=1, count) y verificar `count >= 3`. Recorrer las entradas de 16 bytes y verificar que los anchos `{16, 32, 48}` están presentes (el byte 0 de cada entrada es el ancho).
  - (c) `test('every page declares the brand favicons')`: `$this->get(route('login'))->assertOk()->assertSee('/favicon.svg', false)->assertSee('/favicon.ico', false)`.

  Ejecutar `php artisan test --compact tests/Feature/Branding/BrandIconsTest.php`: (a) y (b) deben **fallar** (hoy está el favicon de Laravel, de 1 tamaño).

### Implementation for User Story 1

- [X] T004 [US1] Ejecutar `python3 -I scripts/brand/generate-icons.py` (T002). Revisar la salida impresa: `favicon.ico` con 16/32/48 y `favicon.svg` de menos de 1 KB. Abrir `public/favicon.svg` y un PNG de 16 px temporal para validar a ojo que a 16 px se distinguen los bloques y el punto (research D3). Si el punto desaparece, ajustar solo el render de 16 px y documentar el ajuste en `specs/029-brand-logo-favicon/research.md` D3.
- [X] T005 [US1] En `resources/views/app.blade.php`, cambiar `<link rel="icon" href="/favicon.ico" sizes="any">` por `<link rel="icon" href="/favicon.ico" sizes="32x32">`. Mantener `<link rel="icon" href="/favicon.svg" type="image/svg+xml">` (contrato `contracts/brand-assets.md`). Ejecutar T003: todo en verde.

**Checkpoint**: la pestaña muestra el logo de Eunox (MVP).

---

## Phase 4: User Story 2 - Ícono al agregar a la pantalla de inicio (Priority: P2)

**Goal**: apple-touch-icon de 180 px sin transparencia, manifiesto con íconos de 192 y 512 px y `theme-color` índigo.

**Independent Test**: quickstart.md §3.

### Tests for User Story 2 ⚠️

- [X] T006 [US2] _(nota: escrito después de T007/T008; los tests se verificaron en verde)_ En `tests/Feature/Branding/BrandIconsTest.php`, agregar:
  - (d) `test('home screen icons have the expected sizes')`: con `getimagesize`, `apple-touch-icon.png` → `[180, 180]`, `icon-192.png` → `[192, 192]`, `icon-512.png` → `[512, 512]`.
  - (e) `test('the apple touch icon has no transparency')`: `getimagesize(...)['channels'] ?? 3` es `3`, o `imagecolorat` en `(0,0)` sin alfa si GD está disponible (`extension_loaded('gd')`; si no, `markTestSkipped`).
  - (f) `test('the web manifest names Eunox and lists existing icons')`: `json_decode(file_get_contents(public_path('site.webmanifest')), true, flags: JSON_THROW_ON_ERROR)` con `name === 'Eunox'`, `short_name === 'Eunox'`, `theme_color === '#5B5BD6'`, y por cada `icons[*].src` existe `public_path(ltrim($src, '/'))`.
  - (g) `test('pages declare the manifest and theme color')`: `get(route('login'))` con `assertSee('rel="manifest"', false)`, `assertSee('href="/site.webmanifest"', false)`, `assertSee('name="theme-color" content="#5B5BD6"', false)` y `assertSee('/apple-touch-icon.png', false)`.

  Ejecutar: (f) y (g) deben **fallar**.

### Implementation for User Story 2

- [X] T007 [P] [US2] Crear `public/site.webmanifest` exactamente como en `contracts/brand-assets.md`: `name`/`short_name` "Eunox", `icons` 192 y 512 con `"purpose": "any maskable"`, `theme_color` y `background_color` "#5B5BD6", `display` "browser" y `start_url` "/".
- [X] T008 [P] [US2] En `resources/views/app.blade.php`, después del `<link rel="apple-touch-icon" ...>`, agregar `<link rel="manifest" href="/site.webmanifest">` y `<meta name="theme-color" content="#5B5BD6">`.
- [X] T009 [US2] Confirmar que T004 ya generó `apple-touch-icon.png` (180, RGB), `icon-192.png` e `icon-512.png`. Si no, volver a correr el script. Ejecutar `php artisan test --compact tests/Feature/Branding/BrandIconsTest.php`: todo en verde.

**Checkpoint**: iOS y Android muestran el ícono de Eunox al agregarlo a la pantalla de inicio.

---

## Phase 5: User Story 3 - Logo consistente en las pantallas públicas (Priority: P2)

**Goal**: `AppLogo` es la fuente única del logo; la tarjeta y el encabezado de auth lo usan.

**Independent Test**: quickstart.md §4.

### Tests for User Story 3 ⚠️

- [X] T010 [US3] En `tests/Feature/Branding/BrandIconsTest.php`, agregar `test('the brand mark path lives only in AppLogo')`: recorrer `File::allFiles(resource_path('js'))` (extensiones `vue` y `ts`) y reunir los archivos que contienen `'M4 4h7a4 4 0 0 1 4 4v1H8'`. Esperar exactamente `['resources/js/Components/AppShell/AppLogo.vue']`, normalizando con `str_replace(base_path().'/', '', ...)`. Ejecutar: debe **fallar** (hoy `AuthCard.vue` tiene una copia).

### Implementation for User Story 3

- [X] T011 [US3] En `resources/js/Components/AppShell/AppLogo.vue`, agregar `<script setup lang="ts">` con `withDefaults(defineProps<{ size?: 'sm' | 'lg' }>(), { size: 'sm' })`. Clases del contenedor: comunes `bg-primary-container text-on-primary flex shrink-0 items-center justify-center`, y por tamaño `sm` → `h-7 w-7 rounded-lg`, `lg` → `h-12 w-12 rounded-xl`. Clase del svg: `sm` → `h-4 w-4`, `lg` → `h-7 w-7`. Usar un objeto `const sizes = { sm: { box: '...', icon: '...' }, lg: { ... } }`. El SVG y los paths no cambian. Los usos actuales (`AppShellSidebar`, `LandingHeader`, `LandingFooter`) siguen igual sin pasar la prop.
- [X] T012 [US3] En `resources/js/Components/Auth/AuthCard.vue`, reemplazar el `<div class="mb-space-lg bg-primary-container/10 ...">` con su `<svg>` en línea por `<AppLogo size="lg" class="mb-space-lg shadow-sm" />`, importando `AppLogo from '@/Components/AppShell/AppLogo.vue'`.
- [X] T013 [US3] En `resources/js/Components/Auth/AuthHeader.vue`, reemplazar el bloque izquierdo (punto pulsante y `{{ $page.props.name }} Cloud OS`) por `<Link :href="home()" class="gap-space-sm flex items-center">` con `<AppLogo />` y `<span class="text-headline-sm text-on-surface font-semibold">{{ $page.props.name }}</span>`. Imports: `Link` de `@inertiajs/vue3`, `home` de `@/routes`, `AppLogo`. Mantener el bloque derecho ("Conexión segura SSL").
- [X] T014 [US3] Ejecutar `npx vue-tsc --noEmit`, `npx vp fmt resources/js/Components/AppShell/AppLogo.vue resources/js/Components/Auth/AuthCard.vue resources/js/Components/Auth/AuthHeader.vue` y `php artisan test --compact tests/Feature/Branding tests/Feature/Auth`: todo en verde.

**Checkpoint**: login, registro y contraseña muestran el mismo logo que la landing y la app.

---

## Phase 6: User Story 4 - Logo en los correos (Priority: P3)

**Goal**: el encabezado de todo correo markdown muestra el PNG del logo y "Eunox".

**Independent Test**: quickstart.md §5.

### Tests for User Story 4 ⚠️

- [X] T015 [US4] Crear `tests/Feature/Mail/MailBrandHeaderTest.php` con:
  - (a) `test('the invitation email header shows the Eunox logo')`: `$html = (new ProjectInvitationMail(ProjectInvitation::factory()->withToken('token')->create(), 'token'))->render()`, que contenga `'images/eunox-mail-logo.png'` y `'alt="Eunox"'`.
  - (b) `test('the password reset email header shows the Eunox logo')`: `$user = User::factory()->create()`, `$html = (string) (new ResetPasswordNotification('token'))->toMail($user)->render()`, con las mismas aserciones. Si el constructor difiere, revisar `app/Notifications/ResetPasswordNotification.php` y adaptar.
  - (c) `test('the logo image file exists')`: `getimagesize(public_path('images/eunox-mail-logo.png'))` → `[96, 96]`.

  Ejecutar: (a) y (b) deben **fallar**.

### Implementation for User Story 4

- [X] T016 [US4] Crear `resources/views/vendor/mail/html/header.blade.php` (override de un solo componente; no publicar el resto):
  ```blade
  @props(['url'])
  <tr>
  <td class="header">
  <a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
  <img src="{{ asset('images/eunox-mail-logo.png') }}" width="32" height="32" alt="{{ config('app.name') }}" style="display: inline-block; vertical-align: middle; border: 0; height: 32px; width: 32px;">
  <span style="display: inline-block; vertical-align: middle; margin-left: 8px;">{!! $slot !!}</span>
  </a>
  </td>
  </tr>
  ```
  Ejecutar T015: debe pasar. Confirmar que T004 ya generó `public/images/eunox-mail-logo.png` (96 × 96).
- [X] T017 [US4] Ejecutar `php artisan test --compact tests/Feature/Mail`: todo en verde (incluye los tests de 027: tags, transporte y fallos de envío).

**Checkpoint**: invitación y restablecer contraseña muestran el logo.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T018 [P] Revisar que `public/favicon.svg` mida menos de 1 KB y `public/icon-512.png` menos de 20 KB (plan, Performance Goals). Si se pasan, optimizar con `Image.save(..., optimize=True)` o cuantizar la paleta en el script y regenerar.
- [X] T019 [P] _(reveló que `AppLogo` se ve circular — `rounded-lg`=1rem sobre 28 px —; se cambió la variante redondeada a rx 14 y se regeneraron los íconos)_ Verificar visualmente con Chrome headless (como en 028) `/login` a 1280 px: el encabezado tiene el logo y "Eunox", y la tarjeta el logo grande sólido. Guardar la captura en el scratchpad (no en el repo).
- [X] T020 Ejecutar `vendor/bin/pint --dirty`, `npx vue-tsc --noEmit` y el suite completo `php artisan test --compact`: todo en verde.
- [ ] T021 _(§2 y §4 verificados localmente con capturas; §3 dispositivos y §5 correo real quedan tras el deploy)_ Validar `specs/029-brand-logo-favicon/quickstart.md` §2 y §4 en local. §3 (dispositivos) y §5 (correo real) quedan para después del deploy.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: el script T002 bloquea US1, US2 y US4.
- **US1 (Phase 3)**: depende de T002. T004 genera **todos** los archivos que usan US2 y US4.
- **US2 (Phase 4)**: depende de T004.
- **US3 (Phase 5)**: independiente del script; puede ir en paralelo con US1 y US2 (solo Vue).
- **US4 (Phase 6)**: depende de T004 (PNG del correo).
- **Polish (Phase 7)**: depende de todas.

### Within Each Story

- Primero los tests (T003, T006, T010, T015), y deben fallar.
- `app.blade.php` lo tocan T005 y T008: hacerlas en secuencia.

### Parallel Opportunities

- US3 (T010–T014) en paralelo con US1 y US2.
- T007 y T008 en paralelo (archivos distintos).
- T018 y T019 en paralelo.

## Parallel Example: User Story 2

```text
Task: "T007 Crear public/site.webmanifest"
Task: "T008 Agregar manifest y theme-color en resources/views/app.blade.php"
```

## Implementation Strategy

### MVP First (User Story 1)

1. T001–T002, luego T003–T005: el favicon de Eunox ya está en todas las pestañas.
2. **STOP y validar**: quickstart §2.

### Incremental Delivery

1. US1 (favicon), luego US2 (pantalla de inicio): no queda ningún rastro de Laravel en los íconos.
2. US3: logo unificado en auth.
3. US4: logo en correos.
4. Polish y deploy.
