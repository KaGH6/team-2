// import "./bootstrap";

/* ===== Fallback synth ===== */
let synthCtx;
function synthFirework() {
    if (!synthCtx) synthCtx = new (window.AudioContext || window.webkitAudioContext)();
    const now = synthCtx.currentTime;
    const buf = synthCtx.createBuffer(1, synthCtx.sampleRate, synthCtx.sampleRate);
    const data = buf.getChannelData(0);
    for (let i = 0; i < data.length; i++) {
        data[i] = (Math.random() * 2 - 1) * (1 - i / buf.length);
    }
    const src = synthCtx.createBufferSource();
    src.buffer = buf;
    const g = synthCtx.createGain();
    g.gain.setValueAtTime(0.9, now);
    g.gain.exponentialRampToValueAtTime(0.0001, now + 0.9);
    src.connect(g).connect(synthCtx.destination);
    src.start(now);
    src.stop(now + 0.9);
}

function playChime() {
    const el = document.getElementById("fireworksSound");
    if (!el) {
        synthFirework();
        return;
    }
    el.currentTime = 0;
    const p = el.play();
    if (p && p.catch) {
        p.catch(() => synthFirework());
    } else {
        setTimeout(() => {
            if (el.paused) synthFirework();
        }, 300);
    }
}

function finish(btn) {
    btn.disabled = true;
    btn.textContent = "達成済み";
}

function createRipple(e, btn) {
    const r = document.createElement("span");
    r.className = "ripple";
    const rect = btn.getBoundingClientRect();
    r.style.left = `${e.clientX - rect.left}px`;
    r.style.top = `${e.clientY - rect.top}px`;
    r.style.width = r.style.height = `${rect.width * 2}px`;
    btn.appendChild(r);
    r.addEventListener("animationend", () => r.remove());
}

function spawnConfetti(x, y) {
    for (let i = 0; i < 140; i++) {
        const p = document.createElement("span");
        p.className = "confetti-piece";
        p.style.background = `hsl(${Math.random() * 360}, 80%, 60%)`;
        p.style.left = `${x}px`;
        p.style.top = `${y}px`;
        const ang = Math.random() * Math.PI * 2;
        const dist = Math.random() * 700 + 150;
        p.style.setProperty("--dx", `${Math.cos(ang) * dist}px`);
        p.style.setProperty("--dy", `${Math.sin(ang) * dist}px`);
        document.body.appendChild(p);
        p.addEventListener("animationend", () => p.remove());
    }
}

// カスタムモーダル表示関数
function showChallengeModal(content, date) {
    // 既存のモーダルがあれば削除
    const existingModal = document.getElementById('challenge-modal');
    if (existingModal) existingModal.remove();

    // モーダルの作成
    const modal = document.createElement('div');
    modal.id = 'challenge-modal';
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>${date} のチャレンジ</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>${content}</p>
            </div>
        </div>
    `;

    // モーダルを表示
    document.body.appendChild(modal);

    // クローズボタンとオーバーレイクリックでモーダルを閉じる
    const closeBtn = modal.querySelector('.modal-close');
    closeBtn.addEventListener('click', () => modal.remove());
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.remove();
    });
}

// FullCalendarが読み込まれるまで待つ
function waitForFullCalendar(callback) {
    if (typeof FullCalendar !== 'undefined') {
        callback();
    } else {
        setTimeout(() => waitForFullCalendar(callback), 100);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // --- Blade側で埋め込む<meta>タグからURLを取得 ---
    const routeDailyComplete = document.querySelector('meta[name="route-daily-complete"]')?.content;
    const routeDailyChange = document.querySelector('meta[name="route-daily-change"]')?.content;
    const routeLogout = document.querySelector('meta[name="route-logout"]')?.content;
    const routeLogin = document.querySelector('meta[name="route-login"]')?.content;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // 必須要素がない場合は処理を中断
    if (!routeDailyComplete || !routeDailyChange || !routeLogout || !routeLogin || !csrfToken) {
        console.error('必要なメタタグが見つかりません');
        return;
    }

    // コントローラから渡されたイベント配列
    const events = window._calendarEvents || [];

    // FullCalendarが読み込まれたら初期化
    waitForFullCalendar(() => {
        // カレンダー初期化
        const today = new Date().toISOString().slice(0, 10);
        const calendarEl = document.getElementById('calendar');

        if (!calendarEl) {
            console.error('カレンダー要素が見つかりません');
            return;
        }

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: events,
            dateClick(info) {
                const ev = calendar.getEventById(info.dateStr);
                if (ev) showChallengeModal(ev.extendedProps.content, info.dateStr);
            }
        });
        calendar.render();

        // 本日のお題 & ボタン設定
        const taskEl = document.getElementById('challenge-task');
        const completeBtn = document.getElementById('completeChallengeBtn');

        if (!taskEl || !completeBtn) {
            console.error('必要な要素が見つかりません');
            return;
        }

        const todaysEvent = events.find(e => e.id === today);
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

        // 完了ボタン
        completeBtn.addEventListener('click', e => {
            e.preventDefault();
            if (completeBtn.disabled) return;

            createRipple(e, completeBtn);
            const rect = completeBtn.getBoundingClientRect();
            spawnConfetti(
                rect.left + rect.width / 2,
                rect.top + rect.height / 2
            );
            playChime();
            finish(completeBtn);

            fetch(routeDailyComplete, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ date: today })
            })
                .then(res => res.json())
                .then(data => {
                    const oldEv = calendar.getEventById(data.id);
                    if (oldEv) oldEv.remove();
                    calendar.addEvent(data);
                    taskEl.innerHTML = data.extendedProps.content;
                    completeBtn.textContent = '完了済み';
                    completeBtn.disabled = true;
                })
                .catch(() => alert('完了処理でエラーが発生しました'));
        });

        // お題変更ボタン
        const changeBtn = document.getElementById('change-btn');
        if (changeBtn) {
            changeBtn.addEventListener('click', () => {
                fetch(routeDailyChange, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ date: today })
                })
                    .then(res => res.json())
                    .then(data => {
                        const oldEv = calendar.getEventById(data.id);
                        if (oldEv) oldEv.remove();
                        calendar.addEvent(data);
                        taskEl.innerHTML = data.extendedProps.content;
                        completeBtn.textContent = '完了する';
                        completeBtn.disabled = false;
                    })
                    .catch(() => alert('お題変更に失敗しました'));
            });
        }
    });

    // ログアウト
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            fetch(routeLogout, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        window.location.href = routeLogin;
                    }
                });
        });
    }

    // リロードボタン（存在する場合）
    const reloadBtn = document.getElementById('reloadBtn');
    if (reloadBtn) {
        reloadBtn.addEventListener('click', () => location.reload());
    }
});