---
description: 'Task list for 026-rebrand-eunox'
---

# Tasks: Rebranding público a Eunox

**Input**: Design documents from `/specs/026-rebrand-eunox/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/branding.md, quickstart.md

**Tests**: Incluidos. La constitución (Principio III, Test-First con Pest) los exige: se escriben primero y deben fallar antes de implementar.

**Organization**: Tareas agrupadas por historia de usuario del spec (US1 UI, US2 correos, US3 preservación del codename).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: Historia a la que pertenece (US1, US2, US3)

## Convenciones para todas las tareas

- **Nombre público**: siempre sale de `config('app.name')` en PHP/Blade y de la prop compartida de Inertia `name` en Vue (`$page.props.name` en templates; `usePage().props.name` en `<script setup>`). Nunca se escribe el literal "Eunox" en componentes ni en plantillas de correo.
- **No renombrar** identificadores internos en minúscula: `friday:open-palette`, `friday:open-task-form`, `friday:kanban-done-collapsed` y `.friday-focus-scope` (FR-008).
- **Atributos de props estáticos**: cuando un componente recibe el texto como atributo estático (p. ej. `title="Iniciar sesión en Friday"`), convertirlo en binding: `` :title="`Iniciar sesión en ${$page.props.name}`" ``.
- Al terminar cada fase PHP, ejecutar `vendor/bin/pint --dirty`.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Dejar `Eunox` como valor del nombre público en todos los entornos.

- [X] T001 Cambiar el default de `'name' => env('APP_NAME', 'Laravel')` a `env('APP_NAME', 'Eunox')` en config/app.php
- [X] T002 [P] Cambiar el default de `'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel'))` a `env('MAIL_FROM_NAME', env('APP_NAME', 'Eunox'))` en config/mail.php
- [X] T003 [P] Cambiar `APP_NAME=Laravel` a `APP_NAME=Eunox` en .env.example, conservando `MAIL_FROM_NAME="${APP_NAME}"` y `VITE_APP_NAME="${APP_NAME}"`
- [X] T004 [P] Cambiar `APP_NAME=Laravel` a `APP_NAME=Eunox` en .env (archivo local, no versionado) y ejecutar `php artisan config:clear`
- [X] T005 [P] Agregar `<env name="APP_NAME" value="Eunox"/>` junto a las demás variables `<env>` en phpunit.xml

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Fallbacks de título y guardia de regresión que comparten todas las historias.

**⚠️ CRITICAL**: Las historias dependen del valor configurado en la Phase 1.

- [X] T006 [P] Cambiar el fallback `import.meta.env.VITE_APP_NAME || 'Laravel'` a `|| 'Eunox'` en resources/js/app.ts (se conserva el formato de título `` `${title} - ${appName}` ``)
- [X] T007 [P] Cambiar `{{ config('app.name', 'Laravel') }}` a `{{ config('app.name', 'Eunox') }}` dentro de `<title>` en resources/views/app.blade.php
- [X] T008 Crear el test de guardia tests/Feature/Branding/NoLegacyBrandInUiTest.php. Debe recorrer recursivamente `resource_path('js')` (`*.vue`, `*.ts`), `resource_path('views')` (`*.blade.php`) y `app_path('Mail')` (`*.php`) y fallar listando archivo:línea de cada coincidencia de la regex sensible a mayúsculas `/\bFriday\b/`. Agregar un segundo caso que verifique que `resources/js/Components/Tasks/Board/TaskBoard.vue` aún contiene `'friday:kanban-done-collapsed'` (FR-008/SC-004). Ejecutarlo: el primer caso debe **fallar** con las ~10 ocurrencias actuales.

**Checkpoint**: Configuración y guardia listas. Se puede empezar con las historias.

---

## Phase 3: User Story 1 - La aplicación se presenta como Eunox (Priority: P1) 🎯 MVP

**Goal**: Toda la UI y los títulos de pestaña muestran "Eunox" (contrato: contracts/branding.md §1–3).

**Independent Test**: Recorrer login, registro, restablecer contraseña, inicio, proyectos, invitación y modal de miembros. No aparece "Friday" ni "Laravel" y las pestañas terminan en "Eunox" (quickstart.md §3, pasos 1–3).

### Tests for User Story 1 ⚠️

- [X] T009 [US1] Crear tests/Feature/Branding/SharedAppNameTest.php con tres casos: (a) un invitado hace `get(route('login'))` y la respuesta Inertia tiene `->where('name', 'Eunox')`; (b) un usuario autenticado (`User::factory()`) visita `route('home')` y obtiene la misma prop `name === 'Eunox'`; (c) con `config(['app.name' => 'Otro'])`, la prop compartida vale `'Otro'` (fuente única, SC-005). Ejecutar: (a) y (b) pasan gracias a la Phase 1, y (c) confirma la fuente única.

### Implementation for User Story 1

- [X] T010 [P] [US1] En resources/js/Components/AppShell/AppShellSidebar.vue (línea ~34), reemplazar `Friday` dentro de `<span class="text-headline-sm text-on-surface">` por `{{ $page.props.name }}`
- [X] T011 [P] [US1] En resources/js/Components/Auth/AuthHeader.vue (línea ~14), reemplazar `Friday Cloud OS` por `{{ $page.props.name }} Cloud OS` (FR-005)
- [X] T012 [P] [US1] En resources/js/Components/AppShell/HomeCanvas.vue (línea ~179), reemplazar `<span>Friday v2.4</span>` por `<span>{{ $page.props.name }} v2.4</span>`
- [X] T013 [P] [US1] En resources/js/pages/Auth/Login.vue (línea ~38), convertir `title="Iniciar sesión en Friday"` en `` :title="`Iniciar sesión en ${$page.props.name}`" ``
- [X] T014 [P] [US1] En resources/js/pages/Auth/Register.vue (línea ~37), convertir `title="Crear cuenta en Friday"` en `` :title="`Crear cuenta en ${$page.props.name}`" ``
- [X] T015 [P] [US1] En resources/js/pages/Auth/ResetPassword.vue (línea ~39), convertir `subtitle="Elige una contraseña segura para tu cuenta de Friday."` en `` :subtitle="`Elige una contraseña segura para tu cuenta de ${$page.props.name}.`" ``
- [X] T016 [P] [US1] En resources/js/pages/Invitations/Show.vue (línea ~195), reemplazar `Ir a Friday` por `Ir a {{ $page.props.name }}`
- [X] T017 [P] [US1] En resources/js/Components/Projects/MembersModal.vue (línea ~175), reemplazar `en Friday,` por `en {{ $page.props.name }},` dentro del texto "Si el correo no tiene cuenta en …". El `usePage<…>()` local tiene un genérico propio: usar `$page.props.name` en el template para no tocar ese tipo.
- [X] T018 [US1] Ejecutar `npm run build` (o `vue-tsc --noEmit` si existe el script `types`/`lint`) y confirmar que no hay errores de tipos. Luego ejecutar `php artisan test --compact tests/Feature/Branding/SharedAppNameTest.php`

**Checkpoint**: US1 completa. La UI dice "Eunox". La guardia T008 aún falla por los archivos de correo (US2).

---

## Phase 4: User Story 2 - Las comunicaciones externas usan Eunox (Priority: P1)

**Goal**: El correo de invitación y los correos por defecto muestran "Eunox" en remitente, asunto y cuerpo (contrato §4).

**Independent Test**: Invitar a un correo nuevo y revisar remitente, asunto y cuerpo en el mailer local; solicitar restablecer contraseña (quickstart.md §3, pasos 4–5).

### Tests for User Story 2 ⚠️

- [X] T019 [US2] Crear tests/Feature/Branding/InvitationMailBrandingTest.php. Construir `ProjectInvitationMail` con `ProjectInvitation::factory()->create()` y un token de prueba, y verificar: (a) `$mail->envelope()->subject` termina en `' en Eunox'` y no contiene `Friday`; (b) `$mail->render()` contiene `Eunox` y no contiene `Friday`; (c) `config('mail.from.name') === 'Eunox'`. Ejecutar: (a) y (b) deben **fallar**.
- [X] T020 [P] [US2] En tests/Feature/Branding/InvitationMailBrandingTest.php, agregar un caso para el correo de restablecer contraseña: `Notification::fake()`, enviar `post(route('password.email'), ['email' => $user->email])` y, con `Notification::assertSentTo($user, ResetPassword::class, fn ($n) => ...)`, verificar que `$n->toMail($user)->render()` contiene `Eunox` y no contiene `Laravel` (encabezado y pie del layout).

### Implementation for User Story 2

- [X] T021 [P] [US2] En app/Mail/ProjectInvitationMail.php (`envelope()`), cambiar el asunto `"... en Friday"` por `"{$this->invitation->invitedBy->name} te invitó a {$this->invitation->project->title} en ".config('app.name')`
- [X] T022 [P] [US2] En resources/views/mail/project-invitation.blade.php (línea 4), reemplazar `en Friday.` por `en {{ config('app.name') }}.` y conservar la firma `{{ config('app.name') }}`
- [X] T023 [US2] Ejecutar `php artisan test --compact tests/Feature/Branding` y `vendor/bin/pint --dirty`. InvitationMailBrandingTest y NoLegacyBrandInUiTest (T008) deben pasar.

**Checkpoint**: US1 y US2 completas. Cero "Friday" visible para personas usuarias y destinatarios.

---

## Phase 5: User Story 3 - El codename Friday se preserva internamente (Priority: P2)

**Goal**: La documentación de ingeniería conserva "Friday" y aclara que la marca pública es "Eunox". Las preferencias de las personas usuarias se mantienen.

**Independent Test**: Revisar README, DESIGN.md y la constitución. Con una preferencia de Kanban guardada, recargar y confirmar que persiste (quickstart.md §4).

### Implementation for User Story 3

- [X] T024 [P] [US3] En README.md, debajo del título `# Friday — Project Management System`, agregar la nota: `> **Friday** es el codename interno del proyecto. El nombre público del producto (UI, correos, marketing) es **Eunox**, configurado vía \`APP_NAME\`.`
- [X] T025 [P] [US3] En DESIGN.md, agregar después del front-matter una nota equivalente: el codename es Friday y la marca visible en la UI es Eunox. No cambiar `name: Friday Productivity Workspace`.
- [X] T026 [P] [US3] En .specify/memory/constitution.md, agregar en el preámbulo (tras `# Friday Constitution`) la línea "Friday es el codename interno; el nombre público del producto es Eunox". Subir la versión a `1.1.1` con `Last Amended: 2026-10-16` y registrar la enmienda PATCH en el Sync Impact Report (comentario HTML al inicio del archivo; crearlo si no existe).
- [X] T027 [US3] Verificar (sin modificar) que `friday:open-palette`, `friday:open-task-form` (resources/js/Layouts/AuthenticatedLayout.vue, HomeCanvas.vue, ActivityDashboard.vue), `friday:kanban-done-collapsed` (resources/js/Components/Tasks/Board/TaskBoard.vue) y `.friday-focus-scope` (resources/css/app.css, layouts) siguen intactos, con `git diff --stat` y `grep -rn "friday[:-]" resources`.

**Checkpoint**: Las tres historias están completas.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T028 [P] Eliminar resources/js/Components/ApplicationLogo.vue (logo de Laravel sin uso). Antes, confirmar con `grep -rn "ApplicationLogo" resources/js` que no hay importaciones (research R7).
- [X] T029 Ejecutar la suite completa `php artisan test --compact` (SC-006) y `vendor/bin/pint --dirty`
- [X] T030 Ejecutar el recorrido manual de specs/026-rebrand-eunox/quickstart.md (§2–§4) con `npm run build` y `php artisan config:clear` previos. Confirmar que las pestañas terminan en "- Eunox".
- [X] T031 Registrar como pendiente (en la descripción del PR) el reemplazo de `public/favicon.svg`, `public/favicon.ico` y `public/apple-touch-icon.png`, que hoy son el logo de Laravel, cuando exista el arte de Eunox. Incluir la nota de despliegue: fijar `APP_NAME=Eunox` en el `.env` de producción y recompilar assets.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de Phase 1 (T005, para que los tests vean `Eunox`).
- **US1 (Phase 3)** y **US2 (Phase 4)**: dependen de Phase 2. Son independientes entre sí y se pueden hacer en paralelo.
- **US3 (Phase 5)**: solo documentación. Puede ir en paralelo con US1 o US2 tras la Phase 1.
- **Polish (Phase 6)**: después de todas las historias.

### Within Each Story

- Primero el test (T009, T019/T020), que debe fallar donde aplique. Después la implementación y luego volver a ejecutar los tests.
- La guardia T008 queda en verde solo cuando US1 **y** US2 están completas.

### Parallel Opportunities

- Phase 1: T002–T005 en paralelo tras T001 (archivos distintos).
- Phase 2: T006 y T007 en paralelo.
- US1: T010–T017 son 8 archivos Vue distintos, todos [P].
- US2: T021 y T022 en paralelo, y T020 en paralelo con la implementación.
- US3: T024–T026 en paralelo.

## Parallel Example: User Story 1

```bash
# Tras T009, lanzar juntos:
Task: "T010 AppShellSidebar.vue → $page.props.name"
Task: "T011 AuthHeader.vue → {name} Cloud OS"
Task: "T012 HomeCanvas.vue → {name} v2.4"
Task: "T013 Login.vue → :title binding"
Task: "T014 Register.vue → :title binding"
Task: "T015 ResetPassword.vue → :subtitle binding"
Task: "T016 Invitations/Show.vue → Ir a {name}"
Task: "T017 MembersModal.vue → texto de ayuda"
```

## Implementation Strategy

### MVP First (User Story 1)

1. Phase 1 + Phase 2 (configuración, fallbacks y guardia).
2. Phase 3 (US1): la UI ya dice Eunox, y el título de pestaña y el remitente dejan de decir "Laravel".
3. **Validar** con SharedAppNameTest y el recorrido manual de la UI.

### Incremental Delivery

1. MVP (US1) → la marca es visible en la app.
2. US2 → los correos quedan alineados y la guardia T008 pasa a verde.
3. US3 → documentación.
4. Polish → suite completa, limpieza y notas de despliegue.
