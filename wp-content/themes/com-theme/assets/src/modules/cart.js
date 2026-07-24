import { module } from 'modujs';
import axios from 'axios';

export default class extends module {
    constructor(m) {
        super(m);
        this.itemsContainer = this.$('items')?.[0] ?? null;
        this.itemsCount = this.$('cart-items-count')?.[0] ?? null;
    }

    update(cart) {
        this.$('item').forEach(item => {
            const key = item.dataset.cartItemKey;
            const nextItem = cart.items?.[key];

            if (!nextItem) {
                item.remove();
                return;
            }

            const price = item.querySelector('[data-module-price-html]');
            if (price && nextItem.priceHTML) {
                price.innerHTML = nextItem.priceHTML;
            }
        });

        if (this.itemsCount) {
            this.itemsCount.textContent = cart.itemsCountText;
        }

        this.el.classList.toggle('is-empty', Number(cart.itemsCount) === 0);
        this.call('updateCount', {
            count: Number(cart.itemsCount) || 0,
            label: cart.itemsCountText,
        }, 'CartButton');
        this.call('setPrice', { key: 'cart-subtotal', val: cart.subtotal }, 'PriceHtml');
        this.call('setPrice', { key: 'cart-total', val: cart.total }, 'PriceHtml');
    }

    refresh() {
        axios.post(THEME_OBJ.ajaxURL, new URLSearchParams({ action: 'iw_cart_refresh' })).then(response => {
            if (response.data?.success) {
                this.call('update', response.data.data.cart, 'Cart');
            }
        });
    }
}
