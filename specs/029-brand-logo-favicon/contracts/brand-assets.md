# Contract: Activos de marca y puntos de uso (029)

## `<head>` de todas las páginas (`resources/views/app.blade.php`)

```html
<link rel="icon" href="/favicon.ico" sizes="32x32">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
<meta name="theme-color" content="#5B5BD6">
```

## `public/site.webmanifest`

```json
{
  "name": "Eunox",
  "short_name": "Eunox",
  "icons": [
    { "src": "/icon-192.png", "sizes": "192x192", "type": "image/png", "purpose": "any maskable" },
    { "src": "/icon-512.png", "sizes": "512x512", "type": "image/png", "purpose": "any maskable" }
  ],
  "theme_color": "#5B5BD6",
  "background_color": "#5B5BD6",
  "display": "browser",
  "start_url": "/"
}
```

## Componente `AppLogo` (fuente única en la UI)

| Prop | Valores | Default | Render |
| ---- | ------- | ------- | ------ |
| `size` | `'sm'` \| `'lg'` | `'sm'` | `sm`: cuadro `h-7 w-7 rounded-lg`, símbolo `h-4 w-4`. `lg`: cuadro `h-12 w-12 rounded-xl`, símbolo `h-7 w-7` |

Siempre `bg-primary-container text-on-primary`, con el SVG `aria-hidden="true"`.

| Lugar | Uso |
| ----- | --- |
| `AppShellSidebar.vue`, `LandingHeader.vue`, `LandingFooter.vue` | `<AppLogo />` (sin cambios) |
| `AuthCard.vue` | `<AppLogo size="lg" />` (reemplaza la copia en línea) |
| `AuthHeader.vue` | `<Link :href="home()">` con `<AppLogo />` y el nombre (reemplaza "Cloud OS") |

## Encabezado de correos (`resources/views/vendor/mail/html/header.blade.php`)

- Recibe `$url` y `$slot` (nombre de la app), igual que el componente del
  framework.
- Renderiza el enlace a `$url` con
  `<img src="{asset('images/eunox-mail-logo.png')}" width="32" height="32" alt="{app.name}">`
  y el nombre en texto.
- Aplica a todo correo markdown (`ProjectInvitationMail` y
  `ResetPasswordNotification`).
