@component('mail::message')
# Verificación de Seguridad Requerida

Hola,

{!! $messageText !!}

Para autorizar este cambio, por favor ingresa el siguiente código de verificación en la pantalla de tu cuenta.

@component('mail::panel')
<div style="text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px;">
{{ $code }}
</div>
@endcomponent

Este código expirará en 5 minutos. Si tú no solicitaste este cambio, por favor ignora este correo y asegúrate de que tu cuenta esté protegida.

Gracias,<br>
{{ config('app.name') }}
@endcomponent
