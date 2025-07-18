<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="route-daily-complete" content="{{ route('daily-challenges.complete') }}">
    <meta name="route-daily-change" content="{{ route('daily-challenges.change') }}">
    <meta name="route-logout" content="{{ route('logout') }}">
    <meta name="route-login" content="{{ route('login') }}">
    <meta name="route-weight-store" content="{{ route('weight.store') }}">

    @isset($events)
    <script>
        window._calendarEvents = @json($events);
    </script>
    @endisset

    <title>@yield('title', 'ミライボディ')</title>

    {{-- FullCalendar CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">

    {{-- Vite - CSSとJSを正しく読み込む --}}
    @vite(['resources/css/app.css','resources/css/sp.css', 'resources/js/app.js'])
</head>

<body>
    @if (!in_array(Route::currentRouteName(), ['login', 'register']))
    <header class="header">
        <div class="header__container">
            <h1 class="header__logo">
                <a href="/">
                    <!-- シンプルロゴ -->
                    <!-- <svg width="32" height="32" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="1.5" fill="none" aria-hidden="true">
                        <path d="M2 12L5 9.3M22 12L19 9.3M19 9.3L12 3L5 9.3M19 9.3V21H5V9.3"/>
                    </svg> -->
                    <!-- オリジナルロゴ -->
                    <svg width="60" height="60" viewBox="0 0 24 24">
                        <image href="/favicon.ico" x="0" y="0" width="24" height="24" />
                    </svg>
                </a>
            </h1>
            <div class="header__current-page">
                <span id="currentPageName"></span>
            </div>

            <input type="checkbox" id="check">
            <label for="check" class="hamburger">
                <span></span>
            </label>
            <nav class="nav">
                <ul class="nav__list">
                    <li class="nav__item"><a href="./">ホーム</a></li>
                    <li class="nav__item"><a href="./weight">体重管理</a></li>
                    <li class="nav__item"><button id="logoutBtn">ログアウト</button></li>
                </ul>
            </nav>
        </div>
    </header>
    @endif

    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/js-circle-progress/dist/circle-progress.min.js" type="module"></script>
    {{-- FullCalendarをapp.jsより前に読み込む --}}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    {{-- progressbar.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/progressbar.js/0.6.1/progressbar.min.js"></script>

    @stack('scripts')
</body>

</html>