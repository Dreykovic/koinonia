@extends('layouts.app')

@section('title', 'Modifier le Bulletin')
@section('page-title', 'Modifier le Bulletin N' . $bulletin->edition_number)

@push('styles')
<style>
    .section-card {
        border-left: 4px solid var(--itcj-blue);
    }
    .section-card.gold {
        border-left-color: var(--itcj-gold);
    }
    .section-card.red {
        border-left-color: var(--itcj-red);
    }
    .item-card {
        background: #f8f9fc;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 0.75rem;
    }
    .item-card:last-child {
        margin-bottom: 0;
    }
</style>
@endpush

@section('content')
<div class="row g-5">
    <!-- Informations de base -->
    <div class="col-lg-4">
        <div class="card mb-5">
            <div class="card-header py-4">
                <h3 class="card-title fw-bold mb-0">Edition N{{ $bulletin->edition_number }}</h3>
                <span class="text-muted fs-7">Annee {{ $bulletin->year }}</span>
            </div>
            <div class="card-body">
                <form action="{{ route('bulletin.update', $bulletin) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="form-label required">Titre</label>
                        <input type="text" name="title" class="form-control" value="{{ $bulletin->title }}" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Sous-titre</label>
                        <input type="text" name="subtitle" class="form-control" value="{{ $bulletin->subtitle }}">
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <label class="form-label required">Debut</label>
                            <input type="date" name="week_start" id="week_start" class="form-control" value="{{ $bulletin->week_start->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label required">Fin</label>
                            <input type="date" name="week_end" id="week_end" class="form-control" value="{{ $bulletin->week_end->format('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="text-muted fs-8 mb-3">
                        <i class="ki-duotone ki-information fs-7"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                        Calcul auto sur 7 jours
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_published" id="is_published" value="1" {{ $bulletin->is_published ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_published">Publie</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-itcj w-100">
                        <i class="ki-duotone ki-check me-2"><i class="path1"></i><i class="path2"></i></i>
                        Enregistrer
                    </button>
                </form>
            </div>
        </div>

        <!-- Apercu -->
        <div class="d-grid gap-2">
            <a href="{{ route('bulletin.show', $bulletin) }}" class="btn btn-itcj-outline" target="_blank">
                <i class="ki-duotone ki-eye me-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                Voir l'apercu
            </a>
            <a href="{{ route('bulletin.index') }}" class="btn btn-light">
                <i class="ki-duotone ki-arrow-left me-2"><i class="path1"></i><i class="path2"></i></i>
                Retour a la liste
            </a>
        </div>
    </div>

    <!-- Contenu du bulletin -->
    <div class="col-lg-8">
        <!-- Services de la semaine -->
        <div class="card section-card mb-5">
            <div class="card-header py-4 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title fw-bold mb-0">Services de la Semaine</h3>
                    <span class="text-muted fs-7">Sante, Tarife, Chauffeur, etc.</span>
                </div>
                <button type="button" class="btn btn-sm btn-itcj" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                    <i class="ki-duotone ki-plus fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                    Ajouter
                </button>
            </div>
            <div class="card-body">
                @forelse($bulletin->weeklyServices as $service)
                    <div class="item-card d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge badge-light-primary me-2">{{ $service->service_label }}</span>
                            <strong>{{ $service->assigned_display_name }}</strong>
                            @if($service->description)
                                <div class="text-muted small mt-1">{{ $service->description }}</div>
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-icon btn-sm btn-light-warning" aria-label="Modifier le service" data-bs-toggle="modal" data-bs-target="#editServiceModal{{ $service->id }}">
                                <i class="ki-duotone ki-pencil fs-6"><i class="path1"></i><i class="path2"></i></i>
                            </button>
                            <form action="{{ route('bulletin.services.destroy', $service) }}" method="POST"
                                  data-confirm="Supprimer ce service ?"
                                  data-confirm-title="Supprimer le service"
                                  data-confirm-ok="Supprimer">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-sm btn-light-danger" aria-label="Supprimer le service">
                                    <i class="ki-duotone ki-trash fs-6"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Modal edit service -->
                    <div class="modal fade" id="editServiceModal{{ $service->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('bulletin.services.update', $service) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Modifier le service</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label required">Type de service</label>
                                            <select name="service_type" class="form-select" required>
                                                @foreach($serviceTypes as $key => $label)
                                                    <option value="{{ $key }}" {{ $service->service_type === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Membre assigne</label>
                                            <select name="assigned_user_id" class="form-select">
                                                <option value="">-- Selectionner --</option>
                                                @foreach($users as $user)
                                                    <option value="{{ $user->id }}" {{ $service->assigned_user_id == $user->id ? 'selected' : '' }}>{{ $user->title_prefix }} {{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Ou nom (si pas membre)</label>
                                            <input type="text" name="assigned_name" class="form-control" value="{{ $service->assigned_name }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Communaute</label>
                                            <input type="text" name="community" class="form-control" value="{{ $service->community }}" placeholder="Ex: Petite communaute XAVIER">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="2">{{ $service->description }}</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-itcj">Enregistrer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center py-4">Aucun service ajoute</p>
                @endforelse
            </div>
        </div>

        <!-- Activites -->
        <div class="card section-card gold mb-5">
            <div class="card-header py-4 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title fw-bold mb-0">Activites / Annonces</h3>
                    <span class="text-muted fs-7">Messes, reunions, visites, evenements</span>
                </div>
                <button type="button" class="btn btn-sm btn-itcj-gold" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                    <i class="ki-duotone ki-plus fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                    Ajouter
                </button>
            </div>
            <div class="card-body">
                @forelse($bulletin->weeklyActivities->sortBy('activity_date') as $activity)
                    <div class="item-card d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge me-2" style="background:{{ $activity->type_color['bg'] }};color:{{ $activity->type_color['text'] }}">
                                <i class="ki-duotone {{ $activity->type_icon }} fs-6" aria-hidden="true"></i>
                                {{ $activity->type_label }}
                            </span>
                            <strong>{{ $activity->title }}</strong>
                            @if($activity->activity_date)
                                <span class="text-muted ms-2">{{ $activity->formatted_date }} {{ $activity->formatted_time }}</span>
                            @endif
                            @if($activity->description)
                                <div class="text-muted small mt-1">{{ $activity->description }}</div>
                            @endif
                             @if($activity->guests)
                                    <div class="service-pill" role="listitem">
                                        <span aria-hidden="true">
                                            <i class="ki-duotone ki-people"><i class="path1"></i><i class="path2"></i></i>
                                        </span>
                                        <span>Invites: {{ Str::limit($activity->guests, 20) }}</span>
                                    </div>
                            @endif

                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-icon btn-sm btn-light-warning edit-activity-btn" aria-label="Modifier l'activite"
                                data-activity-id="{{ $activity->id }}"
                                data-activity-title="{{ $activity->title }}"
                                data-activity-type="{{ $activity->type }}"
                                data-activity-date="{{ $activity->activity_date?->format('Y-m-d') }}"
                                data-activity-time="{{ $activity->activity_time ? \Carbon\Carbon::parse($activity->activity_time)->format('H:i') : '' }}"
                                data-activity-location="{{ $activity->location }}"
                                data-activity-description="{{ $activity->description }}"
                                data-activity-guests="{{ $activity->guests }}"
                                data-activity-highlighted="{{ $activity->is_highlighted ? '1' : '0' }}"
                                data-bs-toggle="modal" data-bs-target="#editActivityModal">
                                <i class="ki-duotone ki-pencil fs-6"><i class="path1"></i><i class="path2"></i></i>
                            </button>
                            <form action="{{ route('bulletin.activities.destroy', $activity) }}" method="POST"
                                  data-confirm="Supprimer cette activite ?"
                                  data-confirm-title="Supprimer l'activite"
                                  data-confirm-ok="Supprimer">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-sm btn-light-danger" aria-label="Supprimer l'activite">
                                    <i class="ki-duotone ki-trash fs-6"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <i class="ki-duotone ki-calendar fs-3x text-muted mb-3"><i class="path1"></i><i class="path2"></i></i>
                        <p class="text-muted mb-4">Aucune activite ajoutee</p>
                        <button type="button" class="btn btn-sm btn-itcj-gold" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                            <i class="ki-duotone ki-plus fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                            Ajouter une activite
                        </button>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Reflexions spirituelles -->
        <div class="card section-card red mb-5">
            <div class="card-header py-4 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title fw-bold mb-0">Reflexions</h3>
                    <span class="text-muted fs-7">Spiritualite et Theologie</span>
                </div>
                <button type="button" class="btn btn-sm btn-itcj-red" data-bs-toggle="modal" data-bs-target="#addReflectionModal">
                    <i class="ki-duotone ki-plus fs-4"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                    Ajouter
                </button>
            </div>
            <div class="card-body">
                @forelse($bulletin->spiritualReflections as $reflection)
                    <div class="item-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge {{ $reflection->type === 'spiritualite' ? 'badge-itcj' : 'badge-itcj-gold' }} me-2">{{ $reflection->type_label }}</span>
                                <strong>{{ $reflection->title }}</strong>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-icon btn-sm btn-light-warning" aria-label="Modifier la reflexion" data-bs-toggle="modal" data-bs-target="#editReflectionModal{{ $reflection->id }}">
                                    <i class="ki-duotone ki-pencil fs-6"><i class="path1"></i><i class="path2"></i></i>
                                </button>
                                <form action="{{ route('bulletin.reflections.destroy', $reflection) }}" method="POST"
                                      data-confirm="Supprimer cette reflexion ?"
                                      data-confirm-title="Supprimer la reflexion"
                                      data-confirm-ok="Supprimer">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-icon btn-sm btn-light-danger" aria-label="Supprimer la reflexion">
                                        <i class="ki-duotone ki-trash fs-6"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <p class="text-muted small mb-1" style="white-space: pre-line;">{{ Str::limit($reflection->content, 200) }}</p>
                        @if($reflection->source)
                            <small class="text-muted fst-italic">{{ $reflection->source }}</small>
                        @endif
                    </div>

                    <!-- Modal edit reflection -->
                    <div class="modal fade" id="editReflectionModal{{ $reflection->id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form action="{{ route('bulletin.reflections.update', $reflection) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Modifier la reflexion</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label required">Type</label>
                                            <select name="type" class="form-select" required>
                                                @foreach($reflectionTypes as $key => $label)
                                                    <option value="{{ $key }}" {{ $reflection->type === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">Titre</label>
                                            <input type="text" name="title" class="form-control" value="{{ $reflection->title }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">Contenu</label>
                                            <textarea name="content" class="form-control" rows="10" required>{{ $reflection->content }}</textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Source</label>
                                            <input type="text" name="source" class="form-control" value="{{ $reflection->source }}" placeholder="Ex: 35e Congregation Generale, decret 4, n38-39">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-itcj">Enregistrer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center py-4">Aucune reflexion ajoutee</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Modal: Ajouter un service -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('bulletin.services.store', $bulletin) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Type de service</label>
                        <select name="service_type" class="form-select" required>
                            <option value="">-- Selectionner --</option>
                            @foreach($serviceTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Membre assigne</label>
                        <select name="assigned_user_id" class="form-select">
                            <option value="">-- Selectionner --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->title_prefix }} {{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ou nom (si pas membre)</label>
                        <input type="text" name="assigned_name" class="form-control" placeholder="Ex: S. Jean DUPONT, SJ">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Communaute</label>
                        <input type="text" name="community" class="form-control" placeholder="Ex: Petite communaute XAVIER">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Details supplementaires..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-itcj">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Ajouter une activite -->
<div class="modal fade" id="addActivityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('bulletin.activities.store', $bulletin) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une activite</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Titre</label>
                        <input type="text" name="title" class="form-control" required placeholder="Ex: Messe a 6h45 du lundi au vendredi">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="">-- Selectionner --</option>
                            @foreach($activityTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="activity_date" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Heure</label>
                            <input type="time" name="activity_time" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lieu</label>
                        <input type="text" name="location" class="form-control" placeholder="Ex: Chapelle, Salle de reunion">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                     <div class="mb-3">
                        <label class="form-label">Invites</label>
                        <textarea name="guests" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_highlighted" value="1">
                        <label class="form-check-label">Mettre en evidence</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-itcj">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Modifier une activite (dynamic single modal) -->
<div class="modal fade" id="editActivityModal" tabindex="-1" role="dialog" aria-labelledby="editActivityModalTitle" aria-modal="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editActivityForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editActivityModalTitle">Modifier l'activite</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Titre</label>
                        <input type="text" name="title" id="editActivityTitle" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Type</label>
                        <select name="type" id="editActivityType" class="form-select" required>
                            @foreach($activityTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="activity_date" id="editActivityDate" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Heure</label>
                            <input type="time" name="activity_time" id="editActivityTime" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lieu</label>
                        <input type="text" name="location" id="editActivityLocation" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="editActivityDescription" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Invites</label>
                        <textarea name="guests" id="editActivityGuests" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_highlighted" id="editActivityHighlighted" value="1">
                        <label class="form-check-label">Mettre en evidence</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-itcj">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Ajouter une reflexion -->
<div class="modal fade" id="addReflectionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('bulletin.reflections.store', $bulletin) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une reflexion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="">-- Selectionner --</option>
                            @foreach($reflectionTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Titre</label>
                        <input type="text" name="title" class="form-control" required placeholder="Ex: Le jesuite en formation">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Contenu</label>
                        <textarea name="content" class="form-control" rows="10" required placeholder="Entrez le texte de la reflexion..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Source</label>
                        <input type="text" name="source" class="form-control" placeholder="Ex: 35e Congregation Generale, decret 4, n38-39">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-itcj">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dynamic activity edit modal
    var activityBaseUrl = "{{ url('bulletin/activities') }}";
    document.querySelectorAll('.edit-activity-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.activityId;
            document.getElementById('editActivityForm').action = activityBaseUrl + '/' + id;
            document.getElementById('editActivityTitle').value = this.dataset.activityTitle || '';
            document.getElementById('editActivityType').value = this.dataset.activityType || '';
            document.getElementById('editActivityDate').value = this.dataset.activityDate || '';
            document.getElementById('editActivityTime').value = this.dataset.activityTime || '';
            document.getElementById('editActivityLocation').value = this.dataset.activityLocation || '';
            document.getElementById('editActivityDescription').value = this.dataset.activityDescription || '';
            document.getElementById('editActivityGuests').value = this.dataset.activityGuests || '';
            document.getElementById('editActivityHighlighted').checked = this.dataset.activityHighlighted === '1';
        });
    });

    // Week date auto-calculation
    const weekStart = document.getElementById('week_start');
    const weekEnd = document.getElementById('week_end');
    let isManualOverride = false;

    function addDays(dateStr, days) {
        const date = new Date(dateStr);
        date.setDate(date.getDate() + days);
        return date.toISOString().split('T')[0];
    }

    function subtractDays(dateStr, days) {
        const date = new Date(dateStr);
        date.setDate(date.getDate() - days);
        return date.toISOString().split('T')[0];
    }

    weekStart.addEventListener('change', function() {
        if (this.value && !isManualOverride) {
            weekEnd.value = addDays(this.value, 6);
        }
        isManualOverride = false;
    });

    weekEnd.addEventListener('change', function() {
        if (this.value) {
            const startDate = new Date(weekStart.value);
            const endDate = new Date(this.value);
            const diffDays = Math.round((endDate - startDate) / (1000 * 60 * 60 * 24));

            if (diffDays !== 6) {
                weekStart.value = subtractDays(this.value, 6);
                isManualOverride = true;
            }
        }
    });
});
</script>
@endpush

