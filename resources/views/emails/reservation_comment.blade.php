@component('mail::message')

{{-- Encabezado formal --}}
# Nuevo Comentario sobre su Reservación

Estimado/a usuario/a,

Hemos recibido un nuevo comentario relacionado con su reservación en el sistema de **Agenda de Escenarios Educativos**. A continuación, le compartimos los detalles:

{{-- Cita del comentario --}}
@component('mail::panel')
{{ $comment }}
@endcomponent

{{-- Mensaje de cierre --}}
Agradecemos su atención y quedamos a su disposición para cualquier consulta o aclaración adicional. Puede responder directamente a este correo para ponerse en contacto con nosotros.

{{-- Firma formal --}}
Atentamente,  
**Equipo de Agenda de Escenarios Educativos**  
Universidad de Colima  
Correo: {{ config('mail.from.address') }}  
Fecha: {{ now()->format('d/m/Y') }}

@endcomponent