<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '管理画面')｜勤怠管理システム</title>

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @stack('styles')
</head>

<body>
    <header class="header">
        <div class="logo">
                <img src="{{ asset('svg/logo.svg') }}" alt="COACHTECHロゴ" class="header-logo">
            </a>
        </div>

        @auth('admin')
        <nav class="nav-links">
            <a href="{{ route('admin.attendances.index') }}">勤怠一覧</a>
            <a href="{{ route('admin.users.index') }}">スタッフ一覧</a>
            <a href="{{ route('admin.corrections.index') }}">申請一覧</a>

            <a href="#" 
               onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                ログアウト
            </a>
            <form id="admin-logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </nav>
        @endauth
    </header>

    <main class="main">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
