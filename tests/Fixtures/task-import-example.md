# Pendientes — Sprint 12

Pendientes convertidos desde la code review del módulo de inventario.

---

## Prioridad Alta

### Hacer atómica la generación de PIN
- **Tipo:** Bug
- **Prioridad:** Alta
- **Responsable:** Emiliano
- **Descripción:** `generatePin()` usa `exists()` + `create()`, lo que deja una condición de carrera. Agregar un índice `UNIQUE` en `(warehouse_id, pin_code)` y reintentar ante `UniqueConstraintViolationException`. (Origen: 4-3 W1)

---

## Prioridad Media

### Escapar comodines LIKE en las búsquedas
- **Tipo:** Bug
- **Prioridad:** Media
- **Responsable:** Emiliano
- **Descripción:** `%` y `_` pasan sin escapar en `ItemManager` y `ProviderManager`. Aplicar `str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $search)` en todo el proyecto. (Origen: 2-5, 2-7)

---

## Prioridad Baja

### Tests de `viewAny` para los roles purchasing y warehouse
- **Tipo:** Test
- **Prioridad:** Baja
- **Responsable:** Emiliano
- **Descripción:** AC4 exige que todos los roles pasen `viewAny` en ítems, pero solo admin y supervisor tienen test. (Origen: 2-5)

---

## No incluidos (resueltos o sin acción)

- **Truncamiento de `StockLevel.quantity`** (3-3): es por diseño, sin acción.
