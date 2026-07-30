<?php
/**
 * Account overview.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$customer = new WC_Customer( $current_user->ID );
$phone = $customer->get_billing_phone();
$billing_address = wc_get_account_formatted_address( 'billing' );
$shipping_address = wc_get_account_formatted_address( 'shipping' );
$billing_edit_url = wc_get_endpoint_url( 'edit-address', 'billing' );
$shipping_edit_url = wc_get_endpoint_url( 'edit-address', 'shipping' );
$orders_count = com_theme_account_endpoint_count( 'orders' );
$tickets_count = com_theme_account_endpoint_count( 'tickets' );
?>

<?php
get_template_part( 'woocommerce/myaccount/page-title', null, [
    'eyebrow'     => __( 'Σύνοψη λογαριασμού', 'com-theme' ),
    'title'       => __( 'Επισκόπηση', 'com-theme' ),
    'description' => __( 'Ελέγξτε τα προσωπικά σας στοιχεία και μεταβείτε γρήγορα στις αγορές ή στα εισιτήριά σας.', 'com-theme' ),
] );
?>

<div class="space-y-50">
    <section>
        <div class="mb-20">
            <h3 class="m-0 text-[1.2rem] font-bold tracking-[.12em] text-blue-soft"><?= esc_html( com\theme::remove_accents( __( 'Αγορές', 'com-theme' ) ) ) ?></h3>
        </div>

        <div class="grid gap-15 sm:grid-cols-2">
            <?php if ( $tickets_count > 0 ) : ?>
                <a href="<?= esc_url( wc_get_account_endpoint_url( 'tickets' ) ) ?>" data-barba-prevent data-account-pages="link" class="group min-h-[18rem] rounded-[1.2rem] border border-blue/15 bg-white p-20 text-blue transition-colors hover:bg-ochre-light md:p-25">
                    <span class="flex items-start justify-between gap-20">
                        <span class="flex items-center gap-10 text-blue-soft">
                            <svg class="h-[2.2rem] w-[2.6rem] fill-current" aria-hidden="true"><use xlink:href="#icon-com-ticket"></use></svg>
                            <span class="text-[1.1rem] font-bold tracking-[.1em]"><?= esc_html( com\theme::remove_accents( __( 'Ενεργά εισιτήρια', 'com-theme' ) ) ) ?></span>
                        </span>
                        <span aria-hidden="true">→</span>
                    </span>
                    <span class="mt-20 block text-[1.3rem] not-italic leading-[1.5]">
                        <?= esc_html(
                            1 === $tickets_count
                                ? __( 'Έχετε 1 ενεργό εισιτήριο', 'com-theme' )
                                : sprintf( __( 'Έχετε %d ενεργά εισιτήρια', 'com-theme' ), $tickets_count )
                        ) ?>
                    </span>
                </a>
            <?php else : ?>
                <a href="<?= esc_url( wc_get_account_endpoint_url( 'tickets' ) ) ?>" data-barba-prevent data-account-pages="link" class="group min-h-[18rem] rounded-[1.2rem] border border-blue/15 bg-white p-20 text-blue transition-colors hover:bg-ochre-light md:p-25">
                    <span class="flex items-start justify-between gap-20">
                        <span class="flex items-center gap-10 text-blue-soft">
                            <svg class="h-[2.2rem] w-[2.6rem] shrink-0" aria-hidden="true"><use xlink:href="#icon-com-ticket"></use></svg>
                            <span class="text-[1.1rem] font-bold tracking-[.1em]"><?= esc_html( com\theme::remove_accents( __( 'Ενεργά εισιτήρια', 'com-theme' ) ) ) ?></span>
                        </span>
                        <span aria-hidden="true">→</span>
                    </span>
                    <span class="mt-20 block text-[1.3rem] not-italic leading-[1.5]"><?= esc_html__( 'Δεν έχουν εκδοθεί ακόμα εισιτήρια', 'com-theme' ) ?></span>
                </a>
            <?php endif; ?>

            <?php if ( $orders_count > 0 ) : ?>
                <a href="<?= esc_url( wc_get_account_endpoint_url( 'orders' ) ) ?>" data-barba-prevent data-account-pages="link" class="group min-h-[18rem] rounded-[1.2rem] border border-blue/15 bg-white p-20 text-blue transition-colors hover:bg-ochre-light md:p-25">
                    <span class="flex items-start justify-between gap-20">
                        <span class="flex items-center gap-10 text-blue-soft">
                            <svg class="size-25 fill-current" aria-hidden="true"><use xlink:href="#icon-cart"></use></svg>
                            <span class="text-[1.1rem] font-bold tracking-[.1em]"><?= esc_html( com\theme::remove_accents( __( 'Σύνολο αγορών', 'com-theme' ) ) ) ?></span>
                        </span>
                        <span aria-hidden="true">→</span>
                    </span>
                    <span class="mt-20 block text-[1.3rem] not-italic leading-[1.5]">
                        <?= esc_html(
                            1 === $orders_count
                                ? __( 'Έχετε κάνει 1 αγορά', 'com-theme' )
                                : sprintf( __( 'Έχετε κάνει %d αγορές', 'com-theme' ), $orders_count )
                        ) ?>
                    </span>
                </a>
            <?php else : ?>
                <a href="<?= esc_url( wc_get_account_endpoint_url( 'orders' ) ) ?>" data-barba-prevent data-account-pages="link" class="group min-h-[18rem] rounded-[1.2rem] border border-blue/15 bg-white p-20 text-blue transition-colors hover:bg-ochre-light md:p-25">
                    <span class="flex items-start justify-between gap-20">
                        <span class="flex items-center gap-10 text-blue-soft">
                            <svg class="size-25 shrink-0 fill-current" aria-hidden="true"><use xlink:href="#icon-cart"></use></svg>
                            <span class="text-[1.1rem] font-bold tracking-[.1em]"><?= esc_html( com\theme::remove_accents( __( 'Σύνολο αγορών', 'com-theme' ) ) ) ?></span>
                        </span>
                        <span aria-hidden="true">→</span>
                    </span>
                    <span class="mt-20 block text-[1.3rem] not-italic leading-[1.5]"><?= esc_html__( 'Δεν έχετε κάνει ακόμα κάποια αγορά', 'com-theme' ) ?></span>
                </a>
            <?php endif; ?>
        </div>
    </section>

    <section>
        <div class="mb-20 flex items-center justify-between gap-20">
            <h3 class="m-0 text-[1.2rem] font-bold tracking-[.12em] text-blue-soft"><?= esc_html( com\theme::remove_accents( __( 'Προσωπικά στοιχεία', 'com-theme' ) ) ) ?></h3>
            <a href="<?= esc_url( wc_get_account_endpoint_url( 'edit-account' ) ) ?>" data-barba-prevent data-account-pages="link" class="text-[1.3rem] underline underline-offset-4"><?= esc_html__( 'Επεξεργασία', 'com-theme' ) ?></a>
        </div>

        <dl class="grid overflow-hidden rounded-[1.2rem] border border-blue/15 sm:grid-cols-2">
            <div class="min-h-[8rem] border-b border-blue/15 p-20 sm:border-r md:p-25">
                <dt class="text-[1rem] tracking-[.12em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Ονοματεπώνυμο', 'com-theme' ) ) ) ?></dt>
                <dd class="m-0 mt-8 text-[1.3rem] font-normal leading-[1.5]"><?= esc_html( trim( $current_user->first_name . ' ' . $current_user->last_name ) ?: $current_user->display_name ) ?></dd>
            </div>
            <div class="min-h-[8rem] border-b border-blue/15 p-20 md:p-25">
                <dt class="text-[1rem] tracking-[.12em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Email', 'com-theme' ) ) ) ?></dt>
                <dd class="m-0 mt-8 break-words text-[1.3rem] font-normal leading-[1.5]"><?= esc_html( $current_user->user_email ) ?></dd>
            </div>
            <div class="min-h-[8rem] p-20 sm:col-span-2 md:p-25">
                <dt class="text-[1rem] tracking-[.12em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Τηλέφωνο', 'com-theme' ) ) ) ?></dt>
                <dd class="m-0 mt-8 text-[1.3rem] font-normal leading-[1.5]"><?= esc_html( $phone !== '' ? $phone : '—' ) ?></dd>
            </div>
        </dl>
    </section>

    <section>
        <div class="mb-20">
            <h3 class="m-0 text-[1.2rem] font-bold tracking-[.12em] text-blue-soft"><?= esc_html( com\theme::remove_accents( __( 'Διευθύνσεις', 'com-theme' ) ) ) ?></h3>
        </div>

        <div class="grid gap-15 sm:grid-cols-2">
            <article class="min-h-[18rem] rounded-[1.2rem] border border-blue/15 p-20 sm:col-span-2 md:p-25">
                <div class="mb-20 flex items-start justify-between gap-20">
                    <div class="flex items-center gap-10 text-blue-soft">
                        <svg class="size-20 fill-current" aria-hidden="true"><use xlink:href="#icon-location"></use></svg>
                        <h4 class="m-0 text-[1.1rem] font-bold tracking-[.1em]"><?= esc_html( com\theme::remove_accents( __( 'Διεύθυνση χρέωσης', 'com-theme' ) ) ) ?></h4>
                    </div>
                    <a href="<?= esc_url( $billing_edit_url ) ?>" data-barba-prevent data-account-pages="link" class="shrink-0 text-[1.3rem] underline underline-offset-4"><?= esc_html__( 'Επεξεργασία', 'com-theme' ) ?></a>
                </div>
                <address class="text-[1.3rem] not-italic leading-[1.5]"><?= $billing_address ? wp_kses_post( $billing_address ) : esc_html__( 'Δεν έχει οριστεί ακόμη.', 'com-theme' ) ?></address>
            </article>

            <article class="hidden min-h-[18rem] rounded-[1.2rem] border border-blue/15 p-20 md:p-25">
                <div class="mb-20 flex items-start justify-between gap-20">
                    <div class="flex items-center gap-10 text-blue-soft">
                        <svg class="size-20 fill-current" aria-hidden="true"><use xlink:href="#icon-location"></use></svg>
                        <h4 class="m-0 text-[1.1rem] font-bold tracking-[.1em]"><?= esc_html( com\theme::remove_accents( __( 'Διεύθυνση αποστολής', 'com-theme' ) ) ) ?></h4>
                    </div>
                    <a href="<?= esc_url( $shipping_edit_url ) ?>" data-barba-prevent data-account-pages="link" class="shrink-0 text-[1.3rem] underline underline-offset-4"><?= esc_html__( 'Επεξεργασία', 'com-theme' ) ?></a>
                </div>
                <address class="text-[1.3rem] not-italic leading-[1.5]"><?= $shipping_address ? wp_kses_post( $shipping_address ) : esc_html__( 'Δεν έχει οριστεί ακόμη.', 'com-theme' ) ?></address>
            </article>
        </div>
    </section>

</div>
