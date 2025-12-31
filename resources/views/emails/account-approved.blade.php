@component('mail::message')
# Bienvenue sur ITCJ Community !

Bonjour {{ $user->name }},

Votre demande d'adhesion a ete approuvee. Vous pouvez maintenant acceder a la plateforme ITCJ Community.

**Vos identifiants de connexion :**

- **Email :** {{ $user->email }}
- **Mot de passe temporaire :** {{ $temporaryPassword }}

@component('mail::button', ['url' => route('login')])
Se connecter
@endcomponent

**Important :** Nous vous recommandons de changer votre mot de passe des votre premiere connexion.

Si vous n'avez pas demande ce compte, veuillez ignorer cet email.

Cordialement,<br>
{{ config('app.name') }}
@endcomponent



