<?php
/**
 * Branded order details shown in customer emails.
 *
 * @package com-theme
 */

defined( 'ABSPATH' ) || exit;

$blue      = '#173276';
$blue_soft = '#8185BE';
$border    = '#DDE2EE';
$card_bg   = '#F8F7F2';
$order_url = $sent_to_admin ? $order->get_edit_order_url() : '';

do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email );

$upper = static function ( string $text ): string {
    if ( class_exists( '\com\theme' ) && method_exists( '\com\theme', 'remove_accents' ) ) {
        return (string) \com\theme::remove_accents( $text );
    }

    return function_exists( 'mb_strtoupper' )
        ? mb_strtoupper( $text, 'UTF-8' )
        : strtoupper( $text );
};
?>

<div style="margin:4px 0 30px 0;color:<?php echo esc_attr( $blue ); ?>;">
    <p style="margin:0 0 18px 0;color:<?php echo esc_attr( $blue ); ?>;font-size:12px;line-height:1.4;font-weight:700;letter-spacing:.12em;white-space:nowrap;"><?php echo esc_html( $upper( __( 'Παραγγελία', 'iw-theme' ) ) ); ?> <?php if ( $order_url ) : ?><a href="<?php echo esc_url( $order_url ); ?>" style="color:<?php echo esc_attr( $blue ); ?>;text-decoration:none;">#<?php echo esc_html( $order->get_order_number() ); ?></a><?php else : ?>#<?php echo esc_html( $order->get_order_number() ); ?><?php endif; ?> · <?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'd/m/Y' ) ); ?></p>

    <?php foreach ( $order->get_items() as $item_id => $item ) :
        if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
            continue;
        }

        $item_name = apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false );
        $quantity  = (int) $item->get_quantity();
        $price     = $order->get_formatted_line_subtotal( $item );
        $day       = (string) $item->get_meta( 'tickets_day', true );
        $time      = (string) $item->get_meta( 'tickets_time', true );
        $tickets   = (int) $item->get_meta( 'tickets_total', true );
        $details   = [];

        if ( $day !== '' ) {
            $timestamp = strtotime( $day );
            $details[] = $timestamp
                ? $upper( wp_date( 'l, j F', $timestamp ) )
                : $upper( $day );
        }
        if ( $time !== '' ) {
            $details[] = $time;
        }
        if ( $tickets > 0 ) {
            $details[] = sprintf(
                '%d %s',
                $tickets,
                $upper( 1 === $tickets ? __( 'Εισιτήριο', 'iw-theme' ) : __( 'Εισιτήρια', 'iw-theme' ) )
            );
        } elseif ( $quantity > 0 ) {
            $details[] = sprintf( '%d ×', $quantity );
        }
        ?>
        <div style="margin:0 0 14px 0;padding:22px 24px;border:1px solid <?php echo esc_attr( $border ); ?>;border-radius:14px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border:0;border-collapse:collapse;">
                <tr>
                    <td valign="top" style="padding:0 18px 0 0;border:0;color:<?php echo esc_attr( $blue ); ?>;font-family:Arial,sans-serif;">
                        <div style="font-size:17px;line-height:1.3;font-weight:700;">
                            <?php echo wp_kses_post( $item_name ); ?>
                        </div>
                        <?php if ( $details ) : ?>
                            <div style="margin-top:9px;color:<?php echo esc_attr( $blue ); ?>;font-size:12px;line-height:1.5;">
                                <?php echo esc_html( implode( '  ·  ', $details ) ); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td valign="middle" align="right" style="width:92px;padding:0;border:0;color:<?php echo esc_attr( $blue ); ?>;font-family:Arial,sans-serif;font-size:19px;line-height:1.2;font-weight:700;white-space:nowrap;">
                        <?php echo wp_kses_post( $price ); ?>
                    </td>
                </tr>
            </table>
        </div>
    <?php endforeach; ?>

    <?php
    $item_totals = $order->get_order_item_totals();
    $summary_totals = [];
    foreach ( $item_totals as $key => $total ) {
        if ( 'payment_method' === $key ) {
            continue;
        }
        $summary_totals[ $key ] = $total;
    }
    ?>

    <div style="margin:22px 0 14px 0;padding:22px 24px;border:1px solid <?php echo esc_attr( $border ); ?>;border-radius:14px;">
        <div style="margin:0 0 15px 0;color:<?php echo esc_attr( $blue_soft ); ?>;font-size:12px;line-height:1.4;font-weight:700;letter-spacing:.12em;">
            <?php echo esc_html( $upper( __( 'Σύνοψη αγοράς', 'iw-theme' ) ) ); ?>
        </div>
        <?php
        $summary_count = count( $summary_totals );
        $summary_index = 0;
        foreach ( $summary_totals as $key => $total ) :
            ++$summary_index;
            $is_last = $summary_index === $summary_count;
            ?>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border:0;border-collapse:collapse;<?php echo $is_last ? 'border-top:1px solid ' . esc_attr( $border ) . ';margin-top:12px;padding-top:12px;' : ''; ?>">
                <tr>
                    <td style="padding:<?php echo $is_last ? '14px' : '5px'; ?> 0 5px 0;border:0;color:<?php echo esc_attr( $is_last ? $blue : $blue_soft ); ?>;font-family:Arial,sans-serif;font-size:14px;line-height:1.4;<?php echo $is_last ? 'font-weight:700;' : ''; ?>">
                        <?php echo wp_kses_post( $total['label'] ); ?>
                    </td>
                    <td align="right" style="padding:<?php echo $is_last ? '14px' : '5px'; ?> 0 5px 16px;border:0;color:<?php echo esc_attr( $blue ); ?>;font-family:Arial,sans-serif;font-size:<?php echo $is_last ? '19px' : '14px'; ?>;line-height:1.4;font-weight:<?php echo $is_last ? '700' : '400'; ?>;white-space:nowrap;">
                        <?php echo wp_kses_post( $total['value'] ); ?>
                    </td>
                </tr>
            </table>
        <?php endforeach; ?>
    </div>

    <div style="margin:0 0 22px 0;padding:22px 24px;border:1px solid <?php echo esc_attr( $border ); ?>;border-radius:14px;">
        <div style="margin:0 0 15px 0;color:<?php echo esc_attr( $blue_soft ); ?>;font-size:12px;line-height:1.4;font-weight:700;letter-spacing:.12em;">
            <?php echo esc_html( $upper( __( 'Στοιχεία αγοράς', 'iw-theme' ) ) ); ?>
        </div>
        <div style="color:<?php echo esc_attr( $blue ); ?>;font-size:14px;line-height:1.55;">
            <?php echo esc_html( $order->get_payment_method_title() ?: __( 'Δεν έχει οριστεί μέθοδος πληρωμής', 'iw-theme' ) ); ?><br>
            <?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'd/m/Y · H:i' ) ); ?>
        </div>
        <div style="margin-top:15px;padding-top:15px;border-top:1px solid <?php echo esc_attr( $border ); ?>;color:<?php echo esc_attr( $blue ); ?>;font-size:14px;line-height:1.4;font-weight:700;">
            <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
        </div>
    </div>

    <?php if ( $order->get_customer_note() ) : ?>
        <div style="margin:0 0 22px 0;padding:20px 24px;background:<?php echo esc_attr( $card_bg ); ?>;border-radius:14px;color:<?php echo esc_attr( $blue ); ?>;font-size:14px;line-height:1.55;">
            <strong><?php esc_html_e( 'Σημείωση', 'iw-theme' ); ?></strong><br>
            <?php echo wp_kses( nl2br( wc_wptexturize_order_note( $order->get_customer_note() ) ), [ 'br' => [] ] ); ?>
        </div>
    <?php endif; ?>
</div>

<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>
