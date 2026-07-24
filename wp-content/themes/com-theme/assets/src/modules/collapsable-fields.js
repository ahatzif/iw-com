import { module } from 'modujs';
const { slideToggle } = window.domSlider;

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'button': 'handleToggle' } };
        this.content = this.$('content')[0];
        this.rows = this.$('row');
    }
    handleToggle() {
        if (this.rows.length > 1) {
            this.rows.forEach((row, index) => {
                if (index === 0) return;
                const isHidden = row.classList.contains('hidden');
                if (isHidden) {
                    row.classList.remove('hidden');
                    row.style.height = '0px';
                    row.style.overflow = 'hidden';
                    const fullHeight = row.scrollHeight + 'px';
                    requestAnimationFrame(() => {
                        row.style.transition = 'height 300ms';
                        row.style.height = fullHeight;
                    });
                    row.addEventListener('transitionend', function te() {
                        row.style.height = '';
                        row.style.transition = '';
                        row.style.overflow = '';
                        row.removeEventListener('transitionend', te);
                    });
                } else {
                    row.style.height = row.scrollHeight + 'px';
                    row.style.overflow = 'hidden';
                    requestAnimationFrame(() => {
                        row.style.transition = 'height 300ms';
                        row.style.height = '0px';
                    });
                    row.addEventListener('transitionend', function te() {
                        row.classList.add('hidden');
                        row.style.height = '';
                        row.style.transition = '';
                        row.style.overflow = '';
                        row.removeEventListener('transitionend', te);
                    });
                }
            });
            this.el.classList.toggle('opened');
        } else {
            this.toggle();
        }
    }

    toggle() {
        this.el.classList.toggle('opened');
        slideToggle({ element: this.content, duration: 300 });
    }
}