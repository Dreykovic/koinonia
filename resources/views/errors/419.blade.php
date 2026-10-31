@php($accent = '#D4A84B')
@extends('errors.layout')

@section('title', 'Session expirée')
@section('code')
419
@endsection
@section('heading', 'Session expirée')
@section('icon')
    <i class="ki-duotone ki-time"><i class="path1"></i><i class="path2"></i></i>
@endsection
@section('actions')
    <a href="javascript:history.back();" class="btn-itcj">
        <i class="ki-duotone ki-arrow-left me-2"><i class="path1"></i><i class="path2"></i></i>
        Revenir à la page
    </a>
    <a href="{{ route('login') }}" class="btn-itcj-outline">
        <i class="ki-duotone ki-entrance-right me-2"><i class="path1"></i><i class="path2"></i></i>
        Se reconnecter
    </a>
@endsection
