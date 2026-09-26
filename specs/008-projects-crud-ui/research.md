# Research: Rediseño del CRUD de Proyectos (008)

**Fase 0** — resolución de unknowns técnicos y decisiones de diseño. Todas las decisiones usan el stack existente (constitución I, II, V); no hay dependencias nuevas ni NEEDS CLARIFICATION pendientes.

## R1. Estrategia de diálogo modal (crear/editar) en Inertia

- **Decision**: el diálogo `ProjectFormModal` se hospeda en `Projects/Index.vue` (modo crear y editar) y en `Projects/Show.vue` (modo editar). Los GET `projects.create` y `projects.edit` se convierten en redirects: `projects.index?new=1` y `projects.show?edit=1`; las páginas detectan el query param y abren el diálogo, limpiando la URL con `router.replace`.
- **Rationale**: la spec (FR-007) exige diálogo modal; en Inertia el patrón idiomático es state en la página + deep-linking por query param (back/forward del navegador funcionan, y se comparte URL). Conserva las rutas existentes (Wayfinder, tests de acceso) sin mantener dos superficies.
- **Alternativas consideradas**: (a) mantener páginas Create/Edit completas — rechazada, contradice FR-007 y rompe el flujo contextual de la referencia; (b) páginas Create/Edit que renderizan solo el modal sobre fondo vacío — rechazada, experiencia rota al recargar; (c) componente `<dialog>` Inertia con página modal dedicada — rechazada, duplica hosts del diálogo.

## R2. Reestilo del Modal Breeze vs modal nuevo del shell

- **Decision**: reestilizar `resources/js/Components/Modal.vue` (Breeze) a tokens: veil `bg-inverse-surface/20 backdrop-blur-sm`, panel `bg-surface-container-lowest rounded-xl shadow-modal`. API (`show`, `maxWidth`, `closeable`, slots) sin cambios.
- **Rationale**: un solo punto de cambio eleva visualmente los modales de sprints/tareas existentes (consistencia inmediata con 007) sin tocar su lógica. `<dialog>` nativo ya da foco atrapado + ESC.
- **Alternativas**: modal nuevo AppShell y migrar todos — rechazada (mayor superficie, doble estándar temporal).

## R3. Búsqueda, filtros y ordenamiento del directorio

- **Decision**: client-side sobre el payload completo del directorio. El servidor envía todos los proyectos del usuario con sus derivaciones; el cliente resuelve búsqueda por nombre, píldoras por estado, orden (recientes/alfabético/progreso) y conmutador cuadrícula/lista. Preferencia de vista en `localStorage`.
- **Rationale**: ≤ decenas de proyectos por usuario (MVP); interactividad instantánea sin endpoints nuevos (YAGNI). "Recientes" = `updated_at` desc (orden por defecto del servidor).
- **Alternativas**: endpoints de búsqueda/filtro server-side — rechazada, complejidad sin beneficio a esta escala.

## R4. "Sprint activo" sin estado de dominio

- **Decision**: heurística de lectura — sprint activo = el sprint con mayor `start_date` tal que `start_date <= hoy` (los sprints vencidos siguen siendo "activos" hasta que el equipo cierre, coherente con la práctica). Se implementa como relación `Project::activeSprint(): HasOne` (`where('start_date', '<=', today())->orderByDesc('start_date')`), eager-loaded en índice y detalle. Sin `SprintStatus` (enmienda de dominio fuera de alcance).
- **Rationale**: la referencia muestra "Sprint 14 Activo" en tarjeta y detalle (FR-002/FR-012) y el dominio no tiene estado de sprint; la heurística da dato real sin migración.
- **Alternativas**: (a) añadir enum `SprintStatus` + columna — rechazada por V (cambio de dominio en una feature de UI; se documenta como trabajo futuro); (b) rango de fechas que contenga hoy — rechazada, un sprint vencido no desaparece de la UI sin razón.

## R5. Progreso del proyecto (fórmula real)

- **Decision**: `Project::progressPercentage()` = `round(tareas Done / tareas totales * 100)`, 0 cuando no hay tareas. Solo `TaskStatus::Done` cuenta como terminada (criterio "done" del docblock existente).
- **Rationale**: el stub devuelve 0 "hasta que exista el módulo de tareas" — ya existe (features 004/005). SC-003 exige valores reales. `ProjectProgressTest` ya fija el contrato (0 sin tareas, mismo valor en index/show, rango 0-100); se amplía con casos done/total.
- **Alternativas**: incluir `InReview` como terminada — rechazada, no es el criterio documentado.

## R6. KPIs del directorio

- **Decision**: reales → "Proyectos en marcha" (count activos del usuario) y "Sprints completados este trimestre" (sprints del usuario con `end_date < hoy` dentro del trimestre calendario actual). Futuros → "Entregas a tiempo" y "Velocidad de equipo" renderizados como tarjetas "Próximamente" (FR-015), sin sparkline simulado.
- **Rationale**: FR-015; los dos primeros se derivan de datos existentes, los otros dos requieren motor de métricas que no existe.
- **Alternativas**: omitir las tarjetas futuras — rechazada, la spec pide mostrarlas como "Próximamente"; simular sparkline — rechazada (SC-003).

## R7. Color de acento y clave de proyecto

- **Decision**: sin acento por proyecto en esta iteración. Barras de progreso, puntos de estado y bordes usan el token `primary`; mapa de estados: Activo → `primary`, Completado → `tertiary`, Archivado → `outline-variant`. Clave/color/icono/fecha objetivo se presentan en el formulario como controles deshabilitados con chip "Próximamente" y nota (FR-008).
- **Rationale**: esas propiedades no existen en datos (spec Assumptions); pintar hex arbitrarios por tarjeta sería simulación. Los estados mapean a tokens de `DESIGN.md`, no a los hex de la maqueta.
- **Alternativas**: color derivado por hash del id — rechazada, identidad visual no estable ni configurable.

## R8. Flash messages

- **Decision**: `HandleInertiaRequests` comparte `flash: { success: string|null, error: string|null }` (de sesión); los redirects de `ProjectController` (store/update/status/destroy/members) añaden `->with('success', ...)`; `AuthenticatedLayout` renderiza `FlashMessage.vue` (banner dismissible sobre el contenido, `role="status"`).
- **Rationale**: US2 escenario 6 y estados de éxito del CRUD requieren aviso; hoy no hay ningún canal (grep: 0 resultados). Banner mínimo sin librería (II, V).
- **Alternativas**: librería toast — rechazada (dependencia nueva); alerts inline por página — rechazada, se pierde el aviso tras redirect.

## R9. Eliminación de proyecto

- **Decision**: zona de peligro dentro de `ProjectFormModal` (modo editar, solo líder), confirmación con `confirm()` nativo, `router.delete`, redirect al directorio con flash. Se retira el botón inline de eliminar de `Show.vue` (FR-011: solo desde el diálogo).
- **Rationale**: cumple FR-011 con el mínimo de superficie; `confirm()` es explícito y bloqueante.
- **Alternativas**: diálogo de confirmación custom con campo de texto — rechazada (V, la spec exige "confirmación explícita", no un formato concreto).

## R10. Pestañas del detalle y preservación de funcionalidad

- **Decision**: `Show.vue` se reestructura con `ProjectTabs.vue`: "General y resumen" (activa por defecto: franja de metadatos, tarjeta sprint activo, sprint siguiente, banner backlog, tarjeta Equipo con la gestión de miembros existente movida aquí) y "Sprints y tareas" (secciones existentes de sprints/tareas/tablero preservadas, componentes `Tasks/*` sin cambios). "Documentación", "Recursos y enlaces" e "Historial" quedan deshabilitadas con "Próximamente" (FR-013). Botones de transición de estado existentes se conservan; "Compartir" (header) → botón con tooltip "Próximamente" (FR-014); "Ajustes" abre el diálogo de edición; "Nuevo Sprint"/"Nueva Tarea" cambian a la pestaña operativa.
- **Rationale**: preserva capacidades construidas (003–005, miembros) sin duplicarlas; el header de la referencia se honra en forma, con los puestos no disponibles marcados.
- **Alternativas**: eliminar gestión de miembros en favor de "Compartir" futuro — rechazada, sería una regresión.

## R11. Contadores de píldoras

- **Decision**: el servidor envía `counts: { active, completed, archived }` calculado sobre **todo** el universo de proyectos del usuario (independiente de la búsqueda), conforme a la Assumption de la spec. "Todos" = suma.
- **Rationale**: evita ambigüedad y drift entre contador y filtro.

## R12. Avatares

- **Decision**: `avatar: string|null` se añade a `owner` y `members` en payloads de projects (la columna existe vía OAuth). `AvatarStack` muestra hasta 3 avatares (líder primero, luego miembros) + "+N".
- **Rationale**: FR-002/FR-012; fallback a iniciales con el `Avatar` existente de AppShell cuando `avatar` es null.

## R13. Páginas Create.vue / Edit.vue

- **Decision**: se eliminan. `ProjectController::create()` → `redirect()->route('projects.index', ['new' => 1])`; `edit()` conserva el 404 para no-propietarios y redirige a `projects.show?edit=1` para el líder. `ContextSwitcher` (shell 007) apunta a `create.url()` → el redirect abre el diálogo automáticamente (flujo consistente).
- **Rationale**: una sola superficie del diálogo; los tests existentes solo exigen el 404 de edit para no-propietarios y nada sobre el render de create.
- **Alternativas**: conservar páginas como fallback — rechazada, doble mantenimiento del formulario.

## R14. Paleta de comandos

- **Decision**: se añade la acción global "Crear nuevo proyecto" (icono `add`, keywords `proyecto, nuevo, crear`) que visita `projects.index.url() + '?new=1'`; el diálogo abre al llegar. Se conservan las acciones existentes de 007.
- **Rationale**: FR-006 exige acción equivalente en la paleta; extiende el contrato del shell sin romperlo.
