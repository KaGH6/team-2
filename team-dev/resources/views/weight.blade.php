{{-- resources/views/home.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">

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

        {{-- <canvas id="weightChart" height="200"></canvas> --}}
    </div>

    {{-- 体重グラフ --}}
    <div class="cjs-scope">
        <h1 id="cjsTitle">--</h1>
        <div class="cjs-meta-stats" id="metaStats">--</div>
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

            {{-- <div class="cjs-meta-stats" id="metaStats">--</div> --}}
        </div>
    </div>


</div>
@endsection