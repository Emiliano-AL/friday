# Data Model: Página de Configuración

Sin migraciones ni entidades nuevas.

## User (existente)

| Campo | Uso en esta feature | Reglas |
| --- | --- | --- |
| `name` | editable | `required`, `string`, `max:255` (recortado por `TrimStrings`) |
| `email` | solo lectura | no se modifica desde Configuración |
| `password` | cambiar/definir | nullable (cuentas de Google); nueva: `required`, `confirmed`, `Password::min(8)` |
| `avatar` | solo lectura | se muestra si existe |
| `oauth_provider` | solo lectura | determina «Google» como método de acceso |

**Método de acceso** (derivado, no se guarda): `password` → «Correo y contraseña»; `oauth_provider` → «Google»; ambos → los dos.

## Efectos de eliminar un User

| Relación | Efecto |
| --- | --- |
| Proyectos propios (`projects.owner_id`) | bloquea la eliminación (D7) |
| Tareas sueltas (`project_id` null, autora/responsable) | se eliminan explícitamente (D8) |
| Tareas de proyecto (`author_id`, `assignee_id`) | quedan en `null` (FK) |
| Membresías, comentarios, actividad, invitaciones enviadas | cascade (FK) |
| Sesiones | se cierra la actual; las demás expiran al no existir el usuario |

## Props de la página `Settings/Index`

```ts
account: {
  name: string;
  email: string;
  avatar: string | null;
  hasPassword: boolean;
  usesGoogle: boolean;
};
ownedProjects: { id: number; title: string; color: string | null }[];
```
