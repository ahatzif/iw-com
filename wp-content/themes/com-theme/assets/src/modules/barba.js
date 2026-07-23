import barba from "@barba/core";
import { module } from 'modujs';
import gsap from 'gsap/gsap-core';

export default class extends module {
    constructor(m) {
        let a;
        super(m);
        this.html = document.getElementsByTagName('html')[0];
        this.backScrollPositions = [];
        this.frontScrollPositions = [];

        this.backLoadedItems = [];
        this.frontLoadedItems = [];
        this.historyY = 0;
        this.loarMoreItems = [];
    }

    init() {
        barba.init({
            debug: true,
            timeout: 15000,
            transitions: [{
                name: 'opacity-transition',
                leave: data => {
                    this.html.classList.add('not-first-load');
                    this.html.classList.add('is-loading');
                    this.call('off', data.next.container, 'Cursor');
                },
                beforeLeave : ( data ) =>{
                    let y = this.modules.Scroll.main.y;

                    this.trigger = data.trigger;
                    let currentLoadMoreItems = [];
                    if( this.modules.LoadMore ){
                        Object.keys(this.modules.LoadMore).forEach( ( key) => {
                            let button = this.modules.LoadMore[ key ];
                            if( button.el.dataset.target ){
                                currentLoadMoreItems[ key ] = {
                                    'page' : button.page,
                                    'html' : button.target.innerHTML
                                };
                            }
                        });
                    }

                    if( this.trigger === 'back' ){
                        this.frontScrollPositions.push( y );
                        this.frontLoadedItems.push( currentLoadMoreItems );
                        this.historyY = this.backScrollPositions.pop( );
                        this.loarMoreItems = this.backLoadedItems.pop();
                    } else if ( this.trigger === 'forward' ){
                        this.backScrollPositions.push( y );
                        this.backLoadedItems.push( currentLoadMoreItems );
                        this.historyY = this.frontScrollPositions.pop( );
                        this.loarMoreItems = this.frontLoadedItems.pop();
                    } else {
                        this.frontScrollPositions = [];
                        this.backScrollPositions.push( y );
                        this.frontLoadedItems = [];
                        this.backLoadedItems.push( currentLoadMoreItems );
                        this.historyY = 0;
                        this.loarMoreItems = [];
                    }


                },
                beforeEnter: (data) => {
                    let match = /<body\s+class\s*=\s*["']([^"']*)["']/gi.exec(data.next.html);
                    if (match || match[1]) {
                        document.body.setAttribute('class', match[1]);
                    }
                    gsap.fromTo(data.next.container, { opacity: 0 }, { opacity: 1, duration: 1, ease: 'none' });
                },
                enter: data => {
                    this.html.classList.remove('is-loading');
                    this.updateNewElements( data.next.html );
                },
                after: data => {
                    this.call('destroy', data.current.container, 'app');
                    this.call('update', data.next.container, 'app');
                    Object.keys(this.loarMoreItems).forEach(key => {
                        this.call( 'setState', this.loarMoreItems[key], 'LoadMore'  );
                    });
                    if( this.historyY !== 0){
                        this.call( 'scrollTo', { target: this.historyY }, 'Scroll' );
                    }
                    this.call('init', false, 'PageHeader');
                    this.call('update', false, 'LenisScrollbar' );
                    //this.call( 'addHoverElements', data.next.container, 'Cursor' );
                }
            }],
            prevent: ({ el }) => {
                return /.pdf/.test(el.href.toLowerCase()) || /.doc/.test(el.href.toLowerCase()) || el.classList && el.classList.contains('ab-item');
            }
        });
    }
    updateNewElements( newHtml ){
        let nextDocument = new DOMParser().parseFromString( newHtml, "text/html" ).documentElement;
        let selectors = [ '#wpadminbar', '#lang-menu' ];
        selectors.forEach( selector => {
            let currentElement = document.querySelector( selector );
            if( currentElement ) {
                currentElement.innerHTML = nextDocument.querySelector( selector ).innerHTML;
            }
        })
    }
}

