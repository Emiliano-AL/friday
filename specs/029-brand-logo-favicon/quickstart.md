# Quickstart: validar el logo de Eunox (029)

## Prerrequisitos

- App local en `https://friday.test` y `npm run dev` corriendo.
- Google Chrome y Python 3 con Pillow (solo para regenerar los íconos).

## 1. Regenerar los íconos (solo si cambia el logo)

```bash
python3 -I scripts/brand/generate-icons.py
```

Esperado: se reescriben los archivos listados en
[data-model.md](./data-model.md) y el script imprime las dimensiones de cada
uno.

## 2. Favicon (US1, SC-001, SC-002, SC-006)

1. Abre `https://friday.test/`, `/login` y el dashboard en una ventana
   privada. Esperado: la pestaña muestra el cuadro índigo con el símbolo
   blanco en las 3 páginas.
2. Abre `https://friday.test/favicon.ico` y `/favicon.svg`. Esperado: el
   isotipo de Eunox, no el de Laravel. A 16 px se distinguen los dos bloques
   y el punto.

## 3. Pantalla de inicio (US2, SC-003)

1. Abre `/apple-touch-icon.png`, `/icon-192.png` e `/icon-512.png`. Esperado:
   cuadro índigo full-bleed con el símbolo centrado y nítido.
2. Después del deploy, en un iPhone (Safari) y en un Android (Chrome), usa
   "Agregar a pantalla de inicio" desde `www.eunox.app`. Esperado: ícono y
   nombre "Eunox", y la barra del navegador en Android de color índigo.

## 4. Pantallas de auth (US3, SC-004)

1. Abre `/login`, `/register` y `/forgot-password`. Esperado: la tarjeta
   muestra el logo grande en cuadro índigo sólido, y el encabezado muestra el
   logo pequeño y "Eunox".
2. Haz clic en el logo del encabezado. Esperado: vas a la landing (`/`).

## 5. Correos (US4, SC-005)

1. Pruebas automatizadas (siguiente sección).
2. Después del deploy, envía una invitación a tu correo. Esperado: en Gmail
   web y Apple Mail, el encabezado muestra el logo y "Eunox". Con imágenes
   bloqueadas se ve "Eunox" en texto.

## 6. Pruebas automatizadas

```bash
php artisan test --compact tests/Feature/Branding tests/Feature/Mail
npx vue-tsc --noEmit
```

Esperado: todo en verde.
