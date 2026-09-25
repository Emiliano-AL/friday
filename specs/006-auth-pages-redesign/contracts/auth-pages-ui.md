# Contrato UI: Páginas de autenticación

**Feature**: `specs/006-auth-pages-redesign`
**Date**: 2026-09-24

Contrato visual/estructural por página. Sirve como checklist de aceptación visual (complementa, no reemplaza, los tests de comportamiento de Pest).

## Shell compartido (GuestLayout)

- [ ] Fondo superficie `#F9F9FF` (token `surface`), texto `on-surface` `#141B2B`, fuente Inter.
- [ ] Header: punto de acento pulsante + marca "Friday" (label-xs, mayúsculas); a la derecha icono candado + "Conexión segura SSL". En móvil el header se simplifica sin romper el ancho.
- [ ] Contenido centrado vertical y horizontalmente; halo difuso: círculo ~500px `primary-fixed/30` con blur detrás de la tarjeta.
- [ ] Footer: enlaces Privacidad • Términos • Soporte; indicador "Idioma: Español (ES)"; píldora "Todos los sistemas operativos" con punto verde pulsante sobre el bloque principal.

## Página: Login (`/login`)

- [ ] Tarjeta blanca (`surface-container-lowest`), ancho máx. ~~440px, radio elevado (~~`rounded-xl`/0.75rem), sombra suave tinte primario.
- [ ] Logo marca (48px, tinte primario 10%) + título "Iniciar sesión en Friday" (headline-lg) + subtítulo "Tu espacio de trabajo ágil y productivo".
- [ ] Botón "Continuar con Google": alto 44px, borde sutil, logo Google, distintivo flotante "Recomendado" (píldora con punto de acento).
- [ ] Divisor: líneas hairline + texto "o continuar con correo" (label-xs).
- [ ] Campo correo: icono mail, placeholder `nombre@empresa.com`, type email, requerido.
- [ ] Campo contraseña: icono lock, placeholder puntos, botón ojo para mostrar/ocultar; enlace "¿Olvidaste tu contraseña?" alineado a la derecha del label.
- [ ] Casilla "Recordar este dispositivo" (estilo design system: 16px, acento al activarse).
- [ ] Botón primario "Iniciar sesión": fondo acento `#5B5BD6`, texto blanco, icono flecha con micro-animación, atajo `↵` visible en sm+.
- [ ] Bajo el formulario: "¿No tienes cuenta de equipo? Regístrate gratis" (enlace acento).
- [ ] Errores de validación por campo bajo el campo, color `error` `#BA1A1A`; banner `status` (p. ej. sesión expirada) visible en tarjeta.
- [ ] Foco visible: anillo doble blanco + acento 35% (token focus de DESIGN.md).

## Página: Registro (`/register`)

- [ ] Misma tarjeta/logo; título de creación de cuenta + subtítulo.
- [ ] Botón Google con distintivo "Recomendado" + divisor "o continuar con correo".
- [ ] Campos: Nombre completo (icono persona), Correo (icono mail), Contraseña (icono lock + toggle ojo), Confirmar contraseña.
- [ ] Botón primario "Crear cuenta"; enlace "¿Ya tienes cuenta? Inicia sesión".
- [ ] Errores por campo (email duplicado, confirmación distinta) bajo cada campo.

## Página: Recuperar contraseña (`/forgot-password`)

- [ ] Tarjeta simplificada: título "Recuperar contraseña", mensaje explicativo de envío de enlace.
- [ ] Campo correo con icono mail + botón primario "Enviar enlace de recuperación".
- [ ] Banner de estado (enlace enviado) con color de éxito/acento dentro de la tarjeta.
- [ ] Enlace "Volver a iniciar sesión".

## Página: Restablecer contraseña (`/reset-password/{token}`)

- [ ] Tarjeta: título "Nueva contraseña" + mensaje breve.
- [ ] Campos: Nueva contraseña (icono lock + toggle ojo), Confirmar contraseña (icono lock + toggle ojo); email y token en inputs ocultos (props existentes).
- [ ] Botón primario "Restablecer contraseña"; enlace de retorno al login.
- [ ] Errores por campo (p. ej. token inválido/expirado, confirmación distinta) bajo cada campo.

## Responsive (todas)

- [ ] 320px–767px: tarjeta a ancho fluido con márgenes `16px`, tipografía de titular escalada (headline-xl-mobile), header/footer compactos, sin scroll horizontal.
- [ ] ≥768px: tarjeta max 440px centrada, header/footer a ancho completo con `px-margin`.
