<?php
defined( 'ABSPATH' ) || exit;

$cart_item_key = (string) ( $args['cart_item_key'] ?? '' );
$cart_item = (array) ( $args['cart_item'] ?? [] );
$variant = (string) ( $args['variant'] ?? 'cart' );
$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'] ?? null, $cart_item, $cart_item_key );

if (
    ! $product
    || ! $product->exists()
    || empty( $cart_item['quantity'] )
    || ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key )
) {
    return;
}

$details = com_theme_cart_item_details( $cart_item );
$product_id = absint( $cart_item['product_id'] ?? 0 );
$variation_id = absint( $cart_item['variation_id'] ?? 0 );
$line_price = WC()->cart->get_product_subtotal( $product, (int) $cart_item['quantity'] );
$is_mini = $variant === 'mini';
$is_summary = $variant === 'summary';
$is_checkout_summary = $variant === 'checkout-summary';
$is_compact = $is_mini || $is_checkout_summary;
$ticket_groups = array_values( (array) ( $details['groups'] ?? [] ) );
$ticket_lines = array_values( (array) ( $details['ticket_lines'] ?? [] ) );
$ticket_count = max( 0, (int) ( $details['ticket_count'] ?? 0 ) );
$ticket_count_label = sprintf(
    _n( '%d εισιτήριο', '%d εισιτήρια', $ticket_count, 'com-theme' ),
    $ticket_count
);
$ticket_count_display = com\theme::remove_accents( $ticket_count_label );
$date_time = trim( implode( ' ', array_filter( [ $details['date'] ?? '', $details['time'] ?? '' ] ) ) );
$hold = (
    ! empty( $cart_item['tickets_for_id'] )
    && class_exists( 'IW_Tickets_DB' )
    && method_exists( 'IW_Tickets_DB', 'get_hold_info' )
)
    ? IW_Tickets_DB::get_hold_info( $cart_item )
    : null;
$hold_start_timestamp = 0;
$hold_expires_timestamp = 0;

if ( $hold ) {
    try {
        $hold_timezone = wp_timezone();
        $hold_start_timestamp = ! empty( $hold->created_at )
            ? ( new DateTimeImmutable( (string) $hold->created_at, $hold_timezone ) )->getTimestamp()
            : 0;
        $hold_expires_timestamp = ! empty( $hold->expires_at )
            ? ( new DateTimeImmutable( (string) $hold->expires_at, $hold_timezone ) )->getTimestamp()
            : 0;
    } catch ( Exception $exception ) {
        $hold_start_timestamp = 0;
        $hold_expires_timestamp = 0;
    }
}

if ( ! $is_compact && ! $is_summary ) :
?>
<article
    class="group cart-item overflow-visible rounded-[1.5rem] bg-white text-blue md:flex md:min-h-[28rem] md:items-center md:px-30"
    data-cart="item"
    data-cart-item-key="<?= esc_attr( $cart_item_key ) ?>"
    data-product-id="<?= esc_attr( (string) $product_id ) ?>"
    data-variation-id="<?= esc_attr( (string) $variation_id ) ?>"
>
    <div class="flex min-w-0 flex-1 flex-col md:flex-row md:items-center lg:w-[96rem] lg:flex-none lg:gap-40">
        <div class="flex min-w-0 flex-1 flex-col gap-30 p-20 md:h-[22rem] md:flex-row md:items-center md:gap-30 md:p-0 lg:w-[87rem] lg:flex-none lg:gap-50">
            <?php if ( $details['image_id'] || ! empty( $details['is_all_museums_ticket'] ) ) : ?>
                <a
                    href="<?= esc_url( $details['permalink'] ?: '#' ) ?>"
                    class="relative aspect-[calc(304/220)] w-full shrink-0 overflow-hidden rounded-[1rem] bg-ochre-light md:w-[30%] lg:w-[30.4rem]"
                    <?= $details['permalink'] ? '' : 'tabindex="-1" aria-hidden="true"' ?>
                    aria-label="<?= esc_attr( $details['title'] ) ?>"
                >
                    <?php if ( ! empty( $details['is_all_museums_ticket'] ) ) : ?>
                        <?php get_template_part( 'templates/parts/all-museums-art' ); ?>
                    <?php else : ?>
                        <?php get_template_part( 'templates/parts/image', null, [
                            'id'       => $details['image_id'],
                            'size'     => 'large',
                            'classes'  => '!h-full !w-full object-cover',
                            'parallax' => false,
                        ] ); ?>
                    <?php endif; ?>
                </a>
            <?php else : ?>
                <div class="aspect-[calc(304/220)] w-full shrink-0 rounded-[1rem] bg-ochre-light md:w-[30%] lg:w-[30.4rem]"></div>
            <?php endif; ?>

            <div class="flex min-w-0 flex-1 flex-col self-stretch">
                <div class="flex flex-col gap-20">
                    <div class="flex flex-col gap-10">
                        <?php if ( $details['location'] ) : ?>
                            <p class="m-0 text-[1rem] font-normal leading-none tracking-[.3em]"><?= esc_html( com\theme::remove_accents( $details['location'] ) ) ?></p>
                        <?php endif; ?>

                        <?php if ( $details['permalink'] ) : ?>
                            <h3 class="m-0 text-[2.4rem] font-bold leading-none lg:text-[2.8rem]">
                                <a href="<?= esc_url( $details['permalink'] ) ?>"><?= esc_html( $details['title'] ) ?></a>
                            </h3>
                        <?php else : ?>
                            <h3 class="m-0 text-[2.4rem] font-bold leading-none lg:text-[2.8rem]"><?= esc_html( $details['title'] ) ?></h3>
                        <?php endif; ?>
                    </div>

                    <?php if ( ! empty( $details['description'] ) ) : ?>
                        <div class="line-clamp-3 text-[1.2rem] font-normal leading-[normal]">
                            <?= wp_kses_post( wpautop( $details['description'] ) ) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-20 flex flex-wrap items-center gap-x-12 gap-y-8 text-[1.4rem] font-normal leading-none text-blue md:mt-auto">
                    <?php if ( $date_time ) : ?>
                        <span><?= esc_html( $date_time ) ?></span>
                    <?php endif; ?>

                    <?php if ( $date_time && $hold_expires_timestamp ) : ?>
                        <span class="h-15 border-l border-blue-soft" aria-hidden="true"></span>
                    <?php endif; ?>

                    <?php if ( $hold_expires_timestamp ) : ?>
                        <div
                            class="flex items-center gap-8"
                            data-module-timer
                            data-start="<?= esc_attr( (string) ( $hold->created_at ?? '' ) ) ?>"
                            data-end="<?= esc_attr( (string) ( $hold->expires_at ?? '' ) ) ?>"
                            data-start-timestamp="<?= esc_attr( (string) $hold_start_timestamp ) ?>"
                            data-expires-timestamp="<?= esc_attr( (string) $hold_expires_timestamp ) ?>"
                            data-day-unit="<?= esc_attr__( 'η', 'com-theme' ) ?>"
                            data-hour-unit="<?= esc_attr__( 'ω', 'com-theme' ) ?>"
                            data-minute-unit="<?= esc_attr__( 'λ', 'com-theme' ) ?>"
                        >
                            <span><?= esc_html( com\theme::remove_accents( __( 'Χρόνος κράτησης:', 'com-theme' ) ) ) ?></span>
                            <span data-timer="display"></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <svg class="hidden h-[28rem] w-[4.4rem] shrink-0 text-blue md:block" aria-hidden="true"><use xlink:href="#icon-com-ticket-divider"></use></svg>
    </div>

    <div class="flex flex-row-reverse items-end justify-between gap-20 border-t border-dashed border-blue-soft/40 p-20 md:h-[20rem] md:w-[13rem] md:flex-col md:items-start md:border-0 md:p-0 lg:ml-auto">
        <div
            class="group product-quantity"
            data-module-product-quantity
            data-cart-item-key="<?= esc_attr( $cart_item_key ) ?>"
        >
            <input
                type="hidden"
                value="1"
                min="0"
                max="1"
                data-product-quantity="input"
            >
            <button
                type="button"
                class="group/button inline-flex cursor-pointer items-center justify-center whitespace-nowrap rounded-[.5rem] border border-blue bg-transparent px-[1.7rem] py-[1.5rem] text-[1.6rem] font-normal leading-none text-blue transition-colors hover:bg-blue hover:text-white group-[.product-quantity.loading]:pointer-events-none group-[.product-quantity.loading]:opacity-40"
                data-product-quantity="change"
                data-direction="-1"
            >
                <span class="relative mr-10 inline-flex size-[1.9rem] shrink-0 items-center justify-center">
                    <svg class="size-[1.9rem] transition-[filter] group-hover/button:brightness-0 group-hover/button:invert" aria-hidden="true"><use xlink:href="#icon-com-ticket-button"></use></svg>
                    <span class="absolute -right-2 -top-2 text-[1rem] font-bold leading-none text-blue transition-colors group-hover/button:text-white">×</span>
                </span>
                <span><?= esc_html__( 'Αφαίρεση', 'com-theme' ) ?></span>
            </button>
        </div>

        <div class="relative text-[1.4rem] font-normal leading-[1.2]">
            <?php if ( $ticket_groups ) : ?>
                <details class="relative" data-cart-ticket-details>
                    <summary class="inline cursor-pointer list-none underline underline-offset-4 transition-opacity hover:opacity-70 focus:outline-none focus-visible:opacity-70 [&::-webkit-details-marker]:hidden">
                        <?= esc_html( $ticket_count_display ) ?>
                    </summary>
                    <div class="absolute bottom-full left-0 z-20 mb-10 w-[28rem] max-w-[calc(100vw-4rem)] rounded-[1rem] bg-white p-15 text-[1.2rem] leading-[1.3] shadow-[0_1rem_3rem_rgba(23,50,118,.18)] md:left-auto md:right-0">
                        <p class="m-0 mb-10 font-bold text-blue-soft"><?= esc_html( $ticket_count_display ) ?></p>
                        <div class="space-y-8">
                            <?php if ( $ticket_lines ) : ?>
                                <?php foreach ( $ticket_lines as $ticket_line ) : ?>
                                    <?php
                                    $ticket_line_label = trim( (string) ( $ticket_line['label'] ?? __( 'Εισιτήριο', 'com-theme' ) ) );
                                    $ticket_line_name = trim( (string) ( $ticket_line['name'] ?? '' ) );
                                    $ticket_line_price = is_numeric( $ticket_line['price'] ?? null ) ? (float) $ticket_line['price'] : 0.0;
                                    ?>
                                    <div class="flex items-start justify-between gap-15">
                                        <span class="min-w-0">
                                            <span class="block"><?= esc_html( sprintf( '1 × %s', com\theme::remove_accents( $ticket_line_label ) ) ) ?></span>
                                            <?php if ( $ticket_line_name ) : ?>
                                                <span class="mt-4 block text-[1.1rem] leading-[1.2] text-blue-soft"><?= esc_html( $ticket_line_name ) ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="shrink-0 font-bold"><?= wp_kses_post( wc_price( $ticket_line_price ) ) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <?php foreach ( $ticket_groups as $group ) : ?>
                                    <div class="flex items-start justify-between gap-15">
                                        <span><?= esc_html( sprintf( '%d × %s', $group['count'], com\theme::remove_accents( $group['label'] ) ) ) ?></span>
                                        <span class="shrink-0 font-bold"><?= wp_kses_post( wc_price( $group['total'] ) ) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </details>
            <?php endif; ?>
            <div
                class="mt-4 shrink-0 text-[1.8rem] font-bold"
                data-module-price-html
                data-key="<?= esc_attr( $product_id . '_' . $variation_id ) ?>"
            ><?= wp_kses_post( $line_price ) ?></div>
        </div>
    </div>
</article>
<?php
return;
endif;
?>
<article
    class="group cart-item relative <?= $is_compact ? 'border-b border-dashed border-blue-soft pb-20 last:border-0 last:pb-0' : ( $is_summary ? '' : 'rounded-[1.5rem] bg-white p-20 text-blue md:p-30' ) ?>"
    data-cart="item"
    data-cart-item-key="<?= esc_attr( $cart_item_key ) ?>"
    data-product-id="<?= esc_attr( (string) $product_id ) ?>"
    data-variation-id="<?= esc_attr( (string) $variation_id ) ?>"
>
    <div class="<?= $is_compact ? 'grid grid-cols-[8rem_1fr] gap-15' : ( $is_summary ? 'space-y-20' : 'grid gap-20 md:grid-cols-[20rem_1fr] md:gap-30' ) ?>">
        <?php if ( $details['image_id'] || ! empty( $details['is_all_museums_ticket'] ) ) : ?>
            <a
                href="<?= esc_url( $details['permalink'] ?: '#' ) ?>"
                class="<?= $is_compact ? 'h-[6.5rem] w-[8rem] rounded-[.8rem]' : ( $is_summary ? 'block aspect-[360/201] w-full rounded-[1rem]' : 'block aspect-[4/3] w-full rounded-[1rem]' ) ?> relative overflow-hidden bg-ochre-light"
                <?= $details['permalink'] ? '' : 'tabindex="-1" aria-hidden="true"' ?>
            >
                <?php if ( ! empty( $details['is_all_museums_ticket'] ) ) : ?>
                    <?php get_template_part( 'templates/parts/all-museums-art', null, [
                        'label_classes' => $is_compact
                            ? 'absolute left-[1rem] top-[1.2rem] text-[1.8rem] font-light leading-[.895] text-ochre-light'
                            : 'absolute left-[2.8rem] top-[3.4rem] text-[4.4rem] font-light leading-[.895] text-ochre-light sm:text-[5.6rem]',
                    ] ); ?>
                <?php else : ?>
                    <?php get_template_part( 'templates/parts/image', null, [
                        'id'       => $details['image_id'],
                        'size'     => $is_compact ? 'thumbnail' : 'large',
                        'classes'  => '!w-full !h-full object-cover',
                        'lazy'     => ! $is_mini && ! $is_checkout_summary,
                        'parallax' => false,
                        'attrs'    => $is_checkout_summary ? 'loading="eager" fetchpriority="high"' : '',
                    ] ); ?>
                <?php endif; ?>
            </a>
        <?php else : ?>
            <div class="<?= $is_compact ? 'h-[6.5rem] w-[8rem] rounded-[.8rem]' : ( $is_summary ? 'aspect-[360/201]' : 'aspect-[4/3]' ) . ' w-full rounded-[1rem]' ?> bg-ochre-light"></div>
        <?php endif; ?>

        <div class="<?= $is_summary ? 'space-y-20' : 'flex min-w-0 flex-col justify-between gap-15' ?> <?= $is_compact ? 'pr-20' : '' ?>">
            <div class="<?= $is_compact ? 'space-y-5' : 'space-y-10' ?>">
                <?php if ( $is_summary ) : ?>
                    <div class="text-[1.2rem] font-bold text-blue-soft"><?= esc_html__( 'ΕΙΣΙΤΗΡΙΟ', 'com-theme' ) ?></div>
                <?php endif; ?>
                <div>
                <?php if ( $details['location'] && ! $is_compact ) : ?>
                    <div class="text-[1rem] font-normal leading-none tracking-[.3em]"><?= esc_html( com\theme::remove_accents( $details['location'] ) ) ?></div>
                <?php endif; ?>
                <?php if ( $details['permalink'] ) : ?>
                    <a
                        href="<?= esc_url( $details['permalink'] ) ?>"
                        class="<?= $is_compact ? 'line-clamp-2 text-[1.3rem]' : ( $is_summary ? 'text-[1.8rem]' : 'text-[2.4rem] lg:text-[2.8rem]' ) ?> block font-bold leading-none"
                    ><?= esc_html( $details['title'] ) ?></a>
                <?php else : ?>
                    <div class="<?= $is_compact ? 'line-clamp-2 text-[1.3rem]' : ( $is_summary ? 'text-[1.8rem]' : 'text-[2.4rem] lg:text-[2.8rem]' ) ?> font-bold leading-none"><?= esc_html( $details['title'] ) ?></div>
                <?php endif; ?>
                </div>



                <?php if ( $date_time || $ticket_groups || $is_compact ) : ?>
                    <div class="flex items-center gap-10 <?= $is_compact ? 'text-[1.1rem] font-normal' : 'text-[1.4rem]' ?>">
                        <div class="flex min-w-0 gap-10">
                            <?php if ( $date_time ) : ?>
                                <div><?= esc_html( $date_time ) ?></div>
                            <?php endif; ?>
                            <?php if ( $ticket_groups ) : ?>
                                <div>
                                    <?= esc_html( $ticket_count_display ) ?>
                                    <?php if ( ! $is_compact ) : ?> →<?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ( $is_compact ) : ?>
                                <div
                                    class="shrink-0"
                                    <?php if ( $is_mini ) : ?>
                                        data-module-price-html
                                        data-key="<?= esc_attr( $product_id . '_' . $variation_id ) ?>"
                                    <?php endif; ?>
                                ><?= wp_kses_post( $line_price ) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( ( $is_mini || $is_checkout_summary ) && $hold_expires_timestamp ) : ?>
                    <div
                        class="flex items-center gap-5 text-[1.1rem] font-normal leading-none"
                        data-module-timer
                        data-start="<?= esc_attr( (string) ( $hold->created_at ?? '' ) ) ?>"
                        data-end="<?= esc_attr( (string) ( $hold->expires_at ?? '' ) ) ?>"
                        data-start-timestamp="<?= esc_attr( (string) $hold_start_timestamp ) ?>"
                        data-expires-timestamp="<?= esc_attr( (string) $hold_expires_timestamp ) ?>"
                        data-day-unit="<?= esc_attr__( 'η', 'com-theme' ) ?>"
                        data-hour-unit="<?= esc_attr__( 'ω', 'com-theme' ) ?>"
                        data-minute-unit="<?= esc_attr__( 'λ', 'com-theme' ) ?>"
                        <?php if ( $is_checkout_summary ) : ?>
                            data-expiry-redirect="<?= esc_url( wc_get_cart_url() ) ?>"
                        <?php endif; ?>
                    >
                        <span><?= esc_html( com\theme::remove_accents( __( 'Χρόνος κράτησης:', 'com-theme' ) ) ) ?></span>
                        <span data-timer="display"></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="hidden <?= $is_compact ? 'text-[1.1rem]' : 'text-[1.4rem]' ?>">
                <?php foreach ( $ticket_groups as $group ) : ?>
                    <div class="flex items-start justify-between gap-15">
                        <span><?= esc_html( sprintf( '%d × %s', $group['count'], com\theme::remove_accents( $group['label'] ) ) ) ?></span>
                        <span class="shrink-0 font-bold"><?= wp_kses_post( wc_price( $group['total'] ) ) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if ( $is_compact ) : ?>
        <div
            class="<?= $is_mini ? 'group product-quantity' : '' ?> absolute right-0 top-0"
            <?php if ( $is_mini ) : ?>
                data-module-product-quantity
                data-cart-item-key="<?= esc_attr( $cart_item_key ) ?>"
            <?php endif; ?>
        >
            <?php if ( $is_mini ) : ?>
                <input
                    type="hidden"
                    value="1"
                    min="0"
                    max="1"
                    data-product-quantity="input"
                >
                <button
                    type="button"
                    class="flex size-20 cursor-pointer items-center justify-center rounded-full border border-blue opacity-60 transition hover:bg-blue hover:text-white hover:opacity-100 group-[.product-quantity.loading]:pointer-events-none group-[.product-quantity.loading]:opacity-40"
                    data-product-quantity="change"
                    data-direction="-1"
                    aria-label="<?= esc_attr( sprintf( __( 'Αφαίρεση %s από το καλάθι', 'com-theme' ), $details['title'] ) ) ?>"
                >
                    <svg class="size-10 fill-current" aria-hidden="true"><use xlink:href="#icon-trash"></use></svg>
                </button>
            <?php else : ?>
                <a
                    href="<?= esc_url( wc_get_cart_remove_url( $cart_item_key ) ) ?>"
                    class="flex size-20 items-center justify-center rounded-full border border-blue opacity-60 transition hover:bg-blue hover:text-white hover:opacity-100"
                    aria-label="<?= esc_attr( sprintf( __( 'Αφαίρεση %s από το καλάθι', 'com-theme' ), $details['title'] ) ) ?>"
                >
                    <svg class="size-10 fill-current" aria-hidden="true"><use xlink:href="#icon-trash"></use></svg>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</article>
