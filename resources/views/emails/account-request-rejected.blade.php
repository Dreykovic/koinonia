@component('mail::message')
# Demande d'adhesion non retenue

Bonjour {{ $accountRequest->name }},

Nous avons examine votre demande d'adhesion a la communaute ITCJ.

Malheureusement, nous ne sommes pas en mesure de donner suite a votre demande pour le moment.

@if($accountRequest->admin_notes)
**Motif :**
{{ $accountRequest->admin_notes }}
@endif

Si vous pensez qu'il s'agit d'une erreur ou si vous souhaitez obtenir plus d'informations, n'hesitez pas a contacter l'administration de l'ITCJ.

Cordialement,<br>
{{ config('app.name') }}
@endcomponent

