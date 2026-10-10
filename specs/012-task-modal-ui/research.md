# Research: Modal de Tarea Mejorado (spec 012)

**Fecha**: 2026-09-30 | **Feature**: [spec.md](spec.md)

Investigación sobre el estado actual del código para resolver los unknowns técnicos
del plan. Fuentes: `TaskFormModal.vue`, `RichTextEditor.vue`, `Modal.vue`,
`package.json`, `resources/css/app.css`, `DESIGN.md`, `tests/Feature/**`.

## D1. Secciones colapsables — mecanismo

**Decision**: Crear un componente local `CollapsibleSection.vue` (Vue 3 `<script setup>`
+ TypeScript) que envuelve su contenido en un acordeón animado con
`<Transition>` de Vue y la técnica `grid-template-rows: 0fr → 1fr` (valores
arbitrarios de Tailwind, sin keyframes CSS). El encabezado completo es un
`<button>` enfocable con `aria-expanded` / `aria-controls`, icono `expand_more`
de `AppIcon` que rota 180° al abrir, e indicadores "Mostrar"/"Ocultar".

**Rationale**: No existe ningún componente de colapso/acordeón en el proyecto
(investigación exhaustiva: sin `<details>`, sin max-height transitions, sin
rotate-180). El estilo house para toggles es `usePopover` (`TaskRow.vue`) con
`:aria-expanded`, que se imita. Vue `<Transition>` es el patrón de animación
existente (`Modal.vue`); no hay keyframes ni utilidades de animación en el CSS
del proyecto (Tailwind v4 `@theme` en `app.css`). La técnica grid-rows evita los
"números mágicos" de max-height del HTML de referencia de Stitch y anima a la
altura real del contenido.

**Alternatives considered**:
- *max-height CSS como el prototipo Stitch*: rechazada — requiere acotar la
  altura máxima a mano y se rompe con contenido más alto; menos elegante que la
  transición de grid-rows.
- *Elemento nativo `<details>`/`<summary>`*: rechazada — sin animación de
  apertura/cierre y menos control de estilos para cumplir SC-005.
- *Dependencia de componentes UI (headlessui, etc.)*: rechazada — viola la
  Constitución II (no se agregan dependencias sin aprobación).

## D2. Altura del área de descripción

**Decision**: Ampliar el `RichTextEditor` con dos props opcionales:
`minHeight?: number` (se inyecta en la config `minHeight` de EditorJS, hoy
fijada en 120) y `maxHeightClass?: string` (clase aplicada vía `:deep()` sobre
`.codex-editor` con `overflow-y: auto`). `TaskFormModal.vue` pasa
`minHeight: 240` y una clase de altura máxima (p. ej. `max-h-[420px]`), de modo
que el área muestra ~10 líneas y recorre el excedente con scroll interno. Los
demás consumidores (`TaskModal.vue`, `ProjectFormModal.vue`,
`SprintFormModal.vue`) quedan sin cambios de comportamiento (defaults).

**Rationale**: EditorJS v2 soporta `minHeight` nativo; el max-height con scroll
en `.codex-editor` es la forma estándar de acotar el holder (el contenedor
`.codex-editor` es el elemento que crece indefinidamente hoy — no hay ningún
constraint de altura en el CSS actual). Props opcionales evitan efectos
colaterales globales y respetan YAGNI (Constitución V).

**Alternatives considered**:
- *Reemplazar EditorJS por `<textarea>` plano (como sugiere el HTML de Stitch)*:
  rechazada — el spec asume explícitamente conservar el editor enriquecido; se
  perderían bloques (headers, listas, código) y la validación existente de
  descripción (≤ 50 000 chars) seguiría, pero el contenido JSON dejaría de
  generarse, regresión silenciosa de la spec 011.
- *Paquete de autosize/textarea*: rechazada — dependencia nueva innecesaria.
- *Cambiar la altura global del editor para todos los consumidores*: rechazada
  — fuera del alcance del spec y cambia superficies no objetivo sin decisión de
  diseño.

## D3. Scroll del cuerpo y pie anclado del diálogo

**Decision**: Reestructurar el interior de `TaskFormModal.vue` (sin tocar
`Modal.vue`): el panel interno se organiza como columna con altura máxima
(`max-h-[85vh]`), el encabezado queda fijo arriba, el cuerpo del formulario
(envuelto en un contenedor `flex-1 overflow-y-auto min-h-0`) se recorre con
scroll propio, y el pie de acciones pasa a ser el último elemento de la columna
(`shrink-0`, con `border-t`), siempre visible.

**Rationale**: `Modal.vue` tiene un único slot default y su panel crece con el
contenido; hoy el pie está dentro del `<form>` y se desplaza fuera de la
vista con contenido largo (confirmado en el código). Mantener `Modal.vue`
intocable evita impactar a todos sus consumidores. SC-001/SC-002/SC-003 exigen
acciones visibles sin scroll de página.

**Alternatives considered**:
- *Añadir slots header/body/footer a `Modal.vue`*: rechazada — toca todos los
  consumidores existentes; YAGNI (Constitución V).
- *Mantener el scroll a nivel de página (estado actual)*: rechazada — el pie y
  las acciones se pierden fuera del viewport, contradice el spec.
- *Altura máxima en el panel de `Modal.vue`*: innecesaria — basta con el
  contenedor interno del formulario de tareas.

## D4. Estado de colapso y errores de validación

**Decision**: El estado expandido/colapsado vive en refs locales dentro de
`TaskFormModal.vue`, reiniciado en el `watch` de apertura existente
(`FR-005`: valor por defecto al abrir; "Vinculación y alcance" abierta solo si
la tarea trae proyecto o sprint). Para `FR-006`, un watcher sobre
`form.errors` expande automáticamente la sección que contiene un campo con
error (hoy: `project_id`/`sprint_id` → sección de vinculación; los campos de
estimación están deshabilitados y no generan errores).

**Rationale**: Estado efímero de UI, sin persistencia según el spec; el patrón
watch + refs es el ya usado en el componente (`createAnother`, `watch(open)`).

**Alternatives considered**: *Persistir preferencia de colapso* (localStorage):
rechazada — `FR-005` lo prohíbe explícitamente.

## D5. Estrategia de pruebas

**Decision**: No se agregan tests nuevos. La regla del proyecto (reglas Pest)
establece que cambios puramente de UI/estilos no requieren tests nuevos, y no
hay suite browser (sin Dusk). La verificación es: (a) suíte Pest existente de
tareas y rich text como regresión (`tests/Feature/Tasks`, `tests/Feature/RichText`),
(b) `npm run types:check` (vue-tsc) y `npm run build`, (c) validación manual
guiada en `quickstart.md`.

**Rationale**: No hay lógica de dominio nueva ni cambios de servidor; el
contrato HTTP de tareas no cambia. El riesgo es regresión visual/UX, que las
feature tests backend no capturan de todos modos.

**Alternatives considered**: *Tests browser con Dusk*: rechazada — no existe en
el proyecto y agregarlo es cambio de stack (Constitución II).

## D6. Alcance del rediseño (qué modales)

**Decision**: El rediseño aplica únicamente a
`resources/js/Components/Tasks/Board/TaskFormModal.vue` (modos crear y editar),
que es el diálogo usado por backlog, kanban y detalle de sprint (spec 010,
FR-005). `resources/js/Components/Tasks/TaskModal.vue` (modal legacy de edición
inline ligado a rutas de proyecto, estilo Breeze antiguo) queda fuera de
alcance.

**Rationale**: El spec habla de "el diálogo de crear y de editar tarea" y su
referencia visual es la pantalla de Stitch "Editar tarea"; `TaskFormModal.vue`
es ese diálogo. `TaskModal.vue` pertenece a una superficie legacy distinta y
tocarlo ampliaría el alcance sin valor definido (YAGNI).

**Alternatives considered**: *Actualizar ambos modales*: rechazada — doble
superficie sin requisito; riesgo de inconsistencia al conservar dos diálogos de
edición.

## D7. Mapeo visual Stitch → tokens del proyecto

**Decision**: No se crean tokens nuevos. El HTML de Stitch usa escala
`slate`/`brand` propia; el plan mapea al sistema existente
(`resources/css/app.css`, Tailwind v4 `@theme` sobre `DESIGN.md`): superficie de
sección `bg-surface-container-low/80 border-outline-variant/30 rounded-xl`,
encabezados `text-label-xs uppercase tracking-wider`, espaciados `space-*`,
radio `radius-*`, sombra `shadow-modal` ya presente en `Modal.vue`, botón
destructivo con tokens `error`/`error-container`. El badge "Próximamente" se
conserva con `UiBadge` y "Proyecto opcional" igual.

**Rationale**: Constitución II + convención documentada en DESIGN.md; el HTML
de Stitch es referencia de layout e interacción, no de tokens.
