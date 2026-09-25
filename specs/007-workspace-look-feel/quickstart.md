# Quickstart: Look & Feel del workspace autenticado

**Feature**: 007-workspace-look-feel
**Date**: 2026-09-25
**Purpose**: Guía de validación end-to-end. Ejecuta estos escenarios para demostrar que el feature cumple el spec. No incluye código de implementación — ver [plan.md](./plan.md), [data-model.md](./data-model.md) y [contracts/shell-ui.md](./contracts/shell-ui.md).

## Prerequisitos

- Dependencias instaladas: `composer install` y `npm install` (ya registradas en el repo).
- App servida: `composer run dev` (o `npm run dev` + Herd). URL local típica: `https://friday.test` (verificar con `php artisan route:list` / Herd).
- Usuario de prueba registrado (vía `/register`, correo+contraseña o Google). Ideal: un usuario **sin proyectos** y otro **con al menos 2 proyectos** para cubrir estados del selector de contexto.

## 1. Baseline automatizado (antes de empezar y al final)

```bash
php artisan test --compact        # suite completa en verde (SC-002)
npm run build                     # build de producción sin errores de tipo/estilo
npm run types:check               # vue-tsc sin errores
vendor/bin/pint --dirty --format agent   # formato Pint limpio en archivos PHP tocados
```

Esperado: suite completa verde, build y chequeo de tipos sin errores.

## 2. Validación del shell (US1 - navegación)

1. Inicia sesión. **Esperado**: barra lateral (logo "Friday", selector de contexto, "Nueva Tarea" con hint `C`, Panel/Proyectos/Mis Tareas/Sprints/Backlog/Ajustes, tarjeta de usuario abajo), encabezado (buscador "Buscar o teclear comando..." con `⌘K`, campana, ayuda, avatar) y área de contenido. Panel activo.
2. Haz clic en **Proyectos**. **Esperado**: navega a proyectos; "Proyectos" queda activo; URL en `match` del item.
3. Haz clic en **Mis Tareas / Sprints / Backlog / Ajustes / Detalles del perfil / Ajustes de cuenta**. **Esperado**: se ven deshabilitados con indicador "Próximamente"; no navegan ni disparan peticiones (verificar en DevTools → Network).
4. Colapsa la barra (botón en la barra o en el encabezado). **Esperado**: se oculta con animación; el contenido ocupa el ancho; repetir la acción la restablece.
5. Entra directo por URL a `/projects`. **Esperado**: "Proyectos" aparece activo sin haber navegado desde la barra.

## 3. Validación de cuenta (US2)

1. Haz clic en el avatar del encabezado. **Esperado**: menú con nombre, correo, "Detalles del perfil" y "Ajustes de cuenta" (deshabilitados, "Próximamente") y "Cerrar sesión".
2. Haz clic fuera o presiona `ESC`. **Esperado**: el menú se cierra.
3. Haz clic en **Cerrar sesión**. **Esperado**: sesión cerrada → redirect a `/login`.
4. Usuario sin foto de perfil. **Esperado**: iniciales sobre fondo de acento en tarjeta, avatar y menú.
5. Correo/nombre largo. **Esperado**: truncado con ellipsis, sin desbordar.

## 4. Validación de comandos y notificaciones (US3)

1. Presiona `⌘K` (Mac) o `Ctrl+K` (otros). **Esperado**: paleta centrada con velo, campo de búsqueda y acciones sugeridas.
2. Teclea "pro". **Esperado**: la lista se filtra (match en etiqueta/keywords).
3. Selecciona "Ir a Proyectos" (`↑`/`↓` + `Enter` o clic). **Esperado**: navega a proyectos; la paleta se cierra.
4. Presiona `C` (fuera de inputs) o haz clic en **Nueva Tarea**. **Esperado**: se abre la paleta.
5. `ESC` o clic en el velo. **Esperado**: paleta cierra y el foco vuelve al disparador.
6. Haz clic en la campana. **Esperado**: popover "Sin notificaciones"; **sin** punto de no leídos, sin datos de ejemplo.
7. En un formulario de login de otra pestaña no aplica, pero dentro de la app: con foco en un campo de texto, presiona `C`. **Esperado**: no abre la paleta (el atajo se ignora en inputs).

## 5. Validación de la página de inicio (FR-010)

1. Ve a `/` (home). **Esperado**: lienzo con migas "Mi Espacio / Lienzo en Blanco", chip "Borrador", botones "Comandos rápidos" y "Añadir bloque", mensaje "Espacio listo para crear", tres tarjetas de acción sugerida, e indicador "Sincronizado en la nube • Friday v2.4" abajo a la derecha.

## 6. Selector de contexto y proyectos dentro del shell

1. Usuario con proyectos: haz clic en el selector. **Esperado**: lista de proyectos; elegir uno navega a su ficha y lo marca como actual.
2. Usuario sin proyectos: **esperado** estado vacío con llamada a crear el primer proyecto.
3. Recorre `/projects`, `/projects/create`, ficha de un proyecto. **Esperado**: todas renderizan dentro del nuevo shell (sin el layout viejo) y su funcionalidad actual sigue intacta (crear/editar/eliminar como antes).

## 7. Responsive y accesibilidad (FR-006/FR-007, SC-004/SC-005/SC-006)

1. 1280px: shell completo, barra fija de ~256px. 800px: barra como drawer con velo, botón de menú en el encabezado. 375px y 320px: un columnado, sin scroll horizontal ni regiones superpuestas.
2. Navegación solo con teclado (`Tab`/`Shift+Tab`): **esperado** foco visible con anillo de acento en todos los controles; orden de foco lógico; `ESC` cierra lo abierto.
3. `⌘K` → `Tab` cicla dentro de la paleta; `ESC` devuelve el foco al disparador.
4. Contraste: texto sobre `surface`/`surface-container-lowest` legible (AA); verificar con las DevTools de contraste.
5. Si algo falla en frontend: revisar `mcp__laravel-boost__browser-logs` (regla operativa del proyecto) antes de instrumentar.

## Resultado esperado

- Escenarios 2–7 pasan en modo claro con paridad visual frente a `screen.png` (SC-001).
- `php artisan test --compact` completo en verde tras los cambios (SC-002), incluido el nuevo `tests/Feature/Shell/ShellRenderingTest.php`.
