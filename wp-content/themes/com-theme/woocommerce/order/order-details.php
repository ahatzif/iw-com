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
$status_is_positive = $order->has_status( [ 'processing', 'completed' ] );
$status_class = $status_is_positive ? 'text-validated' : 'text-limited';
?>

<section class="woocommerce-order-details account-order-summary">
    <?php do_action( 'woocommerce_order_details_before_order_table', $order ); ?>

    <div class="overflow-hidden rounded-[1.2rem] border border-blue/15 bg-white">
        <header class="flex flex-col gap-20 px-20 py-25 md:flex-row md:items-start md:justify-between md:px-25">
            <div>
                <p class="m-0 text-[1rem] font-bold tracking-[.12em] text-blue-soft"><?= esc_html( com\theme::remove_accents( __( 'Κατάσταση αγοράς', 'com-theme' ) ) ) ?></p>
                <h2 class="mt-8 text-[2.2rem] font-bold <?= esc_attr( $status_class ) ?>"><?= esc_html( $status_label ) ?></h2>
                <p class="mt-8 text-[1.2rem] text-blue/60">
                    #<?= esc_html( $order->get_order_number() ) ?>
                    <span aria-hidden="true">·</span>
                    <?= esc_html( wc_format_datetime( $order->get_date_created(), 'd/m/Y' ) ) ?>
                </p>

                <?php if ( $order->has_status( 'pending' ) && ! $is_expired_payment ) : ?>
                    <p class="mt-15 max-w-[48rem] text-[1.2rem] leading-[1.4] text-blue/65"><?= esc_html__( 'Ολοκληρώστε την πληρωμή για να εκδοθούν τα εισιτήριά σας.', 'com-theme' ) ?></p>
                <?php elseif ( $is_expired_payment ) : ?>
                    <p class="mt-15 max-w-[48rem] text-[1.2rem] leading-[1.4] text-blue/65"><?= esc_html__( 'Τα εισιτήρια αποδεσμεύτηκαν. Ξεκινήστε νέα αγορά για να ελέγξετε ξανά τη διαθεσιμότητα.', 'com-theme' ) ?></p>
                <?php endif; ?>
            </div>

            <?php if ( $actions ) : ?>
                <div class="flex shrink-0 flex-wrap items-center gap-10">
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
                </div>
            <?php endif; ?>
        </header>

        <div class="border-t border-blue/15">
            <div class="flex items-center justify-between gap-20 px-20 py-15 md:px-25">
                <h3 class="m-0 text-[1.1rem] font-bold tracking-[.1em] text-blue-soft"><?= esc_html( com\theme::remove_accents( __( 'Εισιτήρια', 'com-theme' ) ) ) ?></h3>
                <span class="text-[1.1rem] text-blue/50">
                    <?= esc_html(
                        sprintf(
                            _n( '%d επιλογή', '%d επιλογές', count( $order_items ), 'com-theme' ),
                            count( $order_items )
                        )
                    ) ?>
                </span>
            </div>
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

                <article class="<?= $visible_item_index > 0 ? 'border-t border-blue/15 ' : '' ?>px-20 py-20 md:px-25">
                    <div class="flex flex-col gap-20 md:flex-row md:items-center">
                        <?php if ( $is_ticket ) : ?>
                            <div class="relative h-[9rem] w-full shrink-0 overflow-hidden rounded-[.8rem] bg-ochre-light md:w-[12rem]">
                                <?php if ( $is_all_museums_ticket ) : ?>
                                    <?php get_template_part( 'templates/parts/all-museums-art' ); ?>
                                <?php elseif ( $image_id ) : ?>
                                    <?php get_template_part( 'templates/parts/image', null, [
                                        'id'       => $image_id,
                                        'size'     => 'medium',
                                        'classes'  => '!h-full !w-full object-cover',
                                        'parallax' => false,
                                    ] ); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="min-w-0 flex-1">
                            <?php if ( $location ) : ?>
                                <p class="m-0 text-[1rem] leading-none tracking-[.3em] text-blue-soft"><?= esc_html( com\theme::remove_accents( $location ) ) ?></p>
                            <?php endif; ?>

                            <h3 class="<?= $location ? 'mt-8 ' : '' ?>m-0 text-[1.6rem] font-bold leading-[1.2]"><?= esc_html( $title ) ?></h3>

                            <div class="mt-12 flex flex-wrap gap-x-12 gap-y-8 text-[1.15rem] text-blue/60">
                                <?php if ( $date ) : ?><span><?= esc_html( $date ) ?></span><?php endif; ?>
                                <?php if ( $time ) : ?><span><?= esc_html( sprintf( __( 'Ώρα %s', 'com-theme' ), $time ) ) ?></span><?php endif; ?>
                                <span><?= esc_html(
                                    $is_ticket
                                        ? sprintf( _n( '%d εισιτήριο', '%d εισιτήρια', $ticket_count, 'com-theme' ), $ticket_count )
                                        : sprintf( __( 'Ποσότητα: %d', 'com-theme' ), $quantity )
                                ) ?></span>
                            </div>

                            <?php if ( $is_ticket && $visitors ) : ?>
                                <div class="mt-15 flex flex-wrap gap-x-15 gap-y-8 text-[1.1rem]">
                                    <?php foreach ( $visitors as $visitor_index => $visitor ) : ?>
                                        <?php
                                        if ( ! is_array( $visitor ) ) {
                                            continue;
                                        }
                                        $visitor_name = trim( (string) ( $visitor['first'] ?? '' ) . ' ' . (string) ( $visitor['last'] ?? '' ) );
                                        $category = trim( (string) ( $visitor['category-name'] ?? $visitor['category_name'] ?? __( 'Εισιτήριο', 'com-theme' ) ) );
                                        ?>
                                        <span class="rounded-full bg-ochre-light px-10 py-6">
                                            <strong><?= esc_html( $visitor_name !== '' ? $visitor_name : sprintf( __( 'Επισκέπτης %d', 'com-theme' ), $visitor_index + 1 ) ) ?></strong>
                                            <span class="text-blue/60"> · <?= esc_html( $category ) ?></span>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
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

            <dl class="m-0 mt-20 grid gap-20 sm:grid-cols-2 md:grid-cols-1 lg:grid-cols-2">
                <?php if ( $payment_method ) : ?>
                    <div>
                        <dt class="text-[1rem] tracking-[.1em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Τρόπος πληρωμής', 'com-theme' ) ) ) ?></dt>
                        <dd class="m-0 mt-8 text-[1.25rem] font-bold leading-[1.35]"><?= esc_html( $payment_method ) ?></dd>
                    </div>
                <?php endif; ?>

                <div>
                    <dt class="text-[1rem] tracking-[.1em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Ημερομηνία αγοράς', 'com-theme' ) ) ) ?></dt>
                    <dd class="m-0 mt-8 text-[1.25rem] font-bold"><?= esc_html( wc_format_datetime( $order->get_date_created(), 'd/m/Y, H:i' ) ) ?></dd>
                </div>

                <div>
                    <dt class="text-[1rem] tracking-[.1em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Αριθμός αγοράς', 'com-theme' ) ) ) ?></dt>
                    <dd class="m-0 mt-8 text-[1.25rem] font-bold">#<?= esc_html( $order->get_order_number() ) ?></dd>
                </div>

                <div>
                    <dt class="text-[1rem] tracking-[.1em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Κατάσταση', 'com-theme' ) ) ) ?></dt>
                    <dd class="m-0 mt-8 text-[1.25rem] font-bold <?= esc_attr( $status_class ) ?>"><?= esc_html( $status_label ) ?></dd>
                </div>
            </dl>
        </section>
    </div>

    <?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>
</section>

<?php
do_action( 'woocommerce_after_order_details', $order );

if ( $show_customer_details ) {
    wc_get_template( 'order/order-details-customer.php', [ 'order' => $order ] );
}
