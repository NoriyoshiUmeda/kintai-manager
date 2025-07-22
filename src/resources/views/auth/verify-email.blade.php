@extends('layouts.app')

@section('title', 'メール認証誘導画面')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth/verify.css') }}">
@endpush

@section('content')
<div class="verify-main-container">
    <div class="verify-panel">
        <div class="verify-content">
            <div class="verify-title">
                登録していただいたメールアドレスに認証メールを送付しました。
            </div>
            <div class="verify-subtitle">
                メール認証を完了してください。
            </div>
            {{-- Mailtrapのトップへ遷移 --}}
            <a href="https://mailtrap.io/" class="verify-btn" target="_blank" rel="noopener">
                認証はこちらから
            </a>
            {{-- メール再送 --}}
            <form method="POST" action="{{ route('verification.send') }}" style="margin-top: 18px;">
                @csrf
                <button type="submit" class="resend-link">認証メールを再送する</button>
            </form>
            @if (session('message'))
                <div class="message">{{ session('message') }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
