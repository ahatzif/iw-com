<?php
/**
 * Saved payment methods.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

$saved_methods = wc_get_customer_saved_methods_list( get_current_user_id() );
$has_methods = (bool) $saved_methods;

do_action( 'woocommerce_before_account_payment_methods', $has_methods );

get_template_part( 'woocommerce/myaccount/page-title', null, [
    'eyebrow'     => __( 'Πληρωμές', 'com-theme' ),
    'title'       => __( 'Μέθοδοι πληρωμής', 'com-theme' ),
    'description' => __( 'Διαχειριστείτε τις κάρτες που έχετε αποθηκεύσει για πιο γρήγορες αγορές.', 'com-theme' ),
] );
?>

<?php if ( $has_methods ) : ?>
    <div class="space-y-15">
        <?php foreach ( $saved_methods as $type => $methods ) : ?>
            <?php foreach ( $methods as $method ) : ?>
                <article class="flex flex-col gap-20 rounded-[1.2rem] border border-blue/15 p-20 sm:flex-row sm:items-center sm:justify-between md:p-25">
                    <div class="flex items-center gap-15">
                        <span class="flex h-40 w-60 items-center justify-center rounded-[.6rem] bg-ochre-light text-[1.1rem] font-bold uppercase">
                            <?= esc_html( $method['method']['brand'] ?? __( 'Κάρτα', 'com-theme' ) ) ?>
                        </span>
                        <div>
                            <strong class="block text-[1.6rem]">
                                <?php if ( ! empty( $method['method']['last4'] ) ) : ?>
                                    •••• <?= esc_html( $method['method']['last4'] ) ?>
                                <?php else : ?>
                                    <?= esc_html__( 'Αποθηκευμένη μέθοδος', 'com-theme' ) ?>
                                <?php endif; ?>
                            </strong>
                            <?php if ( ! empty( $method['expires'] ) ) : ?>
                                <span class="mt-3 block text-[1.2rem] text-blue/55"><?= esc_html( sprintf( __( 'Λήξη %s', 'com-theme' ), $method['expires'] ) ) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-10">
                        <?php foreach ( (array) ( $method['actions'] ?? [] ) as $key => $action ) : ?>
                            <a href="<?= esc_url( $action['url'] ) ?>" data-barba-prevent class="rounded-[.6rem] border border-blue px-15 py-10 text-[1.2rem] font-bold transition-colors hover:bg-blue hover:text-white">
                                <?= esc_html( $action['name'] ) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <div class="rounded-[1.2rem] bg-ochre-light p-25 md:p-40">
        <h3 class="m-0 text-[2.2rem] font-bold"><?= esc_html__( 'Δεν υπάρχουν αποθηκευμένες μέθοδοι', 'com-theme' ) ?></h3>
        <p class="mb-0 mt-10 max-w-[50rem] text-[1.5rem] leading-[1.45]"><?= esc_html__( 'Μπορείτε να αποθηκεύσετε μια κάρτα κατά την ολοκλήρωση της επόμενης αγοράς σας.', 'com-theme' ) ?></p>
    </div>
<?php endif; ?>

<?php do_action( 'woocommerce_after_account_payment_methods', $has_methods ); ?>

<?php if ( WC()->payment_gateways->get_available_payment_gateways() ) : ?>
    <div class="mt-25 text-right">
        <a href="<?= esc_url( wc_get_endpoint_url( 'add-payment-method' ) ) ?>" data-barba-prevent data-account-pages="link" class="inline-flex min-h-50 items-center justify-center rounded-[.8rem] bg-blue px-25 text-[1.4rem] font-bold text-white">
            <?= esc_html__( 'Προσθήκη μεθόδου', 'com-theme' ) ?>
        </a>
    </div>
<?php endif; ?>
