# Data Model: Logo de Eunox (029)

Esta feature **no introduce entidades, tablas ni migraciones**. Solo produce
archivos estáticos de marca.

## Activos de marca (archivos versionados)

| Archivo | Formato | Tamaño | Variante | Uso |
| ------- | ------- | ------ | -------- | --- |
| `public/favicon.svg` | SVG | 28 × 28 u | circular (rx 14) | Favicon moderno |
| `public/favicon.ico` | ICO | 16, 32, 48 px | redondeado, fondo transparente | Favicon clásico |
| `public/apple-touch-icon.png` | PNG | 180 × 180 | full-bleed, sin transparencia | Pantalla de inicio en iOS |
| `public/icon-192.png` | PNG | 192 × 192 | full-bleed | Manifiesto (Android) |
| `public/icon-512.png` | PNG | 512 × 512 | full-bleed | Manifiesto (Android, splash) |
| `public/images/eunox-mail-logo.png` | PNG | 96 × 96 (se muestra a 32) | redondeado | Encabezado de correos |
| `public/site.webmanifest` | JSON | — | — | Nombre, íconos y color de tema |

**Constantes de marca**: fondo `#5B5BD6` (`primary-container`), símbolo
`#FFFFFF` (`on-primary`), símbolo al 16/28 del lado, círculo (rx 14/28) en la variante
redondeada. La fuente de verdad es `scripts/brand/generate-icons.py` (D2).
