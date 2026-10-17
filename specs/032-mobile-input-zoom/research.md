# Research: Evitar el zoom automático al enfocar campos en móvil

Referencia: https://wsform.com/knowledgebase/why-forms-zoom-on-some-mobile-devices-and-browsers-and-how-to-control-it/

## 1. Causa del zoom

- **Decision**: Tratar el tamaño de texto del campo como causa raíz.
- **Rationale**: Safari en iOS (y todos los navegadores de iOS, que usan WebKit) hace zoom automático al enfocar un campo editable cuyo `font-size` computado es menor a 16 px. Con 16 px o más no hay zoom.
- **Alternatives considered**: Ninguna; es el comportamiento documentado del navegador.

## 2. Cómo evitarlo

- **Decision**: Subir el texto de los campos a 16 px solo en pantallas móviles.
- **Rationale**: Corrige la causa, mantiene el zoom manual (accesibilidad, WCAG 1.4.4) y deja escritorio intacto.
- **Alternatives considered**:
  - `maximum-scale=1` / `user-scalable=no` en el meta viewport: rechazada. Bloquea el pellizco en Android y en algunos contextos (FR-003); la referencia lo desaconseja por accesibilidad.
  - Subir a 16 px también en escritorio: rechazada por FR-004.
  - `font-size` 16 px + `transform: scale()` para simular 14 px: rechazada, es frágil y desalinea los layouts.

## 3. Ubicación de la regla con Tailwind v4

- **Decision**: CSS sin capa al final de `resources/css/app.css`.
- **Rationale**: En Tailwind v4 las clases `text-*` viven en la capa de cascada `utilities`. Las declaraciones sin capa ganan a cualquier capa, así que una sola regla global sobrescribe todos los `text-sm` de los campos sin editar cada componente (FR-005).
- **Alternatives considered**:
  - `@layer base`: rechazada, los utilities le ganan y los campos seguirían en 14 px.
  - Agregar `max-md:text-base` a cada campo: rechazada, no cubre campos futuros y toca ~19 archivos.

## 4. Breakpoint

- **Decision**: `@media (max-width: 767px)`.
- **Rationale**: Coincide con el breakpoint `md` (48rem) que la app ya usa para separar móvil de escritorio.

## 5. Campos con texto mayor a 16 px

- **Decision**: Excepción explícita con el modificador important de Tailwind v4 (`text-headline-md!`) en el único caso existente (título en `TaskFormModal`).
- **Rationale**: CSS no permite expresar `max(16px, tamaño actual)` cuando el tamaño viene de otra regla. Un solo caso no justifica un mecanismo genérico.

## 6. Editor enriquecido

- **Decision**: Override con media query dentro del `<style scoped>` de `RichTextEditor.vue`.
- **Rationale**: Su selector scoped (`.rich-text-editor__content :deep(.tiptap)`) fija 14 px en un `div` contenteditable que la regla global de inputs no alcanza. iOS aplica el mismo umbral de 16 px a contenteditable.
