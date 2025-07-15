{{-- resources/views/home.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <button id="logoutBtn">ログアウト</button>

    {{-- 今日のチャレンジ --}}
    <div id="today-challenge" class="mb-4">
        <h2>今日のチャレンジ</h2>
        <audio
            id="fireworksSound"
            preload="auto"
            crossorigin="anonymous"
            src="https://cdn.jsdelivr.net/gh/sfx-datasets/fireworks@main/fireworks-pop-2s.mp3">
        </audio>

        <div class="wrapper">
            <div class="goal-row">
                <div class="goal-card">
                    <span id="challenge-task" class="goal-text"></span>
                </div>
                <button id="change-btn" aria-label="Reload">
                    <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 4 23 10 17 10" />
                        <polyline points="1 20 1 14 7 14" />
                        <path d="M3.51 9a9 9 0 0 1 14.13-3.36L23 10" />
                        <path d="M20.49 15a9 9 0 0 1-14.13 3.36L1 14" />
                    </svg>
                </button>
            </div>
            <button id="completeChallengeBtn" class="btn btn-success">達成！</button>
        </div>


    </div>

    {{-- カレンダー --}}
    <div id="calendar"></div>

</div>
@endsection