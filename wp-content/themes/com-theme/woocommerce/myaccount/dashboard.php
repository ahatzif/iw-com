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
    'eyebrow'     => __( 'Επισκόπηση', 'com-theme' ),
    'title'       => __( 'Τα στοιχεία μου', 'com-theme' ),
    'description' => __( 'Ελέγξτε τα προσωπικά σας στοιχεία και μεταβείτε γρήγορα στις αγορές ή στα εισιτήριά σας.', 'com-theme' ),
] );
?>

<div class="space-y-50">
    <section>
        <div class="mb-20 flex items-center justify-between gap-20">
            <h3 class="m-0 text-[1.2rem] font-bold tracking-[.12em] text-blue-soft"><?= esc_html( com\theme::remove_accents( __( 'Προσωπικά στοιχεία', 'com-theme' ) ) ) ?></h3>
            <a href="<?= esc_url( wc_get_account_endpoint_url( 'edit-account' ) ) ?>" data-barba-prevent data-account-pages="link" class="text-[1.3rem] underline underline-offset-4"><?= esc_html__( 'Επεξεργασία', 'com-theme' ) ?></a>
        </div>

        <dl class="grid overflow-hidden rounded-[1.2rem] border border-blue/15 sm:grid-cols-2">
            <div class="border-b border-blue/15 p-20 sm:border-r md:p-25">
                <dt class="text-[1rem] tracking-[.12em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Ονοματεπώνυμο', 'com-theme' ) ) ) ?></dt>
                <dd class="m-0 mt-8 text-[1.8rem] font-bold"><?= esc_html( trim( $current_user->first_name . ' ' . $current_user->last_name ) ?: $current_user->display_name ) ?></dd>
            </div>
            <div class="border-b border-blue/15 p-20 md:p-25">
                <dt class="text-[1rem] tracking-[.12em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Email', 'com-theme' ) ) ) ?></dt>
                <dd class="m-0 mt-8 break-words text-[1.6rem]"><?= esc_html( $current_user->user_email ) ?></dd>
            </div>
            <div class="p-20 sm:col-span-2 md:p-25">
                <dt class="text-[1rem] tracking-[.12em] text-blue/50"><?= esc_html( com\theme::remove_accents( __( 'Τηλέφωνο', 'com-theme' ) ) ) ?></dt>
                <dd class="m-0 mt-8 text-[1.6rem]"><?= esc_html( $phone !== '' ? $phone : '—' ) ?></dd>
            </div>
        </dl>
    </section>

    <section>
        <div class="mb-20">
            <h3 class="m-0 text-[1.2rem] font-bold tracking-[.12em] text-blue-soft"><?= esc_html( com\theme::remove_accents( __( 'Διευθύνσεις', 'com-theme' ) ) ) ?></h3>
        </div>

        <div class="grid gap-15 sm:grid-cols-2">
            <article class="min-h-[18rem] rounded-[1.2rem] border border-blue/15 p-20 md:p-25 ">
                <div class="mb-20 flex items-start justify-between gap-20">
                    <div class="flex items-center gap-10 text-blue-soft">
                        <svg class="size-20 fill-current" aria-hidden="true"><use xlink:href="#icon-location"></use></svg>
                        <h4 class="m-0 text-[1.1rem] font-bold tracking-[.1em]"><?= esc_html( com\theme::remove_accents( __( 'Διεύθυνση χρέωσης', 'com-theme' ) ) ) ?></h4>
                    </div>
                    <a href="<?= esc_url( $billing_edit_url ) ?>" data-barba-prevent data-account-pages="link" class="shrink-0 text-[1.3rem] underline underline-offset-4"><?= esc_html__( 'Επεξεργασία', 'com-theme' ) ?></a>
                </div>
                <address class="text-[1.3rem] not-italic leading-[1.5]"><?= $billing_address ? wp_kses_post( $billing_address ) : esc_html__( 'Δεν έχει οριστεί ακόμη.', 'com-theme' ) ?></address>
            </article>

            <article class="min-h-[18rem] rounded-[1.2rem] border border-blue/15 p-20 md:p-25 ">
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

    <section>
        <div class="mb-20">
            <h3 class="m-0 text-[1.2rem] font-bold tracking-[.12em] text-blue-soft"><?= esc_html( com\theme::remove_accents( __( 'Αγορές', 'com-theme' ) ) ) ?></h3>
        </div>

        <div class="grid gap-15 sm:grid-cols-2">
            <a href="<?= esc_url( wc_get_account_endpoint_url( 'tickets' ) ) ?>" data-barba-prevent data-account-pages="link" class="group min-h-[18rem] rounded-[1.2rem] border border-blue/15 p-20 text-blue transition-colors hover:bg-ochre-light md:p-25">
                <span class="flex items-start justify-between gap-20">
                    <span class="flex items-center gap-10 text-blue-soft">
                        <svg class="h-[2.2rem] w-[2.6rem] fill-current" aria-hidden="true"><use xlink:href="#icon-com-ticket"></use></svg>
                        <span class="text-[1.1rem] font-bold tracking-[.1em]"><?= esc_html( com\theme::remove_accents( __( 'Ενεργά εισιτήρια', 'com-theme' ) ) ) ?></span>
                    </span>
                    <span aria-hidden="true">→</span>
                </span>
                <strong class="mt-20 block text-[3rem] font-medium"><?= esc_html( (string) $tickets_count ) ?></strong>
            </a>

            <a href="<?= esc_url( wc_get_account_endpoint_url( 'orders' ) ) ?>" data-barba-prevent data-account-pages="link" class="group min-h-[18rem] rounded-[1.2rem] border border-blue/15 p-20 text-blue transition-colors hover:bg-ochre-light md:p-25">
                <span class="flex items-start justify-between gap-20">
                    <span class="flex items-center gap-10 text-blue-soft">
                        <svg class="size-25 fill-current" aria-hidden="true"><use xlink:href="#icon-cart"></use></svg>
                        <span class="text-[1.1rem] font-bold tracking-[.1em]"><?= esc_html( com\theme::remove_accents( __( 'Σύνολο αγορών', 'com-theme' ) ) ) ?></span>
                    </span>
                    <span aria-hidden="true">→</span>
                </span>
                <strong class="mt-20 block text-[3rem] font-medium"><?= esc_html( (string) $orders_count ) ?></strong>
            </a>
        </div>
    </section>

    <section
        class="border-t border-dashed border-blue/25 pt-40"
        data-module-account-delete
        data-delete-error="<?= esc_attr__( 'Δεν ήταν δυνατή η διαγραφή του λογαριασμού.', 'com-theme' ) ?>"
        data-generic-error="<?= esc_attr__( 'Παρουσιάστηκε ένα σφάλμα. Παρακαλούμε δοκιμάστε ξανά.', 'com-theme' ) ?>"
    >

        <p class="m-0 max-w-[68rem] text-[1.3rem] underline  text-blue/70 cursor-pointer"
        data-account-delete = "open"
        aria-haspopup       = "dialog"
        aria-controls       = "account-delete-dialog"
        >
            <?= esc_html__( 'Διαγραφή λογαριασμού.', 'com-theme' ) ?>
        </p>


        <div
            id="account-delete-dialog"
            class="invisible fixed inset-0 z-[90] flex items-center justify-center p-20 opacity-0 pointer-events-none transition-opacity duration-300 [&.is-open]:visible [&.is-open]:opacity-100 [&.is-open]:pointer-events-auto"
            data-account-delete="modal"
            role="dialog"
            aria-modal="true"
            aria-hidden="true"
            aria-labelledby="account-delete-title"
        >
            <div class="absolute inset-0 bg-blue/30" data-account-delete="backdrop"></div>

            <div class="relative z-1 w-full max-w-[82rem] rounded-[2rem] bg-dark p-25 text-white shadow-[0_2rem_6rem_rgba(23,50,118,.25)] md:p-50 lg:p-60">
                <button
                    type="button"
                    class="absolute right-20 top-20 flex size-[56px] items-center justify-center rounded-full border border-white transition-colors hover:bg-white hover:text-dark md:right-30 md:top-30 md:size-[64px]"
                    data-account-delete="close"
                    aria-label="<?= esc_attr__( 'Κλείσιμο', 'com-theme' ) ?>"
                >
                    <svg class="size-[20px] fill-current md:size-[24px]" aria-hidden="true">
                        <use xlink:href="#icon-close-thin"></use>
                    </svg>
                </button>

                <form action="<?= esc_url( admin_url( 'admin-ajax.php' ) ) ?>" method="post" data-account-delete="form">
                    <div class="max-w-[64rem] pr-35">
                        <h4 id="account-delete-title" class="m-0 text-[2rem] font-bold tracking-[.04em] md:text-[2.6rem]">
                            <?= esc_html( com\theme::remove_accents( __( 'Διαγραφή λογαριασμού', 'com-theme' ) ) ) ?>
                        </h4>
                        <p class="mb-0 mt-25 text-[1.6rem] leading-[1.45] md:text-[1.8rem]">
                            <?= esc_html__( 'Είστε βέβαιος ότι θέλετε να διαγράψετε τον λογαριασμό σας;', 'com-theme' ) ?>
                        </p>
                        <p class="mb-0 mt-20 text-[1.5rem] leading-[1.5] text-white/80 md:text-[1.7rem]">
                            <?= esc_html__( 'Η ενέργεια αυτή είναι μη αναστρέψιμη. Όλα τα προσωπικά σας δεδομένα, το ιστορικό σας και η πρόσβασή σας στις υπηρεσίες μας θα διαγραφούν οριστικά.', 'com-theme' ) ?>
                        </p>
                    </div>

                    <p class="mt-25 hidden rounded-[.8rem] border border-error/50 bg-error/15 p-15 text-[1.3rem] text-white" data-account-delete="message" role="alert"></p>

                    <div class="mt-40 flex flex-col-reverse gap-20 sm:flex-row sm:items-center sm:justify-between">
                        <button type="button" class="self-start text-[1.4rem] underline underline-offset-4" data-account-delete="close">
                            <?= esc_html__( 'Επιστροφή', 'com-theme' ) ?>
                        </button>
                        <button type="submit" class="inline-flex min-h-50 items-center justify-center rounded-full bg-error px-30 text-[1.4rem] font-bold text-white transition-opacity hover:opacity-85 disabled:cursor-wait disabled:opacity-50">
                            <?= esc_html( com\theme::remove_accents( __( 'Διαγραφή', 'com-theme' ) ) ) ?>
                        </button>
                    </div>

                    <input type="hidden" name="nonce" value="<?= esc_attr( wp_create_nonce( 'iw-auth-delete-account_delete_account' ) ) ?>">
                    <input type="hidden" name="action" value="iw-auth-delete-account">
                </form>
            </div>
        </div>
    </section>
</div>
