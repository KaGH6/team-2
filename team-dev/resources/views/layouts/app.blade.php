{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MyApp')</title>
    @vite(['resources/css/app.css'])
    <!-- <link rel="stylesheet" href="{{ asset('css/app.css') }}"> -->
</head>

<body>
    @yield('content')
    <!-- <script src="{{ asset('js/app.js') }}"></script> -->
    @stack('scripts')
</body>

</html>