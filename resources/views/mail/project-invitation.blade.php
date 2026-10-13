<x-mail::message>
# Te invitaron a colaborar

**{{ $inviterName }}** te invitó a colaborar en el proyecto **{{ $projectTitle }}** en Friday.

<x-mail::button :url="$url">
Ver invitación
</x-mail::button>

Si aún no tienes cuenta, podrás crearla desde el enlace con este mismo correo. La invitación vence en {{ $validDays }} días.

Si no esperabas esta invitación, puedes ignorar este correo.

{{ config('app.name') }}
</x-mail::message>
