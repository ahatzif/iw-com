<?php
/**
 * Guest order email verification.
 *
 * @package com-theme
 * @version 7.9.0
 *
 * @var bool   $failed_submission Whether the previous verification failed.
 * @var string $verify_url        Verification form action.
 */

defined( 'ABSPATH' ) || exit;
?>

<main class="order-email-verification min-h-screen bg-blue px-1/12 pb-100 pt-[14rem] text-ochre md:px-1/24 md:pb-120 md:pt-[18rem] lg:px-2/24">
    <div>
        <header class="mb-50 border-b border-white pb-40 md:mb-60 md:pb-50">
            <p class="mb-15 text-[1rem] font-medium tracking-[.18em] text-ochre/60">
                <?= esc_html( com\theme::remove_accents( __( 'Πρόσβαση στην παραγγελία', 'com-theme' ) ) ) ?>
            </p>
            <h1 class="m-0 max-w-[80rem] text-[4rem] font-medium leading-[1.05] md:text-[6rem]">
                <?= esc_html__( 'Επιβεβαίωση email', 'com-theme' ) ?>
            </h1>
            <p class="mb-0 mt-20 max-w-[70rem] text-[1.6rem] leading-[1.35] md:text-[2rem]">
                <?= esc_html__( 'Για την προστασία των στοιχείων σας, πληκτρολογήστε το email που χρησιμοποιήσατε κατά την αγορά.', 'com-theme' ) ?>
            </p>
        </header>

        <form
            name="checkout"
            method="post"
            class="woocommerce-form woocommerce-verify-email rounded-[1.5rem] bg-white p-20 text-blue md:px-1/24 md:py-40 lg:py-60"
            action="<?= esc_url( $verify_url ) ?>"
        >
            <?php wp_nonce_field( 'wc_verify_email', 'check_submission' ); ?>

            <?php if ( $failed_submission ) : ?>
                <div class="mb-30 rounded-[1rem] bg-red-50 px-20 py-15 text-[1.4rem] leading-[1.4] text-red-800" role="alert">
                    <?= esc_html__( 'Το email δεν αντιστοιχεί στην παραγγελία. Ελέγξτε το και δοκιμάστε ξανά.', 'com-theme' ) ?>
                </div>
            <?php endif; ?>

            <div>
                <label for="email" class="mb-10 block text-[1.2rem] font-medium tracking-[.14em] text-blue/60">
                    <?= esc_html( com\theme::remove_accents( __( 'Διεύθυνση email', 'com-theme' ) ) ) ?>
                </label>
                <input
                    type="email"
                    class="order-email-verification__input input-text"
                    name="email"
                    id="email"
                    autocomplete="email"
                    inputmode="email"
                    required
                    autofocus
                >

                <?php
                get_template_part(
                    'templates/parts/com-button',
                    null,
                    [
                        'tag'        => 'button',
                        'type'       => 'submit',
                        'label'      => __( 'Προβολή παραγγελίας', 'com-theme' ),
                        'variant'    => 'blue',
                        'size'       => 'large',
                        'classes'    => 'mt-20',
                        'attributes' => [
                            'name'  => 'verify',
                            'value' => '1',
                        ],
                    ]
                );
                ?>

                <p class="mb-0 mt-25 text-[1.3rem] leading-[1.5] text-blue/60">
                    <?= esc_html__( 'Έχετε λογαριασμό;', 'com-theme' ) ?>
                    <button
                        type="button"
                        class="font-medium text-blue underline"
                        data-auth-modal-trigger
                    >
                        <?= esc_html__( 'Συνδεθείτε εδώ', 'com-theme' ) ?>
                    </button>
                </p>
            </div>
        </form>
    </div>
</main>
