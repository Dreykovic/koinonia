@extends('layouts.app')

@section('title', 'Entrée de journal')
@section('page-title', 'Journaux')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Administration</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item"><a href="{{ route('admin.logs.index') }}" class="text-muted">Journaux</a></li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Détail</li>
@endsection

@include('admin.logs._styles')

@php
    $context = $entry['context'];
    unset($context['exception']);
    $json = fn ($data) => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    $hasRequest = $entry['request_id'] && preg_match(\App\Http\Controllers\Admin\LogController::REQUEST_ID_PATTERN, $entry['request_id']);
@endphp

@section('content')
<div class="card">
    <div class="card-header py-4">
        <div>
            <h3 class="card-title fw-bold mb-0 log-message">{{ $entry['message'] }}</h3>
            <span class="text-muted fs-7">
                {{ $entry['datetime']->format('d/m/Y H:i:s') }}
                · <span class="text-uppercase">{{ $entry['level'] }}</span>
                · canal {{ $entry['channel'] }}
                · format {{ $entry['format'] === 'json' ? 'JSON' : 'ligne' }}
            </span>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="row g-3 mb-5 fs-7">
            <div class="col-md-4">
                <div class="text-muted">Utilisateur</div>
                @if($entry['user_id'])
                    {{ $userNames[$entry['user_id']] ?? 'Utilisateur' }} #{{ $entry['user_id'] }}
                @else
                    -
                @endif
            </div>
            <div class="col-md-4">
                <div class="text-muted">Route</div>
                {{ $entry['route'] ?? '-' }}
            </div>
            <div class="col-md-4">
                <div class="text-muted">Requête</div>
                @if($hasRequest)
                    <a href="{{ route('admin.logs.request', ['requestId' => $entry['request_id'], 'date' => $entry['datetime']->format('Y-m-d')]) }}"><code>{{ $entry['request_id'] }}</code></a>
                @else
                    -
                @endif
            </div>
        </div>

        @if($entry['exception'])
            <h4 class="fs-6 fw-bold mb-2">Exception</h4>
            <pre class="log-pre text-danger mb-5">{{ $entry['exception'] }}</pre>
        @endif

        @if($entry['trace'])
            <details class="log-trace mb-5">
                <summary>Stack trace ({{ substr_count($entry['trace'], "\n") + 1 }} lignes)</summary>
                <pre class="log-pre">{{ $entry['trace'] }}</pre>
            </details>
        @endif

        <h4 class="fs-6 fw-bold mb-2">Contexte</h4>
        <pre class="log-pre mb-5">{{ $context === [] ? 'Aucun contexte' : $json($context) }}</pre>

        @if($entry['extra'] !== [])
            <h4 class="fs-6 fw-bold mb-2">Extra</h4>
            <pre class="log-pre mb-5">{{ $json($entry['extra']) }}</pre>
        @endif

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.logs.index') }}" class="btn btn-sm btn-light">Retour</a>
            @if($hasRequest)
                <a href="{{ route('admin.logs.request', ['requestId' => $entry['request_id'], 'date' => $entry['datetime']->format('Y-m-d')]) }}" class="btn btn-sm btn-light-primary">Toute la requête</a>
            @endif
        </div>
    </div>
</div>
@endsection
