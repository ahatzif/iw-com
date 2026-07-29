<?php
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart_display' );
WC()->cart->calculate_totals();

$cart = WC()->cart;
$is_empty = $cart->is_empty();
$count = com_theme_cart_count();
$has_notices = ! empty( wc_get_notices() );
?>
<section
    class="group cart <?= $is_empty ? 'is-empty' : '' ?> flex min-h-screen flex-col bg-blue pb-120 pt-[16rem] text-ochre"
    data-module-cart="main"
>
    <input type="hidden" name="cart_nonce" value="<?= esc_attr( wp_create_nonce( 'cart_nonce' ) ) ?>" data-cart="nonce">

    <div class="page-wrapper flex flex-1 flex-col">
        <header class="max-w-[75rem] group-[.cart.is-empty]:hidden">
            <p class="m-0 text-[1rem] font-medium tracking-[.18em]"><?= esc_html( com\theme::remove_accents( __( 'Αγορά εισιτηρίου', 'com-theme' ) ) ) ?></p>
            <h1 class="mt-20 text-[4.4rem] font-medium leading-[1.08] md:text-[6rem] md:leading-[7rem]"><?= esc_html__( 'Το καλάθι σας', 'com-theme' ) ?></h1>
            <p class="mt-20 max-w-[56rem] text-[1.8rem] leading-[1.35] text-ochre/80"><?= esc_html__( 'Ελέγξτε τα εισιτήριά σας πριν προχωρήσετε στην ολοκλήρωση της αγοράς.', 'com-theme' ) ?></p>
        </header>

        <div class="<?= $has_notices ? 'mt-40' : 'hidden' ?>">
            <?php wc_print_notices(); ?>
        </div>

        <div class="mt-60 hidden flex-1 group-[.cart.is-empty]:mt-40 group-[.cart.is-empty]:flex" data-cart="empty">
            <?php wc_get_template( 'cart/cart-empty.php', [ 'embedded' => true ] ); ?>
        </div>

        <div class="mt-60 group-[.cart.is-empty]:hidden lg:items-start space-y-20" data-cart="filled">
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
                <div class="flex items-start justify-between gap-20">
                    <div>
                        <div class="text-[1.6rem] font-bold text-blue-soft"><?= esc_html__( 'ΣΥΝΟΛΟ', 'com-theme' ) ?></div>
                        <div class="mt-5 text-[1.2rem]" data-cart="cart-items-count"><?= esc_html( com_theme_cart_count_label( $count ) ) ?></div>
                    </div>
                    <div class="text-[2.4rem] font-bold" data-module-price-html data-key="cart-total"><?= wp_kses_post( $cart->get_total() ) ?></div>
                </div>

                <div
                    class="relative -mx-30 mt-30 border-t border-dashed border-blue-soft before:absolute before:-left-15 before:-top-15 before:size-30 before:rounded-full before:bg-blue before:content-[''] after:absolute after:-right-15 after:-top-15 after:size-30 after:rounded-full after:bg-blue after:content-[''] md:-mx-40"
                    aria-hidden="true"
                ></div>

                <div class="mt-30 flex flex-wrap items-stretch justify-end gap-15">
                    <?php
                    get_template_part( 'templates/parts/com-button', null, [
                        'href'    => com_theme_page_url( 'buy-tickets' ),
                        'label'   => __( 'ΣΥΝΕΧΕΙΑ ΑΓΟΡΩΝ', 'com-theme' ),
                        'variant' => 'blue-outline',
                        'classes' => 'min-h-[5.6rem]',
                    ] );

                    get_template_part( 'templates/parts/com-button', null, [
                        'href'    => wc_get_checkout_url(),
                        'label'   => __( 'ΟΛΟΚΛΗΡΩΣΗ ΑΓΟΡΑΣ', 'com-theme' ),
                        'variant' => 'blue',
                        'classes' => 'min-h-[5.6rem]',
                    ] );
                    ?>
                </div>
            </aside>
        </div>
    </div>
</section>
<?php do_action( 'woocommerce_after_cart' ); ?>
