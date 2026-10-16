# Contract: Branding público

## 1. Prop compartida de Inertia

Todas las respuestas Inertia (con o sin sesión) incluyen:

```ts
sharedPageProps.name: string // === config('app.name'), 'Eunox' por defecto
```

Los componentes que nombran el producto **deben** leer esta prop y no escribir el nombre literal.

## 2. Textos de UI (con `{name}` = prop `name`)

| Ubicación | Texto |
| --- | --- |
| Barra lateral (AppShellSidebar) | `{name}` |
| Encabezado de auth (AuthHeader) | `{name} Cloud OS` |
| Login | `Iniciar sesión en {name}` |
| Registro | `Crear cuenta en {name}` |
| Restablecer contraseña | `Elige una contraseña segura para tu cuenta de {name}.` |
| Pie del inicio (HomeCanvas) | `{name} v2.4` |
| Modal de miembros | `Si el correo no tiene cuenta en {name}, …` |
| Página de invitación | Botón `Ir a {name}` |

## 3. Título de pestaña

- Con título de página: `"<Título> - Eunox"`
- Sin título: `"Eunox"`

## 4. Correos

| Campo | Valor |
| --- | --- |
| From name | `Eunox` (`MAIL_FROM_NAME` → `APP_NAME`) |
| Asunto de invitación | `"<Invitador> te invitó a <Proyecto> en Eunox"` |
| Cuerpo de invitación | `… en el proyecto **<Proyecto>** en Eunox.` y la firma `Eunox` |
| Correos por defecto (reset y verificación) | Encabezado y pie del layout con `Eunox` |

## 5. Invariantes

- No aparece la palabra `Friday` (con mayúscula) en `resources/js`, `resources/views` ni `app/Mail`.
- Los identificadores en minúscula `friday:*` y `.friday-focus-scope` se conservan.
