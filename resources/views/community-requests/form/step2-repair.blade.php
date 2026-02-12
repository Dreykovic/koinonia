@extends('layouts.app')

@section('title', 'Demande de reparation')
@section('page-title', 'Demande de Reparation')

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('community-requests.form.step1') }}" class="text-muted text-hover-primary">Nouvelle demande</a>
</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Reparation</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title fw-bold">
                    <i class="ki-duotone ki-wrench fs-3 text-warning me-2"><i class="path1"></i><i class="path2"></i></i>
                    Details de la Reparation
                </h3>
                <div class="card-toolbar">
                    <span class="badge badge-light-warning fs-7">Etape 2 sur 2</span>
                </div>
            </div>
            <div class="card-body">
                <!-- Progress bar -->
                <div class="mb-10">
                    <div class="progress h-6px">
                        <div class="progress-bar bg-warning" style="width: 100%"></div>
                    </div>
                </div>

                @if($errors->any())
                <div class="alert alert-danger mb-5">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('community-requests.form.step2.repair.post') }}" method="POST">
                    @csrf

                    <!-- Lieu de reparation -->
                    <div class="fv-row mb-8">
                        <label class="form-label fw-bold text-dark fs-6 required">Lieu de la reparation</label>
                        <div class="row mt-3">
                            @foreach($locations as $location)
                            <div class="col-md-4 col-6 mb-3">
                                <label class="form-check form-check-custom form-check-solid form-check-sm p-4 rounded border border-gray-300 cursor-pointer">
                                    <input type="radio" name="repair_location_id" value="{{ $location->id }}"
                                        class="form-check-input location-radio"
                                        {{ old('repair_location_id') == $location->id ? 'checked' : '' }}>
                                    <span class="form-check-label fw-semibold text-gray-700 ms-3">
                                        {{ $location->name }}
                                    </span>
                                </label>
                            </div>
                            @endforeach
                            <div class="col-md-4 col-6 mb-3">
                                <label class="form-check form-check-custom form-check-solid form-check-sm p-4 rounded border border-gray-300 cursor-pointer">
                                    <input type="radio" name="repair_location_id" value=""
                                        class="form-check-input location-radio" id="location_other"
                                        {{ old('repair_location_id') === '' ? 'checked' : '' }}>
                                    <span class="form-check-label fw-semibold text-gray-700 ms-3">
                                        Autre
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Autre lieu (conditionnel) -->
                    <div class="fv-row mb-8" id="other_location_container" style="{{ old('repair_location_id') === '' ? '' : 'display:none' }}">
                        <label class="form-label fw-bold text-dark fs-6">Preciser le lieu</label>
                        <input type="text" name="other_location" class="form-control @error('other_location') is-invalid @enderror"
                            value="{{ old('other_location') }}" placeholder="Decrivez le lieu...">
                        @error('other_location')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Numero de chambre -->
                    <div class="fv-row mb-8">
                        <label class="form-label fw-bold text-dark fs-6">Numero de Chambre</label>
                        <input type="text" name="room_number" class="form-control @error('room_number') is-invalid @enderror"
                            value="{{ old('room_number') }}" placeholder="Ex: 12, A3...">
                        @error('room_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted">Optionnel - si la reparation concerne une chambre specifique</div>
                    </div>

                    <!-- Description du probleme -->
                    <div class="fv-row mb-10">
                        <label class="form-label fw-bold text-dark fs-6 required">Decrire le probleme</label>
                        <textarea name="problem_description" rows="5"
                            class="form-control @error('problem_description') is-invalid @enderror"
                            placeholder="Decrivez le probleme en detail..." required>{{ old('problem_description') }}</textarea>
                        @error('problem_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Submit -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('community-requests.form.step1') }}" class="btn btn-light">
                            <i class="ki-duotone ki-arrow-left fs-3 me-2"><i class="path1"></i><i class="path2"></i></i>
                            Retour
                        </a>
                        <button type="submit" class="btn btn-itcj-gold">
                            <i class="ki-duotone ki-check fs-3 me-2"><i class="path1"></i><i class="path2"></i></i>
                            Envoyer la demande
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
$(document).ready(function() {
    $('.location-radio').on('change', function() {
        if ($(this).attr('id') === 'location_other') {
            $('#other_location_container').show();
        } else {
            $('#other_location_container').hide();
            $('input[name="other_location"]').val('');
        }
    });
});
</script>
@endpush
