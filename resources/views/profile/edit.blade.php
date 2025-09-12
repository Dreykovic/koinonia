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
                        <label class="form-label">Telephone</label>
                        <input type="text" name="phone" class="form-control form-control-solid @error('phone') is-invalid @enderror"
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
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
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
            <form action="{{ route('profile.password') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="mb-5">
                        <label class="form-label required">Mot de passe actuel</label>
                        <input type="password" name="current_password" class="form-control form-control-solid @error('current_password') is-invalid @enderror" required>
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label required">Nouveau mot de passe</label>
                        <input type="password" name="password" class="form-control form-control-solid @error('password') is-invalid @enderror" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <label class="form-label required">Confirmer le mot de passe</label>
                        <input type="password" name="password_confirmation" class="form-control form-control-solid" required>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-warning">Changer le mot de passe</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
