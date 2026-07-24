import { module } from 'modujs';
import Emitter from "tiny-emitter/instance";
import axios from "axios";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'open-modal': 'openModal' } };
        Emitter.on( 'open-preview-item', this.openPreviewItem.bind( this ) );
        Emitter.on( 'virtual-map-loaded', this.virtualMapLoaded.bind( this ) );
        Emitter.on( 'virtual-map-toggle', this.virtualMapToggle.bind( this ) );

        this.itemPreviewContent = this.$( 'item-preview-content' )[0];
        this.closeItemPreviewBtn = this.$( 'close-item-preview' )[0];
        this.closeItemPreviewBtn.addEventListener('click', () => this.closeItemPreview());
        this.modal = this.$( 'modal' )[0];
        this.handleKeyDownBind = this.handleKeyDown.bind(this);
        document.addEventListener('keydown', this.handleKeyDownBind);
    }

    handleKeyDown(e) {
        if (e.key === 'Escape') {
            this.closeItemPreview();
        }
    }

    openModal() {
        this.importScript();
        this.call( 'showModal', false, "Modal", this.modal.dataset.moduleModal );
    }


    closeItemPreview(){
        this.modal.classList.remove( 'item-preview-opened' );
        this.call( 'destroy', this.itemPreviewContent, 'app' );
        this.itemPreviewContent.innerHTML = '';
    }

    openPreviewItem( inventory_number ){
        this.call( 'show', false, 'PageLoading' );
        axios.get( THEME_OBJ.ajaxURL + `?action=get_item_preview&lang=${THEME_OBJ.lang}&inventory_number=${inventory_number}` ).then( response => {

            this.itemPreviewContent.innerHTML = response.data.data.html;
            this.call( 'update', this.itemPreviewContent, 'app' );
            this.modal.classList.add( 'item-preview-opened' );
            this.call( 'hide', false, 'PageLoading' );
            this.mapWrapper.classList.remove('active');

        } ).catch( error => console.info(error) );
    }

    importScript(src)  {
        if( this.script ) return;
        this.call( 'show', false, 'PageLoading' );
        this.script = document.createElement('script');
        this.script.src = this.el.dataset.scriptUrl;
        this.script.onload =this.onScriptLoaded.bind( this );
        document.head.appendChild(this.script);
    }

    onScriptLoaded(){
        this.call( 'hide', false, 'PageLoading' );
        this.isKrpanoLoaded = true;
        embedpano({ swf: "tour.swf", xml: this.el.dataset.xmlUrl + '?r=' + Math.random(), target: "krpano", html5: "only", passQueryParameters: true, onready:  krpano  =>{
            this.krpano = krpano;
        } });
    }

    virtualMapToggle(){

    }

    virtualMapLoaded(){
        this.mapWrapper = this.krpano.get("plugin[map_wrapper].sprite");
        this.mapToggle = this.krpano.get("plugin[map_close_wrapper].sprite");
        this.mapLinks = this.krpano.get("plugin[map_links].sprite");
        this.skinScrollWindow = this.krpano.get('layer[skin_scroll_window].sprite');
        this.floor_plant_background = this.krpano.get('plugin[floor_plant_background].sprite');
        this.skin_thumbs = this.krpano.get('layer[skin_thumbs].sprite');
        this.skin_scroll_container = this.krpano.get('layer[skin_scroll_container].sprite');


        this.mapToggle.innerHTML = `<div class="inner text-Body-Text font-bold text-blue !rotate-180 !-translate-x-1/2 absolute left-1/2 top-[2.4rem] writing-mode-rl inline-flex items-center gap-20">
        <span class="group-[.map.active]:hidden">MAP</span>
        <svg class="block group-[.map.active]:hidden" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2.53516 1.84082L2.53516 12.2607L12.6729 2.13965C13.0637 1.74983 13.6968 1.75087 14.0869 2.1416C14.4767 2.53249 14.4757 3.1656 14.085 3.55566L3.98242 13.6406L14.3398 13.6406C14.8921 13.6406 15.3398 14.0883 15.3398 14.6406C15.3398 15.1929 14.8921 15.6406 14.3398 15.6406L1.86133 15.6406L1.75977 15.6357C1.73931 15.6337 1.7193 15.6293 1.69922 15.626C1.64577 15.6348 1.5911 15.6406 1.53516 15.6406C0.982871 15.6406 0.535156 15.1929 0.535156 14.6406L0.535155 1.84082C0.535155 1.28854 0.98287 0.840822 1.53515 0.840822C2.08744 0.840821 2.53515 1.28854 2.53516 1.84082Z" fill="#756546"/></svg>
        <svg class="hidden group-[.map.active]:block" width="23" height="22" viewBox="0 0 23 22" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M20.415 0.542969C20.8055 0.152694 21.4386 0.152777 21.8291 0.542969C22.2196 0.933493 22.2196 1.56748 21.8291 1.95801L12.7861 11L21.8291 20.043C22.2196 20.4335 22.2196 21.0665 21.8291 21.457C21.4386 21.8476 20.8056 21.8476 20.415 21.457L11.3721 12.4141L2.3291 21.458L2.25293 21.5264C1.86024 21.8463 1.28104 21.8238 0.915039 21.458C0.54903 21.092 0.525647 20.5119 0.845703 20.1191L0.915039 20.043L9.95801 11L0.915039 1.95703L0.84668 1.88184C0.526549 1.48907 0.549003 0.909005 0.915039 0.542969C1.28108 0.176936 1.86114 0.15448 2.25391 0.474609L2.3291 0.542969L11.3721 9.58594L20.415 0.542969Z" fill="#A89265"/>
        </svg>
        </div>`;

        /*this.mapToggle.addEventListener( 'click' , e => {this.mapWrapper.classList.toggle('active');});*/

        this.mapToggleClick(this.mapToggle);

        this.mapWrapper.classList.add( 'md:left-1/24', 'md:top-1/24', 'left-1/24', 'bottom-1/24', 'md:top-1/24', '!rounded-[1.5rem]', '!border-none', '!bg-ochre-light', '!items-center', 'group', 'map', '!w-[63px]', '[&.active]:!w-22/24', 'xs:[&.active]:!w-[480px]', 'transition-all', 'duration-300' );
        this.mapToggle.classList.add( '!bg-ochre-light', '!cursor-pointer', '!pointer-events-auto', '!transform-none', 'right-0' );
        this.skinScrollWindow.classList.add(  'md:left-1/24', 'left-1/24' );
        this.floor_plant_background.classList.add( 'left-1/2', 'top-[35px]', '!-translate-x-1/2', 'absolute' );

        // This MutationObserver watches for the dynamic addition of the .map-links element inside the mapWrapper.
        const observer = new MutationObserver(() => {
            const mapLinks = this.mapWrapper.querySelector('.map-links');
            if (mapLinks) {

                // observer.disconnect(); // σταματάει να παρακολουθεί
                mapLinks.classList.add('!pointer-events-auto');
                let mapLinksItems = mapLinks.querySelectorAll('[data-xml-url]');

                mapLinksItems.forEach( el => {
                    // el.addEventListener('click', (ev) => {
                    //     this.changeFloor(ev);
                    // });

                    this.mapLinkItemBind(el)

                });
            }
        });
        observer.observe(this.mapWrapper, { childList: true, subtree: true });

    }

    // helper για το toggle
    mapToggleClick = (el) => {
        if (!el || el.__mapToggleBound) return; // αποφυγή διπλών bindings
        el.__mapToggleBound = true;

        const onToggle = (e) => {
        // μπλοκάρουμε το μελλοντικό click ΜΟΝΟ σε pointer/touch/keyboard
            if (e.type === 'pointerdown' || e.type === 'touchstart' || e.type === 'keydown') {
                e.preventDefault();
            }
            e.stopPropagation();
            this.mapWrapper.classList.toggle('active');

        };

        // tap-friendly + πάνω από όλα
        Object.assign(el.style, {
        touchAction: 'manipulation',
        pointerEvents: 'auto',
        cursor: 'pointer',
        zIndex: 99999
        });

        // ΜΟΝΟ ένα μονοπάτι: pointerdown αν υποστηρίζεται, αλλιώς touchstart+click
        if ('PointerEvent' in window) {
        el.addEventListener('pointerdown', onToggle, { passive: false });
        } else {
        el.addEventListener('touchstart', onToggle, { passive: false });
        el.addEventListener('click', onToggle, { passive: true }); // fallback
        }
    }


    mapLinkItemBind = (el) => {
        // helper για ΜΟΝΟΚΑΝΟΝΙΚΑ map link items (χωρίς delegation)

        if (!el || el.__mapLinkBound) return;   // μην ξαναδένεις
        el.__mapLinkBound = true;

        // tap-friendly
        Object.assign(el.style, {
            touchAction: 'manipulation',
            pointerEvents: 'auto',
            cursor: 'pointer'
        });

        const onActivate = (e) => {
            // μπλοκάρουμε μόνο σε pointer/touch/keyboard — όχι στο click fallback
            if (e.type === 'pointerdown' || e.type === 'touchstart' || e.type === 'keydown') {
            e.preventDefault();
            }
            e.stopPropagation();
            this.changeFloor(e);
        };

        if ('PointerEvent' in window) {
            // σύγχρονα browsers: ΜΟΝΟ pointerdown => όχι διπλά triggers
            el.addEventListener('pointerdown', onActivate, { passive: false });
        } else {
            // παλιότερα: touchstart + click fallback
            el.addEventListener('touchstart', onActivate, { passive: false });
            el.addEventListener('click', (e) => {
            // σε <a> κόψε navigation στο fallback click
            if (el.tagName === 'A') e.preventDefault();
            onActivate(e);
            }, { passive: false });
        }
    };







    changeFloor(ev){
        ev.preventDefault();
        let xmlUrl = ev.target.dataset.xmlUrl;
        // Αν το site τρέχει σε /bmw, πρόσθεσε το prefix, αλλιώς άφησέ το ως έχει
        const basePath = window.location.pathname.includes('/bmw/') ? '/bmw' : '';
        if (xmlUrl.startsWith('/virtual/')) {
            xmlUrl = basePath + xmlUrl;
        }
        xmlUrl = window.location.origin + xmlUrl;

        // xmlUrl = xmlUrl + '?r=' + Math.random();

        if(!xmlUrl){
            return
        }

        if (window.removepano) {
            window.removepano("krpano");
        }

        let panoParent = document.querySelector('[data-krpano-parent]');

        let panoDiv = document.getElementById('krpano');
        if (!panoDiv) {
            panoDiv = document.createElement('div');
            panoDiv.id = 'krpano';
            panoDiv.className = "h-full rounded-t-30 overflow-hidden bg-[red]";
            panoParent.appendChild(panoDiv);
        }


        embedpano({ swf: "tour.swf", xml: xmlUrl, target: "krpano", html5: "only", passQueryParameters: true, onready:  krpano  => {
            this.krpano = krpano;
        } });

    }

    destroy() {
        this.modal.remove();
    }
}




function playSound(soundItem) {
    let audioItem = soundItem.getAttribute('data-audio-item');
    let audio = document.getElementById('writing-audio');
    if(audioItem) {
        audio.src = 'https://www.benaki.org/images/qr/sounds/' + audioItem;
        audio.load();
        audio.play();
    }
}

function gup( name, url ) {
    if (!url) url = location.href;
    name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
    let regexS = "[\\?&]"+name+"=([^&#]*)";
    let regex = new RegExp( regexS );
    let results = regex.exec( url );
    return results ? results[1] : "el" ;
}


window.getItemLightBox = inventory_number => Emitter.emit( 'open-preview-item', inventory_number );
window.virtualMapLoaded = () => { Emitter.emit( 'virtual-map-loaded'); };
window.virtualMapToggle = () => { Emitter.emit( 'virtual-map-toggle'); };
