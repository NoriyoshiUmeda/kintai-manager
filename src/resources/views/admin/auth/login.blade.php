@extends('admin.layouts.app')

@section('title', 'ログイン画面（管理者ユーザー）')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/auth/login.css') }}">
@endpush

@section('content')
    <h2 class="title">管理者ログイン</h2>

    <form method="POST" action="{{ route('admin.login.attempt') }}" class="form">
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
@endsection
