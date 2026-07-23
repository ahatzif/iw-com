import { module } from 'modujs';
import axios from 'axios';
import Ukiyo from "ukiyojs";
import LazyLoad from "vanilla-lazyload";
import { Load } from "./_all.js";

export default class extends module {
    constructor(m) {
        super(m);
        this.target = document.querySelector( this.el.dataset.target );
        this.page = 1;
        this.maxNumPages = parseInt( this.el.dataset.maxNumPages );
        this.text = this.el.textContent;
        this.loadingText = this.el.dataset.loadingText;



        this.el.addEventListener( 'click', (e) => {
            e.preventDefault();
            this.el.parentNode.classList.add( 'loading' );
            this.el.classList.add( 'animate-loading' );
            this.el.textContent = this.loadingText;
            this.page++;
            let url = this.el.href.split("?")[0] + 'page/' + this.page + '/' + ( this.el.dataset.params || '');
            axios.get( url  ).then( (response) => {
                this.el.classList.remove( 'animate-loading' );

                const doc = document.createElement("div");
                doc.innerHTML = response.data;
                doc.innerHTML = doc.querySelector(this.el.dataset.target ).innerHTML;

                let newDivs = [];
                [...doc.querySelectorAll( '& > div' )].forEach( div => {
                    div.classList.add( 'ajax-loaded' );
                    newDivs.push( div );
                    this.target.append( div );
                } );



                this.el.parentNode.classList.remove( 'loading' );
                this.el.textContent = this.text;
                this.el.parentNode.classList.toggle( 'hidden', this.page === this.maxNumPages );
                this.call('update', false, 'Scroll');
                this.call('updateLazy', false, 'Scroll');
                this.call( 'addParallaxImages', this.target.querySelectorAll(".ajax-loaded [data-parallax]"), 'Scroll' );
                newDivs.forEach( div => div.classList.remove( 'ajax-loaded' ) ) ;
            });
        });
    }

    loadContents( href ){
        this.page = 0;
        this.el.href = href;
        this.el.click();
    }


    setState( state = { } ){
        if( state.page ){
            this.page = state.page;
        }
        if( state.html ){
            this.target.innerHTML = state.html;
            this.call('updateLazy', false, 'Scroll');
            this.call( 'addParallaxImages', this.target.querySelectorAll("[data-parallax]"), 'Scroll' );
            this.call('update', false, 'Scroll');
        }
    }

    init(){

    }

}
