<?php
/**
 * Tax number verification field.
 *
 * @var array $args
 */

defined( 'ABSPATH' ) || exit;

$args = wp_parse_args(
    $args ?? [],
    [
        'context'      => 'checkout',
        'field_html'   => '',
        'verified'     => false,
        'company_name' => '',
        'company_info' => '',
        'label'        => '',
        'field_id'     => 'billing_tax_number',
    ]
);

$context = in_array( $args['context'], [ 'checkout', 'account' ], true ) ? $args['context'] : 'checkout';
$classes = [
    'com-tax-number-lookup',
    'com-tax-number-lookup--' . $context,
    $context === 'checkout' ? 'com-checkout-field--wide' : 'account-address-field account-address-field--wide',
];

if ( $args['verified'] ) {
    $classes[] = 'is-verified';
}
?>
<div
    class="<?= esc_attr( implode( ' ', $classes ) ) ?>"
    data-module-tax-number-lookup
    data-invoice-field="billing_tax_number"
    data-tax-number-lookup-aade-description="<?= esc_attr__( 'Το ΑΦΜ επαληθεύεται από την ΑΑΔΕ και τα εταιρικά στοιχεία συμπληρώνονται αυτόματα.', 'com-theme' ) ?>"
    data-tax-number-lookup-vies-description="<?= esc_attr__( 'Το VAT number επαληθεύεται από το VIES και τα διαθέσιμα εταιρικά στοιχεία συμπληρώνονται αυτόματα.', 'com-theme' ) ?>"
    data-tax-number-lookup-manual-description="<?= esc_attr__( 'Για τη συγκεκριμένη χώρα τα στοιχεία τιμολογίου συμπληρώνονται χειροκίνητα.', 'com-theme' ) ?>"
    data-tax-number-lookup-aade-label="<?= esc_attr__( 'Επαλήθευση ΑΦΜ μέσω ΑΑΔΕ', 'com-theme' ) ?>"
    data-tax-number-lookup-vies-label="<?= esc_attr__( 'Επαλήθευση VAT μέσω VIES', 'com-theme' ) ?>"
>
    <?php if ( $context === 'account' && $args['label'] !== '' ) : ?>
        <label class="account-address-field__label" for="<?= esc_attr( $args['field_id'] ) ?>">
            <?= esc_html( $args['label'] ) ?>
            <span class="required" aria-hidden="true">*</span>
        </label>
    <?php endif; ?>

    <div class="com-tax-number-lookup__control">
        <?php
        // Generated and escaped by WooCommerce.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $args['field_html'];
        ?>
        <button
            type="button"
            class="com-tax-number-lookup__search"
            data-tax-number-lookup="search"
            aria-label="<?= esc_attr__( 'Επαλήθευση ΑΦΜ', 'com-theme' ) ?>"
        >
            <span aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false">
                    <path d="M5 12h14M13 6l6 6-6 6"></path>
                </svg>
            </span>
        </button>
    </div>

    <input
        type="hidden"
        name="search_tax_number_nonce"
        value="<?= esc_attr( wp_create_nonce( 'search_tax_number_nonce' ) ) ?>"
        data-tax-number-lookup="nonce"
    >

    <p class="com-tax-number-lookup__description" data-tax-number-lookup="description"></p>
    <p class="com-tax-number-lookup__error" data-tax-number-lookup="error" role="alert" hidden></p>

    <div class="com-tax-number-lookup__preview" data-tax-number-lookup="preview" aria-live="polite">
        <div>
            <strong data-tax-number-lookup="company-name"><?= esc_html( $args['company_name'] ) ?></strong>
            <span data-tax-number-lookup="company-info"><?= wp_kses_post( $args['company_info'] ) ?></span>
        </div>
        <button
            type="button"
            class="com-tax-number-lookup__clear"
            data-tax-number-lookup="clear"
            aria-label="<?= esc_attr__( 'Καθαρισμός επαληθευμένων στοιχείων', 'com-theme' ) ?>"
        >×</button>
    </div>
</div>
