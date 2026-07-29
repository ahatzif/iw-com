<?php
/**
 * Checkout order receipt.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

$payment_method = (string) $order->get_payment_method();
$is_cardlink = in_array(
    $payment_method,
    [
        'cardlink_payment_gateway_woocommerce',
        'cardlink_payment_gateway_woocommerce_iris',
    ],
    true
);

if ( ! $is_cardlink ) :
    $totals = $order->get_order_item_totals();
    ?>
    <ul class="order_details">
        <li class="order">
            <?= esc_html__( 'Order number:', 'woocommerce' ) ?>
            <strong><?= esc_html( $order->get_order_number() ) ?></strong>
        </li>
        <li class="date">
            <?= esc_html__( 'Date:', 'woocommerce' ) ?>
            <strong><?= esc_html( wc_format_datetime( $order->get_date_created() ) ) ?></strong>
        </li>
        <li class="total">
            <?= esc_html__( 'Total:', 'woocommerce' ) ?>
            <strong><?= wp_kses_post( $order->get_formatted_order_total() ) ?></strong>
        </li>
        <?php if ( $order->get_payment_method_title() ) : ?>
            <li class="method">
                <?= esc_html__( 'Payment method:', 'woocommerce' ) ?>
                <strong><?= wp_kses_post( $order->get_payment_method_title() ) ?></strong>
            </li>
        <?php endif; ?>
    </ul>

    <?php do_action( 'woocommerce_receipt_' . $payment_method, $order->get_id() ); ?>
    <div class="clear"></div>
    <?php
    return;
endif;

$steps = [
    1 => [ __( 'ΣΤΟΙΧΕΙΑ', 'com-theme' ), __( 'ΧΡΕΩΣΗΣ', 'com-theme' ) ],
    2 => [ __( 'ΤΡΟΠΟΣ', 'com-theme' ), __( 'ΠΛΗΡΩΜΗΣ', 'com-theme' ) ],
    3 => [ __( 'ΟΛΟΚΛΗΡΩΣΗ', 'com-theme' ), __( 'ΑΓΟΡΑΣ', 'com-theme' ) ],
];
?>

<section class="com-checkout com-checkout-receipt min-h-screen bg-blue pb-120 pt-[16rem] text-ochre">
    <div class="page-wrapper">
        <div class="max-w-[60rem]">
            <p class="m-0 text-[1rem] font-medium tracking-[.18em]"><?= esc_html( com\theme::remove_accents( __( 'Αγορά εισιτηρίου', 'com-theme' ) ) ) ?></p>
            <h1 class="mt-20 text-[4.4rem] font-medium leading-[1.08] md:text-[6rem] md:leading-[7rem]">
                <?= esc_html__( 'Ολοκληρώνουμε', 'com-theme' ) ?><br><?= esc_html__( 'την αγορά σας', 'com-theme' ) ?>
            </h1>
        </div>

        <div class="mt-60 grid gap-60 lg:grid-cols-[minmax(0,60rem)_48rem] lg:items-start lg:justify-between">
            <div class="min-w-0">
                <ol class="com-checkout-stepper flex items-center bg-blue pt-40 lg:sticky lg:top-[12rem] lg:z-10" aria-label="<?= esc_attr__( 'Βήματα checkout', 'com-theme' ) ?>">
                    <?php foreach ( $steps as $number => $labels ) : ?>
                        <?php if ( $number > 1 ) : ?>
                            <span class="mx-10 h-px min-w-10 flex-1 border-t border-dashed border-blue-soft" aria-hidden="true"></span>
                        <?php endif; ?>
                        <li class="contents">
                            <span
                                class="group/checkout-step step-indicator flex shrink-0 items-center gap-10 text-left <?= $number === 3 ? 'active text-white opacity-100' : 'complete text-blue-soft opacity-100' ?>"
                                aria-current="<?= $number === 3 ? 'step' : 'false' ?>"
                            >
                                <span class="relative flex size-40 shrink-0 items-center justify-center text-[1.4rem] transition-colors group-[.active]/checkout-step:text-blue group-[.complete]/checkout-step:text-white">
                                    <span class="absolute inset-0 rounded-full border border-blue-soft transition-all group-[.active]/checkout-step:scale-[1.3] group-[.active]/checkout-step:border-white group-[.active]/checkout-step:bg-white group-[.complete]/checkout-step:border-white" aria-hidden="true"></span>
                                    <span class="relative"><?= esc_html( str_pad( (string) $number, 2, '0', STR_PAD_LEFT ) ) ?></span>
                                </span>
                                <span class="hidden pl-5 text-[1.2rem] leading-[1.15] sm:block lg:text-[1.4rem]">
                                    <?= esc_html( $labels[0] ) ?><br><?= esc_html( $labels[1] ) ?>
                                </span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ol>

                <div
                    class="mt-60 flex min-h-[34rem] flex-col items-center justify-center rounded-[1.5rem] border border-white px-30 py-60 text-center"
                    data-cardlink-redirect-state
                    aria-busy="true"
                >
                    <span class="block size-60 animate-spin rounded-full border-4 border-white/30 border-t-white" data-cardlink-status-spinner aria-hidden="true"></span>
                    <h2 class="mt-30 text-[2.4rem] font-bold" data-cardlink-status-title><?= esc_html__( 'Η παραγγελία σας καταχωρήθηκε', 'com-theme' ) ?></h2>
                    <p class="mt-15 max-w-[46rem] text-[1.6rem] leading-[1.4] text-ochre/80" data-cardlink-status-message>
                        <?= esc_html__( 'Σας μεταφέρουμε στο ασφαλές περιβάλλον πληρωμών της Cardlink για να ολοκληρώσετε την πληρωμή σας.', 'com-theme' ) ?>
                    </p>
                    <p class="mt-20 text-[1.2rem] text-ochre/60" data-cardlink-status-note><?= esc_html__( 'Παρακαλούμε μην κλείσετε ή ανανεώσετε τη σελίδα.', 'com-theme' ) ?></p>
                    <button
                        type="submit"
                        form="payment_form"
                        class="mt-30 hidden min-h-[5.6rem] items-center justify-center rounded-[.8rem] border border-white px-30 text-[1.4rem]"
                        data-cardlink-payment-fallback
                    ><?= esc_html__( 'Συνέχεια στην Cardlink', 'com-theme' ) ?></button>
                </div>
            </div>

            <aside class="min-w-0 lg:sticky lg:top-[14rem]">
                <div class="overflow-hidden rounded-[1.5rem] bg-white p-30 text-blue md:p-40">
                    <h2 class="text-[1.6rem] font-bold text-blue-soft"><?= esc_html__( 'ΣΤΟΙΧΕΙΑ ΠΑΡΑΓΓΕΛΙΑΣ', 'com-theme' ) ?></h2>
                    <dl class="mt-30 space-y-20 text-[1.4rem]">
                        <div class="flex items-start justify-between gap-20">
                            <dt class="text-blue-soft"><?= esc_html__( 'Αριθμός παραγγελίας', 'com-theme' ) ?></dt>
                            <dd class="m-0 font-bold">#<?= esc_html( $order->get_order_number() ) ?></dd>
                        </div>
                        <div class="flex items-start justify-between gap-20">
                            <dt class="text-blue-soft"><?= esc_html__( 'Ημερομηνία', 'com-theme' ) ?></dt>
                            <dd class="m-0 text-right font-bold"><?= esc_html( wc_format_datetime( $order->get_date_created() ) ) ?></dd>
                        </div>
                        <div class="flex items-start justify-between gap-20">
                            <dt class="text-blue-soft"><?= esc_html__( 'Τρόπος πληρωμής', 'com-theme' ) ?></dt>
                            <dd class="m-0 text-right font-bold"><?= wp_kses_post( $order->get_payment_method_title() ) ?></dd>
                        </div>
                    </dl>

                    <div class="relative -mx-30 mt-30 border-t border-dashed border-blue-soft before:absolute before:-left-15 before:-top-15 before:size-30 before:rounded-full before:bg-blue before:content-[''] after:absolute after:-right-15 after:-top-15 after:size-30 after:rounded-full after:bg-blue after:content-[''] md:-mx-40" aria-hidden="true"></div>

                    <div class="mt-30 flex items-center justify-between gap-20">
                        <span class="text-[1.6rem] font-bold text-blue-soft"><?= esc_html__( 'ΣΥΝΟΛΟ', 'com-theme' ) ?></span>
                        <strong class="text-[2.4rem]"><?= wp_kses_post( $order->get_formatted_order_total() ) ?></strong>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <div class="hidden" aria-hidden="true" data-cardlink-payment-form>
        <?php do_action( 'woocommerce_receipt_' . $payment_method, $order->get_id() ); ?>
    </div>
</section>

<script>
var comCheckoutUrl = <?= wp_json_encode( wc_get_checkout_url() ) ?>;

(function () {
    var pendingClass = 'com-cardlink-receipt-pending';
    var storageKey = 'comCheckoutScrollTop';
    var storedScrollTop = null;

    function revealReceipt() {
        document.body.classList.remove(pendingClass);
    }

    try {
        storedScrollTop = window.sessionStorage.getItem(storageKey);
    } catch (error) {
        revealReceipt();
        return;
    }

    if (storedScrollTop === null) {
        revealReceipt();
        return;
    }

    var targetScrollTop = Math.max(0, parseFloat(storedScrollTop) || 0);
    var attempts = 0;

    if ('scrollRestoration' in window.history) {
        window.history.scrollRestoration = 'manual';
    }

    function restoreCheckoutScroll() {
        var scrollContainer = document.querySelector('[data-module-scroll="main"]');

        if (scrollContainer) {
            scrollContainer.scrollTop = targetScrollTop;
        }

        attempts += 1;
        if (attempts < 8) {
            window.requestAnimationFrame(restoreCheckoutScroll);
            return;
        }

        try {
            window.sessionStorage.removeItem(storageKey);
        } catch (error) {
            // The redirect can continue when storage is unavailable.
        }

        revealReceipt();
    }

    restoreCheckoutScroll();
}());

window.addEventListener('pageshow', function (event) {
    var navigation = window.performance && window.performance.getEntriesByType
        ? window.performance.getEntriesByType('navigation')[0]
        : null;

    if (event.persisted || (navigation && navigation.type === 'back_forward')) {
        window.location.replace(comCheckoutUrl);
    }
});

if (!document.getElementById('payment_form')) {
    window.location.replace(comCheckoutUrl);
}

window.setTimeout(function () {
    var fallback = document.querySelector('[data-cardlink-payment-fallback]');
    var paymentForm = document.getElementById('payment_form');

    if (paymentForm && fallback) {
        fallback.classList.remove('hidden');
        fallback.classList.add('inline-flex');
    }
}, 5000);
</script>
