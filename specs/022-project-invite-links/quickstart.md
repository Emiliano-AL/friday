# Quickstart: validar invitaciones por enlace

## Prerrequisitos

- `composer run dev` (o Herd + `npm run dev`).
- Correo local: `MAIL_MAILER=log` o Mailpit/Herd Mail para ver el enlace enviado.
- Un usuario líder con un proyecto activo (seeders `DatabaseSeeder` / `UiTestingSeeder`).

## Tests automáticos

```bash
php artisan test --compact tests/Feature/Projects/ProjectInvitationsTest.php
php artisan test --compact tests/Feature/Projects/ProjectMembersTest.php   # regresión Q1=B
php artisan test --compact tests/Feature/Auth                               # registro respeta intended
```

Esperado: todo en verde. Los tests usan `Mail::fake()` y factories.

## Escenarios manuales

| # | Pasos | Resultado esperado | Spec |
| --- | --- | --- | --- |
| 1 | Líder → Gestionar colaboradores → agrega `nuevo@demo.test` (sin cuenta) | Aparece en "Invitaciones pendientes"; llega correo con enlace | US1-1, FR-001/002 |
| 2 | Ventana privada → abrir enlace | Ve proyecto + invitador; botones crear cuenta / iniciar sesión | US1-2, FR-004 |
| 3 | Crear cuenta desde el enlace | Correo precargado; al terminar vuelve a la invitación; "Aceptar" → entra al proyecto | US1-3, FR-005/006/016 |
| 4 | Líder agrega correo de usuario registrado | Entra al instante, sin correo | US2-4, FR-012 |
| 5 | Invitar `otro@demo.test`, registrar esa cuenta por la vía normal, abrir enlace → "Rechazar" | No es miembro; desaparece de pendientes | US2-3, FR-007 |
| 6 | Abrir un enlace con sesión de otro correo | Mensaje "esta invitación es para …" + cerrar sesión; no puede aceptar | FR-015 |
| 7 | Reenviar y luego cancelar una pendiente | Nuevo correo; tras cancelar, el enlace muestra "no disponible" | US3, FR-009 |
| 8 | Invitar dos veces el mismo correo | Una sola pendiente; se reenvía | FR-011 |
| 9 | Archivar el proyecto y abrir un enlace pendiente | "Proyecto no activo"; no se puede aceptar | Edge case |
| 10 | Abrir modal | "Enlace de invitación rápida" y roles siguen "Próximamente" | FR-013 |
