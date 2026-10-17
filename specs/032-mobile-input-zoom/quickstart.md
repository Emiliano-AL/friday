# Quickstart: verificación manual

## Prerrequisitos

- `npm run dev` (o `npm run build`) en ejecución.
- iPhone con Safari en la misma red, o el Simulador de iOS de Xcode con Safari (`open -a Simulator`).
- Herramientas de desarrollo de Chrome en modo dispositivo, para revisar que los estilos computados apliquen. **No** reproduce el zoom de iOS.

## Escenarios

| # | Pantalla / campo | Acción | Resultado esperado |
|---|------------------|--------|--------------------|
| 1 | Login / registro / restablecer contraseña | Tocar correo y contraseña | Sin zoom; sin scroll horizontal |
| 2 | Backlog → captura rápida | Tocar el input | Sin zoom |
| 3 | Tablero → filtros, selects | Tocar input y abrir select | Sin zoom |
| 4 | Modal de tarea → título | Tocar el título | Sin zoom; el título sigue en 20 px |
| 5 | Modal de tarea / sprint → descripción (editor enriquecido) | Tocar el editor | Sin zoom |
| 6 | Comentarios, paleta de comandos, miembros, adjuntos | Tocar cada campo | Sin zoom |
| 7 | Cualquier pantalla en móvil | Pellizco para acercar | El zoom manual funciona |
| 8 | Escritorio (≥ 768 px) | Inspeccionar campos | `font-size` computado igual que antes (14 px en la mayoría) |
| 9 | Checkbox / selector de archivo | Inspeccionar | Sin cambios de aspecto |

## Comprobación rápida en DevTools

En modo dispositivo con un ancho menor a 768 px, ejecutar en la consola:

```js
[...document.querySelectorAll('input, textarea, select, [contenteditable="true"]')]
  .filter(el => el.offsetParent && parseFloat(getComputedStyle(el).fontSize) < 16)
```

Resultado esperado: `[]`, salvo checkboxes, radios o archivos.
