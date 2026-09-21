---
description: 'Task list for 001-breeze-google-login'
---

# Tasks: Autenticación con correo/contraseña + Google

**Input**: Design documents from `/specs/001-breeze-google-login/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/web-auth-surface.md, quickstart.md

**Tests**: INCLUIDOS — obligatorios por Principio III de la constitución (Test-First con Pest, NON-NEGOTIABLE). Los tests de cada historia deben escribirse PRIMERO y FALLAR antes de la implementación.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: User story label (US1–US4, según spec.md)
- Include exact file paths in descriptions

## Path Conventions

Single project (Laravel monolith). Rutas reales según `plan.md` — `app/`, `routes/`, `resources/js/`, `tests/Feature/`, `config/`, `database/migrations/`.

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Instalación de paquetes aprobados (constitución v1.1.0) y reconciliación con el tooling del starter kit

- [x] T001 Require auth packages: `composer require --dev laravel/breeze ^2.4 && composer require laravel/socialite ^5` (compatibilidad Breeze↔Laravel 13 verificada en research.md R-1)
- [x] T002 Run `php artisan breeze:install vue --pest` and reconcile generated assets with existing tooling (keep `resources/js/app.ts` entry and vite-plus config in `vite.config.ts`; do not overwrite them; remove any duplicated legacy JS entrypoint Breeze may publish; confirm `npm run build` still works)
- [x] T003 [P] Add Google OAuth env vars to `.env.example`: `GOOGLE_CLIENT_ID=`, `GOOGLE_CLIENT_SECRET=`, `GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"`

**Checkpoint**: Paquetes instalados, scaffolding Breeze presente y build de frontend funcional

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Infraestructura de datos y configuración que TODAS las historias necesitan

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T004 Create migration `database/migrations/2026_09_19_000000_add_oauth_to_users_table.php`: make `users.password` nullable; add `avatar` string nullable; add `oauth_provider` string nullable + index; add `oauth_id` string nullable; add unique composite `[oauth_provider, oauth_id]` (constraints verbatim de data-model.md)
- [x] T005 [P] Create backed enum `app/Enums/OAuthProvider.php` (string): single case `Google = 'google'` (Principio IV; FR-012 fija Google como único proveedor)
- [x] T006 Update `app/Models/User.php`: add `avatar`, `oauth_provider`, `oauth_id` to `#[Fillable]`; cast `oauth_provider => OAuthProvider::class`; keep `password => hashed` cast; update PHPDoc `@property` (password `string|null`, new nullable fields)
- [x] T007 [P] Add `google` entry to `config/services.php` (client_id/client_secret/redirect from env, redirect may use relative `/auth/google/callback`); verify no `github` entry exists (FR-012)
- [x] T008 Register `google-oauth-callback` rate limiter in `app/Providers/AppServiceProvider.php`: `Limit::perMinute(10)->by($request->ip())` (research.md R-3). Leave `app/Http/Requests/Auth/LoginRequest.php` untouched here — its throttle is owned by T015
- [x] T009 Wire auth routes in `routes/web.php`: `require __DIR__.'/auth.php';` and define `/` behind `auth` middleware rendering `AppHome` for authenticated users while guests get the public Welcome page (FR-010). Post-auth redirects are set per controller with `redirect()->intended('/')` (T014, T015, T020) — skeletons Laravel 11+ have no `RouteServiceProvider::HOME` to configure
- [x] T010 [P] Create `resources/js/pages/AppHome.vue`: authenticated landing placeholder for the MVP (single root element, `<script setup lang="ts">`, Tailwind)
- [x] T011 Convert Breeze layouts `resources/js/layouts/GuestLayout.vue` and `resources/js/layouts/AppLayout.vue` to `<script setup lang="ts">`; verify `npm run types:check` (`vue-tsc`) passes with the converted files

**Checkpoint**: Foundation ready — base migrada, enum/modelo/config/rutas en su lugar, types:check verde

---

## Phase 3: User Story 1 - Registro e inicio de sesión con correo y contraseña (Priority: P1) 🎯 MVP

**Goal**: Un visitante puede registrarse con nombre/correo/contraseña y entrar después con esas credenciales; errores genéricos y anti fuerza bruta activos (US1, FR-002/003/004/007).

**Independent Test**: quickstart S1–S3 + S5/S6 (filtrados a registro/login/throttle): `php artisan test --compact --filter=RegistrationTest` y `--filter=AuthenticationTest`; verificación manual en navegador.

### Tests for User Story 1 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T012 [P] [US1] Create `tests/Feature/Auth/RegistrationTest.php` (Pest): valid registration creates user (password stored hashed, verifiable via `Hash::check`) and authenticates; duplicate email rejected with error and no second user; password of 7 chars rejected; mismatched `password_confirmation` rejected
- [x] T013 [P] [US1] Create `tests/Feature/Auth/AuthenticationTest.php` (Pest) — login portion: valid credentials authenticate and redirect to `/`; wrong password returns generic error message, user stays guest; after 5 failed attempts the 6th attempt is blocked by throttle even with the correct password (FR-007)

### Implementation for User Story 1

- [x] T014 [US1] Adapt `app/Http/Controllers/Auth/RegisteredUserController.php`: validate `name` required string max:255, `email` required email max:255 unique:users, `password` required min:8 confirmed; create via `Hash::make`; `Auth::login`; session regenerate; redirect to `/` (constraints verbatim de data-model.md)
- [x] T015 [US1] Adapt `app/Http/Requests/Auth/LoginRequest.php` + `app/Http/Controllers/Auth/AuthenticatedSessionController.php`: authenticate by email+password; keep Breeze RateLimiter (5/min, key email+IP) with generic failure message (FR-004); on success session regenerate + `redirect()->intended('/')` (FR-010)
- [x] T016 [P] [US1] Convert `resources/js/pages/auth/Register.vue` to `<script setup lang="ts">`: fields name/email/password/password_confirmation, server `errors` prop rendering, submit to register route via Wayfinder
- [x] T017 [P] [US1] Convert `resources/js/pages/auth/Login.vue` to `<script setup lang="ts">`: fields email/password/remember, server `errors` prop rendering, submit to login route via Wayfinder
- [x] T018 [US1] Verify US1: run quickstart S1–S3 manually (register flow incl. weak-password and duplicate-email errors, generic wrong-password message, throttle block after 5 attempts) and confirm T012/T013 suites are green

**Checkpoint**: User Story 1 fully functional and testable independently

---

## Phase 4: User Story 2 - Inicio de sesión con Google (Priority: P1) 🎯 MVP

**Goal**: Un visitante entra con Google; primera vez crea cuenta, siguientes veces reutilizan la misma; correo coincidente con cuenta local vincula ambos métodos (US2, FR-005/006/012).

**Independent Test**: quickstart S8–S9: `php artisan test --compact --filter=GoogleAuthenticationTest` (flujo completo con `Socialite::fake`, sin red).

### Tests for User Story 2 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T019 [P] [US2] Create `tests/Feature/Auth/GoogleAuthenticationTest.php` (Pest, `Socialite::fake('google')` + `Socialite\Two\User::fake`): redirect route returns provider redirect; callback with new Google account creates user with `oauth_provider='google'`, `oauth_id` set, `email_verified_at` filled and authenticates; second callback reuses same `users.id` (no duplicate); callback with email of an existing local account links to that same `users.id` filling `oauth_*` and preserving password; `InvalidStateException` path redirects to `/login` with generic error and creates no user; intended URL preserved after callback

### Implementation for User Story 2

- [x] T020 [US2] Create `app/Http/Controllers/Auth/GoogleAuthController.php`: `redirect()` returns `Socialite::driver('google')->redirect()`; `callback()` resolves user by `(oauth_provider, oauth_id)`, else links by unique `email` (fill `oauth_*`, `avatar` if null, `email_verified_at` if null — email verified by Google), else creates (name fallback to email prefix, `email_verified_at = now()`); `Auth::login` + session regenerate + `redirect()->intended('/')`; catch `InvalidStateException`/Guzzle `ClientException` → redirect to `login` with generic flash error, no account created (logic per research.md R-2)
- [x] T021 [US2] Add Google routes to `routes/auth.php`: `GET /auth/google/redirect` name `google.redirect` (guest middleware); `GET /auth/google/callback` name `google.callback` (guest + `throttle:google-oauth-callback`) per contracts/web-auth-surface.md
- [x] T022 [US2] Add "Continuar con Google" button to `resources/js/pages/auth/Login.vue` linking to the `google.redirect` route via Wayfinder (FR-001: same screen as email/password form)
- [x] T023 [US2] Verify US2: run quickstart S8–S9 suites green; manual check that no provider other than Google appears anywhere in UI or config (FR-012)

**Checkpoint**: User Stories 1 AND 2 both work independently — MVP completo (ambas P1)

---

## Phase 5: User Story 3 - Recuperación de contraseña olvidada (Priority: P2)

**Goal**: Un usuario local que olvidó su contraseña recibe un enlace de un solo uso por correo y restablece su contraseña (US3, FR-008).

**Independent Test**: quickstart S7: `php artisan test --compact --filter=PasswordResetTest` con `Notification::fake`.

### Tests for User Story 3 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T024 [P] [US3] Create `tests/Feature/Auth/PasswordResetTest.php` (Pest, `Notification::fake`): requesting a link for a registered email sends a reset notification; valid token+email+new password updates the password (login with new password works, old one fails); reused or invalid token is rejected and the current password remains unchanged

### Implementation for User Story 3

- [x] T025 [US3] Adapt `app/Http/Controllers/Auth/PasswordResetLinkController.php`: validate email format; send reset link via Password broker; always return neutral status (no account enumeration, per contract)
- [x] T026 [US3] Adapt `app/Http/Controllers/Auth/NewPasswordController.php`: validate token, email, `password` required min:8 confirmed; `Password::reset` broker call; success → redirect to login with status message; invalid/expired token → back to form with errors, password unchanged
- [x] T027 [P] [US3] Convert `resources/js/pages/auth/ForgotPassword.vue` to `<script setup lang="ts">`: email field, neutral status message display, error rendering
- [x] T028 [P] [US3] Convert `resources/js/pages/auth/ResetPassword.vue` to `<script setup lang="ts">`: email prefilled (readonly from query), token hidden field, password + password_confirmation, status/error rendering
- [x] T029 [US3] Verify US3: quickstart S7 green + manual pass through forgot/reset flow with `MAIL_MAILER=log`

**Checkpoint**: User Story 3 independently functional (registro/login Google intactos)

---

## Phase 6: User Story 4 - Cierre de sesión y control de acceso (Priority: P2)

**Goal**: Cerrar sesión termina la sesión; páginas internas exigen autenticación con retorno a destino; usuarios autenticados no ven la página de acceso (US4, FR-009/010/011).

**Independent Test**: quickstart S4: guest en `/` → redirect a `/login`; tras login vuelve a `/`; autenticado en `/login` → `/`; logout cierra sesión.

### Tests for User Story 4 ⚠️

- [x] T030 [P] [US4] Extend `tests/Feature/Auth/AuthenticationTest.php` — access-control portion: `POST /logout` invalidates session and redirects to `/login`; guest GET `/` redirects to `/login` storing intended URL; authenticated user GET `/login` or `/register` redirects to `/`; after login from an intended redirect, user lands on originally requested page

### Implementation for User Story 4

- [x] T031 [US4] Verify/adapt logout route in `routes/auth.php` + Breeze `AuthenticatedSessionController::destroy`: `auth` middleware, `Auth::guard('web')->logout()`, session invalidate + token regeneration, redirect to `/login` (contract)
- [x] T032 [US4] Verify FR-010/011 wiring end-to-end: `auth` middleware group on `/` (from T009) stores intended URL for guests; `guest` middleware on auth pages redirects authenticated users to `/`; adjust `resources/js/pages/Welcome.vue` link to login if needed
- [x] T033 [US4] Verify US4: quickstart S4 manual + T030 suite green

**Checkpoint**: All four user stories independently functional

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Limpieza YAGNI, documentación y validación completa

- [x] T034 [P] Remove unused Breeze scaffolding (YAGNI): delete unregistered `resources/js/pages/auth/VerifyEmail.vue` and `resources/js/pages/auth/ConfirmPassword.vue` stubs and ensure no verification/confirm routes are registered in `routes/auth.php`
- [x] T035 [P] Update `README.md` sections 1 (Stack Tecnológico) and 2.1 (Autenticación): auth = Laravel Breeze (correo/contraseña) + Socialite con Google únicamente
- [x] T036 Run full quality gates: `composer ci:check` (`pint --test`, `phpstan analyse`, `npm run check`, `npm run types:check`, full `php artisan test`) — all must pass
- [x] T037 Run quickstart.md validation scenarios S1–S10 end-to-end (manual + automated) and confirm success criteria SC-001–SC-007 from spec.md
- [x] T038 Security pass: confirm session regeneration on login/callback/logout; generic auth errors (no account enumeration); login + Google callback throttles active; `config/services.php` has only `google`; no plaintext password anywhere (SC-004)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — empieza de inmediato (T001→T002 secuencial; T003 en paralelo)
- **Foundational (Phase 2)**: Depends on Setup — BLOCKS all user stories (T004 antes que T006 por dependencia de la migración; T005/T007/T010 en paralelo al resto)
- **User Stories (Phase 3+)**: All depend on Foundational; then proceed in priority order US1 → US2 → US3 → US4 (US1 y US2 son P1 y conforman el MVP)
- **Polish (Phase 7)**: Depends on all four stories complete

### User Story Dependencies

- **US1 (P1)**: Foundational only — no dependencies on other stories
- **US2 (P1)**: Foundational only; integra con US1 en el botón de Google en `Login.vue` (T022), pero su verificación (T019/T023) es independiente de US1
- **US3 (P2)**: Foundational only; reutiliza el layout Guest de Fase 2
- **US4 (P2)**: Foundational + verificación de rutas creadas en US1/US2 (logout viene con el scaffold; el trabajo real son los redirects)

### Within Each User Story

- Tests (T012/T013, T019, T024, T030) MUST exist and FAIL before implementation tasks start
- Models/migration before controllers; controllers before pages; per story: tests → backend → frontend → verify

### Parallel Opportunities

- T003 ∥ (T001, T002) — distinto archivo (`.env.example`)
- Fase 2: T005, T007, T010 ∥ T004–T009, T011 (distintos archivos; T006 depende de T004/T005)
- Fase 3: T012 ∥ T013 (test files distintos); T016 ∥ T017 (páginas distintas)
- Fase 5: T024 ∥ —; T027 ∥ T028 (páginas distintas)
- US1 y US2 pueden desarrollarse en paralelo tras Fase 2 si hay capacidad (cuidado: T022 toca `Login.vue` creado en T017 — coordinar)

---

## Parallel Example: User Story 1

```bash
# Tests first, juntos:
Task: "Create tests/Feature/Auth/RegistrationTest.php (Pest)"
Task: "Create tests/Feature/Auth/AuthenticationTest.php (Pest, login portion)"

# Luego páginas en paralelo:
Task: "Convert resources/js/pages/auth/Register.vue to <script setup lang=\"ts\">"
Task: "Convert resources/js/pages/auth/Login.vue to <script setup lang=\"ts\">"
```

---

## Implementation Strategy

### MVP First (User Stories 1 + 2)

1. Complete Phase 1 (Setup) + Phase 2 (Foundational)
2. Complete Phase 3 (US1) → validar con quickstart S1–S3, S5, S6
3. Complete Phase 4 (US2) → validar con S8–S9
4. **STOP and VALIDATE**: ambas historias P1 verdes — esto es el MVP de la feature (acceso a Friday habilitado por ambos métodos)
5. Desplegar/demo

### Incremental Delivery

1. Setup + Foundational → foundation ready
2. US1 → test independently → acceso local funcionando
3. US2 → test independently → acceso Google funcionando (MVP completo)
4. US3 → test independently → recuperación de contraseña
5. US4 → test independently → control de acceso cerrado
6. Polish → quality gates + validación completa

### Parallel Team Strategy

Con dos desarrolladores tras la Fase 2:

- Developer A: US1 (T012–T018)
- Developer B: US2 (T019–T023) — coordinar únicamente `Login.vue` (T017/T022)
- Luego US3 y US4 en secuencia o paralelo

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to spec user story (US1–US4)
- Cada historia es independiente y testeable por sí sola (quickstart la respalda)
- Tests MUST fail antes de implementar (Principio III, NON-NEGOTIABLE)
- Commit after each task or logical group
- Formato estricto en todas las tareas: checkbox + ID + [P?] + [Story?] + ruta de archivo

---

## Phase 8: Convergence

**Purpose**: Trabajo restante detectado al cotejar el código contra spec/plan/tasks tras la implementación

- [ ] T039 Configure real Google OAuth credentials in `.env` (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`) from a Google Cloud OAuth client and validate the real consent flow end-to-end (register a Google account in the app and re-enter) per US2/AC1 and SC-002 (partial)
- [ ] T040 Run the manual quickstart validation in a browser per quickstart.md: S1–S2 (register/login flows and error messages), S3 (throttle block), S4 (logout/access control), timing checks for SC-001 (< 2 min registration) and SC-002 (< 30 s Google flow) (partial)
- [ ] T041 Review dead Breeze scaffolding per YAGNI (Principio V): unrouted `resources/js/pages/Welcome.vue` and unused `resources/js/Components/{DangerButton,Modal,SecondaryButton}.vue` — remove them or document why they stay (unrequested)
- [ ] T042 Raise the host `memory_limit` for PHP CLI (or add `--memory-limit` to the `types:check` script) so `phpstan analyse` does not crash on cold runs per the quality gates in T036 (partial)
