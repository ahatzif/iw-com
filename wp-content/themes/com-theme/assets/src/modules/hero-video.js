import { module } from 'modujs';
import gsap from 'gsap';
import SplitType from 'split-type';

export default class extends module {
    constructor(m) {
        super(m);
        this.video = this.$('video')[0];
        this.html = document.getElementsByTagName( 'html' )[ 0 ];
        this.isFirstLoad = this.html.classList.contains( 'not-first-load' );

    }

    init() {

        let src = this.video.dataset.src;
        if( isMobile && this.video.dataset.mobileSrc ){
            src = this.video.dataset.mobileSrc;
        }
        if( src ) {
            let source= document.createElement( 'source' );
            this.video.appendChild( source );
            this.video.addEventListener( "loadedmetadata", this.onLoadedMetaData.bind( this ) );
            source.src = src;
            this.el.classList.add( 'is-loaded' );
        }

        /*if( ! this.isFirstLoad ){
            this.call( 'stop', false, 'Scroll' );
        }*/
    }

    onLoadedMetaData(){

        this.video.play();
    }

    destroy(){
    }

}
