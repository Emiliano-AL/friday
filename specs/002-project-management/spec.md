# Feature Specification: Gestión de Proyectos (CRUD, métricas y colaboradores)

**Feature Branch**: `002-project-management`

**Created**: 2026-09-20

**Status**: Draft

**Input**: User description: "Gestión de proyectos, CRUD, proyectos multidisciplinarios, métricas y porcentaje de avance, asignación de colaboradores."

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Crear y listar mis proyectos (Priority: P1)

Un usuario autenticado crea un proyecto con un título obligatorio y una
descripción opcional, y lo ve en su lista de proyectos junto a su estado y su
porcentaje de avance. Los proyectos son multidisciplinarios: sirven para
software, operaciones, marketing, legal o gestión personal, sin campos
especializados que limiten el tipo de trabajo.

**Why this priority**: Sin altas y listado no existe nada sobre lo que operar;
es la mitad mínima del CRUD y la entrada a todo el resto de la plataforma.

**Independent Test**: Crear un proyecto válido y comprobar que aparece en la
lista del creador con estado inicial y avance 0%: si solo esto existiera, ya
sería un gestor de proyectos funcional.

**Acceptance Scenarios**:

1. **Given** un usuario autenticado, **When** crea un proyecto con título y descripción, **Then** el proyecto se guarda, el usuario queda registrado como su propietario y es redirigido a la vista del proyecto.
2. **Given** un usuario autenticado, **When** intenta crear un proyecto sin título, **Then** ve un mensaje de error indicando que el título es obligatorio y no se crea nada.
3. **Given** un usuario con al menos un proyecto, **When** abre su lista de proyectos, **Then** ve todos los proyectos de los que es miembro con su título, estado y porcentaje de avance.

---

### User Story 2 - Ver, editar y gestionar el ciclo de vida de un proyecto (Priority: P1)

Un miembro del proyecto abre su ficha, edita título o descripción, y cambia su
estado (activo, archivado, completado) según el momento del ciclo de vida.

**Why this priority**: Completar el CRUD (lectura, edición, eliminación y
ciclo de vida) que define el alcance de la feature; comparte prioridad máxima
con US1 porque juntas cubren la operación diaria sobre proyectos.

**Independent Test**: Crear un proyecto, editar sus campos, archivarlo,
reactivarlo y completarlo; verificar que cada cambio persiste y se refleja en
la lista: si solo esto existiera, el ciclo de vida del proyecto ya sería
gestionable de extremo a extremo.

**Acceptance Scenarios**:

1. **Given** el propietario de un proyecto, **When** edita el título o la descripción y guarda, **Then** los cambios se persisten y se muestran en la ficha y en la lista.
2. **Given** el propietario de un proyecto activo, **When** lo archiva, **Then** el proyecto pasa a estado archivado y sus datos quedan de solo lectura hasta que se reactive.
3. **Given** el propietario de un proyecto archivado, **When** lo reactiva, **Then** vuelve a estado activo y puede editarse de nuevo.
4. **Given** el propietario de un proyecto activo, **When** lo marca como completado, **Then** pasa a estado completado; el propietario puede reactivarlo si fue un error.
5. **Given** el propietario de un proyecto, **When** lo elimina confirmando la acción, **Then** el proyecto y sus membresías desaparecen y ya no aparece en ninguna lista.

---

### User Story 3 - Métricas de avance del proyecto (Priority: P2)

Cualquier miembro ve en la lista y en la ficha el porcentaje de avance del
proyecto, calculado a partir de sus tareas: tareas completadas sobre tareas
totales. Un proyecto sin tareas muestra 0%.

**Why this priority**: Es la visibilidad de progreso que diferencia a Friday de
una simple lista; depende de que existan proyectos (US1) y se enriquecerá
cuando el módulo de tareas entre en vigor, pero la visualización y la regla de
cálculo deben estar definidas desde ahora.

**Independent Test**: Verificar que un proyecto sin tareas muestra 0% y que la
regla de cálculo (completadas/totales) queda documentada y verificable: la
fórmula completa se validará con datos reales cuando exista el módulo de
tareas.

**Acceptance Scenarios**:

1. **Given** un proyecto sin tareas, **When** cualquier miembro lo consulta en lista o ficha, **Then** el avance mostrado es 0%.
2. **Given** un proyecto con tareas, **When** cambia el número de tareas completadas, **Then** el porcentaje refleja completadas/totales, redondeado a entero, en lista y ficha por igual.
3. **Given** un porcentaje calculado, **When** se muestra en cualquier vista, **Then** siempre está entre 0% y 100% y es consistente entre vistas.

---

### User Story 4 - Asignación de colaboradores (Priority: P2)

El propietario añade usuarios registrados como colaboradores de un proyecto y
los retira cuando haga falta; los colaboradores ven el proyecto en su propia
lista.

**Why this priority**: Los proyectos son trabajo de equipo por definición;
asignar colaboradores habilita el uso real multiusuario, pero es un incremento
posterior al núcleo CRUD.

**Independent Test**: Con dos usuarios registrados, el propietario añade al
segundo como colaborador y este ve el proyecto en su lista; al retirarlo,
desaparece de su lista: si solo esto existiera, la colaboración básica ya
funcionaría.

**Acceptance Scenarios**:

1. **Given** el propietario de un proyecto y un usuario registrado que no es miembro, **When** el propietario lo añade por correo, **Then** el usuario aparece en la lista de miembros y el proyecto aparece en su lista personal.
2. **Given** un proyecto con un colaborador, **When** el propietario lo retira, **Then** deja de ser miembro y el proyecto desaparece de su lista.
3. **Given** un propietario, **When** intenta añadir como colaborador un correo no registrado o un miembro existente, **Then** ve un mensaje de error claro y no se crea una membresía duplicada.

---

### Edge Cases

- **Título inválido**: vacío, solo espacios o que exceda la longitud máxima → error de validación claro, sin crear ni corromper el proyecto.
- **Porcentaje con proyecto vacío**: sin tareas, el avance es 0% (nunca error ni división por cero).
- **Correo de colaborador inexistente**: mensaje "usuario no registrado"; no se envía invitación por correo en el MVP.
- **Añadir dos veces al mismo colaborador**: no se duplica la membresía; mensaje informativo.
- **Retirar al propietario**: no permitido; el propietario no puede retirarse a sí mismo mientras existe el proyecto.
- **Acceso de no miembros**: un usuario que no es miembro no puede ver ni editar el proyecto (ni listarlo ni adivinarlo por URL).
- **Proyecto archivado o completado**: datos de solo lectura (salvo reactivación por el propietario).
- **Eliminar un proyecto con colaboradores**: la eliminación también disuelve las membresías; los colaboradores dejan de verlo.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: El sistema MUST permitir a un usuario autenticado crear un proyecto con título obligatorio (máximo 255 caracteres) y descripción opcional; el creador queda registrado automáticamente como miembro propietario.
- **FR-002**: El sistema MUST mostrar a cada usuario una lista de los proyectos de los que es miembro, con título, estado actual y porcentaje de avance.
- **FR-003**: El sistema MUST permitir a cualquier miembro ver la ficha del proyecto: título, descripción, estado, porcentaje de avance y miembros.
- **FR-004**: El sistema MUST permitir al propietario editar el título y la descripción del proyecto.
- **FR-005**: El sistema MUST gestionar el ciclo de vida del proyecto con los estados activo, archivado y completado: activo ↔ archivado y activo ↔ completado, siendo el propietario quien cambia de estado.
- **FR-006**: El sistema MUST calcular el porcentaje de avance como tareas completadas sobre tareas totales del proyecto, redondeado a entero; un proyecto sin tareas tiene 0% de avance.
- **FR-007**: El sistema MUST permitir al propietario añadir colaboradores indicando el correo de un usuario registrado; el colaborador pasa a ver el proyecto en su lista.
- **FR-008**: El sistema MUST permitir al propietario retirar colaboradores; el propietario no puede retirarse a sí mismo.
- **FR-009**: El sistema MUST impedir que usuarios que no son miembros vean, listen o modifiquen un proyecto (acceso denegado sin revelar la existencia del proyecto).
- **FR-010**: El sistema MUST validar los datos de entrada: título requerido con longitud máxima y, al añadir colaboradores, correo con formato válido y correspondiente a un usuario registrado que no sea ya miembro.
- **FR-011**: El sistema MUST permitir al propietario eliminar un proyecto tras confirmación, disolviendo sus membresías.
- **FR-012**: Los proyectos MUST funcionar para cualquier disciplina (software, operaciones, marketing, legal, personal) sin campos obligatorios específicos de un dominio.

### Key Entities _(include if feature involves data)_

- **Proyecto**: unidad de trabajo con título, descripción opcional, estado del ciclo de vida (activo, archivado, completado) y porcentaje de avance calculado a partir de sus tareas. Pertenece a un propietario (usuario creador).
- **Membresía de proyecto**: relación entre un usuario y un proyecto que habilita el acceso; cada proyecto tiene exactamente un propietario (su creador) y cero o más colaboradores.
- **Usuario**: miembro de la plataforma (existente); referenciado como propietario y como colaborador. En esta feature no se modifican sus atributos.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Un usuario crea un proyecto en menos de 1 minuto en su primer intento.
- **SC-002**: La lista de proyectos de un usuario con hasta 50 proyectos carga en menos de 2 segundos en el 95% de las consultas.
- **SC-003**: El porcentaje de avance mostrado es idéntico en lista y ficha en el 100% de los casos y siempre está entre 0% y 100%.
- **SC-004**: El 100% de los accesos de no miembros a un proyecto (por lista, URL directa o edición) son denegados.
- **SC-005**: El 100% de las altas de colaborador válidas reflejan al colaborador en la membresía en menos de 2 segundos, y el 0% de las altas duplicadas o con correo inexistente crean membresías.
- **SC-006**: Un 90% de los propietarios completa crear-editar-archivar sin necesidad de ayuda en su primera sesión.

## Assumptions

- El porcentaje de avance se calcula por conteo de tareas (completadas/totales); la futura ponderación por peso de tarea se añadirá con el módulo de tareas sin cambiar esta regla base.
- En el MVP, los colaboradores deben ser usuarios ya registrados en la plataforma; no hay envío de invitaciones por correo.
- El propietario (creador) es el único que puede editar campos, cambiar el estado, gestionar colaboradores y eliminar el proyecto; los colaboradores tienen acceso de lectura en el MVP. Los permisos más finos quedan para una iteración posterior.
- No hay roles intermedios (admin, editor, etc.): solo propietario y colaborador.
- Los estados posibles del proyecto son exactamente: activo, archivado y completado (Principio IV de la constitución: estados del dominio como enums).
- La eliminación del proyecto es definitiva (hard delete) y disuelve sus membresías; las tareas futuras definirán su propia política de borrado.
- Los proyectos no tienen campos visuales opcionales (imagen de portada, color, fechas) en esta iteración; el README no los lista en el boceto del modelo.
- Toda la funcionalidad exige sesión autenticada (feature 001); no hay proyectos públicos ni anónimos.
