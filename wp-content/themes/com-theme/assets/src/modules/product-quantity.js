import { module } from 'modujs';
import axios from 'axios';

export default class extends module {
    constructor(m) {
        super(m);
        this.input = this.$('input')?.[0] ?? null;
        this.cartItemKey = this.el.dataset.cartItemKey || '';
        this.cartItem = this.el.closest('[data-cart="item"]');
        this.productId = this.cartItem?.dataset.productId || '0';
        this.variationId = this.cartItem?.dataset.variationId || '0';
        this.el.querySelectorAll('[data-product-quantity="change"]').forEach(button => {
            button.addEventListener('click', event => this.change(event));
        });
    }

    change(event) {
        if (!this.input || this.el.classList.contains('loading')) return;

        const direction = Number(event.currentTarget.dataset.direction) || 0;
        const current = Number(this.input.value) || 0;
        const min = Number(this.input.min) || 0;
        const max = Number(this.input.max) || Number.POSITIVE_INFINITY;
        const next = Math.max(min, Math.min(max, current + direction));

        if (next === current) return;

        this.el.classList.add('loading');
        axios.post(THEME_OBJ.ajaxURL, new URLSearchParams({
            action: 'iw_cart_update_product_quantity',
            product_id: this.productId,
            variation_id: this.variationId,
            cart_item_key: this.cartItemKey,
            quantity: String(next),
        })).then(response => {
            if (response.data?.success) {
                this.input.value = String(next);
                this.call('update', response.data.data.cart, 'Cart');
            }
        }).finally(() => {
            this.el.classList.remove('loading');
        });
    }
}
