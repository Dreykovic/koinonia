@extends('layouts.app')

@section('title', 'Modifier Profil')
@section('page-title', 'Modifier mon Profil')

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('profile.show') }}" class="text-muted text-hover-primary">Profil</a>
</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Modifier</li>
@endsection

@section('content')
<div class="row g-5 g-xl-10">
    <!-- Informations personnelles -->
    <div class="col-xl-6">
        <div class="card card-flush">
            <div class="card-header pt-5">
                <h3 class="card-title fw-bold">Informations personnelles</h3>
            </div>
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="mb-5">
                        <label class="form-label required">Nom complet</label>
                        <input type="text" name="name" class="form-control form-control-solid @error('name') is-invalid @enderror"
                            value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" class="form-control form-control-solid @error('email') is-invalid @enderror"
                            value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label" for="phone">Telephone</label>
                        <input type="tel" inputmode="tel" id="phone" name="phone" class="form-control form-control-solid @error('phone') is-invalid @enderror"
                            value="{{ old('phone', $user->phone) }}" placeholder="Ex: +237 6XX XXX XXX">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Numero de chambre</label>
                        <input type="text" name="room_number" class="form-control form-control-solid @error('room_number') is-invalid @enderror"
                            value="{{ old('room_number', $user->room_number) }}" placeholder="Ex: 101, A12...">
                        @error('room_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" id="btn-submit-profile" class="btn btn-primary">
                        <span class="indicator-label">Enregistrer</span>
                        <span class="indicator-progress" style="display: none;">
                            Enregistrement...
                            <span class="spinner-border spinner-border-sm ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Changer mot de passe -->
    <div class="col-xl-6">
        <div class="card card-flush">
            <div class="card-header pt-5">
                <h3 class="card-title fw-bold">Changer le mot de passe</h3>
            </div>
            <form action="{{ route('profile.password') }}" method="POST" id="form-password">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="mb-5">
                        <label class="form-label required">Mot de passe actuel</label>
                        <div class="position-relative">
                            <input type="password" name="current_password" class="form-control form-control-solid @error('current_password') is-invalid @enderror" required style="padding-right: 45px;">
                            <button type="button" class="btn btn-sm btn-icon position-absolute end-0 top-50 translate-middle-y me-2"
                                onclick="togglePassword(this)" aria-label="Afficher le mot de passe">
                                <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                            </button>
                        </div>
                        @error('current_password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label required">Nouveau mot de passe</label>
                        <div class="position-relative">
                            <input type="password" name="password" class="form-control form-control-solid @error('password') is-invalid @enderror" required style="padding-right: 45px;">
                            <button type="button" class="btn btn-sm btn-icon position-absolute end-0 top-50 translate-middle-y me-2"
                                onclick="togglePassword(this)" aria-label="Afficher le mot de passe">
                                <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label required">Confirmer le mot de passe</label>
                        <div class="position-relative">
                            <input type="password" name="password_confirmation" class="form-control form-control-solid" required style="padding-right: 45px;">
                            <button type="button" class="btn btn-sm btn-icon position-absolute end-0 top-50 translate-middle-y me-2"
                                onclick="togglePassword(this)" aria-label="Afficher le mot de passe">
                                <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" id="btn-submit-password" class="btn btn-warning">
                        <span class="indicator-label">Changer le mot de passe</span>
                        <span class="indicator-progress" style="display: none;">
                            Modification...
                            <span class="spinner-border spinner-border-sm ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Mobile back button -->
<a href="{{ route('profile.show') }}" class="mobile-back-btn d-md-none" aria-label="Retour au profil">
    <i class="ki-duotone ki-arrow-left"><i class="path1"></i><i class="path2"></i></i>
</a>

<script>
document.querySelectorAll('form').forEach(function(form) {
    form.addEventListener('submit', function() {
        var btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            var label = btn.querySelector('.indicator-label');
            var progress = btn.querySelector('.indicator-progress');
            if (label) label.style.display = 'none';
            if (progress) progress.style.display = 'inline-block';
        }
    });
});

function togglePassword(btn) {
    var input = btn.parentElement.querySelector('input');
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'ki-duotone ki-eye-slash fs-4';
        icon.innerHTML = '<i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i>';
    } else {
        input.type = 'password';
        icon.className = 'ki-duotone ki-eye fs-4';
        icon.innerHTML = '<i class="path1"></i><i class="path2"></i><i class="path3"></i>';
    }
}
</script>
@endsection



