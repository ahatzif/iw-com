<?php
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart_display' );
WC()->cart->calculate_totals();

$cart = WC()->cart;
$is_empty = $cart->is_empty();
$count = com_theme_cart_count();
?>
<section
    class="group cart <?= $is_empty ? 'is-empty' : '' ?> min-h-screen bg-blue pb-120 pt-[16rem] text-ochre"
    data-module-cart="main"
>
    <input type="hidden" name="cart_nonce" value="<?= esc_attr( wp_create_nonce( 'cart_nonce' ) ) ?>" data-cart="nonce">

    <div class="page-wrapper">
        <header class="max-w-[75rem]">
            <p class="m-0 text-[1rem] font-medium uppercase tracking-[.18em]"><?= esc_html__( 'Αγορά εισιτηρίου', 'com-theme' ) ?></p>
            <h1 class="mt-20 text-[4.4rem] font-medium leading-[1.08] md:text-[6rem] md:leading-[7rem]"><?= esc_html__( 'Το καλάθι σας', 'com-theme' ) ?></h1>
            <p class="mt-20 max-w-[56rem] text-[1.8rem] leading-[1.35] text-ochre/80"><?= esc_html__( 'Ελέγξτε τα εισιτήριά σας πριν προχωρήσετε στην ολοκλήρωση της αγοράς.', 'com-theme' ) ?></p>
        </header>

        <div class="mt-60 hidden group-[.cart.is-empty]:block" data-cart="empty">
            <?php wc_get_template( 'cart/cart-empty.php', [ 'embedded' => true ] ); ?>
        </div>

        <div class="mt-60 grid gap-40 group-[.cart.is-empty]:hidden lg:grid-cols-[minmax(0,1fr)_40rem] lg:items-start lg:gap-60" data-cart="filled">
            <div class="space-y-20" data-cart="items">
                <?php foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) : ?>
                    <?php wc_get_template( 'cart/cart-item.php', [
                        'cart_item_key' => $cart_item_key,
                        'cart_item'     => $cart_item,
                        'variant'       => 'cart',
                    ] ); ?>
                <?php endforeach; ?>
            </div>

            <aside class="rounded-[1.5rem] bg-white p-30 text-blue md:p-40 lg:sticky lg:top-[14rem]">
                <h2 class="text-[1.6rem] font-bold text-blue-soft"><?= esc_html__( 'ΣΥΝΟΨΗ ΠΑΡΑΓΓΕΛΙΑΣ', 'com-theme' ) ?></h2>
                <div class="mt-30 flex items-start justify-between gap-20">
                    <div>
                        <div class="text-[1.6rem] font-bold text-blue-soft"><?= esc_html__( 'ΣΥΝΟΛΟ', 'com-theme' ) ?></div>
                        <div class="mt-5 text-[1.2rem]" data-cart="cart-items-count"><?= esc_html( com_theme_cart_count_label( $count ) ) ?></div>
                    </div>
                    <div class="text-[2.4rem] font-bold" data-module-price-html data-key="cart-total"><?= wp_kses_post( $cart->get_total() ) ?></div>
                </div>

                <a
                    href="<?= esc_url( wc_get_checkout_url() ) ?>"
                    class="mt-30 inline-flex min-h-[5.6rem] w-full items-center justify-center rounded-[1rem] bg-blue px-30 text-[1.6rem] text-white transition-colors hover:bg-blue-soft"
                ><?= esc_html__( 'ΟΛΟΚΛΗΡΩΣΗ ΑΓΟΡΑΣ', 'com-theme' ) ?></a>
                <a
                    href="<?= esc_url( com_theme_page_url( 'buy-tickets' ) ) ?>"
                    class="mt-10 inline-flex min-h-[5.6rem] w-full items-center justify-center rounded-[1rem] border border-blue px-30 text-[1.6rem] transition-colors hover:bg-blue hover:text-white"
                ><?= esc_html__( 'ΣΥΝΕΧΕΙΑ ΑΓΟΡΩΝ', 'com-theme' ) ?></a>
            </aside>
        </div>
    </div>
</section>
<?php do_action( 'woocommerce_after_cart' ); ?>
