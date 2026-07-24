import { module } from 'modujs';
import gsap from 'gsap'; // προτιμώ αυτό αντί για "gsap/gsap-core"
import { ScrollTrigger } from 'gsap/ScrollTrigger';

export default class extends module {
  constructor(m) {
    super(m);
    this.events = { click: { 'scroll-to': 'scroll' } };
  }

  init() {
    const SEGMENTS = parseInt(this.el?.dataset.segmentsCount, 10);
    const ZOOM_FROM = 1;
    const ZOOM_TO = 2;
    const R_END_MULTIPLIER = 1.7;
    const TEXT_GAP = 0;
    let rStart;
    let rEnd;
    let rEndFullScreen;

    gsap.registerPlugin(ScrollTrigger);

    const stage  = document.getElementById('stage');
    const maskEl = document.getElementById('mask');
    const layers = Array.from(maskEl.querySelectorAll('.img-layer'));
    const logo   = document.getElementById('logoSvg');
    const followEls = document.querySelectorAll('[data-txt-wrap]');

    const isDesktop = () => window.innerWidth >= 1024;

    const resetTextFollow = () => {
        followEls.forEach((el) => {
            el.style.left = '';
            el.style.width = '';
            el.style.right = '';
            el.style.height = '';
            el.style.top = '';
        });
    };

    // helper: γράφουμε τη --r ΚΑΙ στο stage (κληρονομείται) ΚΑΙ στη μάσκα (safety)
    const setR = (px) => {
      const v = Math.max(1, px | 0) + 'px';
      stage.style.setProperty('--r', v);
      maskEl.style.setProperty('--r', v);
    };

    const readLogoRadius = () => {
      const el = document.getElementById('logoSvg');
      if (!el) return 80;
      let h = el.getBoundingClientRect().height - 2;
      if (!h) {
        const cs = getComputedStyle(el);
        h = parseFloat(cs.height) || 160;
      }
      return Math.max(1, h / 2);
    };

    const computeEndRadius = () => {
      const w = window.innerWidth;
      const h = window.innerHeight;
      return Math.hypot(w, h) * 0.5 * R_END_MULTIPLIER;
    };


    const updateTextPosition = (rStartPx) => {
      const px = Math.max(0, (rStartPx | 0) + TEXT_GAP);

        resetTextFollow();
    
        followEls.forEach((el) => {

            if (isDesktop()) {
                el.style.left = `calc(50% + ${px}px)`;
                el.style.width = `calc(50% - ${px}px)`;
                el.style.height = `100%`;
            }else{
                el.style.top = `calc(50% + ${px}px)`;
                el.style.height = `calc(50% - ${px}px)`;
                el.style.width = `100%`;
            }   
        });
    };

    // αρχικό state layers
    gsap.set(layers,  { opacity: 0, filter: 'none', scale: ZOOM_FROM });
    if (layers[0]) gsap.set(layers[0], { opacity: 1 });

    // re-calc πριν από κάθε refresh του ScrollTrigger
    const recalc = () => {
        // rStart = 0;
        rStart = readLogoRadius();
        // rEnd   = computeEndRadius();
        rEnd   = readLogoRadius();
        rEndFullScreen = computeEndRadius();
        setR(rStart);
        updateTextPosition(readLogoRadius());
    };

    recalc();

    ScrollTrigger.addEventListener('refreshInit', recalc);

    // window resize -> recalc + refresh (σε επόμενο frame)
    let resizeRaf;
    window.addEventListener('resize', () => {
      cancelAnimationFrame(resizeRaf);
      resizeRaf = requestAnimationFrame(() => {
        recalc();
        ScrollTrigger.refresh();
      });
    });

    // αν αλλάζει dynamic το SVG ύψος
    if (window.ResizeObserver && logo) {
      const ro = new ResizeObserver(() => {
        recalc();
        ScrollTrigger.refresh();
      });
      ro.observe(logo);
    }

    const tl = gsap.timeline({
      defaults: { ease: 'none' },
      scrollTrigger: {
        scroller: this.modules?.Scroll?.main?.el || window,
        trigger: stage,
        start: 'top top',
        end: () => '+=' + (window.innerHeight * SEGMENTS),
        scrub: 1,
        pin: true,
        pinSpacing: true,
        pinReparent: true,
        anticipatePin: 1,
        markers: false,
        invalidateOnRefresh: true // << κρίσιμο
      }
    });

    for (let i = 0; i < SEGMENTS; i++) {
      const isLast  = i === SEGMENTS - 1;
      const current = layers[i % layers.length];
      const next    = layers[(i + 1) % layers.length];
      const label   = 'seg' + (i + 1);
      const third = ((i + 1) % 3 === 0);

      tl.addLabel(label);

      // zoom
      tl.fromTo(
        current,
        { scale: ZOOM_FROM },
        { scale: ZOOM_TO, duration: 2, immediateRender: false },
        label
      );

      // περιστροφή SVG όσο ανοίγει ο κύκλος
      /*
      tl.to(logo, {
        rotation: "+=360",
        duration: 1,
        ease: "none",
        transformOrigin: "50% 50%",
        svgOrigin: "192.5 192.5"
      }, label);
      */

      // ΑΝΤΙ για "to" με baked rStart: function-based fromTo για να παίρνει ΚΑΘΕ ΦΟΡΑ τα νέα rStart/rEnd
      const rProxy = { val: 0 };
      tl.fromTo(
        rProxy,
        { 
          val: () => rStart 
          // start value on-demand (τρέχον rStart)
        }, 
        {
          val: () => third ? rEndFullScreen : rEnd, 
          // end value on-demand (τρέχον rEnd)
          duration: 1,
          onUpdate: () => setR(rProxy.val)
        },
        label
      );

      if (!isLast) {
        const t = label + '+=0.95';
        // tl.to(current, { filter: 'blur(8px)', duration: 0.2 }, t);
        tl.set(current, { filter: 'none', scale: ZOOM_FROM });
        tl.set(next, { opacity: 1 });
        // reset ακτίνας για το επόμενο segment, με το ΤΡΕΧΟΝ rStart
        tl.add(() => setR(rStart));
      } else {
        // άφησέ την τελευταία ορατή μέχρι να “λυθεί” το pin
        // tl.set(layers[0], { opacity: 0 });
      }
    }

    window.dispatchEvent(new Event('resize'));

  }

  destroy() {}
}
