import { module } from 'modujs';
import { PageFlip } from 'page-flip';


export default class extends module {
    constructor(m) {
        super(m);

        this.prev = this.$( 'prev' );
        this.next = this.$( 'next' );
        this.reset = this.$( 'zoom-reset' );
        this.zoom = this.$( 'zoom' )[0];
        this.viewer = this.$( 'viewer' )[0];
        this.currentPage = this.$( 'current-page' )[0];

        this.totalPages = this.el.dataset.totalImages;
        this.pages = [];
        for( let i = 1; i <= this.totalPages; i++ ){
            this.pages.push( this.el.dataset.imagesPath + i + '.jpg' );
        }
        this.pageFlip = new PageFlip(this.viewer, { width: this.el.dataset.width, height: this.el.dataset.height, size: "stretch", showCover: true });
        this.pageFlip.loadFromImages(this.pages);
        this.activeThumb = this.el.querySelector( `.active[data-swiper-slide-index]`);


        [ ...this.next ].forEach( btn => btn.addEventListener( 'click', e => this.pageFlip.flipNext() )  );
        [ ...this.prev ].forEach( btn => btn.addEventListener( 'click', e => this.pageFlip.flipPrev() )  );
        [ ...this.reset ].forEach( btn => btn.addEventListener( 'click', e => this.pageFlip.turnToPage( 0 ) )  );

        this.swiper = this.el.querySelector( '[data-module-swiper]' );
        if( this.swiper ){
            this.swiperModuleName = this.swiper.dataset.moduleSwiper;
        }


        this.pageFlip.on('flip', (e) => {
            this.currentPage.textContent = e.data + 1;
            this.call( 'gotToIndex', e.data, 'Swiper', this.swiperModuleName);
            this.activeThumb.classList.remove( 'active' );
            this.activeThumb = this.el.querySelector( `[data-swiper-slide-index="${e.data}"]`);
            this.activeThumb.classList.add( 'active' );
        } );

        this.swiper.addEventListener( 'click', e => {
            let target = e.target.closest( '.slide' );
            if( target ){
                let page = parseInt( target.dataset.swiperSlideIndex );
                this.pageFlip.turnToPage( page );
            }
        });

    }



}
