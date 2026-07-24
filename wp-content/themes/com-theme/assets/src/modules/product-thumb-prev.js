import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.el.addEventListener( 'click', e => this.swatchClicked(e));
        this.el.addEventListener( 'change', e => this.radioChanged(e));
    }
    init() {
        this.$swatches = this.el.querySelectorAll('[data-product-thumb="swatch"]');
        this.$img = this.el.querySelector('[data-product-thumb="img"]');
        this.$price = this.el.querySelector('[data-product-thumb="price"]');
    }
    swatchClicked(e){
        const swatch = e.target.closest('[data-product-thumb="swatch"]');
        if (!swatch) return;
        this.$swatches.forEach((el) => el.classList.remove('active'));
        swatch.classList.add('active');
        this.changeImg(swatch);
        this.changePrice(swatch);
    }

    radioChanged(e) {
        const radio = e.target.closest('[data-product-material-variation="option"]');
        if (!radio || !radio.checked) return;
        const label = radio.closest('label');
        this.changeImg(label);
        this.changePrice(label);
    }
    changeImg(el) {
        if(!el) return;
        const newImage = el.dataset.image;
        if (this.$img && newImage) {
            this.$img.src = newImage;
        }
    }
    changePrice(el) {
        if(!el) return;
        const newPrice = el.dataset.price;
        if (this.$price && newPrice) {
            if(this.$price.querySelector('.woocommerce-Price-amount')){
                this.$price.querySelector('.woocommerce-Price-amount').innerHTML =`<bdi>${newPrice}&nbsp;<span class="woocommerce-Price-currencySymbol">€</span></bdi>`;
            }
            
        }
    }
    destroy(){}
}
