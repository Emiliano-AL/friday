# Research: Página de Configuración

## D1 — Validación inline en un solo controlador

- **Decision**: `SettingsController` con `$request->validate()` en cada acción.
- **Rationale**: tres formularios de 1–3 campos; los controladores de auth existentes (`RegisteredUserController`, `NewPasswordController`) validan inline.
- **Alternatives**: un FormRequest por formulario (3 archivos para reglas triviales); controladores separados por sección (Breeze `ProfileController` + `PasswordController`).

## D2 — Política de contraseñas

- **Decision**: `['required', 'string', 'confirmed', Rules\Password::min(8)]`, igual que registro y restablecimiento.
- **Rationale**: la spec pide la misma política que el registro.
- **Alternatives**: `Password::defaults()` (más estricta en producción; inconsistente con registro hoy).

## D3 — Contraseña actual y cuentas de Google

- **Decision**: `current_password` es `required` + regla `current_password` solo si `$user->password !== null`; si es `null`, se ignora y se define la contraseña directamente.
- **Rationale**: FR-006/FR-007; la regla nativa ya compara contra el hash del usuario autenticado.

## D4 — Cerrar las demás sesiones

- **Decision**: añadir `auth.session` (`AuthenticateSession`) al grupo `auth` de `routes/web.php`; tras guardar la nueva contraseña llamar `Auth::logoutOtherDevices($nueva)`.
- **Rationale**: mecanismo documentado de Laravel; funciona con cualquier driver de sesión (tests usan `array`, local `database`). El middleware ignora usuarios sin contraseña. Efecto colateral deseado: un restablecimiento de contraseña también cierra sesiones viejas.
- **Alternatives**: borrar filas de la tabla `sessions` (atado al driver `database`, no testeable con `array`).

## D5 — Límite de intentos

- **Decision**: `RateLimiter::for('account-security', 6/min por id de usuario)` aplicado con `throttle:account-security` a `PUT settings/password` y `DELETE settings`.
- **Rationale**: FR-009; mismo patrón que `project-invitations`.

## D6 — Correo de solo lectura

- **Decision**: `updateProfile` valida y guarda solo `name` (`$user->update($request->validate(['name' => ...]))`); cualquier `email` enviado se ignora.
- **Rationale**: Clarificación Q1 = C.

## D7 — Bloqueo de eliminación con proyectos propios

- **Decision**: la página recibe `ownedProjects` (`id`, `title`, `color`) de `Project::where('owner_id', $user->id)` (todos los estados). `destroy` vuelve a comprobarlo y responde con error de validación en `password` si hay alguno.
- **Rationale**: Clarificación Q2 = B; FR-013 exige validación en servidor. La FK `projects.owner_id` hace cascade, por eso se bloquea antes.

## D8 — Qué se borra con la cuenta

- **Decision**: en una transacción, eliminar las tareas sueltas de la persona (`project_id IS NULL` y `author_id` o `assignee_id` = usuario) y luego el usuario. El resto lo resuelven las FKs: membresías, comentarios, actividad e invitaciones enviadas en cascade; `author_id`/`assignee_id` de tareas de proyectos → `null`. Luego `Auth::logout()`, invalidar sesión, regenerar token y redirigir a `login`.
- **Rationale**: las tareas sueltas solo son visibles para su autora; quedarían huérfanas e invisibles.

## D9 — Confirmación de eliminación sin contraseña

- **Decision**: con contraseña → campo `password` + regla `current_password`; sin contraseña → campo `confirmation` que debe ser igual al correo de la cuenta (sin distinguir mayúsculas).
- **Rationale**: FR-010.

## D10 — Navegación

- **Decision**: `footerItems` «Ajustes» → `settings.edit`, `match: '/settings'`; en `ProfileMenu`, los dos `<span>` «Próximamente» pasan a `<Link>` a `settings.edit` (perfil sin ancla, cuenta con `#seguridad`).
- **Rationale**: FR-002; sin rutas nuevas para cada opción.
