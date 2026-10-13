# Data Model: Invitaciones a proyectos por enlace

## Nueva entidad: `ProjectInvitation` (`project_invitations`)

| Campo | Tipo | Reglas |
| --- | --- | --- |
| `id` | bigint PK | |
| `project_id` | FK → `projects.id` | `cascadeOnDelete` (FR: proyecto eliminado invalida invitaciones) |
| `invited_by_id` | FK → `users.id` | quién invita (líder); `cascadeOnDelete` |
| `email` | string(255) | normalizado a minúsculas; email válido |
| `token_hash` | string(64), único | SHA-256 del token en claro (D1) |
| `status` | string (enum `ProjectInvitationStatus`) | `pending` por defecto |
| `expires_at` | timestamp | `now() + 7 días` al crear y al reenviar |
| `last_sent_at` | timestamp | fecha de envío mostrada en el modal |
| `responded_at` | timestamp nullable | al aceptar/rechazar/cancelar |
| `created_at` / `updated_at` | timestamps | |

**Índices**: único `token_hash`; único parcial `(project_id, email) WHERE status = 'pending'` (D7); índice `(project_id, status)` para el listado del modal.

### Enum `App\Enums\ProjectInvitationStatus`

`Pending` · `Accepted` · `Declined` · `Cancelled` (keys TitleCase, valores en minúscula). Con `label()` en español para la UI.

### Transiciones

```text
Pending ──aceptar (correo coincide, vigente, proyecto activo)──▶ Accepted
Pending ──rechazar (correo coincide, vigente)──────────────────▶ Declined
Pending ──cancelar (líder)─────────────────────────────────────▶ Cancelled
Pending ──reenviar (líder)──▶ Pending (nuevo token, expires_at renovado)
Pending + expires_at < now ──▶ "vencida" (derivado, no persistido; reenviar la reactiva)
Accepted / Declined / Cancelled ──▶ terminal (el enlace deja de servir)
```

### Comportamiento del modelo

- `isExpired(): bool` — `status === Pending && expires_at->isPast()`.
- `isUsable(): bool` — `Pending && ! isExpired() && project->status === Active`.
- `matches(User $user): bool` — comparación de correo sin distinguir mayúsculas.
- `scopePending()` — pendientes (incluye vencidas, para que el líder pueda reenviarlas).
- `static issue(Project, string $email, User $inviter): array{0: self, 1: string}` — crea o refresca la pendiente y devuelve el token en claro.

## Relaciones

- `Project hasMany ProjectInvitation` (`invitations()`), `pendingInvitations()`.
- `ProjectInvitation belongsTo Project`, `belongsTo User` (`invitedBy`).
- Al aceptar: `project->members()->syncWithoutDetaching($user)` (sin duplicar, SC-004).

## Sin cambios

- `users`, `projects`, `project_user` (pivot de miembros) conservan su estructura.
