const LIMITS = { facebook: 63206, instagram: 2200, twitter: 280, linkedin: 3000 };

const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

/** X counts links as 23 and CJK / emoji as 2 characters. */
function xLength(t) {
    let n = 0;
    t = String(t ?? '').replace(/https?:\/\/\S+/g, () => { n += 23; return ''; });
    for (const ch of t) {
        const c = ch.codePointAt(0);
        n += (c <= 4351 || (c >= 8192 && c <= 8205) || (c >= 8208 && c <= 8223) || (c >= 8242 && c <= 8247)) ? 1 : 2;
    }
    return n;
}

/** Escape text and colour links, #hashtags and @mentions like the real networks do. */
function formatPost(text, color) {
    return escapeHtml(text).replace(
        /(https?:\/\/[^\s<]+)|(^|[\s(])([#@][\p{L}\p{N}_]+)/gu,
        (m, url, pre, tag) => url
            ? `<span style="color:${color}">${url}</span>`
            : `${pre}<span style="color:${color}">${tag}</span>`,
    );
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('composer', () => ({
        tab: null,
        dragging: false,
        progress: 0,

        init() {
            this.tab = this.$wire.selected[0] ?? 'facebook';
            this.$watch('$wire.selected', (list) => {
                if (!list.includes(this.tab)) this.tab = list[0] ?? 'facebook';
            });
        },
        text(p) {
            return (this.$wire.custom?.[p] || '').trim() || this.$wire.body || '';
        },
        html(p, color) {
            return formatPost(this.text(p), color);
        },
        length(p) {
            const t = this.text(p);
            return p === 'twitter' ? xLength(t) : [...t].length;
        },
        limit: (p) => LIMITS[p],
        over(p) {
            return this.length(p) > LIMITS[p];
        },
        ring(p) {
            return Math.min(100, (this.length(p) / LIMITS[p]) * 100);
        },
        drop(e) {
            this.dragging = false;
            const file = e.dataTransfer.files[0];
            if (file) this.$wire.upload('image', file);
        },
    }));

    // Publishes each channel one after another so every card updates live.
    window.Alpine.data('publisher', () => ({
        current: null,
        async run(ids) {
            for (const id of ids) {
                this.current = id;
                try { await this.$wire.publishTarget(id); } catch (e) { /* the card shows the error */ }
            }
            this.current = null;
            await this.$wire.finished();
        },
        async retry(id) {
            this.current = id;
            await this.$wire.retry(id);
            this.current = null;
        },
    }));
});
