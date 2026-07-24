<?php
/**
 * Edit customer address.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

$page_title = $load_address === 'billing'
    ? __( 'Διεύθυνση χρέωσης', 'com-theme' )
    : __( 'Διεύθυνση αποστολής', 'com-theme' );

do_action( 'woocommerce_before_edit_account_address_form' );
?>

<?php if ( ! $load_address ) : ?>
    <?php wc_get_template( 'myaccount/my-address.php' ); ?>
<?php else : ?>
    <?php
    get_template_part( 'woocommerce/myaccount/page-title', null, [
        'eyebrow'     => __( 'Επεξεργασία', 'com-theme' ),
        'title'       => $page_title,
        'description' => __( 'Συμπληρώστε τα στοιχεία όπως θέλετε να εμφανίζονται στις αγορές σας.', 'com-theme' ),
    ] );
    ?>

    <form method="post" novalidate class="account-form">
        <div class="woocommerce-address-fields">
            <?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>

            <div class="woocommerce-address-fields__field-wrapper grid gap-x-20 sm:grid-cols-2">
                <?php
                foreach ( $address as $key => $field ) {
                    $field['class'] = array_values( array_unique( array_merge( (array) ( $field['class'] ?? [] ), [ 'form-row' ] ) ) );
                    if ( in_array( $key, [ 'billing_address_1', 'billing_address_2', 'shipping_address_1', 'shipping_address_2', 'billing_company', 'shipping_company' ], true ) ) {
                        $field['class'][] = 'sm:col-span-2';
                    }
                    woocommerce_form_field( $key, $field, wc_get_post_data_by_key( $key, $field['value'] ?? '' ) );
                }
                ?>
            </div>

            <?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>

            <div class="mt-35 flex flex-wrap items-center justify-between gap-15 border-t border-blue/15 pt-25">
                <a href="<?= esc_url( wc_get_account_endpoint_url( 'edit-address' ) ) ?>" data-barba-prevent data-account-pages="link" class="text-[1.3rem] underline underline-offset-4">← <?= esc_html__( 'Ακύρωση', 'com-theme' ) ?></a>
                <button type="submit" class="min-h-50 rounded-[.8rem] bg-blue px-25 text-[1.4rem] font-bold text-white" name="save_address" value="<?= esc_attr__( 'Αποθήκευση διεύθυνσης', 'com-theme' ) ?>">
                    <?= esc_html__( 'Αποθήκευση διεύθυνσης', 'com-theme' ) ?>
                </button>
                <?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
                <input type="hidden" name="action" value="edit_address">
            </div>
        </div>
    </form>
<?php endif; ?>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>
