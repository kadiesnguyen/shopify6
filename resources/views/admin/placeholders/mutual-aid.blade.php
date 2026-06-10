@extends('layouts.admin')

@section('title', __('admin.menu.mutual_aid'))

@section('content')
    <x-admin.page-header :title="__('admin.menu.mutual_aid')" />
    <x-ui.empty-state :title="__('admin.placeholders.mutual_aid')" class="bg-white" />
@endsection
