@php($accent = '#D4A84B')
@extends('errors.layout')

@section('title', 'Requête impossible')
@section('code')
{{ $exception->getStatusCode() }}
@endsection
@section('heading', 'Requête impossible')
@section('icon')
    <i class="ki-duotone ki-information-5"><i class="path1"></i><i class="path2"></i><i class="path3"></i></i>
@endsection
