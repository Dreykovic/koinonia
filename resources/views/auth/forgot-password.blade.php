@extends('layouts.guest')

@section('title', 'Mot de passe oublie')

@section('content')
<div class="d-flex flex-column flex-root">
    <div class="d-flex flex-column flex-column-fluid">
        <div class="d-flex flex-row-fluid flex-column flex-column-fluid text-center p-10 py-lg-20">
            <a href="{{ route('login') }}" class="pt-lg-20 mb-12">
                <span class="fs-2qx fw-bold text-primary">ITCJ</span>
                <span class="d-block fs-4 text-muted">Community</span>
            </a>

            <div class="w-lg-500px bg-body rounded shadow-sm p-10 p-lg-15 mx-auto">
                <form class="form w-100" method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="text-center mb-10">
                        <h1 class="text-dark mb-3">Mot de passe oublie ?</h1>
                        <div class="text-gray-400 fw-semibold fs-4">
                            Entrez votre email pour recevoir un lien de reinitialisation.
                        </div>
                    </div>

                    @if(session('status'))
                        <div class="alert alert-success mb-5">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger mb-5">
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div class="fv-row mb-10">
                        <label class="form-label fs-6 fw-bold text-dark">Email</label>
                        <input class="form-control form-control-lg form-control-solid"
                            type="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus required>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-lg btn-primary w-100 mb-5">
                            Envoyer le lien
                        </button>

                        <a href="{{ route('login') }}" class="btn btn-lg btn-light-primary w-100">
                            Retour a la connexion
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
