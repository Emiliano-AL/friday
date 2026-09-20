# Research: Autenticación con correo/contraseña + Google

**Feature**: 001-breeze-google-login | **Date**: 2026-09-19

## R-1: Herramienta de auth tradicional — Breeze vs. alternativas

**Contexto**: La constitución v1.1.0 fija "Laravel Breeze para el login
tradicional". El proyecto es `laravel/blank-vue-starter-kit` (Laravel 13.17,
Inertia 3, Vue 3.5 + TypeScript, Tailwind 4, Pest 5) sin scaffolding de auth.

**Findings**:

- La documentación oficial de Laravel 13 ya no documenta Breeze: los starter
  kits actuales son plantillas completas basadas en Fortify
  (react/svelte/vue/livewire), no paquetes instalables en una app existente.
  ([starter-kits](https://laravel.com/docs/13.x/starter-kits))
- `laravel/breeze` sigue publicándose: v2.4.2 (2026-05-14) declara soporte
  `illuminate/* ^11.0|^12.0|^13.0` → compatible con Laravel 13. Verificado en
  Packagist (repo.packagist.org/p2/laravel/breeze.json).
- Breeze es el único paquete oficial instalable sobre una app existente
  (`php artisan breeze:install vue --pest`); los starter kits nuevos exigen
  crear la aplicación desde cero con `laravel new`, inaplicable aquí.

**Decision**: Instalar `laravel/breeze` ^2.4 con stack `vue` y flag `--pest`,
cumpliendo la constitución literalmente.

**Rationale**: Es la herramienta first-party que la gobernanza aprueba y la
única vía oficial para scaffold de auth en una app ya creada. Genera
controladores, form requests, rutas y páginas probadas, alineado con los
Principios I (monolito Inertia), II (ecosistema) y V (no reinventar auth).

**Alternatives considered**:

- _Starter kit Vue nuevo (Fortify)_: rechazado — es una aplicación plantilla,
  no se instala sobre el repo existente.
- _Auth manual sobre Fortify/sanctum_: rechazado — reinventa flujos que Breeze
  ya entrega testeados (registro, reset, throttle, sesiones).

**Riesgo y mitigación**: Breeze publica stubs Vue en JavaScript y asume el
vite clásico (`laravel-vite-plugin` + `vite`). Este proyecto usa `vite-plus`
(`vp dev/build`), TypeScript estricto (`vue-tsc` en CI) y Tailwind 4.
Mitigación planificada: (1) conservar la tooling existente (`vp`, `app.ts`,
`vite.config` del starter kit); (2) no sobreescribir el entrypoint del
frontend; (3) convertir los stubs publicados a `<script setup lang="ts">`;
(4) verificar `npm run check`, `npm run types:check` y `composer ci:check`
tras la instalación; (5) eliminar vistas/rutas de features no usadas
(verificación de correo, confirmación de contraseña).

## R-2: Login con Google — Socialite y vinculación de cuentas

**Contexto**: FR-005/FR-006/FR-012: Google como único proveedor OAuth, creación
automática de cuenta y vinculación cuando el correo verificado por Google
coincide con una cuenta local.

**Findings** (docs oficiales
[Socialite](https://laravel.com/docs/13.x/socialite)):

- Instalación: `composer require laravel/socialite`; clave `google` en
  `config/services.php` (`client_id`, `client_secret`, `redirect` — acepta
  ruta relativa, se resuelve a URL absoluta).
- Dos rutas: redirect (`Socialite::driver('google')->redirect()`) y callback
  (`->user()` devuelve `getId()`, `getName()`, `getEmail()`, `getAvatar()`).
- Scopes por defecto de google: `openid`, `email`, `profile` → el correo
  devuelto está verificado por Google (política de Google para emails
  devueltos vía scope `email`).
- Testing sin red: `Socialite::fake('google')` y
  `Socialite\Two\User::fake(['id' => ..., 'name' => ..., 'email' => ...])`.

**Decision**: `GoogleAuthController` con métodos `redirect()` y `callback()`:

1. Buscar usuario por `(oauth_provider=google, oauth_id)`.
2. Si no existe, buscar por `email`; si hay coincidencia, vincular
   (oauth_provider, oauth_id, avatar si es null) y fijar `email_verified_at`
   si es null. El correo verificado por Google autoriza la vinculación sin
   pedir contraseña.
3. Si no hay coincidencia, crear la cuenta (name con fallback al prefijo del
   correo si Google no devuelve nombre, email, avatar, oauth_*, email_verified_at=ahora).
4. `Auth::login($user)` + regeneración de sesión + `redirect()->intended('/')`.
5. Excepciones de estado inválido/comunicación (`InvalidStateException`,
   `ClientException`): redirect a login con error de sesión genérico, sin
   crear cuenta.

**Rationale**: la búsqueda por identidad del proveedor primero y por correo
después es el patrón documentado por Laravel (updateOrCreate) extendido con la
regla de vinculación; el correo único global (SC-006) garantiza que no hay
duplicados. Nombre fallback: Google puede devolver `name` null para cuentas
sin perfil completo.

**Alternatives considered**:

- _Tabla `social_accounts` separada_: rechazada — un único proveedor fijo por
  gobernanza (YAGNI); columnas en `users` son suficientes y más simples.
- _Rechazar vinculación y mostrar error_: rechazada — peor UX, contradice
  FR-006 y el escenario de aceptación US2.3.

## R-3: Rate limiting

**Findings**: Breeze genera `LoginRequest` con límite de 5 intentos por minuto
(`RateLimiter::tooManyAttempts`, clave email+IP) — satisface FR-007 para el
login local. Fortify (no usado) documenta el patrón equivalente
(`Limit::perMinute(5)->by($request->email.$request->ip())`).

**Decision**: mantener el throttle de Breeze sin cambios para correo/contraseña.
Para el callback de Google añadir middleware `throttle` dedicado (p. ej.
10 peticiones/minuto por IP) vía `RateLimiter::for('google-oauth-callback')`,
registrado en `AppServiceProvider`, porque el callback hace una llamada
saliente al token endpoint de Google.

## R-4: Verificación de correo y sesiones

**Findings**: La spec asume (MVP) que las cuentas locales no requieren
verificación; `email_verified_at` solo se fija para cuentas creadas/vinculadas
vía Google. La tabla `sessions` existe (driver database por defecto del
skeleton). Breeze registra su grupo de rutas en `routes/auth.php` con
middleware `guest`/`auth`; las redirecciones post-auth las fija cada
controlador (`redirect()->intended('/')`), ya que los skeletons Laravel 11+
ya no incluyen `RouteServiceProvider::HOME`.

**Decision**: no registrar rutas de verificación ni de confirmación de
contraseña. El destino post-autenticación es `/` (home autenticado:
`AppHome.vue` placeholder, ajuste posterior cuando exista el módulo de
proyectos). `web.php` define `/`
con middleware `auth` para la app y sigue ofreciendo `Welcome` solo a invitados
(para cumplir FR-010/FR-011 con la home del dominio).

## Resolución de NEEDS CLARIFICATION

El Technical Context no requirió marcadores: versiones reales verificadas en
`composer.json`/`package.json`, compatibilidad Breeze↔Laravel 13 verificada en
Packagist, patrones OAuth y de testing contrastados con la documentación
oficial 13.x. Todos los unknowns quedan resueltos en este documento.
