import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.content = this.el.querySelector('[data-add-to-cart-modal="content"]');
        this.onDocumentClickBind = this.onDocumentClick.bind(this);
        document.addEventListener('click', this.onDocumentClickBind);

    }

    onDocumentClick(e) {
        const trigger = e.target.closest('[data-product-thumb="add-to-cart-button"]');
        if (!trigger) return;

        const productCard = trigger.closest('[data-module-product-thumb], .product-card');
        if (!productCard) return;

        e.preventDefault();
        e.stopPropagation();

        if (this.setProduct(productCard)) {
            this.openModal();
        }
    }

    setProduct(productCard) {
        if (!this.content) return false;

        const template = productCard.querySelector('template[data-product-add-to-cart-template]');
        if (!template) return false;

        this.call('destroy', this.content, 'app');
        this.content.innerHTML = '';
        this.content.appendChild(template.content.cloneNode(true));

        this.call('update', this.content, 'app');
        return true;
    }

    openModal() {
        const modal = document.querySelector('[data-module-modal="product-add-to-cart"]');
        if (!modal) return;
        if (modal.classList.contains('active')) return;
        this.call('showModal', false, 'Modal', modal.dataset.moduleModal);
    }

    destroy() {
        document.removeEventListener('click', this.onDocumentClickBind);
    }
}
