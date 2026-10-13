---
description: 'Task list for 025-settings-page'
---

# Tasks: Página de Configuración (/settings)

**Input**: Design documents from `/specs/025-settings-page/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/routes.md, quickstart.md

**Tests**: Requeridos (Constitución III, Test-First con Pest). Escribir cada test, verlo fallar y luego implementar.

**Organization**: por historia de usuario (US1 perfil · US2 contraseña · US3 eliminar cuenta).

## Format: `[ID] [P?] [Story] Description`

---

## Phase 1: Setup

- [X] T001 Crear `tests/Feature/Settings/SettingsTest.php` con `php artisan make:test --pest Settings/SettingsTest --no-interaction` (vacío salvo `uses(RefreshDatabase::class)` si la carpeta vecina lo usa; seguir `tests/Feature/Tasks/TaskImportTest.php`)

---

## Phase 2: Foundational (bloquea todas las historias)

- [X] T002 Escribir tests en `tests/Feature/Settings/SettingsTest.php`: invitado en `GET /settings` → redirect a `login`; autenticado → componente `Settings/Index` con props `account` (`name`, `email`, `avatar`, `hasPassword`, `usesGoogle`) y `ownedProjects` (`id`, `title`, `color`) solo de proyectos con `owner_id` = usuario (no los de los que es miembro)
- [X] T003 Crear `app/Http/Controllers/SettingsController.php` (`php artisan make:controller SettingsController --no-interaction`) con `edit(Request $request): Response` que renderiza `Settings/Index` con las props de data-model.md (`hasPassword` = `password !== null`, `usesGoogle` = `oauth_provider !== null`; `ownedProjects` de `Project::query()->where('owner_id', $user->id)->orderBy('title')`, todos los estados)
- [X] T004 Registrar en `routes/web.php` dentro del grupo `auth`: `GET settings` → `edit` (`settings.edit`), `PATCH settings/profile` → `updateProfile` (`settings.profile.update`), `PUT settings/password` → `updatePassword` (`settings.password.update`, `throttle:account-security`), `DELETE settings` → `destroy` (`settings.destroy`, `throttle:account-security`); cambiar el grupo a `Route::middleware(['auth', 'auth.session'])` (research D4)
- [X] T005 [P] Añadir en `app/Providers/AppServiceProvider.php` `RateLimiter::for('account-security', fn (Request $request) => Limit::perMinute(6)->by((string) ($request->user()?->id ?? $request->ip())))` junto a `project-invitations`
- [X] T006 Crear `resources/js/pages/Settings/Index.vue` con `AuthenticatedLayout`, `<Head title="Configuración">`, encabezado de página como `resources/js/pages/Backlog/Index.vue`, y tres secciones vacías con ids `perfil`, `seguridad`, `cuenta`; props tipadas según data-model.md
- [X] T007 [P] Enlazar navegación: en `resources/js/Layouts/AuthenticatedLayout.vue` `footerItems` «Ajustes» → `to: settingsEdit.url()`, `match: '/settings'` (import desde `@/routes/settings`); en `resources/js/Components/AppShell/ProfileMenu.vue` reemplazar los dos `<span>` «Próximamente» por `<Link>` a `settings.edit` («Detalles del perfil» → `#perfil`, «Ajustes de cuenta» → `#seguridad`) con el estilo de ítem activo del menú
- [X] T008 Ejecutar `php artisan test --compact tests/Feature/Settings/SettingsTest.php` (T002 verde)

**Checkpoint**: `/settings` abre desde la barra lateral y el menú de perfil.

---

## Phase 3: User Story 1 — Editar mi perfil (P1) 🎯 MVP

**Goal**: cambiar el nombre; ver el correo como solo lectura.

**Independent Test**: cambiar el nombre y verlo en el menú de perfil sin recargar.

- [X] T009 [US1] Tests en `tests/Feature/Settings/SettingsTest.php`: `PATCH /settings/profile` con `name` válido actualiza y redirige back con flash `success` «Perfil actualizado.»; `name` vacío o > 255 → `assertInvalid('name')` sin cambios; `name` con espacios se guarda recortado; enviar `email` distinto no cambia el correo (research D6)
- [X] T010 [US1] Implementar `updateProfile(Request $request): RedirectResponse` en `app/Http/Controllers/SettingsController.php`: `$request->user()->update($request->validate(['name' => ['required', 'string', 'max:255']]))`; `back()->with('success', 'Perfil actualizado.')`
- [X] T011 [US1] Sección `perfil` en `resources/js/pages/Settings/Index.vue`: avatar/iniciales, `useForm({ name })` con `patch` a `settings.profile.update`, error junto al campo (`InputError`), correo como texto de solo lectura con nota «Para cambiar tu correo, contacta a un administrador.», método de acceso («Correo y contraseña» / «Google» / ambos)
- [X] T012 [US1] Ejecutar `php artisan test --compact tests/Feature/Settings/SettingsTest.php`

**Checkpoint**: US1 funcional y demostrable sola.

---

## Phase 4: User Story 2 — Cambiar o definir mi contraseña (P2)

**Goal**: cambiar la contraseña con la actual, o definirla si la cuenta es de Google; cerrar otras sesiones.

**Independent Test**: cambiar la contraseña y entrar con la nueva; cuenta de Google define una sin pedir la actual.

- [X] T013 [US2] Tests en `tests/Feature/Settings/SettingsTest.php`: con contraseña, `current_password` correcta + `password`/`password_confirmation` válidas → hash actualizado y flash «Contraseña actualizada.»; `current_password` incorrecta → `assertInvalid('current_password')` y hash intacto; confirmación distinta o < 8 caracteres → `assertInvalid('password')`; usuario de Google con `password = null` define contraseña sin `current_password` → flash «Contraseña definida.»; tras 6 intentos en un minuto el 7.º responde 429
- [X] T014 [US2] Implementar `updatePassword(Request $request): RedirectResponse` en `app/Http/Controllers/SettingsController.php`: reglas `current_password` → `$user->password !== null ? ['required', 'current_password'] : ['exclude']`, `password` → `['required', 'string', 'confirmed', Rules\Password::min(8)]`; mensaje `current_password.current_password` = «La contraseña actual no es correcta.»; guardar con `Hash::make`, luego `Auth::logoutOtherDevices($validated['password'])`; flash según si tenía contraseña
- [X] T015 [US2] Sección `seguridad` en `resources/js/pages/Settings/Index.vue`: título «Cambiar contraseña» o «Definir contraseña» según `account.hasPassword`; campo actual solo si `hasPassword`; `useForm` con `put` a `settings.password.update`, `reset()` tras éxito, `autocomplete` `current-password`/`new-password`
- [X] T016 [US2] Ejecutar `php artisan test --compact tests/Feature/Settings/SettingsTest.php`

**Checkpoint**: US1 y US2 funcionan de forma independiente.

---

## Phase 5: User Story 3 — Eliminar mi cuenta (P3)

**Goal**: eliminar la cuenta con confirmación; bloqueado con proyectos propios.

**Independent Test**: cuenta de prueba sin proyectos propios se elimina y vuelve al login.

- [X] T017 [US3] Tests en `tests/Feature/Settings/SettingsTest.php`: con proyectos propios `DELETE /settings` → `assertInvalid` con «Elimina tus proyectos antes de eliminar tu cuenta.» y usuario intacto; sin proyectos y contraseña correcta → usuario eliminado, invitado, redirect a `login`; contraseña incorrecta → inválido; usuario de Google con `confirmation` = su correo (mayúsculas distintas) → eliminado, con otro texto → inválido; se eliminan sus tareas sueltas (`project_id` null) y las de proyectos ajenos quedan con `author_id`/`assignee_id` null
- [X] T018 [US3] Implementar `destroy(Request $request): RedirectResponse` en `app/Http/Controllers/SettingsController.php`: validar `password` (`['required', 'current_password']`) o `confirmation` (`required` + igual a `$user->email` sin distinguir mayúsculas → «La confirmación no coincide.»); si `Project::where('owner_id', $user->id)->exists()` → `ValidationException` en el mismo campo; `DB::transaction` borrando `Task::whereNull('project_id')->where(author_id|assignee_id = user)` y luego `$user->delete()`; `Auth::logout()`, `session()->invalidate()`, `regenerateToken()`, `redirect()->route('login')`
- [X] T019 [US3] Sección `cuenta` (zona de peligro) en `resources/js/pages/Settings/Index.vue`: si `ownedProjects.length > 0`, aviso con la lista de proyectos enlazados (`projects.show`) y botón deshabilitado; si no, botón «Eliminar cuenta» que abre `Modal` de confirmación con campo `password` o `confirmation` (placeholder = correo) y `DangerButton` que hace `delete` a `settings.destroy`
- [X] T020 [US3] Ejecutar `php artisan test --compact tests/Feature/Settings/SettingsTest.php`

**Checkpoint**: las tres historias funcionan.

---

## Phase 6: Polish & Cross-Cutting

- [X] T021 [P] Ejecutar `vendor/bin/pint --dirty --format agent` y `npx prettier --single-quote --write resources/js/pages/Settings/Index.vue resources/js/Layouts/AuthenticatedLayout.vue resources/js/Components/AppShell/ProfileMenu.vue`
- [X] T022 [P] Ejecutar `npm run types:check` (0 errores)
- [X] T023 Ejecutar `php artisan test --compact` completo (comprobar que `auth.session` no rompe tests existentes de auth/Google)
- [ ] T024 Validar en navegador los escenarios 1–4, 6, 7 y 9 de `specs/025-settings-page/quickstart.md` (5 y 8 requieren otra sesión o una cuenta descartable: pedir confirmación antes)

---

## Dependencies & Execution Order

- Setup (T001) → Foundational (T002–T008) → US1 → US2 → US3 → Polish.
- US1, US2 y US3 tocan los mismos dos archivos (`SettingsController.php`, `Settings/Index.vue`), así que se ejecutan en secuencia; cada una es demostrable sola tras Foundational.
- Dentro de cada historia: test → controlador → UI → ejecutar tests.

## Parallel Opportunities

- T005 (rate limiter) y T007 (navegación) en paralelo con T003/T006.
- T021 y T022 en paralelo.

## Implementation Strategy

- **MVP**: Phase 1 + 2 + US1 → la página existe, la navegación funciona y se puede cambiar el nombre.
- Luego US2 (seguridad) y US3 (zona de peligro), validando cada checkpoint.

---

## Phase 7: Convergence

- [X] T025 Add a test in `tests/Feature/Settings/SettingsTest.php` proving other sessions are invalidated: after `PUT /settings/password`, a request carrying a session whose `password_hash_web` is the old hash is logged out (redirect to `login`), while the current session stays authenticated, per FR-008 (partial)
- [X] T026 Add a test in `tests/Feature/Settings/SettingsTest.php` that the 7th `DELETE /settings` within a minute responds 429, per FR-009 (partial)
- [X] T027 Close the profile popover when one of its settings links is clicked (it stays open when already on `/settings`, since the layout does not remount) in `resources/js/Components/AppShell/ProfileMenu.vue` / `resources/js/Layouts/AuthenticatedLayout.vue`, per FR-002 (partial)
