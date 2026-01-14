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
                    <input type="password" name="password" class="form-control form-control-solid @error('password') is-invalid @enderror">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Laisser vide pour ne pas changer.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation" class="form-control form-control-solid">
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label required">Role</label>
                    <select name="role" class="form-select form-select-solid @error('role') is-invalid @enderror" required>
                        <option value="membre" {{ old('role', $user->role) == 'membre' ? 'selected' : '' }}>Membre</option>
                        <option value="formateur" {{ old('role', $user->role) == 'formateur' ? 'selected' : '' }}>Formateur</option>
                        <option value="sante" {{ old('role', $user->role) == 'sante' ? 'selected' : '' }}>Sante</option>
                        <option value="communication" {{ old('role', $user->role) == 'communication' ? 'selected' : '' }}>Communication</option>
                        <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Administrateur</option>
                    </select>
                    @error('role')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Communaute</label>
                    <select name="community_id" class="form-select form-select-solid @error('community_id') is-invalid @enderror">
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
@endsection
