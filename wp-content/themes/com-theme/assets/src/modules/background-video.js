import { module } from 'modujs';
import { TweenMax } from "gsap";

export default class extends module {
    constructor(m) {
        super(m);
        this.video = this.$('video')[0];
        this.setVideoSizeBind = this.setVideoSize.bind( this );
    }
    init() {
        let src = this.video.dataset.src;
        if( src ) {
            let source= document.createElement( 'source' );
            this.video.appendChild( source );
            this.video.addEventListener( "loadedmetadata", this.onLoadedMetaData.bind( this ) );
            source.src = src;
            this.el.classList.add( 'is-loaded' );
        }
    }
    onLoadedMetaData(){
        this.setVideoSize();
        window.addEventListener( 'resize', this.setVideoSizeBind );
        this.video.play();
    }
    setVideoSize(){
        this.videoSectionRatio = this.el.clientHeight / this.el.clientWidth;
        this.videoRatio = this.video.videoHeight / this.video.videoWidth;
        TweenMax.set( this.video, {
            width : this.videoSectionRatio > this.videoRatio ? this.el.clientHeight / this.videoRatio : this.el.clientHeight / this.videoSectionRatio,
            height: this.videoSectionRatio > this.videoRatio ? this.el.clientWidth * this.videoSectionRatio : this.el.clientWidth * this.videoRatio
        } );
        this.call( 'update', false, 'Scroll' );
    }
    destroy(){
        window.removeEventListener( 'resize', this.setVideoSizeBind );
    }
}
