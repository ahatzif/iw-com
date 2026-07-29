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

$field_labels = [
    'billing_first_name'          => __( 'Όνομα', 'com-theme' ),
    'billing_last_name'           => __( 'Επώνυμο', 'com-theme' ),
    'billing_company'             => __( 'Εταιρεία', 'com-theme' ),
    'billing_country'             => __( 'Χώρα / Περιοχή', 'com-theme' ),
    'billing_address_1'           => __( 'Διεύθυνση', 'com-theme' ),
    'billing_address_2'           => __( 'Όροφος, διαμέρισμα κ.λπ.', 'com-theme' ),
    'billing_city'                => __( 'Πόλη', 'com-theme' ),
    'billing_state'               => __( 'Περιφέρεια', 'com-theme' ),
    'billing_postcode'            => __( 'Ταχυδρομικός κώδικας', 'com-theme' ),
    'billing_phone'               => __( 'Τηλέφωνο', 'com-theme' ),
    'billing_email'               => __( 'Email', 'com-theme' ),
    'billing_tax_number'          => __( 'Α.Φ.Μ.', 'com-theme' ),
    'billing_company_name'        => __( 'Επωνυμία επιχείρησης', 'com-theme' ),
    'billing_company_profession'  => __( 'Δραστηριότητα επιχείρησης', 'com-theme' ),
    'billing_tax_office'          => __( 'Δ.Ο.Υ.', 'com-theme' ),
    'shipping_first_name'         => __( 'Όνομα', 'com-theme' ),
    'shipping_last_name'          => __( 'Επώνυμο', 'com-theme' ),
    'shipping_company'            => __( 'Εταιρεία', 'com-theme' ),
    'shipping_country'            => __( 'Χώρα / Περιοχή', 'com-theme' ),
    'shipping_address_1'          => __( 'Διεύθυνση', 'com-theme' ),
    'shipping_address_2'          => __( 'Όροφος, διαμέρισμα κ.λπ.', 'com-theme' ),
    'shipping_city'               => __( 'Πόλη', 'com-theme' ),
    'shipping_state'              => __( 'Περιφέρεια', 'com-theme' ),
    'shipping_postcode'           => __( 'Ταχυδρομικός κώδικας', 'com-theme' ),
    'shipping_phone'              => __( 'Τηλέφωνο', 'com-theme' ),
];

$field_order = $load_address === 'billing'
    ? [
        'billing_first_name',
        'billing_last_name',
        'billing_email',
        'billing_phone',
        'billing_country',
        'billing_state',
        'billing_address_1',
        'billing_address_2',
        'billing_postcode',
        'billing_city',
        'billing_company',
    ]
    : [
        'shipping_first_name',
        'shipping_last_name',
        'shipping_company',
        'shipping_country',
        'shipping_state',
        'shipping_address_1',
        'shipping_address_2',
        'shipping_postcode',
        'shipping_city',
        'shipping_phone',
    ];

$invoice_fields = [
    'billing_tax_number',
    'billing_company_name',
    'billing_company_profession',
    'billing_tax_office',
];

$billing_country = $load_address === 'billing'
    ? wc_get_post_data_by_key( 'billing_country', $address['billing_country']['value'] ?? 'GR' )
    : '';
$billing_tax_number = $load_address === 'billing'
    ? wc_get_post_data_by_key( 'billing_tax_number', $address['billing_tax_number']['value'] ?? '' )
    : '';
$verified_company = [];
if ( $load_address === 'billing' && class_exists( 'IW_WC_Customizations' ) ) {
    $verified_company = strtoupper( $billing_country ?: 'GR' ) === 'GR'
        ? IW_WC_Customizations::get_saved_aade_company_info( $billing_tax_number )
        : IW_WC_Customizations::get_saved_vies_company_info( $billing_country, $billing_tax_number );
}
$verified_company_preview = class_exists( 'IW_WC_Customizations' )
    ? IW_WC_Customizations::get_company_preview_args( $verified_company )
    : [];

$wide_fields = [
    'billing_address_1',
    'billing_address_2',
    'billing_company',
    'shipping_address_1',
    'shipping_address_2',
    'shipping_company',
];

$prepare_field = static function ( array $field, string $key ): array {
    $layout_classes = [ 'form-row', 'form-row-first', 'form-row-last', 'form-row-wide' ];
    $field['class'] = array_values( array_diff( (array) ( $field['class'] ?? [] ), $layout_classes ) );
    $field['class'][] = 'account-address-field__row';

    $field['input_class'] = array_values( array_unique( array_merge(
        (array) ( $field['input_class'] ?? [] ),
        [ 'account-address-field__control' ]
    ) ) );
    $field['label'] = '';
    $field['placeholder'] = '';
    $field['return'] = true;

    if ( $key === 'billing_state' || $key === 'shipping_state' ) {
        $field['placeholder'] = __( 'Επιλέξτε περιφέρεια', 'com-theme' );
    }

    if ( ! empty( $field['required'] ) ) {
        $field['custom_attributes'] = array_merge(
            (array) ( $field['custom_attributes'] ?? [] ),
            [ 'required' => 'required' ]
        );
    }

    return $field;
};

$render_field = static function ( string $key, array $field, bool $invoice_field = false ) use ( $field_labels, $wide_fields, $prepare_field ): void {
    $label = com\theme::remove_accents( $field_labels[ $key ] ?? ( $field['label'] ?? '' ) );
    $field_id = $field['id'] ?? $key;
    $wrapper_classes = [ 'account-address-field' ];

    if ( in_array( $key, $wide_fields, true ) ) {
        $wrapper_classes[] = 'account-address-field--wide';
    }

    $field_html = woocommerce_form_field(
        $key,
        $prepare_field( $field, $key ),
        wc_get_post_data_by_key( $key, $field['value'] ?? '' )
    );
    ?>
    <div
        class="<?= esc_attr( implode( ' ', $wrapper_classes ) ) ?>"
        data-address-field="<?= esc_attr( $key ) ?>"
        <?= $invoice_field ? 'data-invoice-field="' . esc_attr( $key ) . '"' : '' ?>
    >
        <?php if ( $label ) : ?>
            <label class="account-address-field__label" for="<?= esc_attr( $field_id ) ?>">
                <?= esc_html( $label ) ?>
                <span class="account-address-field__required-marker required" aria-hidden="true">*</span>
            </label>
        <?php endif; ?>
        <?php
        // The field markup is generated and escaped by WooCommerce.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $field_html;
        ?>
    </div>
    <?php
};

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

    <form
        method="post"
        class="account-form account-address-form"
        aria-label="<?= esc_attr( $page_title ) ?>"
    >
        <div class="woocommerce-address-fields">
            <?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>

            <?php if ( $load_address === 'billing' && ! empty( $address['billing_receipt_type'] ) ) :
                $receipt_field = $address['billing_receipt_type'];
                $receipt_type = wc_get_post_data_by_key(
                    'billing_receipt_type',
                    $receipt_field['value'] ?? 'receipt'
                );
                $receipt_type = in_array( $receipt_type, [ 'receipt', 'invoice' ], true ) ? $receipt_type : 'receipt';
            ?>
                <fieldset class="account-address-form__section account-address-form__receipt">
                    <legend class="account-address-form__section-title">
                        <?= esc_html( com\theme::remove_accents( __( 'Είδος παραστατικού', 'com-theme' ) ) ) ?>
                        <abbr class="required" title="<?= esc_attr__( 'υποχρεωτικό', 'com-theme' ) ?>">*</abbr>
                    </legend>
                    <div class="account-address-form__radio-group">
                        <?php foreach ( [
                            'receipt' => __( 'Απόδειξη', 'com-theme' ),
                            'invoice' => __( 'Τιμολόγιο', 'com-theme' ),
                        ] as $value => $label ) : ?>
                            <label class="account-address-radio">
                                <input
                                    type="radio"
                                    name="billing_receipt_type"
                                    value="<?= esc_attr( $value ) ?>"
                                    <?= checked( $receipt_type, $value, false ) ?>
                                    required
                                >
                                <span aria-hidden="true"></span>
                                <?= esc_html( $label ) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <section class="account-address-form__section account-address-form__invoice-fields">
                    <h2 class="account-address-form__section-title">
                        <?= esc_html( com\theme::remove_accents( __( 'Στοιχεία τιμολογίου', 'com-theme' ) ) ) ?>
                    </h2>
                    <div class="account-address-form__grid">
                        <?php foreach ( $invoice_fields as $key ) :
                            if ( empty( $address[ $key ] ) ) {
                                continue;
                            }
                            if ( $key === 'billing_tax_number' ) {
                                $tax_field = $prepare_field( $address[ $key ], $key );
                                $tax_field_html = woocommerce_form_field(
                                    $key,
                                    $tax_field,
                                    wc_get_post_data_by_key( $key, $address[ $key ]['value'] ?? '' )
                                );
                                get_template_part(
                                    'templates/parts/woocommerce/tax-number-lookup',
                                    null,
                                    [
                                        'context'      => 'account',
                                        'field_html'   => $tax_field_html,
                                        'verified'     => ! empty( $verified_company ),
                                        'company_name' => $verified_company_preview['company_name'] ?? '',
                                        'company_info' => $verified_company_preview['company_info'] ?? '',
                                        'label'        => com\theme::remove_accents( $field_labels[ $key ] ?? '' ),
                                        'field_id'     => $tax_field['id'] ?? $key,
                                    ]
                                );
                                continue;
                            }
                            $render_field( $key, $address[ $key ], true );
                        endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="account-address-form__section">
                <h2 class="account-address-form__section-title">
                    <?= esc_html( com\theme::remove_accents(
                        $load_address === 'billing'
                            ? __( 'Στοιχεία χρέωσης', 'com-theme' )
                            : __( 'Στοιχεία αποστολής', 'com-theme' )
                    ) ) ?>
                </h2>
                <div class="account-address-form__field-wrapper account-address-form__grid">
                    <?php
                    $rendered_fields = array_merge(
                        $field_order,
                        $load_address === 'billing' ? array_merge( [ 'billing_receipt_type' ], $invoice_fields ) : []
                    );

                    foreach ( $field_order as $key ) {
                        if ( empty( $address[ $key ] ) ) {
                            continue;
                        }
                        $render_field( $key, $address[ $key ] );
                    }

                    foreach ( $address as $key => $field ) {
                        if ( in_array( $key, $rendered_fields, true ) ) {
                            continue;
                        }
                        $render_field( $key, $field );
                    }
                    ?>
                </div>
            </section>

            <?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>

            <div class="account-address-form__actions">
                <a href="<?= esc_url( wc_get_account_endpoint_url( 'edit-address' ) ) ?>" data-barba-prevent data-account-pages="link" class="account-address-form__cancel">← <?= esc_html__( 'Ακύρωση', 'com-theme' ) ?></a>
                <button type="submit" class="account-address-form__submit" name="save_address" value="<?= esc_attr__( 'Αποθήκευση διεύθυνσης', 'com-theme' ) ?>">
                    <?= esc_html__( 'Αποθήκευση διεύθυνσης', 'com-theme' ) ?>
                </button>
                <?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
                <input type="hidden" name="action" value="edit_address">
            </div>
        </div>
    </form>
<?php endif; ?>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>
