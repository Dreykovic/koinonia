@component('mail::message')
# Nouvelle demande de {{ $communityRequest->isRepair() ? 'reparation' : 'achat' }}

Une nouvelle demande a ete soumise sur la plateforme ITCJ Community.

**Informations de la demande :**

- **Type :** {{ $communityRequest->type_label }}
- **Demandeur :** {{ $communityRequest->email }}
- **Communaute :** {{ $communityRequest->community?->name ?? 'Non specifiee' }}
- **Date :** {{ $communityRequest->created_at->format('d/m/Y a H:i') }}

@if($communityRequest->isRepair())
**Details de la reparation :**

- **Lieu :** {{ $communityRequest->location_display }}
@if($communityRequest->room_number)
- **Numero de chambre :** {{ $communityRequest->room_number }}
@endif
- **Description :** {{ $communityRequest->problem_description }}
@else
**Details de l'achat :**

- **Description :** {{ $communityRequest->purchase_description }}
@endif

@component('mail::button', ['url' => route('community-requests.show', $communityRequest)])
Voir la demande
@endcomponent

Vous pouvez traiter cette demande depuis l'interface d'administration.

Cordialement,<br>
{{ config('app.name') }}
@endcomponent

