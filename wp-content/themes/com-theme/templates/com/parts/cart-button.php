<?php
if ( ! function_exists( 'WC' ) || ! WC()->cart || ! function_exists( 'wc_get_cart_url' ) ) {
    return;
}

$cart = WC()->cart;
$cart->calculate_totals();
$cart_url = wc_get_cart_url();
$checkout_url = wc_get_checkout_url();
$museums_url = home_url( '/#museums' );
$count = com_theme_cart_count();
$is_empty = $cart->is_empty();
$is_light_header = isset( $args['is_light_header'] )
    ? (bool) $args['is_light_header']
    : com_theme_header_theme() === 'light';
$count_color_classes = $is_light_header
    ? 'bg-ochre text-blue group-[.has-scrolled]:bg-blue group-[.has-scrolled]:text-white'
    : 'bg-blue text-white';
?>
<div
    class="com-cart-button group cart-button relative shrink-0 [&.active]:z-30"
    data-module-cart-button="main"
    data-ticket-singular="<?= esc_attr__( 'εισιτήριο', 'com-theme' ) ?>"
    data-ticket-plural="<?= esc_attr__( 'εισιτήρια', 'com-theme' ) ?>"
    data-aria-label-template="<?= esc_attr__( 'Καλάθι, %s', 'com-theme' ) ?>"
>
    <div
        class="group cart <?= $is_empty ? 'is-empty' : '' ?> pointer-events-none fixed left-1/12 right-1/12 top-[9rem] pt-10 text-blue opacity-0 transition duration-300 group-[.cart-button.active]:pointer-events-auto group-[.cart-button.active]:opacity-100 md:absolute md:left-auto md:right-0 md:top-full md:w-[44rem] md:pt-20"
        data-module-cart="header"
    >
        <button
            type="button"
            class="fixed inset-0 -z-1 cursor-default bg-blue/30"
            data-cart-button="close"
            aria-label="<?= esc_attr__( 'Κλείσιμο καλαθιού', 'com-theme' ) ?>"
        ></button>
        <div class="relative flex max-h-[calc(100vh-12rem)] flex-col gap-30 overflow-hidden rounded-[1.5rem] bg-white p-30 shadow-[0_0_3rem_rgba(23,50,118,.18)] md:p-40">
            <div class="flex items-center justify-between text-[1.6rem] font-bold leading-none">
                <span><?= esc_html__( 'ΤΟ ΚΑΛΑΘΙ ΜΟΥ', 'com-theme' ) ?></span>
                <button
                    type="button"
                    class="flex size-30 cursor-pointer items-center justify-center rounded-full opacity-70 transition hover:bg-blue/10 hover:opacity-100"
                    data-cart-button="close"
                    aria-label="<?= esc_attr__( 'Κλείσιμο', 'com-theme' ) ?>"
                >
                    <svg class="size-15 fill-current" aria-hidden="true"><use xlink:href="#icon-close-modal"></use></svg>
                </button>
            </div>

            <div class="min-h-0 overflow-y-auto pr-10 group-[.cart.is-empty]:hidden" data-cart="filled">
                <div class="space-y-20" data-cart="items">
                    <?php foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) : ?>
                        <?php wc_get_template( 'cart/cart-item.php', [
                            'cart_item_key' => $cart_item_key,
                            'cart_item'     => $cart_item,
                            'variant'       => 'mini',
                        ] ); ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="hidden space-y-20 group-[.cart.is-empty]:block" data-cart="empty">
                <div class="space-y-5 text-[1.2rem] leading-[1.3]">
                    <p class="m-0 font-bold text-[1.6rem]"><?= esc_html__( 'Το καλάθι σας είναι άδειο', 'com-theme' ) ?></p>
                    <p class="m-0 font-normal"><?= esc_html__( 'Συνεχίστε την επιλογή εισιτηρίων και ανακαλύψτε τα μουσεία μας.', 'com-theme' ) ?></p>
                </div>
                <a
                    href="<?= esc_url( $museums_url ) ?>"
                    class="inline-flex min-h-[4.6rem] w-full items-center justify-center rounded-[1rem] bg-blue px-20 text-[1.4rem] text-white transition-colors hover:bg-blue-soft"
                    data-cart-button="close"
                    <?= is_front_page() ? 'data-module-scroll-to-anchor data-selector="#museums" data-barba-prevent="self"' : '' ?>
                ><?= esc_html__( 'ΕΠΙΛΟΓΗ ΕΙΣΙΤΗΡΙΩΝ', 'com-theme' ) ?></a>
            </div>

            <div class="border-t border-dashed border-blue-soft pt-20">
                <div class="flex items-end justify-between gap-20">
                    <div>
                        <div class="text-[1.2rem] text-blue-soft"><?= esc_html__( 'ΣΥΝΟΛΟ', 'com-theme' ) ?></div>
                        <div class="mt-5 text-[1.3rem]" data-cart="cart-items-count"><?= esc_html( com_theme_cart_count_label( $count ) ) ?></div>
                    </div>
                    <div class="text-[2.4rem] font-bold" data-module-price-html data-key="cart-total"><?= wp_kses_post( $cart->get_total() ) ?></div>
                </div>
                <div class="mt-20 grid grid-cols-2 gap-10 group-[.cart.is-empty]:hidden" data-cart="actions">
                    <a
                        href="<?= esc_url( $cart_url ) ?>"
                        class="inline-flex min-h-[4.6rem] items-center justify-center rounded-[1rem] border border-blue px-15 text-center text-[1.3rem] transition-colors hover:bg-blue hover:text-white"
                    ><?= esc_html__( 'ΠΡΟΒΟΛΗ', 'com-theme' ) ?></a>
                    <a
                        href="<?= esc_url( $checkout_url ) ?>"
                        class="inline-flex min-h-[4.6rem] items-center justify-center rounded-[1rem] bg-blue px-15 text-center text-[1.3rem] text-white transition-colors hover:bg-blue-soft"
                    ><?= esc_html__( 'ΟΛΟΚΛΗΡΩΣΗ', 'com-theme' ) ?></a>
                </div>
            </div>
        </div>
    </div>

    <button
        type="button"
        class="relative z-1 flex size-35 select-none items-center justify-center rounded-full border border-current before:pointer-events-none before:absolute before:inset-0 before:rounded-full before:bg-current before:opacity-0 before:transition-opacity before:duration-300 hover:before:opacity-10 md:size-40"
        data-cart-button="button"
        aria-label="<?= esc_attr( sprintf( __( 'Καλάθι, %s', 'com-theme' ), com_theme_cart_count_label( $count ) ) ) ?>"
        aria-expanded="false"
    >
        <svg class="size-20 fill-current md:size-[2.3rem]" aria-hidden="true"><use xlink:href="#icon-cart-header"></use></svg>
        <span
            class="<?= $count > 0 ? 'flex' : 'hidden' ?> pointer-events-none absolute -right-5 -top-5 min-h-20 min-w-20 items-center justify-center rounded-full px-5 text-[1rem] font-bold leading-none transition-colors <?= esc_attr( $count_color_classes ) ?>"
            data-cart-button="count"
        ><?= esc_html( (string) $count ) ?></span>
    </button>
</div>
