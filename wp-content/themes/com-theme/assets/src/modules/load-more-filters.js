import { module } from 'modujs';
import Ukiyo from "ukiyojs";
import axios from 'axios';

export default class extends module {
    constructor(m) {
        super(m);
        this.isMultiple = this.el.dataset.multiple === 'true';
        this.events = { click: { 'clear': 'clear', 'button': 'setFilters', 'pills': 'pillClick' } };
        this.target = document.querySelector(this.el.dataset.target);
        this.href = this.el.dataset.href;
        this.clearButton = this.$('clear')[0];
        this.pillsContainer = this.$('pills')[0];
        this.pillsContainerOuter = this.$('pills-outer')[0];

        this.searchForm();

    }

    clear(e) {
        e.preventDefault();
        let link = e.currentTarget;
        [...this.el.querySelectorAll('[data-load-more-filters="button"].active')].forEach(el => {
            el.classList.remove('active');
        });
        this.clearButton.classList.add('active');
        this.pillsContainerOuter.classList.remove('active');
        this.createPills([]);
        this.target.classList.add('animate-loading-low');
        link.classList.add('loading');
        axios.get(this.href).then(response => this.updateDOM(response, link ));
        window.history.pushState({ page: 'somePage' }, 'Title', this.href);
    }

    setFilters(e) {
        e.preventDefault();
        let link = e.currentTarget;
        let newURL;
        link.classList.add('loading');
        if (!this.isMultiple) {
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
                newURL += this.slugs.join('/') + '/';
            }

            this.createPills(this.pills);
        }

        window.history.pushState({ page: 'somePage' }, 'Title', newURL);
        axios.get(newURL).then(response => this.updateDOM(response, link));
    }

    updateDOM(response, link = false) {
        link.classList.remove('loading');
        if (!this.isMultiple && link) {
            link.parentNode.querySelector('.active').classList.remove('active');
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

    createPills(pills) {
        this.pillsContainer.innerHTML = '';
        pills.forEach(pill => {
            let pillDiv = document.createElement('div');
            pillDiv.innerHTML = `<div class="mr-20"><span class="cursor-pointer" data-id="${pill.id}" data-remove>x</span> ${pill.name}</div>`
            this.pillsContainer.append(pillDiv);
        });
    }

    pillClick(e) {

        let target = this.el.querySelector('[data-load-more-filters="button"][data-id="' + e.target.dataset.id + '"].active')
        if (target) {
            target.click();
        }
    }


    searchForm(){

        [...this.el.querySelectorAll( '[data-search-text]' )].forEach( term => {
            this.searchTerms.push( term.dataset.searchText );
        });


        this.form = this.$( 'form' );
        if( this.form[0] ){
            this.form = this.form[0];
            this.searchField = this.form.querySelector( 'input[name="search"]' );


            this.form.addEventListener( 'submit', e => {
                e.preventDefault();
                this.searchTerms = this.searchField.value.split( ' ' );
                this.searchField.value = '';
                this.setFilters();
            });
        }
    }




}
