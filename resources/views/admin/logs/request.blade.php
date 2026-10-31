@extends('layouts.app')

@section('title', 'Requête '.Str::limit($requestId, 8, ''))
@section('page-title', 'Journaux')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Administration</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item"><a href="{{ route('admin.logs.index') }}" class="text-muted">Journaux</a></li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Requête</li>
@endsection

@include('admin.logs._styles')

@section('content')
<div class="card">
    <div class="card-header py-4">
        <div>
            <h3 class="card-title fw-bold mb-0">
                Requête <code class="ms-2">{{ $requestId }}</code>
            </h3>
            <span class="text-muted fs-7">
                {{ count($entries) }} ligne(s), tous canaux confondus, dans l'ordre chronologique
                @if(request()->filled('date'))
                    (fichiers du {{ request('date') }} ± 1 jour,
                    <a href="{{ route('admin.logs.request', ['requestId' => $requestId]) }}">chercher dans tous les fichiers</a>)
                @endif
            </span>
        </div>
    </div>
    <div class="card-body pt-0 log-table-wrapper">
        @if($truncated)
            <div class="alert alert-warning mb-5">Affichage limité aux {{ count($entries) }} premières lignes.</div>
        @endif

        @if(empty($entries))
            <div class="text-center text-muted py-10">
                <p class="mb-0">Aucune ligne trouvée pour cette requête.</p>
            </div>
        @else
            @include('admin.logs._entries', ['entries' => $entries, 'showDate' => true])
        @endif

        <a href="{{ route('admin.logs.index') }}" class="btn btn-sm btn-light mt-4">Retour aux journaux</a>
    </div>
</div>
@endsection
