<?php
/**
 * Edit account details.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_edit_account_form' );

get_template_part( 'woocommerce/myaccount/page-title', null, [
    'eyebrow'     => __( 'Προφίλ και ασφάλεια', 'com-theme' ),
    'title'       => __( 'Στοιχεία σύνδεσης', 'com-theme' ),
    'description' => __( 'Ενημερώστε τα βασικά στοιχεία του λογαριασμού ή αλλάξτε τον κωδικό πρόσβασής σας.', 'com-theme' ),
] );
?>

<form class="woocommerce-EditAccountForm edit-account account-form" action="" method="post" <?php do_action( 'woocommerce_edit_account_form_tag' ); ?>>
    <?php do_action( 'woocommerce_edit_account_form_start' ); ?>

    <section>
        <h3 class="mb-20 text-[1.2rem] font-bold tracking-[.12em] text-blue-soft"><?= esc_html( com\theme::remove_accents( __( 'Προσωπικά στοιχεία', 'com-theme' ) ) ) ?></h3>
        <div class="grid gap-x-20 sm:grid-cols-2">
            <p class="form-row">
                <label for="account_first_name"><?= esc_html( com\theme::remove_accents( __( 'Όνομα', 'com-theme' ) ) ) ?> <span class="required" aria-hidden="true">*</span></label>
                <input type="text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?= esc_attr( $user->first_name ) ?>" aria-required="true">
            </p>
            <p class="form-row">
                <label for="account_last_name"><?= esc_html( com\theme::remove_accents( __( 'Επώνυμο', 'com-theme' ) ) ) ?> <span class="required" aria-hidden="true">*</span></label>
                <input type="text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?= esc_attr( $user->last_name ) ?>" aria-required="true">
            </p>
            <p class="form-row sm:col-span-2">
                <label for="account_email"><?= esc_html( com\theme::remove_accents( __( 'Διεύθυνση email', 'com-theme' ) ) ) ?> <span class="required" aria-hidden="true">*</span></label>
                <input type="email" name="account_email" id="account_email" autocomplete="email" value="<?= esc_attr( $user->user_email ) ?>" aria-required="true">
            </p>
        </div>

        <?php do_action( 'woocommerce_edit_account_form_fields' ); ?>
    </section>

    <fieldset class="mt-45 border-t border-dashed border-blue/25 pt-35">
        <legend class="mb-20 text-[1.2rem] font-bold tracking-[.12em] text-blue-soft"><?= esc_html( com\theme::remove_accents( __( 'Αλλαγή κωδικού', 'com-theme' ) ) ) ?></legend>
        <p class="mb-25 text-[1.4rem] leading-[1.45] text-blue/65"><?= esc_html__( 'Αφήστε τα πεδία κενά αν δεν θέλετε να αλλάξετε τον κωδικό σας.', 'com-theme' ) ?></p>

        <div class="grid gap-x-20 sm:grid-cols-2">
            <p class="form-row sm:col-span-2">
                <label for="password_current"><?= esc_html( com\theme::remove_accents( __( 'Τρέχων κωδικός', 'com-theme' ) ) ) ?></label>
                <input type="password" name="password_current" id="password_current" autocomplete="current-password">
            </p>
            <p class="form-row">
                <label for="password_1"><?= esc_html( com\theme::remove_accents( __( 'Νέος κωδικός', 'com-theme' ) ) ) ?></label>
                <input type="password" name="password_1" id="password_1" autocomplete="new-password">
            </p>
            <p class="form-row">
                <label for="password_2"><?= esc_html( com\theme::remove_accents( __( 'Επιβεβαίωση νέου κωδικού', 'com-theme' ) ) ) ?></label>
                <input type="password" name="password_2" id="password_2" autocomplete="new-password">
            </p>
        </div>
    </fieldset>

    <?php do_action( 'woocommerce_edit_account_form' ); ?>

    <div class="mt-35 flex justify-end border-t border-blue/15 pt-25">
        <?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
        <button type="submit" class="min-h-50 rounded-[.8rem] bg-blue px-25 text-[1.4rem] font-bold text-white" name="save_account_details" value="<?= esc_attr__( 'Αποθήκευση αλλαγών', 'com-theme' ) ?>">
            <?= esc_html__( 'Αποθήκευση αλλαγών', 'com-theme' ) ?>
        </button>
        <input type="hidden" name="account_display_name" value="<?= esc_attr( $user->display_name ) ?>">
        <input type="hidden" name="action" value="save_account_details">
    </div>

    <?php do_action( 'woocommerce_edit_account_form_end' ); ?>
</form>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>
