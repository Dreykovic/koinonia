@extends('layouts.app')

@section('title', 'Nouveau Membre')
@section('page-title', 'Nouveau Membre')

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('users.index') }}" class="text-muted text-hover-primary">Membres</a>
</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Nouveau</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Creer un membre</h3>
    </div>
    <form action="{{ route('users.store') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label required" for="name">Nom complet</label>
                    <input type="text" id="name" name="name" class="form-control form-control-solid @error('name') is-invalid @enderror"
                        value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="email">Email</label>
                    <input type="email" inputmode="email" id="email" name="email" class="form-control form-control-solid @error('email') is-invalid @enderror"
                        value="{{ old('email') }}" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label required" for="password">Mot de passe</label>
                    <div class="position-relative">
                        <input type="password" id="password" name="password" class="form-control form-control-solid @error('password') is-invalid @enderror" required style="padding-right: 45px;">
                        <button type="button" class="btn btn-sm btn-icon position-absolute end-0 top-50 translate-middle-y me-2"
                            onclick="togglePassword(this)" aria-label="Afficher le mot de passe">
                            <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="password_confirmation">Confirmer le mot de passe</label>
                    <div class="position-relative">
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control form-control-solid" required style="padding-right: 45px;">
                        <button type="button" class="btn btn-sm btn-icon position-absolute end-0 top-50 translate-middle-y me-2"
                            onclick="togglePassword(this)" aria-label="Afficher le mot de passe">
                            <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label required" for="role">Role</label>
                    <select id="role" name="role" class="form-select form-select-solid @error('role') is-invalid @enderror" required>
                        <option value="membre" {{ old('role') == 'membre' ? 'selected' : '' }}>Membre</option>
                        <option value="formateur" {{ old('role') == 'formateur' ? 'selected' : '' }}>Formateur</option>
                        <option value="sante" {{ old('role') == 'sante' ? 'selected' : '' }}>Sante</option>
                        <option value="chauffeur" {{ old('role') == 'chauffeur' ? 'selected' : '' }}>Chauffeur</option>
                        <option value="communication" {{ old('role') == 'communication' ? 'selected' : '' }}>Communication</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrateur</option>
                    </select>
                    @error('role')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="community_id">Communaute</label>
                    <select id="community_id" name="community_id" class="form-select form-select-solid @error('community_id') is-invalid @enderror">
                        <option value="">Aucune</option>
                        @foreach($communities as $community)
                            <option value="{{ $community->id }}" {{ old('community_id') == $community->id ? 'selected' : '' }}>
                                {{ $community->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('community_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label" for="room_number">Numero de chambre</label>
                    <input type="text" id="room_number" name="room_number" class="form-control form-control-solid @error('room_number') is-invalid @enderror"
                        value="{{ old('room_number') }}" placeholder="Ex: 101, A12...">
                    @error('room_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="phone">Telephone</label>
                    <input type="tel" inputmode="tel" id="phone" name="phone" class="form-control form-control-solid @error('phone') is-invalid @enderror"
                        value="{{ old('phone') }}" placeholder="Ex: +237 6XX XXX XXX">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-5">
                <div class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                        {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Compte actif</label>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <a href="{{ route('users.index') }}" class="btn btn-light me-3">Annuler</a>
            <button type="submit" id="btn-submit" class="btn btn-primary">
                <span class="indicator-label">Creer</span>
                <span class="indicator-progress" style="display: none;">
                    Creation...
                    <span class="spinner-border spinner-border-sm ms-2"></span>
                </span>
            </button>
        </div>
    </form>
</div>

<!-- Mobile back button -->
<a href="{{ route('users.index') }}" class="mobile-back-btn d-md-none" aria-label="Retour aux membres">
    <i class="ki-duotone ki-arrow-left"><i class="path1"></i><i class="path2"></i></i>
</a>

<script>
document.querySelector('form').addEventListener('submit', function() {
    var btn = document.getElementById('btn-submit');
    btn.disabled = true;
    btn.querySelector('.indicator-label').style.display = 'none';
    btn.querySelector('.indicator-progress').style.display = 'inline-block';
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
