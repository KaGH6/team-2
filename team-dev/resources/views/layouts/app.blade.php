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

    @isset($events)
    <script>
        window._calendarEvents = @json($events);
    </script>
    @endisset

    <title>@yield('title', 'MyApp')</title>

    {{-- FullCalendar CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">

    {{-- Vite - CSSとJSを正しく読み込む --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    @yield('content')

    {{-- FullCalendarをapp.jsより前に読み込む --}}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    @stack('scripts')
</body>

</html>