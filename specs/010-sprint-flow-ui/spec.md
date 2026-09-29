# Feature Specification: Flujo de Sprint en la UI

**Feature Branch**: `010-sprint-flow-ui`

**Created**: 2026-09-28

**Status**: Draft

**Input**: User description: "Ahora vamos a trabajar el flujo de sprint, en la UI. tenemos el @DESIGN.md y te paso los archivos html con el diseño trabajado previamente. (modal crear nuevo sprint + detalle del sprint: generales, cobertura, lista de tareas)"

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Detalle completo de un sprint (Priority: P1)

Un miembro del proyecto entra a la ficha de un sprint desde la lista de sprints
del proyecto y ve, en una sola vista: el encabezado del sprint (nombre, estado,
fechas, días restantes y porcentaje de tiempo transcurrido, número de
iteración), métricas reales de avance (cobertura de tareas completadas,
desglose por estado, totales) y la lista de tareas del sprint con búsqueda,
filtros y agrupación. Desde ahí puede añadir una tarea al sprint y abrir las
tareas existentes.

**Why this priority**: Es la vista donde el equipo vive el sprint día a día;
sin ella el sprint solo existe como una fila de fechas en el proyecto. Las
métricas y la lista de tareas aportan el valor inmediato sobre datos que ya
existen en el dominio.

**Independent Test**: Navegar desde la lista de sprints de un proyecto al
detalle de uno con tareas y comprobar que todas las cifras (cobertura,
desglose, conteos, días restantes) corresponden a los datos reales de sus
tareas y fechas, y que la lista filtra y agrupa correctamente.

**Acceptance Scenarios**:

1. **Given** un proyecto con un sprint que tiene tareas en varios estados, **When** un miembro abre el detalle del sprint, **Then** ve el nombre, el estado, el rango de fechas, los días laborables restantes y el porcentaje de tiempo transcurrido, la cobertura (tareas completadas sobre totales, en porcentaje y en números), el desglose por estado con conteo y porcentaje de cada uno, y la lista completa de tareas del sprint.
2. **Given** el detalle de un sprint, **When** el miembro busca, filtra por responsable o tipo, o cambia la agrupación (por estado o por prioridad), **Then** la lista se actualiza al instante mostrando contadores por grupo coherentes con lo visible.
3. **Given** el detalle de un sprint, **When** el miembro pulsa "Añadir tarea a este sprint", **Then** se abre el diálogo de tarea existente con el proyecto y el sprint ya preseleccionados, y al guardar la tarea aparece en la lista del sprint.
4. **Given** un sprint sin tareas, **When** un miembro abre su detalle, **Then** las métricas muestran cero de forma comprensible y la lista muestra un estado vacío que invita a añadir la primera tarea.
5. **Given** un sprint de un proyecto al que el usuario no pertenece (o inexistente), **When** intenta abrir su detalle, **Then** recibe un error de acceso indistinguible de un sprint inexistente.

---

### User Story 2 - Diálogo de creación y edición de sprint (Priority: P2)

El propietario de un proyecto activo crea un sprint desde un diálogo con el
look & feel de referencia: nombre, objetivo del sprint (campo nuevo, opcional),
fecha de inicio y fecha de fin, con la misma validación de fechas existente
(la fecha de fin no puede ser anterior a la de inicio). La edición reutiliza el
mismo diálogo precargado.

**Why this priority**: El alta/edit ya existe funcionalmente pero con una
presentación mínima; alinearla al diseño y añadir el objetivo completa la
ficha del sprint (el objetivo se muestra luego en el detalle) sin cambiar las
reglas del dominio.

**Independent Test**: Crear un sprint con nombre, objetivo y fechas válidas
desde el proyecto y comprobar que aparece en la lista y que su detalle muestra
el objetivo; comprobar que una fecha de fin anterior a la de inicio es
rechazada con el error correspondiente.

**Acceptance Scenarios**:

1. **Given** el propietario de un proyecto activo, **When** crea un sprint con nombre, objetivo y fechas válidas, **Then** el sprint se guarda con esos datos y aparece en la lista de sprints del proyecto con su objetivo disponible en el detalle.
2. **Given** el diálogo de creación, **When** guarda sin nombre o con fecha de fin anterior a la de inicio, **Then** el sistema rechaza la operación mostrando los errores en línea junto a los campos y no se guarda nada.
3. **Given** un sprint existente, **When** el propietario abre la edición, **Then** el diálogo muestra los datos actuales (nombre, objetivo, fechas) y al guardar los cambios se reflejan en la lista y en el detalle.
4. **Given** un colaborador (no propietario) o un proyecto no activo, **When** mira el proyecto, **Then** no encuentra acciones de crear ni editar sprints, y cualquier intento forzado es rechazado por el servidor.

---

### User Story 3 - Ciclo de vida del sprint (Priority: P3)

El propietario gestiona el ciclo del sprint: al crearlo puede arrancarlo de
inmediato o dejarlo planificado; desde el detalle puede activarlo y
completarlo. El chip de estado del encabezado refleja ese ciclo
(planificado / activo / completado) en todo lugar donde se muestra.

**Why this priority**: Da sentido a las acciones "Comenzar inmediatamente" y
"Completar Sprint" del diseño de referencia y a la condición de solo lectura
de los sprints cerrados; depende del detalle (US1) y del diálogo (US2) para
ser usable.

**Independent Test**: Crear un sprint arrancándolo de inmediato, verificar el
estado activo; completarlo desde el detalle y verificar que pasa a completado
y que las acciones de escritura desaparecen.

**Acceptance Scenarios**:

1. **Given** el propietario creando un sprint, **When** activa "Comenzar inmediatamente" y guarda, **Then** el sprint queda activo; si no la activa, queda planificado.
2. **Given** un sprint planificado, **When** el propietario lo activa desde el detalle, **Then** el estado pasa a activo y el cambio se refleja en el encabezado y en la lista del proyecto.
3. **Given** un sprint activo, **When** el propietario lo completa confirmando la acción, **Then** el estado pasa a completado y las acciones de escritura del sprint dejan de ofrecerse.
4. **Given** un sprint completado, **When** cualquier miembro lo consulta, **Then** lo ve en solo lectura con su estado visible.

---

### Edge Cases

- ¿Qué pasa si el sprint no tiene tareas? Cobertura 0 % (0 de 0), desglose vacío y estado vacío con invitación a añadir la primera tarea.
- ¿Qué pasa con fechas futuras o pasadas? El porcentaje de tiempo transcurrido se acota entre 0 % (aún no empieza) y 100 % (ya terminó); los días restantes nunca son negativos.
- ¿Qué pasa si dos sprints del mismo proyecto se solapan en fechas? Ambos existen y son navegables; la numeración de iteración los ordena por fecha de inicio sin imponer restricción de solapamiento.
- ¿Qué pasa si el proyecto está archivado o completado? El detalle queda en solo lectura: se ven métricas y tareas, pero no se ofrecen acciones de escritura (crear/editar/eliminar sprint, añadir/editar tareas, ni acciones de ciclo de vida).
- ¿Qué pasa si se elimina un sprint que tiene tareas? El comportamiento actual del dominio se conserva (el proyecto y sus tareas no se ven afectados); la lista de tareas del detalle nunca muestra tareas de otro sprint.
- ¿Qué pasa si se intenta reactivar o desactivar un sprint? Las transiciones son solo hacia adelante (planificado → activo → completado): reactivar un sprint completado o volver de activo a planificado no está permitido y el sistema lo rechaza con un mensaje claro.
- ¿Qué pasa con nombres muy largos o objetivos extensos? Se truncan visualmente con elipsis en encabezado y tarjetas sin romper el layout.
- ¿Qué pasa si un usuario no miembro accede al detalle? Acceso denegado indistinguible de un sprint inexistente.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: El sistema debe ofrecer una vista de detalle por sprint, navegable desde la lista de sprints de un proyecto (migas: Proyectos / proyecto / Sprints / sprint), visible para cualquier miembro del proyecto; quien no es miembro no puede acceder, ni siquiera de solo lectura.
- **FR-002**: El encabezado del detalle debe mostrar como mínimo: nombre del sprint, chip de estado, número de iteración (posición ordinal del sprint entre los sprints del proyecto, ordenados por fecha de inicio), rango de fechas (inicio – fin), días laborables restantes y porcentaje de tiempo transcurrido derivados de las fechas, y el objetivo del sprint cuando existe.
- **FR-003**: Las métricas del detalle deben calcularse únicamente con datos reales de las tareas del sprint: cobertura (tareas en estado terminado sobre el total, en porcentaje y en números), desglose por estado con conteo y porcentaje de cada estado existente del dominio, y total de tareas del sprint.
- **FR-004**: La lista de tareas del detalle debe permitir: búsqueda de texto (con atajo ⌘F / Ctrl+F enfocando el buscador), filtro por responsable, filtro por tipo y agrupación conmutada por estado o por prioridad; cada grupo muestra su contador, y cada tarea muestra los datos reales disponibles (título, tipo, prioridad, estado, responsable, comentarios).
- **FR-005**: Desde el detalle se debe poder añadir una tarea al sprint usando el diálogo de tarea ya existente, con proyecto y sprint preseleccionados; y abrir/editar las tareas listadas con ese mismo diálogo.
- **FR-006**: El diálogo de creación/edición de sprint debe incluir: nombre obligatorio (máximo 255 caracteres), objetivo opcional (texto libre corto), fecha de inicio obligatoria y fecha de fin obligatoria; debe validar que la fecha de fin no sea anterior a la de inicio, mostrando los errores en línea junto a los campos. El diálogo se presenta en el contexto del proyecto (el proyecto es el del contexto, no un selector).
- **FR-007**: El sistema debe gestionar el ciclo de vida del sprint como estado real del dominio, con las transiciones planificado → activo → completado: la creación ofrece "comenzar inmediatamente" (si no se activa, el sprint queda planificado), el detalle permite activar un sprint planificado y completar un sprint activo (ambas con su confirmación cuando aplique); el estado se muestra como chip en el detalle y en la lista de sprints del proyecto, y un sprint completado queda en solo lectura. Las transiciones son solo hacia adelante: no se puede reactivar un sprint completado ni volver de activo a planificado.
- **FR-008**: Las reglas de permisos existentes se conservan: crear, editar y eliminar sprints solo el propietario del proyecto y solo con el proyecto activo; los colaboradores consultan en solo lectura; cualquier intento forzado fuera de esa regla es rechazado por el servidor con un mensaje claro.
- **FR-009**: Las funcionalidades del diseño de referencia que no existen en los datos deben mostrarse deshabilitadas con "Próximamente" y quedar documentadas como propiedades futuras: story points de tarea (y toda métrica basada en puntos: burn-up, velocidad, ritmo, capacidad, carga por miembro), selección de tareas del backlog al crear el sprint, gráfica de burndown, notas de daily, bitácora de actividad, estado "bloqueado" de tarea (grupo "Bloqueadas"), clave legible de tarea (prefijo-folio) y porcentaje de progreso individual de tarea.
- **FR-010**: El acceso a los sprints sigue siendo por proyecto: el elemento global "Sprints" de la barra lateral permanece como "Próximamente" (una vista transversal de sprints queda fuera de alcance y documentada como futura).

### Key Entities _(include if feature involves data)_

- **Sprint** (existente, ampliado): periodo de trabajo de un proyecto. Atributos actuales: nombre (obligatorio), fecha de inicio, fecha de fin, pertenencia a proyecto. Ampliaciones de esta feature: objetivo opcional (texto libre corto) y estado del ciclo de vida (planificado / activo / completado) con transiciones solo hacia adelante. Métricas derivadas (cobertura, desglose) se calculan sobre sus tareas, sin persistencia propia.
- **Tarea** (existente): se lista y filtra dentro del detalle del sprint a través de su vinculación opcional a un sprint; no cambia su modelo.
- **Proyecto** (existente): contenedor y contexto de navegación; su estado determina solo lectura y sus miembros determinan acceso.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: El propietario crea un sprint completo (nombre, objetivo y fechas) en menos de 1 minuto, y lo edita en menos de 30 segundos.
- **SC-002**: El detalle de un sprint con hasta 100 tareas carga en menos de 2 segundos, con todas las métricas visibles sin interacción adicional.
- **SC-003**: El 100 % de las cifras mostradas en el detalle (cobertura, desglose, conteos, días restantes) coinciden con los datos reales del sprint; ningún dato simulado se presenta como real.
- **SC-004**: Al menos el 90 % de los miembros completa sin ayuda el flujo crear sprint → abrir detalle → añadir tarea al sprint.

## Assumptions

- El dominio ya permite tareas vinculadas a un sprint y sprints ilimitados por proyecto sin restricción de solapamiento; esto no cambia.
- Se añade el campo "objetivo" al sprint como dato opcional (texto corto); los permisos y la validación de fechas existentes se conservan.
- El ciclo de vida del sprint es parte del alcance (confirmado con el usuario): el sprint tendrá estado real (planificado / activo / completado) con transiciones solo hacia adelante; la derivación por fechas deja de usarse como sustituto del estado. La activación de un sprint planificado está permitida aunque exista otro sprint activo en el proyecto (sin exclusividad forzada en esta iteración).
- La navegación es por proyecto: no hay vista transversal de sprints en esta iteración; el elemento "Sprints" del menú global permanece "Próximamente".
- La creación de sprints ocurre siempre dentro del contexto de un proyecto (el diseño de referencia incluye un selector de proyecto pensado para una creación global que queda fuera de alcance).
- Las tareas se gestionan con los diálogos existentes (crear/editar/comentarios), reutilizados con proyecto y sprint preseleccionados; no se construye un diálogo nuevo de tarea.
- Solo se trabaja el modo claro definido en DESIGN.md.
- "Próximamente" sigue la convención del proyecto: control deshabilitado real, sin navegación, sin foco y sin datos simulados.
