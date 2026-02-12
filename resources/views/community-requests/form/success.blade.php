@extends('layouts.app')

@section('title', 'Demande envoyee')
@section('page-title', 'Demande Envoyee')

@section('breadcrumb')
<li class="breadcrumb-item text-muted">Confirmation</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body text-center py-15">
                <div class="mb-10">
                    <i class="ki-duotone ki-check-circle text-success fs-5tx">
                        <i class="path1"></i><i class="path2"></i>
                    </i>
                </div>

                <h1 class="text-dark fw-bolder mb-5">Demande envoyee avec succes!</h1>

                <div class="text-gray-500 fw-semibold fs-6 mb-10">
                    Votre demande a ete enregistree et sera traitee dans les plus brefs delais.<br>
                    Vous pouvez suivre l'etat de vos demandes depuis la liste des demandes.
                </div>

                <div class="d-flex flex-center gap-3">
                    <a href="{{ route('community-requests.form.step1') }}" class="btn btn-itcj-outline">
                        <i class="ki-duotone ki-plus fs-3 me-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
                        Nouvelle demande
                    </a>
                    <a href="{{ route('community-requests.index') }}" class="btn btn-itcj">
                        <i class="ki-duotone ki-element-11 fs-3 me-2"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i></i>
                        Voir mes demandes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
