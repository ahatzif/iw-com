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

                    <?php
                    $ticket_item_ids = [];

                    foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
                        $item_type  = (string) $item->get_meta( 'iw_item_type', true );
                        $content_id = absint( $item->get_meta( 'tickets_for_id', true ) );

                        if ( $item_type === 'tickets' || $content_id > 0 ) {
                            $ticket_item_ids[] = absint( $item_id );
                        }
                    }
                    ?>

                    <?php if ( $ticket_item_ids ) : ?>
                        <section class="mt-50 border-t border-dashed border-blue/20 pt-50" aria-labelledby="guest-order-tickets-title">
                            <header class="mb-30">
                                <p class="mb-10 text-[1rem] font-medium tracking-[.16em] text-blue/50">
                                    <?= esc_html( com\theme::remove_accents( __( 'Είσοδος στο μουσείο', 'com-theme' ) ) ) ?>
                                </p>
                                <h2 id="guest-order-tickets-title" class="m-0 text-[2.4rem] font-bold leading-[1.15]">
                                    <?= esc_html__( 'Τα εισιτήριά σας', 'com-theme' ) ?>
                                </h2>
                                <p class="mb-0 mt-10 text-[1.4rem] leading-[1.5] text-blue/65">
                                    <?= esc_html__( 'Έχετε διαθέσιμο το QR κάθε εισιτηρίου, καθώς και επιλογές Wallet ή PDF.', 'com-theme' ) ?>
                                </p>
                            </header>

                            <div class="space-y-30">
                                <?php foreach ( $ticket_item_ids as $ticket_item_id ) : ?>
                                    <?php
                                    wc_get_template(
                                        'myaccount/tickets-order.php',
                                        [
                                            'order_item_id' => $ticket_item_id,
                                            'embedded'      => true,
                                            'verified_order' => $order,
                                        ]
                                    );
                                    ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    <?php endif; ?>

    <?php if ( $order->has_status( 'failed' ) ) : ?>
        <?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
        <?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
    <?php endif; ?>
</div>
