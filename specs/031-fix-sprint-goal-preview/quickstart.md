# Quickstart: validar la vista previa del objetivo del sprint

## Prerrequisitos

- `npm run build` (o `composer run dev`) después del cambio.
- Un proyecto activo con al menos:
  - Un sprint **planificado** con objetivo enriquecido: dos párrafos, una negrita y una lista.
  - Un sprint **activo** con objetivo enriquecido.
  - Un sprint antiguo con objetivo en **texto plano**. Si no existe, créalo con tinker solo en local: `Sprint::factory()->create(['goal' => 'Texto plano antiguo'])`.

## Verificación automática

```bash
npm run types:check
npx vp check resources/js/Components/Sprints/ProjectSprintsSection.vue resources/js/Components/Home/SprintPanel.vue
php artisan test --compact tests/Feature/Sprints tests/Feature/Home
```

Lo esperado es que todo pase sin errores; los payloads no cambian.

## Validación manual

1. **Proyecto → "Sprints y tareas" → "Otros sprints"**: debajo del nombre se ve el texto del objetivo en una línea, truncado. Al pasar el cursor, la ayuda muestra el texto completo legible. No aparece `{"type":"doc"…`.
2. **Tarjeta del sprint activo** (misma pestaña): el objetivo se ve como texto legible.
3. **Inicio (Home) → panel del sprint**: el objetivo se ve como texto legible, en dos líneas como máximo.
4. **Sprint en texto plano**: se ve igual que antes.
5. **Objetivo con solo párrafos vacíos**: no aparece línea de resumen.
6. **Detalle del sprint**: el objetivo conserva su formato (negritas y lista).
