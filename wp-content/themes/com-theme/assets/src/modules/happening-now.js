import { module } from 'modujs';
import Ukiyo from "ukiyojs";
import axios from 'axios';
import Emitter from "tiny-emitter/instance";

export default class extends module {
    constructor(m) {
        super(m);
        this.isMultiple = this.el.dataset.multiple === 'true';
        this.events = { click: { 'pill': 'pillClick' } };
        this.resultsSelector = this.el.dataset.target;
        this.resultsContainer = document.querySelector(this.resultsSelector);

        this.slugSeparator = this.el.dataset.slugSeparator ? this.el.dataset.slugSeparator : ',';

        this.href = this.el.dataset.href;
        this.addExternalPills();
        this.inited = false;
    }

    addExternalPills() {
        if ( this.resultsContainer && !this.el.contains(this.resultsContainer)) {
            [...this.resultsContainer.querySelectorAll('[data-happening-now="pill"]')].forEach(e => e.addEventListener('click', this.pillClick.bind(this)));
        }

    }

    toggleFilter(args){
        let option = this.el.querySelector(`[data-happening-now="update-filters"][data-name="${args[0]}"][data-value="${args[1]}"]`);
        if(option){
            option.click();
        }
    }

    init() {

        if( this.inited ) return;
        this.inited = true;

        this.filterElements = [...this.el.querySelectorAll('[data-happening-now="update-filters"]')];

        this.filterElements.forEach(el => {

            if (el.tagName === 'INPUT' && el.type === 'text') {
                el.addEventListener('keyup', e => {
                    if (e.key === 'Enter') {
                        setTimeout(() => this.updateFilters(el));
                    }
                });
            } else {
                el.addEventListener('click', e => {
                    setTimeout(() => this.updateFilters(el), 100);
                });
            }

        });

        this.butttonElements = [...this.el.querySelectorAll('[data-happening-now="update-filters-button"]')];
        this.butttonElements.forEach(el => {
            el.addEventListener('click', e => { setTimeout(() => this.updateFilters(), 100); });
        });


        this.scrollToElFromUrl();
    }

    updateSearchField( val ){
        this.searchField = this.el.querySelector( '[name="search"]' );
        this.searchField.value = val;
        this.updateFilters();
    }

    updateFilters(el) {
        if( el ){

            let isActive = el.classList.contains('active');

            [...document.querySelectorAll(`[data-module-call="HappeningNow,toggleFilter,${el.dataset.name},${el.dataset.value}"]`)].forEach(toggle => {
                toggle.classList.toggle('active', isActive);
            });


            if (!isActive && el.dataset.type === 'radio-button') {
                [...el.parentNode.querySelectorAll('.active[data-type="radio-button"]')].forEach(button => {
                    if (button !== el) button.classList.remove('active');
                });
                el.classList.add('active');
            } else if (el.dataset.type === 'button') {
                el.classList.toggle('active');
            }
        }



        this.slugs = [];

        this.filterElements.forEach(element => {
            if (element.dataset.name) {
                if (element.tagName === 'INPUT') {
                    if (element.type === 'checkbox') {
                        if (element.checked) {
                            this.slugs.push(element.dataset.name);
                        }
                    } else if (element.type === 'text' || element.type === 'button') {
                        if (element.value) {
                            this.slugs.push(element.dataset.name + '/' + element.value);
                        }
                    }
                } else {
                    if (element.classList.contains('active')) {
                        if (element.dataset.value) {
                            if (!this.slugs[element.dataset.name]) {
                                this.slugs[element.dataset.name] = [element.dataset.value];
                            } else {
                                this.slugs[element.dataset.name].push(element.dataset.value)
                            }
                        } else {
                            this.slugs.push(element.dataset.name);
                        }
                    }
                }
            }
        });

        let newURL = this.href;


        // ΜΤ: to evala ayto giati sta filtra ton proionton
        // an ebbgazes ola ta pills to newUrl emene "/products/antigrafa/filter" anti "/products/antigrafa"
        if(Object.keys(this.slugs).length === 0 && newURL.includes('/filter')){
            newURL = newURL.replace("/filter", "");
        }


        for (let key in this.slugs) {
            let element = this.slugs[key];
            if (typeof element === "string") {
                newURL += element + '/';
            } else {
                newURL += key + '/' + element.join(this.slugSeparator) + '/';
            }
        }
        this.call('show', false, 'PageLoading');
        this.el.classList.toggle('has-filters', Object.keys(this.slugs).length > 0);

        this.fetchResults(newURL);
    }

    fetchResults(newURL) {
        this.call('show', false, 'PageLoading');
        axios.get(newURL).then(response => this.placeResults(response, newURL));
    }

    placeResults(response, newURL) {


        this.call( 'addToHistoryOpts', {url : newURL, trigger : 'barba', action : 'push'}, 'Load' );

        let doc = document.createElement("div");
        doc.innerHTML = response.data;
        this.resultsContainer.innerHTML = doc.querySelector(this.resultsSelector).innerHTML;
        this.addExternalPills();
        //this.call('update', false, 'Scroll');
        this.call('updateLazy', false, 'Scroll');
        this.call('update', this.resultsContainer, 'app');
        this.call('hide', false, 'PageLoading');
        this.call('scrollTo', { target: this.el, options: { offset: -150 } }, 'Scroll');
    }

    pillClick(e) {
        let pill = e.currentTarget;
        let selector = `[data-happening-now="update-filters"][data-name="${pill.dataset.name}"]`;
        let valueSelector = pill.dataset.value ? `[data-value="${pill.dataset.value}"]` : '';
        let target;



        if (pill.dataset.type && (pill.dataset.type === 'input' || pill.dataset.type === 'clear-input')) {

            let items = this.el.querySelectorAll(selector);
            items.forEach( item => {
                target = item;
                target.value = '';
            });






            if (pill.dataset.type === 'input') { // for datepicker
                target.click();
                if (target.closest('[data-module-datepicker]')) {
                    this.call('refresh', false, 'Datepicker');
                    this.updateFilters();
                }
                return;
            } else {
                if( pill.dataset.type === 'clear-input' ){
                    Emitter.emit( 'happening-now-pill-click', pill );
                }
                this.updateFilters(pill);
            }
        } else {
            target = this.el.querySelector(selector + valueSelector);
        }


        if (!target) target = this.el.querySelector(selector);
        if (target) {
            pill.remove();
            target.click();
        }
    }


    scrollToElFromUrl(){
        const url = window.location.pathname;
        if (
            url.includes('/mathisi/audiences') ||
            url.includes('/mathisi/participant-type') ||
            url.includes('/mathisi/school-classes') ||
            url.includes('/mathisi/learning-category')
        ) {
            this.scrollToEl = document.querySelector('[data-scroll-to-el]');
            if (this.scrollToEl) {
                this.call('scrollTo',{target: this.el,options: { offset: -150 }},'Scroll');
            }
        }
    }
}
