@php($accent = '#D4A84B')
@extends('errors.layout')

@section('title', 'Trop de requêtes')
@section('code')
429
@endsection
@section('heading', 'Trop de requêtes')
@section('icon')
    <i class="ki-duotone ki-timer"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
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
