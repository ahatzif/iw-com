<?php
defined( 'ABSPATH' ) || exit;

remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
    echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'Πρέπει να συνδεθείτε για να ολοκληρώσετε την αγορά.', 'com-theme' ) ) );
    return;
}

$billing_fields = $checkout->get_checkout_fields( 'billing' );
$field_order = [
    'billing_first_name',
    'billing_last_name',
    'billing_address_1',
    'billing_postcode',
    'billing_city',
    'billing_state',
    'billing_country',
    'billing_phone',
    'billing_email',
];
$invoice_fields = [
    'billing_tax_number',
    'billing_company_name',
    'billing_company_profession',
    'billing_tax_office',
];
$receipt_type = $checkout->get_value( 'billing_receipt_type' ) ?: 'receipt';
$field_labels = [
    'billing_first_name'         => __( 'Όνομα', 'com-theme' ),
    'billing_last_name'          => __( 'Επώνυμο', 'com-theme' ),
    'billing_address_1'          => __( 'Διεύθυνση', 'com-theme' ),
    'billing_postcode'           => __( 'Ταχυδρομικός κώδικας', 'com-theme' ),
    'billing_city'               => __( 'Πόλη', 'com-theme' ),
    'billing_state'              => __( 'Περιφέρεια', 'com-theme' ),
    'billing_country'            => __( 'Χώρα / Περιοχή', 'com-theme' ),
    'billing_phone'              => __( 'Τηλέφωνο', 'com-theme' ),
    'billing_email'              => __( 'Email', 'com-theme' ),
    'billing_tax_number'         => __( 'Α.Φ.Μ.', 'com-theme' ),
    'billing_company_name'       => __( 'Επωνυμία επιχείρησης', 'com-theme' ),
    'billing_company_profession' => __( 'Δραστηριότητα επιχείρησης', 'com-theme' ),
    'billing_tax_office'         => __( 'Δ.Ο.Υ.', 'com-theme' ),
];

$prepare_field = static function ( array $field, string $key, bool $wide = false ) use ( $field_labels ): array {
    $field['class'] = array_values( array_unique( array_merge( (array) ( $field['class'] ?? [] ), [ 'com-checkout-field' ], $wide ? [ 'com-checkout-field--wide' ] : [] ) ) );
    $field['input_class'] = array_values( array_unique( array_merge( (array) ( $field['input_class'] ?? [] ), [ 'com-checkout-field__control' ] ) ) );
    $field['label_class'] = array_values( array_unique( array_merge( (array) ( $field['label_class'] ?? [] ), [ 'com-checkout-field__label' ] ) ) );
    $field['label'] = $field_labels[ $key ] ?? ( $field['label'] ?? '' );
    $field['placeholder'] = $key === 'billing_state'
        ? __( 'Επιλέξτε περιφέρεια', 'com-theme' )
        : '';
    $field['autocomplete'] = $field['autocomplete'] ?? $key;
    if ( ! empty( $field['required'] ) ) {
        $field['custom_attributes'] = array_merge(
            (array) ( $field['custom_attributes'] ?? [] ),
            [ 'required' => 'required' ]
        );
    }

    return $field;
};
?>
<section class="com-checkout min-h-screen bg-blue pb-120 pt-[16rem] text-ochre">
    <div class="page-wrapper">
        <div class="max-w-[60rem]">
            <p class="m-0 text-[1rem] font-medium uppercase tracking-[.18em]"><?= esc_html__( 'Αγορά εισιτηρίου', 'com-theme' ) ?></p>
            <h1 class="mt-20 text-[4.4rem] font-medium leading-[1.08] md:text-[6rem] md:leading-[7rem]">
                <?= esc_html__( 'Ολοκληρώστε', 'com-theme' ) ?><br><?= esc_html__( 'την αγορά σας', 'com-theme' ) ?>
            </h1>
        </div>

        <form
            name="checkout"
            method="post"
            class="checkout woocommerce-checkout mt-60 grid gap-60 lg:grid-cols-[minmax(0,60rem)_48rem] lg:items-start lg:justify-between"
            action="<?= esc_url( wc_get_checkout_url() ) ?>"
            enctype="multipart/form-data"
            aria-label="<?= esc_attr__( 'Ολοκλήρωση αγοράς', 'com-theme' ) ?>"
            data-module-checkout-steps
        >
            <div class="min-w-0">
                <ol class="com-checkout-stepper grid grid-cols-[auto_1fr_auto_1fr_auto] items-center gap-10" aria-label="<?= esc_attr__( 'Βήματα checkout', 'com-theme' ) ?>">
                    <?php
                    $steps = [
                        1 => __( 'ΣΤΟΙΧΕΙΑ ΧΡΕΩΣΗΣ', 'com-theme' ),
                        2 => __( 'ΤΡΟΠΟΣ ΠΛΗΡΩΜΗΣ', 'com-theme' ),
                        3 => __( 'ΟΛΟΚΛΗΡΩΣΗ', 'com-theme' ),
                    ];
                    foreach ( $steps as $number => $label ) :
                        if ( $number > 1 ) :
                    ?>
                        <span class="h-px bg-blue-soft" aria-hidden="true"></span>
                    <?php endif; ?>
                        <li
                            class="group step-indicator flex items-center gap-10 <?= $number === 1 ? 'active' : '' ?>"
                            data-checkout-steps="indicator"
                            data-step="<?= esc_attr( (string) $number ) ?>"
                        >
                            <span class="flex size-40 shrink-0 items-center justify-center rounded-full border border-blue-soft text-[1.4rem] text-blue-soft transition-all group-[.step-indicator.active]:size-60 group-[.step-indicator.active]:border-white group-[.step-indicator.active]:bg-white group-[.step-indicator.active]:text-blue">
                                <?= esc_html( str_pad( (string) $number, 2, '0', STR_PAD_LEFT ) ) ?>
                            </span>
                            <span class="hidden max-w-[8rem] text-[1.1rem] leading-[1.15] text-blue-soft group-[.step-indicator.active]:text-white sm:block"><?= esc_html( $label ) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>

                <div class="mt-60">
                    <?php wc_print_notices(); ?>
                    <div class="com-checkout-fields" data-checkout-steps="step" data-step="1">
                        <p class="m-0 max-w-[56rem] text-[1.8rem] leading-[1.35]"><?= esc_html__( 'Συμπληρώστε τα στοιχεία που θα χρησιμοποιηθούν για την έκδοση του παραστατικού σας.', 'com-theme' ) ?></p>

                        <fieldset class="mt-40 border-0 p-0">
                            <legend class="sr-only"><?= esc_html__( 'Είδος παραστατικού', 'com-theme' ) ?></legend>
                            <div class="flex flex-wrap gap-40 text-[1.8rem]">
                                <?php foreach ( [ 'receipt' => __( 'Απόδειξη', 'com-theme' ), 'invoice' => __( 'Τιμολόγιο', 'com-theme' ) ] as $value => $label ) : ?>
                                    <label class="com-checkout-radio">
                                        <input
                                            type="radio"
                                            name="billing_receipt_type"
                                            value="<?= esc_attr( $value ) ?>"
                                            <?= checked( $receipt_type, $value, false ) ?>
                                            data-checkout-steps="receipt-type"
                                        >
                                        <span aria-hidden="true"></span>
                                        <?= esc_html( $label ) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>

                        <div class="com-checkout-fields__grid mt-40">
                            <?php foreach ( $field_order as $key ) :
                                if ( empty( $billing_fields[ $key ] ) ) {
                                    continue;
                                }
                                $wide = in_array( $key, [ 'billing_address_1' ], true );
                                woocommerce_form_field( $key, $prepare_field( $billing_fields[ $key ], $key, $wide ), $checkout->get_value( $key ) );
                            endforeach; ?>
                        </div>

                        <div
                            class="com-checkout-invoice-fields mt-20 <?= $receipt_type === 'invoice' ? '' : 'hidden' ?>"
                            data-checkout-steps="invoice-fields"
                        >
                            <div class="com-checkout-fields__grid">
                                <?php foreach ( $invoice_fields as $key ) :
                                    if ( empty( $billing_fields[ $key ] ) ) {
                                        continue;
                                    }
                                    woocommerce_form_field( $key, $prepare_field( $billing_fields[ $key ], $key ), $checkout->get_value( $key ) );
                                endforeach; ?>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="mt-40 inline-flex min-h-[5.6rem] w-full cursor-pointer items-center justify-center rounded-[1rem] bg-white px-30 text-[1.6rem] text-blue transition-colors hover:bg-ochre"
                            data-checkout-steps="next"
                        ><?= esc_html__( 'Επόμενο', 'com-theme' ) ?></button>
                    </div>

                    <div class="hidden" data-checkout-steps="step" data-step="2">
                        <div class="flex items-center justify-between gap-20">
                            <h2 class="text-[2.4rem] font-bold"><?= esc_html__( 'Τρόπος πληρωμής', 'com-theme' ) ?></h2>
                            <button type="button" class="cursor-pointer text-[1.3rem] underline underline-offset-4" data-checkout-steps="back"><?= esc_html__( 'Πίσω', 'com-theme' ) ?></button>
                        </div>
                        <p class="mt-15 text-[1.6rem] leading-[1.4] text-ochre/80"><?= esc_html__( 'Επιλέξτε τον τρόπο πληρωμής και ολοκληρώστε με ασφάλεια την παραγγελία σας.', 'com-theme' ) ?></p>

                        <div class="mt-30">
                            <?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
                            <?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
                            <?php woocommerce_checkout_payment(); ?>
                            <?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="order-first min-w-0 lg:order-last lg:sticky lg:top-[14rem]">
                <?php woocommerce_order_review(); ?>
            </aside>
        </form>
    </div>
</section>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
