<!--
SYNC IMPACT REPORT
==================
Version change: (sin versionar, plantilla sin rellenar) → 1.0.0 (adopción inicial)
Modified principles: ninguna (adopción inicial; los 5 principios reemplazan placeholders)
Added sections: Core Principles (5), Stack Tecnológico Fijo, Workflow de Desarrollo y Quality Gates, Governance
Removed sections: ninguna
Follow-up TODOs: ninguno
-->

# Friday Constitution

## Core Principles

### I. Monolith-First con Inertia (NON-NEGOTIABLE)
Friday es y permanece como una aplicación monolítica. Toda la funcionalidad se entrega
desde un único desplegable: el backend Laravel renderiza páginas mediante
`Inertia::render()` y el estado fluye del servidor al cliente a través de props.
Está prohibido introducir una API REST desacoplada o un frontend SPA autónomo
sin una enmienda constitucional. Rationale: un solo desplegable reduce
boilerplate y fricción operativa, y es la forma idiomática de escalar un MVP
del dominio de Friday.

### II. Alineación con el Ecosistema Laravel
Toda funcionalidad se construye con las herramientas first-party y del ecosistema:
generadores `artisan make:*`, Eloquent, migraciones, form requests, policies y
Vite. Las dependencias (Composer/npm) no se agregan ni cambian sin aprobación
explícita. Rationale: maximiza la mantenibilidad, el onboarding y la
previsibilidad del código para cualquier desarrollador del ecosistema.

### III. Test-First con Pest (NON-NEGOTIABLE)
Toda lógica de dominio nueva (proyectos, sprints, tareas, comentarios, métricas)
debe estar cubierta por tests de feature escritos con Pest usando factories
antes de integrarse. La regla es: tests escritos → fallan → implementación →
verde. Durante el desarrollo se ejecuta el subconjunto mínimo de tests relevante;
el suite completo (`php artisan test --compact`) debe pasar antes de considerar
cerrada cualquier capacidad. Rationale: el dominio de Friday (flujos de estados,
órdenes Kanban/Backlog, cierre de sprints) es propenso a regresiones silenciosas.

### IV. Tipado Estricto y Estados como Enums
PHP 8.4 con tipado explícito: property promotion, type hints en parámetros y
declaraciones de retorno en todos los métodos. Todo estado del dominio
(`task.type`, `task.priority`, `task.status`, `project.status`, `sprint.status`)
se modela como enum de PHP — nunca como strings sueltos. Rationale: la lógica
del tablero Kanban, del backlog y de las métricas de avance depende de máquinas
de estado bien definidas; los enums hacen los estados comprobables en tiempo
de compilación.

### V. Simplicidad y YAGNI (Alcance MVP)
Solo se construye lo que define el alcance del MVP: autenticación Socialite
(Google/GitHub), CRUD de proyectos con métricas de avance, sprints con
activación/cierre, tareas aisladas o por sprint con comentarios, y las vistas
Backlog y Kanban. Toda abstracción, capa o configuración que supere esa
necesidad debe justificarse ante la revisión. Rationale: la velocidad del MVP
es la prioridad; la complejidad prematura es el principal riesgo del proyecto.

## Stack Tecnológico Fijo

- Backend: Laravel 13+ sobre PHP 8.4, con PostgreSQL como única base de datos.
- Frontend: Vue 3 con Composition API (`<script setup>`) e Inertia.js V3 como
  capa de integración; Tailwind CSS para estilos.
- Autenticación: exclusivamente Laravel Socialite (Google/GitHub); no se
  añaden proveedores ni flujos alternativos sin enmienda constitucional.
- Tipado de rutas frontend mediante Wayfinder (`@/actions/`, `@/routes/`).
- Cualquier desviación de este stack requiere aprobación explícita, igual que
  el cambio de dependencias (Principio II).

## Workflow de Desarrollo y Quality Gates

- Estilo de código garantizado con Laravel Pint (`vendor/bin/pint --dirty`);
  código PHP sin formatear no se integra.
- Tests con Pest; los tests de feature son la capa principal de verificación y
  preceden a scripts de verificación ad-hoc o a Tinker.
- Estilos y formateo del frontend siguen las convenciones documentadas en
  `AGENTS.md` / reglas de Laravel Boost del proyecto.
- Errores de frontend se diagnostican con los logs de navegador de Boost antes
  de introducir instrumentación temporal.
- Las nuevas capacidades del dominio (PRs) deben demostrar su escenario
  end-to-end funcionando, no solo compilar.

## Governance

Esta constitución tiene precedencia sobre cualquier otra práctica, guía o
convenio del repositorio. En caso de conflicto, la constitución gana y el
documento conflictivo debe actualizarse.

- Enmiendas: toda modificación requiere actualizar este documento, justificar
  el cambio y registrarlo en el Sync Impact Report del propio archivo.
- Versionado semántico de la constitución: MAJOR para cambios incompatibles
  de gobernanza (eliminación o redefinición de principios), MINOR para
  principios o secciones nuevos o ampliaciones sustanciales, PATCH para
  aclaraciones y correcciones de redacción.
- Cumplimiento: toda revisión de código verifica que el cambio respeta los
  principios activos; la complejidad que contradiga el Principio V debe
  justificarse explícitamente en la revisión.
- Las reglas operativas de runtime (formato, tests, flujo diario) viven en
  `AGENTS.md` y `.ai/rules`; esta constitución define las reglas no
  negociables que esos documentos no pueden contradecir.

**Version**: 1.0.0 | **Ratified**: 2026-09-19 | **Last Amended**: 2026-09-19
