import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        
        // Binds για να μπορούμε να αφαιρούμε listeners στο destroy
        this.onRootClick = this.onRootClick.bind(this);
        this.updateHeaderHeight = this.updateHeaderHeight.bind(this);

        // refs
        this.pageHeader = null;
        this.headerHeight = 0;
    }
    
    init() {
        // Βρες το sticky header (αν υπάρχει)
        this.pageHeader = document.querySelector('[data-module-page-header]');
        this.updateHeaderHeight();

        // this.el.addEventListener( 'click', (e) => {
        //     e.preventDefault();
        //     this.scroll(e);
        // });

        // Listeners
        this.el.addEventListener('click', this.onRootClick);

        window.addEventListener('resize', this.updateHeaderHeight);

    }

    scroll(e) {
        e.preventDefault();

        const selector = e.currentTarget.dataset.target;
        const target = document.querySelector(selector);

        if (!target) return;

        target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    onRootClick(e) {

        // Βρες trigger με data-scroll-to μέσα στο module
        const trigger = e.target.closest('[data-scroll-to]');      

        if (!trigger || !this.el.contains(trigger)) return;
        // Αν είναι link/btn, κόψε το default
        e.preventDefault();

        const selector = trigger.dataset.target; // π.χ. "#section-3"

        if (!selector) return;

        this.scrollToSelector(selector, trigger.dataset.offset);

    }

    updateHeaderHeight() {
        // Αν δεν υπάρχει header, ύψος 0
        if (!this.pageHeader) {
            this.headerHeight = 0;
            return;
        }

        // offsetHeight = rendered ύψος (με padding/border)
        // Αν το header κάνει shrink, αυτό θα ακολουθεί δυναμικά
        this.headerHeight = this.pageHeader.offsetHeight || 0;
    }

    scrollToSelector(selector, extraOffset = '0') {

        const target = document.querySelector(selector);

        if (!target) return;

        // Ενημέρωσε latest ύψος πριν το scroll (γιατί μπορεί να άλλαξε)
        this.updateHeaderHeight();

        // Y θέση του target ως προς όλη τη σελίδα
        const targetTop = target.getBoundingClientRect().top + window.scrollY;
       

        // Επιπλέον offset από data-offset (π.χ. data-offset="16")
        const extra = parseInt(extraOffset, 10) || 0;

        // Τελικός στόχος: top - headerHeight - extra
        const finalY = Math.max(0, targetTop - this.headerHeight - extra);

        // εφαρμόζουμε offset για scrollIntoView
        target.style.scrollMarginTop = (this.headerHeight + extra) + 'px';

        target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
        
    }


    destroy(){
        this.el.removeEventListener('click', this.onRootClick);
        window.removeEventListener('resize', this.updateHeaderHeight);
    }
}

