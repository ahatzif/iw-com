import { module } from 'modujs';
import Ukiyo from "ukiyojs";
import axios from 'axios';

export default class extends module {
    constructor(m) {
        super(m);
        this.isMultiple = this.el.dataset.multiple === 'true';
        this.events = { click: { 'button': 'setFilters', 'pills': 'pillClick', 'clear': 'clear' } };
        this.target = document.querySelector(this.el.dataset.target);
        this.href = this.el.dataset.href;
        this.clearButton = this.$('clear')[0];
        this.pillsContainer = this.$('pills')[0];
        this.pillsContainerOuter = this.$('pills-outer')[0];

    }

    clear(e) {
        e.preventDefault();
        [...this.el.querySelectorAll('[data-load-more-filters="button"].active')].forEach(el => el.classList.remove('active'));
        this.clearButton.classList.add('active');
        if (this.pillsContainerOuter) this.pillsContainerOuter.classList.remove('active');
        this.createPills([]);
        this.target.classList.add('animate-loading-low');
        window.history.pushState({}, '', this.href);
        axios.get(this.href).then(response => this.updateDOM(response));
    }
    createPills(pills) {
        if (!this.pillsContainer) return;
        this.pillsContainer.innerHTML = '';
        pills.forEach(pill => {
            let pillDiv = document.createElement('div');
            pillDiv.innerHTML = `<div class="bg-ochre-light rounded-20 text-H8 md:text-H7 text-dark px-[1.7rem] py-[.8rem] flex items-center"><svg class="size-[.8rem] shrink-0 mr-10 fill-current cursor-pointer" data-id="${pill.id}" data-remove><use xlink:href="#icon-clear-filter"></use></svg>${pill.name}</div>`;
            this.pillsContainer.append(pillDiv);
        });
    }

    pillClick(e) {
        let target = this.el.querySelector('[data-load-more-filters="button"][data-id="' + e.target.dataset.id + '"].active')
        if (target) target.click();
    }

    setFilters(e) {
        e.preventDefault();
        let link = e.currentTarget;
        let newURL;
        if (!this.isMultiple) {
            link.classList.add('loading');
            this.el.classList.add('pointer-events-none');
            newURL = link.href;
        } else {
            this.target.classList.add('animate-loading-low');
            link.classList.toggle('active');
            this.slugs = [];
            this.pills = [];
            [...this.el.querySelectorAll('[data-load-more-filters="button"].active')].forEach(el => {
                this.slugs.push(el.dataset.slug);
                this.pills.push({ id: el.dataset.id, name: el.dataset.name })
            });
            this.clearButton.classList.toggle('active', this.slugs.length === 0);
            this.pillsContainerOuter.classList.toggle('active', this.slugs.length > 0);
            newURL = this.href;

            if (this.slugs.length > 0) {
                const filtersByTax = {};

                [...this.el.querySelectorAll('[data-load-more-filters="button"].active')].forEach(el => {
                    const taxonomy = el.dataset.taxonomy;
                    if (!filtersByTax[taxonomy]) filtersByTax[taxonomy] = [];
                    filtersByTax[taxonomy].push(el.dataset.slug);
                });

                Object.keys(filtersByTax).forEach(tax => newURL += tax + '/' + filtersByTax[tax].join(',') + '/');
            }


            this.createPills(this.pills);
        }
        window.history.pushState({ page: 'somePage' }, 'Title', newURL);
        axios.get(newURL).then(response => this.updateDOM(response, link));
    }
    updateDOM(response, link = false) {
        if (!this.isMultiple && link) {
            const activeLink = link.parentNode.querySelector('.active');
            if (activeLink) activeLink.classList.remove('active');
            link.classList.remove('loading');
            link.classList.add('active');
        }
        this.target.classList.remove('animate-loading-low');
        let doc = document.createElement("div");
        doc.innerHTML = response.data;
        doc.innerHTML = doc.querySelector(this.el.dataset.target).innerHTML;
        this.target.innerHTML = '';
        let newDivs = [];
        [...doc.querySelectorAll('& > div')].forEach(div => {
            div.classList.add('ajax-loaded');
            newDivs.push(div);
            this.target.append(div);
        });
        this.call('update', false, 'Scroll');
        this.call('updateLazy', false, 'Scroll');
        this.call('addParallaxImages', this.target.querySelectorAll(".ajax-loaded [data-parallax]"), 'Scroll');
        this.call('update', this.target, 'app');
        newDivs.forEach(div => div.classList.remove('ajax-loaded'));
        this.el.classList.remove('pointer-events-none');
    }
}
