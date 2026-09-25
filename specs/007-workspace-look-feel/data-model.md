# Data Model: Look & Feel del workspace autenticado

**Feature**: 007-workspace-look-feel
**Date**: 2026-09-25

## Resumen

**No hay cambios de esquema de base de datos.** La feature es de presentación: lee únicamente datos existentes (`users`, `projects`) y los proyecta a la capa Inertia como props compartidas. No hay migraciones, ni estados de dominio nuevos, ni transiciones.

## Entidades de vista (read-only, existentes)

### Usuario (sesión) — fuente: modelo `User` (existente)

| Campo    | Tipo           | Uso en el shell                                                                               |
| -------- | -------------- | --------------------------------------------------------------------------------------------- |
| `id`     | int            | identidad (logout, matching)                                                                  |
| `name`   | string         | tarjeta de usuario, avatar (fallback: iniciales), menú de perfil                              |
| `email`  | string         | tarjeta de usuario y menú de perfil (truncado con ellipsis si es largo)                       |
| `avatar` | string \| null | avatar en tarjeta y topbar; si es nulo/ausente, iniciales de `name` sobre `primary-container` |

Compartido hoy por `HandleInertiaRequests` como `auth.user` (modelo completo). **Sin cambios.**

### Resumen de proyecto — fuente: modelo `Project` (existente)

Nueva **prop compartida** `projects` (solo usuarios autenticados): lista ligera para el selector de contexto.

| Campo   | Tipo   | Regla                                                               |
| ------- | ------ | ------------------------------------------------------------------- |
| `id`    | int    | identifica el proyecto; el selector navega a su ficha               |
| `title` | string | se muestra truncado si es largo; alimenta la inicial del distintivo |

Reglas de obtención: proyectos del usuario autenticado (misma relación/base de query que usa `ProjectController`), orden alfabético por `title`, límite de 50. Si la lista está vacía, el selector muestra estado vacío con llamada a crear el primer proyecto (no rompe el layout).

### Elemento de navegación — configuración estática del shell

No persiste datos; es configuración en código con esta forma:

| Campo      | Tipo                   | Regla                                                                                 |
| ---------- | ---------------------- | ------------------------------------------------------------------------------------- |
| `label`    | string                 | copy en español del diseño (Panel, Proyectos, Mis Tareas, Sprints, Backlog, Ajustes)  |
| `icon`     | string                 | nombre de icono Material Symbols (p. ej. `space_dashboard`, `folder`)                 |
| `to`       | ruta Wayfinder \| null | destino real si la sección existe; `null` si está "Próximamente"                      |
| `match`    | string                 | prefijo de URL para estado activo (p. ej. `/projects`)                                |
| `disabled` | boolean                | `true` cuando `to` es `null`: se renderiza deshabilitado con indicador "Próximamente" |

Estado activo: un único item activo por página, derivado del URL actual (FR-004). Elementos deshabilitados no navegan ni lanzan peticiones.

### Acción de comando — configuración estática (paleta ⌘K)

| Campo      | Tipo     | Regla                                                                               |
| ---------- | -------- | ----------------------------------------------------------------------------------- |
| `label`    | string   | copy en español ("Crear nueva tarea", "Ir a Proyectos", "Ir al Panel")              |
| `icon`     | string   | nombre de icono Material Symbols                                                    |
| `shortcut` | string   | hints visuales (`⌘K`, `C`, `G then S`) — informativos                               |
| `keywords` | string[] | términos extra para el filtrado                                                     |
| `run`      | handler  | navegación Wayfinder a la sección existente; "Crear nueva tarea" navega a Proyectos |

### Notificación

Sin entidad en esta iteración: la campana solo renderiza el estado vacío "Sin notificaciones" (FR-012). Cuando exista backend de notificaciones, esta entidad se definirá en su spec correspondiente.

## Validaciones

- No hay validaciones nuevas: la feature no recibe entrada del usuario ni escribe datos.
- Invariantes de presentación: nombres/correos largos truncados; `avatar` ausente → iniciales; lista de proyectos vacía → estado vacío.

## Impacto en modelos existentes

Ninguno. No se modifican factories, relaciones ni casts. `User` y `Project` se leen tal cual.
