import { module } from 'modujs';
import EmblaCarousel from 'embla-carousel';
import Fade from 'embla-carousel-fade';
import { isMobile } from 'mobile-device-detect';

export default class extends module {
    constructor(m) {
        super(m);

        const options = this.el.dataset.options
            ? JSON.parse(decodeURIComponent(this.el.dataset.options))
            : {};

        if (options.mobileOnly && window.innerWidth > 767) return;

        const {
            mobileOnly,
            fade,
            autoplay = false,
            autoplayStartDelay = 3000,
            autoplayDelay = 5000,
            ...emblaOptions
        } = options;

        this.options = {
            loop: true,
            align: 'start',
            dragFree: true,
            containScroll: false,
            ...emblaOptions,
        };
        this.plugins = fade ? [Fade()] : [];
        this.autoplayEnabled = autoplay;
        this.autoplayStartDelay = autoplayStartDelay;
        this.autoplayDelay = autoplayDelay;

        this.viewport = this.$('swiper')[0] || this.el.querySelector('[data-swiper]');
        this.container = this.viewport?.querySelector('.wrapper');
        if (!this.viewport || !this.container) return;

        this.touchStartBind = this.onTouchStart.bind(this);
        this.touchMoveBind = this.onTouchMove.bind(this);
        this.touchEndBind = this.onTouchEnd.bind(this);
        this.onSelectBind = this.onSlideChange.bind(this);
        this.addEvents();

        this.embla = EmblaCarousel(this.viewport, this.options, this.plugins);
        this.embla.on('select', this.onSelectBind);
        this.embla.on('reInit', this.onSelectBind);

        this.prevBtn = this.el.querySelector('[data-arrow-prev]');
        this.nextBtn = this.el.querySelector('[data-arrow-next]');
        this.onPrevBind = () => {
            this.embla.scrollPrev();
            this.restartAutoplay();
        };
        this.onNextBind = () => {
            this.embla.scrollNext();
            this.restartAutoplay();
        };
        this.prevBtn?.addEventListener('click', this.onPrevBind);
        this.nextBtn?.addEventListener('click', this.onNextBind);

        this.dots = [...this.el.querySelectorAll('[data-embla-carousel="dot"]')];
        this.captions = [...this.el.querySelectorAll('[data-embla-carousel="caption"]')];
        this.dotHandlers = this.dots.map((dot, index) => {
            const handler = () => {
                this.embla.scrollTo(index);
                this.restartAutoplay();
            };
            dot.addEventListener('click', handler);
            return handler;
        });

        this.onSlideChange();
        this.setupAutoplay();
    }

    addEvents() {
        if (isMobile && this.viewport) {
            this.viewport.addEventListener('touchstart', this.touchStartBind, { passive: true });
        }
    }

    onTouchStart(ev) {
        this.stopAutoplay();
        this.touchStartEv = ev.touches[0];
        this.viewport.addEventListener('touchmove', this.touchMoveBind, { passive: true });
    }

    onTouchMove(ev) {
        this.touchMoveEv = ev.touches[0];
        this.viewport.removeEventListener('touchmove', this.touchMoveBind);

        const dx = Math.abs(this.touchMoveEv.pageX - this.touchStartEv.pageX);
        const dy = Math.abs(this.touchMoveEv.pageY - this.touchStartEv.pageY);

        if (dx > dy) {
            this.call('stop', false, 'Scroll');
            this.viewport.addEventListener('touchend', this.touchEndBind, { passive: true });
            this.viewport.addEventListener('touchcancel', this.touchEndBind, { passive: true });
        }
    }

    onTouchEnd() {
        this.call('start', false, 'Scroll');
        this.viewport.removeEventListener('touchend', this.touchEndBind);
        this.viewport.removeEventListener('touchcancel', this.touchEndBind);
        this.restartAutoplay();
    }

    setupAutoplay() {
        if (!this.autoplayEnabled || !this.embla) return;

        this.onMouseEnterBind = () => this.stopAutoplay();
        this.onMouseLeaveBind = () => this.restartAutoplay();
        this.onFocusInBind = () => this.stopAutoplay();
        this.onFocusOutBind = () => this.restartAutoplay();
        this.onPointerDownBind = () => this.stopAutoplay();
        this.onPointerUpBind = () => this.restartAutoplay();

        this.el.addEventListener('mouseenter', this.onMouseEnterBind);
        this.el.addEventListener('mouseleave', this.onMouseLeaveBind);
        this.el.addEventListener('focusin', this.onFocusInBind);
        this.el.addEventListener('focusout', this.onFocusOutBind);
        this.viewport.addEventListener('pointerdown', this.onPointerDownBind, { passive: true });
        this.viewport.addEventListener('pointerup', this.onPointerUpBind, { passive: true });
        this.viewport.addEventListener('pointercancel', this.onPointerUpBind, { passive: true });

        this.scheduleAutoplay(this.autoplayStartDelay);
    }

    scheduleAutoplay(delay = this.autoplayDelay) {
        if (!this.autoplayEnabled || !this.embla) return;
        this.stopAutoplay();
        this.autoplayTimer = window.setTimeout(() => {
            this.embla.scrollNext();
            this.scheduleAutoplay(this.autoplayDelay);
        }, delay);
    }

    restartAutoplay() {
        this.scheduleAutoplay(this.autoplayDelay);
    }

    stopAutoplay() {
        if (!this.autoplayTimer) return;
        window.clearTimeout(this.autoplayTimer);
        this.autoplayTimer = null;
    }

    onSlideChange() {
        if (!this.embla) return;

        const activeIndex = this.embla.selectedScrollSnap();
        this.dots.forEach((dot, index) => {
            const isActive = index === activeIndex;
            dot.classList.toggle('active', isActive);
            dot.setAttribute('aria-current', isActive ? 'true' : 'false');
        });
        this.captions.forEach((caption, index) => {
            const isActive = index === activeIndex;
            caption.classList.toggle('active', isActive);
            caption.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });
    }

    destroy() {
        this.stopAutoplay();
        this.prevBtn?.removeEventListener('click', this.onPrevBind);
        this.nextBtn?.removeEventListener('click', this.onNextBind);
        this.dots?.forEach((dot, index) => dot.removeEventListener('click', this.dotHandlers[index]));
        this.viewport?.removeEventListener('touchstart', this.touchStartBind);
        this.viewport?.removeEventListener('touchmove', this.touchMoveBind);
        this.viewport?.removeEventListener('touchend', this.touchEndBind);
        this.viewport?.removeEventListener('touchcancel', this.touchEndBind);
        this.el?.removeEventListener('mouseenter', this.onMouseEnterBind);
        this.el?.removeEventListener('mouseleave', this.onMouseLeaveBind);
        this.el?.removeEventListener('focusin', this.onFocusInBind);
        this.el?.removeEventListener('focusout', this.onFocusOutBind);
        this.viewport?.removeEventListener('pointerdown', this.onPointerDownBind);
        this.viewport?.removeEventListener('pointerup', this.onPointerUpBind);
        this.viewport?.removeEventListener('pointercancel', this.onPointerUpBind);
        this.embla?.destroy();
    }
}
