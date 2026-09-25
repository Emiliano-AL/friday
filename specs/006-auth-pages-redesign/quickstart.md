# Quickstart: Validación del rediseño de páginas de autenticación

**Feature**: `specs/006-auth-pages-redesign`
**Date**: 2026-09-24

Guía para validar el feature end-to-end tras la implementación. No incluye código de implementación.

## Prerequisitos

- Servidor local corriendo (Herd): `https://friday.test` (o `composer run dev`).
- Dependencias instaladas: `composer install` y `npm install` ya hechos.
- Referencia visual a mano: `/Users/elanda/Downloads/stitch_friday_workspace_app (1)/screen.png` y `code.html`.

## 1. Construir assets

```bash
npm run build   # o npm run dev para iterar
```

**Esperado**: build sin errores; los tokens de `DESIGN.md` y la fuente Inter quedan en el CSS.

## 2. Red de regresión de comportamiento (obligatoria)

```bash
php artisan test --compact tests/Feature/Auth
```

**Esperado**: `AuthenticationTest`, `RegistrationTest`, `PasswordResetTest`, `GoogleAuthenticationTest` en verde **sin ninguna modificación** — prueban que los formularios postean a los mismos endpoints con los mismos campos (FR-005).

```bash
vendor/bin/pint --dirty
```

**Esperado**: sin cambios de formato (solo si hubo toques PHP, p. ej. `app.blade.php` no aplica, pero ejecútalo igual).

## 3. Verificación visual por página

Abrir en navegador (y en DevTools con viewport 375px y 1440px), comparando con `screen.png` y el contrato [contracts/auth-pages-ui.md](contracts/auth-pages-ui.md):

| URL                       | Qué validar                                                                                                                                                                   |
| ------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `/login`                  | Tarjeta centrada 440px, halo, botón Google con "Recomendado", divisor, iconos en campos, toggle ojo funciona, casilla remember, flecha en botón, footer con píldora de estado |
| `/register`               | Mismo shell; campos nombre/correo/contraseña/confirmación; toggle ojo; enlace a login                                                                                         |
| `/forgot-password`        | Tarjeta simplificada con explicación y campo correo                                                                                                                           |
| `/reset-password/{token}` | Campos nueva contraseña con toggles (generar token real vía flujo de forgot)                                                                                                  |

**Flujos interactivos mínimos**:

1. Login con credenciales inválidas → errores rojos por campo dentro de la tarjeta, layout intacto.
2. Login válido → redirige al dashboard.
3. "Continuar con Google" → redirige a Google OAuth (mismo comportamiento previo).
4. Registro con email duplicado → error bajo el campo correo.
5. Forgot-password con email válido → banner de estado "te hemos enviado el enlace" dentro de la tarjeta.

## 4. Responsive y accesibilidad

- DevTools: 320px, 375px, 768px, 1440px → sin scroll horizontal en ninguna (SC-004).
- Lighthouse (vía Boost MCP o DevTools) en `/login`: sin violaciones críticas de accesibilidad (contraste, labels, foco visible) (SC-005).
- Navegación completa con Tab: anillo de foco acento visible en cada interactivo (FR-007).

## 5. Verificación de aislamiento de alcance

- Revisar `/projects` (login requerido): las páginas de Projects deben verse **idénticas** a antes (sin tokens ni estilos nuevos filtrados) — FR-009.

## Criterio de éxito

Todos los pasos en verde + checklist de `contracts/auth-pages-ui.md` completo en desktop y móvil.
