# Quickstart: Validación de identidad y fecha de término del proyecto

**Fecha**: 2026-10-09 | **Feature**: [spec.md](spec.md) | **Contrato**: [contracts/project-identity.md](contracts/project-identity.md)

Guía de validación end-to-end. Prerequisitos: entorno instalado (`composer
install`, `npm install`), BD migrada (`php artisan migrate`), assets compilados
(`npm run build` o `npm run dev`), servidor disponible (Herd: `friday.test`).

## 1. Validación automática

```bash
php artisan migrate               # nueva migración de 4 columnas
php artisan test --compact tests/Feature/Projects tests/Feature/Shell
vendor/bin/pint --dirty
npm run build && npm run types:check
php artisan test --compact        # suite completa antes de cerrar
```

Tests clave (fase de implementación):
- `tests/Feature/Projects/ProjectCrudTest.php` (+): guardado completo con
  normalización a mayúsculas; rechazo de clave duplicada del mismo propietario
  (create y update ignore-self); misma clave de otro propietario válida;
  validaciones de clave (longitud/regex), color e icono fuera de lista, fecha
  invposible; fecha vacía en update → `null`; no-propietario → 404.
- `tests/Feature/Shell/ShellRenderingTest.php` (+ aditivo): `projects.0.color` e
  `projects.0.icon` presentes en la prop compartida.
- `tests/Feature/Projects/ProjectShowPayloadTest.php` / `ProjectDirectoryTest.php`
  (+ aditivos): los 4 campos en `ProjectDetail` / `DirectoryProject`.

## 2. Escenarios manuales (navegador)

| # | Escenario | Pasos | Resultado esperado |
| --- | --- | --- | --- |
| 1 | Crear con identidad completa | Proyectos → Nuevo: nombre "Friday App", clave "frid" (minúsculas), color violeta, icono rocket, fecha de término futura | Proyecto creado; ficha muestra clave "FRID" (mayúscula), tile rocket sobre violeta tenue, y la fecha en "Fecha Objetivo". |
| 2 | Sin identidad | Crear otro proyecto solo con nombre | Ficha con tile por defecto (folder/estilos actuales), sin chip de clave, "Fecha Objetivo" = "Sin fecha". |
| 3 | Edición precarga | Abrir "Ajustes" del proyecto del escenario 1 | Formulario con clave, color, icono y fecha ya seleccionados. |
| 4 | Cambiar y quitar | Editar: cambiar clave a "FRAP", quitar la fecha, guardar | Ficha muestra "FRAP" y "Sin fecha". |
| 5 | Clave duplicada | Crear/editar con clave "FRAP" en otro proyecto **del mismo usuario** | Error junto al campo clave; nada se guarda. |
| 6 | Misma clave, otro propietario | Con otro usuario, crear proyecto con clave "FRAP" | Guarda sin conflicto. |
| 7 | Validaciones | Clave de 1 carácter / con guion; color imposible (vía consola o request); fecha 31-feb | Cada intento rechazado con mensaje junto a su campo. |
| 8 | Vencida | Editar fecha a una pasada | Ficha: badge "Vencida" junto a la fecha. |
| 9 | Permisos | Miembro (no propietario) abre la ficha | Ve clave/color/icono/fecha; el botón "Ajustes" no le permite editar (404 al intentar). |
| 10 | Navegación | Abrir el menú de proyectos del sidebar | Cada proyecto muestra su icono y color; el del escenario 2 con el default. |
| 11 | Proyecto antiguo | Ver un proyecto creado antes de la feature | Se ve normal con defaults, sin errores. |
| 12 | Persistencia | Recargar tras los cambios del escenario 4 | Todo lo guardado sigue mostrándose. |

## 3. Criterios de aceptación rápidos

- [ ] Suite completa en verde con los tests existentes intactos.
- [ ] Los 12 escenarios manuales pasan.
- [ ] `vendor/bin/pint --dirty`, `npm run build` y `npm run types:check` limpios.
- [ ] Ningún "Próximamente" queda en el bloque de identidad ni en la celda de
      fecha de la ficha.
