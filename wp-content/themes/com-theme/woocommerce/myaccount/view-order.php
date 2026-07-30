<?php
/**
 * Single order.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

$order = wc_get_order( $order_id );
?>

<?php
get_template_part( 'woocommerce/myaccount/page-title', null, [
    'eyebrow'     => __( 'Λεπτομέρειες αγοράς', 'com-theme' ),
    'title'       => $order ? sprintf( __( 'Αγορά #%s', 'com-theme' ), $order->get_order_number() ) : __( 'Αγορά', 'com-theme' ),
    'description' => $order ? com\theme::remove_accents( mb_strtoupper( wc_format_datetime( $order->get_date_created(), 'l, j F · H:i' ), 'UTF-8' ) ) : '',
] );
?>

<div class="account-order-details">
    <?php do_action( 'woocommerce_view_order', $order_id ); ?>
</div>
