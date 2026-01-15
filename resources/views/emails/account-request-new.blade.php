@component('mail::message')
# Nouvelle demande d'adhesion

Une nouvelle demande d'adhesion a la communaute a ete soumise sur la plateforme ITCJ Community.

**Informations du demandeur :**

- **Nom :** {{ $accountRequest->name }}
- **Email :** {{ $accountRequest->email }}
- **Telephone :** {{ $accountRequest->phone ?? 'Non renseigne' }}
- **Communaute :** {{ $accountRequest->community?->name ?? 'Non specifiee' }}
- **Date de la demande :** {{ $accountRequest->created_at->format('d/m/Y a H:i') }}

@component('mail::button', ['url' => route('account-requests.show', $accountRequest)])
Voir la demande
@endcomponent

Vous pouvez approuver ou rejeter cette demande depuis l'interface d'administration.

Cordialement,<br>
{{ config('app.name') }}
@endcomponent


