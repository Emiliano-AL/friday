# Data Model: Carga masiva de tareas

Sin migraciones. Se crean filas de `tasks` existentes.

## Tarea creada por importación

| Campo `tasks` | Origen | Regla |
| --- | --- | --- |
| `title` | `### Nombre` | requerido, ≤ 255 caracteres, se quitan `**` envolventes |
| `type` | Tipo | `Tarea`→`other`, `Feature`→`feature`, `Bug`→`bug`, `Test`→`test` (sin distinguir mayúsculas) |
| `priority` | Prioridad (viñeta) | `Baja`→`low`, `Media`→`medium`, `Alta`→`high`, `Urgente`→`urgent`; si difiere de la sección `##` → aviso |
| `status` | — | siempre `backlog` |
| `assignee_id` | Responsable | ver D5 de research; null si no hay coincidencia única |
| `description` | Descripción | JSON de Tiptap (D4); ≤ 50 000 caracteres |
| `project_id` | destino | proyecto o null |
| `sprint_id` | — | null |
| `author_id` | — | usuario que importa |

## Resultado de análisis (no persistido)

```ts
type ImportPreview = {
    destination: { projectId: number | null; label: string };
    tasks: Array<{
        line: number;                       // línea del ### en el archivo
        title: string;
        type: 'other' | 'feature' | 'bug' | 'test' | null;
        typeLabel: string | null;
        priority: 'low' | 'medium' | 'high' | 'urgent' | null;
        priorityLabel: string | null;
        assignee: { id: number; name: string } | null;
        responsibleText: string | null;     // texto original del archivo
        excerpt: string;                    // descripción en texto plano, ≤ 140 caracteres
        state: 'ok' | 'warning' | 'error' | 'duplicate' | 'repeated';
        messages: string[];                 // avisos o errores en español
    }>;
    excluded: Array<{ name: string; reason: string }>;  // sección "No incluidos"
    counts: { importable: number; total: number };
};
```

`importable` = tareas con estado `ok` o `warning`. `error`, `duplicate` y `repeated` no se importan.

## Límites

- Contenido ≤ 1 MB; ≤ 200 tareas `###`; si se supera, error global.
