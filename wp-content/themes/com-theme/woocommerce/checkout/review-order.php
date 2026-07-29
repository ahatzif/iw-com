<?php
defined( 'ABSPATH' ) || exit;

$count = com_theme_cart_count();
$capture_html = static function ( callable $callback ): string {
    ob_start();
    $callback();

    return (string) ob_get_clean();
};
?>
<div class="woocommerce-checkout-review-order-table overflow-hidden rounded-[1.5rem] bg-white text-blue">
    <div class="space-y-30 p-30 md:p-40">
        <?php do_action( 'woocommerce_review_order_before_cart_contents' ); ?>
        <div class="space-y-20">
            <?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
                $product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                if ( ! $product || ! $product->exists() || empty( $cart_item['quantity'] ) || ! apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
                    continue;
                }
            ?>
                <?php wc_get_template( 'cart/cart-item.php', [
                    'cart_item_key' => $cart_item_key,
                    'cart_item'     => $cart_item,
                    'variant'       => 'checkout-summary',
                ] ); ?>
            <?php endforeach; ?>
        </div>
        <?php do_action( 'woocommerce_review_order_after_cart_contents' ); ?>
    </div>

    <div class="relative border-t border-dashed border-blue-soft px-30 py-30 before:absolute before:-left-15 before:-top-15 before:size-30 before:rounded-full before:bg-blue before:content-[''] after:absolute after:-right-15 after:-top-15 after:size-30 after:rounded-full after:bg-blue after:content-[''] md:px-40">
        <?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) :
            $coupon_html = $capture_html( static function () use ( $coupon ): void {
                wc_cart_totals_coupon_html( $coupon );
            } );
        ?>
            <div class="mb-15 flex items-center justify-between gap-20 text-[1.3rem] text-blue-soft">
                <span><?= esc_html( sprintf( __( 'Κουπόνι: %s', 'com-theme' ), $coupon->get_code() ) ) ?></span>
                <span><?= wp_kses_post( $coupon_html ) ?></span>
            </div>
        <?php endforeach; ?>

        <?php foreach ( WC()->cart->get_fees() as $fee ) :
            $fee_html = $capture_html( static function () use ( $fee ): void {
                wc_cart_totals_fee_html( $fee );
            } );
        ?>
            <div class="mb-15 flex items-center justify-between gap-20 text-[1.3rem] text-blue-soft">
                <span><?= esc_html( $fee->name ) ?></span>
                <span><?= wp_kses_post( $fee_html ) ?></span>
            </div>
        <?php endforeach; ?>

        <div class="flex items-start justify-between gap-20">
            <div>
                <div class="text-[1.6rem] font-bold text-blue-soft"><?= esc_html__( 'ΣΥΝΟΛΟ', 'com-theme' ) ?></div>
                <div class="mt-5 text-[1.2rem]"><?= esc_html( com_theme_cart_count_label( $count ) ) ?></div>
            </div>
            <div class="text-[2.4rem] font-bold"><?= wp_kses_post( WC()->cart->get_total() ) ?></div>
        </div>
    </div>
</div>
