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
                    <label class="form-label required">Nom complet</label>
                    <input type="text" name="name" class="form-control form-control-solid @error('name') is-invalid @enderror"
                        value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Email</label>
                    <input type="email" name="email" class="form-control form-control-solid @error('email') is-invalid @enderror"
                        value="{{ old('email') }}" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label required">Mot de passe</label>
                    <input type="password" name="password" class="form-control form-control-solid @error('password') is-invalid @enderror" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation" class="form-control form-control-solid" required>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label required">Role</label>
                    <select name="role" class="form-select form-select-solid @error('role') is-invalid @enderror" required>
                        <option value="membre" {{ old('role') == 'membre' ? 'selected' : '' }}>Membre</option>
                        <option value="formateur" {{ old('role') == 'formateur' ? 'selected' : '' }}>Formateur</option>
                        <option value="sante" {{ old('role') == 'sante' ? 'selected' : '' }}>Sante</option>
                        <option value="communication" {{ old('role') == 'communication' ? 'selected' : '' }}>Communication</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrateur</option>
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
                    <label class="form-label">Numero de chambre</label>
                    <input type="text" name="room_number" class="form-control form-control-solid @error('room_number') is-invalid @enderror"
                        value="{{ old('room_number') }}" placeholder="Ex: 101, A12...">
                    @error('room_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telephone</label>
                    <input type="text" name="phone" class="form-control form-control-solid @error('phone') is-invalid @enderror"
                        value="{{ old('phone') }}" placeholder="Ex: +237 6XX XXX XXX">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-5">
                <div class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                        {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label">Compte actif</label>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <a href="{{ route('users.index') }}" class="btn btn-light me-3">Annuler</a>
            <button type="submit" class="btn btn-primary">Creer</button>
        </div>
    </form>
</div>
@endsection
