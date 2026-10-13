# Quickstart: validar Configuración

## Prerrequisitos

- `composer run dev` (o `npm run dev`) y la app en Herd.
- Una cuenta con contraseña y, opcionalmente, una de Google sin contraseña.

## Tests

```bash
php artisan test --compact tests/Feature/Settings/SettingsTest.php
npm run types:check
```

## Escenarios manuales

1. **Navegación** — Pulsar «Ajustes» en la barra lateral: abre `/settings` y la entrada queda activa. En el menú de perfil, «Detalles del perfil» y «Ajustes de cuenta» abren la misma página; ya no aparece «Próximamente».
2. **Nombre** — Cambiar el nombre y guardar: confirmación y el menú de perfil muestra el nuevo nombre sin recargar. Nombre vacío: error junto al campo.
3. **Correo** — Se ve como solo lectura; no hay forma de editarlo.
4. **Contraseña** — Contraseña actual incorrecta: error. Correcta + nueva confirmada: confirmación; cerrar sesión y entrar con la nueva.
5. **Otras sesiones** — Con la cuenta abierta en otro navegador, cambiar la contraseña: el otro navegador vuelve al login en su siguiente petición.
6. **Cuenta de Google** — Sin contraseña: la sección muestra «Definir contraseña» sin pedir la actual.
7. **Eliminar (bloqueado)** — Siendo dueña de proyectos: la zona de peligro lista los proyectos con enlace y el botón está deshabilitado.
8. **Eliminar (permitido)** — Con una cuenta de prueba sin proyectos propios: confirmar con la contraseña → vuelve al login y ya no se puede entrar.
9. **Móvil** — A 390 px las secciones se apilan sin scroll horizontal.
