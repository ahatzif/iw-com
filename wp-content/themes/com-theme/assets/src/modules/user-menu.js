import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);

        this.button = this.$('button')[0];
        this.panel = this.$('panel')[0];
        this.isOpen = false;

        this.toggleMenu = this.toggleMenu.bind(this);
        this.onDocumentClick = this.onDocumentClick.bind(this);
        this.onKeydown = this.onKeydown.bind(this);

        this.button?.addEventListener('click', this.toggleMenu);
        document.addEventListener('click', this.onDocumentClick);
        document.addEventListener('keydown', this.onKeydown);

    }

    init() {
        this.call('addScrollListener', this, 'Scroll');
    }

    setOpen(isOpen) {
        this.isOpen = isOpen;
        this.el.classList.toggle('active', isOpen);
        this.button?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        this.panel?.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    }

    toggleMenu(event) {
        event.preventDefault();
        event.stopPropagation();
        this.setOpen(!this.isOpen);
    }

    onDocumentClick(event) {
        if (this.isOpen && !this.el.contains(event.target)) {
            this.setOpen(false);
        }
    }

    onKeydown(event) {
        if (event.key !== 'Escape' || !this.isOpen) return;

        this.setOpen(false);
        this.button?.focus({ preventScroll: true });
    }

    onScroll() {
        if (this.isOpen) {
            this.setOpen(false);
        }
    }

    destroy() {
        this.button?.removeEventListener('click', this.toggleMenu);
        document.removeEventListener('click', this.onDocumentClick);
        document.removeEventListener('keydown', this.onKeydown);
        this.call('removeScrollListener', this, 'Scroll');
    }

}
