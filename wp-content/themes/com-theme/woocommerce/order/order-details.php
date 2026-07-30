<?php
/**
 * Order details.
 *
 * @package com-theme
 * @version 10.9.0
 *
 * @var bool $show_downloads Whether downloads should be displayed.
 */

defined( 'ABSPATH' ) || exit;

$order = wc_get_order( $order_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

if ( ! $order ) {
    return;
}

$order_items = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
$downloads = $order->get_downloadable_items();
$show_customer_details = $order->get_user_id() === get_current_user_id();
$actions = array_filter(
    wc_get_account_orders_actions( $order ),
    static fn ( string $key ): bool => 'view' !== $key,
    ARRAY_FILTER_USE_KEY
);

if ( $show_downloads ) {
    wc_get_template(
        'order/order-downloads.php',
        [
            'downloads'  => $downloads,
            'show_title' => true,
        ]
    );
}

$payment_method = trim( (string) $order->get_payment_method_title() );
if ( $order->get_payment_method() === 'cardlink_payment_gateway_woocommerce' ) {
    $payment_method = __( 'Πιστωτική / χρεωστική κάρτα', 'com-theme' );
} elseif ( $order->get_payment_method() === 'cardlink_payment_gateway_woocommerce_iris' ) {
    $payment_method = __( 'IRIS', 'com-theme' );
}

$order_totals = $order->get_order_item_totals();
$additional_totals = array_diff_key(
    $order_totals,
    array_flip( [ 'cart_subtotal', 'order_total', 'payment_method' ] )
);
$hold_state = class_exists( 'CPT_As_Product' ) && method_exists( 'CPT_As_Product', 'get_order_ticket_hold_state' )
    ? CPT_As_Product::get_order_ticket_hold_state( $order )
    : [ 'has_ticket_items' => false, 'is_active' => true ];
$is_expired_payment = $order->has_status( [ 'pending', 'failed' ] )
    && ! empty( $hold_state['has_ticket_items'] )
    && empty( $hold_state['is_active'] );
$status_label = $is_expired_payment
    ? __( 'Η κράτηση έληξε', 'com-theme' )
    : ( $order->has_status( 'pending' ) ? __( 'Αναμένει πληρωμή', 'com-theme' ) : wc_get_order_status_name( $order->get_status() ) );
?>

<section class="woocommerce-order-details account-order-summary">
    <?php do_action( 'woocommerce_order_details_before_order_table', $order ); ?>

    <div class="overflow-hidden rounded-[1.2rem] border border-blue/15 bg-white">
        <div class="px-20 pt-20 md:px-25 md:pt-25">
            <h2 class="m-0 text-[1.1rem] font-bold tracking-[.1em] text-blue-soft"><?= esc_html( com\theme::remove_accents( __( 'Εισιτήρια', 'com-theme' ) ) ) ?></h2>
        </div>
        <div>
            <?php do_action( 'woocommerce_order_details_before_order_table_items', $order ); ?>

            <?php $visible_item_index = 0; ?>
            <?php foreach ( $order_items as $item_id => $item ) : ?>
                <?php
                if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
                    continue;
                }

                $product = $item->get_product();
                $item_type = (string) $item->get_meta( 'iw_item_type', true );
                $content_id = absint( $item->get_meta( 'tickets_for_id', true ) );
                $is_ticket = $item_type === 'tickets' || $content_id > 0;
                $is_all_museums_ticket = $content_id ? com_theme_is_all_museums_ticket( $content_id ) : false;
                $title = trim( (string) $item->get_meta( 'iw_title', true ) );
                $title = $title !== '' ? $title : ( $content_id ? get_the_title( $content_id ) : $item->get_name() );
                $image_id = $content_id ? com_theme_museum_image_id( $content_id ) : ( $product ? absint( $product->get_image_id() ) : 0 );
                $location = $content_id ? com_theme_museum_location_label( $content_id ) : '';
                $date_raw = trim( (string) $item->get_meta( 'tickets_day', true ) );
                $date_timestamp = $date_raw !== '' ? strtotime( $date_raw ) : false;
                $date = $date_timestamp ? date_i18n( 'd/m/Y', $date_timestamp ) : $date_raw;
                $time = str_replace( '-', ':', trim( (string) $item->get_meta( 'tickets_time', true ) ) );
                $ticket_count = max( 1, (int) $item->get_meta( 'tickets_total', true ) );
                $visitors = json_decode( (string) $item->get_meta( 'tickets_visitors', true ), true );
                $visitors = is_array( $visitors ) ? $visitors : [];
                $quantity = max( 1, (int) $item->get_quantity() );
                ?>

                <article class="<?= $visible_item_index > 0 ? 'border-t border-blue/15 ' : '' ?>px-20 py-20 md:px-25 md:py-25">
                    <div class="flex flex-col gap-20 md:flex-row md:items-center">
                        <div class="min-w-0 flex-1">
                            <h3 class="m-0 text-[1.3rem] font-bold leading-[1.5]"><?= esc_html( $title ) ?></h3>
                            <p class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-4 text-[.9rem] leading-[1.45]">
                                <span><?= esc_html( com\theme::remove_accents( mb_strtoupper( wc_format_datetime( $order->get_date_created(), 'l, j F · H:i' ), 'UTF-8' ) ) ) ?></span>
                                <span aria-hidden="true">·</span>
                                <span><?= esc_html( com\theme::remove_accents(
                                    $is_ticket
                                        ? sprintf( _n( '%d εισιτήριο', '%d εισιτήρια', $ticket_count, 'com-theme' ), $ticket_count )
                                        : sprintf( __( 'Ποσότητα: %d', 'com-theme' ), $quantity )
                                ) ) ?></span>
                                <strong class="font-bold"><?= wp_kses_post( $order->get_formatted_line_subtotal( $item ) ) ?></strong>
                            </p>
                        </div>

                        <div class="shrink-0 text-[1.6rem] font-bold"><?= wp_kses_post( $order->get_formatted_line_subtotal( $item ) ) ?></div>
                    </div>
                </article>
                <?php $visible_item_index++; ?>
            <?php endforeach; ?>

            <?php do_action( 'woocommerce_order_details_after_order_table_items', $order ); ?>
        </div>

        <?php if ( $order->get_customer_note() ) : ?>
            <div class="border-t border-blue/15 bg-ochre-light p-20 text-[1.3rem] leading-[1.5] md:px-25">
                <strong class="mb-8 block"><?= esc_html__( 'Σημείωση', 'com-theme' ) ?></strong>
                <?= wp_kses( nl2br( wc_wptexturize_order_note( $order->get_customer_note() ) ), [ 'br' => [] ] ) ?>
            </div>
        <?php endif; ?>

    </div>

    <?php if ( $show_customer_details ) : ?>
        <?php wc_get_template( 'order/order-details-customer.php', [ 'order' => $order ] ); ?>
    <?php endif; ?>

    <div class="mt-20 grid gap-15 md:grid-cols-2">
        <section class="rounded-[1.2rem] border border-blue/15 p-20 md:p-25">
            <h2 class="m-0 text-[1.2rem] font-bold tracking-[.12em] text-blue-soft"><?= esc_html( com\theme::remove_accents( __( 'Σύνοψη αγοράς', 'com-theme' ) ) ) ?></h2>

            <dl class="m-0 mt-20 space-y-12">
                <div class="flex items-center justify-between gap-20">
                    <dt class="text-[1.2rem] text-blue/60"><?= esc_html__( 'Αξία εισιτηρίων', 'com-theme' ) ?></dt>
                    <dd class="m-0 text-[1.3rem]"><?= wp_kses_post( $order->get_subtotal_to_display() ) ?></dd>
                </div>

                <?php foreach ( $additional_totals as $total ) : ?>
                    <div class="flex items-center justify-between gap-20">
                        <dt class="text-[1.2rem] text-blue/60"><?= esc_html( wp_strip_all_tags( $total['label'] ) ) ?></dt>
                        <dd class="m-0 text-right text-[1.3rem]"><?= wp_kses_post( $total['value'] ) ?></dd>
                    </div>
                <?php endforeach; ?>

                <div class="flex items-center justify-between gap-20 border-t border-blue/15 pt-15">
                    <dt class="text-[1.3rem] font-bold"><?= esc_html__( 'Σύνολο', 'com-theme' ) ?></dt>
                    <dd class="m-0 text-[1.8rem] font-bold"><?= wp_kses_post( $order->get_formatted_order_total() ) ?></dd>
                </div>
            </dl>
        </section>

        <section class="rounded-[1.2rem] border border-blue/15 p-20 md:p-25">
            <h2 class="m-0 text-[1.2rem] font-bold tracking-[.12em] text-blue-soft"><?= esc_html( com\theme::remove_accents( __( 'Στοιχεία αγοράς', 'com-theme' ) ) ) ?></h2>

            <dl class="m-0 mt-20">
                <?php if ( $payment_method ) : ?>
                    <div>
                        <dd class="m-0 text-[1.3rem] leading-[1.5]"><?= esc_html( $payment_method ) ?></dd>
                    </div>
                <?php endif; ?>

                <div>
                    <dd class="m-0 text-[1.3rem] leading-[1.5]"><?= esc_html( wc_format_datetime( $order->get_date_created(), 'd/m/Y, H:i' ) ) ?></dd>
                </div>

                <div class="mt-15 border-t border-blue/15 pt-15">
                    <dt class="text-[1.3rem] font-bold"><?= esc_html( $status_label ) ?></dt>
                    <?php if ( $is_expired_payment ) : ?>
                        <dd class="m-0 mt-8 text-[1.05rem] leading-[1.4] text-blue/55"><?= esc_html__( 'Τα εισιτήρια αποδεσμεύτηκαν. Ξεκινήστε νέα αγορά για να ελέγξετε ξανά τη διαθεσιμότητα.', 'com-theme' ) ?></dd>
                    <?php endif; ?>
                </div>
            </dl>
        </section>
    </div>

    <?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>

    <?php if ( $actions ) : ?>
        <footer class="mt-20 flex flex-wrap justify-end gap-10">
            <?php foreach ( $actions as $key => $action ) : ?>
                <a
                    href="<?= esc_url( $action['url'] ) ?>"
                    class="<?= $key === 'pay'
                        ? 'inline-flex min-h-40 items-center justify-center rounded-[.6rem] border border-blue bg-blue px-20 text-[1.2rem] font-bold text-white transition-colors hover:bg-transparent hover:text-blue'
                        : 'inline-flex min-h-40 items-center justify-center rounded-[.6rem] border border-blue/25 px-20 text-[1.2rem] font-bold text-blue transition-colors hover:border-blue' ?>"
                    data-barba-prevent
                    aria-label="<?= esc_attr( $action['aria-label'] ?? $action['name'] ) ?>"
                ><?= esc_html( $action['name'] ) ?></a>
            <?php endforeach; ?>
        </footer>
    <?php endif; ?>
</section>

<?php
do_action( 'woocommerce_after_order_details', $order );
