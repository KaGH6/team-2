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

    <title>@yield('title', 'MyApp')</title>

    {{-- FullCalendar CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">

    {{-- Vite - CSSとJSを正しく読み込む --}}
    @vite(['resources/css/app.css','resources/css/sp.css', 'resources/js/app.js'])
</head>

<body>
    @if (!in_array(Route::currentRouteName(), ['login', 'register']))
    <header class="header">
        <div class="headercontainer">
            <h1 class="headerlogo"><a href="/"></a></h1>
            <input type="checkbox" id="check">
            <label for="check" class="hamburger">
                <span></span>
            </label>
            <nav class="nav">
                <ul class="navlist">
                    <li class="navitem"><a href="/">ホーム</a></li>
                    <li class="navitem"><a href="#">体重管理</a></li>
                    <li class="navitem"><a href="#">ログアウト</a></li>
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

    @stack('scripts')
</body>

</html>