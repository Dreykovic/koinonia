@php($accent = '#1E5AA8')
@extends('errors.layout')

@section('title', 'Maintenance')
@section('code')
503
@endsection
@section('heading', 'Maintenance en cours')
@section('icon')
    <i class="ki-duotone ki-setting-2"><i class="path1"></i><i class="path2"></i></i>
@endsection
@section('actions')
    <a href="javascript:location.reload();" class="btn-itcj-outline">
        <i class="ki-duotone ki-arrows-circle me-2"><i class="path1"></i><i class="path2"></i></i>
        Réessayer
    </a>
@endsection
