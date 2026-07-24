import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { button: 'toggle', close: 'close' } };
        this.count = this.$('count')?.[0] ?? null;
        this.button = this.$('button')?.[0] ?? null;
        this.onEscapeKeyBind = this.onEscapeKey.bind(this);
    }

    toggle(event) {
        event.preventDefault();
        const shouldOpen = !this.el.classList.contains('active');
        this.el.classList.toggle('active', shouldOpen);
        this.setExpanded(shouldOpen);
    }

    show() {
        this.el.classList.add('active');
        this.setExpanded(true);
    }

    close() {
        this.el.classList.remove('active');
        this.setExpanded(false);
    }

    setExpanded(expanded) {
        this.button?.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        window.removeEventListener('keydown', this.onEscapeKeyBind);
        if (expanded) {
            window.addEventListener('keydown', this.onEscapeKeyBind);
        }
    }

    updateCount(value) {
        if (!this.count) return;
        const count = Number(typeof value === 'object' ? value.count : value) || 0;
        const label = typeof value === 'object' && value.label
            ? value.label
            : `${count} ${count === 1 ? 'εισιτήριο' : 'εισιτήρια'}`;
        this.count.textContent = count > 0 ? String(count) : '';
        this.count.classList.toggle('hidden', count === 0);
        this.count.classList.toggle('flex', count > 0);
        this.button?.setAttribute('aria-label', `Καλάθι, ${label}`);
    }

    onEscapeKey(event) {
        if (event.key === 'Escape') {
            this.close();
            this.button?.focus();
        }
    }

    destroy() {
        window.removeEventListener('keydown', this.onEscapeKeyBind);
    }
}
