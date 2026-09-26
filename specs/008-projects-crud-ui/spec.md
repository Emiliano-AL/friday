# Feature Specification: Rediseño del CRUD de Proyectos

**Feature Branch**: `[008-projects-crud-ui]`

**Created**: 2026-09-25

**Status**: Draft

**Input**: User description: "Ahora ayudame a mejorar la UI de proyectos el CRUD completo, seguimos respetando @DESIGN.md y el layout general que ya trabajaste: ahora vamos a trabajar con el flujo de un proyecto, quiza algunas porpiedades aun no existen, documentalas para trabjarlas posteriormente, a nivel de UI ponlas como proximamente. Te paso el html del flujo de projectos (listado, agregar, editar y detalle de proyecto)."

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Directorio de proyectos (Priority: P1)

El usuario autenticado abre la sección Proyectos y encuentra un directorio visual de todas las iniciativas en las que participa (como líder o como miembro). La página muestra un encabezado con el total de proyectos activos, una barra de búsqueda por nombre, píldoras de filtrado por estado (Todos / Activos / Completados / Archivados, con contadores reales), un selector de ordenamiento (recientes, alfabético, progreso) y un conmutador de vista (cuadrícula o lista compacta). Cada tarjeta de proyecto presenta su identidad visual, título, descripción, estado, porcentaje de progreso calculado con datos reales, el sprint activo cuando existe, la fecha límite cuando exista, el equipo asignado (avatares con desbordamiento "+N") y un menú contextual de acciones. Una tarjeta de acción rápida "Iniciar un nuevo proyecto" siempre visible invita a crear. Al final, un panel de indicadores muestra métricas reales (proyectos en marcha, sprints completados) y deja marcadas como "Próximamente" las métricas que aún no tienen datos (entregas a tiempo, velocidad de equipo).

**Why this priority**: Es la puerta de entrada al flujo de trabajo; sin un directorio claro el usuario no puede llegar a ninguna otra capacidad de proyecto. Es el lienzo donde viven las acciones de crear, editar, archivar y entrar al detalle.

**Independent Test**: Puede probarse de forma aislada creando varios proyectos con distintos estados, tareas y miembros, y verificando que el directorio refleja los contadores, el progreso y los filtros con exactitud, sin salir de la página.

**Acceptance Scenarios**:

1. **Given** que el usuario participa en 5 proyectos (3 activos, 1 completado, 1 archivado), **When** abre el directorio, **Then** ve las píldoras "Activos (3)", "Completados (1)" y "Archivados (1)", el chip "3 Activos" junto al título, y las tarjetas muestran progreso y equipo coherentes con los datos reales.
2. **Given** que el usuario escribe un nombre en la búsqueda, **When** confirma, **Then** solo permanecen visibles los proyectos cuyo nombre coincide, y si ninguno coincide aparece un estado vacío con opción de limpiar el filtro.
3. **Given** que el usuario selecciona la píldora "Archivados", **When** la vista se actualiza, **Then** solo se listan proyectos archivados y sus acciones de edición no aparecen (solo reactivar y ver detalle).
4. **Given** que el usuario no tiene proyectos, **When** abre el directorio, **Then** ve un estado vacío amable con una única acción destacada: iniciar un nuevo proyecto.
5. **Given** que el usuario abre el menú de una tarjeta, **When** elige "Archivar", **Then** el proyecto desaparece de "Activos" y aparece contabilizado en "Archivados", sin perder información.

---

### User Story 2 - Crear y editar proyecto desde un diálogo (Priority: P2)

Desde el directorio (botón "Nuevo Proyecto", tarjeta de acción rápida o atajo de teclado "N"), el usuario crea un proyecto en un diálogo modal con los campos que existen hoy: nombre (obligatorio) y descripción breve (opcional). Los campos del diseño de referencia que aún no existen en los datos —clave de 4 letras, color de acento, fecha objetivo e icono— se muestran deshabilitados con la marca "Próximamente", documentando la intención sin bloquear el flujo. La edición usa el mismo diálogo, precargado, accesible desde el menú de tarjeta o del detalle; incluye una zona de peligro que permite al líder eliminar el proyecto tras una confirmación explícita. Los errores de validación se muestran junto a cada campo y el foco va al primer campo inválido. El borrado, la edición y el archivado solo están disponibles para el líder del proyecto; los demás miembros solo pueden ver y navegar.

**Why this priority**: Crear y mantener proyectos es la operación de escritura esencial del CRUD; el directorio (P1) ya ofrece los puntos de entrada, y este flujo los completa sin cambiar de página, manteniendo el contexto del usuario.

**Independent Test**: Puede probarse de forma aislada creando un proyecto desde cero con nombre y descripción, editándolo, archivándolo, reactivándolo y eliminándolo, verificando permisos con una segunda cuenta miembro (que no ve las acciones de escritura).

**Acceptance Scenarios**:

1. **Given** que el usuario pulsa "N" fuera de cualquier campo de texto, **When** está en el directorio, **Then** se abre el diálogo de creación con el foco en el nombre.
2. **Given** que el usuario deja el nombre vacío e intenta crear, **When** envía el formulario, **Then** ve un mensaje claro junto al campo nombre y el foco se posiciona en él; no se cierra el diálogo.
3. **Given** que el usuario abre el diálogo de creación, **When** observa la sección de identidad visual, **Then** los selectores de clave, color e icono aparecen deshabilitados con la marca "Próximamente" y un texto que anuncia que llegarán próximamente.
4. **Given** que el usuario es el líder, **When** abre el diálogo de edición de su proyecto archivado, **Then** puede reactivarlo y los campos de contenido permanecen editables solo si el proyecto está activo.
5. **Given** que el usuario miembro (no líder) abre el menú de una tarjeta, **When** busca acciones de edición, **Then** no las encuentra y, si intenta forzar la ruta de edición, el sistema se lo impide con un estado de acceso denegado.
6. **Given** que el líder elige "Eliminar proyecto" en la zona de peligro, **When** confirma en el diálogo de confirmación, **Then** el proyecto se elimina y el usuario regresa al directorio con un aviso de éxito.

---

### User Story 3 - Detalle de proyecto con pestañas (Priority: P3)

Al entrar a un proyecto, el usuario ve una cabecera de identidad: ruta de navegación (Proyectos / nombre), título con su clave cuando exista, descripción, insignias de estado y sprint activo, y un conjunto de acciones (nuevo sprint, nueva tarea, editar). Debajo, una franja de metadatos muestra al líder, al equipo asignado con desbordamiento "+N", el progreso global con barra y conteo de tareas terminadas, y la fecha objetivo cuando exista. El contenido se organiza en pestañas: "General y resumen" (metadatos, sprint activo con su avance, próximo sprint, acceso al backlog), "Sprints y tareas" (la funcionalidad existente de sprints, tareas y tablero del proyecto, preservada), y tres pestañas futuras —"Documentación", "Recursos y enlaces" e "Historial"— que aparecen deshabilitadas con la marca "Próximamente". Las acciones "Compartir" y las métricas de salud/velocidad del diseño de referencia se muestran como "Próximamente" en su lugar correspondiente.

**Why this priority**: El detalle consume lo construido en P1/P2 y reorganiza la funcionalidad ya existente de sprints y tareas bajo un mismo techo; es la historia con mayor superficie pero la de menor riesgo, porque no introduce operaciones de escritura nuevas.

**Independent Test**: Puede probarse de forma aislada verificando que cada pestaña renderiza su contenido correcto, que los metadatos coinciden con los datos reales del proyecto, y que la funcionalidad previa de sprints y tareas sigue operando dentro de su pestaña.

**Acceptance Scenarios**:

1. **Given** un proyecto activo con sprint activo, tareas y 4 miembros, **When** el usuario abre el detalle, **Then** la franja de metadatos muestra al líder, "4 especialistas" (o el conteo real), el progreso con el conteo de tareas terminadas y la insignia de estado con el nombre del sprint activo.
2. **Given** que el usuario cambia a la pestaña "Sprints y tareas", **When** la vista carga, **Then** encuentra la funcionalidad existente de sprints y tareas operando igual que antes del rediseño.
3. **Given** que el usuario pulsa "Nuevo sprint", **When** confirma la creación, **Then** el sprint aparece en la pestaña correspondiente y la insignia del detalle se actualiza si es el primer sprint activo.
4. **Given** un proyecto sin tareas, **When** el usuario abre el detalle, **Then** el progreso muestra 0% sin conteo de fracción y la sección de tareas muestra su estado vacío habitual.
5. **Given** que el usuario visita las pestañas "Documentación", "Recursos y enlaces" o "Historial", **When** intenta abrirlas, **Then** permanecen deshabilitadas con la marca "Próximamente".

---

### Edge Cases

- **Directorio vacío**: el usuario sin proyectos ve una invitación clara a crear el primero, no un listado en blanco.
- **Búsqueda sin resultados**: mensaje de "sin coincidencias" con acción para limpiar filtros.
- **Proyecto sin tareas**: progreso 0% sin fracción "(0/0)" visible; conteo de sprints completados puede ser 0.
- **Proyecto sin sprint activo**: la tarjeta y la insignia muestran "Sin sprint activo" en lugar del nombre.
- **Textos largos**: títulos y descripciones se truncan con elipsis sin romper el layout.
- **Equipos grandes**: avatares apilados con contador "+N" (máximo 3 visibles) en tarjetas y detalle.
- **Proyecto archivado o completado**: contenido en solo lectura; solo se permite reactivar (líder) y ver el detalle.
- **Permisos**: miembro no líder no ve acciones de escritura; intentos directos por ruta son rechazados con acceso denegado.
- **Doble envío**: el botón de guardar se deshabilita mientras el formulario procesa.
- **Atajos de teclado**: "N" y "⌘F" no se disparan con el foco en campos de texto.
- **Pantallas pequeñas**: a 375px de ancho no aparece scroll horizontal; las tarjetas pasan a una columna y el diálogo ocupa el ancho disponible.
- **Eliminación**: solo el líder, con confirmación explícita; al eliminar, el usuario regresa al directorio.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: El sistema debe mostrar en el directorio únicamente los proyectos en los que el usuario participa (líder o miembro), nunca proyectos ajenos.
- **FR-002**: Cada tarjeta de proyecto debe mostrar como mínimo: título, descripción (truncada si es larga), estado con etiqueta legible, porcentaje de progreso calculado como tareas terminadas entre tareas totales (0% si no hay tareas), sprint activo cuando exista, y equipo asignado con avatares y contador de desbordamiento.
- **FR-003**: El directorio debe ofrecer píldoras de filtrado por estado (Todos, Activos, Completados, Archivados) con contadores reales derivados del conjunto visible para el usuario.
- **FR-004**: El directorio debe ofrecer búsqueda por nombre de proyecto con resultados inmediatos y un estado vacío cuando no hay coincidencias.
- **FR-005**: El directorio debe ofrecer ordenamiento por recientes (predeterminado), alfabético y progreso, y un conmutador de vista cuadrícula/lista cuya preferencia se recuerda entre visitas.
- **FR-006**: Debe existir una tarjeta de acción rápida "Iniciar un nuevo proyecto" que abra el diálogo de creación, con atajo de teclado "N" (sin conflicto con campos de texto) y acción equivalente en la paleta de comandos global.
- **FR-007**: La creación y edición de proyecto deben realizarse en un diálogo modal con los campos existentes (nombre obligatorio, descripción opcional), validación inline, foco gestionado y cierre con "Escape" o clic fuera.
- **FR-008**: Los campos del diseño de referencia que aún no existen en los datos (clave de identificación de hasta 4 letras, color de acento con paleta predefinida, icono del proyecto y fecha objetivo) deben presentarse en los formularios deshabilitados y marcados como "Próximamente", junto con una nota que explique que estarán disponibles en una próxima versión; deben quedar documentados en el plan como propiedades futuras del dominio.
- **FR-009**: Solo el líder del proyecto puede editar, archivar, completar, reactivar o eliminar; el sistema debe ocultar esas acciones a los demás miembros y rechazar cualquier intento directo con acceso denegado.
- **FR-010**: El menú contextual de cada tarjeta debe ofrecer al líder: editar, archivar o completar (según el estado actual) y reactivar (si está archivado o completado); las transiciones de estado deben respetar la máquina de estados del dominio (activo ↔ archivado/completado).
- **FR-011**: La eliminación de un proyecto debe exigir confirmación explícita, ser accesible únicamente desde la zona de peligro del diálogo de edición y, tras ejecutarse, devolver al usuario al directorio con un aviso de confirmación.
- **FR-012**: El detalle de proyecto debe presentar: ruta de navegación, título y descripción, insignia de estado con sprint activo, franja de metadatos (líder, equipo con desbordamiento, progreso global con conteo de tareas terminadas, fecha objetivo cuando exista) y acciones de nuevo sprint y nueva tarea operativas.
- **FR-013**: El detalle debe organizar su contenido en pestañas: "General y resumen" (predeterminada), "Sprints y tareas" (funcionalidad existente preservada) y las pestañas futuras "Documentación", "Recursos y enlaces" e "Historial" deshabilitadas con la marca "Próximamente".
- **FR-014**: Las acciones y métricas del diseño de referente que dependen de capacidades aún inexistentes (compartir, prioridad del proyecto, salud del proyecto, velocidad de equipo, entregas a tiempo) deben mostrarse como "Próximamente" en su lugar correspondiente, nunca con datos simulados.
- **FR-015**: El panel de indicadores del directorio debe mostrar con datos reales el número de proyectos en marcha y el número de sprints completados; los indicadores de entregas a tiempo y velocidad de equipo deben mostrarse como "Próximamente".
- **FR-016**: Toda la experiencia debe funcionar en modo claro, respetar el sistema de diseño (tipografías, espaciados, radios y sombras definidos) y ser utilizable a 375px de ancho sin scroll horizontal.
- **FR-017**: El diálogo y las vistas deben ser operables por teclado: foco inicial y retorno correctos, cierre con "Escape", orden de foco lógico y anillos de foco visibles con contraste suficiente.

### Key Entities _(include if feature involves data)_

- **Proyecto** (existente): iniciativa de trabajo con título, descripción, estado (activo, completado, archivado), líder, miembros, sprints y tareas. El progreso se deriva de las tareas terminadas.
- **Sprint** (existente): ciclo de trabajo del proyecto; el detalle y las tarjetas referencian al sprint activo cuando existe.
- **Tarea** (existente): unidad de trabajo con estado; alimenta el porcentaje de progreso.
- **Propiedades futuras del Proyecto** (documentadas, sin persistencia en esta feature): clave de identificación de hasta 4 letras mayúsculas; color de acento elegido de una paleta fija de 7 colores; icono elegido de un conjunto fijo; fecha objetivo; prioridad del proyecto; etiqueta de área o equipo.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: El usuario puede crear un proyecto nuevo (nombre y descripción) en menos de 30 segundos y con un máximo de 3 interacciones desde el directorio.
- **SC-002**: El usuario localiza un proyecto por nombre en el directorio en menos de 10 segundos usando la búsqueda o los filtros.
- **SC-003**: El 100% de los valores mostrados (progreso, contadores, equipos, sprints) coincide con los datos reales del sistema; ningún dato simulado se presenta como real y todo lo no disponible se marca "Próximamente".
- **SC-004**: La experiencia completa es utilizable a 375px de ancho sin scroll horizontal ni elementos cortados.
- **SC-005**: El 100% de las acciones de escritura (crear, editar, archivar, completar, reactivar, eliminar) quedan restringidas al líder del proyecto, verificable con una cuenta miembro no líder.

## Assumptions

- Los campos clave, color, icono, fecha objetivo, prioridad y área/equipo **no** se agregan a los datos en esta feature; se documentan como propiedades futuras y su presencia en la interfaz es informativa ("Próximamente").
- La creación y la edición usan el mismo diálogo modal; la referencia visual solo ilustra la creación, pero el mismo patrón se aplica a la edición por consistencia con el resto de la aplicación.
- La acción "Ajustes" del diseño de referencia se resuelve como el diálogo de edición del proyecto.
- La acción "Compartir" y la gestión visual de miembros quedan fuera de alcance (futuro), aunque la capacidad de datos de miembros ya existe.
- El indicador decorativo "Sincronizado con Friday Cloud" de la referencia se omite porque no comunica un estado real del sistema.
- El progreso del proyecto se calcula como tareas terminadas entre tareas totales, con 0% cuando no hay tareas.
- El modo oscuro queda fuera de alcance (la aplicación opera en modo claro).
- La funcionalidad existente de sprints, tareas y tablero del proyecto se preserva íntegra dentro de la pestaña "Sprints y tareas".
- Los contadores de las píldoras reflejan el universo de proyectos del usuario, no solo los visibles tras la búsqueda.
