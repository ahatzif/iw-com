import { module } from 'modujs';
import axios from 'axios';
import Emitter from "tiny-emitter/instance";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { submit: { 'form': 'onSubmit' }, input: { 'input': 'onInput' }, keydown: { 'input': 'onKeyDown' }, 'click' : { 'enter' : 'onEnter' }};
        this.form = this.$( 'form' )[0];
        this.select = this.el.querySelector( '[data-module-scrollbar]');
        this.input  = this.el.querySelector( 'input[name="keywords"]');
        this.suggestionsBox = this.$('suggestions')[0];
        this.keywords = this.$('keywords')[0];
        this.debounceTimer = null;
        this.input.addEventListener( 'focus', () => { this.onInput();});
        this.searchURL = this.$( 'form' )[0].getAttribute( 'action' );
        Emitter.on('click-outside', this.onBlur.bind( this ) );
        this.el.addEventListener( 'click', e => e.stopPropagation() );


        this.select.addEventListener( 'change', () => {
            this.onInput( true );
        });

    }

    onEnter(){
        window.location = this.searchURL + '?keywords=' + this.input.value;
    }

    onSubmit( e ){
        e.preventDefault();
    }

    onBlur( e ){
        this.el.classList.remove( 'open' );
        this.el.classList.remove( 'focus' );
        //this.clearSuggestions();
    }

    onInput(e) {
        this.el.classList.add( 'focus' );


        const query = this.input.value.trim();
        if (query.length === 0) {
            this.el.classList.remove( 'open' );
            this.clearSuggestions();
            return;
        } else {
            this.el.classList.add( 'open' );
        }

        if( ! e ) return;

        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.clearSuggestions();
            this.fetchSuggestions(query);
        }, 300);
    }

    fetchSuggestions(query) {
        if (this.abortController) {
            this.abortController.abort();
        }
        this.abortController = new AbortController();


        this.el.classList.add( 'loading' );
        this.el.classList.remove( 'no-results' );
        this.keywords.textContent = query;
        axios.post(THEME_OBJ.ajaxURL, new FormData( this.form ), { signal: this.abortController.signal }  ).then( response => {
            this.el.classList.remove( 'no-results' );
            this.el.classList.remove( 'loading' );
            if (response.data && response.data.results.length) {
                this.showSuggestions(response.data.results);
            } else {
                this.clearSuggestions();
                this.el.classList.add( 'no-results' );
            }
        })
        .catch((error) => {
            if (error.name === 'CanceledError') {
                //console.log('Previous request aborted:', error.message);
            } else {
                //console.error('Error fetching suggestions:', error);
            }
        });
    }


    showSuggestions(results) {
        this.activeIndex = -1;
        this.clearSuggestions();
        results.forEach((result) => {
            const suggestion = document.createElement('div');
            suggestion.classList.add( 'suggestion', 'border-b', 'border-current', 'last:border-none', 'text-current', 'transition', 'duration-500' );
            suggestion.innerHTML = this.suggestionHtml( result );
            this.suggestionsBox.appendChild(suggestion);
        });
    }

    suggestionHtml( result ){

        let photoHTML = '';
        if( ! result.taxonomy ){
            photoHTML= result.photo ? `<img alt="" class="object-contain rounded-[1rem] top-0 left-0 w-full h-full" src="${result.photo}">` : `<span class="absolute inset-0 flex items-center justify-center bg-white rounded-[1rem]" ><svg class="size-[30%] fill-current"><use xlink:href="#icon-no-image"></use></svg></span>` ;
            photoHTML = `<div class="h-100 w-100 relative shrink-0">${photoHTML}</div>`;
        }
        return `<a href="${result.link}" class="flex gap-40 py-15 px-25">
                    ${photoHTML}
                    <div class="flex justify-center flex-col leading-none">
                        <div class="text-H6 md:text-H5">${result.title}</div>
                        <div class="text-H7">${result.type}</div>
                    </div>
                </a>`
    }

    clearSuggestions() {
        this.suggestionsBox.innerHTML = '';
    }




    onKeyDown(e) {
        const suggestions = this.suggestionsBox.querySelectorAll('.suggestion');

        if (suggestions.length === 0) {
            if( e.key === 'Enter' ) {
                window.location = this.searchURL + '?keywords=' + this.input.value;
            } else {
                return;
            }
        };

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.activeIndex = (this.activeIndex + 1) % suggestions.length;
                this.highlightSuggestion(suggestions);
                break;

            case 'ArrowUp':
                e.preventDefault();
                this.activeIndex = (this.activeIndex - 1 + suggestions.length) % suggestions.length;
                this.highlightSuggestion(suggestions);
                break;

            case 'Enter':
                e.preventDefault();
                if (this.activeIndex >= 0) {
                    suggestions[this.activeIndex].querySelector( 'a' ).click();
                    this.onBlur();
                } else {
                    window.location = this.searchURL + '?keywords=' + this.input.value;
                }
                break;

            case 'Escape':
                this.clearSuggestions();
                break;
        }
    }

    highlightSuggestion(suggestions) {
        suggestions.forEach((suggestion, index) => {
            if (index === this.activeIndex) {
                suggestion.classList.add( 'bg-ochre-light'  );
                //suggestion.scrollIntoView({ block: 'nearest' }); // Ensure visibility
            } else {
                suggestion.classList.remove('bg-ochre-light' );
            }
        });
    }



}


