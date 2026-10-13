# Implementation Plan: Invitaciones a proyectos por enlace

**Branch**: `022-project-invite-links` | **Date**: 2026-10-10 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/022-project-invite-links/spec.md`

## Summary

Permitir que el líder de un proyecto invite por correo a personas sin cuenta en
Friday. Se añade la entidad `ProjectInvitation` (token hasheado, vigencia de
7 días, estados como enum), un correo de invitación, una página pública
`invitations/{token}` que guía a crear cuenta o iniciar sesión, y acciones de
aceptar/rechazar que exigen que el correo de la sesión coincida. Agregar a un
usuario ya registrado sigue adjuntándolo al instante (Q1=B). El modal de
colaboradores lista las pendientes con reenvío y cancelación; el enlace rápido y
los roles siguen "Próximamente" (Q2=A). Detalle de decisiones en
[research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4 (Laravel 13) + Vue 3 `<script setup>` / TypeScript.

**Primary Dependencies**: Inertia v3, Wayfinder, Laravel Mail (SMTP ya
configurado), Breeze + Socialite existentes. Sin dependencias nuevas.

**Storage**: PostgreSQL — nueva tabla `project_invitations` (ver
[data-model.md](data-model.md)); índice único parcial para una pendiente por
proyecto+correo.

**Testing**: Pest feature tests con factories y `Mail::fake()`.

**Target Platform**: web monolítica Inertia (Herd local).

**Project Type**: web-app monolito (Constitución I).

**Performance Goals**: operaciones puntuales; búsqueda de invitación por
`token_hash` indexado.

**Constraints**: token nunca almacenado en claro; envío síncrono para reportar
fallos (D5); límite 20 invitaciones/hora por usuario (D6); español en UI y
correo.

**Scale/Scope**: 1 tabla + 1 enum + 1 modelo/factory, 1 Mailable, 2
controladores (gestión del líder y respuesta del invitado), 1 página Inertia
nueva, cambios en `ProjectMemberController`, `RegisteredUserController`,
`ProjectController` (payload), `MembersModal.vue`, `Auth/Register.vue`.

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

| Principio | Evaluación |
| --- | --- |
| I. Monolith-First con Inertia | PASS — Página `Invitations/Show` vía `Inertia::render`; acciones por formularios Inertia; sin API. |
| II. Ecosistema Laravel | PASS — `make:model -mf`, `make:mail --markdown`, `make:enum`, form requests, policies, RateLimiter. Sin dependencias nuevas. |
| III. Test-First con Pest | PASS — `ProjectInvitationsTest` (crear, reenviar, cancelar, aceptar, rechazar, coincidencia de correo, vencida, proyecto inactivo, duplicados, límite) escrito antes de implementar; regresión de `ProjectMembersTest` y registro. |
| IV. Tipado y Enums | PASS — `ProjectInvitationStatus` enum; vencimiento derivado de `expires_at`, no string suelto. |
| V. Simplicidad / YAGNI | PASS con nota — Introduce correo saliente (el MVP no lo tenía), justificado: es el núcleo pedido de la feature. Sin scheduler de vencimiento, sin cola, sin roles, sin enlace reutilizable. |
| Stack / Autenticación | PASS — Reutiliza Breeze + Google; solo se cambia el destino post-registro a `intended` (sin nuevos flujos de auth). |

**Post-design re-check (tras Phase 1)**: el diseño no añade capas; la lógica de
emisión vive en el modelo (`ProjectInvitation::issue`) y dos controladores
delgados. PASS.

## Project Structure

### Documentation (this feature)

```text
specs/022-project-invite-links/
├── plan.md              # This file
├── research.md          # Phase 0
├── data-model.md        # Phase 1
├── contracts/routes.md  # Phase 1 (rutas, props, mensajes)
├── quickstart.md        # Phase 1
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/
├── Enums/ProjectInvitationStatus.php                 # NUEVO
├── Models/ProjectInvitation.php                      # NUEVO (issue, isExpired, isUsable, matches)
├── Models/Project.php                                # + invitations(), pendingInvitations()
├── Mail/ProjectInvitationMail.php                    # NUEVO (markdown)
├── Http/Controllers/ProjectMemberController.php      # store: registrado → adjunta; no registrado → invita
├── Http/Controllers/ProjectInvitationController.php  # NUEVO: resend, destroy (líder)
├── Http/Controllers/InvitationResponseController.php # NUEVO: show (público), accept, decline
├── Http/Controllers/ProjectController.php            # payload: pendingInvitations (propietario)
├── Http/Controllers/Auth/RegisteredUserController.php# create: precarga email; store: redirect()->intended
└── Providers/AppServiceProvider.php                  # RateLimiter 'project-invitations'
database/
├── migrations/xxxx_create_project_invitations_table.php
└── factories/ProjectInvitationFactory.php
resources/
├── views/mail/project-invitation.blade.php           # markdown mail
├── js/pages/Invitations/Show.vue                     # NUEVO
├── js/pages/Auth/Register.vue                        # prop email precargado
├── js/Components/Projects/MembersModal.vue           # sección "Invitaciones pendientes"
└── js/types/project.ts                               # PendingInvitation
routes/web.php                                        # rutas de contracts/routes.md
tests/Feature/Projects/ProjectInvitationsTest.php     # NUEVO
```

**Structure Decision**: monolito Laravel existente; todo vive en las carpetas
actuales. La página de invitación es pública (fuera del grupo `auth`) porque el
invitado aún no tiene cuenta.

## Complexity Tracking

> Sin violaciones. Nota bajo Principio V: el correo saliente es la primera
> notificación por email del producto; se limita a este caso (envío síncrono,
> una plantilla) y no introduce infraestructura de notificaciones general.
