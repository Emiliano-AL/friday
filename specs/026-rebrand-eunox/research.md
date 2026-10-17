# Research: Rebranding público a Eunox

No quedaron `NEEDS CLARIFICATION` en el Technical Context. Estas son las decisiones de diseño tomadas a partir del código actual.

## R1. Fuente única del nombre público

- **Decision**: Usar `config('app.name')` (variable `APP_NAME`) como única fuente, con default `'Eunox'` en `config/app.php`. Fijar `APP_NAME=Eunox` en `.env.example`, `.env` y `phpunit.xml`.
- **Rationale**: `HandleInertiaRequests::share()` ya expone `'name' => config('app.name')` y el tipo `sharedPageProps.name: string` ya existe. `MAIL_FROM_NAME="${APP_NAME}"` y el layout de correos markdown de Laravel también leen `app.name`, así que un solo valor cubre UI, títulos, remitente y pie de correos (FR-004, SC-005).
- **Alternatives considered**: (a) Agregar una clave `config('app.brand')` separada de `app.name`: es redundante, porque `app.name` no se usa para nada interno. (b) Usar una constante TS en el frontend: duplicaría el valor y rompería SC-005.

## R2. Cómo leen el nombre los componentes Vue

- **Decision**: En cada componente, leer `usePage().props.name` (o `$page.props.name` en el template) en lugar del literal "Friday".
- **Rationale**: Es el patrón Inertia ya en uso (`usePage` aparece en `SidebarNav`, `ContextSwitcher`, etc.) y está tipado. Son ~8 lugares, poca repetición.
- **Alternatives considered**: Un composable `useAppName()`. Se descarta por YAGNI (Principio V) porque sería una línea envolviendo otra línea.

## R3. Título de pestaña

- **Decision**: Mantener `title: (t) => t ? \`${t} - ${appName}\` : appName` en `resources/js/app.ts` y cambiar solo el fallback `'Laravel'` → `'Eunox'`. En `app.blade.php`, cambiar `config('app.name', 'Laravel')` → `config('app.name', 'Eunox')`.
- **Rationale**: `VITE_APP_NAME="${APP_NAME}"` ya hereda el valor. El fallback cubre el edge case "nombre de configuración ausente".
- **Alternatives considered**: Pasar el título desde la prop `name` en runtime. Es innecesario porque Vite ya inyecta el valor en el build. **Nota operativa**: cambiar `APP_NAME` exige recompilar los assets (`npm run build`) para que el título se actualice.

## R4. Correos

- **Decision**: El asunto de `ProjectInvitationMail` usa `config('app.name')`. La plantilla `mail/project-invitation.blade.php` reemplaza "en Friday" por `en {{ config('app.name') }}`. El remitente sale de `MAIL_FROM_NAME` → `APP_NAME`, y el default de `config/mail.php` pasa de `'Laravel'` a `'Eunox'`.
- **Rationale**: Los correos de restablecer contraseña y de verificación usan las notificaciones por defecto de Laravel, cuyo layout ya imprime `config('app.name')` en encabezado y pie. Con R1 quedan cubiertos sin publicar vistas de vendor.
- **Alternatives considered**: Publicar `vendor/mail` para personalizar el layout. No hace falta para cumplir el spec.

## R5. Identificadores internos con `friday`

- **Decision**: No renombrar `friday:open-palette`, `friday:open-task-form`, `friday:kanban-done-collapsed` ni `.friday-focus-scope`.
- **Rationale**: No son visibles para las personas usuarias. Renombrar la clave de `localStorage` borraría sus preferencias (SC-004). Mantenerlos es coherente con "codename Friday".
- **Alternatives considered**: Migrar la clave de `localStorage` con fallback de lectura. Agrega complejidad sin beneficio visible.

## R6. Guardia de regresión

- **Decision**: Agregar un test Pest que recorra `resources/js/**/*.vue`, `resources/views/**/*.blade.php` y `app/Mail/**/*.php`, y falle si encuentra la palabra `Friday` con mayúscula (regex `/\bFriday\b/`, sensible a mayúsculas).
- **Rationale**: Hace verificable SC-001 en CI. Las ocurrencias internas son en minúscula (`friday:`, `friday-`), así que no generan falsos positivos.
- **Alternatives considered**: Revisión manual solamente. No escala y no protege contra regresiones futuras.

## R7. Recursos gráficos

- **Hallazgo**: `public/favicon.svg` (y probablemente `favicon.ico` y `apple-touch-icon.png`) es el **logo de Laravel** (`#FF2D20`). `resources/js/Components/ApplicationLogo.vue` también es el logo de Laravel, pero no se usa en ningún lugar. `AppShell/AppLogo.vue` es un glifo propio neutral.
- **Decision**: Fuera de alcance según el spec (no hay arte de Eunox). Se registra como pendiente. Como limpieza opcional de bajo riesgo, se puede eliminar `ApplicationLogo.vue`, que no se usa.
- **Follow-up**: Cuando exista el logo de Eunox, reemplazar los favicons. Mientras tanto, la pestaña sigue mostrando el ícono de Laravel, lo cual contradice el espíritu de FR-006 aunque no su letra (que habla de textos).

## R8. Documentación

- **Decision**: README.md y DESIGN.md mantienen "Friday" y agregan una línea: "Friday es el codename interno; el nombre público del producto es **Eunox**". La constitución recibe la misma nota como enmienda PATCH 1.1.1 con su entrada en el Sync Impact Report. Las specs 001–025 no se tocan.
