<?php
/**
 * Branded customer addresses shown in emails.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

$billing_address  = $order->get_formatted_billing_address();
$shipping_address = $order->get_formatted_shipping_address();
$blue             = '#173276';
$blue_soft        = '#8185BE';
$border           = '#DDE2EE';

$upper = static function ( string $text ): string {
    if ( class_exists( '\com\theme' ) && method_exists( '\com\theme', 'remove_accents' ) ) {
        return (string) \com\theme::remove_accents( $text );
    }

    return function_exists( 'mb_strtoupper' )
        ? mb_strtoupper( $text, 'UTF-8' )
        : strtoupper( $text );
};
?>

<div style="margin:0 0 22px 0;padding:22px 24px;border:1px solid <?php echo esc_attr( $border ); ?>;border-radius:14px;color:<?php echo esc_attr( $blue ); ?>;">
    <div style="margin:0 0 16px 0;color:<?php echo esc_attr( $blue_soft ); ?>;font-size:12px;line-height:1.4;font-weight:700;letter-spacing:.12em;">
        <?php echo esc_html( $upper( __( 'Διεύθυνση χρέωσης', 'woocommerce' ) ) ); ?>
    </div>
    <div style="font-size:14px;line-height:1.55;font-style:normal;">
        <?php echo wp_kses_post( $billing_address ?: esc_html__( 'Μη διαθέσιμη', 'iw-theme' ) ); ?>
    </div>
    <?php if ( $order->get_billing_email() || $order->get_billing_phone() ) : ?>
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid <?php echo esc_attr( $border ); ?>;font-size:14px;line-height:1.65;">
            <?php
            $contact_rows = [];
            if ( $order->get_billing_email() ) {
                $contact_rows[] = sprintf(
                    '<span style="color:%1$s;">%2$s</span>&nbsp;<a href="mailto:%3$s" style="color:%4$s;text-decoration:none;">%5$s</a>',
                    esc_attr( $blue_soft ),
                    esc_html__( 'Email:', 'iw-theme' ),
                    esc_attr( $order->get_billing_email() ),
                    esc_attr( $blue ),
                    esc_html( $order->get_billing_email() )
                );
            }
            if ( $order->get_billing_phone() ) {
                $contact_rows[] = sprintf(
                    '<span style="color:%1$s;">%2$s</span>&nbsp;%3$s',
                    esc_attr( $blue_soft ),
                    esc_html__( 'Τηλέφωνο:', 'iw-theme' ),
                    wc_make_phone_clickable( $order->get_billing_phone() )
                );
            }
            echo wp_kses_post( implode( '<br>', $contact_rows ) );
            ?>
        </div>
    <?php endif; ?>
    <?php do_action( 'woocommerce_email_customer_address_section', 'billing', $order, $sent_to_admin, false ); ?>
</div>

<?php if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() && $shipping_address ) : ?>
    <div style="margin:0 0 22px 0;padding:22px 24px;border:1px solid <?php echo esc_attr( $border ); ?>;border-radius:14px;color:<?php echo esc_attr( $blue ); ?>;">
        <div style="margin:0 0 16px 0;color:<?php echo esc_attr( $blue_soft ); ?>;font-size:12px;line-height:1.4;font-weight:700;letter-spacing:.12em;">
            <?php echo esc_html( $upper( __( 'Διεύθυνση αποστολής', 'woocommerce' ) ) ); ?>
        </div>
        <div style="font-size:14px;line-height:1.55;font-style:normal;">
            <?php echo wp_kses_post( $shipping_address ); ?>
        </div>
        <?php do_action( 'woocommerce_email_customer_address_section', 'shipping', $order, $sent_to_admin, false ); ?>
    </div>
<?php endif; ?>
