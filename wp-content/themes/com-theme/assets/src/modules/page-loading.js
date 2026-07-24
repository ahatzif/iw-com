import { module } from 'modujs';
const BAR_MIN_VISIBLE_DURATION = 650;

export default class extends module {
    constructor(m) {
        super(m);
        this.html = document.documentElement;
        this.hideTimer = null;
        this.showTime = 0;
    }
    show() {
        window.clearTimeout(this.hideTimer);
        this.hideTimer = null;
        this.showTime = window.performance.now();
        this.el.classList.add( 'loading' );
        this.html.classList.add('page-loading-is-active');
    }
    hide() {
        const elapsed = window.performance.now() - this.showTime;

        if (this.el.classList.contains('loading') && elapsed < BAR_MIN_VISIBLE_DURATION) {
            window.clearTimeout(this.hideTimer);
            this.hideTimer = window.setTimeout(() => this.hide(), BAR_MIN_VISIBLE_DURATION - elapsed);
            return;
        }

        this.hideTimer = null;
        this.el.classList.remove( 'loading' );
        this.html.classList.remove('page-loading-is-active');
    }
}
