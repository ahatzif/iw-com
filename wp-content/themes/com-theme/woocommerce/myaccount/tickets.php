<?php
/**
 * Customer tickets.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
$current_page = preg_match( '#/page/([0-9]+)/?#', $request_uri, $matches ) ? max( 1, absint( $matches[1] ) ) : 1;
$active_orders = class_exists( 'IW_Ticketing' ) ? IW_Ticketing::get_ticket_orders( 'active' ) : [];
$past_orders = class_exists( 'IW_Ticketing' ) ? IW_Ticketing::get_ticket_orders( 'past', 4, $current_page ) : [];
$active_items = is_array( $active_orders['items'] ?? null ) ? $active_orders['items'] : [];
$past_items = is_array( $past_orders['items'] ?? null ) ? $past_orders['items'] : [];
$pagination = $past_orders['pagination'] ?? null;

get_template_part( 'woocommerce/myaccount/page-title', null, [
    'eyebrow'     => __( 'Οι επισκέψεις μου', 'com-theme' ),
    'title'       => __( 'Τα εισιτήριά μου', 'com-theme' ),
    'description' => __( 'Βρείτε τα ενεργά εισιτήρια, τα QR και το ιστορικό των επισκέψεών σας.', 'com-theme' ),
] );
?>

<div class="space-y-50">
    <section>
        <div class="mb-20 flex items-end justify-between gap-20">
            <div>
                <p class="mb-5 text-[1rem] font-medium tracking-[.16em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Έτοιμα για χρήση', 'com-theme' ) ) ) ?></p>
                <h3 class="m-0 text-[2.2rem] font-bold"><?= esc_html__( 'Ενεργά εισιτήρια', 'com-theme' ) ?></h3>
            </div>
            <?php if ( $active_items ) : ?>
                <span class="flex min-w-30 items-center justify-center rounded-full bg-blue px-10 py-6 text-[1.1rem] font-bold text-white"><?= esc_html( (string) count( $active_items ) ) ?></span>
            <?php endif; ?>
        </div>

        <?php if ( $active_items ) : ?>
            <div class="space-y-15">
                <?php foreach ( $active_items as $ticket_order ) : ?>
                    <?php wc_get_template( 'global/account-ticket-card.php', [
                        'order'     => $ticket_order,
                        'order_url' => IW_Ticketing_Endpoints::get_order_item_url( $ticket_order['order_item_id'] ),
                        'status'    => 'active',
                    ] ); ?>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="min-h-[18rem] rounded-[1.2rem] border border-blue/15 bg-white p-20 text-blue md:p-25">
                <div class="flex items-start justify-between gap-20">
                    <div class="flex items-center gap-10 text-blue-soft">
                        <svg class="h-[2.2rem] w-[2.6rem] shrink-0" aria-hidden="true"><use xlink:href="#icon-com-ticket"></use></svg>
                        <h4 class="m-0 text-[1.1rem] font-bold tracking-[.1em]"><?= esc_html( com\theme::remove_accents( __( 'Ενεργά εισιτήρια', 'com-theme' ) ) ) ?></h4>
                    </div>
                </div>
                <p class="mb-0 mt-20 text-[1.3rem] font-normal leading-[1.5]"><?= esc_html__( 'Δεν έχουν εκδοθεί ακόμα εισιτήρια', 'com-theme' ) ?></p>
            </div>
            <div class="mt-15 flex justify-end">
                <?php get_template_part( 'templates/parts/com-button', null, [
                    'href'    => com_theme_page_url( 'buy-tickets' ),
                    'label'   => __( 'Εισιτήρια', 'com-theme' ),
                    'variant' => 'blue-outline',
                    'size'    => 'small',
                    'icon'    => 'com-ticket-button',
                ] ); ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if ( $past_items ) : ?>
        <section class="border-t border-dashed border-blue/25 pt-40" id="account-past-tickets">
            <div class="mb-20">
                <p class="mb-5 text-[1rem] font-medium tracking-[.16em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Ιστορικό', 'com-theme' ) ) ) ?></p>
                <h3 class="m-0 text-[2.2rem] font-bold"><?= esc_html__( 'Προηγούμενα εισιτήρια', 'com-theme' ) ?></h3>
            </div>

            <div class="space-y-15">
                <?php foreach ( $past_items as $ticket_order ) : ?>
                    <?php wc_get_template( 'global/account-ticket-card.php', [
                        'order'     => $ticket_order,
                        'order_url' => IW_Ticketing_Endpoints::get_order_item_url( $ticket_order['order_item_id'] ),
                        'status'    => 'past',
                    ] ); ?>
                <?php endforeach; ?>
            </div>

            <?php if ( $pagination && $pagination->max_num_pages > 1 ) :
                $base_url = trailingslashit( wc_get_account_endpoint_url( 'tickets' ) );
            ?>
                <nav class="mt-30 flex items-center justify-between gap-20 border-t border-blue/15 pt-20" aria-label="<?= esc_attr__( 'Σελιδοποίηση εισιτηρίων', 'com-theme' ) ?>">
                    <?php if ( $current_page > 1 ) :
                        $previous_url = $current_page === 2 ? $base_url : $base_url . 'page/' . ( $current_page - 1 ) . '/';
                    ?>
                        <a href="<?= esc_url( $previous_url ) ?>" data-barba-prevent data-account-pages="link" class="text-[1.3rem] underline underline-offset-4">← <?= esc_html__( 'Προηγούμενα', 'com-theme' ) ?></a>
                    <?php else : ?><span></span><?php endif; ?>

                    <?php if ( $current_page < $pagination->max_num_pages ) : ?>
                        <a href="<?= esc_url( $base_url . 'page/' . ( $current_page + 1 ) . '/' ) ?>" data-barba-prevent data-account-pages="link" class="text-[1.3rem] underline underline-offset-4"><?= esc_html__( 'Επόμενα', 'com-theme' ) ?> →</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
