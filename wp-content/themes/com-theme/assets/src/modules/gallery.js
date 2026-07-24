import { module } from 'modujs';
import { Fancybox } from "@fancyapps/ui";
import "@fancyapps/ui/dist/fancybox/fancybox.css";

export default class extends module {
    constructor(m) {
        super(m);
        this.inited = false;



    }
    init() {

        if (this.inited) {
            return;
        }
        this.inited = true;
        [...this.el.querySelectorAll('[data-fancybox]')].forEach(el => {
            el.dataset.fancybox = this.el.dataset.moduleGallery;
            if( el.dataset.href ) {
                el.setAttribute( 'href', el.dataset.href);
            }
        })
        this.numbersAdded = false;

        Fancybox.bind('[data-fancybox="' + this.el.dataset.moduleGallery + '"]', {
            Hash: false,
            Thumbs: false,
            caption: (fancybox, slide) => {
                const captionEl = slide.triggerEl?.querySelector('[data-caption]');
                const creditsEl = slide.triggerEl?.querySelector('[data-credits]');
                let $txtString = '';
                if(captionEl || creditsEl){
                    $txtString += `<div class="fancybox-desc space-y-40 border-t border-dashed border-current py-20 px-20 ml-0 md:ml-40 md:pl-0">`;

                    if(captionEl && captionEl.innerHTML)
                        $txtString += `<div class="text-H6 md:text-H5">${captionEl.innerHTML}</div>`;

                    if(creditsEl && creditsEl.innerHTML)
                        $txtString += `<div class="text-H7">${creditsEl.innerHTML}</div>`;

                    $txtString += `</div>`;
                    return $txtString;
                }
            },
            Toolbar: {
                display: { left: [], middle: [], right: ["close"], },
                items: {
                    close: { tpl: '<button class="fancybox-close group" title="{{CLOSE}}" data-fancybox-close><svg class="size-[2.1rem]"><use xlink:href=\"#icon-close-fancybox\"></use></svg></button>' }
                },
            },
            contentClick: "toggleCover",
            on: {
                loaded: this.addNumbers.bind(this),
                "Carousel.change": this.onSlideDone.bind(this),
                done: this.onSlideDone.bind(this),
                close: this.onClose.bind(this)
            },
            Carousel: {
                Navigation: {
                    classes: {
                        container: "fancybox-custom-nav gap-10",
                        button: "fancybox-custom-nav-button group",
                        isNext: "is-next",
                        isPrev: "is-prev"
                    },
                    prevTpl: "<svg data-arrow-prev class=\"text-white !block border-[0.2rem] border-current shrink-0 select-none  rounded-full w-40 h-40 cursor-pointer [&.inactive]:pointer-events-none [&.inactive]:opacity-20\"><use xlink:href=\"#icon-circle-arrow\"></use></svg>",
                    nextTpl: "<svg data-arrow-next class=\"text-white !block border-[0.2rem] border-current shrink-0 select-none  rounded-full w-40 h-40 cursor-pointer [&.inactive]:pointer-events-none [&.inactive]:opacity-20 rotate-180 \"><use xlink:href=\"#icon-circle-arrow\"></use></svg>",
                },
                transition: "fade",
                animated: false

            },
            Images: {
                Panzoom: {
                    panMode: "mousemove",
                    mouseMoveFactor: 1.1,
                    mouseMoveFriction: 0.12,
                },
            },
        });

    }
    onClose() {
        this.numbersAdded = false;

    }
    addCustomContentWrapper(slide) {
        if( slide.el.querySelector( '.custom-fancybox__content') ) return;
        const customDiv = document.createElement("div");
        customDiv.className = "custom-fancybox__content";
        const contentEl = slide.el.querySelector(".fancybox__content");
        slide.el.insertBefore(customDiv, contentEl);
        customDiv.appendChild(contentEl);
    }
    onSlideDone(fancybox) {
        this.numbersContainer.innerHTML = (fancybox.carousel.page + 1) + '/' + fancybox.userSlides.length;
        let slide = fancybox.carousel.slides[ fancybox.carousel.page ];
        this.addCustomContentWrapper(slide);
    }
    addNumbers(fancybox) {


        if (this.numbersAdded) return;
        this.numbersAdded = true
        this.numbersContainer = document.createElement("div");
        this.numbersContainer.classList.add('text-H7', 'text-medium', 'order-last', 'flex', 'items-center', 'pr-10' );

        let container = fancybox.container.querySelector('.fancybox-custom-nav');
        if (container) {
            let prevButton = container.querySelector('.is-next');
            container.insertBefore(this.numbersContainer, prevButton);
        }

    }
    destroy() {
        Fancybox.close();
    }
}
