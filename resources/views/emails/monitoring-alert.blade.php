@component('mail::message')
# Alerte : {{ $rule }}

{{ $alertMessage }}

- **Environnement :** {{ $environment }}
- **Application :** {{ $appUrl }}
- **Date :** {{ $occurredAt }}

@if (!empty($details))
**Contexte :**

@component('mail::table')
| Clé | Valeur |
|:----|:-------|
@foreach ($details as $key => $value)
| {{ $key }} | {{ str_replace(['|', "\n"], ['\|', ' '], \Illuminate\Support\Str::limit($value, 300)) }} |
@endforeach
@endcomponent
@endif

Les détails complets sont dans `storage/logs/app-*.log` (rechercher `alert.{{ $rule }}` ou le request_id).
Les e-mails de cette règle sont suspendus pendant {{ config('monitoring.alerts.throttle_minutes') }} minutes.

{{ config('app.name') }}
@endcomponent
