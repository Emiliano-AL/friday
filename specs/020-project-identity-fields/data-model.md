# Data Model: Identidad y fecha de término del proyecto

**Fecha**: 2026-10-09 | **Feature**: [spec.md](spec.md) | **Research**: [research.md](research.md)

## Cambio de esquema

Migración `2026_10_09_000000_add_identity_fields_to_projects_table` (aditiva, sin
backfill obligatorio):

| Columna | Tipo | Nullable | Reglas de validación (Form Requests) |
| --- | --- | --- | --- |
| `key` | string(5) | sí | `nullable, string, min:2, max:5, regex:/^[A-Z0-9]+$/` + `Rule::unique('projects','key')->where('owner_id', …)` (con `->ignore($this->project)` en update). Normalización previa en `prepareForValidation`: `trim` + `strtoupper` |
| `color` | string(7) | sí | `nullable` + `Rule::enum(ProjectColor::class)` (paleta cerrada de 8 hex) |
| `icon` | string(32) | sí | `nullable` + `Rule::enum(ProjectIcon::class)` (conjunto cerrado de 8 nombres Material Symbols) |
| `target_date` | date | sí | `nullable, date` (fecha real; vacío en update → `null`) |

Índices: ninguno nuevo (volumen personal; `owner_id` ya indexado). La unicidad de
`key` es por propietario a nivel aplicación (ver research D3).

## Enums nuevos (`app/Enums`, string-backed)

### ProjectColor (valor = hex)
`INDIGO = '#5b5bd6'` (marca/default), `BLUE = '#2563eb'`, `GREEN = '#10b981'`,
`AMBER = '#f59e0b'`, `RED = '#ef4444'`, `CYAN = '#06b6d4'`, `VIOLET = '#8b5cf6'`,
`PINK = '#ec4899'`. Default de la aplicación: `null` → estilos actuales
(`bg-primary`).

### ProjectIcon (valor = nombre Material Symbols)
`FOLDER = 'folder'` (default), `ROCKET = 'rocket'`, `BUG = 'bug_report'`,
`DESIGN = 'design_services'`, `CODE = 'code'`, `DATABASE = 'database'`,
`CAMPAIGN = 'campaign'`, `TERMINAL = 'terminal'`. Default: `null` → `folder`.

Serialización a cliente: **valor escalar** (`?->value`), nunca objeto enum
(research D8).

## Modelo `Project` (cambios)

- Fillable: + `key`, `color`, `icon`, `target_date`.
- Casts: `color => ProjectColor::class`, `icon => ProjectIcon::class`,
  `target_date => date:Y-m-d` (el enum `status` ya existe).
- `@property` del docblock actualizado.

## Reglas de negocio consolidadas

| Regla | Fuente |
| --- | --- |
| Normalización clave: trim + mayúsculas antes de validar y persistir | FR-003 |
| Unicidad clave por propietario, insensible a mayúsculas (por normalización) | FR-003, SC-003 |
| Color/icono solo de listas cerradas | FR-004, FR-005 |
| Fecha opcional; pasada permitida pero marcada "Vencida" en ficha | FR-006, FR-007 |
| Edición de identidad y fecha solo por propietario con proyecto activo | FR-010 |
| Proyectos existentes: `null` en todo → defaults presentacionales | FR-011 |

## Payloads (todos aditivos)

1. **`Projects/Show` + edit payload** (`ProjectController@projectPayload`):
   + `key: string|null`, `color: string|null` (hex), `icon: string|null`,
   `targetDate: string|null` (Y-m-d).
2. **Directorio** (`ProjectController@index`, `DirectoryProject`): mismos 4 campos
   (el modal de edición del directorio los precarga).
3. **Sidebar compartido** (`HandleInertiaRequests::projectSummaries`):
   `get(['id','title','color','icon'])`; tipo `ProjectSummary` + `color`,
   `icon` (ambos `string|null`).

## Invariantes verificables

1. Toda clave persistida está en mayúsculas y cumple 2–5 alfanuméricos.
2. Dos proyectos del mismo propietario nunca comparten clave; dos propietarios
   distintos sí pueden.
3. `color`/`icon` en BD siempre pertenecen a los enums (validación de entrada).
4. Los 4 campos aparecen en show/edit/directorio/sidebar; ningún test existente
   se modifica (aserciones por campo).
