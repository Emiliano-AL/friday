# Quickstart: Gestión de colaboradores del proyecto

**Feature**: `specs/016-project-collaborators`
**Date**: 2026-10-05

Guía de validación end-to-end. Prerequisitos: proyecto instalado, PostgreSQL (Herd) activo y `npm run build` ejecutado tras los cambios frontend.

## 1. Verificación automática (backend)

```bash
php artisan test --compact                              # suite completa
php artisan test --compact tests/Feature/Projects       # contrato de miembros + tests nuevos del payload
vendor/bin/pint --dirty --format agent                  # formato
npm run check && npm run build                          # tipos/lint/frontend
```

**Esperado**: suite verde incluyendo los 7 tests existentes de `ProjectMembersTest` (sin modificación), los nuevos tests de payload (`openTasksCount`, propietario en opciones de asignación) y cero errores de tipos.

## 2. Verificación manual (navegador)

Escenario A — reemplazo del inline form:
1. Entra como propietario de un proyecto con miembros (o créalos) y abre `/projects/{id}`.
2. **Esperado**: ya NO existe el formulario inline de "Agregar colaborador" en la tarjeta Equipo; en su lugar hay un botón "Gestionar colaboradores" (solo visible para el propietario).
3. Ábrelo. **Esperado**: modal con propietario primero (badge "Propietario", sin eliminar) y cada colaborador con avatar/iniciales, nombre, correo y "N tareas abiertas" cuando aplica.

Escenario B — agregar:
1. En el modal, escribe el correo de un usuario existente no miembro y confirma.
2. **Esperado**: aparece en la lista sin recargar, el input se limpia y la tarjeta Equipo de la página refleja el cambio.
3. Repite con: un correo ya miembro (mensaje "ya es miembro"), un correo no registrado (mensaje "no encontrado"), el correo del propietario (mensaje de duplicado). El modal permanece abierto en todos los casos.

Escenario C — eliminar con advertencia:
1. Con un colaborador que tenga tareas abiertas asignadas en el proyecto, pulsa retirar.
2. **Esperado**: confirmación que advierte "…tiene N tareas abiertas…quedarán sin responsable"; al confirmar, desaparece del modal y de la tarjeta Equipo, y sus tareas del proyecto quedan sin responsable (comprobar en el tablero/tareas).
3. Verifica que el propietario NO tiene botón de eliminar y que "Listo" cierra el modal.

Escenario D — funciones deshabilitadas:
1. Dentro del modal, pulsa el selector de rol, "Copiar enlace" y cualquier elemento con "Próximamente".
2. **Esperado**: nada ocurre (sin navegación ni cambios); el footer solo afirma que los colaboradores pueden recibir tareas asignadas.

Escenario E — asignación limitada a colaboradores:
1. En "Mis Tareas" crea una tarea en un proyecto: abre el selector de responsable.
2. **Esperado**: las opciones son exactamente propietario + colaboradores de ese proyecto (el propietario aparece aunque no seas tú).
3. Intenta asignar a un externo (vía consola/red si se desea): el servidor rechaza con "El responsable debe ser miembro del proyecto."

Escenario F — no propietario:
1. Entra como colaborador (no propietario) del mismo proyecto.
2. **Esperado**: no ve el botón "Gestionar colaboradores" ni acciones de retirar en la tarjeta Equipo.

## 3. Verificación del conteo de tareas abiertas (tinker)

```bash
php artisan tinker --execute '
$p = Project::withCount(["tasks" => fn ($q) => $q->where("status", "!=", "done")])->find($id);
$p->members->map(fn ($m) => [$m->name, $m->tasks()->where("project_id", $p->id)->where("status", "!=", "done")->count()]);'
```

**Esperado**: los conteos coinciden con los `openTasksCount` mostrados en el modal.

## Referencias

- Contrato del modal y endpoints: [contracts/members-modal.md](contracts/members-modal.md)
- Reglas de datos: [data-model.md](data-model.md)
- Decisiones técnicas: [research.md](research.md)
