# Research: Corregir la vista previa del objetivo del sprint

## R1. Dónde convertir el objetivo a texto plano

- **Decision**: En el frontend, con `tiptapToPlainText` de `resources/js/utils/tiptapContent.ts`.
- **Rationale**: Es la convención existente: `ProjectCard.vue` resume así `project.description`. El helper ya cubre todos los casos de la spec:
  - JSON válido: aplana los nodos en una línea con los espacios normalizados (FR-001, FR-003).
  - Texto plano antiguo o JSON inválido: `isTiptapJson` es falso, así que lo devuelve recortado (FR-004 y el edge case de llaves escritas a mano).
  - Doc sin texto: devuelve `''`, así que el `v-if` oculta el resumen (FR-005).
  - `null` o vacío: devuelve `''`.
- **Alternatives considered**: Agregar `goalExcerpt` en los payloads de PHP (`ProjectController`, `HomeController`). Sería testeable con Pest, pero duplicaría en PHP la lógica de aplanado que ya existe en TS y crearía dos fuentes de verdad. Lo descarté.

## R2. Vistas afectadas

- **Decision**: Corregir 3 puntos de render:
  - `ProjectSprintsSection.vue`: la tarjeta del sprint activo y las filas de "Otros sprints". En las filas se corrigen tanto el texto como el atributo `:title`.
  - `SprintPanel.vue`: el panel del sprint en el inicio.
- **Rationale**: `grep` de `.goal` en `resources/js`. `SprintHeader.vue` usa `RichTextContent` (correcto, FR-006). `SprintFormModal.vue` es la edición (FR-007). `pages/Sprints/Show.vue` solo pasa `goal` al modal.

## R3. Verificación

- **Decision**: `vue-tsc` y `vp check`, la validación manual de `quickstart.md`, y volver a correr los tests de Pest de sprints y home como regresión.
- **Rationale**: No hay runner de tests JS en el proyecto. Agregar Vitest sería una dependencia nueva fuera del alcance de un bug de presentación.
