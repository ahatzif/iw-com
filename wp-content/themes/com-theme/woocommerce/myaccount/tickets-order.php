<?php
/**
 * Ticket order detail.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $order_item_id ) ) {
    return;
}

$embedded = ! empty( $embedded );
$is_reservation = false;
$verified_order = $verified_order ?? null;
$ticket_bundle = [];

if ( class_exists( 'IW_Ticketing' ) ) {
    $ticket_bundle = $embedded
        && $verified_order instanceof WC_Order
        && method_exists( 'IW_Ticketing', 'get_order_tickets_for_verified_order' )
            ? IW_Ticketing::get_order_tickets_for_verified_order( $verified_order, $order_item_id )
            : IW_Ticketing::get_order_tickets( $order_item_id );
}

if ( empty( $ticket_bundle ) && class_exists( 'IW_Ticketing' ) ) {
    $ticket_bundle = IW_Ticketing::get_reservation_order_tickets( $order_item_id );
    $is_reservation = true;
}

if ( ! $embedded ) {
    get_template_part( 'woocommerce/myaccount/page-title', null, [
        'eyebrow'     => __( 'Είσοδος στο μουσείο', 'com-theme' ),
        'title'       => __( 'Τα εισιτήριά σας', 'com-theme' ),
        'description' => __( 'Έχετε διαθέσιμο το QR κάθε εισιτηρίου, καθώς και επιλογές Wallet ή PDF.', 'com-theme' ),
    ] );
}
?>

<?php if ( ! $embedded ) : ?>
    <a href="<?= esc_url( wc_get_account_endpoint_url( 'tickets' ) ) ?>" data-barba-prevent data-account-pages="link" class="mb-30 inline-flex text-[1.3rem] underline underline-offset-4">← <?= esc_html__( 'Πίσω στα εισιτήρια', 'com-theme' ) ?></a>
<?php endif; ?>

<?php if ( empty( $ticket_bundle ) ) : ?>
    <div class="rounded-[1.2rem] bg-ochre-light p-25 text-[1.5rem]"><?= esc_html__( 'Δεν ήταν δυνατή η φόρτωση αυτών των εισιτηρίων.', 'com-theme' ) ?></div>
    <?php return; ?>
<?php endif; ?>

<?php
$order_data = $ticket_bundle['order'] ?? [];

if ( $order_data && ! $embedded ) {
    wc_get_template( 'global/account-ticket-card.php', [
        'order'  => $order_data,
        'status' => 'active',
        'size'   => 'large',
    ] );
}

if ( $is_reservation && ! empty( $order_data['order_id'] ) ) {
    $reservation_order = wc_get_order( $order_data['order_id'] );
    $reservation_item = $reservation_order ? $reservation_order->get_item( $order_item_id ) : null;
    if ( $reservation_order && $reservation_item && class_exists( 'IW_WC_Tickets_Cart_Manager' ) ) {
        $ticket_bundle['tickets'] = IW_WC_Tickets_Cart_Manager::build_ticket_rows_from_order_item( $reservation_order, $order_item_id, $reservation_item );
    }
}

$tickets = (array) ( $ticket_bundle['tickets'] ?? [] );
?>

<?php if ( $tickets ) : ?>
    <div class="mt-25 space-y-15">
        <?php foreach ( $tickets as $index => $ticket ) :
            $ticket = is_array( $ticket ) ? (object) $ticket : $ticket;
            $ticket_uuid = ! empty( $ticket->ticket_uuid ) ? (string) $ticket->ticket_uuid : '';
            $download_apple_pass_url = '';
            $download_google_pass_url = '';
            $download_ticket_pdf_url = '';

            if ( $ticket_uuid !== '' && class_exists( 'IW_Apple_Wallet_Tickets_Service' ) && method_exists( 'IW_Apple_Wallet_Tickets_Service', 'build_public_link_signature' ) ) {
                $expires = time() + ( 365 * DAY_IN_SECONDS );
                $signature = IW_Apple_Wallet_Tickets_Service::build_public_link_signature( $ticket_uuid, $expires );
                $download_apple_pass_url = add_query_arg( [
                    'ticket_uuid' => $ticket_uuid,
                    'exp'         => $expires,
                    'sig'         => $signature,
                ], home_url( '/wp-json/iw/v1/apple-wallet/tickets/public-pass' ) );

                if ( class_exists( 'IW_Ticket_PDF_Service' ) && method_exists( 'IW_Ticket_PDF_Service', 'build_public_pdf_url' ) ) {
                    $download_ticket_pdf_url = IW_Ticket_PDF_Service::build_public_pdf_url( $ticket_uuid, $expires );
                }
                if ( class_exists( 'IW_Ticket_PDF_Service' ) && method_exists( 'IW_Ticket_PDF_Service', 'build_google_wallet_url' ) ) {
                    $download_google_pass_url = IW_Ticket_PDF_Service::build_google_wallet_url( $ticket_uuid, $expires );
                }
            }

            $qr_data_uri = ! $is_reservation && class_exists( 'IW_Ticket_PDF_Service' ) && method_exists( 'IW_Ticket_PDF_Service', 'build_ticket_qr_data_uri' )
                ? IW_Ticket_PDF_Service::build_ticket_qr_data_uri( $ticket )
                : '';
            $price_category = (string) ( $ticket->price_category ?? '' );
            $category_parts = explode( ':', $price_category, 2 );
            $price_label = trim( $category_parts[1] ?? $category_parts[0] );
            $reference = $ticket_uuid !== '' ? strtoupper( substr( $ticket_uuid, -8 ) ) : sprintf( '%02d', $index + 1 );
        ?>
            <article class="relative flex flex-col overflow-hidden rounded-[1.5rem] border border-blue/15 bg-white md:flex-row">
                <div class="grid min-w-0 flex-1 gap-25 p-20 md:grid-cols-[minmax(0,1fr)_auto] md:items-center md:p-30 md:pr-0">
                    <div>
                        <div class="mb-20 space-y-6">
                            <p class="m-0 text-[1.1rem] font-bold tracking-[.14em] text-blue"><?= esc_html( com\theme::remove_accents( sprintf( __( 'Εισιτήριο %d', 'com-theme' ), $index + 1 ) ) ) ?></p>
                            <p class="m-0 text-[.9rem] tracking-[.08em] text-blue/45"><?= esc_html( com\theme::remove_accents( __( 'Κωδικός', 'com-theme' ) ) ) ?> #<?= esc_html( $reference ) ?></p>
                        </div>

                        <dl class="space-y-10 text-[1.3rem] leading-[1.25]">
                        <div class="flex flex-wrap items-baseline gap-x-6">
                            <dt class="text-blue/65"><?= esc_html( com\theme::remove_accents( __( 'Όνομ/μο:', 'com-theme' ) ) ) ?></dt>
                            <dd class="m-0 font-bold"><?= esc_html( ( $ticket->attendee_name ?? '' ) ?: '—' ) ?></dd>
                        </div>
                        <div class="flex flex-wrap items-baseline gap-x-6">
                            <dt class="text-blue/65"><?= esc_html( com\theme::remove_accents( __( 'Κατηγορία:', 'com-theme' ) ) ) ?></dt>
                            <dd class="m-0 font-bold"><?= esc_html( $price_label ?: '—' ) ?></dd>
                        </div>
                        <div class="flex flex-wrap items-baseline gap-x-6">
                            <dt class="text-blue/65"><?= esc_html( com\theme::remove_accents( __( 'Τιμή:', 'com-theme' ) ) ) ?></dt>
                            <dd class="m-0 font-bold"><?= wp_kses_post( wc_price( isset( $ticket->unit_price ) ? $ticket->unit_price : 0 ) ) ?></dd>
                        </div>
                        </dl>
                    </div>

                    <?php if ( ! $is_reservation ) : ?>
                        <div class="grid min-w-[10rem] gap-5">
                            <?php if ( $download_apple_pass_url ) : ?>
                                <a href="<?= esc_url( $download_apple_pass_url ) ?>" target="_blank" rel="noopener" data-barba-prevent class="flex items-center justify-center transition-opacity hover:opacity-75">
                                    <img src="<?= esc_url( get_theme_file_uri( '/assets/images/svg/add-to-apple-wallet.svg' ) ) ?>" alt="<?= esc_attr__( 'Προσθήκη στο Apple Wallet', 'com-theme' ) ?>" class="w-auto max-w-full" style="height: 3rem;">
                                </a>
                            <?php endif; ?>
                            <?php if ( $download_google_pass_url ) : ?>
                                <a href="<?= esc_url( $download_google_pass_url ) ?>" target="_blank" rel="noopener" data-barba-prevent class="flex items-center justify-center transition-opacity hover:opacity-75">
                                    <img src="<?= esc_url( get_theme_file_uri( '/assets/images/svg/add-to-google-wallet.svg' ) ) ?>" alt="<?= esc_attr__( 'Προσθήκη στο Google Wallet', 'com-theme' ) ?>" class="w-auto max-w-full" style="height: 3rem;">
                                </a>
                            <?php endif; ?>
                            <?php if ( $download_ticket_pdf_url ) : ?>
                                <a href="<?= esc_url( $download_ticket_pdf_url ) ?>" data-barba-prevent class="flex items-center justify-center transition-opacity hover:opacity-75">
                                    <img src="<?= esc_url( get_theme_file_uri( '/assets/images/svg/download-pdf-file.svg' ) ) ?>" alt="<?= esc_attr__( 'Λήψη εισιτηρίου σε PDF', 'com-theme' ) ?>" class="w-auto max-w-full" style="height: 3rem;">
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ( ! $is_reservation && $qr_data_uri ) : ?>
                    <div class="relative hidden w-[4.4rem] shrink-0 self-stretch md:block" aria-hidden="true">
                        <span class="absolute bottom-[1.8rem] left-1/2 top-[1.8rem] border-l border-dashed border-blue/15"></span>

                        <svg class="absolute left-0 top-[-.1rem] z-20 h-[2rem] w-full overflow-visible text-blue/15" viewBox="0 0 44 20" preserveAspectRatio="none">
                            <rect x="0" y="0" width="44" height="3" fill="white"></rect>
                            <path d="M0 1.5H3A19 18 0 0 0 41 1.5H44" fill="none" stroke="currentColor" stroke-width="1" stroke-linejoin="round"></path>
                        </svg>

                        <svg class="absolute bottom-[-.1rem] left-0 z-20 h-[2rem] w-full overflow-visible text-blue/15" viewBox="0 0 44 20" preserveAspectRatio="none">
                            <rect x="0" y="17" width="44" height="3" fill="white"></rect>
                            <path d="M0 18.5H3A19 18 0 0 1 41 18.5H44" fill="none" stroke="currentColor" stroke-width="1" stroke-linejoin="round"></path>
                        </svg>
                    </div>

                    <div class="flex items-center justify-center border-t border-dashed border-blue/15 p-20 md:w-[16rem] md:justify-start md:border-0 md:pl-0 md:pr-20">
                        <img src="<?= esc_attr( $qr_data_uri ) ?>" width="100" height="100" alt="<?= esc_attr__( 'QR εισιτηρίου', 'com-theme' ) ?>" class="size-[10rem] shrink-0">
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
