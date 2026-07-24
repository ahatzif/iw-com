import { module } from 'modujs';
import axios from "axios";
import {capitalizeFirstLetter} from "normalize-text";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'build-filters' : 'buildFilters', 'pills-container' : 'filterRemoveClick', 'clear-form' : 'clearForm', 'clear-group' : 'clearGroup' }, };
        this.extraUrlParams = [ 'year-from-era', 'year-to-era', 'list-view' ];
        this.construct();
        this.openPopup = false;
    }

    setFilterPopup( name ){
        this.openPopup = name;
    }

    construct() {

        this.groups = [ ... this.$( 'filter-group' ) ] ;
        [...this.el.querySelectorAll( 'form' )].forEach( form => form.addEventListener( 'submit', this.onFormSubmit.bind( this ) ) );
        [...this.el.querySelectorAll( '[data-collections-advanced-search="filter"]' )].forEach( el => el.addEventListener( 'click', this.filterClick.bind( this ) ) );

        this.inputFilters = [];
        this.valueFilters = {};
        this.addInputFilters( this.el.querySelectorAll( 'input[type="text"]' ) );
        this.pillContainer = this.$( 'pills-container' )[0];
        this.resultsFoundContainer = this.$( 'results-found' )[0];
        this.inited = false;
        this.target = document.querySelector( this.el.dataset.target );
    }

    init(){
        if( this.inited ) return;
        this.inited = true;
        this.call( 'addFilter', this.valueFilters, 'FilterModifier' );
        this.buildFilters( true );
    }

    clearGroup( e ){
        let el = e.currentTarget;
        let group = el.closest( '[data-collections-advanced-search="filter-group"]');
        [...group.querySelectorAll( '[data-filter] ')].forEach( el => el.classList.remove( 'active' ) );
        this.buildFilters(  );
    }

    clearForm( e ){
        let el = e.currentTarget;
        let form = el.closest( '[data-collections-advanced-search="form"]');
        let inputs = form.querySelectorAll( 'input[type="text"]');
        [...inputs].forEach( input => input.value = '' );

        this.addInputFilters( inputs );
        this.buildFilters();
    }
    filterClick( e ){

        let el = e.currentTarget;
        let group = el.closest( '[data-collections-advanced-search="filter-group"]');

        if (el.dataset.toggle === "1") {
            let isActive = group.classList.contains( 'active' );
            group.querySelector( `[data-filter-id="${isActive ? 0 : 1 }"]`).click();
            return;
        }

        if (group.dataset.type === 'radio') {
            group.classList.toggle( 'active', el.dataset.filterId === "1" );
            let active = group.querySelector( '[data-filter].active' );
            if( active ){
                active.classList.remove( 'active' );
            }
            if( el !== active ){
                el.classList.add( 'active' );
            }
        } else {
            el.classList.toggle( 'active' );
        }

        this.buildFilters(  );
    }

    chooseRightElement( group ){
        let el = null;
        if(window.innerWidth >= 1280 ){
            el = group.querySelectorAll( '[data-filter-desktop].active' );
        } else {
            el = group.querySelectorAll( '[data-filter-mobile].active' );
        }
        return el;
    }

    buildFilters( pillOnly = false ) {

        let queryString = [];
        let pills = [];

        Object.keys(this.inputFilters).forEach( key => {
            let input = this.inputFilters[ key ];

            let labelAddon = this.el.querySelector( `.active[data-${key}-label]`);
            if( labelAddon ){
                input.value += ' ' +labelAddon.getAttribute( `data-${key}-label` );
            }

            pills[key] = { label: input.label, items : input.value };

            queryString.push( `${encodeURIComponent(key)}=${encodeURIComponent( input.value )}` )
        });

        this.groups.forEach( group => {
            if( group.dataset.name ) {
                let activeFilters = [];
                let activeFiltersObj = [];
                let el = this.chooseRightElement(group);
                let isXl = window.innerWidth >= 1280;

                [...group.querySelectorAll( '[data-filter].active' )].forEach( filter => {
                    if( ! filter.offsetParent ) return;
                    if( filter.dataset.filterId && filter.dataset.filterId !== '0' ){
                        activeFilters.push( filter.dataset.filterId );
                        activeFiltersObj.push( { id: filter.dataset.filterId, label : filter.dataset.filterLabel } );
                    }
                });

               if ( activeFilters.length ) {
                   queryString.push( `${encodeURIComponent(group.dataset.name)}=${encodeURIComponent( activeFilters.join( '_' ) )}` )
                   pills[group.dataset.name] = { label: group.querySelector( '[data-label]').innerHTML, items: activeFiltersObj, type : group.dataset.type };
               }
            }
        });


        this.buildPills(pills);

        if( pillOnly ){
            return;
        }

        const urlParams = new URLSearchParams(window.location.search);
        this.extraUrlParams.forEach( param => {
            let value = urlParams.get( param );
            if( value ){
                queryString.push( `${encodeURIComponent(param )}=${encodeURIComponent( value )}` );
            }
        });

        queryString = queryString.join( '&' );
        const cleanedPath = window.location.pathname.replace(/\/page\/\d+\//, '/');
        const newURL = `${window.location.origin}${cleanedPath}?${queryString}`;
        this.call( 'show', false, 'PageLoading' );

        window.history.pushState({ path: newURL }, '', newURL);
        axios.get(newURL).then(response => this.updateDOM(response ));

    }
    updateDOM(response, link = false) {
        this.call( 'hide', false, 'PageLoading' );
        let doc = document.createElement("div");
        doc.innerHTML = response.data;
        this.el.innerHTML = doc.querySelector( "[data-module-collections-advanced-search]" ) .innerHTML;
        this.call('updateLazy', false, 'Scroll');

        this.construct();
        this.init();
        this.call('update', this.el, 'app' );

        this.el.classList.remove('pointer-events-none');

        if( this.openPopup ){
            let openPopup = this.el.querySelector( `[data-name="${this.openPopup}"] [data-filter-popup="toggle"]`);
            if( openPopup ) openPopup.click();

        }
    }

    setSort( args ){
        this.sort = args[0];
        this.buildFilters();
    }

    onFormSubmit( e ) {
        e.preventDefault();
        this.addInputFilters( e.target.querySelectorAll( 'input[type="text"]') );
        this.buildFilters();
    }

    addInputFilters( inputs ){
        [ ...inputs ].forEach( input => {
            if( input.value ){
                this.inputFilters[ input.name ] = { value: input.value, label: input.dataset.label || input.placeholder };
            } else if( this.inputFilters[ input.name ] ) {
                delete this.inputFilters[ input.name ];
            }
        });
    }

    buildPills( pills ){
        this.pillContainer.innerHTML = '';
        this.pillContainerContents = '';
        Object.keys(pills).forEach( name => {
            let pillGroup = pills[ name ];
            this.pillContainerContents += this.pillGroupTemplate( pillGroup, name );
        });

        this.pillContainer.innerHTML = this.pillContainerContents;
        this.resultsFoundContainer.querySelector('.peer').classList.toggle( 'active', this.pillContainerContents !== '' );

        this.el.querySelectorAll( '[data-active-on-results]').forEach( e => e.classList.toggle( 'active', this.pillContainerContents !== '' ) );

    }

    pillGroupTemplate( pillGroup, name ){
        let pills = '';

        if ( typeof pillGroup.items === 'object' ) {
            Object.keys(pillGroup.items).forEach( key => {
               pills += this.pillTemplate( pillGroup.items[key].id, pillGroup.type === 'radio' ? pillGroup.label + ` <strong>${pillGroup.items[key].label}</strong>` : pillGroup.items[key].label, name );
            });

        } else {
            pills = this.pillTemplate( '', pillGroup.items, name );
        }
        return `<div class="inline-block [&_.terms-count]:hidden mr-30 mb-10">
            <div class="hidden inline-flex font-bold border-b border-transparent h-[2.5rem] text-H8 md:text-H7 text-dark items-center cursor-pointer shrink-0 mr-10 align-bottom">${pillGroup.label}: </div>
            ${pills}
        </div>`;
    }


    pillTemplate(id, label, name = '') {
        return `<div class="pill mb-10 inline-flex items-center white-space-nowrap rounded-full bg-ochre-light h-[3.2rem] text-H8 md:text-H7 text-dark cursor-pointer shrink-0 [&:not(:last-child)]:mr-10 py-[0.8rem] px-[1.7rem] gap-10" data-filter-remove data-name="${name}" data-id="${id}">
            <svg class="inline-block size-10 fill-current"><use xlink:href="#icon-clear-filter"></use></svg>
            <div class="inline-block">${label}</div>
        </div>`;
    }

    filterRemoveClick(e) {
        let removeFilter = e.target.closest( '[data-filter-remove]' );
        if( removeFilter ){
            let id = removeFilter.dataset.id;
            let name = removeFilter.dataset.name;
            if( id ){
                let filterName = name ? `[data-name="${name}"] ` : '';
                let filter = this.el.querySelectorAll(`${filterName}[data-filter-id="${id}"]`);
                filter.forEach( f => {
                    if( ! f.offsetParent ) return;
                    if( f ) f.click();
                });

            } else if( name ) {
                let filter = this.el.querySelector(`input[type="text"][name="${name}"]`);
                if( filter ){
                    filter.value = '';
                    filter.closest( 'form' ).dispatchEvent( new Event('submit', { cancelable: true }) );
                }
            }
        }
    }

    addValueFilter(filterName, callback) {
        if (!this.valueFilters[filterName]) this.valueFilters[filterName] = [];

        this.valueFilters[filterName].push(callback);
    }

    applyValueFilters(filterName, value, ...args ) {
        if (!this.valueFilters[filterName]) return value;

        return this.valueFilters[filterName].reduce((currentValue, callback) => {
            return callback(currentValue, ...args);
        }, value);
    }
}
