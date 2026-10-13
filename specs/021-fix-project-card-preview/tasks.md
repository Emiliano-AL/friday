---
description: 'Task list for 021-fix-project-card-preview'
---

# Tasks: Vista previa legible e identificable del proyecto

**Input**: Design documents from `/specs/021-fix-project-card-preview/`

**Prerequisites**: plan.md, spec.md, research.md

**Tests**: No se generan tareas de test. La spec no las pide y el proyecto no tiene runner de unit tests frontend (research D4). La verificación es `npm run types:check` + `npm run build` + escenarios en navegador. La suite Pest no se toca (SC-003).

**Organization**: Tareas agrupadas por historia de usuario. Sin cambios backend ni migraciones.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Paralelizable (archivo distinto, sin dependencias pendientes)
- **[Story]**: Historia a la que pertenece (US1, US2)

---

## Phase 1: Setup

No aplica: no hay dependencias, archivos ni configuración nuevos.

---

## Phase 2: Foundational

No aplica: cada historia toca su propio punto; no hay prerequisitos compartidos.

---

## Phase 3: User Story 1 - Descripción legible en la tarjeta (Priority: P1) 🎯 MVP

**Goal**: La tarjeta de `/projects` muestra la descripción como texto plano legible (máx. 2 líneas), nunca como JSON de Tiptap. El texto legado se muestra igual que antes.

**Independent Test**: Editar un proyecto (p. ej. solo cambiar su color) y guardar. La tarjeta muestra el texto de la descripción sin `{"type":"doc"...`. Un proyecto con descripción legada en texto plano se ve igual que antes, y uno con un documento vacío no muestra párrafo.

### Implementation for User Story 1

- [X] T001 [US1] Añadir y exportar `tiptapToPlainText(value: string | null | undefined): string` en `resources/js/utils/tiptapContent.ts`. Reglas: `null`/`undefined`/vacío → `''`. Si `isTiptapJson(value)` es true, hacer `JSON.parse` y recorrer el árbol **recursivamente y de forma genérica** (cualquier nodo con `content: unknown[]`, no solo paragraph/text: headings, listas, blockquote y codeBlock también aportan texto). Acumular `node.text` de los nodos hoja e insertar un espacio entre bloques. Normalizar con `.replace(/\s+/g, ' ').trim()`. En cualquier otro caso (texto legado) devolver `value.trim()` sin transformar. Tipar el nodo genérico localmente (p. ej. `{ type?: string; text?: string; content?: unknown[] }`) sin cambiar las interfaces exportadas existentes.
- [X] T002 [US1] En `resources/js/components/Projects/ProjectCard.vue`: importar `tiptapToPlainText` desde `@/utils/tiptapContent` y añadir `const descriptionExcerpt = computed(() => tiptapToPlainText(props.project.description));`. En el `<p>` de las líneas ~97-102, cambiar `v-if="project.description"` por `v-if="descriptionExcerpt"` y `{{ project.description }}` por `{{ descriptionExcerpt }}`, manteniendo exactamente las clases actuales (`line-clamp-2`, etc.) (FR-001–FR-003). Depende de T001.

**Checkpoint**: US1 lista. La tarjeta muestra texto legible para descripciones en Tiptap JSON, legadas, vacías y largas.

---

## Phase 4: User Story 2 - El distintivo se refleja en la tarjeta (Priority: P2)

**Goal**: El azulejo de la tarjeta usa `project.icon` y un fondo tenue de `project.color`, con el mismo tratamiento que `resources/js/pages/Projects/Show.vue:456-470`. Sin distintivo, se ve como hoy. La franja superior sigue usando el color de estado.

**Independent Test**: Asignar icono y color a un proyecto, guardar y comprobar el azulejo en `/projects`, primero tras guardar y luego tras recargar. Probar también solo color, solo icono, y quitar ambos (vuelve al default). La franja superior y el badge de estado no cambian.

### Implementation for User Story 2

- [X] T003 [US2] En `resources/js/components/Projects/ProjectCard.vue` (azulejo, líneas ~65-70): cambiar `:class="identityClasses"` por `:class="project.color ? '' : identityClasses"` y añadir `:style="project.color ? { backgroundColor: \`${project.color}1A\`, color: project.color } : undefined"`. Cambiar `<AppIcon name="folder" :size="20" />` por `<AppIcon :name="project.icon ?? 'folder'" :size="20" />`. **No** tocar `statusColor` (franja superior) ni el `UiBadge` de estado (FR-004, FR-005). Este cambio no depende de US1, pero comparte archivo con T002, así que se hace en secuencia.

**Checkpoint**: US1 y US2 funcionan. El distintivo se ve en la tarjeta y la semántica de estado no cambia.

---

## Phase 5: Polish & Cross-Cutting Concerns

- [X] T004 Ejecutar `npm run types:check` y `npm run build`, y corregir cualquier error de tipos en `resources/js/utils/tiptapContent.ts` o `resources/js/components/Projects/ProjectCard.vue`.
- [ ] T005 Verificar en navegador `/projects` (URL vía `get-absolute-url`) los escenarios de spec.md: US1 1–4 (Tiptap JSON, texto legado, documento vacío, descripción larga) y US2 1–4 (icono+color, sin distintivo, solo uno, quitado). Comprobar también que la ficha `/projects/{id}` sigue renderizando la descripción enriquecida sin cambios (FR-007).
- [X] T006 Ejecutar `php artisan test --compact` para confirmar que la suite existente pasa sin modificaciones (SC-003).

---

## Dependencies & Execution Order

- **US1**: T001 → T002.
- **US2**: T003 es independiente de US1 en lógica, pero edita `ProjectCard.vue` como T002, así que se ejecuta después de T002 para evitar conflictos.
- **Polish**: T004 → T005 → T006, después de T003.

### Parallel Opportunities

- Mínimas, porque solo hay 2 archivos. T001 (`tiptapContent.ts`) y T003 (`ProjectCard.vue`) tocan archivos distintos y podrían hacerse en paralelo. T002 debe ir después de ambos.

```text
# Ejemplo en paralelo:
T001 [US1] tiptapToPlainText en resources/js/utils/tiptapContent.ts
T003 [US2] azulejo con icon/color en resources/js/components/Projects/ProjectCard.vue
# Luego:
T002 [US1] descriptionExcerpt en ProjectCard.vue
```

## Implementation Strategy

### MVP (solo US1)

1. T001 → T002.
2. Verificar US1 en `/projects`: deja de verse el JSON, que es la parte más visible del bug.

### Entrega incremental

1. US1 (T001–T002), luego verificar.
2. US2 (T003), luego verificar.
3. Polish (T004–T006).
