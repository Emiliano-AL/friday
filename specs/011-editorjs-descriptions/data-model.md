# Data Model: Editor de contenido enriquecido en descripciones (011)

## Entidades (existentes — cambio de formato, no de esquema)

| Campo                | Columna                | Tipo (inalterado) | Reglas de validación (nuevas)                        |
| -------------------- | ---------------------- | ----------------- | ---------------------------------------------------- |
| Tarea.descripción    | `tasks.description`    | `text` nullable   | `nullable`, `string`, `max:50000` (antes sin tope)   |
| Proyecto.descripción | `projects.description` | `text` nullable   | `nullable`, `string`, `max:50000` (antes sin tope)   |
| Sprint.objetivo      | `sprints.goal`         | `text` nullable   | `nullable`, `string`, `max:50000` (antes `max:1000`) |

- **Sin columnas ni tablas nuevas.** La semántica del campo (opcional, permisos, dónde se muestra) no cambia.
- **Contenido guardado**: estructura de bloques en JSON (ver formato abajo). El editor siempre emite JSON; la API permanece permisiva con texto plano (FR-005: se normaliza/muestra como párrafo).

## Formato del contenido estructurado

```json
{
    "time": 1727654400000,
    "blocks": [
        {
            "type": "paragraph",
            "data": {
                "text": "Texto con <b>negrita</b> y un <a href=\"https://ejemplo.com\">enlace</a>."
            }
        },
        { "type": "header", "data": { "text": "Encabezado", "level": 2 } },
        {
            "type": "list",
            "data": { "style": "unordered", "items": ["Primero", "Segundo"] }
        },
        { "type": "quote", "data": { "text": "Cita", "caption": "" } },
        { "type": "code", "data": { "code": "const x = 1;" } }
    ],
    "version": "2.31"
}
```

- Bloques soportados: `paragraph` (por defecto), `header` (nivel 2–4), `list` (`ordered`/`unordered`), `quote`, `code`.
- En línea (núcleo): **negrita, cursiva, enlaces**; paquetes: `inlineCode` y `marker` (resaltado).
- Inline HTML permitido dentro de `data.text`: `b`, `i`, `strong`, `em`, `code`, `mark`, `br`, `a[href]` — todo lo demás se escapa en el renderizado.

## Normalización y degradación (FR-005)

```text
valor nulo/vacío            → bloques vacíos (sin descripción)
JSON válido con "blocks"    → se usa tal cual
cualquier otro string       → {"blocks":[{"type":"paragraph","data":{"text": <string escapado>}}]}
```

- La **migración** aplica esta misma regla a las filas existentes (eager), dejando el invariante "todo contenido guardado es bloques" desde el día uno.

## Migración de datos (una sola corrida)

Para `tasks.description`, `projects.description`, `sprints.goal`:

1. Valores `NULL` → sin cambios.
2. Valor que `json_decode` a objeto con `blocks` array → sin cambios.
3. Resto (texto plano, JSON sin `blocks`, etc.) → envolver en párrafo con el texto exacto (escapado para inline).
4. Idempotente: segunda corrida no modifica nada.

## Reglas de negocio invariantes

- Los tres campos siguen **opcionales**; formularios permiten guardar vacío.
- Mensaje de validación (ES): "El contenido no puede superar los 50000 caracteres."
- Listas/tarjetas **no** renderizan el contenido (solo títulos/metadatos).

## Propiedades FUTURAS documentadas (UI "Próximamente")

- Imágenes y adjuntos (subida de archivos) en el editor.
- Importación/exportación de marcado (markdown).
- Herramientas adicionales (tablas, delimitadores, embebedores).
- Vista previa de solo lectura independiente para tareas (hoy la lectura es el propio diálogo de edición).
