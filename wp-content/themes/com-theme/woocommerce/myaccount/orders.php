<?php
/**
 * Customer orders.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_orders', $has_orders );

get_template_part( 'woocommerce/myaccount/page-title', null, [
    'eyebrow'     => __( 'Ιστορικό', 'com-theme' ),
    'title'       => __( 'Οι αγορές μου', 'com-theme' ),
    'description' => __( 'Δείτε την κατάσταση και τις λεπτομέρειες όλων των αγορών σας.', 'com-theme' ),
] );
?>

<?php if ( $has_orders ) : ?>
    <div class="space-y-15">
        <?php foreach ( $customer_orders->orders as $customer_order ) :
            $order = wc_get_order( $customer_order );
            if ( ! $order ) {
                continue;
            }

            $items = $order->get_items();
            $first_item = $items ? reset( $items ) : null;
            $content_id = $first_item ? absint( $first_item->get_meta( 'tickets_for_id', true ) ) : 0;
            $product = $first_item && is_callable( [ $first_item, 'get_product' ] ) ? $first_item->get_product() : null;
            $image_id = $content_id ? com_theme_museum_image_id( $content_id ) : ( $product ? absint( $product->get_image_id() ) : 0 );
            $item_count = $order->get_item_count() - $order->get_item_count_refunded();
            $actions = wc_get_account_orders_actions( $order );
        ?>
            <article class="overflow-hidden rounded-[1.2rem] border border-blue/15">
                <div class="grid gap-20 p-20 sm:grid-cols-[12rem_1fr] md:p-25">
                    <div class="aspect-[4/3] overflow-hidden rounded-[.8rem] bg-ochre-light">
                        <?php if ( $image_id ) : ?>
                            <?= wp_get_attachment_image( $image_id, 'medium', false, [ 'class' => 'size-full object-cover' ] ) ?>
                        <?php else : ?>
                            <div class="flex size-full items-center justify-center text-blue/25">
                                <svg class="size-40 fill-current" aria-hidden="true"><use xlink:href="#icon-com-ticket"></use></svg>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-col gap-15 border-b border-dashed border-blue/25 pb-20 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="mb-8 text-[1rem] uppercase tracking-[.12em] text-blue/50"><?= esc_html__( 'Αριθμός αγοράς', 'com-theme' ) ?></p>
                                <a href="<?= esc_url( $order->get_view_order_url() ) ?>" data-barba-prevent data-account-pages="link" class="text-[2rem] font-bold underline decoration-blue/30 underline-offset-4">
                                    #<?= esc_html( $order->get_order_number() ) ?>
                                </a>
                            </div>
                            <span class="self-start rounded-full bg-ochre px-15 py-8 text-[1.2rem] font-bold"><?= esc_html( wc_get_order_status_name( $order->get_status() ) ) ?></span>
                        </div>

                        <dl class="mt-20 grid grid-cols-2 gap-x-20 gap-y-15 text-[1.3rem] lg:grid-cols-4">
                            <div>
                                <dt class="text-[1rem] uppercase tracking-[.1em] text-blue/50"><?= esc_html__( 'Ημερομηνία', 'com-theme' ) ?></dt>
                                <dd class="m-0 mt-5 font-bold"><?= esc_html( wc_format_datetime( $order->get_date_created(), 'd/m/Y' ) ) ?></dd>
                            </div>
                            <div>
                                <dt class="text-[1rem] uppercase tracking-[.1em] text-blue/50"><?= esc_html__( 'Είδη', 'com-theme' ) ?></dt>
                                <dd class="m-0 mt-5 font-bold"><?= esc_html( (string) $item_count ) ?></dd>
                            </div>
                            <div>
                                <dt class="text-[1rem] uppercase tracking-[.1em] text-blue/50"><?= esc_html__( 'Σύνολο', 'com-theme' ) ?></dt>
                                <dd class="m-0 mt-5 font-bold"><?= wp_kses_post( $order->get_formatted_order_total() ) ?></dd>
                            </div>
                            <div>
                                <dt class="text-[1rem] uppercase tracking-[.1em] text-blue/50"><?= esc_html__( 'Πληρωμή', 'com-theme' ) ?></dt>
                                <dd class="m-0 mt-5 font-bold"><?= esc_html( $order->get_payment_method_title() ?: '—' ) ?></dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <?php if ( $actions ) : ?>
                    <footer class="flex flex-wrap justify-end gap-10 border-t border-blue/15 bg-ochre-light px-20 py-15 md:px-25">
                        <?php foreach ( $actions as $key => $action ) : ?>
                            <a
                                href="<?= esc_url( $action['url'] ) ?>"
                                class="inline-flex min-h-40 items-center justify-center rounded-[.6rem] border border-blue px-15 text-[1.2rem] font-bold transition-colors hover:bg-blue hover:text-white"
                                <?= $key === 'view' ? 'data-barba-prevent data-account-pages="link"' : 'data-barba-prevent' ?>
                            ><?= esc_html( $action['name'] ) ?></a>
                        <?php endforeach; ?>
                    </footer>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ( 1 < $customer_orders->max_num_pages ) : ?>
        <nav class="mt-40 flex items-center justify-between gap-20 border-t border-blue/20 pt-25" aria-label="<?= esc_attr__( 'Σελιδοποίηση αγορών', 'com-theme' ) ?>">
            <?php if ( 1 !== $current_page ) : ?>
                <a href="<?= esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ) ?>" data-barba-prevent data-account-pages="link" class="text-[1.4rem] underline underline-offset-4">← <?= esc_html__( 'Προηγούμενη', 'com-theme' ) ?></a>
            <?php else : ?><span></span><?php endif; ?>
            <?php if ( (int) $customer_orders->max_num_pages !== (int) $current_page ) : ?>
                <a href="<?= esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ) ?>" data-barba-prevent data-account-pages="link" class="text-[1.4rem] underline underline-offset-4"><?= esc_html__( 'Επόμενη', 'com-theme' ) ?> →</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php else : ?>
    <div class="rounded-[1.2rem] bg-ochre-light p-25 md:p-40">
        <svg class="mb-25 h-[3.2rem] w-[3.8rem] fill-blue" aria-hidden="true"><use xlink:href="#icon-com-ticket"></use></svg>
        <h3 class="m-0 text-[2.2rem] font-bold"><?= esc_html__( 'Δεν υπάρχουν αγορές ακόμη', 'com-theme' ) ?></h3>
        <p class="mb-25 mt-10 max-w-[48rem] text-[1.5rem] leading-[1.45]"><?= esc_html__( 'Μόλις ολοκληρώσετε μια αγορά, θα εμφανιστεί εδώ μαζί με την κατάστασή της.', 'com-theme' ) ?></p>
        <a href="<?= esc_url( com_theme_page_url( 'buy-tickets' ) ) ?>" class="inline-flex min-h-50 items-center justify-center rounded-[.8rem] bg-blue px-25 text-[1.4rem] text-white"><?= esc_html__( 'Αγορά εισιτηρίων', 'com-theme' ) ?> →</a>
    </div>
<?php endif; ?>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
