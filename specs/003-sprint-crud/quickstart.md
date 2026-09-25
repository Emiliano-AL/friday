# Quickstart: validación end-to-end — Gestión de Sprints

**Feature**: 003-sprint-crud | **Date**: 2026-09-21
**Contrato**: [contracts/web-sprints-surface.md](./contracts/web-sprints-surface.md) ·
**Modelo**: [data-model.md](./data-model.md)

Escenarios ejecutables que demuestran la feature de extremo a extremo. No
incluye código de implementación.

## Prerequisites

- Features 001 (auth) y 002 (proyectos) instaladas y funcionando.
- Dependencias y build al día: `composer install && npm install && npm run build`.
- Tests en SQLite `:memory:`; desarrollo con la DB del `.env` (PostgreSQL
  según constitución).

```bash
php artisan migrate        # aplica create_sprints_table
npm run build              # regenera Wayfinder con projects.sprints.*
```

## Escenarios de validación

S1–S4 son manuales por navegador; S5–S7 son automáticos (Pest). Los escenarios
automáticos cubren las mismas reglas que los manuales.

### S1 — Alta y listado (US1)

1. Como propietario de un proyecto activo, abrir la ficha del proyecto →
   sección Sprints vacía con formulario de alta.
2. Crear un sprint con nombre, fecha de inicio y fecha de fin → aparece en la
   lista de la ficha.
3. Crear varios sprints con fechas de inicio desordenadas → la lista siempre
   aparece ordenada por fecha de inicio ascendente.
4. Crear sin nombre, con fecha de fin anterior a la de inicio o con valores no
   fecha → error por campo visible; no se guarda nada.

### S2 — Edición (US2)

1. Editar nombre y fechas de un sprint desde el modal → la lista refleja los
   nuevos valores.
2. Guardar una fecha de fin anterior a la de inicio → error; el sprint
   conserva sus datos anteriores.

### S3 — Eliminación (US3)

1. Eliminar un sprint confirmando la acción → desaparece de la lista; el
   proyecto y sus demás sprints permanecen intactos.
2. Iniciar la eliminación y no confirmarla → no se elimina nada.

### S4 — Solo lectura y permisos (US4)

1. Proyecto archivado o completado: la lista de sprints sigue visible, sin
   acciones de escritura; forzar la petición manualmente → mensaje "El
   proyecto no admite cambios en su estado actual.".
2. Colaborador (miembro no propietario): ve la lista en cualquier estado, sin
   acciones de escritura; forzar una escritura → 404.
3. Usuario que no es miembro abre la ficha del proyecto → 404.
4. Crear sprints con fechas pasadas o solapados entre sí → se aceptan
   (FR-009).

### S5 — CRUD automatizado

```bash
php artisan test --compact --filter=SprintCrudTest
```

Esperado: alta válida asociada al proyecto y visible en la lista, orden por
fecha de inicio ascendente, validaciones (nombre requerido, `end_date` ≥
`start_date`), edición de nombre/fechas, eliminación sin afectar al proyecto
ni a otros sprints, fechas pasadas y solapamiento permitidos.

### S6 — Acceso y estados automatizado

```bash
php artisan test --compact --filter=SprintAccessTest
```

Esperado: colaborador en solo lectura (escritura → 404), no miembro → 404 en
la ficha y en las escrituras, propietario en proyecto archivado/completado
recibe `errors.project` con el mensaje de bloqueo, la lista sigue visible
fuera del estado activo.

### S7 — Calidad de contrato

```bash
composer ci:check
npm run types:check
```

Esperado: todos los gates pasan.

## Criterio de "feature funcionando"

S1–S4 verificados manualmente + S5–S6 en verde + S7 sin violaciones. Cubre los
escenarios de aceptación US1–US4 y habilita el paso a
`/skill:speckit-tasks`.
