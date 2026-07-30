<?php
/**
 * Order customer details.
 *
 * @package com-theme
 * @version 8.7.0
 */

defined( 'ABSPATH' ) || exit;

$show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();
$billing_email = sanitize_email( (string) $order->get_billing_email() );
$billing_phone = trim( (string) $order->get_billing_phone() );
?>

<section class="woocommerce-customer-details account-order-addresses mt-20">
    <div class="grid gap-15<?= $show_shipping ? ' sm:grid-cols-2' : '' ?>">
        <article class="min-h-[18rem] rounded-[1.2rem] border border-blue/15 p-20<?= $show_shipping ? '' : ' sm:col-span-2' ?> md:p-25">
            <div class="mb-20 flex items-start gap-10 text-blue-soft">

                <h3 class="m-0 text-[1.1rem] font-bold tracking-[.1em]"><?= esc_html( com\theme::remove_accents( __( 'Διεύθυνση χρέωσης', 'com-theme' ) ) ) ?></h3>
            </div>

            <address class="border-0 bg-transparent p-0 text-[1.3rem] not-italic leading-[1.5]">
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

            <?php if ( $billing_email !== '' || $billing_phone !== '' ) : ?>
                <dl class="mt-15 space-y-5 border-t border-blue/15 pt-15 text-[1.3rem] leading-[1.5]">
                    <?php if ( $billing_email !== '' ) : ?>
                        <div class="flex flex-wrap gap-x-5">
                            <dt class="text-blue/50"><?= esc_html__( 'Email:', 'com-theme' ) ?></dt>
                            <dd class="m-0">
                                <a href="mailto:<?= esc_attr( $billing_email ) ?>"><?= esc_html( $billing_email ) ?></a>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <?php if ( $billing_phone !== '' ) : ?>
                        <div class="flex flex-wrap gap-x-5">
                            <dt class="text-blue/50"><?= esc_html__( 'Τηλέφωνο:', 'com-theme' ) ?></dt>
                            <dd class="m-0">
                                <a href="tel:<?= esc_attr( preg_replace( '/[^0-9+]/', '', $billing_phone ) ) ?>"><?= esc_html( $billing_phone ) ?></a>
                            </dd>
                        </div>
                    <?php endif; ?>
                </dl>
            <?php endif; ?>
        </article>

        <?php if ( $show_shipping ) : ?>
            <article class="min-h-[18rem] rounded-[1.2rem] border border-blue/15 p-20 md:p-25">
                <div class="mb-20 flex items-start gap-10 text-blue-soft">

                    <h3 class="m-0 text-[1.1rem] font-bold tracking-[.1em]"><?= esc_html( com\theme::remove_accents( __( 'Διεύθυνση αποστολής', 'com-theme' ) ) ) ?></h3>
                </div>

                <address class="border-0 bg-transparent p-0 text-[1.3rem] not-italic leading-[1.5]">
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
