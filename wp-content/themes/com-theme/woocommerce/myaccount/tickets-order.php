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

$is_reservation = false;
$ticket_bundle = class_exists( 'IW_Ticketing' ) ? IW_Ticketing::get_order_tickets( $order_item_id ) : [];

if ( empty( $ticket_bundle ) && class_exists( 'IW_Ticketing' ) ) {
    $ticket_bundle = IW_Ticketing::get_reservation_order_tickets( $order_item_id );
    $is_reservation = true;
}

get_template_part( 'woocommerce/myaccount/page-title', null, [
    'eyebrow'     => __( 'Είσοδος στο μουσείο', 'com-theme' ),
    'title'       => __( 'Τα εισιτήριά σας', 'com-theme' ),
    'description' => __( 'Έχετε διαθέσιμο το QR κάθε εισιτηρίου, καθώς και επιλογές Wallet ή PDF.', 'com-theme' ),
] );
?>

<a href="<?= esc_url( wc_get_account_endpoint_url( 'tickets' ) ) ?>" data-barba-prevent data-account-pages="link" class="mb-30 inline-flex text-[1.3rem] underline underline-offset-4">← <?= esc_html__( 'Πίσω στα εισιτήρια', 'com-theme' ) ?></a>

<?php if ( empty( $ticket_bundle ) ) : ?>
    <div class="rounded-[1.2rem] bg-ochre-light p-25 text-[1.5rem]"><?= esc_html__( 'Δεν ήταν δυνατή η φόρτωση αυτών των εισιτηρίων.', 'com-theme' ) ?></div>
    <?php return; ?>
<?php endif; ?>

<?php
$order_data = $ticket_bundle['order'] ?? [];

if ( $order_data ) {
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
            <article class="relative overflow-hidden rounded-[1.2rem] border border-blue/15 bg-white">
                <div class="border-b border-dashed border-blue/25 px-20 py-15 md:px-25">
                    <div class="flex items-center justify-between gap-20">
                        <span class="text-[1.1rem] font-bold tracking-[.14em] text-blue-soft"><?= esc_html( com\theme::remove_accents( sprintf( __( 'Εισιτήριο %d', 'com-theme' ), $index + 1 ) ) ) ?></span>
                        <span class="text-[1rem] text-blue/50">#<?= esc_html( $reference ) ?></span>
                    </div>
                </div>

                <div class="grid gap-25 p-20 md:grid-cols-[1fr_auto] md:items-center md:p-25">
                    <dl class="grid gap-x-30 gap-y-20 sm:grid-cols-2">
                        <div>
                            <dt class="text-[.9rem] tracking-[.1em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Ονοματεπώνυμο', 'com-theme' ) ) ) ?></dt>
                            <dd class="m-0 mt-5 text-[1.6rem] font-bold"><?= esc_html( ( $ticket->attendee_name ?? '' ) ?: '—' ) ?></dd>
                        </div>
                        <div>
                            <dt class="text-[.9rem] tracking-[.1em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Κατηγορία', 'com-theme' ) ) ) ?></dt>
                            <dd class="m-0 mt-5 text-[1.6rem] font-bold"><?= esc_html( $price_label ?: '—' ) ?></dd>
                        </div>
                        <div>
                            <dt class="text-[.9rem] tracking-[.1em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Τιμή', 'com-theme' ) ) ) ?></dt>
                            <dd class="m-0 mt-5 text-[1.6rem] font-bold"><?= wp_kses_post( wc_price( isset( $ticket->unit_price ) ? $ticket->unit_price : 0 ) ) ?></dd>
                        </div>
                    </dl>

                    <?php if ( ! $is_reservation ) : ?>
                        <div class="flex flex-col items-center gap-15 sm:flex-row md:flex-col">
                            <?php if ( $qr_data_uri ) : ?>
                                <div class="rounded-[.8rem] border border-blue/15 bg-white p-8">
                                    <img src="<?= esc_attr( $qr_data_uri ) ?>" width="128" height="128" alt="<?= esc_attr__( 'QR εισιτηρίου', 'com-theme' ) ?>" class="size-[12.8rem]">
                                </div>
                            <?php endif; ?>

                            <div class="flex flex-wrap justify-center gap-8">
                                <?php if ( $download_apple_pass_url ) : ?>
                                    <a href="<?= esc_url( $download_apple_pass_url ) ?>" target="_blank" rel="noopener" data-barba-prevent>
                                        <img src="<?= esc_url( get_theme_file_uri( '/assets/images/svg/add-to-apple-wallet.svg' ) ) ?>" alt="<?= esc_attr__( 'Προσθήκη στο Apple Wallet', 'com-theme' ) ?>" class="h-35 w-auto">
                                    </a>
                                <?php endif; ?>
                                <?php if ( $download_google_pass_url ) : ?>
                                    <a href="<?= esc_url( $download_google_pass_url ) ?>" target="_blank" rel="noopener" data-barba-prevent>
                                        <img src="<?= esc_url( get_theme_file_uri( '/assets/images/svg/add-to-google-wallet.svg' ) ) ?>" alt="<?= esc_attr__( 'Προσθήκη στο Google Wallet', 'com-theme' ) ?>" class="h-35 w-auto">
                                    </a>
                                <?php endif; ?>
                                <?php if ( $download_ticket_pdf_url ) : ?>
                                    <a href="<?= esc_url( $download_ticket_pdf_url ) ?>" data-barba-prevent class="inline-flex h-35 items-center gap-8 rounded-[.5rem] border border-blue px-12 text-[1.1rem] font-bold">
                                        <svg class="size-15 fill-current" aria-hidden="true"><use xlink:href="#icon-download"></use></svg>
                                        PDF
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
