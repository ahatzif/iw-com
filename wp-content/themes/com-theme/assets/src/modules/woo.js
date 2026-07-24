// modules/woo.js
import { module } from 'modujs';

export default class extends module {
  init() { this.reinit(this.el || document); }
  update() { this.init(); }

  reinit(ctx) {
    const $ = window.jQuery;
    if (!$) return; // jQuery δεν φορτώθηκε -> κάνε graceful fallback (δες πιο κάτω)

    const $ctx = $(ctx);

    // Gallery
    if (typeof $.fn.wc_product_gallery === 'function') {
      $('.woocommerce-product-gallery', $ctx).each(function () {
        $(this).wc_product_gallery(window.wc_single_product_params || {});
      });
    }

    // Variations
    if (typeof $.fn.wc_variation_form === 'function') {
      $('form.variations_form', $ctx).each(function () {
        $(this).wc_variation_form();
        $(this).find('.variations select').trigger('change');
      });
    }

    $(document.body).trigger('wc-product-gallery-after-init', [$ctx]);
  }
}
