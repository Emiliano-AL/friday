# Contract: accesos de UI

## Inyecciones del layout (`AuthenticatedLayout.vue`)

| Clave | Tipo | Efecto |
| --- | --- | --- |
| `friday:open-palette` | `() => void` | Abre el modal de acciones rápidas (sin cambios) |
| `friday:open-task-form` | `() => void` | **Nuevo**: carga `taskFormProjects` si falta y abre el formulario global de nueva tarea |

## Mapa de accesos (resultado esperado)

| Acceso | Antes | Después |
| --- | --- | --- |
| Inicio → "Nueva Tarea" (`ActivityDashboard.vue`) | paleta | `friday:open-task-form` |
| Inicio vacío → tarjeta "Nueva tarea" (`HomeCanvas.vue`) | paleta | `friday:open-task-form` |
| Inicio vacío → botón "⌘K / acciones" (`HomeCanvas.vue`) | paleta | paleta (sin cambio) |
| Barra lateral → botón principal | "Nueva Tarea" → `/backlog` | "Acciones rápidas" + ⌘K → paleta |
| Paleta → "Crear nueva tarea" (C) | `/backlog` | formulario |
| Tecla C (fuera de Mis Tareas y de campos de texto) | paleta | formulario |
| Tecla C en Mis Tareas | formulario de la página | sin cambio |
| ⌘K | alterna paleta | sin cambio |

## Prop compartido

`taskFormProjects` (optional). Visitas completas: ausente. Partial reload con
`only: ['taskFormProjects']`: presente con la forma de [data-model.md](../data-model.md).

## Formulario global

`TaskFormModal` en modo `create`, `currentUser` = `auth.user`,
`projects` = `taskFormProjects`, sin proyecto/sprint inicial. Tras guardar,
`back()` recarga la página actual.
