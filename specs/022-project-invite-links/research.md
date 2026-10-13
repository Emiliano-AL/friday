# Research: Invitaciones a proyectos por enlace

**Fecha**: 2026-10-10 | **Feature**: [spec.md](spec.md) | **Plan**: [plan.md](plan.md)

Sin NEEDS CLARIFICATION pendientes (Q1–Q3 resueltas en la spec). Investigación
directa sobre el código existente.

## Hallazgos

- `ProjectMemberController@store` valida `Rule::exists('users', 'email')` y adjunta
  al instante; el mensaje `email.exists` es el "correo no encontrado" que esta
  feature reemplaza para correos sin cuenta. `destroy` y `ProjectPolicy::manageMembers`
  (solo propietario) se reutilizan.
- Autenticación (feature 001): `AuthenticatedSessionController@store` y
  `GoogleAuthController@callback` ya terminan en `redirect()->intended(...)`;
  `RegisteredUserController@store` termina en `redirect(route('home'))` — **no**
  respeta la URL intentada. No hay verificación de correo (`User` no implementa
  `MustVerifyEmail`).
- Correo: `mail.default = smtp`; no existen Mailables ni vistas de correo aún.
  Cola: `queue.default = database` (requiere worker).
- Rate limiters existentes en `AppServiceProvider` (`google-oauth-*`): patrón a
  reutilizar.
- `MembersModal.vue` muestra "Enlace de invitación rápida" y roles como
  "Próximamente" (líneas ~131–248); el flujo de "invitaciones pendientes" no
  existe en la UI actual.
- `ProjectController@show` entrega `project` vía `projectPayload`; el modal lee
  miembros de ahí.

## Decisiones

### D1. Tabla `project_invitations` con token hasheado
- **Decision**: entidad propia con `token_hash` (SHA-256 del token aleatorio de
  64 caracteres); el token en claro solo existe en el enlace del correo.
- **Rationale**: FR-003 (no adivinable); una fuga de la base no expone enlaces
  usables. SHA-256 (no bcrypt) porque el token ya tiene alta entropía y se busca
  por igualdad.
- **Alternativas**: URLs firmadas de Laravel sin tabla → no permiten cancelar,
  reenviar ni listar pendientes (FR-008/009). Guardar el token en claro → riesgo
  innecesario.

### D2. Estado como enum; "vencida" derivada de `expires_at`
- **Decision**: enum `ProjectInvitationStatus` (`Pending`, `Accepted`,
  `Declined`, `Cancelled`); una invitación `Pending` con `expires_at` pasado se
  trata como vencida (`isExpired()`), sin job de barrido.
- **Rationale**: Constitución IV; YAGNI evita un scheduler solo para marcar
  vencidas.
- **Alternativas**: estado `Expired` persistido por job programado → más
  piezas sin beneficio visible.

### D3. Registro conserva la invitación vía `url.intended`
- **Decision**: al abrir el enlace sin sesión, se guarda el enlace como URL
  intentada (`redirect()->setIntendedUrl`) y el correo invitado en sesión;
  `RegisteredUserController@store` pasa a `redirect()->intended(route('home'))`
  y `create` precarga el correo (FR-016). Login y Google ya respetan `intended`.
- **Rationale**: FR-004/005 con un cambio de una línea en el registro; mismo
  mecanismo para los tres caminos de acceso.
- **Alternativas**: aceptar automáticamente al registrarse → contradice
  "aceptar" explícito de la spec; parámetro en query a través de OAuth → frágil.

### D4. Coincidencia estricta de correo (Q3=A)
- **Decision**: aceptar/rechazar exige `strtolower(user.email) ===
  invitation.email`; si no coincide, la página muestra a qué correo se envió y
  ofrece cerrar sesión.
- **Rationale**: FR-015; un enlace reenviado no da acceso.

### D5. Envío síncrono con manejo de fallo
- **Decision**: `Mail::to(...)->send(new ProjectInvitationMail(...))` dentro de
  `try/catch`; si falla, la invitación queda `Pending` y se avisa al líder que
  puede reenviar.
- **Rationale**: edge case "envío fallido" visible al instante; la cola
  `database` exigiría un worker activo y ocultaría el fallo.
- **Alternativas**: `queue()` → el líder vería "enviada" aunque nunca salga.

### D6. Límite de envío
- **Decision**: `RateLimiter::for('project-invitations', 20 por hora por usuario)`
  aplicado a crear y reenviar.
- **Rationale**: FR-014 con el patrón existente.

### D7. Una sola invitación pendiente por proyecto+correo
- **Decision**: al invitar un correo con pendiente vigente se reenvía esa
  (nuevo token, `expires_at` renovado); si estaba vencida, se reutiliza la
  fila igual. Índice único parcial en PostgreSQL sobre `(project_id, email)
  WHERE status = 'pending'` como red de seguridad.
- **Rationale**: FR-011 y SC-004.

### D8. Registrados se agregan al instante (Q1=B)
- **Decision**: `store` primero busca usuario por correo: si existe → flujo
  actual (adjuntar); si no → invitación. El mensaje `email.exists` desaparece.
- **Rationale**: FR-012; las pruebas existentes de 016 para usuarios
  registrados siguen válidas.

## Resolución de clarificaciones

| # | Tema | Resolución |
| --- | --- | --- |
| Q1 | Usuarios registrados | Se agregan al instante (D8) |
| Q2 | Enlace de invitación rápida | Fuera de alcance; sigue "Próximamente" |
| Q3 | Cuenta que acepta | Correo debe coincidir exactamente (D4) |
