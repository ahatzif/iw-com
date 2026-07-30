<?php
/**
 * Customer orders.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_orders', $has_orders );

get_template_part( 'woocommerce/myaccount/page-title', null, [
    'eyebrow'     => __( 'Ιστορικό', 'com-theme' ),
    'title'       => __( 'Οι αγορές μου', 'com-theme' ),
    'description' => __( 'Δείτε την κατάσταση και τις λεπτομέρειες όλων των αγορών σας.', 'com-theme' ),
] );
?>

<?php if ( $has_orders ) : ?>
    <div class="space-y-25">
        <?php foreach ( $customer_orders->orders as $customer_order ) :
            $order = wc_get_order( $customer_order );
            if ( ! $order ) {
                continue;
            }

            $items = $order->get_items();
            $ticket_count = 0;

            foreach ( $items as $item ) {
                $quantity = max( 1, (int) $item->get_meta( 'tickets_total', true ) );
                $ticket_count += $quantity;
            }

            if ( $ticket_count <= 0 ) {
                $ticket_count = max( 0, $order->get_item_count() - $order->get_item_count_refunded() );
            }

            $hold_state = class_exists( 'CPT_As_Product' ) && method_exists( 'CPT_As_Product', 'get_order_ticket_hold_state' )
                ? CPT_As_Product::get_order_ticket_hold_state( $order )
                : [ 'has_ticket_items' => false, 'is_active' => true ];
            $is_expired_payment = $order->has_status( [ 'pending', 'failed' ] )
                && ! empty( $hold_state['has_ticket_items'] )
                && empty( $hold_state['is_active'] );
            $status_label = $is_expired_payment
                ? __( 'Η κράτηση έληξε', 'com-theme' )
                : ( $order->has_status( 'pending' ) ? __( 'Αναμένει πληρωμή', 'com-theme' ) : wc_get_order_status_name( $order->get_status() ) );
            $actions = wc_get_account_orders_actions( $order );
            $pay_action = $actions['pay'] ?? null;
            $view_action = $actions['view'] ?? null;
            $status_is_positive = $order->has_status( [ 'processing', 'completed' ] );
            $status_text_class = $status_is_positive ? 'text-validated' : 'text-limited';
            $date_label = mb_strtoupper(
                com\theme::remove_accents( wc_format_datetime( $order->get_date_created(), 'l, j F · H:i' ) ),
                'UTF-8'
            );
            $status_label_upper = mb_strtoupper( com\theme::remove_accents( $status_label ), 'UTF-8' );
            $ticket_count_label = mb_strtoupper(
                com\theme::remove_accents(
                    sprintf( _n( '%d εισιτήριο', '%d εισιτήρια', $ticket_count, 'com-theme' ), $ticket_count )
                ),
                'UTF-8'
            );
        ?>
            <article class="relative overflow-hidden rounded-[1.2rem] border border-blue/15 bg-white">
                <div class="grid min-h-[17rem] md:grid-cols-[minmax(0,1fr)_14rem] lg:grid-cols-[minmax(0,1fr)_17rem]">
                    <div class="flex min-w-0 flex-col px-20 py-25 md:px-25 lg:px-35 lg:py-30">


                        <h2 class="text-[2rem] font-bold leading-none">
                            #<?= esc_html( $order->get_order_number() ) ?>
                        </h2>

                        <p class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-4 text-[.9rem] leading-[1.45]">
                            <span><?= esc_html( $date_label ) ?></span>
                            <span aria-hidden="true">·</span>
                            <span><?= esc_html( $ticket_count_label ) ?></span>
                            <strong class="font-bold"><?= wp_kses_post( $order->get_formatted_order_total() ) ?></strong>
                        </p>
                        <p class="mt-5  flex flex-wrap items-center gap-x-8 gap-y-4 text-[.9rem] font-medium leading-[1.3] tracking-[.22em]">
                            <span class="<?= esc_attr( $status_text_class ) ?>"><?= esc_html( $status_label_upper ) ?></span>
                        </p>

                        <?php if ( $view_action ) : ?>
                            <a
                                href="<?= esc_url( $view_action['url'] ) ?>"
                                data-barba-prevent
                                data-account-pages="link"
                                class="mt-25 inline-flex w-fit text-[1.05rem] font-medium text-blue-soft transition-colors hover:text-blue md:mt-auto"
                            >
                                <?= esc_html__( 'Περισσότερα →', 'com-theme' ) ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <footer class="flex min-h-[12rem] flex-col justify-between px-20 py-25 md:min-h-0 md:px-25 lg:px-30 lg:py-30">
                        <?php if ( $pay_action ) : ?>
                            <a
                                href="<?= esc_url( $pay_action['url'] ) ?>"
                                data-barba-prevent
                                class="inline-flex min-h-40 w-fit items-center self-end rounded-[.6rem] border border-blue px-20 text-[1.2rem] transition-colors hover:bg-blue hover:text-white"
                            >
                                <span><?= esc_html( $pay_action['name'] ) ?></span>
                            </a>
                        <?php else : ?>
                            <span aria-hidden="true"></span>
                        <?php endif; ?>
                    </footer>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ( 1 < $customer_orders->max_num_pages ) : ?>
        <nav class="mt-40 flex items-center justify-between gap-20 border-t border-blue/20 pt-25" aria-label="<?= esc_attr__( 'Σελιδοποίηση αγορών', 'com-theme' ) ?>">
            <?php if ( 1 !== $current_page ) : ?>
                <a href="<?= esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ) ?>" data-barba-prevent data-account-pages="link" class="text-[1.4rem] underline underline-offset-4">← <?= esc_html__( 'Προηγούμενη', 'com-theme' ) ?></a>
            <?php else : ?><span></span><?php endif; ?>
            <?php if ( (int) $customer_orders->max_num_pages !== (int) $current_page ) : ?>
                <a href="<?= esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ) ?>" data-barba-prevent data-account-pages="link" class="text-[1.4rem] underline underline-offset-4"><?= esc_html__( 'Επόμενη', 'com-theme' ) ?> →</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php else : ?>
    <div class="flex flex-wrap items-center gap-15 rounded-[1.2rem] bg-blue p-20 text-white md:px-25">
        <svg class="h-[2.6rem] w-[3.1rem] shrink-0" aria-hidden="true"><use xlink:href="#icon-com-ticket-light"></use></svg>
        <h3 class="m-0 min-w-0 flex-1 text-[1.6rem] font-bold"><?= esc_html__( 'Δεν έχετε κάνει ακόμα κάποια αγορά', 'com-theme' ) ?></h3>
        <a href="<?= esc_url( com_theme_page_url( 'buy-tickets' ) ) ?>" class="inline-flex min-h-40 items-center justify-center rounded-[.6rem] border border-white px-15 text-[1.2rem] font-bold text-white"><?= esc_html__( 'Αγορά εισιτηρίων', 'com-theme' ) ?> →</a>
    </div>
<?php endif; ?>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
