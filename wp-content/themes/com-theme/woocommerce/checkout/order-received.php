<?php
/**
 * "Order received" message.
 *
 * WooCommerce renders this immediately before the guest email-verification
 * form. For a valid signed order URL, the verification template supplies the
 * complete page heading, so the generic message must not be duplicated above
 * it.
 *
 * @package com-theme
 * @version 8.8.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;

$is_valid_order_url = false;

if ( ! $order && is_order_received_page() ) {
    global $wp;

    $requested_order_id = absint( $wp->query_vars['order-received'] ?? 0 );
    $requested_key      = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $requested_order    = $requested_order_id > 0 ? wc_get_order( $requested_order_id ) : false;

    $is_valid_order_url = $requested_order instanceof WC_Order
        && $requested_key !== ''
        && hash_equals( $requested_order->get_order_key(), $requested_key );
}

if ( $is_valid_order_url ) {
    return;
}
?>

<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
    <?php
    $message = apply_filters(
        'woocommerce_thankyou_order_received_text',
        esc_html__( 'Ευχαριστούμε. Η παραγγελία σας έχει παραληφθεί.', 'com-theme' ),
        $order
    );

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $message;
    ?>
</p>
