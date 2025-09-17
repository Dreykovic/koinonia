@extends('layouts.app')

@section('title', 'Demande d\'achat')
@section('page-title', 'Demande d\'Achat')

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('community-requests.form.step1') }}" class="text-muted text-hover-primary">Nouvelle demande</a>
</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Achat</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title fw-bold">
                    <i class="ki-duotone ki-purchase fs-3 text-success me-2"><i class="path1"></i><i class="path2"></i></i>
                    Details de l'Achat
                </h3>
                <div class="card-toolbar">
                    <span class="badge badge-light-success fs-7">Etape 2 sur 2</span>
                </div>
            </div>
            <div class="card-body">
                <!-- Progress bar -->
                <div class="mb-10">
                    <div class="progress h-6px">
                        <div class="progress-bar bg-success" style="width: 100%"></div>
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

                <form action="{{ route('community-requests.form.step2.purchase.post') }}" method="POST">
                    @csrf

                    <!-- Description de l'achat -->
                    <div class="fv-row mb-10">
                        <label class="form-label fw-bold text-dark fs-6 required">Decrire la nature de l'achat</label>
                        <textarea name="purchase_description" rows="8" maxlength="2000" id="purchase_description"
                            class="form-control @error('purchase_description') is-invalid @enderror"
                            placeholder="Decrivez en detail ce que vous souhaitez acheter, la quantite, et toute autre information utile..." required>{{ old('purchase_description') }}</textarea>
                        @error('purchase_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="d-flex justify-content-between mt-2">
                            <div class="form-text text-muted">
                                Soyez le plus precis possible : type de produit, quantite, specifications techniques si necessaire, etc.
                            </div>
                            <div class="form-text text-end"><span id="purchase_description_count">{{ strlen(old('purchase_description', '')) }}</span> / 2000</div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('community-requests.form.step1') }}" class="btn btn-light">
                            <i class="ki-duotone ki-arrow-left fs-3 me-2"><i class="path1"></i><i class="path2"></i></i>
                            Retour
                        </a>
                        <button type="submit" class="btn btn-itcj" id="submitBtn">
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
    // Character counter
    $('#purchase_description').on('input', function() {
        $('#purchase_description_count').text(this.value.length);
    });

    // Loading state on submit
    $('form').on('submit', function() {
        var btn = $('#submitBtn');
        btn.prop('disabled', true);
        btn.html('<span class="spinner-border spinner-border-sm me-2"></span> Envoi en cours...');
    });
});
</script>
@endpush
