@extends('layouts.member')

@section('title', __('chat.title'))
@section('hide_portal_header', '1')
@section('full_bleed', '1')
@section('portal_chat_page', '1')

@section('content')
    <x-member.chat-panel
        :messages-url="route('member.chat.messages.index')"
        :send-url="route('member.chat.messages.store')"
        :back-url="route('member.home')"
    />
@endsection
