@component('mail::message')
# Mise a jour de votre demande

Bonjour,

Le statut de votre demande de **{{ $communityRequest->type_label }}** a ete mis a jour.

@component('mail::panel')
**Nouveau statut :** {{ $communityRequest->status_label }}
@endcomponent

**Rappel de votre demande :**

- **Type :** {{ $communityRequest->type_label }}
- **Date de soumission :** {{ $communityRequest->created_at->format('d/m/Y') }}
@if($communityRequest->isRepair())
- **Lieu :** {{ $communityRequest->location_display }}
- **Description :** {{ $communityRequest->problem_description }}
@else
- **Description :** {{ $communityRequest->purchase_description }}
@endif

@if($communityRequest->admin_notes)
**Note de l'administration :**
{{ $communityRequest->admin_notes }}
@endif

@if($communityRequest->assignedUser)
**Personne assignee :** {{ $communityRequest->assignedUser->name }}
@endif

@if($communityRequest->status === 'completed' && $communityRequest->completed_at)
**Date de completion :** {{ $communityRequest->completed_at->format('d/m/Y a H:i') }}
@endif

Merci de votre confiance.

Cordialement,<br>
{{ config('app.name') }}
@endcomponent
