# Contract: Payload del panel de inicio (prop `dashboard`)

**Feature**: `specs/015-home-active-dashboard`
**Date**: 2026-10-03
**Type**: UI contract (Inertia page prop)
**Consumer**: `resources/js/pages/AppHome.vue` (y componentes bajo `resources/js/Components/Home/`)

## Semántica

La ruta `GET /` (nombre `home`) devuelve la página `AppHome` con una prop `dashboard`:

- `dashboard = null` → el usuario **no tiene actividad**; `AppHome` renderiza `HomeCanvas` (estado vacío actual, inalterado).
- `dashboard = object` → el usuario **tiene actividad**; `AppHome` renderiza el panel de actividad.

Las props globales de `HandleInertiaRequests` (`auth.user`, `flash`, `projects` del sidebar) se mantienen sin cambios en ambos casos.

## Convenciones

- Arrays asociativos camelCase (igual que `ProjectController::projectPayload`, `ProjectSprintController::sprintDetail`).
- Enums serializados como `{ status, statusLabel }` / `{ priority, priorityLabel }` con valores del enum y su `label()` en español.
- Fechas: `Y-m-d` (dates) / ISO-8601 datetime (`createdAt`, `completedAt`).
- Toda referencia a entidad incluye al menos `id` + display name.
- Nada de datos simulados: si una sección no aplica, su valor es `null` (objeto completo) o la lista vacía con su estado vacío en UI.

## Shape

```jsonc
{
  "dashboard": {
    // FR-003 subtítulo contextual (el saludo se compone en cliente con la hora local)
    "context": {
      "priorityTasks": 3,              // tareas activas (status≠Done) del usuario con priority Urgent|High
      "sprint": {                      // null si no hay sprint activo
        "name": "Sprint 14",
        "coverage": 75                 // % tareas Done del sprint (0-100)
      }
    },

    // FR-004 tarjetas de resumen
    "summary": {
      "sprint": {                      // null si no hay sprint activo (FR-011)
        "id": 14,
        "name": "Sprint 14",
        "coverage": 75,
        "daysRemaining": 4,
        "project": { "id": 2, "title": "Pasarela de Pagos" }
      },
      "tasks": { "active": 8, "urgent": 3 },   // urgent = Urgent+High activas
      "projects": { "active": 4 }              // proyectos del usuario con status Active
    },

    // FR-005 mis tareas para hoy & prioritarias (máx. 6)
    "myTasks": {
      "total": 8,                      // total real (para "Ver todas mis tareas (8)")
      "items": [
        {
          "id": 102,
          "title": "Integración de Apple Pay y Google Pay",
          "status": "in_progress",
          "statusLabel": "En curso",
          "priority": "urgent",
          "priorityLabel": "Urgente",
          "project": { "id": 2, "title": "Pasarela de Pagos" },  // null para tareas sin proyecto (standalone)
          "commentsCount": 3
        }
      ]
    },

    // FR-006 panel del sprint activo (null si no existe → sección omitida, FR-011)
    "sprintPanel": {
      "id": 14,
      "name": "Sprint 14",
      "goal": "Integrar de forma segura Apple Pay y Google Pay…",
      "project": { "id": 2, "title": "Pasarela de Pagos" },
      "total": 8,
      "done": 6,
      "coverage": 75,
      "daysRemaining": 4,
      "burndown": {                    // FR-006 gráfico de avance
        "startDate": "2026-10-20",
        "endDate": "2026-10-31",
        "total": 8,
        "points": [                    // un punto por día natural del sprint
          { "date": "2026-10-20", "ideal": 1, "done": 0 },
          { "date": "2026-10-21", "ideal": 2, "done": 1 }
        ]
      }
    },

    // FR-007 proyectos activos (máx. 4)
    "projects": {
      "items": [
        {
          "id": 2,
          "title": "Pasarela de Pagos",
          "progress": 84,              // Project::progressPercentage()
          "note": "Sprint 14 • 4 días restantes"   // o "{n} tareas abiertas" si no hay sprint activo
        }
      ]
    },

    // FR-008 insight semanal (null cuando completed = 0 → estado vacío en UI)
    "weeklyInsight": {
      "completed": 14,
      "avgCycleDays": 1.8
    },

    // FR-009 actividad del equipo (máx. 8)
    "activity": {
      "items": [
        {
          "id": 991,
          "type": "task_completed",
          "typeLabel": "completó la tarea",
          "user": { "id": 7, "name": "Elena Rostova", "avatar": null },
          "task": { "id": 110, "title": "SDK Webhook handler" },   // null si no aplica
          "project": { "id": 2, "title": "Pasarela de Pagos" },
          "sprint": { "id": 14, "name": "Sprint 14" },             // null si no aplica
          "createdAt": "2026-10-03T09:12:00Z"
        }
      ]
    }
  }
}
```

## Destinos de navegación (P3 — rutas existentes)

| Acción UI | Destino (named route Wayfinder) |
| --- | --- |
| Ir al sprint / Abrir tablero del sprint | `projects.sprints.show` (projectId, sprintId) |
| Nueva tarea | abrir paleta de comandos (`inject('friday:open-palette')`, idéntico al atajo `C` y a `HomeCanvas`) |
| Ver todas mis tareas | `tasks.index` |
| Ver todos (proyectos) | `projects.index` |
| Enlace de tarea del feed | `tasks.show` (taskId) |

## Invariantes verificables (tests)

1. Usuario sin proyectos ni tareas ⇒ `dashboard` es `null` y el resto de props globales no cambian.
2. Toda fecha/`count`/porcentaje del payload se corresponde con el estado real de BD (seed determinista en tests).
3. Un usuario miembro de un proyecto ve actividad de ese proyecto; un externo no (scope por `isMember`).
4. `summary.sprint`, `sprintPanel` y `context.sprint` son `null` simultáneamente cuando no hay sprint activo.
5. `weeklyInsight` es `null` si el usuario no completó tareas en la semana ISO actual.
