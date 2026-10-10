# Contract: Identidad y fecha de término del proyecto

**Fecha**: 2026-10-09 | **Feature**: [spec.md](spec.md) | **Data model**: [data-model.md](data-model.md)

Contrato de las interfaces UI/servidor de la funcionalidad. Todo es aditivo sobre
los contratos existentes de proyectos.

## 1. Rutas (sin cambios de mapa)

| Método | Ruta | Nombre | Cambio |
| --- | --- | --- | --- |
| POST | `/projects` | `projects.store` | Acepta y persiste `key`, `color`, `icon`, `target_date` |
| PUT/PATCH | `/projects/{project}` | `projects.update` | Idem; autorización actual: 404 no-propietario, 403 proyecto no activo |

## 2. Contrato de escritura (body de store/update)

```text
title        required, string, max:255
description  nullable, string, max:50000
key          nullable, string, min:2, max:5, regex /^[A-Z0-9]+$/ (tras trim+strtoupper),
             única por propietario (insensible a mayúsculas por normalización; ignore-self en update)
color        nullable, enum ProjectColor  (#5b5bd6 | #2563eb | #10b981 | #f59e0b |
             #ef4444 | #06b6d4 | #8b5cf6 | #ec4899)
icon         nullable, enum ProjectIcon   (folder | rocket | bug_report | design_services |
             code | database | campaign | terminal)
target_date  nullable, date (Y-m-d real)
```

Errores: vuelven vía redirect Inertia con `errors.<campo>` (convención actual del
modal: mensajes inline junto a cada campo; título y clave con `ring-error`).

## 3. Contratos de lectura (payloads)

### ProjectDetail (show + edit) — campos nuevos
`key: string | null`, `color: string | null` (hex), `icon: string | null`,
`targetDate: string | null` (Y-m-d).

### DirectoryProject (directorio + prop del modal de edición)
Los mismos 4 campos, mismo formato.

### ProjectSummary (sidebar compartido)
`{ id, title, color: string | null, icon: string | null }`.

## 4. Contrato UI

### Formulario (`ProjectFormModal`)
- Bloque "Identidad Visual" **sin** badge "Próximamente" ni footer informativo.
- Clave: input habilitado, maxlength 5, transform a mayúsculas al teclear,
  placeholder "CLAVE"; error inline `errors.key`.
- Color: 8 swatches de la paleta; activo con ring; sin selección = default de
  marca.
- Icono: grid de 8 botones con `AppIcon`; activo con ring; sin selección =
  `folder`.
- Fecha: `input type="date"` habilitado; error inline `errors.target_date`.
- Modo edición: precarga los 4 valores; "guardar" los persiste (fecha vacía =
  eliminar).

### Ficha (`Projects/Show`)
- Header: tile con `icon` (fallback `folder`) sobre fondo tenue de `color`
  (fallback estilos actuales); chip con `key` junto al h1 (oculto si `null`).
- Resumen, celda "Fecha Objetivo": fecha formateada (es) si existe; "Sin fecha"
  si `null`; badge "Vencida" (tono error) si `targetDate < hoy` — computado en
  el cliente.

### Navegación (`ContextSwitcher`)
- Tile de cada proyecto (lista y actual): `AppIcon` con `icon` del proyecto;
  fondo tenue de `color` cuando existe; fallback = comportamiento actual
  (iniciales / `bg-primary-container`).

## 5. Invariantes verificables

1. `POST /projects` con los 4 campos válidos persiste exactamente esos valores
   (clave en mayúsculas) y redirige a la ficha mostrándolos.
2. Clave duplicada del mismo propietario → 302 con `errors.key` y 0 filas
   nuevas; misma clave de otro propietario → éxito.
3. `color`/`icon` fuera de las listas cerradas → `errors.color` / `errors.icon`.
4. `target_date` imposible → `errors.target_date`; vacío en update → columna
   `null`.
5. Payloads de show/directorio/sidebar incluyen los 4 campos; los tests
   existentes pasan sin modificación.
