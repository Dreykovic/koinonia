@extends('layouts.app')

@section('title', 'Modifier Membre')
@section('page-title', 'Modifier le Membre')

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('users.index') }}" class="text-muted text-hover-primary">Membres</a>
</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Modifier</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Modifier {{ $user->name }}</h3>
    </div>
    <form action="{{ route('users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label required">Nom complet</label>
                    <input type="text" name="name" class="form-control form-control-solid @error('name') is-invalid @enderror"
                        value="{{ old('name', $user->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Email</label>
                    <input type="email" name="email" class="form-control form-control-solid @error('email') is-invalid @enderror"
                        value="{{ old('email', $user->email) }}" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label">Nouveau mot de passe</label>
                    <div class="position-relative">
                        <input type="password" name="password" class="form-control form-control-solid @error('password') is-invalid @enderror" style="padding-right: 45px;">
                        <button type="button" class="btn btn-sm btn-icon position-absolute end-0 top-50 translate-middle-y me-2"
                            onclick="togglePassword(this)" aria-label="Afficher le mot de passe">
                            <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Laisser vide pour ne pas changer.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <div class="position-relative">
                        <input type="password" name="password_confirmation" class="form-control form-control-solid" style="padding-right: 45px;">
                        <button type="button" class="btn btn-sm btn-icon position-absolute end-0 top-50 translate-middle-y me-2"
                            onclick="togglePassword(this)" aria-label="Afficher le mot de passe">
                            <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label required" for="roles">Rôles</label>
                    <select id="roles" name="roles[]" class="form-select form-select-solid @error('roles') is-invalid @enderror @error('roles.*') is-invalid @enderror" multiple required>
                        @foreach(\App\Enums\PlatformRole::options() as $value => $label)
                            <option value="{{ $value }}" {{ in_array($value, old('roles', $user->roles ?? [])) ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">Maintenez Ctrl (Cmd sur Mac) pour sélectionner plusieurs rôles</small>
                    @error('roles')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @error('roles.*')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="user_profile_id">Profil</label>
                    <select id="user_profile_id" name="user_profile_id" class="form-select form-select-solid @error('user_profile_id') is-invalid @enderror">
                        <option value="">Aucun profil</option>
                        @foreach($profiles as $profile)
                            <option value="{{ $profile->id }}" {{ old('user_profile_id', $user->user_profile_id) == $profile->id ? 'selected' : '' }}>
                                {{ $profile->name }}
                                @if(!$profile->is_active)
                                    <span class="text-muted">(Inactif)</span>
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @if($profiles->isEmpty())
                        <small class="form-text text-warning">Aucun profil disponible. Créez-en un d'abord.</small>
                    @endif
                    @error('user_profile_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label" for="community_id">Communauté</label>
                    <select id="community_id" name="community_id" class="form-select form-select-solid @error('community_id') is-invalid @enderror">
                        <option value="">Aucune</option>
                        @foreach($communities as $community)
                            <option value="{{ $community->id }}" {{ old('community_id', $user->community_id) == $community->id ? 'selected' : '' }}>
                                {{ $community->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('community_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Statut</label>
                    <div class="form-check form-switch form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            Utilisateur actif
                        </label>
                    </div>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label">Numero de chambre</label>
                    <input type="text" name="room_number" class="form-control form-control-solid @error('room_number') is-invalid @enderror"
                        value="{{ old('room_number', $user->room_number) }}">
                    @error('room_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telephone</label>
                    <input type="text" name="phone" class="form-control form-control-solid @error('phone') is-invalid @enderror"
                        value="{{ old('phone', $user->phone) }}">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-5">
                <div class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                        {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label">Compte actif</label>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <a href="{{ route('users.index') }}" class="btn btn-light me-3">Annuler</a>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </form>
</div>

<script>
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

