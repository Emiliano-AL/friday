# Quickstart: validación end-to-end — Gestión de Proyectos

**Feature**: 002-project-management | **Date**: 2026-09-20
**Contrato**: [contracts/web-projects-surface.md](./contracts/web-projects-surface.md) ·
**Modelo**: [data-model.md](./data-model.md)

Escenarios ejecutables que demuestran la feature de extremo a extremo. No
incluye código de implementación.

## Prerequisites

- Feature 001 instalada (sesión de usuario funcional).
- Dependencias y build al día: `composer install && npm install && npm run build`.
- Tests en SQLite `:memory:` (sin servidor de base de datos); desarrollo con
  la DB del `.env` (PostgreSQL según constitución).

```bash
php artisan migrate        # aplica create_projects_table + project_user
npm run build              # regenera Wayfinder con las rutas nuevas
```

## Escenarios de validación

S1–S4 son manuales por navegador; S5–S9 son automáticos (Pest). Los escenarios
automáticos cubren las mismas reglas que los manuales.

### S1 — Alta y listado (US1)

1. Entrar como usuario autenticado, abrir `/projects` → lista vacía con botón
   de alta.
2. Crear proyecto con título y descripción → redirect a la ficha; aparece en
   `/projects` con estado "activo" y avance 0%.
3. Crear sin título → error "el título es obligatorio", no se crea nada.

### S2 — Edición y ciclo de vida (US2)

1. En la ficha, editar título/descripción → cambios visibles en ficha y lista.
2. Archivar → estado "archivado", ficha en solo lectura; reactivar → vuelve a
   "activo" y se puede editar.
3. Completar → estado "completado"; reactivar funciona; archivar → completado
   directo NO aparece como opción (transición no permitida, error claro si se
   fuerza la petición).
4. Eliminar con confirmación → desaparece del índice y del listado de sus
   colaboradores.

### S3 — Métricas de avance (US3)

1. Proyecto sin tareas muestra 0% en índice y ficha (idénticos).
2. El avance nunca supera 100% ni muestra error con proyectos vacíos.

### S4 — Colaboradores (US4)

1. Propietario añade colaborador por correo registrado → aparece en la ficha y
   el proyecto aparece en el índice del colaborador.
2. Añadir el mismo correo de nuevo → error, sin duplicado. Añadir correo no
   registrado → error "usuario no registrado".
3. Retirar colaborador → desaparece de su índice. Intentar retirar al
   propietario → no permitido.
4. Un tercer usuario (no miembro) abre la URL de la ficha → 404.

### S5 — CRUD automatizado

```bash
php artisan test --compact --filter=ProjectCrudTest
```

Esperado: alta válida (creador propietario, redirect a ficha), validaciones de
título, edición, eliminación con disolución de membresías.

### S6 — Ciclo de vida automatizado

```bash
php artisan test --compact --filter=ProjectLifecycleTest
```

Esperado: transiciones permitidas (active↔archived, active↔completed),
rechazo de transiciones ilegales con error.

### S7 — Colaboradores automatizado

```bash
php artisan test --compact --filter=ProjectMembersTest
```

Esperado: alta/retiro, rechazo de duplicados y correos inexistentes,
propietario no se puede retirar, índice del colaborador refleja membresía.

### S8 — Control de acceso automatizado

```bash
php artisan test --compact --filter=ProjectAccessTest
```

Esperado: no miembros reciben 404 en ficha/edición/eliminación y la petición
de membresía; el índice nunca lista proyectos ajenos.

### S9 — Métricas automatizadas

```bash
php artisan test --compact --filter=ProjectProgressTest
```

Esperado: avance 0% para proyectos sin tareas; el método expone la fórmula
acotada (documentada para el módulo de tareas).

### S10 — Calidad de contrato

```bash
composer ci:check
npm run types:check
```

Esperado: todos los gates pasan.

## Criterio de "feature funcionando"

S1–S4 verificados manualmente + S5–S9 en verde + S10 sin violaciones. Cubre
los escenarios de aceptación US1–US4 y habilita el paso a
`/skill:speckit-tasks`.
