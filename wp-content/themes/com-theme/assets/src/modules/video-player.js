import { module } from 'modujs';
import { isMobile } from "mobile-device-detect";
import {gsap} from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Draggable from "gsap/Draggable";
let Emitter = require('tiny-emitter/instance');

gsap.registerPlugin(Draggable);





export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'toggle-play': 'togglePlay', 'full-screen' : 'fullScreen', 'volume' : 'toggleVolume', 'set-current-time': 'setCurrentTime' } };

        this.video = this.$('video')[0];
        this.isPlaying = this.video.autoplay;
        this.progressHandle = this.$('progress-handle')[0];
        this.progressBar = this.$('progress-bar')[0];
        this.time = this.$('time')[0];
        let that = this;
        this.fillProgressBarBind = this.fillProgressBar.bind( this );
        this.onResizeBind = this.onResize.bind( this );

        this.moveTimeout = null;
        this.resizeTimeout = null;

        this.el.addEventListener( 'mouseenter', this.mouseMove.bind( this ) );
        this.el.addEventListener( 'mousemove', this.mouseMove.bind( this ) );
        this.el.addEventListener( 'touchstart', this.mouseMove.bind( this ) );
        this.el.addEventListener( 'touchmove', this.mouseMove.bind( this ) );

        this.el.addEventListener( 'mouseleave', this.mouseLeave.bind( this ) );
        this.el.addEventListener( 'mouseleave', this.mouseLeave.bind( this ) );


        if( this.progressHandle ){

            Draggable.create( this.progressHandle, {
                type: "x",
                bounds: this.progressHandle.parentNode,
                onDragStart : function() {
                    that.video.removeEventListener( 'timeupdate', this.fillProgressBarBind );
                    if( that.isPlaying ) that.video.pause();
                },
                onDrag : function() {
                    that.video.currentTime = that.video.duration * this.endX / this.maxX;
                    that.setProgress();
                },
                onDragEnd : function ( ){
                    that.video.addEventListener( 'timeupdate', this.fillProgressBarBind );
                    if( that.isPlaying ) that.video.play();
                }
            } );

            this.draggable = Draggable.get( this.progressHandle );

            this.video.addEventListener( 'timeupdate', this.fillProgressBarBind );
            window.addEventListener( 'resize', this.onResizeBind );

        }



        this.video.addEventListener( 'ended', this.videoEnded.bind( this )  );
        this.onPauseAllVideosBind = this.onPauseAllVideos.bind( this );
        Emitter.on('pause-all-videos', this.onPauseAllVideosBind );



    }

    setProgress(){
        let progress = this.video.currentTime / this.video.duration;
        gsap.set( this.progressBar, { x: progress * 100 + '%' } );
    }

    mouseMove(){
        if( this.moveTimeout ) clearTimeout( this.moveTimeout );
        this.el.classList.add( 'pointer-on' );
        this.moveTimeout = setTimeout( this.mouseLeave.bind( this ) , 3000 );
    }

    mouseLeave(){
        if( this.isPlaying ){
            this.el.classList.remove( 'pointer-on' );
        }

    }

    onResize(){
        if( this.resizeTimeout ) clearTimeout( this.resizeTimeout );
        this.resizeTimeout = setTimeout( ()=>{
            this.draggable.applyBounds();
            this.fillProgressBar();
            console.info('+');
        },100)

    }


    toggleVolume(){
        this.video.volume = this.video.volume === 1 ? 0 : 1;
        this.el.classList.toggle( 'volume-on', this.video.volume === 1);
    }

    init(){

        this.scrollTrigger = ScrollTrigger.create({
            scroller: this.modules.Scroll.main.el,
            trigger: this.video,
            start: "bottom 25%",
            end: "bottom 25%",
            onEnter: () => {
                if( this.isPlaying ){
                    this.togglePlay(); // pause on exit up
                }
            },
            onLeaveBack: () => {
                if( this.video.dataset.autoplay ){
                    this.togglePlay();
                }
            },
        });

        this.scrollTrigger = ScrollTrigger.create({

            scroller: this.modules.Scroll.main.el,
            trigger: this.video,
            start: "top bottom-=25%",
            bottom: "top bottom-=25%",
            onEnter: () => {
                if( this.video.dataset.autoplay ){
                    this.togglePlay();
                }
            },
            onLeaveBack: () => {
                if( this.isPlaying ){
                    this.togglePlay();  // pause on exit down
                }
            },
        });
    }


    togglePlay() {
        this.el.classList.add( 'remove-cover' );
        this.mouseMove();
        this.isPlaying = ! this.isPlaying;
        this.isPlaying ? this.video.play() : this.video.pause();
        this.el.classList.toggle('playing', this.isPlaying );
        this.el.classList.toggle('paused', !this.isPlaying );
        this.isPlaying ? Emitter.emit('pause-all-videos', this.video ) : false;
    }


    onPauseAllVideos( video ){
        video !== this.video && this.isPlaying && this.togglePlay();
    }



    setCurrentTime( e ){
        let box = e.currentTarget.getBoundingClientRect();
        this.video.currentTime = this.video.duration * ( e.clientX - box.x ) / box.width;
        this.fillProgressBar();
    }

    fillProgressBar(){
        if( this.video.currentTime &&  this.video.duration ){
            this.time.textContent = this.formatSeconds( this.video.currentTime ) + ' / ' + this.formatSeconds(this.video.duration)
        }
        this.progress = this.video.currentTime / this.video.duration;
        gsap.set( this.progressHandle, { x: this.progress * this.draggable.maxX } );
        gsap.set( this.progressBar, { x: this.progress * 100 + '%' } );
        this.draggable.update();
    }

    formatSeconds(seconds) {
        let minutes = Math.floor((seconds % 3600) / 60).toString().padStart(2, '0');
        let remainingSeconds = Math.ceil(seconds % 60).toString().padStart(2, '0');
        return minutes + " : " + remainingSeconds;
    }

    fullScreen(){
        if (!document.fullscreenElement) {
            if (this.el.requestFullscreen) {
                this.el.requestFullscreen();
            } else if (this.el.mozRequestFullScreen) { /* Firefox */
                this.el.mozRequestFullScreen();
            } else if (this.el.webkitRequestFullscreen) { /* Chrome, Safari & Opera */
                this.el.webkitRequestFullscreen();
            } else if (this.el.msRequestFullscreen) { /* IE/Edge */
                this.el.msRequestFullscreen();
            }
            this.onResize();
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
                this.onResize();
            }
        }

    }


    videoEnded(){
        //this.el.classList.remove( 'remove-cover' );
        this.el.classList.remove('playing');
        this.isPlaying = false;
    }


    destroy(){
        window.removeEventListener( 'resize', this.onResizeBind );
        Emitter.off('pause-all-videos', this.onPauseAllVideosBind );
    }
}
