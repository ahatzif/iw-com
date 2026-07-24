import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";

export default class extends module {
    constructor(m) {
        super(m);
        this.clickOutsideBind = this.clickOutside.bind(this);
        Emitter.on('click-outside', this.clickOutsideBind);
        this.bg = this.$('bg')[0];
        new ResizeObserver(() => { this.setHeight() }).observe(this.el);
        this.inited = false;
        this.defaultLabel = this.$('label')[0]?.innerText.trim();
    }
    init() {
        if (this.inited) return;
        this.inited = true;
        [...this.$('toggle')].forEach(t => t.addEventListener('click', this.toggle.bind(this)));
        [...this.$('expandChildren')].forEach(t => t.addEventListener('click', this.expandChildren.bind(this)));
        [...this.$('option')].forEach(t => t.addEventListener('click', this.optionClick.bind(this)));
        [...this.$('single-option')].forEach(t => {
            t.addEventListener('click', this.singleOptionClick.bind(this))

            if( t.classList.contains( 'active' ) ) {
                this.$('label')[0].textContent = t.dataset.title || t.innerText;
            }
        });
    }

    toggle(e) {
        this.el.classList.toggle('open');
        this.setHeight();
    }

    singleOptionClick(e) {
        const el = e.currentTarget;
        const type = el.dataset.type;
        const isActive = el.classList.contains('active');
        const isMultiselect = el.dataset.multiselect === 'true';
        let label = this.$('label')[0];

        if (!this.defaultLabel) this.defaultLabel = label?.innerText.trim(); // fallback, just in case

        if (type === 'radio') {
            if (isActive) {
                el.classList.remove('active');
                label.innerHTML = this.defaultLabel;
                this.toggle();
                return;
            }

            this.$('single-option').forEach(option => option.classList.remove('active'));
            el.classList.add('active');
            label.innerHTML = el.dataset.title || el.innerText;

        } else if (type === 'checkbox' && isMultiselect) {
            el.classList.toggle('active');
        } else if (type === 'checkbox' && !isMultiselect) {
            this.$('single-option').forEach(option => {
                if (option !== el) option.classList.remove('active');
            });

            if (isActive) {
                el.classList.remove('active');
                label.innerHTML = this.defaultLabel;
                this.toggle();
                return;
            } else {
                el.classList.add('active');
                label.innerHTML = el.dataset.title || el.innerText;
            }
        }

        this.toggle();
    }



    optionClick(e) {
        let el = e.currentTarget;
        el.classList.toggle('active');
        this.toggle();
    }

    clickOutside(ev) {
        if (ev.target === this.el || this.el.contains(ev.target)) {
            return;
        }
        this.el.classList.remove('open');
        this.setHeight();
    }

    setHeight() {
        let isOpen = this.el.classList.contains('open');
        this.bg.style.height = this.$('toggle')[0].getBoundingClientRect().height + (isOpen ? this.$('content')[0].getBoundingClientRect().height : 0) + 'px';
    }

    destroy() {
        Emitter.off('click-outside', this.clickOutsideBind);
    }

    expandChildren( e ){
        e.stopPropagation();
        let el = e.currentTarget;
        el.classList.toggle( 'expanded' );
        let options = this.el.querySelectorAll( `[data-parent-id="${el.dataset.id}"]` );
        [...options].forEach( option => {
            option.classList.toggle( 'opened' );
        })
    }
}