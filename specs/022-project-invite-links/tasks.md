---
description: 'Task list for 022-project-invite-links'
---

# Tasks: Invitaciones a proyectos por enlace

**Input**: Design documents from `/specs/022-project-invite-links/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/routes.md, quickstart.md

**Tests**: INCLUIDOS. La Constitución III (Test-First con Pest, NON-NEGOTIABLE) exige escribirlos antes de implementar: tests → fallan → implementación → verde. Usar factories y `Mail::fake()`.

**Organization**: por historia de usuario (US1–US3 de spec.md). US4 de la spec original quedó fuera de alcance (Q2=A).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: paralelizable (archivo distinto, sin dependencias pendientes)
- **[Story]**: US1, US2, US3

---

## Phase 1: Setup

- [X] T001 Crear el enum `App\Enums\ProjectInvitationStatus` con `php artisan make:enum ProjectInvitationStatus --no-interaction` en `app/Enums/ProjectInvitationStatus.php`: casos `Pending = 'pending'`, `Accepted = 'accepted'`, `Declined = 'declined'`, `Cancelled = 'cancelled'` (keys TitleCase) y método `label(): string` en español (Pendiente, Aceptada, Rechazada, Cancelada), siguiendo el estilo de `app/Enums/ProjectStatus.php`.
- [X] T002 Crear modelo, migración y factory con `php artisan make:model ProjectInvitation -mf --no-interaction`. Migración en `database/migrations/xxxx_create_project_invitations_table.php` con columnas exactas de `data-model.md`: `project_id` FK → `projects` `cascadeOnDelete`; `invited_by_id` FK → `users` `cascadeOnDelete`; `email` string(255); `token_hash` string(64) **único**; `status` string default `'pending'`; `expires_at` timestamp; `last_sent_at` timestamp; `responded_at` timestamp nullable; `timestamps()`; índice `(project_id, status)`; e índice único parcial vía `DB::statement('CREATE UNIQUE INDEX project_invitations_pending_unique ON project_invitations (project_id, email) WHERE status = \'pending\'')` (y `DROP INDEX` en `down`).
- [X] T003 [P] Registrar el rate limiter `project-invitations` (20 por hora por usuario: `Limit::perHour(20)->by($request->user()?->id ?: $request->ip())`) en `app/Providers/AppServiceProvider.php`, junto a los limiters `google-oauth-*` existentes.

---

## Phase 2: Foundational (bloquea todas las historias)

- [X] T004 Implementar `app/Models/ProjectInvitation.php`: `$fillable`; casts `status => ProjectInvitationStatus::class`, `expires_at`/`last_sent_at`/`responded_at => 'datetime'`; constante `VALID_DAYS = 7`; relaciones `project(): BelongsTo` e `invitedBy(): BelongsTo` (`invited_by_id`); `scopePending(Builder)`; `isExpired(): bool` (`status === Pending && expires_at->isPast()`); `isUsable(): bool` (`Pending && ! isExpired() && project->status === ProjectStatus::Active`); `matches(User $user): bool` (correo sin distinguir mayúsculas); `static findByToken(string $token): ?self` (busca por `hash('sha256', $token)`); `static issue(Project $project, string $email, User $inviter): array{0: self, 1: string}` que normaliza el correo a minúsculas, reutiliza la fila `Pending` existente de ese proyecto+correo o crea una nueva, genera `Str::random(64)`, guarda `token_hash`, `expires_at = now()->addDays(self::VALID_DAYS)`, `last_sent_at = now()`, y devuelve `[$invitation, $plainToken]`. PHPDoc con array shapes.
- [X] T005 [P] Implementar `database/factories/ProjectInvitationFactory.php` con estado por defecto `pending` (email único, `token_hash` de un token aleatorio, `expires_at` +7 días, `last_sent_at` now) y estados `expired()`, `accepted()`, `declined()`, `cancelled()`.
- [X] T006 Añadir a `app/Models/Project.php` las relaciones `invitations(): HasMany` y `pendingInvitations(): HasMany` (filtra `status = pending`, ordena por `last_sent_at` desc).
- [X] T007 Crear el Mailable con `php artisan make:mail ProjectInvitationMail --markdown=mail.project-invitation --no-interaction`: `app/Mail/ProjectInvitationMail.php` recibe `ProjectInvitation $invitation` y `string $token` por constructor promovido; asunto "{invitador} te invitó a {proyecto} en Friday"; vista `resources/views/mail/project-invitation.blade.php` en español con proyecto, quién invita, vigencia de 7 días y botón "Ver invitación" → `route('invitations.show', $token)`.
- [X] T008 Registrar en `routes/web.php` todas las rutas de `contracts/routes.md`: dentro del grupo `auth`: `POST projects/{project}/invitations/{invitation}/resend` (`projects.invitations.resend`, `throttle:project-invitations`), `DELETE projects/{project}/invitations/{invitation}` (`projects.invitations.destroy`), `POST invitations/{token}/accept` (`invitations.accept`), `POST invitations/{token}/decline` (`invitations.decline`); **fuera** del grupo `auth`: `GET invitations/{token}` (`invitations.show`). Luego `php artisan wayfinder:generate` si no corre automáticamente con Vite.

**Checkpoint**: modelo, correo y rutas listos; `php artisan migrate` aplica sin errores.

---

## Phase 3: User Story 1 - Invitar por correo a alguien sin cuenta (Priority: P1) 🎯 MVP

**Goal**: el líder invita un correo sin cuenta; la persona recibe el enlace, crea su cuenta (correo/contraseña o Google) y acepta.

**Independent Test**: invitar un correo no registrado → abrir enlace sin sesión → registrarse (correo precargado) → volver a la invitación → aceptar → aparece como miembro.

### Tests for User Story 1 (escribir primero; deben fallar)

- [X] T009 [P] [US1] Crear `tests/Feature/Projects/ProjectInvitationsTest.php` (`php artisan make:test --pest Projects/ProjectInvitationsTest` — ajustar ruta a `tests/Feature/Projects/`) con tests: (a) el líder agrega un correo sin cuenta → se crea `ProjectInvitation` pendiente con correo en minúsculas y `Mail::assertSent(ProjectInvitationMail::class)` al correo; (b) agregar un correo registrado sigue adjuntando al instante y **no** envía correo (regresión Q1=B); (c) invitar dos veces el mismo correo deja una sola pendiente y reenvía (FR-011); (d) proyecto archivado → error `email` "No puedes invitar en un proyecto archivado o completado."; (e) un no propietario recibe 404; (f) si el envío lanza excepción, la invitación existe y hay flash `error` "No pudimos enviar el correo. Puedes reenviar la invitación."; (g) `GET invitations/{token}` sin sesión renderiza `Invitations/Show` con `state = 'guest'` y datos del proyecto/invitador, y fija la URL intentada; (h) token inexistente, cancelado, usado o vencido → `state = 'unavailable'` con `reason` correcto; (i) usuario con correo coincidente acepta → es miembro, invitación `Accepted` con `responded_at`, redirige a `projects.show`; (j) aceptar en proyecto archivado → no se vuelve miembro.
- [X] T010 [P] [US1] Añadir a `tests/Feature/Auth/RegistrationTest.php`: (a) tras abrir un enlace de invitación sin sesión, `GET register` entrega el prop `email` precargado; (b) `POST register` redirige a la URL intentada (la invitación) en lugar de `home`; (c) sin invitación previa, sigue redirigiendo a `home`.

### Implementation for User Story 1

- [X] T011 [US1] Modificar `app/Http/Controllers/ProjectMemberController.php@store`: quitar `Rule::exists('users', 'email')` y su mensaje; si el proyecto no está `Active` → `back()->withErrors(['email' => 'No puedes invitar en un proyecto archivado o completado.'])`; si existe usuario con ese correo → flujo actual (miembro existente → error actual; si no, `attach`); si no existe → aplicar manualmente el limiter `project-invitations` (`RateLimiter::tooManyAttempts`/`hit`, 429 con mensaje "Demasiadas invitaciones, inténtalo más tarde."), `ProjectInvitation::issue(...)`, `Mail::to($email)->send(new ProjectInvitationMail(...))` dentro de `try/catch (Throwable)`; éxito → `redirect()->route('projects.show', $project)->with('success', 'Invitación enviada a {email}.')`; fallo → mismo redirect `->with('error', 'No pudimos enviar el correo. Puedes reenviar la invitación.')`.
- [X] T012 [US1] Crear `app/Http/Controllers/InvitationResponseController.php` (`php artisan make:controller InvitationResponseController --no-interaction`) con `show(Request, string $token): Response` que resuelve `ProjectInvitation::findByToken`: inexistente → `state 'unavailable', reason 'not_found'`; cancelada/aceptada/rechazada → `reason 'cancelled'|'used'`; vencida → `'expired'`; proyecto no activo → `'inactive_project'`; sin sesión → `redirect()->setIntendedUrl($request->fullUrl())`, `session(['invitation_email' => $invitation->email])`, `state 'guest'`; sesión con correo distinto → `state 'mismatch'` + `currentEmail`; si no → `state 'valid'`. Props según `contracts/routes.md` (`invitation.email`, `project.{title,color,icon}`, `invitedBy.{name,avatar}`, `expiresAt`).
- [X] T013 [US1] Añadir a `InvitationResponseController` el método `accept(Request, string $token): RedirectResponse`: 404 si no existe; 403 si `! matches($request->user())`; si no `isUsable()` → redirect a `invitations.show`; en transacción `project->members()->syncWithoutDetaching($user)` (si no es propietario), `status = Accepted`, `responded_at = now()`; limpiar `session('invitation_email')`; `redirect()->route('projects.show', $project)->with('success', 'Te uniste a {proyecto}.')`.
- [X] T014 [P] [US1] Modificar `app/Http/Controllers/Auth/RegisteredUserController.php`: `create()` pasa `'email' => $request->session()->get('invitation_email')` a `Inertia::render('Auth/Register', ...)`; `store()` termina en `redirect()->intended(route('home', absolute: false))`.
- [X] T015 [P] [US1] Modificar `resources/js/pages/Auth/Register.vue`: `defineProps<{ email?: string | null }>()` y `useForm` inicializa `email: props.email ?? ''`.
- [X] T016 [US1] Crear `resources/js/pages/Invitations/Show.vue` (layout de invitado como `pages/Auth/*`, activar skill `inertia-vue-development`): tile con color/icono del proyecto, "{invitador} te invitó a colaborar en {proyecto}", vigencia; por estado: `guest` → botones "Crear cuenta" (`register`) primario e "Iniciar sesión" (`login`) secundario + nota de usar `{email}`; `valid` → "Aceptar" (POST `invitations.accept` vía Wayfinder) primario y "Rechazar" secundario; `mismatch` → "Esta invitación es para {email} y tu sesión es {currentEmail}" + botón "Cerrar sesión" (POST `logout`); `unavailable` → mensaje por `reason` ("La invitación venció…", "ya no está disponible…", "el proyecto no está activo…", "no encontramos…") y enlace a inicio.
- [X] T017 [US1] Añadir a `resources/js/types/project.ts` el tipo `PendingInvitation { id: number; email: string; sentAt: string; isExpired: boolean }` y `pendingInvitations: PendingInvitation[]` en `ProjectDetail`; en `app/Http/Controllers/ProjectController.php` (`projectPayload`) incluir `pendingInvitations` (solo si `isOwnedBy`, si no `[]`) con `last_sent_at` ISO e `isExpired()`.
- [X] T018 [US1] Modificar `resources/js/Components/Projects/MembersModal.vue`: tras agregar, mostrar el flash de éxito/error de la página; añadir la sección "Invitaciones pendientes ({n})" entre el formulario y "Miembros actuales", listando correo, "Enviada {fecha relativa}" y etiqueta "Vencida" si `isExpired` (acciones en US3). Mantener "Enlace de invitación rápida" y roles como "Próximamente" (FR-013).

**Checkpoint**: T009 y T010 en verde; escenarios 1–3 de `quickstart.md` funcionan, incluido registro con Google desde el enlace.

---

## Phase 4: User Story 2 - Aceptar o rechazar teniendo ya cuenta (Priority: P1)

**Goal**: quien ya tiene cuenta con el correo invitado abre el enlace (iniciando sesión si hace falta) y acepta o rechaza; solo el correo coincidente puede responder.

**Independent Test**: invitar correo no registrado → registrar esa cuenta por la vía normal → abrir enlace → rechazar → no es miembro; con otra cuenta → no puede responder.

### Tests for User Story 2 (escribir primero)

- [X] T019 [P] [US2] Añadir a `tests/Feature/Projects/ProjectInvitationsTest.php`: (a) sesión con correo distinto → `show` con `state 'mismatch'` y `accept`/`decline` responden 403 sin cambiar la invitación (FR-015); (b) `decline` con correo coincidente → `Declined`, `responded_at` fijado, no es miembro, redirige a `home`; (c) aceptar cuando ya es miembro no duplica la fila en `project_user` (SC-004); (d) login desde el enlace (`POST login` con URL intentada) vuelve a la invitación; (e) callback de Google con URL intentada vuelve a la invitación (mockear Socialite como en `GoogleAuthenticationTest.php`).

### Implementation for User Story 2

- [X] T020 [US2] Añadir a `app/Http/Controllers/InvitationResponseController.php` el método `decline(Request, string $token): RedirectResponse` con las mismas guardas que `accept` (404/403/usable), `status = Declined`, `responded_at = now()`, limpiar `session('invitation_email')`, `redirect()->route('home')->with('success', 'Rechazaste la invitación a {proyecto}.')`.
- [X] T021 [US2] Verificar que `resources/js/pages/Invitations/Show.vue` conecta "Rechazar" a `invitations.decline` y que el estado `mismatch` no muestra botones de respuesta.

**Checkpoint**: T019 en verde; escenarios 4–6 de `quickstart.md`.

---

## Phase 5: User Story 3 - Gestionar invitaciones pendientes (Priority: P2)

**Goal**: el líder ve, reenvía y cancela invitaciones pendientes desde el modal.

**Independent Test**: crear invitación → reenviar (nuevo correo, enlace anterior inválido, vigencia renovada) → cancelar (enlace "no disponible", desaparece de la lista).

### Tests for User Story 3 (escribir primero)

- [X] T022 [P] [US3] Añadir a `tests/Feature/Projects/ProjectInvitationsTest.php`: (a) `resend` genera nuevo token (el viejo deja de resolver), renueva `expires_at` y `last_sent_at`, envía correo; (b) `resend` sobre una vencida la reactiva; (c) `destroy` marca `Cancelled` y el enlace muestra `reason 'cancelled'`; (d) no propietario → 404 en ambas; (e) invitación de otro proyecto → 404; (f) proyecto no activo → no permite reenviar; (g) superar 20 envíos/hora → 429; (h) el payload de `Projects/Show` incluye `pendingInvitations` para el propietario y `[]` para miembros.

### Implementation for User Story 3

- [X] T023 [US3] Crear `app/Http/Controllers/ProjectInvitationController.php` (`php artisan make:controller ProjectInvitationController --no-interaction`) con `resend(Request, Project, ProjectInvitation)` y `destroy(Request, Project, ProjectInvitation)`: `abort_unless(isOwnedBy, 404)`, `authorize('manageMembers', $project)`, `abort_unless($invitation->project_id === $project->id && status === Pending, 404)`; `resend` exige proyecto activo, reutiliza `ProjectInvitation::issue(...)` y el mismo `try/catch` de envío que T011; `destroy` → `status = Cancelled`, `responded_at = now()`; ambos redirigen a `projects.show` con flash en español.
- [X] T024 [US3] En `resources/js/Components/Projects/MembersModal.vue`, añadir a cada fila de "Invitaciones pendientes" los botones icono "Reenviar" (POST `projects.invitations.resend` vía Wayfinder) y "Cancelar invitación" (DELETE `projects.invitations.destroy`, con confirmación), con `aria-label`/`title`, estado de carga y `preserveScroll`.

**Checkpoint**: T022 en verde; escenarios 7–8 de `quickstart.md`.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T025 Ejecutar `vendor/bin/pint --dirty --format agent`.
- [X] T026 [P] Ejecutar `npm run types:check` y corregir errores en los archivos `.vue`/`.ts` tocados.
- [X] T027 Ejecutar `php artisan test --compact tests/Feature/Projects tests/Feature/Auth` y corregir regresiones (en especial `ProjectMembersTest`, cuyo caso "correo no registrado → error" debe actualizarse a "crea invitación").
- [ ] T028 Validar en navegador los escenarios 1–10 de `specs/022-project-invite-links/quickstart.md` con `MAIL_MAILER=log` o Herd Mail.
- [X] T029 Pedir al usuario ejecutar la suite completa `php artisan test --compact`.

---

## Dependencies & Execution Order

- **Setup (T001–T003)** → **Foundational (T004–T008)** → historias.
- **US1 (T009–T018)**: MVP; requiere Foundational.
- **US2 (T019–T021)**: depende de US1 (reutiliza `InvitationResponseController` y `Invitations/Show.vue`).
- **US3 (T022–T024)**: depende de Foundational y de T017–T018 (lista del modal); independiente de US2.
- **Polish (T025–T029)**: tras las historias elegidas.

### Within each story

Tests primero (deben fallar) → backend → frontend → checkpoint en verde.

### Parallel Opportunities

- Setup: T003 en paralelo con T001–T002.
- Foundational: T005 en paralelo con T004/T006; T007 tras T004.
- US1: T009 y T010 en paralelo; T014 y T015 en paralelo entre sí y con T012–T013.
- US2 y US3 pueden avanzar en paralelo una vez cerrada US1 (archivos distintos salvo el test compartido: coordinar T019/T022).

```text
# Ejemplo US1 en paralelo:
T009 [US1] tests de invitación en tests/Feature/Projects/ProjectInvitationsTest.php
T010 [US1] tests de registro en tests/Feature/Auth/RegistrationTest.php
# luego:
T014 [US1] RegisteredUserController   |   T015 [US1] Register.vue
```

## Implementation Strategy

### MVP (US1)

1. Setup + Foundational.
2. US1 completo: invitar correo sin cuenta → registrarse → aceptar.
3. Validar escenarios 1–4 de `quickstart.md`. Ya cubre el hueco principal del reporte.

### Entrega incremental

1. MVP (US1) → validar.
2. US2 (rechazar, coincidencia de correo, retorno tras login/Google) → validar.
3. US3 (reenviar/cancelar en el modal) → validar.
4. Polish.
