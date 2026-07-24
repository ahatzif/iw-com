import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { 'availability-notification-button': 'openAvailabilityNotificationPopup' } };
        this.productId = this.el.dataset.productId;
        this.variation_id = this.el.dataset.variationId;
        this.placeholder = this.el.querySelector('[data-placeholder-product-thumb]');
    }

    init() {
    
        // Προσπάθησε πρώτα να πάρεις variations από το ίδιο το element
        if (this.el.dataset.variations) {
            try {
                this.variations = JSON.parse(this.el.dataset.variations);
            } catch (e) {
                console.warn('Invalid JSON in data-variations on main element:', e);
                this.variations = {};
            }
        }

        // Αν δεν υπάρχουν, ψάξε για child element με data-variations
        if (!this.variations || Object.keys(this.variations).length === 0) {
            const variationEl = this.el.querySelector('[data-variations]');
            if (variationEl && variationEl.dataset.variations) {
                try {
                    this.variations = JSON.parse(variationEl.dataset.variations);
                } catch (e) {
                    console.warn('Invalid JSON in data-variations on child element:', e);
                    this.variations = {};
                }
            }
        }

        if(!this.variations){
            this.variations = '{}';
        }


        // Προσπάθησε πρώτα να πάρεις defaults από το ίδιο το element
        if (this.el.dataset.defaults) {
            try {
                this.defaults = JSON.parse(this.el.dataset.defaults);
            } catch (e) {
                console.warn('Invalid JSON in data-defaults on main element:', e);
                this.defaults = {};
            }
        }

        // Αν δεν υπάρχουν, ψάξε για child element με data-defaults
        if (!this.defaults || Object.keys(this.defaults).length === 0) {
            const defaultsEl = this.el.querySelector('[data-defaults]');
            if (defaultsEl && defaultsEl.dataset.defaults) {
                try {
                    this.defaults = JSON.parse(defaultsEl.dataset.defaults);
                } catch (e) {
                    console.warn('Invalid JSON in data-defaults on child element:', e);
                    this.defaults = {};
                }
            }
        }

        if(!this.defaults){
            this.defaults = '{}';
        }

        this.selected = {
            pa_color: this.defaults.pa_color || '',
            pa_material: this.defaults.pa_material || '',
            pa_dimension: this.defaults.pa_dimension || ''
        };

    
        this.el.addEventListener('click', (e) => {
            const option = e.target.closest('[data-value]');
            if (!option) return;
            const attrContainer = option.closest('[data-attr]');
            const attrName = attrContainer.dataset.attr;
            const attrValue = option.dataset.value;
            
            // Αφαίρεση active μόνο για pa_color
            if(attrName === 'pa_color'){                
                const swatches = attrContainer.querySelectorAll('[data-value]');
                swatches.forEach(el => el.classList.remove('active'));
                option.classList.add('active');
            }

            // Ενημερώνουμε το επιλεγμένο attribute
            this.selected[attrName] = attrValue;

            // Βρίσκουμε το variation key
            const key = Object.values(this.selected).filter(Boolean).join('-');

            if (this.variations[key]) {
                this.updateProduct(this.variations[key]);
                this.variation_id = this.variations[key].id;
                this.el.classList.toggle( 'in-stock', this.variations[key].in_stock  )
            }
            this.togglePlaceholderImg(this.variations[key]);
        });
    }


    togglePlaceholderImg(selectedVariationKey){
        if(!this.placeholder) return;
        this.placeholder.classList.toggle('hidden', selectedVariationKey);
    }
  

    openAvailabilityNotificationPopup(){
        let modal = document.querySelector( '[data-module-modal="product-availability-notification"]' );
        if( modal ){

            this.call( 'showModal', false, 'Modal', modal.dataset.moduleModal );
            let productIdInput = modal.querySelector( 'input[name="product_id"]' );
            let variationIdInput = modal.querySelector( 'input[name="variation_id"]' );
            if( productIdInput && productIdInput ){
                productIdInput.value = this.productId;
                variationIdInput.value = this.variation_id;
            }
        }
    }

    updateProduct(variation) {
        const imgEl = this.el.querySelector('[data-product-thumb="img"]');
        if (imgEl && variation.image) {
            imgEl.src = variation.image;
        }
        const priceEl = this.el.querySelector('[data-product-thumb="price"]');
        if (priceEl && variation.price) {
            priceEl.innerHTML = variation.price;
        }
    }

    destroy() {}
}
