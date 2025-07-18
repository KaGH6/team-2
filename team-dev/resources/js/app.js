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
    const existingModal = document.getElementById('challenge-modal');
    if (existingModal) existingModal.remove();

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

    document.body.appendChild(modal);

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

// 達成率を更新する関数
function updateProgressCircle(events) {
    const progressElement = document.querySelector('circle-progress');
    if (!progressElement) {
        console.warn('circle-progress element not found');
        return;
    }

    const todayDate = new Date();
    const currentDay = todayDate.getDate();
    const currentMonth = todayDate.getMonth() + 1;

    const thisMonthEvents = events.filter(e => {
        const d = new Date(e.start);
        return (d.getMonth() + 1) === currentMonth && d.getDate() <= currentDay;
    });

    const completedDays = thisMonthEvents.filter(e => e.extendedProps.is_completed).length;
    const percentage = currentDay > 0 ? Math.floor((completedDays / currentDay) * 100) : 0;

    progressElement.setAttribute('value', percentage);
    progressElement.style.transition = 'all 0.8s ease';
}

// ===== 体重グラフ関連の関数 =====
function initWeightChart(csrfToken) {
    const yearSel = document.getElementById('yearSel');
    const monthSel = document.getElementById('monthSel');
    const metaStats = document.getElementById('metaStats');
    const titleEl = document.getElementById('cjsTitle');
    const ctx = document.getElementById('chart')?.getContext('2d');

    if (!yearSel || !monthSel || !ctx) {
        console.log('Weight chart elements not found');
        return;
    }

    console.log('Initializing weight chart');

    // ユーティリティ関数
    function getDaysInMonth(y, m) { return new Date(y, m, 0).getDate(); }

    function buildDailyTotals(records, y, m) {
        const days = getDaysInMonth(y, m);
        const totals = Array(days).fill(null);
        records.forEach(r => {
            const d = new Date(r.date);
            if (d.getFullYear() === y && d.getMonth() + 1 === m) {
                const idx = d.getDate() - 1;
                totals[idx] = r.weight;
            }
        });
        return totals;
    }

    function calcStats(data) {
        const nums = data.filter(v => v != null);
        if (!nums.length) return { min: null, max: null, avg: null };
        const min = Math.min(...nums);
        const max = Math.max(...nums);
        const avg = nums.reduce((a, b) => a + b, 0) / nums.length;
        return { min, max, avg };
    }

    function fmt(num) { return (num == null ? '--' : num.toFixed(1)); }

    // 年月セレクタを構築
    const now = new Date();
    const nowY = now.getFullYear();
    for (let y = nowY - 2; y <= nowY + 1; y++) yearSel.add(new Option(y, y));
    for (let m = 1; m <= 12; m++) monthSel.add(new Option(m, m));

    // 前回の選択を復元（localStorage使用）
    const savedYear = localStorage.getItem('weightChart_selectedYear');
    const savedMonth = localStorage.getItem('weightChart_selectedMonth');

    yearSel.value = savedYear || nowY;
    monthSel.value = savedMonth || (now.getMonth() + 1);

    // タイトル更新
    function updateTitle(y, m) {
        const isThisMonth = (y === nowY && m === (now.getMonth() + 1));
        if (titleEl) {
            titleEl.textContent = `${y}年${m}月` + (isThisMonth ? ' 今月' : '');
        }
    }

    // Chart.js セットアップ
    const rootScope = document.querySelector('.cjs-scope');
    const BRAND_RGB = rootScope ? getComputedStyle(rootScope).getPropertyValue('--cjs-brand-rgb')?.trim() || '0,191,165' : '0,191,165';
    const GRID_COLOR = rootScope ? getComputedStyle(rootScope).getPropertyValue('--cjs-border-grid')?.trim() || '#d0d0d0' : '#d0d0d0';
    const FG_MUTED = rootScope ? getComputedStyle(rootScope).getPropertyValue('--cjs-fg-muted')?.trim() || '#666' : '#666';

    function makeFillGradient(ctx, area) {
        const g = ctx.createLinearGradient(0, area.top, 0, area.bottom);
        g.addColorStop(0, `rgba(${BRAND_RGB},0.25)`);
        g.addColorStop(1, `rgba(${BRAND_RGB},0.00)`);
        return g;
    }

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: '体重 (kg)',
                data: [],
                borderColor: `rgba(${BRAND_RGB},1)`,
                backgroundColor: (context) => {
                    const { chart, chartArea } = context;
                    if (!chartArea) return `rgba(${BRAND_RGB},0.25)`;
                    return makeFillGradient(chart.ctx, chartArea);
                },
                fill: true,
                tension: 0.25,
                spanGaps: true,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: `rgba(${BRAND_RGB},1)`,
                pointBorderWidth: 0
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: FG_MUTED }
                },
                y: {
                    beginAtZero: false,
                    grid: { color: GRID_COLOR, lineWidth: 0.5 },
                    ticks: { color: FG_MUTED }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: (items) => {
                            if (!items.length) return '';
                            const day = items[0].label.toString().padStart(2, '0');
                            const y = yearSel.value;
                            const m = String(monthSel.value).padStart(2, '0');
                            return `${y}-${m}-${day}`;
                        },
                        label: (ctx) => {
                            const v = ctx.parsed.y;
                            return v == null ? 'データなし' : `${v.toFixed(1)} kg`;
                        }
                    }
                }
            },
            animation: { duration: 320, easing: 'easeOutQuart' }
        }
    });

    // データ描画 & 統計表示
    function draw(y, m) {
        updateTitle(y, m);

        const data = buildDailyTotals(window.userWeightRecords || [], y, m);
        const days = getDaysInMonth(y, m);
        chart.data.labels = Array.from({ length: days }, (_, i) => i + 1);
        chart.data.datasets[0].data = data;
        chart.update();

        const { min, max, avg } = calcStats(data);
        if (metaStats) {
            metaStats.innerHTML = `最小 <strong>${fmt(min)}</strong> / 最大 <strong>${fmt(max)}</strong> / 平均 <strong>${fmt(avg)}</strong> kg`;
        }
    }

    // グローバルに公開
    window.updateWeightChart = () => {
        draw(+yearSel.value, +monthSel.value);
    };

    // イベントリスナー
    yearSel.addEventListener('change', () => {
        const selectedYear = +yearSel.value;
        const selectedMonth = +monthSel.value;

        // 選択を保存
        localStorage.setItem('weightChart_selectedYear', selectedYear);
        localStorage.setItem('weightChart_selectedMonth', selectedMonth);

        draw(selectedYear, selectedMonth);
    });

    monthSel.addEventListener('change', () => {
        const selectedYear = +yearSel.value;
        const selectedMonth = +monthSel.value;

        // 選択を保存
        localStorage.setItem('weightChart_selectedYear', selectedYear);
        localStorage.setItem('weightChart_selectedMonth', selectedMonth);

        draw(selectedYear, selectedMonth);
    });

    const jumpTodayBtn = document.getElementById('jumpTodayBtn');
    if (jumpTodayBtn) {
        jumpTodayBtn.addEventListener('click', () => {
            const now = new Date();
            yearSel.value = now.getFullYear();
            monthSel.value = now.getMonth() + 1;
            draw(+yearSel.value, +monthSel.value);
        });
    }

    // 初期データ取得と描画
    fetchWeightDataAndInitChart(csrfToken, () => {
        draw(+yearSel.value, +monthSel.value);
    });
}

// ====== データ取得関数 ======
async function fetchWeightDataAndInitChart(csrfToken, callback) {
    try {
        // キャッシュされたデータがあるかチェック
        const cachedData = localStorage.getItem('weightChart_cachedData');
        const cacheTimestamp = localStorage.getItem('weightChart_cacheTimestamp');
        const now = Date.now();

        // キャッシュが5分以内の場合は使用
        if (cachedData && cacheTimestamp && (now - parseInt(cacheTimestamp) < 5 * 60 * 1000)) {
            console.log('Using cached weight data');
            window.userWeightRecords = JSON.parse(cachedData);
            if (callback) callback();
            return;
        }

        console.log('Fetching weight data...');
        const res = await fetch('/user/weights', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }

        const data = await res.json();
        console.log('Weight data received:', data);

        window.userWeightRecords = data.weights || [];

        // データをキャッシュに保存
        localStorage.setItem('weightChart_cachedData', JSON.stringify(window.userWeightRecords));
        localStorage.setItem('weightChart_cacheTimestamp', now.toString());

        console.log('Weight records stored:', window.userWeightRecords);

        if (callback) callback();
    } catch (error) {
        console.error('データ取得エラー:', error);
        // エラー時はキャッシュデータを使用
        const cachedData = localStorage.getItem('weightChart_cachedData');
        if (cachedData) {
            console.log('Using cached data due to fetch error');
            window.userWeightRecords = JSON.parse(cachedData);
        } else {
            window.userWeightRecords = [];
        }
        if (callback) callback();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded');

    // --- Blade側で埋め込む<meta>タグからURLを取得 ---
    const routeDailyComplete = document.querySelector('meta[name="route-daily-complete"]')?.content;
    const routeDailyChange = document.querySelector('meta[name="route-daily-change"]')?.content;
    const routeLogout = document.querySelector('meta[name="route-logout"]')?.content;
    const routeLogin = document.querySelector('meta[name="route-login"]')?.content;
    const routeWeightStore = document.querySelector('meta[name="route-weight-store"]')?.content;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // 必須要素がない場合は処理を中断
    if (!csrfToken) {
        console.error('CSRFトークンが見つかりません');
        return;
    }

    // グローバル変数として体重データを保持
    window.userWeightRecords = window.userWeightRecords || [];

    // コントローラから渡されたイベント配列
    const events = window._calendarEvents || [];

    // 初期表示時に達成率を更新
    updateProgressCircle(events);

    // ===== チャレンジ管理部分 =====
    if (routeDailyComplete && routeDailyChange) {
        waitForFullCalendar(() => {
            const today = new Date().toISOString().slice(0, 10);
            const calendarEl = document.getElementById('calendar');

            if (!calendarEl) {
                console.warn('カレンダー要素が見つかりません');
                return;
            }

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: "dayGridMonth",
                locale: "ja",
                events: events,
                fixedWeekCount: false,
                buttonText: {
                    today: "今日",
                },
                dayCellContent: function (arg) {
                    // 数字だけ抽出
                    return { html: String(arg.date.getDate()) };
                },
                dateClick(info) {
                    const ev = calendar.getEventById(info.dateStr);
                    if (ev)
                        showChallengeModal(
                            ev.extendedProps.content,
                            info.dateStr
                        );
                },
            });
            calendar.render();

            const taskEl = document.getElementById('challenge-task');
            const completeBtn = document.getElementById('completeChallengeBtn');

            if (taskEl && completeBtn) {
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

                completeBtn.addEventListener('click', e => {
                    e.preventDefault();
                    if (completeBtn.disabled) return;

                    createRipple(e, completeBtn);
                    const rect = completeBtn.getBoundingClientRect();
                    spawnConfetti(rect.left + rect.width / 2, rect.top + rect.height / 2);
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

                            const eventIndex = events.findIndex(e => e.id === data.id);
                            if (eventIndex !== -1) {
                                events[eventIndex] = data;
                            } else {
                                events.push(data);
                            }
                            updateProgressCircle(events);
                        })
                        .catch(() => alert('完了処理でエラーが発生しました'));
                });

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
            }
        });
    }

    // ===== 体重管理部分 =====
    const weightForm = document.getElementById('weightForm');
    console.log('Weight form found:', !!weightForm);
    console.log('Weight store route:', routeWeightStore);

    if (weightForm && routeWeightStore) {
        weightForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log('Weight form submitted');

            const formData = new FormData(e.target);
            const submitBtn = document.getElementById('weightSubmitBtn');
            const messageDiv = document.getElementById('saveMessage');

            // フォームデータの確認
            const requestData = {
                date: formData.get('date'),
                weight: formData.get('weight')
            };
            console.log('Request data:', requestData);

            submitBtn.disabled = true;
            submitBtn.textContent = '登録中...';

            try {
                console.log('Sending request to:', routeWeightStore);
                const response = await fetch(routeWeightStore, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(requestData)
                });

                console.log('Response status:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('Response data:', data);

                if (data.success) {
                    window.userWeightRecords = data.allWeights;
                    console.log('Updated weight records:', window.userWeightRecords);

                    // キャッシュを更新
                    localStorage.setItem('weightChart_cachedData', JSON.stringify(window.userWeightRecords));
                    localStorage.setItem('weightChart_cacheTimestamp', Date.now().toString());

                    // グラフ更新関数が存在する場合は実行
                    if (typeof window.updateWeightChart === 'function') {
                        window.updateWeightChart();
                        console.log('Weight chart updated');
                    }

                    if (messageDiv) {
                        messageDiv.innerHTML = '<div class="alert alert-success">登録しました</div>';
                    }

                    const weightInput = document.getElementById('weightInput');
                    if (weightInput) {
                        weightInput.value = '';
                    }

                    setTimeout(() => {
                        if (messageDiv) messageDiv.innerHTML = '';
                    }, 3000);
                } else {
                    console.error('Server returned success:false', data);
                    if (messageDiv) {
                        messageDiv.innerHTML = '<div class="alert alert-danger">登録に失敗しました</div>';
                    }
                }
            } catch (error) {
                console.error('Error details:', error);
                if (messageDiv) {
                    messageDiv.innerHTML = '<div class="alert alert-danger">エラーが発生しました: ' + error.message + '</div>';
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = '登録';
            }
        });
    } else {
        console.warn('Weight form not found or route not defined');
    }

    // ===== 体重グラフ部分 =====
    const chartEl = document.getElementById('chart');
    const yearSel = document.getElementById('yearSel');
    const monthSel = document.getElementById('monthSel');

    // グラフの初期化（Chart.jsが読み込まれるまで待つ）
    if (chartEl && yearSel && monthSel) {
        console.log('Chart elements found, initializing...');

        function waitForChartJs() {
            if (typeof Chart !== 'undefined') {
                console.log('Chart.js loaded, initializing weight chart');
                initWeightChart(csrfToken);
            } else {
                console.log('Waiting for Chart.js...');
                setTimeout(waitForChartJs, 100);
            }
        }

        waitForChartJs();
    } else {
        console.log('Chart elements not found');
    }

    //  ヘッダー：ページタイトル自動表示
    const pageMapping = {
        "/": "ホーム",
        "./": "ホーム",
        "/weight": "体重管理",
        "./weight": "体重管理",
    };
    // -----------------
    // debag
    console.log("app.js loaded", Math.random());
    updateCurrentPageName();

    function updateCurrentPageName() {
        const currentPageElement = document.getElementById("currentPageName");
        const currentPath = window.location.pathname;
        const pageName = pageMapping[currentPath] || "ホーム";
        // console.log("currentPath:", currentPath, "pageName:", pageName);
        // console.log("updateCurrentPageName called!");

        if (currentPageElement) {
            currentPageElement.textContent = pageName;
        }
    }

    // ログアウト
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn && routeLogout) {
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
                    } else if (routeLogin) {
                        window.location.href = routeLogin;
                    }
                });
        });
    }

    // リロードボタン
    const reloadBtn = document.getElementById('reloadBtn');
    if (reloadBtn) {
        reloadBtn.addEventListener('click', () => location.reload());
    }
});