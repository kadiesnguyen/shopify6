@extends('layouts.member')

@section('title', __('member.customers.title'))

@section('content')
    <x-ui.empty-state
        :title="__('member.customers.placeholder')"
        class="bg-white"
    />
@endsection
