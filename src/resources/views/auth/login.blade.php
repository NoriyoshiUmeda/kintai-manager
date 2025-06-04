@extends('layouts.app')

@section('title', 'ログイン画面（一般ユーザー）')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
@endpush

@section('content')
    <h2 class="title">ログイン</h2>

    <form method="POST" action="{{ route('login') }}" class="form">
        @csrf

        {{-- メールアドレス --}}
        <div class="form-group">
            <label for="email">メールアドレス</label>
            <input
                type="text"
                name="email"
                id="email"
                value="{{ old('email') }}"
                autofocus
            >
            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        {{-- パスワード --}}
        <div class="form-group">
            <label for="password">パスワード</label>
            <input
                type="password"
                name="password"
                id="password"
                autocomplete="current-password"
            >
            @error('password')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        {{-- ログイン実行ボタン --}}
        <div class="form-group">
            <button type="submit" class="btn">ログインする</button>
        </div>

        {{-- 会員登録へのリンク --}}
        <div class="login-link">
            <a href="{{ route('register') }}">会員登録はこちら</a>
        </div>
    </form>
@endsection
