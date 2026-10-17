# Quickstart: Validar el rebranding a Eunox

## Prerrequisitos

- `.env` con `APP_NAME=Eunox` (y `MAIL_FROM_NAME="${APP_NAME}"`, `VITE_APP_NAME="${APP_NAME}"`).
- `php artisan config:clear` y `npm run build` (o `npm run dev`). El título de pestaña depende del build de Vite.

## 1. Tests automatizados

```bash
php artisan test --compact tests/Feature/Branding
php artisan test --compact   # suite completa, SC-006
```

Resultado esperado: todo en verde. Ver el contrato en [contracts/branding.md](./contracts/branding.md).

## 2. Guardia rápida por grep

```bash
grep -rnI "\bFriday\b" resources/js resources/views app/Mail   # sin resultados
grep -rnI "Laravel" resources/js/pages resources/js/Components   # sin resultados visibles
```

## 3. Recorrido manual (SC-001, SC-002)

1. Sin sesión, abrir `/login`, `/register` y `/forgot-password` → el encabezado dice "Eunox Cloud OS", el título dice "Iniciar sesión en Eunox" y la pestaña termina en "Eunox".
2. Iniciar sesión → la barra lateral dice "Eunox" y el pie del inicio dice "Eunox v2.4". Navegar a proyectos, tareas y ajustes: todas las pestañas terminan en "- Eunox".
3. En un proyecto, abrir Miembros → el texto de ayuda menciona "Eunox".
4. Invitar a un correo nuevo, abrir el correo en el mailer local (Herd/Mailpit) → remitente "Eunox", asunto "… en Eunox" y cuerpo "… en Eunox". Abrir el enlace → botón "Ir a Eunox".
5. Solicitar restablecer contraseña → el correo muestra "Eunox" en remitente, encabezado y pie.

## 4. Preservación del codename (SC-004)

1. Antes de aplicar el cambio, colapsar la columna "Hecho" del Kanban.
2. Aplicar el cambio y recargar → la columna sigue colapsada (la clave `friday:kanban-done-collapsed` no cambió).
3. Crear una tarea titulada "Revisar Friday" → el título se muestra intacto.

## Pendiente conocido

Los favicons siguen siendo el logo de Laravel hasta que exista arte de Eunox (ver research R7).
