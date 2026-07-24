import { module } from 'modujs';
import gsap from "gsap/gsap-core";
import { ScrollTrigger } from 'gsap/ScrollTrigger';

export default class extends module {
    constructor(m) {
        super(m);
        this.reverse = false;
        this.direction = 1;
        this.duration = 40;

        this.handleMouseEnter = this.handleMouseEnter.bind(this);
        this.handleMouseLeave = this.handleMouseLeave.bind(this);
        this.handleTouchStart = this.handleTouchStart.bind(this);
        this.handleTouchEnd = this.handleTouchEnd.bind(this);
    }

    init() {
        this.movable = this.$('movable')[0];

        this.tl = gsap.timeline({
            repeat: -1,
            onReverseComplete() {
                this.totalTime(this.rawTime() + this.duration() * 10);
            }
        }).to(this.movable, {
            xPercent: this.reverse ? 100 : -100,
            duration: 20,
            ease: "none"
        }, 0);

        this.movable.addEventListener('mouseenter', this.handleMouseEnter);
        this.movable.addEventListener('mouseleave', this.handleMouseLeave);
        this.movable.addEventListener('touchstart', this.handleTouchStart, { passive: true });
        this.movable.addEventListener('touchend', this.handleTouchEnd);

        ScrollTrigger.create({
            scroller: this.modules.Scroll.main.el,
            onUpdate: (self) => {
                if (self.direction !== this.direction) {
                    this.direction = self.direction;
                    gsap.to(this.tl, { timeScale: this.direction, overwrite: true });
                }
            }
        });
        window.addEventListener("resize", this.onResize.bind(this));
    }

    handleMouseEnter() { this.tl.pause() }

    handleMouseLeave() {
        this.tl.play();
        gsap.to(this.tl, { timeScale: this.direction, overwrite: true });
    }

    handleTouchStart() { this.tl.pause() }

    handleTouchEnd() {
        this.tl.play();
        gsap.to(this.tl, { timeScale: this.direction, overwrite: true });
    }

    onResize() {
        let time = this.tl.totalTime();
        this.tl.totalTime(0);
        this.tl.totalTime(time);
    }
}
