import barba from "@barba/core";
import { module } from 'modujs';
import gsap from 'gsap/gsap-core';


export default class extends module {
    constructor(m) {
        super(m);
        this.html = document.getElementsByTagName('html')[0];
    }

    init() {

        barba.init({
            debug: false,
            timeout: 15000,

            prefetchIgnore: true,
            transitions: [{
                name: 'opacity-transition',
                leave: data => {
                    this.html.classList.add('not-first-load');
                    this.html.classList.add('is-loading');
                    this.call('show', false, 'PageLoading');
                    this.call('off', data.next.container, 'Cursor');
                    this.hash = data.next.url.hash ? '#' + data.next.url.hash : false;

                },
                beforeEnter: data => {
                    let match = /<body\s+class\s*=\s*["']([^"']*)["']/gi.exec(data.next.html);
                    if (match && match[1]) {
                        document.body.setAttribute('class', match[1]);
                    }
                    gsap.fromTo(data.next.container, { opacity: 0 }, { opacity: 1, duration: 1, ease: 'none' });

                },
                enter: data => {
                    this.html.classList.remove('is-loading');
                    this.updateNewElements(data.next.html);
                    if (this.hash) {
                        requestAnimationFrame(() => {
                            const el = document.querySelector(this.hash);
                            this.hash = '';
                            if (el) {
                                this.call( 'scrollTo', { target: el, options: { offset: -document.querySelector( 'header' ).offsetHeight}  }, 'Scroll' );
                            }
                        });
                    }
                },
                after: data => {
                    this.call('destroy', data.current.container, 'app');
                    this.call('update', data.next.container, 'app');
                    this.call('close', false, 'ItemPreview');
                    this.call('init', false, 'PageHeader');
                    this.call('hide', false, 'PageLoading');
                }
            }],
            prevent: ({ el }) => {

                if (el.href.includes('/cart') || el.href.includes('/checkout' ) ) return true;

                let prevent = false;
                window.barbaPreventPages.some( page => {
                    prevent = el.href.includes(page);
                    return prevent;
                });
                if( prevent ) return true;

                return /.pdf/.test(el.href.toLowerCase()) || /.doc/.test(el.href.toLowerCase()) || el.classList && el.classList.contains('ab-item');
            }
        });
    }
    updateNewElements(newHtml) {
        let nextDocument = new DOMParser().parseFromString(newHtml, "text/html").documentElement;
        let selectors = ['#wpadminbar', '.lang-menu', '#page-border', '[data-module-page-loading]', '[data-module-page-header]', '.burger-user-menu'];
        selectors.forEach(selector => {
            let currentElement = document.querySelector(selector);
            let nextElement = nextDocument.querySelector(selector);
            if (currentElement && nextElement) {
                currentElement.innerHTML = nextElement.innerHTML;
                if (selector === '[data-module-page-header]') {
                    currentElement.className = nextElement.className;
                }
            }
        });
        this.call('update', document.querySelector('[data-module-page-header]'), 'app');
    }

    addToHistory(newURL) {
        barba.history.add(newURL,  'barba', 'push' );
    }

    addToHistoryOpts(args = { url : '', trigger : 'barba', action : 'push' } ) {
        barba.history.add(args.url, args.target, args.action );
    }

    go( href ){
        barba.go( href );
    }
}
