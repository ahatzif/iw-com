<?php
/**
 * Checkout confirmation.
 *
 * Signed-in customers continue to their account order after this template has
 * run the WooCommerce thank-you lifecycle. Guests stay here and receive the
 * same order-detail presentation without requiring an account.
 *
 * @package com-theme
 * @version 8.1.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order">
    <?php if ( ! $order ) : ?>
        <?php wc_get_template( 'checkout/order-received.php', [ 'order' => false ] ); ?>
        <?php return; ?>
    <?php endif; ?>

    <?php do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

    <?php if ( $order->has_status( 'failed' ) ) : ?>
        <main class="min-h-screen bg-blue px-1/12 pb-100 pt-[14rem] text-ochre md:px-1/24 md:pb-120 md:pt-[18rem] lg:px-2/24">
            <div>
                <header class="mb-50 border-b border-white pb-40 md:mb-60 md:pb-50">
                    <p class="mb-15 text-[1rem] font-medium tracking-[.18em] text-ochre/60">
                        <?= esc_html( com\theme::remove_accents( __( 'Ολοκλήρωση αγοράς', 'com-theme' ) ) ) ?>
                    </p>
                    <h1 class="m-0 text-[4rem] font-medium leading-[1.05] md:text-[6rem]">
                        <?= esc_html__( 'Η πληρωμή δεν ολοκληρώθηκε', 'com-theme' ) ?>
                    </h1>
                </header>

                <div class="rounded-[1.5rem] bg-white p-20 md:p-40 lg:p-60">
                    <p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed">
                        <?= esc_html__( 'Η συναλλαγή δεν ολοκληρώθηκε. Μπορείτε να δοκιμάσετε ξανά την πληρωμή.', 'com-theme' ) ?>
                    </p>
                    <p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
                        <a href="<?= esc_url( $order->get_checkout_payment_url() ) ?>" class="button pay">
                            <?= esc_html__( 'Επανάληψη πληρωμής', 'com-theme' ) ?>
                        </a>
                        <?php if ( is_user_logged_in() ) : ?>
                            <a href="<?= esc_url( wc_get_page_permalink( 'myaccount' ) ) ?>" class="button pay">
                                <?= esc_html__( 'Ο λογαριασμός μου', 'com-theme' ) ?>
                            </a>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </main>
    <?php else : ?>
        <main class="min-h-screen bg-blue px-1/12 pb-100 pt-[14rem] text-ochre md:px-1/24 md:pb-120 md:pt-[18rem] lg:px-2/24">
            <div>
                <header class="mb-50 border-b border-white pb-40 md:mb-60 md:pb-50">
                    <p class="mb-15 text-[1rem] font-medium tracking-[.18em] text-ochre/60">
                        <?= esc_html( com\theme::remove_accents( __( 'Η αγορά ολοκληρώθηκε', 'com-theme' ) ) ) ?>
                    </p>
                    <h1 class="m-0 text-[4rem] font-medium leading-[1.05] md:text-[6rem]">
                        <?= esc_html__( 'Ευχαριστούμε', 'com-theme' ) ?>
                    </h1>
                    <p class="mb-0 mt-20 max-w-[70rem] text-[1.6rem] leading-[1.35] md:text-[2rem]">
                        <?= esc_html(
                            sprintf(
                                /* translators: %s: order number */
                                __( 'Η παραγγελία σας #%s καταχωρήθηκε με επιτυχία. Παρακάτω θα βρείτε όλες τις λεπτομέρειες της αγοράς σας.', 'com-theme' ),
                                $order->get_order_number()
                            )
                        ) ?>
                    </p>
                </header>

                <section class="rounded-[1.5rem] bg-white p-20 text-blue md:p-40 lg:p-60">
                    <?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
                    <?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
                </section>
            </div>
        </main>
    <?php endif; ?>

    <?php if ( $order->has_status( 'failed' ) ) : ?>
        <?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
        <?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
    <?php endif; ?>
</div>
