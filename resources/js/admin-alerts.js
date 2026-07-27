function playAdminAlertTone() {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;

    if (!AudioContextClass) {
        return;
    }

    const ctx = playAdminAlertTone.ctx || new AudioContextClass();
    playAdminAlertTone.ctx = ctx;

    if (ctx.state === 'suspended') {
        ctx.resume().catch(() => {});
    }

    const now = ctx.currentTime;
    const oscillator = ctx.createOscillator();
    const gain = ctx.createGain();

    oscillator.type = 'sine';
    oscillator.frequency.setValueAtTime(880, now);
    oscillator.frequency.setValueAtTime(1175, now + 0.12);
    gain.gain.setValueAtTime(0.0001, now);
    gain.gain.exponentialRampToValueAtTime(0.18, now + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.35);

    oscillator.connect(gain);
    gain.connect(ctx.destination);
    oscillator.start(now);
    oscillator.stop(now + 0.36);
}

document.addEventListener('alpine:init', () => {
    Alpine.data('adminAlerts', (config) => ({
        counts: {
            recharge_pending: Number(config.initial?.recharge_pending || 0),
            withdrawal_pending: Number(config.initial?.withdrawal_pending || 0),
            chat_unread: Number(config.initial?.chat_unread || 0),
        },
        pollUrl: config.pollUrl,
        pollTimer: null,
        audioReady: false,

        init() {
            const unlock = () => {
                this.audioReady = true;
                playAdminAlertTone.ctx =
                    playAdminAlertTone.ctx ||
                    new (window.AudioContext || window.webkitAudioContext)();
                if (playAdminAlertTone.ctx?.state === 'suspended') {
                    playAdminAlertTone.ctx.resume().catch(() => {});
                }
                window.removeEventListener('pointerdown', unlock);
                window.removeEventListener('keydown', unlock);
            };

            window.addEventListener('pointerdown', unlock, { once: true });
            window.addEventListener('keydown', unlock, { once: true });

            this.pollTimer = setInterval(() => this.poll(), 8000);
        },

        formatBadge(value) {
            const n = Number(value || 0);
            if (n <= 0) {
                return '';
            }

            return n > 99 ? '99+' : String(n);
        },

        async poll() {
            try {
                const res = await fetch(this.pollUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!res.ok) {
                    return;
                }

                const data = await res.json();
                const nextRecharge = Number(data.recharge_pending || 0);
                const nextWithdrawal = Number(data.withdrawal_pending || 0);
                const nextChat = Number(data.chat_unread || 0);
                const increased =
                    nextRecharge > this.counts.recharge_pending ||
                    nextWithdrawal > this.counts.withdrawal_pending ||
                    nextChat > this.counts.chat_unread;

                if (increased && this.audioReady) {
                    playAdminAlertTone();
                }

                this.counts.recharge_pending = nextRecharge;
                this.counts.withdrawal_pending = nextWithdrawal;
                this.counts.chat_unread = nextChat;
            } catch (_) {
                // ignore transient poll errors
            }
        },
    }));
});
