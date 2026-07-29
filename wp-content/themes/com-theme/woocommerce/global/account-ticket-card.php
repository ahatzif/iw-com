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
$slot_end = $ticket_order['slot_end'] ?? null;
$ticket_count = absint( $ticket_order['tickets_count'] ?? 0 );
$order_url = (string) $card_args['order_url'];
$is_past = $card_args['status'] === 'past';
$post_type_object = get_post_type_object( $ticket_post->post_type );
$type_label = $post_type_object ? $post_type_object->labels->singular_name : __( 'Εισιτήριο', 'com-theme' );
$calendar_links = [];

if ( ! $is_past && $slot_start && class_exists( 'IW_Ticketing_ICS' ) && method_exists( 'IW_Ticketing_ICS', 'get_calendar_links' ) ) {
    $calendar_links = IW_Ticketing_ICS::get_calendar_links( $ticket_post->ID, $slot_start, $slot_end ?: null );
}
?>

<article class="group overflow-hidden rounded-[1.2rem] border border-blue/15 <?= $card_args['size'] === 'large' ? 'bg-ochre-light' : 'bg-white' ?>">
    <div class="grid md:grid-cols-[24rem_1fr] lg:grid-cols-[28rem_1fr]">
        <a href="<?= esc_url( $order_url ?: get_permalink( $ticket_post ) ) ?>" <?= $order_url ? 'data-barba-prevent data-account-pages="link"' : '' ?> class="relative block aspect-[4/3] overflow-hidden bg-ochre-light md:aspect-auto md:min-h-[22rem]">
            <?php if ( $is_all_museums_ticket ) : ?>
                <?php get_template_part( 'templates/parts/all-museums-art', null, [
                    'label_classes' => 'absolute left-[2.8rem] top-[3.4rem] text-[4.4rem] font-light leading-[.895] text-ochre-light sm:text-[5.6rem]',
                ] ); ?>
            <?php elseif ( $image_id ) : ?>
                <?php get_template_part( 'templates/parts/image', null, [
                    'id'       => $image_id,
                    'size'     => 'large',
                    'classes'  => 'size-full object-cover transition-transform duration-500 group-hover:scale-[1.03]',
                    'parallax' => false,
                ] ); ?>
            <?php else : ?>
                <div class="flex size-full items-center justify-center bg-blue text-ochre">
                    <svg class="h-50 w-60 fill-current" aria-hidden="true"><use xlink:href="#icon-com-ticket-light"></use></svg>
                </div>
            <?php endif; ?>
        </a>

        <div class="flex min-w-0 flex-col justify-between gap-25 p-20 md:p-25">
            <div>
                <div class="mb-15 flex flex-wrap items-center justify-between gap-10">
                    <span class="text-[1rem] font-medium tracking-[.16em] text-blue-soft"><?= esc_html( com\theme::remove_accents( $type_label ) ) ?></span>
                    <span class="rounded-full px-12 py-6 text-[1rem] font-bold <?= $is_past ? 'bg-blue/10 text-blue/60' : 'bg-blue text-white' ?>">
                        <?= esc_html( $is_past ? __( 'Ολοκληρωμένο', 'com-theme' ) : __( 'Ενεργό', 'com-theme' ) ) ?>
                    </span>
                </div>

                <?php if ( $order_url ) : ?>
                    <a href="<?= esc_url( $order_url ) ?>" data-barba-prevent data-account-pages="link" class="block text-[2rem] font-bold leading-[1.15] md:text-[2.4rem]"><?= esc_html( get_the_title( $ticket_post ) ) ?></a>
                <?php else : ?>
                    <h3 class="m-0 text-[2rem] font-bold leading-[1.15] md:text-[2.4rem]"><?= esc_html( get_the_title( $ticket_post ) ) ?></h3>
                <?php endif; ?>

                <?php if ( $location ) : ?>
                    <p class="mb-0 mt-8 text-[1.3rem] text-blue-soft"><?= esc_html( $location ) ?></p>
                <?php endif; ?>
            </div>

            <div class="flex flex-col gap-20 border-t border-dashed border-blue/25 pt-20 sm:flex-row sm:items-end sm:justify-between">
                <dl class="grid grid-cols-2 gap-x-25 gap-y-10 text-[1.3rem]">
                    <?php if ( $slot_start instanceof DateTimeInterface ) : ?>
                        <div>
                            <dt class="text-[.9rem] tracking-[.1em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Ημερομηνία', 'com-theme' ) ) ) ?></dt>
                            <dd class="m-0 mt-4 font-bold"><?= esc_html( wp_date( 'd/m/Y', $slot_start->getTimestamp() ) ) ?></dd>
                        </div>
                        <div>
                            <dt class="text-[.9rem] tracking-[.1em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Ώρα', 'com-theme' ) ) ) ?></dt>
                            <dd class="m-0 mt-4 font-bold">
                                <?= esc_html( wp_date( 'H:i', $slot_start->getTimestamp() ) ) ?>
                                <?php if ( $slot_end instanceof DateTimeInterface ) : ?>
                                    – <?= esc_html( wp_date( 'H:i', $slot_end->getTimestamp() ) ) ?>
                                <?php endif; ?>
                            </dd>
                        </div>
                    <?php endif; ?>
                    <div>
                        <dt class="text-[.9rem] tracking-[.1em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Εισιτήρια', 'com-theme' ) ) ) ?></dt>
                        <dd class="m-0 mt-4 font-bold"><?= esc_html( (string) $ticket_count ) ?></dd>
                    </div>
                </dl>

                <div class="flex flex-wrap gap-10">
                    <?php if ( ! empty( $calendar_links['google'] ) ) : ?>
                        <a href="<?= esc_url( $calendar_links['google'] ) ?>" target="_blank" rel="noopener" data-barba-prevent class="inline-flex min-h-40 items-center rounded-[.6rem] border border-blue px-12 text-[1.1rem] font-bold">
                            <?= esc_html__( 'Στο ημερολόγιο', 'com-theme' ) ?>
                        </a>
                    <?php endif; ?>
                    <?php if ( $order_url ) : ?>
                        <a href="<?= esc_url( $order_url ) ?>" data-barba-prevent data-account-pages="link" class="inline-flex min-h-40 items-center rounded-[.6rem] bg-blue px-15 text-[1.2rem] font-bold text-white">
                            <?= esc_html__( 'Προβολή', 'com-theme' ) ?> →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</article>
