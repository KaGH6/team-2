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

    {{-- 月間達成率 --}}
    <div class="text-center mb-4">
        <h4>今月の達成率</h4>
        <div id="monthProgress" style="position: relative; display: inline-block;">
            <strong style="
          position: absolute;
          top: 50%; left: 50%;
          transform: translate(-50%, -50%);
          font-size: 1.2rem;
      "></strong>
        </div>
    </div>

    <!-- プログレスバーが表示される場所 -->
    <circle-progress value="50" max="100" text-format="percent"></circle-progress>

    {{-- 体重管理セクション --}}
    <div id="weight-section" class="mb-5">
        <h2>体重管理</h2>
        <form id="weightForm" class="row g-2 align-items-center mb-3">
            @csrf
            <div class="col-auto">
                <input type="date"
                    name="date"
                    id="weightDate"
                    class="form-control"
                    value="{{ now()->toDateString() }}"
                    required>
            </div>
            <div class="col-auto">
                <input type="number"
                    name="weight"
                    id="weightInput"
                    class="form-control"
                    step="0.1"
                    placeholder="体重 (kg)"
                    required>
            </div>
            <div class="col-auto">
                <button type="submit"
                    id="weightSubmitBtn"
                    class="btn btn-primary">
                    登録
                </button>
            </div>
        </form>
        <div id="saveMessage"></div>

        <canvas id="weightChart" height="200"></canvas>
    </div>

    {{-- カレンダー --}}
    <div id="calendar"></div>

    {{-- 体重グラフ --}}
    <div class="cjs-scope">
        <h1 id="cjsTitle">--</h1>
        <div class="cjs-card">
            <div class="cjs-controls cjs-controls-inline-suffix">
                <div class="cjs-inline-select">
                    <select id="yearSel" aria-label="年"></select><span class="cjs-suffix">年</span>
                </div>
                <div class="cjs-inline-select">
                    <select id="monthSel" aria-label="月"></select><span class="cjs-suffix">月</span>
                </div>
                <button id="jumpTodayBtn" title="今日の年月にジャンプ">今日</button>
            </div>

            <div class="cjs-chart-wrap">
                <canvas id="chart"></canvas>
            </div>

            <div class="cjs-meta-stats" id="metaStats">--</div>
        </div>
    </div>


</div>
@endsection