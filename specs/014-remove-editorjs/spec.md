# Feature Specification: Eliminación del editor de bloques EditorJS

**Feature Branch**: `014-remove-editorjs`

**Created**: 2026-10-02

**Status**: Draft

**Input**: User description: "Necesito que me ayudes a remover las referecnias a editorjs @editorjs/editorjs"

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Edición de descripciones sin el editor de bloques (Priority: P1)

Como usuario de Friday, al crear o editar un proyecto, una tarea o un sprint, quiero escribir la descripción (o el goal del sprint) con el nuevo editor de texto enriquecido en lugar del editor de bloques actual, para poder seguir gestionando mi contenido con formato (negritas, listas, encabezados, citas, código) sin depender de esa herramienta.

**Why this priority**: Es el flujo principal afectado: los tres formularios (proyecto, sprint, tarea) usan hoy el editor basado en EditorJS. Si solo se implementara esta historia, el cambio ya tendría valor completo: los formularios siguen funcionando con capacidades de formato equivalentes y la dependencia desaparece.

**Independent Test**: Se puede probar de forma independiente abriendo el formulario de edición de una tarea existente, modificando su descripción (aplicando negritas, una lista y un encabezado) y guardando; la descripción persistida se refleja con formato en la vista de detalle.

**Acceptance Scenarios**:

1. **Given** un proyecto con descripción existente, **When** abro su formulario de edición, **Then** veo el contenido de la descripción en el editor y puedo modificarlo (con formato) y guardarlo sin errores.
2. **Given** un sprint con goal existente, **When** edito el goal y guardo, **Then** el valor persistido se muestra correctamente en la vista de detalle del sprint.
3. **Given** una tarea con descripción existente (incluido contenido creado previamente con formato de bloques), **When** abro su modal de edición, **Then** la descripción aparece completa y legible, y puedo guardar cambios.
4. **Given** un campo de descripción vacío, **When** dejo el campo vacío y guardo, **Then** se persiste como vacío sin errores de validación ni mensajes en consola.

---

### User Story 2 - Visualización del contenido existente (Priority: P2)

Como usuario de Friday, al consultar el detalle de un proyecto, sprint o tarea, quiero que el contenido se siga viendo de forma legible tras el cambio de editor: el contenido creado con el nuevo editor debe mostrar su formato (encabezados, listas, negritas, citas, código), y el contenido heredado debe seguir siendo legible como texto simple.

**Why this priority**: La remoción del editor no debe degradar la experiencia de lectura. El contenido nuevo conservará formato gracias al nuevo editor (FR-005), y el contenido heredado queda migrado a texto plano (FR-004) sin pérdida de legibilidad.

**Independent Test**: Se puede probar de forma independiente abriendo la vista de detalle de una tarea cuya descripción fue creada con el nuevo editor (con encabezados, listas y negritas) y verificando que se renderiza con formato, y abriendo una con contenido heredado (migrado a texto plano) y verificando que se lee como texto simple.

**Acceptance Scenarios**:

1. **Given** una tarea cuya descripción fue creada con el nuevo editor (con encabezados, listas, citas o código), **When** abro su vista de detalle, **Then** el contenido se muestra con su formato estructurado y legible, sin contenido perdido ni HTML crudo.
2. **Given** una descripción heredada migrada a texto plano, **When** abro su vista de detalle, **Then** el texto se muestra legible como párrafo simple.
3. **Given** una descripción con enlaces, **When** se renderiza en la vista de detalle, **Then** los enlaces siguen siendo clicables y seguros (se abren en pestaña nueva con protección, sin scripts).

---

### User Story 3 - Desinstalación completa y sin código muerto (Priority: P3)

Como mantenedor del proyecto, quiero que la eliminación sea total: ningún paquete del ecosistema EditorJS en las dependencias, ni componentes, tipos ni utilidades quedando huérfanas, para no arrastrar deuda ni peso muerto en el repositorio.

**Why this priority**: Es la consolidación del cambio: sin ella, los formularios y la visualización ya funcionan (US1/US2), pero quedaría dependencia instalada y código sin usar. Es necesaria para considerar la remoción cerrada, pero las historias P1/P2 entregan el valor de usuario antes.

**Independent Test**: Se puede probar de forma independiente verificando que las dependencias de EditorJS desaparecen del manifiesto de paquetes, el proyecto compila y arranca, y que no quedan referencias al ecosistema en el código fuente ni utilidades exclusivas del formato bloques sin consumidores.

**Acceptance Scenarios**:

1. **Given** el repositorio tras el cambio, **When** se buscan referencias al ecosistema EditorJS en el código fuente y en el manifiesto de paquetes, **Then** no hay coincidencias.
2. **Given** el proyecto tras el cambio, **When** se instalan dependencias y se compila el frontend, **Then** el proceso completa sin errores y la aplicación arranca.
3. **Given** las utilidades del formato de bloques que quedaron sin consumidores tras la migración, **When** se inspecciona el código, **Then** se eliminaron junto con sus consumidores (sin código muerto), salvo que sigan teniendo un consumidor activo.

---

### Edge Cases

- **Contenido heredado en formato JSON de bloques**: las descripciones/goal guardadas antes del cambio están en formato de bloques; la migración los convierte a texto plano preservando el texto visible, y debe ser idempotente y re-ejecutable sin duplicar ni corromper contenido.
- **Contenido heredado en edición**: al editar con el nuevo editor un registro cuyo contenido se migró a texto plano, el contenido debe cargarse como texto simple sin errores; al guardar, pasa al formato nativo del nuevo editor.
- **Límite de longitud**: las validaciones de servidor existentes (longitud máxima y nulabilidad de descripción/goal) deben seguir aplicando con el nuevo editor.
- **Pegado de contenido externo**: al pegar contenido con formato desde fuentes externas, el editor debe integrarlo de forma segura (sin scripts ni estilos inseguros) y coherente con el formato soportado.
- **Datos a mitad de migración**: la migración de base de datos debe ser idempotente; si una ejecución se interrumpe, re-ejecutarla debe completar la conversión sin corromper filas ya migradas.
- **Validación en modal de tarea**: el modal de tarea valida descripción y muestra errores inline; el nuevo editor debe seguir mostrando errores de validación junto al campo.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: El proyecto NO debe depender de ningún paquete del ecosistema EditorJS (núcleo ni herramientas/Plugins): deben eliminarse del manifiesto de dependencias del frontend.
- **FR-002**: Los formularios de creación/edición de proyectos (descripción), sprints (goal) y tareas (descripción) deben seguir permitiendo introducir, editar y guardar ese contenido con formato usando el nuevo editor (FR-005).
- **FR-003**: Todo el contenido existente (descripciones y goals ya guardados) debe seguir siendo visible y editable tras el cambio; ningún registro existente puede quedar ilegible o inaccesible.
- **FR-004**: El formato JSON de bloques de EditorJS se elimina del modelo de datos: el contenido existente se migra a texto plano mediante una migración de base de datos idempotente. No hay datos en producción, por lo que no existe riesgo de pérdida de contenido en la migración. A partir del cambio, el contenido nuevo se guarda en el formato nativo del nuevo editor (FR-005); el contenido heredado migrado a texto plano debe seguir siendo legible y editable (se interpreta como texto simple).
- **FR-005**: El editor de bloques se sustituye por el editor de texto enriquecido **Tiptap** (https://tiptap.dev/docs) en los formularios de proyecto (descripción), sprint (goal) y tarea (descripción). Debe ofrecer capacidades equivalentes o superiores a las actuales: negritas, cursivas, enlaces, encabezados, listas (viñetas y numeradas), citas, código en línea y fragmentos de código. **Aprobación explícita del usuario registrada**: Tiptap es una dependencia nueva añadida con aprobación explícita, conforme al Principio II de la constitución.
- **FR-006**: No debe quedar código muerto tras la remoción: el componente de edición basado en EditorJS, la declaración de tipos creada para su plugin, las utilidades del formato de bloques (normalización, conversión a HTML/texto, sanitización) y el render de solo lectura por bloques deben eliminarse, siendo reemplazados por sus equivalentes del nuevo editor.
- **FR-007**: Las validaciones de servidor existentes para descripción/goal (nulabilidad y longitud máxima) deben seguir aplicando sin cambios de comportamiento.
- **FR-008**: El nuevo editor debe integrarse en los formularios existentes conservando su comportamiento actual: placeholder, mensaje de error inline junto al campo, foco programático y reseteo al cerrar/reabrir los diálogos.
- **FR-009**: Las vistas de detalle (solo lectura) deben renderizar el contenido del nuevo formato de forma legible y con su formato (encabezados, listas, negritas, citas, código, enlaces), y el contenido heredado migrado a texto plano debe mostrarse como texto simple; en ambos casos el HTML renderizado debe estar sanitizado (sin scripts ni atributos inseguros).

### Key Entities _(include if feature involves data)_

- **Contenido largo de proyecto/sprint/tarea**: los campos `projects.description`, `sprints.goal` y `tasks.description`; texto de formato libre, nullable, con límite de longitud definido en el modelo de datos. Es el único dato afectado por el cambio de formato.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: El 100% de las descripciones y goals existentes siguen siendo visibles tras el cambio (cero registros perdidos o ilegibles).
- **SC-002**: Los usuarios pueden crear y editar descripciones/goals con el mismo número de pasos que antes del cambio (abrir formulario → escribir → guardar).
- **SC-003**: Ningún formulario de proyecto, sprint o tarea muestra errores de validación regresivos ni mensajes de consola como consecuencia del cambio.
- **SC-004**: El código del editor eliminado desaparece por completo del paquete de frontend compilado, cuyo tamaño no aumenta respecto al estado anterior.
- **SC-005**: El suite completo de pruebas del proyecto pasa tras el cambio, incluidas las pruebas de los flujos de creación/edición de proyectos, sprints y tareas.

## Assumptions

- Tiptap es la única dependencia nueva autorizada, aprobada explícitamente por el usuario para esta feature (registrado en FR-005); ninguna otra dependencia se añade ni cambia (Principio II de la constitución).
- No hay contenido en producción: la migración del formato de bloques a texto plano no arriesga pérdida de contenido de usuarios (criterio del usuario para elegir la opción B de FR-004).
- Los especificaciones históricas que introdujeron EditorJS (011, 012, 013) son registro histórico y no se reescriben; el nuevo spec es la referencia vigente.
- El cambio afecta únicamente a los campos de contenido largo (descripción de proyecto/tarea y goal de sprint); ninguna otra capacidad del dominio (tablero, sprints, métricas) cambia.
- Las decisiones de implementación de Tiptap (paquetes exactos, versión, formato nativo de persistencia, configuración de extensiones y sanitización) se definen en la fase de planificación con la documentación oficial vigente; el spec fija el comportamiento, no la configuración interna.
