# Quickstart: Panel de inicio con actividad del usuario

**Feature**: `specs/015-home-active-dashboard`
**Date**: 2026-10-03

Guía de validación end-to-end. Prerequisitos: proyecto instalado (`composer install`, `npm install`), PostgreSQL corriendo (Herd) y `npm run dev` o build activo para el frontend.

## 1. Verificación automática (backend)

```bash
php artisan test --compact                      # suite completo
php artisan test --compact tests/Feature/Home   # tests nuevos de la feature
vendor/bin/pint --dirty --format agent          # formato (solo si hay cambios PHP)
```

**Esperado**: suite verde, incluidos los nuevos tests de `tests/Feature/Home/` (payload con/sin actividad, actividades registradas, scope por membresía, regresión del estado vacío) y `tests/Feature/Shell/ShellRenderingTest.php` (contrato del sidebar intacto).

## 2. Verificación manual del comportamiento (navegador)

Escenario A — estado vacío (regresión):
1. Registra/inicia sesión con un usuario nuevo (sin proyectos ni tareas).
2. Visita `/`. **Esperado**: se ve el lienzo actual "Espacio listo para crear" (sin cambios visuales).

Escenario B — panel con actividad:
1. Con ese usuario: crea un proyecto, un sprint (inícialo) y 2–3 tareas asignadas a ti; completa una tarea; añade un comentario.
2. Vuelve a `/`. **Esperado**: saludo personalizado + subtítulo contextual, tarjetas de resumen, "Mis tareas para hoy & prioritarias", panel del sprint activo con gráfico, proyectos activos, insight semanal y feed con los eventos que acabas de generar (todo coherente con lo creado).
3. Clic en "Ir al sprint" / "Abrir tablero del sprint" → tablero del sprint activo. "Ver todas mis tareas" → `tasks.index`. "Ver todos" → `projects.index`. "Nueva tarea" → paleta de comandos.

Escenario C — aislamiento:
1. Con un segundo usuario (sin membresía en el proyecto anterior), visita `/`. **Esperado**: estado vacío (no ve nada del proyecto ajeno). Tras añadirlo como miembro y generar actividad, su feed muestra solo eventos de sus proyectos.

Escenario D — sin sprint activo: completa/cierra el sprint o crea tareas sin sprint. **Esperado**: desaparecen tarjeta, panel y botón de sprint; el resto del panel se mantiene.

## 3. Verificación del registro de actividad (tinker)

```bash
php artisan tinker --execute '
$p = Project::first(); $t = $p->tasks()->first();
$u = User::first();
\App\Models\Activity::where("project_id", $p->id)->latest()->limit(5)->get(["type","user_id","task_id","created_at"]);'
```

**Esperado**: filas `task_created`/`task_completed`/`comment_created`/`sprint_started` con `user_id` del actor real.

## 4. QA frontend

```bash
npm run check        # vue-tsc + eslint, sin errores
```

**Esperado**: sin errores de tipos ni lint en los componentes nuevos (`resources/js/Components/Home/**`, `AppHome.vue`).

## Referencias

- Contrato del payload: [contracts/dashboard-payload.md](contracts/dashboard-payload.md)
- Reglas de datos: [data-model.md](data-model.md)
- Decisiones técnicas: [research.md](research.md)
