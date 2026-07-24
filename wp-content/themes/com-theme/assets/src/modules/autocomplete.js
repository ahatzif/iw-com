import { module } from 'modujs';
import axios from "axios";
import Emitter from "tiny-emitter/instance";

export default class extends module {
    constructor(m) {
        super(m);
        this.resultTemplate = this.$('result-template')[0];
        this.resultTemplate.remove();
        this.inputField = this.$( 'search-field' )[0];
        this.inputFieldSelection = this.el.querySelector('input[type="hidden"]');
        this.currentAbortController = null;
        this.messages = JSON.parse( this.el.dataset.messages );
        this.searchQuery = '';
        this.prevValue = '';
        this.inputField.addEventListener( 'keydown' , this.onKeyDown.bind( this ) );
        this.inputField.addEventListener( 'keyup' , this.onKeyUp.bind( this ) );
        this.inputField.addEventListener( 'focus' , this.onFocus.bind( this ) );
        this.inputField.addEventListener( 'blur' , this.onBlur.bind( this) );
        this.debounceTimer = null;
        this.scrollbar = this.el.querySelector( '[data-module-scrollbar]');

        this.searchUrl = this.el.dataset.searchUrl || '';

        this.form = this.el.closest('form');
        if (this.form) {
            this.form.addEventListener('submit', this.onSubmit.bind(this));
        }

    }

    onKeyUp( e ){


        const currentValue = this.inputField.value.trim();
        if (currentValue === this.prevValue) return;

        this.inputFieldSelection.value = '';

        this.prevValue = this.searchQuery = currentValue;

        if ( this.currentAbortController) {
            this.currentAbortController.abort();
        }
        if( ! this.hasSearchQuery() ) return;
        clearTimeout(this.debounceTimer);
        this.hideResults();
        this.el.classList.add('loading');
        this.debounceTimer = setTimeout(() => this.fetchSuggestions() , 300);

    }

    fetchSuggestions(){
        this.currentAbortController = new AbortController();
        const formData = new FormData();
        formData.append('search', this.searchQuery);
        formData.append('action', this.el.dataset.action);
        axios.post( THEME_OBJ.ajaxURL, formData, { signal: this.currentAbortController.signal } ).then( response => {
            let responseData = response.data;
            if (this.currentAbortController.signal.aborted) return;
            this.el.classList.remove('loading');
            this.resultsContainer.innerHTML = '';
            if (!responseData.success) {
                this.setResults( responseData.data.message );
            } else {
                this.activeIndex = -1;
                for (const [id, item] of Object.entries( responseData.data.results)) {
                    const clone = this.resultTemplate.cloneNode(true);
                    clone.dataset.id = item.id;
                    clone.dataset.name = item.name;
                    let html = clone.innerHTML.replaceAll( '{{name}}', item.name );
                    if( item.fields ){
                        for( let key in item.fields ){
                            let keyWithDashes = key.replace( '_', '-' ); // tailwind uses _ for space
                            html = html.replaceAll( `{{${keyWithDashes}}}`, item.fields[ key ] );
                            if( item.fields[ key ] !== ''){
                                clone.classList.add( 'has-field-' + keyWithDashes );
                            }
                        }
                    }
                    clone.innerHTML = html;
                    clone.addEventListener('click', this.resultClick.bind(this));
                    this.resultsContainer.appendChild(clone);
                }
            }
            this.showResults();
        });
    }

    hasSearchQuery(){

        if( this.searchQuery.length < 3 ){
            this.setResults( this.messages.minChars );
            return false;
        }
        return true;
    }



    setResults( text ){
        if( ! this.resultsContainer ){
            this.resultsContainer = this.el.querySelector('.scroll-content');
        }
        this.resultsContainer.innerHTML = text;
        this.el.classList.remove('loading');
        this.showResults();
    }

    showResults(){
        this.el.classList.add( 'show-autocomplete' );
    }

    hideResults( ){
        this.el.classList.remove( 'show-autocomplete' );
    }

    resultClick(e) {

        let link = e.currentTarget.querySelector( 'a');
        if(  link ){
            return;
        }

        e.preventDefault();
        e.stopPropagation();
        this.inputFieldSelection.value = e.currentTarget.dataset.id;
        this.suggestions = this.el.querySelectorAll('[data-autocomplete="result-template"]');
        this.suggestions.forEach((suggestion, index) => {
            suggestion.classList.remove( 'active', 'focus' );
        });
        e.currentTarget.classList.add( 'active', 'focus' );
        this.setFieldValue( e.currentTarget.dataset.name );
        this.hideResults();
    }

    setFieldValue( val ){
        this.prevValue = this.inputField.value = val;
    }



    onFocus(){
        if( this.hasSearchQuery() ) this.showResults();
    }

    onBlur( e ){
        if( ! this.el.contains( e.relatedTarget ) ) {
            this.hideResults();
        }
    }

    onKeyDown(e) {
        this.suggestions = this.el.querySelectorAll('[data-autocomplete="result-template"]');

        if (this.suggestions.length === 0) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.submitSearch();
            }
            return;
        }

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.activeIndex = (this.activeIndex + 1) % this.suggestions.length;
                this.highlightSuggestion();
                break;

            case 'ArrowUp':
                e.preventDefault();
                this.activeIndex = (this.activeIndex - 1 + this.suggestions.length) % this.suggestions.length;
                this.highlightSuggestion();
                break;

            case 'Enter':
                e.preventDefault();
                if (this.activeIndex >= 0) {
                    let link = this.suggestions[this.activeIndex].querySelector('a');
                    if (link) {
                        this.call('go', link.href, 'Load');
                    } else {
                        this.suggestions[this.activeIndex].click();
                    }
                } else {
                    this.submitSearch();
                }
                break;

            case 'Escape':
                e.preventDefault();
                this.clearSuggestions();
                break;
        }
    }

    onSubmit(e) {
        e.preventDefault();
        this.submitSearch();
    }

    submitSearch() {
        const value = this.inputField.value.trim();

        if (!value || !this.searchUrl) {
            this.inputField.focus();
            return;
        }

        const targetUrl = this.searchUrl.replace('$search', encodeURIComponent(value));
        this.call('go', targetUrl, 'Load');
    }

    clearSuggestions(){
        this.inputFieldSelection.value = '';
        this.setFieldValue( '' );
        this.inputField.blur();
        this.setResults( '' );
        this.hideResults();
    }

    highlightSuggestion() {
        this.suggestions.forEach((suggestion, index) => {
            if (index === this.activeIndex) {
                suggestion.classList.add( 'focus'  );
                Emitter.emit( 'scroll-to', { container : this.scrollbar, el: suggestion, speed : 1 })
            } else {
                suggestion.classList.remove('focus' );
            }
        });
    }
}
