# Quickstart: Sección Sprints del detalle de proyecto

**Feature**: `specs/017-sprints-section`
**Date**: 2026-10-06

Guía de validación end-to-end. Prerequisitos: proyecto instalado, PostgreSQL (Herd) activo y `npm run build` ejecutado tras los cambios frontend.

## 1. Verificación automática

```bash
php artisan test --compact        # suite completa (backend intacto: los tests de payload deben seguir verdes)
npm run check && npm run build    # tipos/lint/frontend
```

**Esperado**: suite verde sin tests nuevos (cero cambios de backend); vue-tsc sin errores; build OK.

## 2. Verificación manual (navegador)

Datos de partida sugeridos (dev): el proyecto 17 tiene activo "Checkout exprés", planificado "Pulido y lanzamiento" y sprints completados de sprints anteriores. Ajusta según tu BD.

Escenario A — estructura y datos reales:
1. Entra como miembro/propietario a `/projects/17` y abre la pestaña "Sprints y tareas".
2. **Esperado**: encabezado "Sprints · N en total", chips con conteos exactos por estado y botón "Planificar un sprint"; tarjeta expandida del activo con badge "Sprint Activo", fechas, días restantes, meta, "Progreso de tareas" con % y "X de Y tareas" reales; debajo "Otros Sprints" con el planificado y completados.
3. Compara el % y "X de Y" con el tablero del sprint (abrir "Ver tablero"): deben coincidir.

Escenario B — filtros:
1. Pulsa cada chip (Activo/Planificados/Completados/Todos).
2. **Esperado**: la lista "Otros Sprints" se filtra al instante; los conteos de los chips no cambian; un filtro sin resultados muestra mensaje vacío honesto.

Escenario C — acciones y permisos:
1. Como propietario: en un planificado verás "Editar"/"Eliminar" (eliminar pide confirmación y desaparece); en el activo "Completar sprint" (con confirmación) y "Ver tablero"; en completados "Ver resumen".
2. **Esperado**: cada acción ejecuta el flujo existente (modal de edición, borrado con confirmación, completado con flash, navegación al sprint).
3. Como colaborador (no propietario): mismas vistas sin acciones de mutación.

Escenario D — estados vacíos y límites:
1. Proyecto sin sprints → estado vacío con "Planificar un sprint".
2. Sprint activo vencido → "0 días restantes" sin errores.
3. Sprint sin meta → sin bloque "Meta del Sprint".
4. **Esperado**: ningún elemento simulado; "Puntos de historia" aparece como "Próximamente" deshabilitado en la tarjeta activa y no aparece en filas.

Escenario E — colapsar:
1. Pulsa "Colapsar todos" y luego "Expandir".
2. **Esperado**: la lista se oculta/muestra al instante y conserva el estado mientras dura la sesión.

## 3. Regresión de la pestaña General

1. Abre la pestaña "General y resumen".
2. **Esperado**: las tarjetas "Sprint Actual", "Próximo Sprint" y Backlog se ven **igual que antes** (no se tocaron).

## Referencias

- Contrato del componente: [contracts/sprints-section.md](contracts/sprints-section.md)
- Reglas de datos y derivaciones: [data-model.md](data-model.md)
- Decisiones técnicas: [research.md](research.md)
