{{-- resources/views/home.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">

    {{-- 今日のチャレンジ --}}
    <div id="today-challenge" class="mb-4">
        <h2>今日のチャレンジ</h2>
        <p id="challenge-task"></p>
        <button id="completeChallengeBtn" class="btn btn-success">完了する</button>
        <button id="change-btn" class="btn btn-secondary">お題を変える</button>
    </div>

    {{-- カレンダー --}}
    <div id="calendar"></div>

</div>
@endsection

@push('scripts')
{{-- FullCalendar --}}
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1) コントローラから渡されたイベント配列
        const events = @json($events);

        // 2) カレンダーを初期化
        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: events,
            dateClick: function(info) {
                // 日付クリックでチャレンジ内容をポップアップ
                const ev = calendar.getEventById(info.dateStr);
                if (ev) {
                    alert(ev.extendedProps.content);
                }
            }
        });
        calendar.render();

        // 3) 本日のお題表示 & ボタンラベル初期設定
        const today = new Date().toISOString().slice(0, 10);
        const todaysEvent = events.find(e => e.id === today);
        const taskEl = document.getElementById('challenge-task');
        const completeBtn = document.getElementById('completeChallengeBtn');

        if (todaysEvent) {
            taskEl.innerHTML = todaysEvent.extendedProps.content;
            if (todaysEvent.extendedProps.is_completed) {
                completeBtn.textContent = '完了済み';
                completeBtn.disabled = true;
            }
        } else {
            taskEl.textContent = '本日のお題はまだありません';
            completeBtn.disabled = true;
        }

        // 4) CSRF トークン取得
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // 5) 完了ボタンクリック時の Ajax 処理
        completeBtn.addEventListener('click', function() {
            fetch("{{ route('daily-challenges.complete') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        date: today
                    })
                })
                .then(res => res.json())
                .then(data => {
                    // カレンダー上の古いイベントを置き換え
                    const oldEv = calendar.getEventById(data.id);
                    if (oldEv) oldEv.remove();
                    calendar.addEvent(data);

                    // ボタン・お題テキストを更新
                    taskEl.innerHTML = data.extendedProps.content;
                    completeBtn.textContent = '完了済み';
                    completeBtn.disabled = true;
                })
                .catch(() => alert('完了処理でエラーが発生しました'));
        });

        // 6) お題変更ボタンクリック時の Ajax 処理
        document.getElementById('change-btn').addEventListener('click', function() {
            fetch("{{ route('daily-challenges.change') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        date: today
                    })
                })
                .then(res => res.json())
                .then(data => {
                    // カレンダー上の古いイベントを置き換え
                    const oldEv = calendar.getEventById(data.id);
                    if (oldEv) oldEv.remove();
                    calendar.addEvent(data);

                    // ボタン・お題テキストを更新（リセット）
                    taskEl.innerHTML = data.extendedProps.content;
                    completeBtn.textContent = '完了する';
                    completeBtn.disabled = false;
                })
                .catch(() => alert('お題変更に失敗しました'));
        });

    });
</script>
@endpush