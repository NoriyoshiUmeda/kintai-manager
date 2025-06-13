<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '勤怠管理システム')</title>

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @stack('styles')
</head>

<body>
    <header class="header">
        <div class="logo">
            <img src="{{ asset('svg/logo.svg') }}" alt="COACHTECHロゴ" class="header-logo">
        </div>

        @auth
        <nav class="nav-links">
            @if (isset($todayStatus) && $todayStatus === 3)
                <!-- 退勤後のリンク -->
                <a href="{{ route('attendances.index') }}">今月の出勤一覧</a>
                <a href="{{ route('corrections.index') }}">申請一覧</a>
            @else
                <!-- 出勤前・出勤中・休憩中のリンク -->
                <a href="{{ route('attendances.create') }}">勤怠</a>
                <a href="{{ route('attendances.index') }}">勤怠一覧</a>
                <a href="{{ route('corrections.index') }}">申請</a>
            @endif

            <!-- ログアウトは共通 -->
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                ログアウト
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
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
