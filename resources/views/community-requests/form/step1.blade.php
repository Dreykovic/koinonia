@extends('layouts.app')

@section('title', 'Nouvelle demande')
@section('page-title', 'Reparations et Achats en Communaute')
@push('styles')
    <style>
.share-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}

.share-modal-content {
    background: #fff;
    width: 350px;
    margin: 10% auto;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.close {
    float: right;
    cursor: pointer;
    font-size: 20px;
}
</style>

@endpush
@section('breadcrumb')
<li class="breadcrumb-item text-muted">Nouvelle demande</li>
@endsection
@section('page-actions')

<button type="button" onclick="openShareModal()" class="btn btn-itcj desktop-add-btn">
     <i class="ki-duotone ki-fasten fs-3 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
     Partager ce formulaire
</button>

@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title fw-bold">
                    <i class="ki-duotone ki-questionnaire-tablet fs-3 text-primary me-2"><i class="path1"></i><i class="path2"></i></i>
                    Nouvelle Demande
                </h3>
                <div class="card-toolbar">
                    <span class="badge badge-light-primary fs-7">Etape 1 sur 2</span>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-10">
                    <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-6">
                        <i class="ki-duotone ki-information fs-2tx text-info me-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                        <div class="d-flex flex-stack flex-grow-1">
                            <div class="fw-semibold">
                                <div class="fs-6 text-gray-700">
                                    Remplir ce formulaire quand il y a une reparation a faire en communaute
                                    ou un achat qui ne releve pas du budget des petites communautes.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="mb-10">
                    <div class="progress h-6px">
                        <div class="progress-bar bg-primary" style="width: 50%"></div>
                    </div>
                </div>

                @if(session('error'))
                <div class="alert alert-danger mb-5">{{ session('error') }}</div>
                @endif

                <form action="{{ route('community-requests.form.step1.post') }}" method="POST">
                    @csrf

                    <!-- Info utilisateur -->
                    <div class="fv-row mb-8">
                        <label class="form-label fw-bold text-dark fs-6">Demandeur</label>
                        <div class="d-flex align-items-center p-5 rounded" style="background: var(--itcj-blue-light, #f0f4f8);">
                            <div class="symbol symbol-40px me-4">
                                <span class="symbol-label bg-primary text-white fw-bold fs-6">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </span>
                            </div>
                            <div>
                                <span class="fw-bold text-dark d-block">{{ $user->name }}</span>
                                <span class="text-muted fs-7">{{ $user->email }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Communaute -->
                    <div class="fv-row mb-8">
                        <label class="form-label fw-bold text-dark fs-6 required">Nom de la communaute</label>
                        <div class="row mt-3">
                            @foreach($communities as $community)
                            <div class="col-md-4 col-6 mb-3">
                                <label class="form-check form-check-custom form-check-solid form-check-sm p-4 rounded border border-gray-300 cursor-pointer {{ old('community_id', $user->community_id) == $community->id ? 'border-primary bg-light-primary' : '' }}">
                                    <input type="radio" name="community_id" value="{{ $community->id }}"
                                        class="form-check-input"
                                        {{ old('community_id', $user->community_id) == $community->id ? 'checked' : '' }}>
                                    <span class="form-check-label fw-semibold text-gray-700 ms-3">
                                        {{ $community->name }}
                                    </span>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @error('community_id')
                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Type de demande -->
                    <div class="fv-row mb-10">
                        <label class="form-label fw-bold text-dark fs-6 required">Nature de la demande</label>
                        <div class="row mt-3">
                            <div class="col-md-6 mb-3">
                                <label class="d-flex align-items-center justify-content-between p-5 rounded border border-gray-300 cursor-pointer {{ old('request_type') == 'reparation' ? 'border-warning bg-light-warning' : '' }}">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-wrench fs-2x text-warning me-4"><i class="path1"></i><i class="path2"></i></i>
                                        <div>
                                            <span class="fw-bold text-dark d-block">Reparation</span>
                                            <span class="text-muted fs-7">Signaler un probleme a reparer</span>
                                        </div>
                                    </div>
                                    <input type="radio" name="request_type" value="reparation"
                                        class="form-check-input"
                                        {{ old('request_type') == 'reparation' ? 'checked' : '' }}>
                                </label>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="d-flex align-items-center justify-content-between p-5 rounded border border-gray-300 cursor-pointer {{ old('request_type') == 'achat' ? 'border-success bg-light-success' : '' }}">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-purchase fs-2x text-success me-4"><i class="path1"></i><i class="path2"></i></i>
                                        <div>
                                            <span class="fw-bold text-dark d-block">Achat</span>
                                            <span class="text-muted fs-7">Demander un achat</span>
                                        </div>
                                    </div>
                                    <input type="radio" name="request_type" value="achat"
                                        class="form-check-input"
                                        {{ old('request_type') == 'achat' ? 'checked' : '' }}>
                                </label>
                            </div>
                        </div>
                        @error('request_type')
                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Submit -->
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-itcj" id="submitBtn">
                            Suivant
                            <i class="ki-duotone ki-arrow-right fs-3 ms-2"><i class="path1"></i><i class="path2"></i></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Share Modal -->
<div id="shareModal" class="share-modal" role="dialog" aria-labelledby="shareModalTitle" aria-modal="true">
    <div class="share-modal-content">
        <span class="close" onclick="closeShareModal()" aria-label="Fermer">&times;</span>

        <h4 id="shareModalTitle">Partager le formulaire</h4>
<div class="d-flex flex-column align-items-center ">
    <!-- QR Code -->
        <div id="qrcode"></div>

        <a id="downloadQR" class="btn btn-sm btn-itcj mt-2" download="formulaire-qrcode.png">
             <i class="ki-duotone ki-arrow-down fs-3 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
            Telecharger (apercu)
        </a>
        <a href="{{ route('community-requests.form.qr-download') }}" class="btn btn-sm btn-itcj-outline mt-2">
             <i class="ki-duotone ki-arrow-down fs-3 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
            Telecharger en haute qualite (SVG)
        </a>
</div>
    

        <!-- Link -->
        <div class="mt-3 d-flex align-items-center ">
            <input type="text" id="shareLink" readonly class="form-control" disabled>
            <button class="btn btn-icon btn-light ms-2" onclick="copyLink()" aria-label="Copier le lien">
                     <i class="ki-duotone ki-copy fs-3 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js" integrity="sha384-3zSEDfvllQohrq0PHL1fOXJuC/jSOO34H46t6UQfobFOmxE5BpjjaIJY5F2/bMnU" crossorigin="anonymous"></script>
<script>
let qr;
const formUrl = "{{ route('community-requests.form.step1') }}";

function openShareModal() {
    document.getElementById('shareModal').style.display = 'block';
    document.getElementById('shareLink').value = formUrl;

    // Nettoyer ancien QR
    document.getElementById('qrcode').innerHTML = "";

    qr = new QRCode(document.getElementById("qrcode"), {
        text: formUrl,
        width: 200,
        height: 200,
    });

    // Attendre que le canvas soit genere
    setTimeout(() => {
        const canvas = document.querySelector("#qrcode canvas");
        if (canvas) {
            document.getElementById('downloadQR').href = canvas.toDataURL("image/png");
        }
    }, 300);
}

function closeShareModal() {
    document.getElementById('shareModal').style.display = 'none';
}

function copyLink() {
    const input = document.getElementById('shareLink');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value);
    alert("Lien copie !");
}
</script>

<script>
$(document).ready(function() {
    // Loading state on submit
    $('form').on('submit', function() {
        var btn = $('#submitBtn');
        btn.prop('disabled', true);
        btn.html('<span class="spinner-border spinner-border-sm me-2"></span> Chargement...');
    });

    // Highlight selected radio on change
    $('input[name="community_id"]').on('change', function() {
        $('input[name="community_id"]').closest('label').removeClass('border-primary bg-light-primary');
        $(this).closest('label').addClass('border-primary bg-light-primary');
    });

    $('input[name="request_type"]').on('change', function() {
        $('input[name="request_type"]').closest('label').removeClass('border-warning bg-light-warning border-success bg-light-success');
        if ($(this).val() === 'reparation') {
            $(this).closest('label').addClass('border-warning bg-light-warning');
        } else {
            $(this).closest('label').addClass('border-success bg-light-success');
        }
    });
});
</script>
@endpush
