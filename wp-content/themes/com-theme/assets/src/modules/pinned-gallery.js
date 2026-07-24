import { module } from 'modujs';
import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

export default class extends module {
  constructor(m) {
    super(m);
    this.events = {};
    this.currentIndex = 0;
    this.lastTargetIndex = 0;
  }

  init() {
    if (window.innerWidth < 1024) return;

    this.section = this.el;
    if (!this.section) return;

    this.header = document.querySelector('header');

    this.slides = Array.from(this.section.querySelectorAll('.slide'));
    this.total = this.slides.length;
    if (this.total <= 1) return;

    this.btnNext = this.section.querySelector('[data-arrow-next]');
    this.btnPrev = this.section.querySelector('[data-arrow-prev]');
    if (!this.btnNext || !this.btnPrev) return;

    this.createPin();
  }

    getHeaderHeight() {
    const header = document.querySelector('header');
    if (!header) return 0;
    return Math.round(header.getBoundingClientRect().height);
    }

  createPin() {
    this.stepPx = () => window.innerHeight;

    this.scrollTrigger = ScrollTrigger.create({
      scroller: this.modules?.Scroll?.main?.el || undefined,
      trigger: this.section,

        // ✅ pin κάτω από sticky header
        start: () => `top top+=${this.getHeaderHeight()}`,

      // ⬇️ προαιρετικά κάνε offset και στο end για να μην “τρως” χώρο
      end: () => `+=${(this.total - 1) * this.stepPx()}`,

      pin: true,
      pinSpacing: true,
      anticipatePin: 1,
      invalidateOnRefresh: true,
      pinType: 'transform',

      onRefresh: () => {
        this.lastTargetIndex = 0;
        this.currentIndex = 0;
      },

      onUpdate: (self) => {
        const targetIndex = Math.round(self.progress * (this.total - 1));
        if (targetIndex === this.lastTargetIndex) return;

        const diff = targetIndex - this.lastTargetIndex;
        if (diff > 0) for (let i = 0; i < diff; i++) this.btnNext.click();
        else for (let i = 0; i < Math.abs(diff); i++) this.btnPrev.click();

        this.lastTargetIndex = targetIndex;
      },
    });
  }

  destroy() {
    if (this.scrollTrigger) {
      this.scrollTrigger.kill();
      this.scrollTrigger = null;
    }
  }
}
