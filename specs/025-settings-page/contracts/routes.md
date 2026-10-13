# Contract: rutas de Configuración

Todas bajo `auth` (+ `auth.session`). Respuestas Inertia; errores de validación en la bolsa por defecto; éxito con flash `success`.

| Método | URI | Nombre | Middleware extra | Cuerpo | Éxito |
| --- | --- | --- | --- | --- | --- |
| GET | `/settings` | `settings.edit` | — | — | `Settings/Index` con `account`, `ownedProjects` |
| PATCH | `/settings/profile` | `settings.profile.update` | — | `name` | back + «Perfil actualizado.» |
| PUT | `/settings/password` | `settings.password.update` | `throttle:account-security` | `current_password` (si tiene contraseña), `password`, `password_confirmation` | back + «Contraseña actualizada.» / «Contraseña definida.»; otras sesiones cerradas |
| DELETE | `/settings` | `settings.destroy` | `throttle:account-security` | `password` (si tiene contraseña) o `confirmation` (= correo) | logout + redirect `login` |

## Errores

| Caso | Campo | Mensaje |
| --- | --- | --- |
| Nombre vacío / > 255 | `name` | validación estándar en español |
| Contraseña actual incorrecta | `current_password` | «La contraseña actual no es correcta.» |
| Nueva no coincide / corta | `password` | validación estándar |
| Confirmación de borrado incorrecta | `password` o `confirmation` | «La confirmación no coincide.» |
| Tiene proyectos propios | `password` o `confirmation` | «Elimina tus proyectos antes de eliminar tu cuenta.» |
| Demasiados intentos | — | 429 |
