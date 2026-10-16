# Contract: Envío de correo transaccional

## 1. Mailer `postmark` (config/mail.php)

```php
'postmark' => [
    'transport' => 'postmark',
    'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID', 'outbound'),
    'client' => ['timeout' => 5],
],
```

`Mail::mailer('postmark')->getSymfonyTransport()` **debe** ser una instancia de `Symfony\Component\Mailer\Bridge\Postmark\Transport\PostmarkApiTransport`.

Mailers permitidos: `smtp`, `postmark`, `log`, `array`. Servicios de correo permitidos en `config/services.php`: solo `postmark`.

## 2. Mensajes

| Mensaje | Asunto | Etiqueta | From |
| --- | --- | --- | --- |
| ProjectInvitationMail | `"<Invitador> te invitó a <Proyecto> en Eunox"` (026) | `project-invitation` | `MAIL_FROM_NAME <MAIL_FROM_ADDRESS>` |
| ResetPasswordNotification | Estándar de Laravel | `password-reset` | ídem |

## 3. Comportamiento ante fallo del proveedor

| Flujo | Entrada | Respuesta HTTP | UI | Log |
| --- | --- | --- | --- | --- |
| Invitar `POST /projects/{id}/members` | Postmark lanza `TransportException` | 302 → proyecto | flash `error`: "No pudimos enviar el correo. Puedes reenviar la invitación." | `report()` |
| Restablecer `POST /forgot-password` | Postmark lanza `TransportExceptionInterface` | 302 → back | `errors.email`: "No pudimos enviar el correo. Intenta de nuevo en unos minutos." | `report()` |

Ninguno de los dos flujos devuelve 500.
