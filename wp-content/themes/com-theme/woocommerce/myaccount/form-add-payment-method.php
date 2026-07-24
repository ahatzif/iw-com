<?php
/**
 * Add payment method.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

get_template_part( 'woocommerce/myaccount/page-title', null, [
    'eyebrow'     => __( 'Πληρωμές', 'com-theme' ),
    'title'       => __( 'Προσθήκη μεθόδου', 'com-theme' ),
    'description' => __( 'Προσθέστε με ασφάλεια μια νέα μέθοδο πληρωμής στον λογαριασμό σας.', 'com-theme' ),
] );
?>

<a href="<?= esc_url( wc_get_account_endpoint_url( 'payment-methods' ) ) ?>" data-barba-prevent data-account-pages="link" class="mb-30 inline-flex text-[1.3rem] underline underline-offset-4">← <?= esc_html__( 'Πίσω στις μεθόδους πληρωμής', 'com-theme' ) ?></a>

<?php if ( $available_gateways ) : ?>
    <form id="add_payment_method" method="post" class="account-form">
        <div id="payment" class="woocommerce-Payment">
            <ul class="woocommerce-PaymentMethods payment_methods methods">
                <?php
                current( $available_gateways )->set_current();
                foreach ( $available_gateways as $gateway ) :
                ?>
                    <li class="woocommerce-PaymentMethod payment_method_<?= esc_attr( $gateway->id ) ?>">
                        <input id="payment_method_<?= esc_attr( $gateway->id ) ?>" type="radio" class="input-radio" name="payment_method" value="<?= esc_attr( $gateway->id ) ?>" <?php checked( $gateway->chosen, true ); ?>>
                        <label for="payment_method_<?= esc_attr( $gateway->id ) ?>"><?= wp_kses_post( $gateway->get_title() ) ?> <?= wp_kses_post( $gateway->get_icon() ) ?></label>
                        <?php if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
                            <div class="woocommerce-PaymentBox payment_box payment_method_<?= esc_attr( $gateway->id ) ?>" style="display:none;">
                                <?php $gateway->payment_fields(); ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php do_action( 'woocommerce_add_payment_method_form_bottom' ); ?>

            <div class="mt-25 flex justify-end">
                <?php wp_nonce_field( 'woocommerce-add-payment-method', 'woocommerce-add-payment-method-nonce' ); ?>
                <button type="submit" class="button" id="place_order" value="<?= esc_attr__( 'Προσθήκη μεθόδου', 'com-theme' ) ?>"><?= esc_html__( 'Προσθήκη μεθόδου', 'com-theme' ) ?></button>
                <input type="hidden" name="woocommerce_add_payment_method" id="woocommerce_add_payment_method" value="1">
            </div>
        </div>
    </form>
<?php else : ?>
    <div class="rounded-[1.2rem] bg-ochre-light p-25 text-[1.5rem] leading-[1.45]">
        <?= esc_html__( 'Νέες μέθοδοι πληρωμής μπορούν να αποθηκευτούν κατά την ολοκλήρωση μιας αγοράς.', 'com-theme' ) ?>
    </div>
<?php endif; ?>
