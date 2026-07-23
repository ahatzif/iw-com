import { module } from 'modujs';
import Swiper from "swiper";

export default class extends module {
    constructor(m) {
        super(m);
        window.CookieDeclaration = undefined;
        this.checkReady();
    }

    checkReady(){

        let cookiebotIsNotLoaded = typeof window.CookieConsent === "undefined";
        let cookieDeclarationNotLoaded = typeof window.CookieDeclaration === 'undefined';

        if( cookiebotIsNotLoaded || cookieDeclarationNotLoaded ) this.checkLater();
        if( cookieDeclarationNotLoaded ) this.createScript();
        else this.onDeclarationLoad();
    }

    checkLater(){
        this.raf = window.requestAnimationFrame( () => this.checkReady() );
    }

    createScript(){
        if( this.script ) return;
        this.script = document.createElement('script');
        this.script.type = 'text/javascript';
        this.script.async = true;
        this.script.src = "https://consent.cookiebot.com/" + CookieConsent.serial + "/cd.js";
        this.script.id = 'CookieDeclaration';
        if( this.el.dataset.culture ) { this.script.dataset.culture = this.el.dataset.culture; }
        this.el.appendChild(this.script);
    }

    onDeclarationLoad(){
        setTimeout(  () => {
            this.createHeaders();
            this.wrapTables();
            this.removeClassNames();
            this.call('update', false, 'Scroll');
        }, 1000) ;
    }

    removeClassNames(){
        [...document.querySelectorAll('[class^="CookieDeclaration"]')].forEach( el => el.className = '' );
    }

    createHeaders(){
        [...document.querySelectorAll('[class^="CookieDeclarationTypeHeader"]')].forEach( el => {
            el.className = '';
            let h2 = document.createElement("h2");
            while (el.firstChild) h2.appendChild(el.firstChild);
            el.parentNode.replaceChild(h2, el);
        } );
    }

    wrapTables(){
        [...document.querySelectorAll('table')].forEach( (table,key) => {
            table.outerHTML = '<div data-module-overflow-scroll  class="swiper" id="ckt-' + key + '" ><div class="swiper-wrapper">' + table.outerHTML + '</div></div>';

        } );
        setTimeout( () => {
            [...document.querySelectorAll('table')].forEach( (table,key) => {
                table.classList.add( 'swiper-slide');
                table.style.minWidth = '700px';
                new Swiper( '#ckt-' + key , { slidesPerView: 'auto', freeMode: true, freeModeSticky: false, });
            } );
        }, 100 );
        this.call( 'update', this.el, 'app' );


    }

    destroy(){
        window.cancelAnimationFrame( this.raf );
    }

}
