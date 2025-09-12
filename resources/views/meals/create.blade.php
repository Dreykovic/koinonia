@extends('layouts.app')

@section('title', 'Nouveau Repas')
@section('page-title', 'Nouveau Repas du Dimanche')

@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{ route('meals.index') }}" class="text-muted text-hover-primary">Repas</a>
</li>
<li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
<li class="breadcrumb-item text-muted">Nouveau</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Creer un repas du dimanche</h3>
    </div>
    <form action="{{ route('meals.store') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label required">Date du repas</label>
                    <input type="date" name="meal_date" class="form-control form-control-solid @error('meal_date') is-invalid @enderror"
                        value="{{ old('meal_date') }}" required>
                    @error('meal_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Selectionnez un dimanche.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Capacite maximale</label>
                    <input type="number" name="max_capacity" class="form-control form-control-solid @error('max_capacity') is-invalid @enderror"
                        value="{{ old('max_capacity', 60) }}" min="1" max="200" required>
                    @error('max_capacity')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-5">
                <label class="form-label">Notes (optionnel)</label>
                <textarea name="notes" class="form-control form-control-solid @error('notes') is-invalid @enderror"
                    rows="3" placeholder="Informations supplementaires sur ce repas...">{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <a href="{{ route('meals.index') }}" class="btn btn-light me-3">Annuler</a>
            <button type="submit" class="btn btn-primary">Creer</button>
        </div>
    </form>
</div>
@endsection
