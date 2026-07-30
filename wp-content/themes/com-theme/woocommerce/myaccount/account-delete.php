<?php
/**
 * Account deletion controls.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;
?>

<section
    class="mt-45 border-t border-dashed border-blue/25 pt-40"
    data-module-account-delete
    data-delete-error="<?= esc_attr__( 'Δεν ήταν δυνατή η διαγραφή του λογαριασμού.', 'com-theme' ) ?>"
    data-generic-error="<?= esc_attr__( 'Παρουσιάστηκε ένα σφάλμα. Παρακαλούμε δοκιμάστε ξανά.', 'com-theme' ) ?>"
>
    <button
        type="button"
        class="m-0 cursor-pointer border-0 bg-transparent p-0 text-[1.3rem] text-blue/70 underline"
        data-account-delete="open"
        aria-haspopup="dialog"
        aria-controls="account-delete-dialog"
    >
        <?= esc_html__( 'Διαγραφή λογαριασμού', 'com-theme' ) ?>
    </button>

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
