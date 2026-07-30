<?php
/**
 * Customer addresses.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

$customer_id = get_current_user_id();
$get_addresses = [
    'billing' => __( 'Διεύθυνση χρέωσης', 'com-theme' ),
];

// Temporarily hidden: purchases currently contain digital tickets only.
// if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) {
//     $get_addresses['shipping'] = __( 'Διεύθυνση αποστολής', 'com-theme' );
// }

$get_addresses = apply_filters( 'woocommerce_my_account_get_addresses', $get_addresses, $customer_id );

get_template_part( 'woocommerce/myaccount/page-title', null, [
    'eyebrow'     => __( 'Στοιχεία χρέωσης', 'com-theme' ),
    'title'       => __( 'Διευθύνσεις', 'com-theme' ),
    'description' => __( 'Οι διευθύνσεις αυτές χρησιμοποιούνται αυτόματα κατά την ολοκλήρωση της αγοράς.', 'com-theme' ),
] );
?>

<div class="space-y-15">
    <?php foreach ( $get_addresses as $name => $address_title ) :
        $address = wc_get_account_formatted_address( $name );
        $edit_url = wc_get_endpoint_url( 'edit-address', $name );
    ?>
        <article class="min-h-[18rem] rounded-[1.2rem] border border-blue/15 p-20 md:p-25">
            <div class="mb-20 flex items-start justify-between gap-20">
                <div class="flex items-center gap-10 text-blue-soft">
                    <svg class="size-20 fill-current" aria-hidden="true"><use xlink:href="#icon-location"></use></svg>
                    <h3 class="m-0 text-[1.1rem] font-bold tracking-[.1em]"><?= esc_html( com\theme::remove_accents( $address_title ) ) ?></h3>
                </div>
                <a href="<?= esc_url( $edit_url ) ?>" data-barba-prevent data-account-pages="link" class="text-[1.3rem] underline underline-offset-4">
                    <?= esc_html__( 'Επεξεργασία', 'com-theme' ) ?>
                </a>
            </div>
            <address class="text-[1.3rem] not-italic leading-[1.5]">
                <?= $address ? wp_kses_post( $address ) : esc_html__( 'Δεν έχει οριστεί ακόμη.', 'com-theme' ) ?>
            </address>

            <?php do_action( 'woocommerce_my_account_after_my_address', $name ); ?>
        </article>
    <?php endforeach; ?>
</div>
