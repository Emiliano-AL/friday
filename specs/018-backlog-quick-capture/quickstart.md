# Quickstart: Validación de Backlog General

**Fecha**: 2026-10-08 | **Feature**: [spec.md](spec.md) | **Contrato**: [contracts/backlog.md](contracts/backlog.md)

Guía para validar la feature de extremo a extremo. Prerequisitos: entorno con
`composer install` + `npm install` ejecutado, base de datos migrada
(`php artisan migrate`), servidor de desarrollo (`composer run dev` o
`php artisan serve` + `npm run dev` para regenerar rutas Wayfinder).

## 1. Rutas y navegación

1. `php artisan route:list --name=backlog` → existe `GET /backlog` con
   middleware `auth`.
2. Con Wayfinder regenerado, `resources/js/Layouts/AuthenticatedLayout.vue`
   tiene el item "Backlog" habilitado (ya no muestra "Próximamente") y apunta a
   `/backlog`.

## 2. Escenarios manuales (navegador)

| # | Escenario | Pasos | Resultado esperado |
| --- | --- | --- | --- |
| 1 | Captura mínima | Entrar a `/backlog`, escribir "Revisar webhook de Stripe", Enter | El pendiente aparece al inicio de "Media" con código `FRN-N`, sin proyecto. Total del encabezado +1. |
| 2 | Validación | Enter con el campo vacío | Mensaje de error junto al campo; nada se guarda. |
| 3 | Persistencia | Recargar tras la captura | El pendiente sigue listado. |
| 4 | Captura con prioridad/proyecto | Elegir "Urgente" y un proyecto en los chips, Enter | Aparece en "Alta y urgente" y bajo el filtro "Con proyecto". |
| 5 | Filtros y conteos | Alternar Todas / Sin proyecto / Con proyecto | Conteos del encabezado coinciden con lo listado. |
| 6 | Triage | Abrir el pendiente, asignar proyecto y cambiar a prioridad alta, guardar | Se mueve a "Alta y urgente"; bajo "Sin proyecto" ya no aparece. |
| 7 | Puntos | Editar puntos = 5 | El encabezado suma +5 pts. |
| 8 | Entrega | Con proyecto asignado, vincular al sprint próximo | Desaparece del backlog; visible en la planificación del sprint. |
| 9 | Borrado | Eliminar un pendiente y confirmar | Sale de la lista y se permanece en `/backlog`. |
| 10 | Rollover de sprint | Cerrar el sprint del escenario 8 con el pendiente sin terminar | El pendiente reaparece en el backlog conservando proyecto, prioridad y puntos. |
| 11 | Visibilidad | Con otro usuario (miembro de otro proyecto), entrar a `/backlog` | No ve los pendientes "sin proyecto" ajenos; solo los propios y los de sus proyectos. |
| 12 | Sprint próximo | Ver el banner superior | Muestra el sprint activo/planificado más próximo entre mis proyectos. |

## 3. Validación automática

```bash
# Tests de la feature (Pest)
php artisan test --compact tests/Feature/Backlog

# Suites tocadas por los cambios (policy, request, rollover)
php artisan test --compact tests/Feature/Tasks tests/Feature/Sprints

# Estilo
vendor/bin/pint --dirty

# Suite completa antes de cerrar
php artisan test --compact
```

Archivos de prueba clave (creados en la fase de implementación):

- `tests/Feature/Backlog/BacklogPageTest.php` — payload, scopes, conteos,
  resumen, `nextSprint`, autenticación.
- `tests/Feature/Backlog/QuickCaptureTest.php` — captura título-único con
  defaults (status `backlog`, prioridad `medium`, type `other`, sin
  proyecto/responsable), generación de `FRN-###` secuencial, rechazo de título
  vacío.
- `tests/Feature/Backlog/BacklogTriageTest.php` — asignación de proyecto
  (miembro / no miembro), puntos, cambio de prioridad, permisos de borrado
  (autor sí, tercero no).
- `tests/Feature/Backlog/SprintRolloverTest.php` — completar sprint devuelve
  pendientes sin terminar al backlog; las completadas se quedan.
- Ajustes en `tests/Feature/Tasks/*` y `tests/Feature/Sprints/*` por el cambio
  de policy/request/observer.

## 4. Criterios de aceptación rápidos

- [ ] `php artisan test --compact` en verde completo.
- [ ] Los 12 escenarios manuales pasan.
- [ ] No hay rutas API nuevas; las mutaciones usan `tasks.*` (Constitución I).
- [ ] `vendor/bin/pint --dirty` sin cambios.
