<?php
/**
 * Order customer details.
 *
 * @package com-theme
 * @version 8.7.0
 */

defined( 'ABSPATH' ) || exit;

$show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();
?>

<section class="woocommerce-customer-details account-order-addresses mt-20">
    <div class="grid gap-15<?= $show_shipping ? ' sm:grid-cols-2' : '' ?>">
        <article class="min-h-[18rem] rounded-[1.2rem] border border-blue/15 p-20<?= $show_shipping ? '' : ' sm:col-span-2' ?> md:p-25">
            <div class="mb-20 flex items-start gap-10 text-blue-soft">

                <h3 class="m-0 text-[1.1rem] font-bold tracking-[.1em]"><?= esc_html( com\theme::remove_accents( __( 'Διεύθυνση χρέωσης', 'com-theme' ) ) ) ?></h3>
            </div>

            <address class="text-[1.3rem] not-italic leading-[1.5]">
                <?= wp_kses_post( $order->get_formatted_billing_address( esc_html__( 'Δεν έχει οριστεί ακόμη.', 'com-theme' ) ) ) ?>

                <?php
                /**
                 * Fires after the billing address in the order customer details.
                 *
                 * @param string   $address_type Address type.
                 * @param WC_Order $order        Order object.
                 */
                do_action( 'woocommerce_order_details_after_customer_address', 'billing', $order );
                ?>
            </address>
        </article>

        <?php if ( $show_shipping ) : ?>
            <article class="min-h-[18rem] rounded-[1.2rem] border border-blue/15 p-20 md:p-25">
                <div class="mb-20 flex items-start gap-10 text-blue-soft">

                    <h3 class="m-0 text-[1.1rem] font-bold tracking-[.1em]"><?= esc_html( com\theme::remove_accents( __( 'Διεύθυνση αποστολής', 'com-theme' ) ) ) ?></h3>
                </div>

                <address class="text-[1.3rem] not-italic leading-[1.5]">
                    <?= wp_kses_post( $order->get_formatted_shipping_address( esc_html__( 'Δεν έχει οριστεί ακόμη.', 'com-theme' ) ) ) ?>

                    <?php
                    /**
                     * Fires after the shipping address in the order customer details.
                     *
                     * @param string   $address_type Address type.
                     * @param WC_Order $order        Order object.
                     */
                    do_action( 'woocommerce_order_details_after_customer_address', 'shipping', $order );
                    ?>
                </address>
            </article>
        <?php endif; ?>
    </div>

    <?php do_action( 'woocommerce_order_details_after_customer_details', $order ); ?>
</section>
