{{-- Avertissement affiché quand une partie de la page n'a pas pu être chargée (erreur journalisée côté serveur). --}}
@props(['message' => null])

@if($message)
    <div class="alert alert-warning alert-dismissible fade show mb-5" role="alert" aria-live="polite" data-degraded-notice>
        <i class="ki-duotone ki-information-5 fs-2 me-2" aria-hidden="true"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
        <span>{{ $message }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer cette notification"></button>
    </div>
@endif
