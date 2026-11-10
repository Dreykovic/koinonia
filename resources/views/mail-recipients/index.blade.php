@extends('layouts.app')

@section('title', 'Destinataires mails')
@section('page-title', 'Destinataires des Mails')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Configuration</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Destinataires mails</li>
@endsection

@section('content')
<div class="row g-5 g-xl-10">
    <div class="col-xl-8">
        @if(session('success'))
            <div class="alert alert-success mb-5">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger mb-5">{{ session('error') }}</div>
        @endif

        @foreach($types as $type => $label)
        @php $items = $recipients->get($type, collect()); @endphp
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title fw-bold">{{ $label }}</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th>Adresse</th>
                                <th>Nom</th>
                                <th class="text-center">Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $recipient)
                            <tr>
                                <td class="text-dark fw-bold">{{ $recipient->email }}</td>
                                <td>{{ $recipient->name }}</td>
                                <td class="text-center">
                                    @if($recipient->active)
                                        <span class="badge badge-light-success">Actif</span>
                                    @else
                                        <span class="badge badge-light-danger">Inactif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('mail-recipients.update', $recipient) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="active" value="{{ $recipient->active ? 0 : 1 }}">
                                        <button type="submit" class="btn btn-sm btn-light-primary">
                                            {{ $recipient->active ? 'Désactiver' : 'Activer' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('mail-recipients.destroy', $recipient) }}" method="POST" class="d-inline"
                                          data-confirm="Supprimer ce destinataire ?"
                                          data-confirm-title="Supprimer le destinataire"
                                          data-confirm-ok="Supprimer">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger">
                                            <i class="ki-duotone ki-trash fs-5"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-10">
                                    <div class="text-muted">Aucune adresse configurée : les administrateurs reçoivent ces mails.</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title fw-bold">Ajouter un Destinataire</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('mail-recipients.store') }}" method="POST">
                    @csrf
                    <div class="mb-5">
                        <label class="form-label fw-semibold required">Type de mail</label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            @foreach($types as $type => $label)
                                <option value="{{ $type }}" @selected(old('type') === $type)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-5">
                        <label class="form-label fw-semibold required">Adresse email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-5">
                        <label class="form-label fw-semibold">Nom (optionnel)</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-itcj w-100">Ajouter</button>
                </form>

                <div class="notice bg-light-primary rounded border-primary border border-dashed p-5 mt-5 fs-7 text-gray-700">
                    Quand au moins une adresse active est configurée pour un type, seules ces adresses reçoivent le mail.
                    Sinon, tous les administrateurs actifs le reçoivent.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
