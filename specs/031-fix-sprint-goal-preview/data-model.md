# Data Model: Corregir la vista previa del objetivo del sprint

Sin cambios de datos.

- **Sprint.goal** (`string|null`, existente, máx. 50 000 caracteres): JSON de Tiptap (desde la feature 011) o texto plano (sprints antiguos). Se guarda y se edita igual que hoy.
- **Derivado en presentación** (no se persiste): `goalPreview = tiptapToPlainText(goal)`, de una línea. Si es `''`, el resumen no se muestra.
