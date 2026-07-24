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
?>
<article
    class="group cart-item <?= $is_mini ? 'border-b border-dashed border-blue-soft pb-20 last:border-0 last:pb-0' : ( $is_summary ? '' : 'rounded-[1.5rem] bg-white p-20 text-blue md:p-30' ) ?>"
    data-cart="item"
    data-cart-item-key="<?= esc_attr( $cart_item_key ) ?>"
    data-product-id="<?= esc_attr( (string) $product_id ) ?>"
    data-variation-id="<?= esc_attr( (string) $variation_id ) ?>"
>
    <div class="<?= $is_mini ? 'grid grid-cols-[8rem_1fr] gap-15' : ( $is_summary ? 'space-y-20' : 'grid gap-20 md:grid-cols-[20rem_1fr] md:gap-30' ) ?>">
        <?php if ( $details['image_id'] ) : ?>
            <a
                href="<?= esc_url( $details['permalink'] ?: '#' ) ?>"
                class="<?= $is_mini ? 'h-[6.5rem] w-[8rem] rounded-[.8rem]' : ( $is_summary ? 'block aspect-[360/201] w-full rounded-[1rem]' : 'block aspect-[4/3] w-full rounded-[1rem]' ) ?> overflow-hidden bg-ochre-light"
                <?= $details['permalink'] ? '' : 'tabindex="-1" aria-hidden="true"' ?>
            >
                <?= wp_get_attachment_image(
                    $details['image_id'],
                    $is_mini ? 'thumbnail' : 'large',
                    false,
                    [ 'class' => 'size-full object-cover' ]
                ) ?>
            </a>
        <?php else : ?>
            <div class="<?= $is_mini ? 'h-[6.5rem] w-[8rem] rounded-[.8rem]' : ( $is_summary ? 'aspect-[360/201]' : 'aspect-[4/3]' ) . ' w-full rounded-[1rem]' ?> bg-ochre-light"></div>
        <?php endif; ?>

        <div class="<?= $is_summary ? 'space-y-20' : 'flex min-w-0 flex-col justify-between gap-15' ?>">
            <div class="<?= $is_mini ? 'space-y-5' : 'space-y-10' ?>">
                <?php if ( $is_summary ) : ?>
                    <div class="text-[1.2rem] font-bold text-blue-soft"><?= esc_html__( 'ΕΙΣΙΤΗΡΙΟ', 'com-theme' ) ?></div>
                <?php endif; ?>
                <?php if ( $details['permalink'] ) : ?>
                    <a
                        href="<?= esc_url( $details['permalink'] ) ?>"
                        class="<?= $is_mini ? 'line-clamp-2 text-[1.4rem]' : ( $is_summary ? 'text-[2.4rem]' : 'text-[2rem] md:text-[2.4rem]' ) ?> block font-bold leading-[1.2]"
                    ><?= esc_html( $details['title'] ) ?></a>
                <?php else : ?>
                    <div class="<?= $is_mini ? 'line-clamp-2 text-[1.4rem]' : ( $is_summary ? 'text-[2.4rem]' : 'text-[2rem] md:text-[2.4rem]' ) ?> font-bold leading-[1.2]"><?= esc_html( $details['title'] ) ?></div>
                <?php endif; ?>

                <?php if ( $details['location'] ) : ?>
                    <div class="<?= $is_mini ? 'text-[1.1rem]' : 'text-[1.4rem]' ?> text-blue-soft"><?= esc_html( $details['location'] ) ?></div>
                <?php endif; ?>

                <?php if ( $details['date'] || $details['time'] ) : ?>
                    <dl class="<?= $is_mini ? 'text-[1.1rem]' : 'text-[1.4rem]' ?> space-y-2 leading-[1.2]">
                        <?php if ( $details['date'] ) : ?>
                            <div class="flex gap-5"><dt><?= esc_html__( 'ΗΜ/ΝΙΑ:', 'com-theme' ) ?></dt><dd class="m-0 font-bold"><?= esc_html( $details['date'] ) ?></dd></div>
                        <?php endif; ?>
                        <?php if ( $details['time'] ) : ?>
                            <div class="flex gap-5"><dt><?= esc_html__( 'ΩΡΑ:', 'com-theme' ) ?></dt><dd class="m-0 font-bold"><?= esc_html( $details['time'] ) ?></dd></div>
                        <?php endif; ?>
                    </dl>
                <?php endif; ?>
            </div>

            <div class="<?= $is_mini ? 'space-y-5 text-[1.1rem]' : 'space-y-8 text-[1.4rem]' ?>">
                <?php foreach ( $details['groups'] as $group ) : ?>
                    <div class="flex items-start justify-between gap-15">
                        <span><?= esc_html( sprintf( '%d × %s', $group['count'], mb_strtoupper( $group['label'] ) ) ) ?></span>
                        <span class="shrink-0 font-bold"><?= wp_kses_post( wc_price( $group['total'] ) ) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ( ! $is_summary ) : ?>
                <div class="flex items-center justify-between gap-15 border-t border-dashed border-blue-soft pt-15">
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
                            class="cursor-pointer text-[1.2rem] underline underline-offset-4 transition-opacity group-[.product-quantity.loading]:pointer-events-none group-[.product-quantity.loading]:opacity-40"
                            data-product-quantity="change"
                            data-direction="-1"
                        ><?= esc_html__( 'Αφαίρεση', 'com-theme' ) ?></button>
                    </div>
                    <div
                        class="<?= $is_mini ? 'text-[1.4rem]' : 'text-[1.8rem]' ?> shrink-0 font-bold"
                        data-module-price-html
                        data-key="<?= esc_attr( $product_id . '_' . $variation_id ) ?>"
                    ><?= wp_kses_post( $line_price ) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</article>
