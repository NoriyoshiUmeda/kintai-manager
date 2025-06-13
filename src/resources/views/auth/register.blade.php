@extends('layouts.app')

@section('title', '会員登録画面（一般ユーザー）')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
@endpush

@section('content')
    <h2 class="title">会員登録</h2>

    <form method="POST" action="{{ route('register') }}" class="form">
        @csrf

        <div class="form-group">
            <label for="name">名前</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}">
            @error('name')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">メールアドレス</label>
            <input type="text" name="email" id="email" value="{{ old('email') }}">
            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">パスワード</label>
            <input type="password" name="password" id="password">
            @error('password')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">パスワード確認</label>
            <input type="password" name="password_confirmation" id="password_confirmation">
            @error('password_confirmation')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <button type="submit" class="btn">登録する</button>
        </div>

        <div class="login-link">
            <a href="{{ route('login') }}">ログインはこちら</a>
        </div>
    </form>
@endsection
