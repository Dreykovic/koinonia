{{--
    Liste d'entrées de journal (table sur ordinateur, cartes sur mobile).
    Attend : $entries (itérable d'entrées LogReader), $userNames (id => nom),
    $showDate (bool, afficher la date en plus de l'heure).
--}}
@php
    $levelBadges = [
        'debug' => 'badge-light-secondary',
        'info' => 'badge-light-info',
        'notice' => 'badge-light-primary',
        'warning' => 'badge-light-warning',
        'error' => 'badge-light-danger',
        'critical' => 'badge-danger',
        'alert' => 'badge-danger',
        'emergency' => 'badge-danger',
    ];
    $entryUrl = fn ($entry) => route('admin.logs.show', array_filter([
        'channel' => $entry['channel'],
        'date' => $entry['file_date'],
        'offset' => $entry['offset'],
    ], fn ($v) => $v !== null));
    $requestUrl = fn ($entry) => route('admin.logs.request', [
        'requestId' => $entry['request_id'],
        'date' => $entry['datetime']->format('Y-m-d'),
    ]);
    $validRequestId = fn ($entry) => $entry['request_id'] && preg_match(\App\Http\Controllers\Admin\LogController::REQUEST_ID_PATTERN, $entry['request_id']);
@endphp

<div class="table-responsive">
    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-3 log-table">
        <thead>
            <tr class="fw-bold text-muted">
                <th class="min-w-100px">Date</th>
                <th class="min-w-80px">Niveau</th>
                <th class="min-w-80px">Canal</th>
                <th class="min-w-250px">Événement</th>
                <th class="min-w-120px">Utilisateur</th>
                <th class="min-w-100px">Requête</th>
                <th class="text-end"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
            <tr>
                <td class="text-nowrap">
                    @if($showDate)
                        <span class="text-dark fw-bold d-block">{{ $entry['datetime']->format('d/m/Y') }}</span>
                    @endif
                    <span class="text-muted fs-8">{{ $entry['datetime']->format('H:i:s') }}</span>
                </td>
                <td><span class="badge {{ $levelBadges[$entry['level']] ?? 'badge-light' }} fs-8 text-uppercase">{{ $entry['level'] }}</span></td>
                <td><span class="badge badge-light fs-8">{{ $entry['channel'] }}</span></td>
                <td>
                    <span class="text-dark fw-semibold log-message">{{ Str::limit($entry['message'], 160) }}</span>
                    @if($entry['exception'])
                        <span class="text-danger d-block fs-8">{{ Str::limit(strtok($entry['exception'], "\n"), 140) }}</span>
                    @endif
                    @if($entry['route'])
                        <span class="text-muted d-block fs-9">{{ $entry['route'] }}</span>
                    @endif
                </td>
                <td class="fs-8">
                    @if($entry['user_id'])
                        {{ $userNames[$entry['user_id']] ?? 'Utilisateur' }} <span class="text-muted">#{{ $entry['user_id'] }}</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="fs-8">
                    @if($validRequestId($entry))
                        <a href="{{ $requestUrl($entry) }}" title="Voir toute la requête {{ $entry['request_id'] }}"><code>{{ Str::limit($entry['request_id'], 8, '') }}</code></a>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="text-end">
                    <a href="{{ $entryUrl($entry) }}" class="btn btn-icon btn-sm btn-light-primary" title="Voir le détail">
                        <i class="ki-duotone ki-eye fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="logs-mobile">
    @foreach($entries as $entry)
        <div class="log-mobile-card">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <span class="fs-8 text-muted">
                    {{ $entry['datetime']->format($showDate ? 'd/m/Y H:i:s' : 'H:i:s') }} · {{ $entry['channel'] }}
                </span>
                <span class="badge {{ $levelBadges[$entry['level']] ?? 'badge-light' }} fs-8 text-uppercase">{{ $entry['level'] }}</span>
            </div>
            <a href="{{ $entryUrl($entry) }}" class="text-dark fw-semibold log-message d-block">{{ Str::limit($entry['message'], 120) }}</a>
            @if($entry['exception'])
                <span class="text-danger d-block fs-8 mt-1">{{ Str::limit(strtok($entry['exception'], "\n"), 100) }}</span>
            @endif
            <div class="fs-8 text-muted mt-2">
                @if($entry['user_id'])
                    {{ $userNames[$entry['user_id']] ?? 'Utilisateur' }} #{{ $entry['user_id'] }}
                @endif
                @if($validRequestId($entry))
                    · <a href="{{ $requestUrl($entry) }}"><code>{{ Str::limit($entry['request_id'], 8, '') }}</code></a>
                @endif
            </div>
        </div>
    @endforeach
</div>
