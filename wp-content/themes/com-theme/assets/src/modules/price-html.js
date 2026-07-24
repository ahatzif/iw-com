import { module } from 'modujs';
import gsap from "gsap/gsap-core";

export default class extends module {
    constructor(m) {
        super(m);
        this.priceEl = this.el.querySelector( '.woocommerce-Price-amount.amount');
        this.key = this.el.dataset.key;
    }

    parsePrice(str) {
        const cleaned = str.replace(/\./g, '').replace(',', '.').replace(/[^\d.-]/g, '');
        const parsed = parseFloat(cleaned);
        return isNaN(parsed) ? 0 : parsed;
    }

    setPrice( args ){
        if( args.key !== this.key ) return;
        //this.priceEl.innerHTML = responseData.data.new_total;
        const oldVal = this.parsePrice(this.priceEl.textContent);
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = args.val;
        const newVal = this.parsePrice(tempDiv.textContent || tempDiv.innerText);
        gsap.fromTo( this.priceEl, { textContent: oldVal }, {
            textContent: newVal,
            duration: 0.4,
            ease: "power1.out",
            snap: { textContent: 0.01 },
            onUpdate: () => {
                let val = parseFloat(this.priceEl.textContent);
                this.priceEl.textContent = isNaN(val) ? '' : val.toLocaleString('el-GR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'
            },
            onComplete: () => {
                this.priceEl.innerHTML = args.val;
            }
        } );
    }
}
