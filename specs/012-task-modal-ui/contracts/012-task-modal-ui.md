# UI Contract: Modal de Tarea Mejorado (spec 012)

**Fecha**: 2026-09-30 | **Feature**: [spec.md](spec.md) | **Plan**: [plan.md](plan.md)

Contrato de interfaz de usuario para el rediseño del diálogo de crear/editar
tarea. Cubre la API pública del componente de tarea, el nuevo componente de
acordeón y las garantías de comportamiento. No hay contratos HTTP nuevos: el
formulario sigue enviando a las rutas existentes `tasks.store` / `tasks.update`
/ `tasks.destroy` (Wayfinder `@/routes/tasks`) con el mismo payload.

## 1. `CollapsibleSection.vue` (nuevo)

Componente de sección colapsable accesible y animado.

**Props**

| Prop         | Tipo      | Default  | Descripción                                                      |
| ------------ | --------- | -------- | ---------------------------------------------------------------- |
| `title`      | `string`  | — (req.) | Etiqueta del encabezado (mayúsculas, tracking amplio por estilo) |
| `icon`       | `string`  | — (req.) | Nombre de icono del sistema (`AppIcon`)                          |
| `open`       | `boolean` | `false`  | Estado de expansión, **controlado por el padre** (`v-model:open` / `defineModel`) — habilita el reinicio por apertura (FR-005) y el auto-expand por errores (FR-006) |
| `toggleHint` | `boolean` | `true`   | Muestra los indicadores "Mostrar"/"Ocultar" junto al chevron     |

**Slots**

| Slot      | Descripción                                              |
| --------- | -------------------------------------------------------- |
| `default` | Contenido colapsable (campos del formulario)             |
| `badge`   | Insignicia opcional junto al título (p. ej. "Próximamente", "Proyecto opcional") |

**Contrato de comportamiento**

- El encabezado completo es un único `<button>` enfocable; expone
  `aria-expanded` (true/false) y `aria-controls` apuntando al contenedor del
  contenido.
- El componente es controlado: el estado expandido lo posee el padre
  (`v-model:open`), que puede forzar la apertura (errores de validación,
  `FR-006`) y restablecer el estado en cada apertura del diálogo (`FR-005`).
- Al activarlo se despliega/repliega el contenido con una transición suave
  (`<Transition>` de Vue, técnica grid-rows; duración percibida < 0,5 s).
- El icono del encabezado rota 180° cuando está expandido; el hint alterna
  "Mostrar"/"Ocultar".
- Colapsar nunca modifica ni descarta el contenido de los campos internos
  (`FR-004`).

## 2. `RichTextEditor.vue` (ampliado, contrato aditivo)

Se conserva la API existente (`modelValue`, `placeholder`, `error`, `id`;
`save()`, `render()`, `isEmpty()`, `focus()` expuestos) y se añaden:

| Prop             | Tipo     | Default | Descripción                                                                 |
| ---------------- | -------- | ------- | --------------------------------------------------------------------------- |
| `minHeight`      | `number` | `120`   | Altura mínima del área de edición (config `minHeight` de EditorJS)          |
| `maxHeightClass` | `string` | `''`    | Clase aplicada sobre `.codex-editor`; cuando se informa, añade scroll interno (`overflow-y: auto`) |

**Garantía**: los consumidores existentes que no pasen las props nuevas
conservan el comportamiento actual.

## 3. `TaskFormModal.vue` (contrato público inalterable)

**Props y eventos**: sin cambios — `open`, `mode: 'create' | 'edit'`, `task?`,
`projects`, `currentUser`, `initialStatus?`, `initialProject?`,
`initialSprint?`; evento `close`. Ningún consumidor (Tasks/Index, Projects/Show,
Sprints/Show) requiere modificaciones.

**Garantías de comportamiento**

| #   | Garantía                                                                                                                                | Spec |
| --- | --------------------------------------------------------------------------------------------------------------------------------------- | ---- |
| G1  | Título, datos principales (tipo, estado, prioridad, responsable) y descripción siempre visibles sin scroll en viewport estándar        | SC-001 |
| G2  | "Vinculación y alcance" y "Estimación y fechas" son acordeones (`CollapsibleSection`), colapsadas por defecto                          | FR-001–FR-003 |
| G3  | "Vinculación y alcance" inicia expandida si la tarea tiene proyecto o sprint; el estado de colapso se reinicia en cada apertura         | FR-003, FR-005 |
| G4  | Colapsar/reexpandir conserva los valores; guardar envía los valores de secciones colapsadas                                             | FR-004 |
| G5  | Un error de validación en `project_id`/`sprint_id` expande automáticamente su sección y muestra el mensaje junto al campo              | FR-006 |
| G6  | El área de descripción muestra ~10 líneas (mín. 240 px) y recorre el excedente con scroll interno sin empujar las acciones fuera       | FR-007, SC-003 |
| G7  | El pie es una sola fila: izquierda "Eliminar tarea" (edición, con confirmación) o casilla "Crear otra tarea al guardar" (creación); derecha "Cancelar" + acción primaria; no existe caja independiente de eliminación | FR-009 |
| G8  | Atajo Ctrl/Cmd+Enter para guardar, estado de procesamiento en la acción primaria y preselecciones por contexto se conservan             | FR-010 |
| G9  | Los badges "Proyecto opcional" y "Próximamente" se conservan; los campos de estimación siguen deshabilitados sin datos simulados        | FR-011 |
| G10 | El cuerpo del diálogo tiene scroll propio con altura máxima (~85 vh) y el pie permanece visible/anclado                                | SC-001, SC-002 |
