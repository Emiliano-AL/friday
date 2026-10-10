# Quickstart: validación de la sustitución EditorJS → Tiptap

**Propósito**: guía de validación end-to-end de la feature. Prerequisitos, comandos y escenarios esperados. Detalles de diseño en [plan.md](./plan.md), [research.md](./research.md) y [contracts/tiptap-rich-text.md](./contracts/tiptap-rich-text.md).

## Prerequisitos

- Repo en `/Users/elanda/Herd/friday` con PHP 8.4 y Node/npm disponibles.
- Base de datos de desarrollo con las migraciones aplicadas (incluida la histórica `2026_09_30_000000_convert_long_text_fields_to_block_content`, cuyo efecto esta feature revierte en datos).

## Setup

```bash
# 1. Dependencias: quitar EditorJS, añadir Tiptap v3
npm uninstall @editorjs/editorjs @editorjs/code @editorjs/header @editorjs/inline-code @editorjs/list @editorjs/marker @editorjs/quote
npm install @tiptap/vue-3 @tiptap/pm @tiptap/starter-kit @tiptap/extensions

# 2. Migración de datos (bloques EditorJS → texto plano)
php artisan migrate

# 3. Compilación y tipado
npm run build          # o el script de build del proyecto (vp)
npx vue-tsc --noEmit   # typecheck
```

**Resultado esperado**: instalación sin vulnerabilidades bloqueantes, migración exitosa, build y typecheck sin errores.

## Validación automatizada

```bash
php artisan test --compact                                # suite completo
php artisan test tests/Feature/RichText                    # área afectada
```

**Esperado**: suite verde. En concreto:
- `BlockContentToPlainTextMigrationTest` (nuevo): convierte bloques → texto plano en las 3 columnas, es idempotente, respeta `null` y valores ya planos.
- `RichTextValidationTest`: los payloads con JSON con shape de doc Tiptap y texto plano se persisten; los límites `max:50000` siguen aplicando.
- `DescriptionMigrationTest` (histórico): sigue pasando sin cambios.

**Comprobación de remoción total** (FR-001, SC-004):

```bash
grep -ri "@editorjs" package.json resources/     # 0 coincidencias
grep -ri "editorjs" resources/js/                # 0 coincidencias
ls resources/js/Components/RichText/             # solo RichTextEditor.vue y RichTextContent.vue
ls resources/js/utils/                           # sin blocks.ts; con tiptapContent.ts
```

## Escenarios manuales (app en `composer run dev` / Herd)

Preparación: crear o localizar un proyecto, un sprint y una tarea con descripción (los existentes quedaron como texto plano tras la migración).

1. **Editar descripción de tarea con formato (US1)**: abrir el modal de la tarea → aplicar H2, negrita, lista y cita → guardar → reabrir: el contenido aparece igual. En `storage/logs/laravel.log` y consola del navegador no hay errores.
2. **Goal de sprint (US1)**: editar el goal del sprint con formato → guardar → verificar en la cabecera del sprint.
3. **Descripción de proyecto (US1)**: editar la descripción del proyecto → guardar → verificar en la página de detalle del proyecto.
4. **Contenido heredado (US2/FR-004)**: abrir una tarea cuya descripción existía antes del cambio → debe mostrarse como texto plano legible → editarla y guardar: a partir de ahí queda en formato Tiptap.
5. **Solo lectura (US2)**: en detalle de proyecto y cabecera de sprint, el contenido con formato se renderiza estructurado; los enlaces abren en pestaña nueva (`noopener`).
6. **Vaciado (FR-003)**: borrar todo el contenido y guardar → se persiste vacío; al reabrir, el placeholder aparece y no hay errores de validación.
7. **Errores inline (FR-008)**: forzar un error de validación del campo → el mensaje aparece junto al editor y el borde pasa a `border-error`.
8. **Seguridad (FR-009)**: intentar crear un enlace con URL `javascript:alert(1)` → el editor no lo convierte en enlace (o lo rechaza); pegar HTML con `<script>` → no se ejecuta nada y el contenido visible es solo el texto.
9. **"Crear otra" en tareas**: en el modal de creación, activar "crear otra", guardar → el formulario se resetea incluido el editor (regresión del quirk actual).
10. **Alturas (paridad UI)**: el modal de tarea mantiene `min-height` 240 y scroll interno con descripciones largas (`max-h-[420px]`); proyectos y sprints conservan el alto por defecto.

## Criterio de cierre

- [ ] Suite completo de tests verde (`php artisan test --compact`).
- [ ] Build y typecheck sin errores.
- [ ] Cero referencias a `@editorjs` en `package.json` y `resources/`.
- [ ] Los 10 escenarios manuales verificados.
- [ ] Sin mensajes de consola del navegador en los flujos 1–9 (usar las herramientas de logs del navegador de Boost si aparecen).
