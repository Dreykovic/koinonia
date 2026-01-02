@extends('layouts.app')

@section('title', 'Nouvelle Edition')
@section('page-title', 'Nouvelle Edition du Bulletin')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-4">
                <h3 class="card-title fw-bold mb-0">Creer une nouvelle edition</h3>
                <span class="text-muted fs-7">Ce sera l'edition N<strong>{{ $nextNumber }}</strong> de l'annee {{ now()->year }}</span>
            </div>
            <div class="card-body">
                <form action="{{ route('bulletin.store') }}" method="POST">
                    @csrf

                    <div class="row mb-6">
                        <div class="col-md-6">
                            <label class="form-label required">Titre du bulletin</label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', 'ATTOUNGBLAN') }}" placeholder="Ex: ATTOUNGBLAN" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="text-muted fs-7 mt-1">Le nom du bulletin (apparait en haut)</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sous-titre</label>
                            <input type="text" name="subtitle" class="form-control @error('subtitle') is-invalid @enderror" value="{{ old('subtitle', "Hebdomadaire d'information de la communaute jesuite de l'ITCJ") }}" placeholder="Description courte">
                            @error('subtitle')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-6">
                        <div class="col-md-6">
                            <label class="form-label required">Debut de la semaine</label>
                            <input type="date" name="week_start" id="week_start" class="form-control @error('week_start') is-invalid @enderror" value="{{ old('week_start', $suggestedWeekStart->format('Y-m-d')) }}" required>
                            @error('week_start')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Fin de la semaine</label>
                            <input type="date" name="week_end" id="week_end" class="form-control @error('week_end') is-invalid @enderror" value="{{ old('week_end', $suggestedWeekEnd->format('Y-m-d')) }}" required>
                            @error('week_end')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="text-muted fs-7 mb-4">
                        <i class="ki-duotone ki-information fs-6 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                        Les dates sont calculees automatiquement sur 7 jours lorsque vous modifiez l'une des deux.
                    </div>

                    <div class="alert alert-light-primary d-flex align-items-center mb-6">
                        <i class="ki-duotone ki-information-5 fs-2x text-primary me-3"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                        <div>
                            <strong>Numero d'edition automatique</strong><br>
                            <span class="text-muted">Le numero sera automatiquement attribue : <strong>N{{ $nextNumber }}</strong> pour l'annee {{ now()->year }}</span>
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_published" id="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_published">Publier immediatement</label>
                        </div>
                        <div class="text-muted fs-7 mt-1">Laissez decoche pour enregistrer en brouillon et ajouter le contenu d'abord</div>
                    </div>

                    <div class="separator my-6"></div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('bulletin.index') }}" class="btn btn-light">
                            <i class="ki-duotone ki-arrow-left me-2"><i class="path1"></i><i class="path2"></i></i>
                            Retour
                        </a>
                        <button type="submit" class="btn btn-itcj">
                            <i class="ki-duotone ki-check me-2"><i class="path1"></i><i class="path2"></i></i>
                            Creer et ajouter le contenu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const weekStart = document.getElementById('week_start');
    const weekEnd = document.getElementById('week_end');
    let isManualOverride = false;

    // Helper function to add days to a date
    function addDays(dateStr, days) {
        const date = new Date(dateStr);
        date.setDate(date.getDate() + days);
        return date.toISOString().split('T')[0];
    }

    // Helper function to subtract days from a date
    function subtractDays(dateStr, days) {
        const date = new Date(dateStr);
        date.setDate(date.getDate() - days);
        return date.toISOString().split('T')[0];
    }

    // When start date changes, update end date (+6 days = 7 day period)
    weekStart.addEventListener('change', function() {
        if (this.value && !isManualOverride) {
            weekEnd.value = addDays(this.value, 6);
        }
        isManualOverride = false;
    });

    // When end date changes, update start date (-6 days = 7 day period)
    weekEnd.addEventListener('change', function() {
        if (this.value) {
            // Check if user is manually overriding
            const startDate = new Date(weekStart.value);
            const endDate = new Date(this.value);
            const diffDays = Math.round((endDate - startDate) / (1000 * 60 * 60 * 24));

            if (diffDays !== 6) {
                // User is manually changing, update start date
                weekStart.value = subtractDays(this.value, 6);
                isManualOverride = true;
            }
        }
    });
});
</script>
@endpush







