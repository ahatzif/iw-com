import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'zoom-in': 'zoomIn', 'zoom-out': 'zoomOut', 'zoom-reset': 'zoomReset', 'next': 'next', 'prev' : 'prev'} };
        this.currentPage = this.$( 'current-page' )[0];
        this.currentPageNumber = 1;
        this.zoomContainer = this.$( 'zoom-container' )[0];
        this.initialZoom = parseInt( window.innerWidth <768 ? 100 : this.zoomContainer.dataset.initialZoom );
        this.currentZoom = this.initialZoom;

    }

    initPages( num = 0 ){
        this.pages = this.el.querySelectorAll( '[data-page]'); // can't use this.$( 'page' );
        this.maxPageNumber = this.pages.length;
        if( this.$( 'max-page' ).length ) {
            this.$( 'max-page' )[ 0 ].textContent = this.maxPageNumber;
        }
        this.pages.forEach(page => this.observer.observe(page));
    }

    init(){
        this.scrollBar = this.el.querySelector( '[data-module-scrollbar]' );
        if( this.scrollBar ){
            this.scrollBar = this.scrollBar.dataset.moduleScrollbar;
            this.observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const index = Array.from(this.pages).indexOf(entry.target);
                        if (index !== -1) {
                            this.currentPageNumber = index + 1;
                            this.currentPage.textContent = this.currentPageNumber;
                        }
                    }
                });
            }, {  threshold: 0.5 });
            this.initPages();
        }

    }
    zoomIn() {
        this.currentZoom = Math.min( window.innerWidth <768 ? 200 : 100, this.currentZoom + 10);
        this.zoomContainer.style.width = `${this.currentZoom}%`;
    }
    zoomOut() {
        this.currentZoom = Math.max(10, this.currentZoom - 10);
        this.zoomContainer.style.width = `${this.currentZoom}%`;
    }
    zoomReset() {
        this.currentRotation = (this.currentRotation || 0) + 90;
        this.pages.forEach(img => {
            img.style.width = '100%';
            img.style.height = 'auto';
            img.style.transform = `rotate(${this.currentRotation}deg)`;
            img.style.transformOrigin = 'center center';
        });
    }
    next(){
        this.setCurrentPage();
    }
    prev(){
        this.setCurrentPage(-1);
    }
    setCurrentPage( prefix = 1 ) {
        this.currentPageNumber = Math.min(Math.max(1, this.currentPageNumber + prefix), this.maxPageNumber);
        this.currentPage.textContent = this.currentPageNumber;
        if( this.scrollBar ){
            this.call( 'scrollIntoView',  this.pages[ this.currentPageNumber - 1 ] , 'Scrollbar', this.scrollBar );
        }
    }
}
