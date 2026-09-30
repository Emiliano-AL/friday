# Quickstart: Editor de contenido enriquecido en descripciones (011)

Guía de validación end-to-end. Detalles en
[contracts/editorjs-descriptions.md](./contracts/editorjs-descriptions.md) y
[data-model.md](./data-model.md).

## 0. Preparación

```bash
composer install && npm install   # instala los paquetes del editor (contrato §1)
php artisan migrate               # convierte descripciones/objetivos previos a bloques
php artisan serve &               # o Herd
npm run dev
```

## 1. Suite en verde

```bash
php artisan test --compact        # incluidos DescriptionMigrationTest y RichTextValidationTest
npm run check:fix && npm run types:check && npm run build
```

## 2. Descripción de tarea (US1)

1. Mis Tareas → `C` → escribir descripción con encabezado, lista, negrita, un
   enlace `https://` y una cita.
2. Guardar y reabrir la tarea: el editor muestra exactamente los mismos bloques.
3. Verificar una tarea creada **antes** de la feature: su descripción aparece
   como párrafo normal (sin pasos manuales).
4. Guardar con descripción vacía: se permite (campo opcional).
5. "Crear otra al guardar": el editor queda limpio tras guardar.
6. En la pestaña Sprints del proyecto, comprobar que ya **no** aparece la nota
   "barra de herramientas de marcado… próximamente" en el diálogo de tarea.

## 3. Proyecto y sprint (US2–US3)

1. Proyecto → editar descripción con lista y enlace → la ficha renderiza
   formateado (encabezados/listas/enlaces visibles, no etiquetas crudas).
2. Sprint → crear/editar objetivo con negritas y una lista → el detalle del
   sprint muestra el objetivo formateado bajo el encabezado.
3. Proyectos/sprints con contenido previo de texto plano: se ven como párrafo.

## 4. Seguridad (SC-004)

1. En una descripción, intentar pegar/insertar `"><img src=x onerror=alert(1)>`
   y un enlace `javascript:alert(1)` (vía edición del DOM si el editor lo
   rechaza): al guardar y renderizar, **no** se ejecuta nada; el enlace
   `javascript:` queda neutralizado o despojado.
2. `blocksToHtml` debe escapar el contenido no permitido: verificar en
   proyecto/sprint que el texto se muestra literal.

## 5. Validación y límites (FR-004)

1. Enviar un contenido de > 50000 caracteres (script o repetición): el
   formulario muestra el error en línea y no guarda.
2. Guardado JSON normal: persiste y reabre igual (regresión).

## 6. Migración (FR-002 / SC-002)

```bash
php artisan migrate:fresh --seed    # o contra copia de datos con texto plano
php artisan migrate                 # la migración de la feature corre de nuevo
```

Verificar: filas con texto plano quedan como `{"blocks":[{"type":"paragraph",…}]}`
con el texto exacto; valores JSON válidos intactos; `NULL` sin cambios;
correr dos veces → sin cambios la segunda (idempotencia, cubierta por tests).

## 7. Regresión y polish

- Suites de Tasks/Projects/Sprints/Auth/Shell verdes; listas y tarjetas sin
  render de descripción; editor a 375px usable (sin overflow de página);
  recorrido de teclado del diálogo intacto (Tab dentro del editor no rompe ESC
  ni el cierre).
- Consola del navegador sin errores (`browser-logs`).

## 8. Checklist de cierre

- [ ] §1–§7 validados; gates en verde; commit final de la feature.
