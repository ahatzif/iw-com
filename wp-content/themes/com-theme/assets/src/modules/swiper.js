import { module } from 'modujs';
import Swiper from "swiper";
import { Navigation, EffectFade, Autoplay, FreeMode, Pagination, Zoom } from 'swiper/modules';
import { isMobile } from 'mobile-device-detect';


export default class extends module {
    constructor(m) {
        super(m);


        this.events = { click: { 'fullscreen': 'goFullScreen', 'download': 'downloadImage' } };

        let options = this.el.dataset.options ? JSON.parse(decodeURIComponent(this.el.dataset.options)) : {};

        if (options.mobileOnly && window.innerWidth > 767) return;

        this.swiperEl = this.$('swiper')[0];
        this.touchStartBind = this.onTouchStart.bind(this);
        this.touchMoveBind = this.onTouchMove.bind(this);
        this.touchEndBind = this.onTouchEnd.bind(this);
        this.addEvents();


        this.sliderMoveBind = this.sliderMove.bind(this);


        this.hasZoom = this.el.dataset.zoom === 'true';


        let modules = [Navigation, EffectFade, Autoplay, FreeMode, Pagination];
        if (this.hasZoom) {
            modules.push(Zoom);
        }



        this.options = {
            ...{
                modules: modules,
                freeMode: true,
                mousewheel: true,
                slidesPerView: "auto",
                wrapperClass: 'wrapper',
                slideClass: 'slide',
                navigation: {
                    hiddenClass: 'inactive',
                    disabledClass: 'inactive',
                    prevEl: this.el.querySelector("[data-arrow-prev]"),
                    nextEl: this.el.querySelector("[data-arrow-next]"),
                },
                pagination: {
                    el: this.el.querySelector('[data-swiper-pagination]'),
                    clickable: true,
                },
            }, ...options
        };
        this.swiper = new Swiper(this.swiperEl, this.options);

        this.swiper.on('sliderMove', this.sliderMoveBind);

        if (this.hasZoom) {
            this.buildZoom();
        }
        this.currentScale = 1;
        this.swiper.on('slideChange', this.onSlideChange.bind(this));
        this.onResizeBind = this.onResize.bind(this);
        if (this.options.centeredSlides) {
            this.firstSlide = this.el.querySelector('.' + this.options.slideClass);
            window.addEventListener('resize', this.onResizeBind);
            this.onResize();

        }
        //this.swiper.loopFix();
        this.listener = this.el.closest('[data-swiper-listener]');
    }

    onResize() {
        return;
        this.swiperEl.style.transform = 'none';
        setTimeout(() => {
            let move = -(this.swiperEl.offsetWidth - this.firstSlide.offsetWidth) / 2;
            this.swiperEl.style.transform = `translateX(${move}px)`;
            this.swiper.loopFix();
        }, 100);

    }

    destroy() {
        window.removeEventListener('resize', this.onResizeBind);
    }

    gotToIndex(index) {
        // this.swiper.slideTo(index);
        const targetSlide = [...this.swiper.slides].find((slide) => slide.dataset.swiperSlideIndex == index);
        if (targetSlide) {
            const swiperIndex = this.swiper.slides.indexOf(targetSlide);
            this.swiper.slideTo(swiperIndex);
        }
    }

    buildZoom() {


        this.swiper.on('click', (swiper, event) => {
            const clickedSlide = event.target.closest('.slide');
            if (!clickedSlide) return;
            const zoomContainer = clickedSlide.querySelector('.swiper-zoom-container');
            if (!zoomContainer) return;
            const zoomedImg = zoomContainer.querySelector('img');
            zoomedImg.style.transformOrigin = '';
            const zoom = swiper.zoom;
            if (clickedSlide) {
                const zoom = swiper.zoom;
                if (zoom.scale && zoom.scale > 1) {
                    zoom.out();
                } else {
                    const rect = zoomContainer.getBoundingClientRect();
                    const offsetX = event.clientX - rect.left; // Click X relative to the image
                    const offsetY = event.clientY - rect.top;  // Click Y relative to the image
                    const imageWidth = zoomContainer.offsetWidth;
                    const imageHeight = zoomContainer.offsetHeight;


                    if (this.el.querySelector('[data-module-hotspots]')) {
                        if (!event.target.closest('[data-hotspots="hotspot"]')) {
                            return;
                        }
                        zoom.in();
                        let transformOriginString = `${offsetX / imageWidth * 100}% ${offsetY / imageHeight * 100}%`;
                        zoomedImg.style.transformOrigin = transformOriginString;
                    } else {
                        zoom.in();
                        const translateX = (imageWidth / 2 - offsetX) * zoom.scale;
                        const translateY = (imageHeight / 2 - offsetY) * zoom.scale;
                        zoomContainer.style.transform = `translate(${translateX}px, ${translateY}px)`;
                    }


                }
            }
        });
    }

    sliderMove(e) {
        if (e.progress > 0.05) {
            this.el.classList.add('swiped');
            this.swiper.off('sliderMove', this.sliderMoveBind);
        }

    }

    onTouchStart(ev) {
        this.touchStartEv = ev.touches[0];
        this.swiperEl.addEventListener('touchmove', this.touchMoveBind, { passive: true });

    }

    onTouchMove(ev) {
        this.touchMoveEv = ev.touches[0];
        this.swiperEl.removeEventListener('touchmove', this.touchMoveBind);
        let dx = Math.abs(this.touchMoveEv.pageX - this.touchStartEv.pageX);
        let dy = Math.abs(this.touchMoveEv.pageY - this.touchStartEv.pageY);
        if (dx > dy) {
            this.call('stop', false, 'Scroll');
            this.swiperEl.addEventListener('touchend', this.touchEndBind, { passive: true });
            this.swiperEl.addEventListener('touchcancel', this.touchEndBind, { passive: true });
        }
    }

    onTouchEnd(ev) {
        this.call('start', false, 'Scroll');
        this.swiperEl.removeEventListener('touchend', this.touchEndBind, { passive: true });
        this.swiperEl.removeEventListener('touchcancel', this.touchEndBind, { passive: true });
    }

    addEvents() {
        if (isMobile) {
            this.swiperEl.addEventListener('touchstart', this.touchStartBind, { passive: true });
        }
    }
    goFullScreen() {
        if (!document.fullscreenElement) {
            if (this.el.requestFullscreen) {
                this.el.requestFullscreen();
            } else if (this.el.mozRequestFullScreen) { /* Firefox */
                this.el.mozRequestFullScreen();
            } else if (this.el.webkitRequestFullscreen) { /* Chrome, Safari & Opera */
                this.el.webkitRequestFullscreen();
            } else if (this.el.msRequestFullscreen) { /* IE/Edge */
                this.el.msRequestFullscreen();
            } else {
                this.el.webkitEnterFullscreen();
            }

        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }

    }

    downloadImage() {
        const currentSlide = this.swiper.slides[this.swiper.activeIndex];
        const imageElement = currentSlide.querySelector('img');
        if (!imageElement) {
            alert('No image found in the current slide!');
            return;
        }
        const imageUrl = imageElement.src;
        const link = document.createElement('a');
        link.href = imageUrl;
        link.download = imageUrl.split('/').pop();
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    setSlide(number) {
        this.swiper.slideTo(number);
    }

    zoomIn() {
        if (this.hasZoom && this.swiper && this.swiper.zoom) {
            const currentScale = this.swiper.zoom.scale || 1;
            this.swiper.zoom.in(Math.min(currentScale + 1, 20));
        }
    }

    zoomOut() {
        this.swiper.zoom.out();
    }


    onSlideChange() {

        if (this.el.closest('[data-popup-gallery="popup"]') && this.el.closest('[data-popup-gallery="popup"]').classList.contains('active')) {
            this.call('stopVideo', false, 'PopupGallery');
        }


        if (this.listener) {
            const moduleAttr = [...this.listener.attributes].find(attr => attr.name.startsWith('data-module-'));

            if (moduleAttr) {
                let moduleName = moduleAttr.name.replace('data-module-', '');
                moduleName = moduleName.split('-').map(part => part.charAt(0).toUpperCase() + part.slice(1)).join('');
                this.call('slideChange', { activeIndex: this.swiper.activeIndex }, moduleName);
            }
        }
    }

    reinit() {
        if (this.swiper) {
            this.swiper.destroy(true, true);
            this.swiper = null;
        }
        this.swiper = new Swiper(this.swiperEl, this.options);
    }


}
