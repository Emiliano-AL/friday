# Contract: Modal "Gestionar colaboradores"

**Feature**: `specs/016-project-collaborators`
**Date**: 2026-10-05
**Type**: UI contract (Inertia page props + endpoints preservados)

## Semántica

La página `GET /projects/{project}` (miembro del proyecto) renderiza `Projects/Show` con la prop `project`. El propietario ve un botón "Gestionar colaboradores" en la tarjeta Equipo que abre el modal. **El formulario inline anterior desaparece por completo** (FR-001).

## Convenciones

- Mismo estilo de payload que `projectPayload` actual (camelCase, `owner`/`members` separados).
- El modal es de solo presentación: consume los endpoints existentes (Inertia form + redirect), sin endpoints nuevos.
- Elementos deshabilitados: `aria-disabled="true"` + badge "Próximamente" (patente del proyecto).

## Shape del payload de proyecto (cambios respecto al actual)

```jsonc
{
  "project": {
    // …campos existentes sin cambios…
    "isOwner": true,
    "owner": {
      "id": 9,
      "name": "LandaE",
      "email": "landa@mail.com",
      "avatar": null,
      "openTasksCount": 12        // NUEVO: tareas del proyecto no-Done asignadas al propietario
    },
    "members": [                  // colaboradores del pivot (NO incluye al propietario)
      {
        "id": 5,
        "name": "Alex Morales",
        "email": "alex.morales@test.dev",
        "avatar": null,
        "openTasksCount": 3       // NUEVO
      }
    ]
  }
}
```

## Estructura del modal (props `{ open: boolean, project: ProjectDetail }`, emit `close`)

1. **Header**: título "Gestionar colaboradores" + subtítulo "Controla quién tiene acceso en {proyecto}" + cierre (✕ / `ESC` / backdrop, vía `Modal.vue`).
2. **Agregar colaborador**: input de correo con placeholder "correo@empresa.com" + botón "Agregar colaborador"; errores inline por campo (`email`), sin cerrar el modal. Éxito: input limpio y lista actualizada.
3. **Miembros actuales (N)**: lista en este orden:
   - Propietario primero: avatar/iniciales, nombre, correo, badge `Propietario`; sin acciones de eliminar.
   - Cada colaborador: avatar/iniciales, nombre, correo, "N tareas abiertas" (omitido si 0), botón retirar (solo si `isOwner`).
4. **Eliminar**: confirmación nativa (`window.confirm`); si `openTasksCount > 0` el texto advierte "…tiene N tareas abiertas en el proyecto que quedarán sin responsable."; tras confirmar, `DELETE` con `preserveScroll`, modal permanece abierto, lista se actualiza por redirect.
5. **Secciones deshabilitadas** (visibles, sin acción):
   - "Permisos del proyecto": selector de rol mostrando "Editor" (rol genérico actual), deshabilitado + "Próximamente".
   - "Enlace de invitación rápida": botón "Copiar enlace" deshabilitado + "Próximamente".
6. **Footer**: texto honesto "Los colaboradores podrán recibir tareas asignadas de este proyecto." + botón "Listo" (cierra).

## Endpoints (SIN CAMBIOS — contrato preservado por tests existentes)

| Acción | Endpoint (Wayfinder) | Cuerpo | Éxito | Errores |
| --- | --- | --- | --- | --- |
| Agregar | `projects.members.store` → POST `/projects/{project}/members` | `{ email }` | redirect `projects.show` | sesión `email`: duplicado / no registrado / propietario |
| Eliminar | `projects.members.destroy` → DELETE `/projects/{project}/members/{user}` | — | redirect `projects.show` | 404: no propietario, o propietario a sí mismo |

## Regla de opciones de asignación (FR-008)

- `TaskController@index` → `projects[].members` pasa a incluir **propietario + colaboradores** (deduplicado), igual que `Show.vue` ya arma localmente.
- Misma igualdad en el payload de contexto de tareas del detalle de sprint.
- La validación de servidor ya exige colaborador (sin cambios).

## Invariantes verificables (tests)

1. Payload del proyecto incluye `owner` + `members[]` con `openTasksCount` igual al conteo real de tareas no-Done del proyecto por persona.
2. El propietario aparece exactamente una vez en el modal (como Propietario) y nunca tiene botón de eliminar.
3. Los 7 tests existentes de `ProjectMembersTest` siguen pasando sin modificación.
4. El propietario figura entre las opciones de asignación del payload de Mis Tareas.
5. No existe ningún endpoint nuevo ni modificación de contrato en los dos endpoints de miembros.
