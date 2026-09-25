---
description: 'Task list for feature implementation'
---

# Tasks: Rediseño del layout de páginas de autenticación

**Input**: Design documents from `/specs/006-auth-pages-redesign/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/auth-pages-ui.md ✅

**Tests**: No se generan tests nuevos (rediseño visual sin lógica nueva, según spec FR-005). La red de regresión es la suite existente `tests/Feature/Auth/*` que debe pasar sin modificación; la verificación visual se hace con `quickstart.md`.

**Organization**: Tareas agrupadas por user story para implementación y verificación independiente.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias)
- **[Story]**: A qué user story pertenece (US1, US2, US3)
- Incluye rutas exactas de archivos

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Base visual compartida: fuente Inter y tokens de DESIGN.md. Bloquea todo lo demás.

- [x] T001 Add Google Fonts link for Inter (`Inter:wght@400;500;600;700&display=swap`) with preconnect hints in resources/views/app.blade.php (copy `<link>` tags from reference HTML header; research.md §1)
- [x] T002 Declare DESIGN.md design tokens in `@theme` block of resources/css/app.css: full color palette as CSS custom properties (`--color-surface: #f9f9ff`, `--color-primary-container: #5b5bd6`, `--color-on-surface: #141b2b`, `--color-error: #ba1a1a`, etc. — all tokens from DESIGN.md YAML front-matter), set `--font-sans` to Inter, spacing scale (`--spacing-space-*`), radius scale, and font-size utilities (`--text-headline-lg`, `--text-body-md`, `--text-label-xs`, etc. with lineHeight/letterSpacing/fontWeight from DESIGN.md typography section; research.md §2). Values MUST match DESIGN.md verbatim — do not invent tokens.

**Checkpoint**: `npm run build` compila; clases utilitarias Tailwind de tokens disponibles (p. ej. `bg-surface`, `text-primary-container`).

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Componentes del sistema de auth y shell compartido. Ninguna página puede rehacerse hasta que esta fase esté completa.

- [x] T003 [P] Create AuthTextInput.vue in resources/js/Components/Auth/: text input styled per DESIGN.md Inputs spec (white/surface-container-low background 36–44px, 1px border `outline-variant`, radius, `body-md`, focus state with accent border `#5B5BD6` + `2px` ring `rgba(91,91,214,0.15)`); props: `modelValue`, `type`, `placeholder`, `icon` (leading Material-style icon slot), `error?: string`; named slot `trailing` for action buttons (eye toggle); label association and accessible focus ring per FR-007.
- [x] T004 [P] Create AuthCheckbox.vue in resources/js/Components/Auth/: checkbox per DESIGN.md Checkboxes spec (16x16px, 4px radius, inactive: 1px `#D1D5DB` border on white; active: `#5B5BD6` background with white checkmark); props: `modelValue: boolean`, `label: string`; hover label color transition per reference design.
- [x] T005 [P] Create SocialGoogleButton.vue in resources/js/Components/Auth/: full-width 44px button, white/surface background, subtle border + shadow, inline multi-color Google "G" SVG (copy paths from reference code.html lines 21–26), label "Continuar con Google"; floating "Recomendado" badge pill (accent dot + label-xs accent text, `bg-surface-container-high`); hover/active micro-interactions per reference (`active:scale-[0.99]`, bg transition); renders as `<a>` pointing to Wayfinder `googleRedirect()` route from `@/routes/google` (same behavior as current Login.vue).
- [x] T006 [P] Create AuthHeader.vue in resources/js/Components/Auth/: brand row per reference — pulsing 8px accent dot + "Friday" in uppercase label-xs with tracking (left); lock icon + "Conexión segura SSL" label-xs variant color (right); full width with `px-margin` horizontal padding; compact-safe for mobile.
- [x] T007 [P] Create AuthFooter.vue in resources/js/Components/Auth/: footer row per reference — "Privacidad • Términos • Soporte" links with outline-variant separators, "Idioma: Español (ES)" with language icon; plus status pill "Todos los sistemas operativos" (green pulsing dot, pill background `surface-container-low`) placed above footer links in the main column; label-sm `on-surface-variant` colors.
- [x] T008 Create AuthCard.vue in resources/js/Components/Auth/ (depends on T001, T002): white card (`bg-surface-container-lowest`), `max-w-[440px]`, radius `0.75rem`, soft shadow with primary tint, `p-2rem` padding; slots: `logo` (48px rounded accent-tint brand mark container), `title` (headline-lg), `subtitle` (body-md `on-surface-variant`), default (form content), `footer-links` (below-form links); centered content header.
- [x] T009 Rebuild resources/js/Layouts/GuestLayout.vue (depends on T006, T007, T008): full-height flex column; AuthHeader on top; centered main with decorative blurred accent halo (`absolute` ~500px circle `primary-fixed/30 blur-3xl`, `-top-24`, `pointer-events-none`) behind AuthCard (which wraps `<slot />`); status pill + footer below main; responsive lateral margins (`px-margin-mobile` → `px-margin` at lg); keep `bg-surface`/`text-on-surface`/`font-sans` base; verify GuestLayout is only consumed by the 4 Auth pages (already verified in plan.md).

**Checkpoint**: Foundation ready — las 4 páginas Auth pueden rehacerse en paralelo usando el shell y componentes nuevos.

---

## Phase 3: User Story 1 - Inicio de sesión con el nuevo layout (Priority: P1) 🎯 MVP

**Goal**: Página `/login` replica el diseño de referencia (tarjeta, Google "Recomendado", divisor, campos con iconos, toggle ojo, remember, footer) sin cambiar comportamiento (spec US1).

**Independent Test**: Según quickstart.md §3: `/login` renderiza igual que screen.png en desktop y 375px; toggle ojo alterna visibilidad; credenciales inválidas muestran errores por campo dentro de la tarjeta; login válido redirige al dashboard; `php artisan test --compact tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/GoogleAuthenticationTest.php` sigue en verde.

### Implementation for User Story 1

- [x] T010 [US1] Rebuild resources/js/pages/Auth/Login.vue with new components (depends on T003, T004, T005, T009): keep existing `<script setup lang="ts">` logic unchanged — same `useForm({email, password, remember})`, same `form.post(login.url())` from `@/routes`, same props `canResetPassword`, `status`, same Wayfinder imports; template per contract contracts/auth-pages-ui.md §Login: SocialGoogleButton → divider "o continuar con correo" → AuthTextInput email (mail icon, placeholder `nombre@empresa.com`) → AuthTextInput password (lock icon, trailing eye-toggle button switching `type` password/text via local `showPassword` ref, aria-label "Alternar visibilidad de contraseña") with label row containing "¿Olvidaste tu contraseña?" link → AuthCheckbox "Recordar este dispositivo" → submit button (accent bg, white label-md text, arrow icon with hover translate, `↵` kbd hidden below sm) → "¿No tienes cuenta de equipo? Regístrate gratis" link to register route; render per-field validation errors under each input (use existing `form.errors` from Inertia) and `status` banner inside the card; remove old Breeze component imports (GuestLayout stays as layout, TextInput/InputLabel/PrimaryButton/Checkbox no longer used here).
- [x] T011 [US1] Validate login story per quickstart.md §2–§4: run `php artisan test --compact tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/GoogleAuthenticationTest.php` (must pass unchanged), `npm run build` clean, visual check of `/login` against screen.png at 1440px and 375px (no horizontal scroll), toggle ojo and focus ring verified manually.

**Checkpoint**: US1 completamente funcional e independiente — la página de login está rediseñada y el comportamiento no regresó.

---

## Phase 4: User Story 2 - Registro de cuenta con el nuevo layout (Priority: P2)

**Goal**: Página `/register` con el mismo sistema visual del login adaptada a creación de cuenta (spec US2).

**Independent Test**: Según quickstart.md §3: `/register` renderiza con la tarjeta/título/campos del contrato; registro con email duplicado o confirmación distinta muestra errores bajo cada campo; `php artisan test --compact tests/Feature/Auth/RegistrationTest.php` sigue en verde.

### Implementation for User Story 2

- [x] T012 [US2] Rebuild resources/js/pages/Auth/Register.vue with new components (depends on T003, T004, T005, T009): keep script logic unchanged — same `useForm({name, email, password, password_confirmation})`, same post to register route, same props/errors; template per contracts/auth-pages-ui.md §Registro: SocialGoogleButton + divider → AuthTextInput Nombre completo (person icon) → AuthTextInput Correo (mail icon) → AuthTextInput Contraseña (lock icon + eye toggle via `showPassword` ref) → AuthTextInput Confirmar contraseña (lock icon + eye toggle via second `showConfirmPassword` ref) → submit "Crear cuenta" → "¿Ya tienes cuenta? Inicia sesión" link to login route; per-field errors under inputs.
- [x] T013 [US2] Validate register story per quickstart.md: `php artisan test --compact tests/Feature/Auth/RegistrationTest.php` green, visual check `/register` at 1440px and 375px.

**Checkpoint**: US1 + US2 funcionan independientemente.

---

## Phase 5: User Story 3 - Recuperación de contraseña con el nuevo layout (Priority: P3)

**Goal**: Páginas `/forgot-password` y `/reset-password/{token}` con tarjetas centradas del mismo sistema visual (spec US3).

**Independent Test**: Según quickstart.md §3: forgot-password muestra explicación + campo correo + banner de estado tras envío; reset-password con token muestra campos de nueva contraseña con toggles; `php artisan test --compact tests/Feature/Auth/PasswordResetTest.php` sigue en verde.

### Implementation for User Story 3

- [x] T014 [P] [US3] Rebuild resources/js/pages/Auth/ForgotPassword.vue with new components (depends on T003, T009): keep script unchanged — same `useForm({email})`, post to `passwordEmail` route, same `status` prop; template per contracts/auth-pages-ui.md §Recuperar: title "Recuperar contraseña" + explanatory body text → AuthTextInput email (mail icon) → submit "Enviar enlace de recuperación" → success banner rendering `status` prop (accent/success style) inside card → "Volver a iniciar sesión" link.
- [x] T015 [P] [US3] Rebuild resources/js/pages/Auth/ResetPassword.vue with new components (depends on T003, T009): keep script unchanged — same `useForm({email, token, password, password_confirmation})` with `email`/`token` seeded from props, post to password store route; template per contracts/auth-pages-ui.md §Restablecer: title "Nueva contraseña" + brief message → AuthTextInput Nueva contraseña (lock icon + eye toggle) → AuthTextInput Confirmar contraseña (lock icon + eye toggle) → hidden email + token inputs preserving current values → submit "Restablecer contraseña"; per-field errors (invalid/expired token, mismatch) under inputs.
- [x] T016 [US3] Validate recovery stories per quickstart.md: `php artisan test --compact tests/Feature/Auth/PasswordResetTest.php` green, visual check of both pages at 1440px and 375px.

**Checkpoint**: Las 3 user stories funcionan independientemente.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Verificación cruzada de alcance, calidad y regresión final.

- [x] T017 Run full auth regression: `php artisan test --compact tests/Feature/Auth` — los 4 archivos deben pasar sin modificación (spec FR-005, SC-002).
- [x] T018 Verify scope isolation per quickstart.md §5: navegar `/projects` (y create/edit de proyecto) con login — las páginas deben verse idénticas a antes del rediseño (los componentes Breeze globales no se tocaron; FR-009).
- [x] T019 [P] Responsive + accessibility sweep per quickstart.md §4: DevTools en 320px/375px/768px/1440px en las 4 páginas (sin scroll horizontal, SC-004); Lighthouse en `/login` sin violaciones críticas de accesibilidad (contraste, labels, foco visible, SC-005); navegación por Tab con anillo de foco acento en cada interactivo (FR-007).
- [x] T020 Final quality gates: `vendor/bin/pint --dirty` (si hubo cambios PHP), `npm run build` sin errores ni warnings nuevos; marcar checklist completo en contracts/auth-pages-ui.md para las 4 páginas en desktop y móvil.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Sin dependencias — empieza de inmediato; bloquea Phase 2 (tokens y fuente necesarios para estilos).
- **Foundational (Phase 2)**: Depende de Phase 1. T003–T007 son paralelos entre sí; T008 depende de tokens (T002); T009 depende de T006+T007+T008. **Bloquea todas las user stories.**
- **User Stories (Phase 3–5)**: Dependen de Phase 2. US2 y US3 pueden empezar en cuanto Phase 2 termina (en paralelo con US1 si hay capacidad); dentro de US3, T014 y T015 son paralelos.
- **Polish (Phase 6)**: Depende de que las user stories deseadas estén completas.

### User Story Dependencies

- **US1 (P1)**: Solo depende de Phase 2. Sin dependencias de otras stories. **MVP.**
- **US2 (P2)**: Solo depende de Phase 2 (reutiliza componentes de Phase 2, no de US1).
- **US3 (P3)**: Solo depende de Phase 2 (T014/T015 no dependen de T010–T013).

Las tres stories son implementables y verificables de forma independiente; comparten únicamente la infraestructura de Phase 1–2.

### Within Each User Story

- Rebuild de página (conservando `<script>` logic intacto) → validación de la story (tests existentes + visual) → checkpoint.
- Regla dura: si un test de `tests/Feature/Auth/*` requiere cambiarse, se detiene y se revisa — FR-005 prohíbe cambios de comportamiento.

### Parallel Opportunities

- **Phase 1**: T001 y T002 son paralelos (archivos distintos).
- **Phase 2**: T003, T004, T005, T006, T007 en paralelo (5 archivos nuevos independientes). Luego T008, y al final T009.
- **Phase 3–5**: con capacidad de equipo, las 3 stories en paralelo tras Phase 2; en US3, T014 ∥ T015.
- **Phase 6**: T018 y T019 en paralelo.

---

## Parallel Example: Foundational Phase

```text
T003 AuthTextInput.vue   ─┐
T004 AuthCheckbox.vue    ─┤
T005 SocialGoogleButton ──┼─ ejecutar en paralelo (archivos nuevos independientes)
T006 AuthHeader.vue      ─┤
T007 AuthFooter.vue      ─┘
        ↓
T008 AuthCard.vue        (tokens listos)
        ↓
T009 GuestLayout.vue     (header + footer + card listos)
        ↓
Fase de user stories (US1 ∥ US2 ∥ US3 posible)
```

## Parallel Example: User Stories tras Phase 2

```text
Developer A: T010 Login.vue     + T011 validación US1   (MVP)
Developer B: T012 Register.vue  + T013 validación US2
Developer C: T014 ForgotPassword ∥ T015 ResetPassword + T016 validación US3
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1 (Setup) → Phase 2 (Foundational).
2. Phase 3 (US1: Login) → T011 validación.
3. **STOP and VALIDATE**: login rediseñado, tests en verde, comparación visual con screen.png.
4. Demo/deploy si se desea — el login es la página de mayor tráfico.

### Incremental Delivery

1. Setup + Foundational → base lista.
2.  - US1 → validar → MVP entregable.
3.  - US2 → validar.
4.  - US3 → validar.
5. Polish (regresión completa, scope isolation, responsive/a11y).

Cada story agrega valor sin romper las anteriores; el comportamiento funcional nunca cambia (FR-005).

---

## Notes

- [P] tasks = archivos distintos, sin dependencias.
- [Story] etiqueta traza cada tarea a su user story (US1/US2/US3).
- Regla de oro del feature: **los `<script setup>` de las 4 páginas no cambian su lógica** — solo el template y los imports de componentes visuales. Los tests de feature existentes son intocables.
- Detenerse en cualquier checkpoint para validar la story de forma independiente.
- Evitar: tocar componentes Breeze globales (afecta Projects, fuera de alcance FR-009), añadir dependencias npm/Composer, modificar rutas o controladores.
