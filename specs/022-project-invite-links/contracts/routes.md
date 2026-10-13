# Contract: rutas e Inertia props

## Rutas nuevas / modificadas

| Método | URI | Nombre | Middleware | Acción |
| --- | --- | --- | --- | --- |
| POST | `projects/{project}/members` | `projects.members.store` | `auth`, `throttle:project-invitations`* | **Modificada**: correo registrado → adjunta (igual que hoy); correo sin cuenta → crea/refresca invitación y envía correo |
| POST | `projects/{project}/invitations/{invitation}/resend` | `projects.invitations.resend` | `auth`, `throttle:project-invitations` | Reenvía: nuevo token, `expires_at` +7 días |
| DELETE | `projects/{project}/invitations/{invitation}` | `projects.invitations.destroy` | `auth` | Cancela (`Cancelled`) |
| GET | `invitations/{token}` | `invitations.show` | `web` (sin `auth`) | Página de invitación; sin sesión fija URL intentada y correo invitado en sesión |
| POST | `invitations/{token}/accept` | `invitations.accept` | `auth` | Acepta → miembro → redirige a `projects.show` |
| POST | `invitations/{token}/decline` | `invitations.decline` | `auth` | Rechaza → redirige a `home` |

\* El límite se aplica solo cuando se crea/reenvía una invitación (el camino de usuario registrado no envía correo).

Autorización de rutas `projects/*/invitations/*`: `ProjectPolicy::manageMembers` (propietario) + proyecto `Active`; la invitación debe pertenecer al `{project}` (404 si no).

## Errores y mensajes (español)

| Caso | Respuesta |
| --- | --- |
| Correo inválido / vacío | errores de validación actuales |
| Ya es miembro | `email`: "Este usuario ya es miembro del proyecto." (actual) |
| Proyecto no activo | `email`: "No puedes invitar en un proyecto archivado o completado." |
| Falla de envío | invitación creada; flash `error`: "No pudimos enviar el correo. Puedes reenviar la invitación." |
| Límite excedido | 429 → flash/aviso "Demasiadas invitaciones, inténtalo más tarde." |
| Enlace inexistente/cancelado/usado/vencido | `invitations.show` renderiza estado `unavailable` con motivo (no 404 genérico) |
| Correo de la sesión no coincide | `invitations.show` estado `mismatch`; `accept`/`decline` → 403 |

## Inertia props

### `Projects/Show` (solo propietario)

```ts
project.pendingInvitations: Array<{
    id: number;
    email: string;
    sentAt: string;      // ISO, last_sent_at
    isExpired: boolean;
}>
```
Para no propietarios el array va vacío.

### `Invitations/Show` (página nueva)

```ts
{
    state: 'valid' | 'unavailable' | 'mismatch' | 'guest';
    reason?: 'expired' | 'cancelled' | 'used' | 'inactive_project' | 'not_found';
    invitation?: {
        email: string;
        project: { title: string; color: string | null; icon: string | null };
        invitedBy: { name: string; avatar: string | null };
        expiresAt: string;
    };
    currentEmail?: string;   // en estado 'mismatch'
}
```

### `Auth/Register`

```ts
{ email?: string }   // correo invitado precargado desde la sesión (FR-016)
```

## Correo `ProjectInvitationMail`

- Asunto: "{invitador} te invitó a {proyecto} en Friday".
- Cuerpo: proyecto, quién invita, vigencia (7 días), botón "Ver invitación" → `invitations.show`.
