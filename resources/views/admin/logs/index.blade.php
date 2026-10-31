@extends('layouts.app')

@section('title', 'Journaux')
@section('page-title', 'Journaux')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Administration</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Journaux</li>
@endsection

@include('admin.logs._styles')

@section('content')
<div class="card mb-5">
    <div class="card-header py-4">
        <div>
            <h3 class="card-title fw-bold mb-0">
                <i class="ki-duotone ki-document fs-3 text-primary me-2"><i class="path1"></i><i class="path2"></i></i>
                Journaux de l'application
            </h3>
            <span class="text-muted fs-7">Consultation en lecture seule des fichiers de logs, du plus récent au plus ancien</span>
        </div>
    </div>
    <div class="card-body pt-0">
        @if($errors->any())
            <div class="alert alert-danger mb-5">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="GET" action="{{ route('admin.logs.index') }}" class="log-filters">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <label for="log-from">Du</label>
                    <input type="datetime-local" id="log-from" name="from" class="form-control form-control-sm" value="{{ $input['from'] ?? '' }}">
                </div>
                <div class="col-6 col-md-3">
                    <label for="log-to">Au</label>
                    <input type="datetime-local" id="log-to" name="to" class="form-control form-control-sm" value="{{ $input['to'] ?? '' }}">
                </div>
                <div class="col-6 col-md-3">
                    <label for="log-level">Niveau minimum</label>
                    <select id="log-level" name="level" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach($levels as $level)
                            <option value="{{ $level }}" @selected(($input['level'] ?? '') === $level)>{{ ucfirst($level) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label for="log-channel">Canal</label>
                    <select id="log-channel" name="channel" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach($channels as $channel)
                            <option value="{{ $channel }}" @selected(($input['channel'] ?? '') === $channel)>{{ $channel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label for="log-user">Utilisateur</label>
                    <input type="text" id="log-user" name="user" class="form-control form-control-sm" placeholder="Id, e-mail ou nom" value="{{ $input['user'] ?? '' }}">
                </div>
                <div class="col-12 col-md-3">
                    <label for="log-route">Route</label>
                    <input type="text" id="log-route" name="route" class="form-control form-control-sm" placeholder="ex. reservations.store" value="{{ $input['route'] ?? '' }}">
                </div>
                <div class="col-12 col-md-3">
                    <label for="log-request">Request id</label>
                    <input type="text" id="log-request" name="request_id" class="form-control form-control-sm" placeholder="Référence de la page d'erreur" value="{{ $input['request_id'] ?? '' }}">
                </div>
                <div class="col-12 col-md-3">
                    <label for="log-q">Texte libre</label>
                    <input type="search" id="log-q" name="q" class="form-control form-control-sm" placeholder="Événement, message, contexte…" value="{{ $input['q'] ?? '' }}">
                </div>
            </div>
            <div class="d-flex gap-2 mt-4 flex-wrap">
                <button type="submit" class="btn btn-sm btn-itcj">
                    <i class="ki-duotone ki-magnifier fs-5"><i class="path1"></i><i class="path2"></i></i>
                    Filtrer
                </button>
                <a href="{{ route('admin.logs.index') }}" class="btn btn-sm btn-light">Réinitialiser</a>
            </div>
        </form>

        @if($matchedUsers !== null)
            <div class="text-muted fs-8 mt-3">
                @if($matchedUsers->isEmpty())
                    Aucun utilisateur ne correspond à « {{ $input['user'] }} ».
                @else
                    Utilisateur(s) : {{ $matchedUsers->map(fn ($name, $id) => $name.' #'.$id)->implode(', ') }}
                @endif
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header py-4">
        <h3 class="card-title fw-bold mb-0">
            {{ $total }} entrée(s)
            @if(isset($input['from']) && ! isset($input['to']) && ! request()->filled('from'))
                <span class="text-muted fs-7 fw-normal ms-2">sur les dernières 24 heures</span>
            @endif
        </h3>
    </div>
    <div class="card-body pt-0 log-table-wrapper">
        @if($entries->isEmpty())
            <div class="text-center text-muted py-10">
                <i class="ki-duotone ki-check-circle fs-3x text-success mb-3"><i class="path1"></i><i class="path2"></i></i>
                <p class="mb-0">Aucune entrée ne correspond à ces filtres.</p>
            </div>
        @else
            @include('admin.logs._entries', ['entries' => $entries, 'showDate' => true])
        @endif

        @if($entries->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
