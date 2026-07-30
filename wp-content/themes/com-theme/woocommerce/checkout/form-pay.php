<?php
/**
 * Pay for an existing order using the same stepped UI as the main checkout.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

$hold_state = class_exists( 'CPT_As_Product' ) && method_exists( 'CPT_As_Product', 'get_order_ticket_hold_state' )
    ? CPT_As_Product::get_order_ticket_hold_state( $order )
    : [ 'has_ticket_items' => false, 'is_active' => true, 'expires_at' => '' ];
$is_expired = ! empty( $hold_state['has_ticket_items'] ) && empty( $hold_state['is_active'] );
$ticket_count = 0;
$order_items = [];

foreach ( $order->get_items() as $item_id => $item ) {
    if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
        continue;
    }

    $product = is_callable( [ $item, 'get_product' ] ) ? $item->get_product() : null;
    $content_id = absint( $item->get_meta( 'tickets_for_id', true ) );
    $is_all_museums_ticket = $content_id ? com_theme_is_all_museums_ticket( $content_id ) : false;
    $title = $content_id ? get_the_title( $content_id ) : $item->get_name();
    $image_id = $content_id ? com_theme_museum_image_id( $content_id ) : ( $product ? absint( $product->get_image_id() ) : 0 );
    $location = $content_id ? com_theme_museum_location_label( $content_id ) : '';
    $date_raw = trim( (string) $item->get_meta( 'tickets_day', true ) );
    $date_ts = $date_raw !== '' ? strtotime( $date_raw ) : false;
    $date = $date_ts ? date_i18n( 'd/m/Y', $date_ts ) : $date_raw;
    $time = str_replace( '-', ':', trim( (string) $item->get_meta( 'tickets_time', true ) ) );
    $item_ticket_count = max( 1, (int) $item->get_meta( 'tickets_total', true ) );
    $ticket_count += $item_ticket_count;

    $visitor_labels = [];
    $visitors = json_decode( (string) $item->get_meta( 'tickets_visitors', true ), true );
    foreach ( is_array( $visitors ) ? $visitors : [] as $visitor ) {
        if ( ! is_array( $visitor ) ) {
            continue;
        }
        $label = trim( (string) ( $visitor['category-name'] ?? $visitor['category_name'] ?? '' ) );
        if ( $label !== '' ) {
            $visitor_labels[ $label ] = ( $visitor_labels[ $label ] ?? 0 ) + 1;
        }
    }

    $order_items[] = [
        'item'                  => $item,
        'title'                 => $title,
        'is_all_museums_ticket' => $is_all_museums_ticket,
        'image_id'              => $image_id,
        'location'              => $location,
        'date'                  => $date,
        'time'                  => $time,
        'ticket_count'          => $item_ticket_count,
        'visitor_labels'        => $visitor_labels,
    ];
}

$steps = [
    1 => [ __( 'ΣΤΟΙΧΕΙΑ', 'com-theme' ), __( 'ΧΡΕΩΣΗΣ', 'com-theme' ) ],
    2 => [ __( 'ΤΡΟΠΟΣ', 'com-theme' ), __( 'ΠΛΗΡΩΜΗΣ', 'com-theme' ) ],
    3 => [ __( 'ΟΛΟΚΛΗΡΩΣΗ', 'com-theme' ), __( 'ΑΓΟΡΑΣ', 'com-theme' ) ],
];

$checkout = WC()->checkout();
$billing_fields = $checkout->get_checkout_fields( 'billing' );
$field_order = [
    'billing_first_name',
    'billing_last_name',
    'billing_country',
    'billing_state',
    'billing_address_1',
    'billing_postcode',
    'billing_city',
    'billing_phone',
    'billing_email',
];
$invoice_fields = [
    'billing_tax_number',
    'billing_company_name',
    'billing_company_profession',
    'billing_tax_office',
];
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
    $field['label'] = com\theme::remove_accents( $field_labels[ $key ] ?? ( $field['label'] ?? '' ) );
    $field['placeholder'] = $key === 'billing_state' ? __( 'Επιλέξτε περιφέρεια', 'com-theme' ) : '';
    $field['autocomplete'] = $field['autocomplete'] ?? $key;
    if ( ! empty( $field['required'] ) ) {
        $field['custom_attributes'] = array_merge( (array) ( $field['custom_attributes'] ?? [] ), [ 'required' => 'required' ] );
    }
    if ( $key === 'billing_country' ) {
        $field['custom_attributes'] = array_merge( (array) ( $field['custom_attributes'] ?? [] ), [ 'data-checkout-steps' => 'country' ] );
    }

    return $field;
};
$get_billing_value = static function ( string $key ) use ( $order ): string {
    if ( isset( $_POST[ $key ] ) ) {
        return (string) wc_clean( wp_unslash( $_POST[ $key ] ) );
    }

    if ( in_array( $key, [
        'billing_receipt_type',
        'billing_tax_number',
        'billing_company_name',
        'billing_company_profession',
        'billing_tax_office',
    ], true ) ) {
        return (string) $order->get_meta( '_' . $key, true );
    }

    $getter = 'get_' . $key;
    return is_callable( [ $order, $getter ] ) ? (string) $order->{$getter}() : '';
};
$receipt_type = $get_billing_value( 'billing_receipt_type' ) ?: 'receipt';
$billing_country = $get_billing_value( 'billing_country' ) ?: 'GR';
$billing_tax_number = $get_billing_value( 'billing_tax_number' );
$verified_company = [];
if ( class_exists( 'IW_WC_Customizations' ) ) {
    $verified_company = strtoupper( $billing_country ) === 'GR'
        ? IW_WC_Customizations::get_saved_aade_company_info( $billing_tax_number )
        : IW_WC_Customizations::get_saved_vies_company_info( $billing_country, $billing_tax_number );
}
$verified_company_preview = class_exists( 'IW_WC_Customizations' )
    ? IW_WC_Customizations::get_company_preview_args( $verified_company )
    : [];

$hold_expires_timestamp = 0;
if ( ! empty( $hold_state['expires_at'] ) ) {
    try {
        $hold_expires_timestamp = ( new DateTimeImmutable( (string) $hold_state['expires_at'], wp_timezone() ) )->getTimestamp();
    } catch ( Exception $exception ) {
        $hold_expires_timestamp = 0;
    }
}
?>

<section class="com-checkout com-order-pay min-h-screen bg-blue pb-120 pt-[16rem] text-ochre">
    <div class="page-wrapper">
        <div class="max-w-[60rem]">
            <p class="m-0 text-[1rem] font-medium tracking-[.18em]"><?= esc_html( com\theme::remove_accents( __( 'Αγορά εισιτηρίου', 'com-theme' ) ) ) ?></p>
            <h1 class="mt-20 text-[4.4rem] font-medium leading-[1.08] md:text-[6rem] md:leading-[7rem]">
                <?php if ( $is_expired ) : ?>
                    <?= esc_html__( 'Η κράτησή σας έληξε', 'com-theme' ) ?>
                <?php else : ?>
                    <?= esc_html__( 'Ολοκληρώστε', 'com-theme' ) ?><br><?= esc_html__( 'την αγορά σας', 'com-theme' ) ?>
                <?php endif; ?>
            </h1>
        </div>

        <?php if ( $is_expired ) : ?>
            <div class="mt-60 max-w-[86rem] overflow-hidden rounded-[1.5rem] bg-white text-blue">
                <div class="grid gap-30 p-30 md:grid-cols-[14rem_1fr] md:p-40">
                    <div class="flex aspect-square items-center justify-center rounded-[1rem] bg-ochre-light">
                        <svg class="h-60 w-70 fill-blue" aria-hidden="true"><use xlink:href="#icon-com-ticket"></use></svg>
                    </div>
                    <div class="self-center">
                        <p class="text-[1rem] tracking-[.15em] text-blue-soft"><?= esc_html( com\theme::remove_accents( __( 'Αριθμός αγοράς', 'com-theme' ) ) ) ?></p>
                        <h2 class="mt-10 text-[3rem] font-bold">#<?= esc_html( $order->get_order_number() ) ?></h2>
                        <p class="mt-15 text-[1.5rem] leading-[1.45] text-blue/75"><?= esc_html__( 'Τα εισιτήρια αποδεσμεύτηκαν. Για νέα διαθεσιμότητα χρειάζεται να επιλέξετε ξανά ημερομηνία και ώρα.', 'com-theme' ) ?></p>
                        <div class="mt-25 flex flex-wrap gap-10">
                            <a href="<?= esc_url( com_theme_page_url( 'buy-tickets' ) ) ?>" class="inline-flex min-h-50 items-center justify-center rounded-[.8rem] bg-blue px-25 text-[1.3rem] font-bold text-white"><?= esc_html__( 'Νέα αγορά εισιτηρίων', 'com-theme' ) ?> →</a>
                            <a href="<?= esc_url( wc_get_account_endpoint_url( 'orders' ) ) ?>" class="inline-flex min-h-50 items-center justify-center rounded-[.8rem] border border-blue px-25 text-[1.3rem] font-bold"><?= esc_html__( 'Οι αγορές μου', 'com-theme' ) ?></a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <form
                id="order_review"
                method="post"
                class="woocommerce-checkout mt-60 grid gap-60 lg:grid-cols-[minmax(0,60rem)_48rem] lg:items-start lg:justify-between"
                aria-label="<?= esc_attr__( 'Ολοκλήρωση αγοράς', 'com-theme' ) ?>"
                data-module-checkout-steps
            >
                <div class="min-w-0">
                    <div data-checkout-steps="steps-anchor" aria-hidden="true"></div>
                    <ol class="com-checkout-stepper flex items-center bg-blue pt-40 lg:sticky lg:top-[12rem] lg:z-10" aria-label="<?= esc_attr__( 'Βήματα checkout', 'com-theme' ) ?>">
                        <?php foreach ( $steps as $number => $labels ) : ?>
                            <?php if ( $number > 1 ) : ?>
                                <span class="mx-10 h-px min-w-10 flex-1 border-t border-dashed border-blue-soft" aria-hidden="true"></span>
                            <?php endif; ?>
                            <li class="contents">
                                <button
                                    type="button"
                                    class="group/checkout-step step-indicator flex shrink-0 items-center gap-10 text-left text-blue-soft opacity-60 transition-opacity disabled:cursor-not-allowed [&.active]:text-white [&.active]:opacity-100 [&.complete]:opacity-100 <?= $number === 1 ? 'active' : '' ?>"
                                    data-checkout-steps="indicator"
                                    data-step="<?= esc_attr( (string) $number ) ?>"
                                    <?= $number === 3 ? 'disabled' : '' ?>
                                    aria-current="<?= $number === 1 ? 'step' : 'false' ?>"
                                >
                                    <span class="relative flex size-40 shrink-0 items-center justify-center text-[1.4rem] transition-colors group-[.active]/checkout-step:text-blue group-[.complete]/checkout-step:text-white">
                                        <span class="absolute inset-0 rounded-full border border-blue-soft transition-all group-[.active]/checkout-step:scale-[1.3] group-[.active]/checkout-step:border-white group-[.active]/checkout-step:bg-white group-[.complete]/checkout-step:border-white" aria-hidden="true"></span>
                                        <span class="relative"><?= esc_html( str_pad( (string) $number, 2, '0', STR_PAD_LEFT ) ) ?></span>
                                    </span>
                                    <span class="hidden pl-5 text-[1.2rem] leading-[1.15] sm:block lg:text-[1.4rem]"><?= esc_html( $labels[0] ) ?><br><?= esc_html( $labels[1] ) ?></span>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ol>

                    <div class="mt-60">
                        <?php wc_print_notices(); ?>

                        <div class="com-checkout-fields" data-checkout-steps="step" data-step="1">
                            <p class="m-0 max-w-[56rem] text-[1.8rem] leading-[1.35]"><?= esc_html__( 'Ελέγξτε ή ενημερώστε τα στοιχεία που θα χρησιμοποιηθούν για την έκδοση του παραστατικού σας.', 'com-theme' ) ?></p>

                            <fieldset class="mt-40 border-0 p-0">
                                <legend class="sr-only"><?= esc_html__( 'Είδος παραστατικού', 'com-theme' ) ?></legend>
                                <div class="flex flex-wrap gap-40 text-[1.8rem]">
                                    <?php foreach ( [ 'receipt' => __( 'Απόδειξη', 'com-theme' ), 'invoice' => __( 'Τιμολόγιο', 'com-theme' ) ] as $value => $label ) : ?>
                                        <label class="com-checkout-radio">
                                            <input type="radio" name="billing_receipt_type" value="<?= esc_attr( $value ) ?>" <?= checked( $receipt_type, $value, false ) ?> data-checkout-steps="receipt-type">
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
                                    woocommerce_form_field(
                                        $key,
                                        $prepare_field( $billing_fields[ $key ], $key, $key === 'billing_address_1' ),
                                        $get_billing_value( $key )
                                    );
                                endforeach; ?>
                            </div>

                            <div class="com-checkout-invoice-fields mt-20 <?= $receipt_type === 'invoice' ? '' : 'hidden' ?>" data-checkout-steps="invoice-fields">
                                <div class="com-checkout-fields__grid">
                                    <?php foreach ( $invoice_fields as $key ) :
                                        if ( empty( $billing_fields[ $key ] ) ) {
                                            continue;
                                        }
                                        $invoice_field = $prepare_field( $billing_fields[ $key ], $key );
                                        $invoice_field['return'] = true;
                                        $field_html = woocommerce_form_field( $key, $invoice_field, $get_billing_value( $key ) );
                                        if ( $key === 'billing_tax_number' ) {
                                            get_template_part( 'templates/parts/woocommerce/tax-number-lookup', null, [
                                                'context'      => 'checkout',
                                                'field_html'   => $field_html,
                                                'verified'     => ! empty( $verified_company ),
                                                'company_name' => $verified_company_preview['company_name'] ?? '',
                                                'company_info' => $verified_company_preview['company_info'] ?? '',
                                            ] );
                                            continue;
                                        }
                                        ?>
                                        <div class="com-checkout-invoice-field" data-invoice-field="<?= esc_attr( $key ) ?>">
                                            <?php
                                            // Generated and escaped by WooCommerce.
                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            echo $field_html;
                                            ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <button type="button" class="com-checkout-button mt-40" data-checkout-steps="next"><?= esc_html__( 'Επόμενο', 'com-theme' ) ?></button>
                        </div>

                        <div class="hidden" data-checkout-steps="step" data-step="2">
                            <h2 class="text-[2.4rem] font-bold"><?= esc_html__( 'Τρόπος πληρωμής', 'com-theme' ) ?></h2>
                            <p class="mt-15 text-[1.6rem] leading-[1.4] text-ochre/80"><?= esc_html__( 'Επιλέξτε τον τρόπο πληρωμής και ολοκληρώστε με ασφάλεια την παραγγελία σας.', 'com-theme' ) ?></p>

                            <?php do_action( 'woocommerce_pay_order_before_payment' ); ?>
                            <div id="payment" class="mt-30">
                                <ul class="wc_payment_methods payment_methods methods">
                                    <?php if ( ! empty( $available_gateways ) ) : ?>
                                        <?php foreach ( $available_gateways as $gateway ) : ?>
                                            <?php wc_get_template( 'checkout/payment-method.php', [ 'gateway' => $gateway ] ); ?>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <li><?php wc_print_notice( esc_html__( 'Δεν υπάρχει διαθέσιμος τρόπος πληρωμής.', 'com-theme' ), 'notice' ); ?></li>
                                    <?php endif; ?>
                                </ul>

                                <div class="form-row place-order">
                                    <input type="hidden" name="woocommerce_pay" value="1">
                                    <input type="hidden" name="iw_order_pay_billing" value="1">
                                    <?php wc_get_template( 'checkout/terms.php' ); ?>
                                    <?php do_action( 'woocommerce_pay_order_before_submit' ); ?>
                                    <?php $pay_button_text = __( 'Συνέχεια στην ασφαλή πληρωμή', 'com-theme' ); ?>
                                    <?= apply_filters(
                                        'woocommerce_pay_order_button_html',
                                        '<button type="submit" class="com-checkout-button w-full" id="place_order" value="' . esc_attr( $pay_button_text ) . '" data-value="' . esc_attr( $pay_button_text ) . '">' . esc_html( $pay_button_text ) . '</button>'
                                    ) ?>
                                    <?php do_action( 'woocommerce_pay_order_after_submit' ); ?>
                                    <?php wp_nonce_field( 'woocommerce-pay', 'woocommerce-pay-nonce' ); ?>
                                </div>
                            </div>
                        </div>

                        <div class="hidden" data-checkout-steps="step" data-step="3">
                            <div class="flex min-h-[34rem] flex-col items-center justify-center rounded-[1.5rem] border border-white px-30 py-60 text-center" aria-live="polite">
                                <span class="block size-60 animate-spin rounded-full border-4 border-white/30 border-t-white" aria-hidden="true"></span>
                                <h2 class="mt-30 text-[2.4rem] font-bold"><?= esc_html__( 'Προετοιμάζουμε την πληρωμή', 'com-theme' ) ?></h2>
                                <p class="mt-15 max-w-[46rem] text-[1.6rem] leading-[1.4] text-ochre/80"><?= esc_html__( 'Σας μεταφέρουμε στο ασφαλές περιβάλλον πληρωμών της Cardlink.', 'com-theme' ) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="min-w-0 lg:sticky lg:top-[14rem]">
                    <div class="overflow-hidden rounded-[1.5rem] bg-white text-blue">
                        <div class="space-y-25 p-30 md:p-40">
                            <?php foreach ( $order_items as $order_item_index => $details ) : ?>
                                <article class="grid grid-cols-[8rem_1fr] gap-15">
                                    <div class="relative h-[6.5rem] w-[8rem] overflow-hidden rounded-[.8rem] bg-ochre-light">
                                        <?php if ( $details['is_all_museums_ticket'] ) : ?>
                                            <?php get_template_part( 'templates/parts/all-museums-art', null, [
                                                'label_classes' => 'absolute left-[1rem] top-[1.2rem] text-[1.8rem] font-light leading-[.895] text-ochre-light',
                                            ] ); ?>
                                        <?php elseif ( $details['image_id'] ) : ?>
                                            <?php get_template_part( 'templates/parts/image', null, [
                                                'id'       => $details['image_id'],
                                                'size'     => 'thumbnail',
                                                'classes'  => '!h-full !w-full object-cover',
                                                'lazy'     => false,
                                                'parallax' => false,
                                                'attrs'    => 'loading="eager" fetchpriority="high"',
                                            ] ); ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="min-w-0">
                                        <?php if ( $details['location'] ) : ?>
                                            <p class="text-[1rem] font-normal leading-none tracking-[.3em]"><?= esc_html( com\theme::remove_accents( $details['location'] ) ) ?></p>
                                        <?php endif; ?>
                                        <h2 class="mt-5 line-clamp-2 text-[1.4rem] font-bold leading-none"><?= esc_html( $details['title'] ) ?></h2>
                                        <div class="mt-8 flex flex-wrap gap-x-10 gap-y-5 text-[1.1rem] font-normal">
                                            <?php if ( $details['date'] ) : ?><span><?= esc_html( $details['date'] ) ?><?= $details['time'] ? ' ' . esc_html( $details['time'] ) : '' ?></span><?php endif; ?>
                                            <span><?= esc_html( com\theme::remove_accents( sprintf( _n( '%d εισιτήριο', '%d εισιτήρια', $details['ticket_count'], 'com-theme' ), $details['ticket_count'] ) ) ) ?></span>
                                        </div>
                                        <?php if ( $details['visitor_labels'] ) : ?>
                                            <p class="mt-7 text-[1.05rem] text-blue/60">
                                                <?php
                                                $labels = [];
                                                foreach ( $details['visitor_labels'] as $label => $count ) {
                                                    $labels[] = $count . '× ' . $label;
                                                }
                                                echo esc_html( implode( ' · ', $labels ) );
                                                ?>
                                            </p>
                                        <?php endif; ?>

                                        <?php if ( $order_item_index === 0 && $hold_expires_timestamp ) : ?>
                                            <div
                                                class="mt-7 flex items-center gap-5 text-[1.1rem] leading-none"
                                                data-module-timer
                                                data-start="<?= esc_attr( $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '' ) ?>"
                                                data-end="<?= esc_attr( (string) $hold_state['expires_at'] ) ?>"
                                                data-start-timestamp="<?= esc_attr( $order->get_date_created() ? (string) $order->get_date_created()->getTimestamp() : '' ) ?>"
                                                data-expires-timestamp="<?= esc_attr( (string) $hold_expires_timestamp ) ?>"
                                                data-day-unit="<?= esc_attr__( 'η', 'com-theme' ) ?>"
                                                data-hour-unit="<?= esc_attr__( 'ω', 'com-theme' ) ?>"
                                                data-minute-unit="<?= esc_attr__( 'λ', 'com-theme' ) ?>"
                                                data-expiry-redirect="<?= esc_url( wc_get_account_endpoint_url( 'orders' ) ) ?>"
                                            >
                                                <span><?= esc_html( com\theme::remove_accents( __( 'Χρόνος κράτησης:', 'com-theme' ) ) ) ?></span>
                                                <span class="font-bold" data-timer="display"></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="relative border-t border-dashed border-blue-soft px-30 py-30 before:absolute before:-left-15 before:-top-15 before:size-30 before:rounded-full before:bg-blue before:content-[''] after:absolute after:-right-15 after:-top-15 after:size-30 after:rounded-full after:bg-blue after:content-[''] md:px-40">
                            <div class="flex items-start justify-between gap-20">
                                <div>
                                    <div class="text-[1.6rem] font-bold text-blue-soft"><?= esc_html__( 'ΣΥΝΟΛΟ', 'com-theme' ) ?></div>
                                    <div class="mt-5 text-[1.2rem]"><?= esc_html( sprintf( _n( '%d εισιτήριο', '%d εισιτήρια', $ticket_count, 'com-theme' ), $ticket_count ) ) ?></div>
                                </div>
                                <strong class="text-[2.4rem]"><?= wp_kses_post( $order->get_formatted_order_total() ) ?></strong>
                            </div>
                        </div>
                    </div>
                </aside>
            </form>
        <?php endif; ?>
    </div>
</section>
