@php($accent = '#8B2332')
@extends('errors.layout')

@section('title', 'Erreur serveur')
@section('code')
{{ $exception->getStatusCode() }}
@endsection
@section('heading', 'Service indisponible')
@section('icon')
    <i class="ki-duotone ki-disconnect"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i></i>
@endsection
@section('actions')
    <a href="{{ route('home') }}" class="btn-itcj">
        <i class="ki-duotone ki-home-2 me-2"><i class="path1"></i><i class="path2"></i></i>
        Accueil
    </a>
    <a href="javascript:location.reload();" class="btn-itcj-outline">
        <i class="ki-duotone ki-arrows-circle me-2"><i class="path1"></i><i class="path2"></i></i>
        Réessayer
    </a>
@endsection
