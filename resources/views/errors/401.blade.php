@php($accent = '#1E5AA8')
@extends('errors.layout')

@section('title', 'Non authentifié')
@section('code')
401
@endsection
@section('heading', 'Authentification requise')
@section('icon')
    <i class="ki-duotone ki-user"><i class="path1"></i><i class="path2"></i></i>
@endsection
@section('actions')
    <a href="{{ route('login') }}" class="btn-itcj">
        <i class="ki-duotone ki-entrance-right me-2"><i class="path1"></i><i class="path2"></i></i>
        Se connecter
    </a>
@endsection
