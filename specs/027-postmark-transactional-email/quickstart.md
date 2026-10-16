# Quickstart: Validar Postmark

## Prerrequisitos (fuera de la app)

1. Crear un **Server** en Postmark y copiar su **Server API Token**.
2. Verificar el **dominio** (DKIM + Return-Path) o, como mínimo, un **Sender Signature** para la dirección remitente.
3. Si la cuenta está pendiente de aprobación, solo puede enviar a direcciones del mismo dominio.

## 1. Tests (sin credenciales ni red)

```bash
php artisan test --compact tests/Feature/Mail tests/Feature/Auth tests/Feature/Branding tests/Feature/Projects/ProjectInvitationsTest.php
php artisan test --compact     # suite completa (SC-004)
```

## 2. Dependencias y configuración limpias (SC-005)

```bash
composer show | grep -iE "mail|resend|sendgrid|mailgun|brevo|aws"   # solo symfony/mailer y symfony/postmark-mailer
grep -nE "'(ses|resend|roundrobin|failover|sendmail)'" config/mail.php config/services.php   # sin resultados
```

## 3. Prueba sin entrega (token de prueba)

En `.env`, configurar:

```dotenv
MAIL_MAILER=postmark
POSTMARK_API_KEY=POSTMARK_API_TEST
MAIL_FROM_ADDRESS="no-reply@<dominio-verificado>"
```

```bash
php artisan config:clear
php artisan tinker --execute="Mail::raw('ping', fn (\$m) => \$m->to('test@blackhole.postmarkapp.com')->subject('ping'));"
```

Resultado esperado: sin excepción. Con el token de prueba, Postmark acepta el envío y no lo entrega.

## 4. Prueba real (staging o producción)

1. Poner el Server API Token real en `POSTMARK_API_KEY` y ejecutar `php artisan config:clear`.
2. Invitar un buzón real a un proyecto → llega en menos de 1 minuto con remitente "Eunox <…>". En Postmark → Activity, el mensaje aparece con la etiqueta `project-invitation` (SC-001, SC-002).
3. En `/forgot-password`, pedir el enlace para una cuenta real → llega con la etiqueta `password-reset`. El enlace permite cambiar la contraseña.

## 5. Fallo controlado (SC-003)

1. Poner un token inválido (`POSTMARK_API_KEY=invalid`) y ejecutar `php artisan config:clear`.
2. Invitar → aparece el mensaje "No pudimos enviar el correo. Puedes reenviar la invitación." y la invitación queda pendiente.
3. Pedir restablecimiento → aparece el error bajo el campo email "No pudimos enviar el correo. Intenta de nuevo en unos minutos." No hay página de error.
4. `php artisan pail` (o `storage/logs`) muestra la excepción de Postmark registrada.
5. Restaurar el token.

## 6. Desarrollo local

`MAIL_MAILER=log` (default) o `MAIL_MAILER=smtp` con el buzón de Herd (Mailpit). Ninguno de los dos sale a internet.
