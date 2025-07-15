import "./bootstrap";
/* ===== Fallback synth ===== */
let synthCtx;

function synthFirework() {
    if (!synthCtx)
        synthCtx = new (window.AudioContext || window.webkitAudioContext)();
    const now = synthCtx.currentTime;
    const buf = synthCtx.createBuffer(
        1,
        synthCtx.sampleRate,
        synthCtx.sampleRate
    );
    const data = buf.getChannelData(0);
    for (let i = 0; i < data.length; i++) {
        data[i] = (Math.random() * 2 - 1) * (1 - i / buf.length);
    } // noise burst
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
            if (el.paused) {
                synthFirework();
            }
        }, 300);
    }
}

const finish = (btn) => {
    btn.disabled = true;
    btn.textContent = "達成済み";
};

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

const press = document.getElementById("completeChallengeBtn");
press.addEventListener("click", (e) => {
    if (press.disabled) return;
    createRipple(e, press);
    const rect = press.getBoundingClientRect();
    spawnConfetti(rect.left + rect.width / 2, rect.top + rect.height / 2);
    playChime();
    finish(press);
});

document
    .getElementById("reloadBtn")
    .addEventListener("click", () => location.reload());
