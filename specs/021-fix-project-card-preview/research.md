# Research: Vista previa legible e identificable del proyecto

**Fecha**: 2026-10-10 | **Feature**: [spec.md](spec.md) | **Plan**: [plan.md](plan.md)

Sin NEEDS CLARIFICATION pendientes. Investigación directa (alcance de 2
archivos) en lugar de agente.

## Hallazgos

- `resources/js/utils/tiptapContent.ts` (66 líneas) ya ofrece
  `isTiptapJson(value)` (detecta string JSON con `type: 'doc'`),
  `docFromPlainText(text)` y `normalizeToTiptap(value)` — **pero no existe
  extractor de texto plano**. Sus interfaces tipan solo `doc → paragraph →
  text`; el starterKit puede producir headings/listas, así que el extractor debe
  recorrer recursivamente `content` genéricamente y no limitarse a las
  interfaces declaradas.
- `ProjectCard.vue:97-102` interpola `{{ project.description }}` crudo con
  `line-clamp-2`; es la única interpolación de descripción de proyecto fuera de
  `RichTextContent` (grep sobre `resources/js`: la otra coincidencia es
  `SprintComingSoon.vue`, contenido estático "Próximamente", fuera de alcance).
  `ProjectListRow.vue` no muestra descripción.
- El azulejo (`:65-70`) tiene `AppIcon name="folder"` fijo y
  `identityClasses` derivados del **estado** (`:34-38`); no consume
  `project.color`/`project.icon` aunque `DirectoryProject` ya los trae (feature
  020). El patrón de tinte ya existe en `Show.vue` (fondo `${color}1A`, icono
  coloreado) y `ContextSwitcher.vue` — se replica para consistencia.
- La descripción del proyecto se guarda como JSON de Tiptap al editar desde el
  formulario (el editor serializa documento); la ficha la renderiza bien con
  `RichTextContent`. Por eso el bug solo es visible en la tarjeta.

## Decisiones

### D1. Extractor en `tiptapContent.ts` con passthrough de texto legado
- **Decision**: nueva función exportada
  `tiptapToPlainText(value: string | null | undefined): string`:
  - `null/undefined/blank` → `''`.
  - `isTiptapJson(value)` → parsear y recorrer recursivamente el árbol:
    acumular `node.text` de los nodos hoja y tratar cada bloque (cualquier nodo
    con `content` cuyo padre es `doc` o bloque de primer nivel) como separador
    de espacio; resultado normalizado (colapsar espacios en blanco, trim).
  - Texto plano legado → devolverlo sin transformación (trim).
- **Rationale**: FR-001/FR-002; un solo punto de verdad para "texto visible de
  una descripción"; reusable por futuras vistas previas.
- **Alternativas**: renderizar `RichTextContent` dentro de la tarjeta → rompe
  el `line-clamp` visual y arranca el editor para un extracto; normalizar el
  almacenamiento a texto plano → cambio de datos innecesario (la ficha usa el
  JSON enriquecido) y contradice la dirección de texto enriquecido (011/014).

### D2. Extracto como computed en `ProjectCard.vue`
- **Decision**: `descriptionExcerpt = computed(() => tiptapToPlainText(project.description))`;
  el `<p>` pasa a `v-if="descriptionExcerpt"` y muestra el extracto (mismas
  clases `line-clamp-2`).
- **Rationale**: FR-003; cuando el texto visible es vacío (bloque vacío, JSON
  sin texto) el párrafo se omite — cubre el edge case sin lógica extra.

### D3. Azulejo con distintivo y fallback exacto al actual
- **Decision**: icono `project.icon ?? 'folder'` (AppIcon); cuando
  `project.color` existe, fondo en línea `backgroundColor: color + '1A'` y
  color del icono `color`; cuando no, las clases actuales `identityClasses`.
  La franja superior (`statusColor`) y el badge de estado no se tocan (FR-005).
- **Rationale**: FR-004/FR-005; paridad visual con ficha y ContextSwitcher
  (mismo tratamiento de tinte `1A`).
- **Alternativas**: teñir también la franja superior → rompe la semántica de
  estado que hoy comunica.

### D4. Sin cambios backend ni tests Pest
- **Decision**: no hay lógica de dominio server-side nueva; la verificación es
  `npm run build` + `npm run types:check` + quickstart en navegador.
- **Rationale**: Constitución III aplica a lógica de dominio; el proyecto no
  tiene runner de unit tests frontend y añadir uno por una corrección de
  presentación viola YAGNI.

## Resolución de clarificaciones

| # | Tema | Resolución |
| --- | --- | --- |
| 1 | ¿Existe extractor de texto plano? | Hallazgo: no; se crea en `tiptapContent.ts` (D1) |
| 2 | ¿Dónde más se interpola la descripción cruda? | Solo `ProjectCard.vue` (proyecto); `SprintComingSoon` es estático |
| 3 | ¿Cómo pintar el distintivo sin romper el estado? | Azulejo teñido; franja intacta (D3) |
