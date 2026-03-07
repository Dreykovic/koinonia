@extends('layouts.app')

@section('title', 'Nouvelle demande')
@section('page-title', 'Reparations et Achats en Communaute')
@push('styles')
    <style>
.share-overlay {
    display: none;
    position: fixed;
    z-index: 9999;
    inset: 0;
    background: rgba(0,0,0,0.5);
    -webkit-backdrop-filter: blur(4px);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
}
.share-overlay.active {
    display: flex;
}
.share-panel {
    background: #fff;
    width: 90vw;
    max-width: 400px;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    animation: shareSlideUp 0.25s ease-out;
}
@keyframes shareSlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.share-close {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: #f5f5f5;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #666;
    transition: background 0.2s;
}
.share-close:hover { background: #e0e0e0; }

.share-socials {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}
.share-social-btn {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.15s, box-shadow 0.15s;
    color: #fff;
    font-size: 20px;
}
.share-social-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.share-social-btn.whatsapp { background: #25D366; }
.share-social-btn.telegram { background: #0088cc; }
.share-social-btn.email { background: #EA4335; }
.share-social-btn.native { background: var(--itcj-blue, #1976d2); }

.share-link-group {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8f8f8;
    border-radius: 10px;
    padding: 6px 6px 6px 14px;
}
.share-link-group input {
    flex: 1;
    border: none;
    background: transparent;
    font-size: 13px;
    color: #333;
    outline: none;
    min-width: 0;
}
.share-link-copy {
    flex-shrink: 0;
    border: none;
    background: var(--itcj-blue, #1976d2);
    color: #fff;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
    white-space: nowrap;
}
.share-link-copy:hover { opacity: 0.85; }
.share-link-copy.copied {
    background: #2e7d32;
}

.share-qr-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}
.share-qr-section canvas,
.share-qr-section img {
    border-radius: 8px;
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
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
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
<div id="shareModal" class="share-overlay" role="dialog" aria-labelledby="shareModalTitle" aria-modal="true">
    <div class="share-panel">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 id="shareModalTitle" class="mb-0 fw-bold">Partager le formulaire</h4>
            <button class="share-close" onclick="closeShareModal()" aria-label="Fermer">&times;</button>
        </div>

        <!-- Social sharing -->
        <div class="share-socials mb-4">
            <button class="share-social-btn whatsapp" onclick="shareVia('whatsapp')" title="WhatsApp">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </button>
            <button class="share-social-btn telegram" onclick="shareVia('telegram')" title="Telegram">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
            </button>
            <button class="share-social-btn email" onclick="shareVia('email')" title="Email">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            </button>
            <button class="share-social-btn native" id="nativeShareBtn" onclick="shareVia('native')" title="Plus d'options" style="display:none;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.39 3 3 3s3-1.39 3-3-1.39-3-3-3z"/></svg>
            </button>
        </div>

        <!-- Copy link -->
        <div class="share-link-group mb-4">
            <input type="text" id="shareLink" readonly value="">
            <button class="share-link-copy" id="copyBtn" onclick="copyLink()">Copier</button>
        </div>

        <!-- QR Code -->
        <div class="share-qr-section">
            <div id="qrcode"></div>
            <div class="d-flex gap-2 flex-wrap justify-content-center">
                <a id="downloadQR" class="btn btn-sm btn-itcj" download="formulaire-qrcode.png">
                    <i class="ki-duotone ki-arrow-down fs-4 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                    PNG
                </a>
                <a href="{{ route('community-requests.form.qr-download') }}" class="btn btn-sm btn-itcj-outline">
                    <i class="ki-duotone ki-arrow-down fs-4 me-1"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                    SVG
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js" integrity="sha384-3zSEDfvllQohrq0PHL1fOXJuC/jSOO34H46t6UQfobFOmxE5BpjjaIJY5F2/bMnU" crossorigin="anonymous"></script>
<script>
let qr;
const formUrl = @json(route('community-requests.form.step1'));
const shareText = "Formulaire de demande - Reparations et Achats en Communaute";

// Show native share button if supported
if (navigator.share) {
    document.getElementById('nativeShareBtn').style.display = '';
}

function openShareModal() {
    var modal = document.getElementById('shareModal');
    modal.classList.add('active');
    document.getElementById('shareLink').value = formUrl;
    document.body.style.overflow = 'hidden';

    document.getElementById('qrcode').innerHTML = "";
    qr = new QRCode(document.getElementById("qrcode"), {
        text: formUrl,
        width: 180,
        height: 180,
    });

    setTimeout(function() {
        var canvas = document.querySelector("#qrcode canvas");
        if (canvas) {
            document.getElementById('downloadQR').href = canvas.toDataURL("image/png");
        }
    }, 300);
}

function closeShareModal() {
    document.getElementById('shareModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Close on overlay click
document.getElementById('shareModal').addEventListener('click', function(e) {
    if (e.target === this) closeShareModal();
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeShareModal();
});

function shareVia(platform) {
    var encoded = encodeURIComponent(formUrl);
    var textEncoded = encodeURIComponent(shareText + "\n" + formUrl);
    var url = '';

    switch (platform) {
        case 'whatsapp':
            url = 'https://wa.me/?text=' + textEncoded;
            break;
        case 'telegram':
            url = 'https://t.me/share/url?url=' + encoded + '&text=' + encodeURIComponent(shareText);
            break;
        case 'email':
            url = 'mailto:?subject=' + encodeURIComponent(shareText) + '&body=' + encodeURIComponent(formUrl);
            break;
        case 'native':
            if (navigator.share) {
                navigator.share({ title: shareText, url: formUrl }).catch(function() {});
            }
            return;
    }
    if (url) window.open(url, '_blank', 'noopener,noreferrer');
}

function copyLink() {
    var input = document.getElementById('shareLink');
    var btn = document.getElementById('copyBtn');
    input.select();
    input.setSelectionRange(0, 99999);

    function onCopied() {
        btn.textContent = 'Copie !';
        btn.classList.add('copied');
        setTimeout(function() {
            btn.textContent = 'Copier';
            btn.classList.remove('copied');
        }, 2000);
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(input.value).then(onCopied).catch(function() {
            document.execCommand('copy');
            onCopied();
        });
    } else {
        document.execCommand('copy');
        onCopied();
    }
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


