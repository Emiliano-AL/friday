# Research: Identidad y fecha de término del proyecto

**Fecha**: 2026-10-09 | **Feature**: [spec.md](spec.md) | **Plan**: [plan.md](plan.md)

Sin NEEDS CLARIFICATION pendientes: los cuatro campos son greenfield y todos los
puntos de integración fueron inspeccionados en disco (con el trabajo paralelo sin
commit presente).

## Hallazgos del código actual

- `projects` solo tiene `owner_id, title, description, status` (+timestamps). No
  existe rastro de `key`/`color`/`icon`/`target_date` en BD, modelos, payloads ni
  tipos.
- `ProjectFormModal.vue`: bloque "Identidad Visual" deshabilitado (líneas 126-172)
  con 4 colores inline (`#5b5bd6, #2563eb, #10b981, #f59e0b` + "+3"), input clave
  (`w-20`, uppercase) e input date; footer "…estarán disponibles próximamente".
  Props del modal: `project?: {id, title, description}` — se alimenta con objetos
  inline desde `Projects/Index.vue:69-79` y `Projects/Show.vue:1005-1009`, así que
  extender el prop toca esos dos call sites.
- Submit: `form.post(store.url())` / `form.put(update.url(id))` (Wayfinder
  `@/routes/projects`); errores inline (`form.errors.*`); descripción vía
  `RichTextEditor` (async save antes de enviar).
- Backend: `ProjectController@store` crea solo owner/title/description; `@update`
  exige `isOwnedBy` (404 si no) + `authorize('update')` (owner y proyecto activo,
  403 si archivado). Ambos Form Requests tienen reglas idénticas
  (title/description). `projectPayload()` (show/edit), `index()` (directorio) y
  `HandleInertiaRequests::projectSummaries()` (sidebar: literalmente
  `get(['id','title'])`, límite 50) no exponen identidad.
- `ProjectPolicy::update` = owner AND active. Miembros solo ven.
- Ficha (`Projects/Show.vue`): tile `bg-primary` con `AppIcon name="folder"`
  (:430), h1 del título (:436), y la tarjeta "FECHA OBJETIVO — Próximamente"
  (:532-546) como 4ª celda del grid resumen.
- `ContextSwitcher.vue`: tiles por proyecto = **inicial del título** en cuadrados
  `bg-surface-container-high`; el tile del proyecto actual = `bg-primary-container`.
  No hay icono/color por proyecto.
- `AppIcon` = Material Symbols por ligadura de fuente (`name` libre, sin
  allowlist). No existe mapa de iconos ni paleta en el código (grep negativo).
- Tests: convenciones Pest claras en `ProjectCrudTest`; **ninguna aserción de
  forma cerrada** sobre proyectos (todas por campo) → añadir campos es seguro.
  `ShellRenderingTest` fija `projects.0.id/title` (aditivo-seguro).
- `User::factory`/`ProjectFactory`: estados `archived()`, `completed()`,
  `withMembers()` — punto natural para `withIdentity()`.

## Decisiones

### D1. Columnas y nulabilidad
- **Decision**: migración con `key` string(5) nullable, `color` string(7)
  nullable, `icon` string(32) nullable, `target_date` date nullable. Sin backfill:
  los defaults son presentacionales (color → estilo actual `bg-primary`, icono →
  `folder`, clave/fecha → ocultos).
- **Rationale**: FR-011 (proyectos existentes sin acción del usuario) y YAGNI;
  opcionalidad total del conjunto (supuesto del spec).
- **Alternativas**: defaults en BD (p. ej. color NOT NULL con default) → fuerza
  valores inventados sobre proyectos existentes y complica "sin elegir".

### D2. Paleta e iconos como enums PHP + constantes TS espejo
- **Decision**: `ProjectColor` (string-backed enum, 8 casos, hex como valor) y
  `ProjectIcon` (string-backed enum, 8 casos, nombre Material Symbols como valor)
  en `app/Enums`. Validación con `Rule::enum(...)`. En el cliente, listas
  constantes TS en `types/project.ts` (PALETTE de 8 hex, PROJECT_ICONS de 8
  nombres) alimentando los pickers. El drift TS↔PHP se controla con tests de
  validación (color/icono fuera de lista rechazados) y la verificación visual del
  quickstart.
- **Rationale**: Constitución IV (estados/valores cerrados como enums); el picker
  necesita las listas en el cliente de todas formas.
- **Alternativas**: solo constantes TS + `Rule::in` con array duplicado en PHP →
  mismos enums sin tipado; tabla de catálogo en BD → overkill para valores
  cerrados estables.
- **Paleta concreta**: los 4 visibles del mock (`#5b5bd6, #2563eb, #10b981,
  #f59e0b`) + 3 del sistema de diseño: `#ef4444` (error/crimson), `#06b6d4`
  (cyan), `#8b5cf6` (violeta). Default: `null` → estilos actuales.
- **Iconos concretos** (Material Symbols, coherencia con nombres ya usados en la
  app): `folder, rocket, bug_report, design_services, code, database, campaign,
  terminal`. Default: `null` → `folder`.

### D3. Clave: normalización y unicidad por propietario
- **Decision**: en el Form Request `prepareForValidation`: `key` → `trim` +
  `strtoupper`. Reglas: `nullable, string, min:2, max:5, regex:/^[A-Z0-9]+$/`
  (tras normalizar). Unicidad: `Rule::unique('projects','key')->where(fn ($q) =>
  $q->where('owner_id', $this->user()->id))` y en update `->ignore($this->project)`.
  Mensaje de duplicado junto al campo (convención `form.errors.key` ya existente).
- **Rationale**: cumple FR-003 verbatim; almacenar siempre en mayúsculas hace la
  unicidad insensible a mayúsculas de forma natural.
- **Alternativas**: unicidad global → el spec la descarta (otro propietario puede
  reusarla); unique compuesto en BD con columna generada → complejidad sin
  beneficio a este volumen (la app valida; carrera de doble submit es
  despreciable y el unique por propietario no es exigible en SQL simple con
  nullable).

### D4. Superficies de visualización (FR-007/008/009)
- **Decision**: (a) Ficha: tile del header usa `icon`+`color` (fallback folder /
  estilo actual), chip de `key` junto al h1, tarjeta "Fecha Objetivo" muestra la
  fecha formateada (es, "9 oct 2026") o "Sin fecha", con badge "Vencida" en tono
  error cuando `target_date < hoy` (computado cliente). (b) Navegación:
  `projectSummaries()` pasa a `get(['id','title','color','icon'])` y
  `ContextSwitcher` pinta icono en el tile (fondo tenue del color; fallback
  iniciales actuales). (c) Directorio (`Projects/Index`) **fuera de alcance**:
  FR-009 nombra la navegación; la tarjeta del directorio ya tiene jerarquía
  propia.
- **Rationale**: YAGNI + literalidad del spec; el directorio puede incorporarlo
  después sin coste (datos ya en el tipo).
- **Alternativas**: pintar también el directorio → crece el diff sobre archivos
  con trabajo paralelo del usuario sin necesidad.

### D5. Formulario: habilitación y pickers
- **Decision**: eliminar badge "Próximamente" y el footer informativo; clave:
  input habilitado (maxlength 5, transform a mayúsculas en input, placeholder
  "CLAVE"); color: fila de 8 swatches seleccionables (ring en el activo, opción de
  ninguno implícita por default); icono: grid de 8 botones con `AppIcon`
  (seleccionable, default ninguno); fecha: `input type="date"` habilitado. El
  prop `project` del modal se extiende a `{id,title,description,key,color,icon,
  targetDate}` y ambos call sites (`Index.vue`, `Show.vue`) pasan los valores.
- **Rationale**: reutiliza la estructura visual ya maquetada (solo se habilita);
  los pickers cerrados nacen de las listas D2.
- **Alternativas**: librería de pickers → dependencia nueva prohibida sin
  aprobación.

### D6. Permisos
- **Decision**: sin cambios. La identidad viaja por `update` (owner + activo, 404
  no-owner / 403 archivado, reglas actuales). Los miembros reciben los campos en
  los payloads de solo lectura.
- **Rationale**: FR-010 ya es el comportamiento actual del endpoint.

### D7. Fecha: almacenamiento y formato
- **Decision**: columna `date`; cast Eloquent `date:Y-m-d`; validación
  `nullable, date`; en edición, vacío → `null` (se persiste la ausencia). Cliente:
  input date nativo; display formateado en es (p. ej. `9 oct 2026`) y estado
  "Vencida" si pasó.
- **Rationale**: FR-006/FR-007 y edge cases del spec.
- **Alternativas**: datetime → falso precisión; rechazar fechas pasadas en
  validación → el spec las permite (marca vencida).

### D8. Serialización de enums al cliente
- **Decision**: payloads entregan `color` e `icon` como **valor escalar**
  (`$project->color?->value`), no como objetos enum. Tipos TS: `'#5b5bd6' | …`
  (union desde la constante PALETTE) y nombres de icono como union.
- **Rationale**: los enums serializados como objetos romperían Inertia/JSON y el
  consumo es solo de valor.

## Resolución de clarificaciones

| # | Tema | Resolución |
| --- | --- | --- |
| 1 | ¿Existen los campos en algún lado? | Hallazgo: greenfield total |
| 2 | ¿Dónde se valida la paleta/icono cerrados? | D2: enums PHP + reglas; espejo TS con tests |
| 3 | ¿Cómo se hace única la clave por propietario? | D3: unique rule scoped + normalización |
| 4 | ¿Qué superficies muestran la identidad? | D4: ficha + navegación; directorio fuera |
| 5 | ¿Qué pasa con proyectos existentes? | D1: nulables + defaults presentacionales |
| 6 | ¿Quién puede editarla? | D6: owner + activo (sin cambios) |

## Baseline

- 2026-10-09: `php artisan test --compact` → 260 passed, 0 fallos; `npm run build` → OK.
