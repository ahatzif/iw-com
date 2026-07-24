import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);

        this.events = { click: { link: 'activateFromClick' } };
        this.links = [...this.$('link')];
        this.sections = this.links.map(link => document.querySelector(link.dataset.selector));
        this.activeIndex = -1;
        this.lockedIndex = -1;
    }

    init() {
        if (!this.links.length || this.sections.some(section => !section)) return;

        if (this.modules.Scroll && this.modules.Scroll.main) {
            this.scrollModule = this.modules.Scroll.main;
            this.scrollModule.addScrollListener(this);
        } else {
            this.onWindowScroll = () => this.onScroll(window.scrollY);
            window.addEventListener('scroll', this.onWindowScroll, { passive: true });
        }

        this.onResize = () => this.onScroll();
        this.unlockFromUserScroll = () => {
            this.lockedIndex = -1;
        };
        this.onKeydown = event => {
            if (['ArrowDown', 'ArrowUp', 'End', 'Home', 'PageDown', 'PageUp', ' '].includes(event.key)) {
                this.unlockFromUserScroll();
            }
        };

        this.scrollInputElement = this.scrollModule ? this.scrollModule.el : window;
        this.scrollInputElement.addEventListener('wheel', this.unlockFromUserScroll, { passive: true });
        this.scrollInputElement.addEventListener('touchstart', this.unlockFromUserScroll, { passive: true });
        window.addEventListener('keydown', this.onKeydown);
        window.addEventListener('resize', this.onResize);
        this.onScroll();
    }

    activateFromClick(event) {
        this.lockedIndex = this.links.indexOf(event.currentTarget);
        this.setActive(this.lockedIndex);
    }

    onScroll() {
        if (this.lockedIndex >= 0) {
            this.setActive(this.lockedIndex);
            return;
        }

        const header = document.querySelector('header');
        const headerHeight = header ? header.offsetHeight : 0;
        const activationLine = headerHeight + (window.innerHeight - headerHeight) / 2;
        let nextActiveIndex = 0;

        this.sections.forEach((section, index) => {
            if (section.getBoundingClientRect().top <= activationLine) {
                nextActiveIndex = index;
            }
        });

        this.setActive(nextActiveIndex);
    }

    setActive(index) {
        if (index < 0 || index === this.activeIndex) return;

        this.links.forEach((link, linkIndex) => {
            const isActive = linkIndex === index;
            link.classList.toggle('active', isActive);

            if (isActive) {
                link.setAttribute('aria-current', 'location');
            } else {
                link.removeAttribute('aria-current');
            }
        });

        this.activeIndex = index;
    }

    destroy() {
        if (this.scrollModule && this.scrollModule.removeScrollListener) {
            this.scrollModule.removeScrollListener(this);
        }

        if (this.onWindowScroll) {
            window.removeEventListener('scroll', this.onWindowScroll);
        }

        if (this.onResize) {
            window.removeEventListener('resize', this.onResize);
        }

        if (this.scrollInputElement && this.unlockFromUserScroll) {
            this.scrollInputElement.removeEventListener('wheel', this.unlockFromUserScroll);
            this.scrollInputElement.removeEventListener('touchstart', this.unlockFromUserScroll);
        }

        if (this.onKeydown) {
            window.removeEventListener('keydown', this.onKeydown);
        }
    }
}
