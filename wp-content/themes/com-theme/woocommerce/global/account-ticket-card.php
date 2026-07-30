<?php
/**
 * Ticket order preview used inside My Account.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

$card_args = wp_parse_args( $args ?? [], [
    'order'     => [],
    'order_url' => '',
    'status'    => 'active',
    'size'      => 'medium',
] );

$ticket_order = $card_args['order'];
$ticket_post = $ticket_order['post'] ?? null;

if ( ! $ticket_post instanceof WP_Post ) {
    return;
}

$is_all_museums_ticket = com_theme_is_all_museums_ticket( $ticket_post->ID );
$image_id = com_theme_museum_image_id( $ticket_post->ID );
$location = com_theme_museum_location_label( $ticket_post->ID );

if ( $is_all_museums_ticket && $location === '' ) {
    $location = com_theme_all_museums_default_location_label();
}
$slot_start = $ticket_order['slot_start'] ?? null;
$ticket_count = absint( $ticket_order['tickets_count'] ?? 0 );
$ticket_data = (
    class_exists( 'IW_Ticketing' )
    && method_exists( 'IW_Ticketing', 'get_order_tickets' )
    && ! empty( $ticket_order['order_item_id'] )
)
    ? IW_Ticketing::get_order_tickets( (int) $ticket_order['order_item_id'] )
    : [];
$tickets = is_array( $ticket_data['tickets'] ?? null ) ? $ticket_data['tickets'] : [];
$ticket_total = 0.0;
$order_url = (string) $card_args['order_url'];
$ticket_url = $order_url ?: get_permalink( $ticket_post );
$date_time = '';
$ticket_count_label = sprintf(
    _n( '%d εισιτήριο', '%d εισιτήρια', $ticket_count, 'com-theme' ),
    $ticket_count
);

if ( $slot_start instanceof DateTimeInterface ) {
    $date_time = wp_date( 'd/m/Y H:i', $slot_start->getTimestamp() );
}

foreach ( $tickets as $ticket ) {
    $ticket = is_array( $ticket ) ? (object) $ticket : $ticket;
    $ticket_total += is_numeric( $ticket->unit_price ?? null ) ? (float) $ticket->unit_price : 0.0;
}
?>

<article class="group flex w-full flex-col overflow-visible rounded-[1.5rem] border border-blue/15 bg-white text-blue md:flex-row md:items-stretch md:px-30">
    <div class="flex min-w-0 flex-1 flex-col md:flex-row md:items-stretch">
        <div class="flex min-w-0 flex-1 flex-col gap-30 p-20 md:flex-row md:items-center md:gap-30 md:px-0 md:py-30">
            <a
                href="<?= esc_url( $ticket_url ) ?>"
                <?= $order_url ? 'data-barba-prevent data-account-pages="link"' : '' ?>
                class="relative aspect-[16/9] w-full shrink-0 overflow-hidden rounded-[1rem] bg-ochre-light md:aspect-[calc(304/220)] md:w-[28%]"
                aria-label="<?= esc_attr( get_the_title( $ticket_post ) ) ?>"
            >
                <?php if ( $is_all_museums_ticket ) : ?>
                    <?php get_template_part( 'templates/parts/all-museums-art' ); ?>
                <?php elseif ( $image_id ) : ?>
                    <?php get_template_part( 'templates/parts/image', null, [
                        'id'       => $image_id,
                        'size'     => 'large',
                        'classes'  => '!h-full !w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]',
                        'parallax' => false,
                    ] ); ?>
                <?php else : ?>
                    <div class="flex size-full items-center justify-center bg-blue text-ochre">
                        <svg class="h-50 w-60 fill-current" aria-hidden="true"><use xlink:href="#icon-com-ticket-light"></use></svg>
                    </div>
                <?php endif; ?>
            </a>

            <div class="flex min-w-0 flex-1 flex-col self-stretch">
                <div class="flex flex-col gap-10">
                    <?php if ( $location ) : ?>
                        <p class="m-0 text-[1rem] font-normal leading-none tracking-[.3em]"><?= esc_html( com\theme::remove_accents( $location ) ) ?></p>
                    <?php endif; ?>

                    <h3 class="m-0 text-[1.6rem] font-bold leading-none">
                        <a href="<?= esc_url( $ticket_url ) ?>" <?= $order_url ? 'data-barba-prevent data-account-pages="link"' : '' ?>><?= esc_html( get_the_title( $ticket_post ) ) ?></a>
                    </h3>
                </div>

                <div class="
                text-[1.2rem] opacity-60 transition-opacity hover:opacity-100  mt-20 flex flex-wrap items-center gap-x-10 gap-y-8 font-normal leading-none text-blue md:mt-auto">
                    <?php if ( $date_time ) : ?>
                        <span><?= esc_html( $date_time ) ?></span>
                    <?php endif; ?>
                    <?php if ( $order_url ) : ?>
                        <a href="<?= esc_url( $order_url ) ?>" data-barba-prevent data-account-pages="link" class="underline underline-offset-4 transition-opacity hover:opacity-70">
                            <?= esc_html( com\theme::remove_accents( $ticket_count_label ) ) ?> →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

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
    </div>

    <div class="flex items-end justify-end border-t border-dashed border-blue-soft/40 p-20 leading-[1.2] md:w-[13rem] md:border-0 md:pb-30 md:pl-0 md:pr-0 md:pt-30 lg:ml-auto">
        <div class="shrink-0 text-[1.8rem] font-bold"><?= wp_kses_post( wc_price( $ticket_total ) ) ?></div>
    </div>
</article>
