<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Dompdf\Dompdf;
use Dompdf\Options as DompdfOptions;

class IW_Ticket_PDF_Service {

    const REST_NS   = 'iw/v1';
    const REST_BASE = 'tickets/public-pdf';
    const PDF_TEMPLATE_VERSION = 'v10';

    protected static $email_images_dirname = 'email-images';

    public static function init(): void {
        add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
        add_action( 'woocommerce_payment_complete', [ __CLASS__, 'send_tickets_email_for_order' ], 40 );
    }

    public static function register_rest_routes(): void {
        register_rest_route(
            self::REST_NS,
            '/' . self::REST_BASE,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ __CLASS__, 'handle_public_pdf_download' ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'ticket_uuid' => [ 'required' => true, 'type' => 'string' ],
                        'exp'         => [ 'required' => true, 'type' => 'integer' ],
                        'sig'         => [ 'required' => true, 'type' => 'string' ],
                        'view'        => [ 'required' => false, 'type' => 'string' ],
                    ],
                ],
            ]
        );
    }

    public static function send_tickets_email_for_order( $order_id ): void {
        $order_id = (int) $order_id;
        if ( $order_id <= 0 ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        if ( in_array( $order->get_status(), [ 'failed', 'cancelled', 'refunded' ], true ) ) {
            return;
        }

        $already_sent = (int) $order->get_meta( '_iw_ticket_pdf_email_sent', true );
        if ( $already_sent > 0 ) {
            return;
        }

        $recipients = self::email_recipients_for_order( $order );
        if ( empty( $recipients ) ) {
            return;
        }

        $email_data = self::build_tickets_email_data_for_order( $order_id, true );
        if ( isset( $email_data['error'] ) ) {
            return;
        }

        $sent = false;
        foreach ( $recipients as $recipient ) {
            $sent = wp_mail(
                $recipient,
                (string) $email_data['subject'],
                (string) $email_data['message'],
                [ 'Content-Type: text/html; charset=UTF-8' ],
                array_values( array_unique( (array) $email_data['attachments'] ) )
            ) || $sent;
        }

        if ( $sent ) {
            $order->update_meta_data( '_iw_ticket_pdf_email_sent', time() );
            $order->save();
        }
    }

    protected static function email_recipients_for_order( $order ): array {
        $raw = $order ? $order->get_meta( '_iw_cashier_delivery_emails', true ) : '';

        if ( is_string( $raw ) ) {
            $decoded = json_decode( $raw, true );
            $raw     = is_array( $decoded ) ? $decoded : [ $raw ];
        }

        $raw = (array) $raw;
        $raw[] = (string) $order->get_billing_email();

        $recipients = [];
        foreach ( (array) $raw as $email_group ) {
            $parts = is_array( $email_group )
                ? $email_group
                : preg_split( '/[\s,;\x{037E}]+/u', (string) $email_group, -1, PREG_SPLIT_NO_EMPTY );

            foreach ( (array) $parts as $email ) {
                $email = sanitize_email( (string) $email );
                if ( $email !== '' && is_email( $email ) && ! in_array( $email, $recipients, true ) ) {
                    $recipients[] = $email;
                }
            }
        }

        return $recipients;
    }

    public static function build_tickets_email_data_for_order( int $order_id, bool $include_attachments = true ): array {
        $order_id = (int) $order_id;
        if ( $order_id <= 0 ) {
            return [ 'error' => __( 'This email requires an order.', 'iw-theme' ) ];
        }

        $tickets = self::get_order_tickets( $order_id );
        if ( empty( $tickets ) ) {
            return [ 'error' => __( 'No tickets were found for this order.', 'iw-theme' ) ];
        }

        $attachments    = [];
        $list_items     = [];
        $grouped_tickets = [];
        $exp = time() + ( 365 * DAY_IN_SECONDS );
        foreach ( $tickets as $index => $ticket ) {
            $order_item_id = (int) ( $ticket['order_item_id'] ?? 0 );
            $group_key = $order_item_id > 0 ? $order_item_id : $index;

            if ( ! isset( $grouped_tickets[ $group_key ] ) ) {
                $grouped_tickets[ $group_key ] = [
                    'post_id'     => (int) ( $ticket['post_id'] ?? 0 ),
                    'slot_start'  => (string) ( $ticket['slot_start'] ?? '' ),
                    'slot_end'    => (string) ( $ticket['slot_end'] ?? '' ),
                    'tickets'     => [],
                ];
            }

            if ( empty( $grouped_tickets[ $group_key ]['slot_end'] ) && ! empty( $ticket['slot_end'] ) ) {
                $grouped_tickets[ $group_key ]['slot_end'] = (string) $ticket['slot_end'];
            }

            $grouped_tickets[ $group_key ]['tickets'][] = $ticket;

            if ( $include_attachments ) {
                $pdf_path = self::ensure_ticket_pdf( $ticket );
                if ( $pdf_path !== '' && file_exists( $pdf_path ) ) {
                    $attachments[] = $pdf_path;
                }
            }
        }

        foreach ( $grouped_tickets as $group ) {

            $post_id = (int) $group['post_id'];
            $title   = $post_id > 0 ? get_the_title( $post_id ) : __( 'Ticket', 'iw-theme' );

            $post_type = $post_id > 0 ? get_post_type( $post_id ) : '';
            $permalink = $post_id > 0 ? get_permalink( $post_id ) : '';
            $icon_color = '#173276';


            $building_data  = self::get_ticket_post_building_data( $post_id );
            $building_title = (string) ( $building_data['building_title'] ?? '' );
            $address_title  = (string) ( $building_data['address_title'] ?? '' );

            $address_url = (string) ( $building_data['address_url'] ?? '' );


            $slot_label = self::format_slot_label( (string) $group['slot_start'] );
            $location_icon_html = self::build_email_icon_img_html( 'location', $icon_color, __( 'Location', 'iw-theme' ) );
            $calendar_links_html = self::build_email_calendar_links_html(
                $post_id,
                (string) $group['slot_start'],
                (string) ( $group['slot_end'] ?? '' )
            );

            $image_html = '';
            $has_email_hero = false;

            $image_id = get_post_thumbnail_id( $post_id );
            if ( $image_id ) {
                $image_src = wp_get_attachment_image_src( $image_id, 'large' );

                if ( ! empty( $image_src[0] ) ) {
                    $email_hero_url = self::get_email_hero_image_url( (int) $image_id );
                    $has_email_hero = $email_hero_url !== '';
                    $image_url = $has_email_hero ? $email_hero_url : (string) $image_src[0];
                    $image_tag = sprintf(
                        '<img src="%s" alt="%s" style="display:block;width:100%%;height:auto;border:0;margin:0;%s">',
                        esc_url( $image_url ),
                        esc_attr( $title ),
                        $has_email_hero ? 'border-radius:16px 16px 0 0;' : 'border-radius:16px;'
                    );

                    if ( $permalink !== '' ) {
                        $image_tag = sprintf(
                            '<a href="%s" target="_blank" style="display:block;text-decoration:none;">%s</a>',
                            esc_url( $permalink ),
                            $image_tag
                        );
                    }



                    $image_html = '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin: 30px 0 ' . ( $has_email_hero ? '0' : '30px' ) . ' 0;"><tr><td style="padding:0;">' . $image_tag . '</td></tr></table>';
                }
            }

            $ticket_rows = '';

            foreach ( $group['tickets'] as $index => $ticket ) {

                $name = trim( (string) ( $ticket['attendee_name'] ?? '' ) );

                if ( $name === '' ) {
                    $name = sprintf( __( 'Participant %d', 'iw-theme' ), $index + 1 );
                }

                $price_category = (string) ( $ticket['price_category'] ?? '' );
                $price_parts = explode( ':', $price_category );
                $price_label = isset( $price_parts[1] ) && $price_parts[1] !== '' ? $price_parts[1] : $price_category;

                $unit_price = isset( $ticket['unit_price'] ) ? (float) $ticket['unit_price'] : 0.0;
                $price_html = function_exists( 'wc_price' ) ? wc_price( $unit_price ) : number_format_i18n( $unit_price, 2 );

                $ticket_uuid = (string) ( $ticket['ticket_uuid'] ?? '' );

                $wallet_url = self::build_wallet_public_url( $ticket_uuid, $exp );
                $google_wallet_url = self::build_google_wallet_public_url( $ticket_uuid, $exp );
                $pdf_url = self::build_public_pdf_url( $ticket_uuid, $exp );

                $qr_data_uri = self::build_qr_data_uri_for_email( $ticket );

                $ticket_actions = '';

                if ( $wallet_url !== '' ) {
                    $ticket_actions .= self::build_wallet_button_html( 'apple', $wallet_url );
                }

                if ( $google_wallet_url !== '' ) {
                    $ticket_actions .= self::build_wallet_button_html( 'google', $google_wallet_url );
                }

                $ticket_card_margin = $index > 0 ? '12px 0 0 0' : '0';
                $ticket_buttons = $ticket_actions . self::build_download_pdf_button_html( $pdf_url );

                $ticket_rows .= sprintf(
                    '
                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%%" bgcolor="#F7F5EE" style="border-collapse:separate;background-color:#F7F5EE;border:1px solid #EBE6D6;border-radius:16px;margin:%s;">
                        <tr>
                            <td valign="top" style="padding:28px 20px 28px 28px;color:#173276;">
                                <div style="font-size:14px;line-height:1.2;font-weight:700;color:#173276;margin:0 0 18px 0;">%s %d</div>
                                <div style="font-size:13px;line-height:1.35;color:#173276;margin:0 0 8px 0;">%s: <strong>%s</strong></div>
                                <div style="font-size:13px;line-height:1.35;color:#173276;margin:0 0 8px 0;">%s: <strong>%s</strong></div>
                                <div style="font-size:13px;line-height:1.35;color:#173276;margin:0;">%s: <strong>%s</strong></div>
                                %s
                            </td>
                            <td valign="middle" align="right" style="width:104px;padding:28px 28px 28px 10px;">%s</td>
                        </tr>
                    </table>',
                    esc_attr( $ticket_card_margin ),
                    esc_html__( 'ΕΙΣΙΤΗΡΙΟ', 'iw-theme' ),
                    $index + 1,
                    esc_html__( 'ΟΝΟΜ/ΜΟ', 'iw-theme' ),
                    esc_html( IW_Ticketing::remove_accents( $name ) ),
                    esc_html__( 'ΚΑΤΗΓΟΡΙΑ', 'iw-theme' ),
                    esc_html( IW_Ticketing::remove_accents( $price_label !== '' ? $price_label : '-' ) ),
                    esc_html__( 'ΤΙΜΗ', 'iw-theme' ),
                    wp_kses_post( $price_html ),
                    $ticket_buttons !== '' ? '<div style="margin-top:18px;">' . $ticket_buttons . '</div>' : '',
                    $qr_data_uri !== '' ? '<img src="' . esc_attr( $qr_data_uri ) . '" width="96" height="96" alt="QR" style="display:block;width:96px;height:96px;margin:0;">' : ''
                );
            }

            $event_card_margin = $image_html !== '' ? '0' : '60px 0 0 0';
            $event_card_radius = $has_email_hero ? '0 0 16px 16px' : '16px';
            $event_card_padding = $has_email_hero ? '28px 30px 30px 30px' : '30px';

            $list_items[] = sprintf(
                '
                %s
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%%" bgcolor="#F7F5EE" style="border-collapse:separate;background-color:#F7F5EE;border-radius:%s;margin:%s;padding:%s;">
                    <tr>
                        <td>
                            <div style="font-size:18px;line-height:1.25;color:#173276;margin:0 0 14px 0;">%s</div>

                              <table role="presentation" cellpadding="0" cellspacing="0" width="100%%" style="border-collapse:collapse;margin:18px 0 0 0;">
                                <tr>
                                    <td style="padding:0;">%s%s</td>
                                </tr>
                            </table>
                            %s
                        </td>
                    </tr>
                </table>
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%%" style="border-collapse:collapse;margin:0;">
                    <tr>
                        <td style="padding:0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%%" style="border-collapse:collapse;margin:0;">
                                <tr>
                                    <td height="12" style="height:12px;font-size:0;line-height:0;padding:0;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                %s',
                $image_html,
                esc_attr( $event_card_radius ),
                esc_attr( $event_card_margin ),
                esc_attr( $event_card_padding ),

                $permalink !== ''
                    ? '<a href="' . esc_url( $permalink ) . '" target="_blank" style="color:#173276;text-decoration:none;font-weight:400 !important;">' . str_replace( 'class="font-bold"', 'style="font-weight:bold"', $title ) . '</a>'
                    : esc_html( $title ),
                $building_title !== ''
                    ? '<table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 10px 0;"><tr><td valign="top" style="width:16px;padding:0 7px 0 0;">' . $location_icon_html . '</td><td valign="top" style="padding:0;"><div style="font-size:13px;line-height:1.3;color:#173276;margin:0;">' . str_replace( 'class="font-bold"', 'style="font-weight:bold"', $building_title ) .

                     ( $address_url !== ''
                        ? ' - ' . $address_title . ' - <a href="' . esc_url( $address_url ) . '" target="_blank" style="color:#173276;text-decoration:underline;font-weight:normal;">' . esc_html__( 'Χάρτης', 'iw-theme' ) . '</a>'
                        : '' ) .

                    '</div></td></tr></table>'
                    : '',

                $slot_label !== ''
                    ? '<div style="font-size:13px;line-height:1.3;color:#173276;margin:0;">' . $slot_label . '</div>'
                    : '',
                $calendar_links_html,
                $ticket_rows
            );
        }

        $subject = sprintf( __( 'Τα εισιτήριά σας για την παραγγελία #%d', 'iw-theme' ), $order_id );
        $order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : false;
        $message = self::build_email_structured_data_html( $order, $grouped_tickets );

        if ( $order && function_exists( 'iw_theme_render_order_email_intro' ) ) {
            ob_start();
            iw_theme_render_order_email_intro(
                $order,
                __( 'Τα εισιτήριά σας είναι έτοιμα', 'iw-theme' ),
                __( 'Σας ευχαριστούμε για την αγορά σας. Θα βρείτε τα εισιτήριά σας συνημμένα σε αυτό το email. Μπορείτε επίσης να κατεβάσετε κάθε εισιτήριο ή να το προσθέσετε στο wallet σας από τις παρακάτω ασφαλείς συνδέσεις.', 'iw-theme' )
            );
            $message .= ob_get_clean();
        } else {
            $message .= '<h2 style="margin:0 0 28px 0;font-size:35px;line-height:115%;font-weight:bold;color:#173276;">' . esc_html__( 'Τα εισιτήριά σας είναι έτοιμα', 'iw-theme' ) . '</h2>';
            $message .= '<p style="font-size:24px;line-height:130%;color:#173276;margin:0 0 18px 0;">' . esc_html__( 'Σας ευχαριστούμε για την αγορά σας. Θα βρείτε τα εισιτήριά σας συνημμένα σε αυτό το email. Μπορείτε επίσης να κατεβάσετε κάθε εισιτήριο ή να το προσθέσετε στο wallet σας από τις παρακάτω ασφαλείς συνδέσεις.', 'iw-theme' ) . '</p>';
        }
        $order_url = '';

        if ( $order && method_exists( $order, 'get_view_order_url' ) ) {
            $order_url = (string) $order->get_view_order_url();
        }

        if ( $order_url !== '' ) {
            $message .= '<a target="_blank" href="' . esc_url( $order_url ) . '" class="btn">' . IW_Ticketing::remove_accents(__( 'Παραγγελία', 'iw-theme' ) ). '</a>';

        }
        $message .= implode( '', $list_items );

        if( ! empty( $contact_page = get_field( 'contact_page', 'option' ) ) ) {


            $message .= '<table role="presentation" cellpadding="0" cellspacing="0" width="100%%" style="border-collapse:separate;background:#FFFFFF;border-radius:20px;padding: 0 30px;margin-bottom: 40px;">
                        <tr valign="center">
                            <td style="font-size: 16px;">' . __('Έχεις κάποια απορία σχετικά με την εκδήλωση;', 'iw-theme') . '</td>
                            <td style="white-space: nowrap; padding-left: 10px;"><div><a href="' . esc_url(get_permalink( $contact_page )) . '" class="btn">' . IW_Ticketing::remove_accents(__('Επικοινωνία', 'iw-theme')) . '</a></div></td>
                        </tr>
                    </table>';
        }




        if ( function_exists( 'iw_theme_render_email_support_paragraph' ) ) {
            ob_start();
            iw_theme_render_email_support_paragraph();
            $message .= ob_get_clean();
        } elseif ( function_exists( 'iw_theme_email_support_message' ) ) {
            $message .= wp_kses_post( wpautop( wptexturize( iw_theme_email_support_message() ) ) );
        }

        return [
            'subject'     => $subject,
            'message'     => $message,
            'attachments' => $attachments,
            'ticket_count' => count( $tickets ),
        ];
    }

    public static function build_order_item_email_context( $item ): array {
        if ( ! is_object( $item ) || ! method_exists( $item, 'get_meta' ) ) {
            return [];
        }

        $post_id = absint( $item->get_meta( 'tickets_for_id', true ) );
        if ( $post_id <= 0 ) {
            return [];
        }

        $title = (string) get_the_title( $post_id );
        if ( $title === '' ) {
            $title = __( 'Ticket', 'iw-theme' );
        }

        $tickets_total = (int) $item->get_meta( 'tickets_total', true );
        if ( $tickets_total <= 0 && method_exists( $item, 'get_quantity' ) ) {
            $tickets_total = (int) $item->get_quantity();
        }

        $day = trim( (string) $item->get_meta( 'tickets_day', true ) );
        $time = trim( (string) $item->get_meta( 'tickets_time', true ) );
        $slot_label = self::format_order_item_slot_label( $day, $time );
        $building_data = self::get_ticket_post_building_data( $post_id );

        $image_url = '';
        $image_id = get_post_thumbnail_id( $post_id );
        if ( $image_id ) {
            $image_src = wp_get_attachment_image_src( $image_id, 'full' );
            $image_url = $image_src ? (string) $image_src[0] : '';
        }

        $meta_rows = [
            [
                'key'   => 'tickets_total',
                'label' => __( 'Αριθμός εισιτηρίων', 'iw-theme' ),
                'value' => max( 1, $tickets_total ),
            ],
            [
                'key'   => 'tickets_slot',
                'label' => __( 'Slot', 'iw-theme' ),
                'value' => $slot_label,
            ],
            [
                'key'   => 'tickets_building',
                'label' => __( 'Κτίριο', 'iw-theme' ),
                'value' => $building_data['building_title'],
            ],
            [
                'key'   => 'tickets_address',
                'label' => __( 'Διεύθυνση', 'iw-theme' ),
                'value' => $building_data['address_title'],
            ],
        ];

        return [
            'post_id'           => $post_id,
            'title'             => $title,
            'display_title'     => $title,
            'permalink'         => get_permalink( $post_id ),
            'image_url'         => $image_url,
            'image_object_fit'  => 'cover',
            'image_background'  => 'transparent',
            'tickets_total'     => max( 1, $tickets_total ),
            'slot_label'        => $slot_label,
            'building_id'       => $building_data['building_id'],
            'building_title'    => $building_data['building_title'],
            'address_title'     => $building_data['address_title'],
            'meta_rows'         => array_values(
                array_filter(
                    $meta_rows,
                    static function ( array $row ): bool {
                        return trim( (string) $row['value'] ) !== '';
                    }
                )
            ),
            'hidden_meta_keys'  => [
                'iw_title',
                'iw_price',
                'tickets_for_id',
                'tickets_day',
                'tickets_time',
                'tickets_visitors',
                'tickets_total',
                'tickets_channel',
            ],
        ];
    }

    public static function handle_public_pdf_download( WP_REST_Request $request ) {
        $ticket_uuid = trim( (string) $request->get_param( 'ticket_uuid' ) );
        $exp         = (int) $request->get_param( 'exp' );
        $sig         = trim( (string) $request->get_param( 'sig' ) );
        $view        = strtolower( trim( (string) $request->get_param( 'view' ) ) );
        $html_view   = $view === 'html';
        $admin_preview = $html_view && is_user_logged_in() && current_user_can( 'manage_options' );

        if ( $ticket_uuid === '' || $exp <= 0 || $sig === '' ) {
            return new WP_REST_Response( [ 'error' => 'missing_params' ], 400 );
        }

        if ( ! $admin_preview && ! self::validate_public_pdf_link( $ticket_uuid, $exp, $sig ) ) {
            return new WP_REST_Response( [ 'error' => 'invalid_or_expired_link' ], 403 );
        }

        if ( ! class_exists( 'IW_Tickets_DB' ) || ! method_exists( 'IW_Tickets_DB', 'get_ticket_by_uuid' ) ) {
            return new WP_REST_Response( [ 'error' => 'db_not_ready' ], 500 );
        }

        $ticket = IW_Tickets_DB::get_ticket_by_uuid( $ticket_uuid );
        if ( empty( $ticket ) ) {
            return new WP_REST_Response( [ 'error' => 'not_found' ], 404 );
        }

        // Block access if the event has passed
        if ( ! empty( $ticket['slot_start'] ) ) {
            try {
                $event_date = new DateTimeImmutable(
                    (string) $ticket['slot_start'],
                    wp_timezone()
                );

                $now = new DateTimeImmutable(
                    'now',
                    wp_timezone()
                );

                if ( $now > $event_date ) {
                    return new WP_REST_Response(
                        [ 'error' => 'event_expired' ],
                        403
                    );
                }

            } catch ( Throwable $e ) {
                // fail open
            }
        }

        if ( $html_view ) {
            try {
                $html = self::build_ticket_template_html( $ticket );
            } catch ( Throwable $e ) {
                return new WP_REST_Response( [ 'error' => 'html_generation_failed' ], 500 );
            }

            nocache_headers();
            header( 'Content-Type: text/html; charset=UTF-8' );
            echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }

        $pdf_path = self::ensure_ticket_pdf( $ticket );
        if ( $pdf_path === '' || ! file_exists( $pdf_path ) ) {
            return new WP_REST_Response( [ 'error' => 'pdf_generation_failed' ], 500 );
        }

        nocache_headers();
        header( 'Content-Type: application/pdf' );
        header( 'Content-Length: ' . (string) filesize( $pdf_path ) );
        header( 'Content-Disposition: attachment; filename="ticket-' . sanitize_file_name( $ticket_uuid ) . '.pdf"' );
        readfile( $pdf_path );
        exit;
    }

    public static function build_public_pdf_url( string $ticket_uuid, int $exp ): string {
        $ticket_uuid = trim( $ticket_uuid );
        $exp = (int) $exp;
        $sig = self::build_public_pdf_signature( $ticket_uuid, $exp );

        return add_query_arg(
            [
                'ticket_uuid' => rawurlencode( $ticket_uuid ),
                'exp'         => $exp,
                'sig'         => $sig,
            ],
            rest_url( self::REST_NS . '/' . self::REST_BASE )
        );
    }

    public static function build_ticket_qr_data_uri( $ticket ): string {
        if ( is_object( $ticket ) ) {
            $ticket = get_object_vars( $ticket );
        }

        if ( ! is_array( $ticket ) ) {
            return '';
        }

        return self::build_qr_data_uri_for_email( $ticket );
    }

    public static function build_google_wallet_url( string $ticket_uuid, int $exp ): string {
        return self::build_google_wallet_public_url( $ticket_uuid, $exp );
    }

    protected static function validate_public_pdf_link( string $ticket_uuid, int $exp, string $sig ): bool {
        $expected = self::build_public_pdf_signature( $ticket_uuid, $exp );
        return $expected !== '' && hash_equals( $expected, $sig );
    }

    protected static function build_public_pdf_signature( string $ticket_uuid, int $exp ): string {
        $ticket_uuid = trim( $ticket_uuid );
        $exp = (int) $exp;
        if ( $ticket_uuid === '' || $exp <= 0 ) {
            return '';
        }

        return hash_hmac( 'sha256', $ticket_uuid . '|' . $exp, wp_salt( 'iw_ticketing_pdf_public' ) );
    }

    protected static function build_wallet_public_url( string $ticket_uuid, int $exp ): string {
        $ticket_uuid = trim( $ticket_uuid );
        if ( $ticket_uuid === '' ) {
            return '';
        }

        if ( ! class_exists( 'IW_Apple_Wallet_Tickets_Service' ) || ! method_exists( 'IW_Apple_Wallet_Tickets_Service', 'build_public_link_signature' ) ) {
            return '';
        }

        $sig = IW_Apple_Wallet_Tickets_Service::build_public_link_signature( $ticket_uuid, $exp );
        return add_query_arg(
            [
                'ticket_uuid' => rawurlencode( $ticket_uuid ),
                'exp'         => $exp,
                'sig'         => $sig,
            ],
            rest_url( 'iw/v1/apple-wallet/tickets/public-pass' )
        );
    }

    protected static function build_google_wallet_public_url( string $ticket_uuid, int $exp ): string {
        if ( class_exists( 'IW_Google_Wallet_Tickets_Service' ) && method_exists( 'IW_Google_Wallet_Tickets_Service', 'build_public_save_url' ) ) {
            return IW_Google_Wallet_Tickets_Service::build_public_save_url( $ticket_uuid, $exp );
        }

        if ( ! class_exists( 'IW_Google_Wallet_Tickets_Service' ) || ! method_exists( 'IW_Google_Wallet_Tickets_Service', 'build_public_link_signature' ) ) {
            return '';
        }

        if ( ! defined( 'IW_Google_Wallet_Tickets_Service::ROUTE_BASE' ) ) {
            return '';
        }

        $sig = IW_Google_Wallet_Tickets_Service::build_public_link_signature( $ticket_uuid, $exp );
        return add_query_arg(
            [
                'ticket_uuid' => rawurlencode( $ticket_uuid ),
                'exp'         => $exp,
                'sig'         => $sig,
            ],
            rest_url( 'iw/v1/' . IW_Google_Wallet_Tickets_Service::ROUTE_BASE . '/public-pass' )
        );
    }

    protected static function build_wallet_button_html( string $wallet, string $url ): string {
        $wallet = strtolower( trim( $wallet ) );
        $url = trim( $url );
        if ( $url === '' || ! in_array( $wallet, [ 'apple', 'google' ], true ) ) {
            return '';
        }

        $image_url = self::get_wallet_button_image_url( $wallet );
        if ( $image_url === '' ) {
            return '';
        }

        $alt = $wallet === 'apple' ? __( 'Add to Apple Wallet', 'iw-theme' ) : __( 'Add to Google Wallet', 'iw-theme' );


        return sprintf(
            '<a href="%s" style="display:block;text-decoration:none;margin:0 0 5px 0;"><img src="%s" width="110" height="34" alt="%s" style="display:block;width:110px;height:34px;border:0;padding:0;margin:0;"></a>',
            esc_url( $url ),
            esc_url( $image_url ),
            esc_attr( $alt ),

        );
    }

    protected static function build_download_pdf_button_html( string $url ): string {
        $url = trim( $url );
        if ( $url === '' ) {
            return '';
        }

        $image_url = self::get_download_pdf_button_image_url();
        if ( $image_url === '' ) {
            return '';
        }

        return sprintf(
            '<a href="%s" style="display:block;text-decoration:none;"><img src="%s" width="110" height="33" alt="%s" style="display:inline-block;width:110px;height:33px;border:0;padding:0;margin:0;"></a>',
            esc_url( $url ),
            esc_url( $image_url ),
            esc_attr__( 'Download PDF Ticket', 'iw-theme' )
        );
    }

    protected static function get_wallet_button_image_url( string $wallet ): string {
        $filename = $wallet === 'google' ? 'add-to-google-wallet.png' : 'add-to-apple-wallet.png';
        $upload = wp_upload_dir();
        $dir = trailingslashit( $upload['basedir'] ) . self::$email_images_dirname . '/wallet/';
        if ( file_exists( $dir . $filename ) ) {
            return trailingslashit( $upload['baseurl'] ) . self::$email_images_dirname . '/wallet/' . $filename;
        }

        return '';
    }

    protected static function get_download_pdf_button_image_url(): string {
        $filename = 'download-pdf-file.png';
        $upload = wp_upload_dir();
        $dir = trailingslashit( $upload['basedir'] ) . self::$email_images_dirname . '/wallet/';
        if ( file_exists( $dir . $filename ) ) {
            return trailingslashit( $upload['baseurl'] ) . self::$email_images_dirname . '/wallet/' . $filename;
        }

        return '';
    }

    protected static function get_email_hero_image_url( int $attachment_id ): string {
        if ( $attachment_id <= 0 || ! class_exists( 'Imagick' ) ) {
            return '';
        }

        $source_path = get_attached_file( $attachment_id );
        if ( ! is_string( $source_path ) || $source_path === '' || ! file_exists( $source_path ) ) {
            return '';
        }


        $upload = wp_upload_dir();
        if ( empty( $upload['basedir'] ) || empty( $upload['baseurl'] ) ) {
            return '';
        }

        $width              = 520;
        $height             = 210;
        $radius             = 16;
        $email_hero_version = 'v2';

        $cache_dir = trailingslashit( $upload['basedir'] ) . self::$email_images_dirname;
        if ( ! wp_mkdir_p( $cache_dir ) ) {
            return '';
        }

        $mtime = (int) filemtime( $source_path );
        $filename = sprintf(
            'ticket-email-hero-' . $email_hero_version . '-' . self::PDF_TEMPLATE_VERSION . '-%d-%d-%dx%d-%d.png',
            $attachment_id,
            $mtime,
            $width,
            $height,
            $radius
        );
        $cache_path = trailingslashit( $cache_dir ) . $filename;

        if ( file_exists( $cache_path ) ) {
            return trailingslashit( $upload['baseurl'] ) . self::$email_images_dirname . '/' . $filename;
        }

        try {
            $source = new Imagick( $source_path );
            if ( method_exists( $source, 'autoOrient' ) ) {
                $source->autoOrient();
            }
            $source->setIteratorIndex( 0 );
            $source->cropThumbnailImage( $width, $height );
            $source->setImagePage( 0, 0, 0, 0 );
            $source->setImageFormat( 'png' );

            $rounded_source = new Imagick();
            $rounded_source->newImage( $width, $height, new ImagickPixel( 'transparent' ), 'png' );
            $rounded_source->compositeImage( $source, Imagick::COMPOSITE_OVER, 0, 0 );
            $rounded_source->setImageAlphaChannel( Imagick::ALPHACHANNEL_ACTIVATE );

            $mask = new Imagick();
            $mask->newImage( $width, $height, new ImagickPixel( '#000000' ), 'png' );
            $mask_draw = new ImagickDraw();
            $mask_draw->setFillColor( new ImagickPixel( '#FFFFFF' ) );
            // Extend the rounded rectangle below the canvas so only the top
            // corners are rounded. The bottom edge must meet the event card
            // without the white "notches" produced by the previous overlay.
            $mask_draw->roundRectangle( 0, 0, $width - 1, $height + $radius, $radius, $radius );
            $mask->drawImage( $mask_draw );

            $rounded_source->compositeImage( $mask, Imagick::COMPOSITE_COPYOPACITY, 0, 0 );

            $hero = new Imagick();
            $hero->newImage( $width, $height, new ImagickPixel( '#FFFFFF' ), 'png' );
            $hero->compositeImage( $rounded_source, Imagick::COMPOSITE_OVER, 0, 0 );
            $hero->setImageFormat( 'png' );
            $hero->writeImage( $cache_path );

            $source->clear();
            $rounded_source->clear();
            $mask->clear();
            $hero->clear();
        } catch ( Throwable $e ) {

            return '';
        }

        return file_exists( $cache_path )
            ? trailingslashit( $upload['baseurl'] ) . self::$email_images_dirname . '/' . $filename
            : '';
    }

    protected static function build_email_icon_img_html( string $icon_name, string $color = '#31312F', string $alt = '' ): string {
        $sizes = [
            'calendar' => [ 16, 16 ],
            'location' => [ 13, 16 ],
        ];

        if ( ! isset( $sizes[ $icon_name ] ) ) {
            return '';
        }

        $url = self::get_email_icon_image_url( $icon_name, $color );
        if ( $url === '' ) {
            return '';
        }

        [ $width, $height ] = $sizes[ $icon_name ];

        return sprintf(
            '<img src="%s" width="%d" height="%d" alt="%s" style="display:block;width:%dpx;height:%dpx;border:0;margin:0;">',
            esc_url( $url ),
            $width,
            $height,
            esc_attr( $alt ),
            $width,
            $height
        );
    }

    protected static function get_email_icon_image_url( string $icon_name, string $color = '#31312F' ): string {
        if ( ! class_exists( 'Imagick' ) ) {
            return '';
        }

        $sizes = [
            'calendar' => [ 16, 16 ],
            'location' => [ 13, 16 ],
        ];

        if ( ! isset( $sizes[ $icon_name ] ) ) {
            return '';
        }

        $svg_path = self::get_theme_svg_icon_path( $icon_name . '.svg' );
        if ( $svg_path === '' ) {
            return '';
        }

        $upload = wp_upload_dir();
        if ( empty( $upload['basedir'] ) || empty( $upload['baseurl'] ) ) {
            return '';
        }

        $cache_dir = trailingslashit( $upload['basedir'] ) . self::$email_images_dirname;
        if ( ! wp_mkdir_p( $cache_dir ) ) {
            return '';
        }

        $color_slug = strtolower( preg_replace( '/[^a-f0-9]/i', '', $color ) );
        if ( $color_slug === '' ) {
            $color_slug = '31312f';
        }

        [ $width, $height ] = $sizes[ $icon_name ];
        $scale = 3;
        $render_width = $width * $scale;
        $render_height = $height * $scale;
        $filename = sprintf( 'email-icon-fill-v2-' . self::PDF_TEMPLATE_VERSION . '-%s-%s-%dx%d@%dx.png', $icon_name, $color_slug, $width, $height, $scale );
        $cache_path = trailingslashit( $cache_dir ) . $filename;

        if ( file_exists( $cache_path ) ) {
            return trailingslashit( $upload['baseurl'] ) . self::$email_images_dirname . '/' . $filename;
        }

        $svg_raw = (string) file_get_contents( $svg_path );
        if ( $svg_raw === '' ) {
            return '';
        }

        $svg_raw = self::colorize_email_svg( $svg_raw, $color );
        $svg_raw = self::resize_email_svg_canvas( $svg_raw, $render_width, $render_height );

        try {
            $image = new Imagick();
            $image->setBackgroundColor( new ImagickPixel( 'transparent' ) );
            $image->readImageBlob( $svg_raw );
            $image->setImageFormat( 'png32' );
            $image->setImagePage( 0, 0, 0, 0 );
            $image->writeImage( $cache_path );
            $image->clear();
        } catch ( Throwable $e ) {
            return '';
        }

        return file_exists( $cache_path )
            ? trailingslashit( $upload['baseurl'] ) . self::$email_images_dirname . '/' . $filename
            : '';
    }

    protected static function get_theme_svg_icon_path( string $filename ): string {
        $relative_path = 'assets/images/svg/' . ltrim( $filename, '/' );
        $search_paths = [];

        if ( function_exists( 'get_stylesheet_directory' ) ) {
            $search_paths[] = trailingslashit( get_stylesheet_directory() ) . $relative_path;
        }

        if ( function_exists( 'get_template_directory' ) ) {
            $search_paths[] = trailingslashit( get_template_directory() ) . $relative_path;
        }

        $search_paths = (array) apply_filters( 'iw_ticketing_theme_svg_icon_paths', $search_paths, $filename );
        foreach ( $search_paths as $path ) {
            if ( is_string( $path ) && file_exists( $path ) && is_readable( $path ) ) {
                return $path;
            }
        }

        return '';
    }

    protected static function colorize_email_svg( string $svg_raw, string $color ): string {
        if ( ! preg_match( '/^#[a-f0-9]{6}$/i', $color ) ) {
            $color = '#31312F';
        }

        $svg_raw = preg_replace( '/<svg\b(?![^>]*\bfill=)([^>]*)>/i', '<svg$1 fill="' . $color . '">', $svg_raw, 1 );
        $svg_raw = preg_replace( '/\sfill="(?!none)[^"]*"/i', ' fill="' . $color . '"', $svg_raw );
        $svg_raw = preg_replace( '/<(path|rect|circle|ellipse|polygon|polyline)\b(?![^>]*\bfill=)([^>]*)>/i', '<$1 fill="' . $color . '"$2>', $svg_raw );
        $svg_raw = preg_replace( '/\sstroke="[^"]*"/i', ' stroke="none"', $svg_raw );
        $svg_raw = preg_replace( '/<(path|rect|circle|ellipse|polygon|polyline)\b(?![^>]*\bstroke=)([^>]*)>/i', '<$1 stroke="none"$2>', $svg_raw );

        return $svg_raw;
    }

    protected static function resize_email_svg_canvas( string $svg_raw, int $width, int $height ): string {
        $svg_raw = preg_replace( '/\swidth="[^"]*"/i', ' width="' . $width . '"', $svg_raw, 1 );
        $svg_raw = preg_replace( '/\sheight="[^"]*"/i', ' height="' . $height . '"', $svg_raw, 1 );

        return $svg_raw;
    }

    protected static function get_order_tickets( int $order_id ): array {
        global $wpdb;

        $table = $wpdb->prefix . 'iw_tickets';
        $rows = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d ORDER BY slot_start ASC, id ASC", $order_id ),
            ARRAY_A
        );

        return is_array( $rows ) ? $rows : [];
    }

    protected static function build_email_structured_data_html( $order, array $grouped_tickets ): string {
        if ( ! $order || empty( $grouped_tickets ) || ! method_exists( $order, 'get_order_number' ) ) {
            return '';
        }

        $markup_blocks = [];

        foreach ( $grouped_tickets as $group_key => $group ) {
            $markup = self::build_event_reservation_structured_data( $order, (string) $group_key, $group );

            if ( empty( $markup ) ) {
                continue;
            }

            foreach ( self::normalize_schema_markup_items( $markup ) as $item_markup ) {
                $markup_blocks[] = self::build_event_reservation_microdata_html( $item_markup );
            }
        }

        return implode( "\n", array_filter( $markup_blocks ) );
    }

    protected static function build_event_reservation_structured_data( $order, string $group_key, array $group ): array {
        $post_id = (int) ( $group['post_id'] ?? 0 );

        if ( $post_id <= 0 || empty( $group['slot_start'] ) ) {
            return [];
        }

        $event_title = self::clean_schema_text( (string) get_the_title( $post_id ) );
        if ( $event_title === '' ) {
            $event_title = __( 'Event', 'iw-theme' );
        }

        $start_date = self::format_schema_datetime( (string) $group['slot_start'] );
        if ( $start_date === '' ) {
            return [];
        }

        $end_date = ! empty( $group['slot_end'] ) ? self::format_schema_datetime( (string) $group['slot_end'] ) : '';
        $building_data  = self::get_ticket_post_building_data( $post_id );
        $building_title = self::clean_schema_text( (string) ( $building_data['building_title'] ?? '' ) );
        $address_title  = self::clean_schema_text( (string) ( $building_data['address_title'] ?? '' ) );
        $reservation_url = is_numeric( $group_key ) && class_exists( 'IW_Ticketing_Endpoints' )
            ? IW_Ticketing_Endpoints::get_order_item_url( (int) $group_key )
            : ( method_exists( $order, 'get_view_order_url' ) ? (string) $order->get_view_order_url() : '' );

        $event = [
            '@type'     => 'Event',
            'name'      => $event_title,
            'startDate' => $start_date,
        ];

        if ( $end_date !== '' ) {
            $event['endDate'] = $end_date;
        }

        if ( $reservation_url !== '' ) {
            $event['url'] = $reservation_url;
        }

        $image_url = self::get_schema_image_url( $post_id );
        if ( $image_url !== '' ) {
            $event['image'] = $image_url;
        }

        if ( $building_title !== '' || $address_title !== '' ) {
            $postal_code = self::extract_postal_code( $address_title );
            $address_defaults = apply_filters(
                'iw_ticket_email_schema_address_defaults',
                [
                    'addressLocality' => 'Athens',
                    'addressRegion'   => 'Attica',
                    'postalCode'      => $postal_code,
                    'addressCountry'  => 'GR',
                ],
                $post_id,
                $building_data
            );

            $event['location'] = [
                '@type'   => 'Place',
                'name'    => $building_title !== '' ? $building_title : $address_title,
                'address' => array_merge(
                    [
                        '@type'         => 'PostalAddress',
                        'streetAddress' => $address_title,
                    ],
                    is_array( $address_defaults ) ? $address_defaults : []
                ),
            ];
        }

        $tickets = (array) ( $group['tickets'] ?? [] );
        $ticket_count = count( $tickets );
        $reservations = [];

        foreach ( $tickets as $index => $ticket ) {
            $ticket_uuid = trim( (string) ( $ticket['ticket_uuid'] ?? '' ) );
            $ticket_number = $ticket_uuid !== '' ? $ticket_uuid : sprintf( '%s-%d', $order->get_order_number(), $index + 1 );
            $attendee_name = self::clean_schema_text( (string) ( $ticket['attendee_name'] ?? '' ) );
            if ( $attendee_name === '' ) {
                $attendee_name = self::clean_schema_text( (string) $order->get_formatted_billing_full_name() );
            }
            if ( $attendee_name === '' ) {
                $attendee_name = (string) $order->get_billing_email();
            }

            $reservation = [
                '@context'          => 'http://schema.org',
                '@type'             => 'EventReservation',
                'reservationNumber' => sprintf( '%s-%s-%d', $order->get_order_number(), $group_key, $index + 1 ),
                'reservationStatus' => 'http://schema.org/Confirmed',
                'modifiedTime'      => self::format_schema_datetime( (string) current_time( 'mysql' ) ),
                'underName'         => [
                    '@type' => 'Person',
                    'name'  => $attendee_name,
                ],
                'reservationFor'    => $event,
                'ticketNumber'      => $ticket_number,
                'numSeats'          => '1',
            ];

            if ( (string) $order->get_billing_email() !== '' ) {
                $reservation['underName']['email'] = (string) $order->get_billing_email();
            }

            $barcode_hash = trim( (string) ( $ticket['barcode_hash'] ?? '' ) );
            if ( $barcode_hash !== '' ) {
                $reservation['ticketToken'] = 'qrCode:' . $barcode_hash;
            }

            $pdf_url = $ticket_uuid !== '' ? self::build_public_pdf_url( $ticket_uuid, time() + ( 365 * DAY_IN_SECONDS ) ) : '';
            if ( $pdf_url !== '' ) {
                $reservation['ticketDownloadUrl'] = $pdf_url;
                $reservation['ticketPrintUrl'] = $pdf_url;
            }

            if ( $reservation_url !== '' ) {
                $reservation['url'] = $reservation_url;
                $reservation['modifyReservationUrl'] = $reservation_url;
                $reservation['action'] = [
                    '@type' => 'ViewAction',
                    'name'  => __( 'Προβολή εισιτηρίων', 'iw-theme' ),
                    'url'   => $reservation_url,
                ];
            }

            $reservations[] = $reservation;
        }

        if ( empty( $reservations ) ) {
            return [
                '@context'          => 'http://schema.org',
                '@type'             => 'EventReservation',
                'reservationNumber' => sprintf( '%s-%s', $order->get_order_number(), $group_key ),
                'reservationStatus' => 'http://schema.org/Confirmed',
                'modifiedTime'      => self::format_schema_datetime( (string) current_time( 'mysql' ) ),
                'underName'         => [
                    '@type' => 'Person',
                    'name'  => self::clean_schema_text( (string) $order->get_formatted_billing_full_name() ) ?: (string) $order->get_billing_email(),
                ],
                'reservationFor'    => $event,
                'numSeats'          => (string) max( 1, $ticket_count ),
            ];
        }

        return count( $reservations ) === 1 ? $reservations[0] : $reservations;
    }

    protected static function normalize_schema_markup_items( array $markup ): array {
        if ( isset( $markup[0] ) && is_array( $markup[0] ) ) {
            return array_values( array_filter( $markup, 'is_array' ) );
        }

        return [ $markup ];
    }

    protected static function build_event_reservation_microdata_html( array $reservation ): string {
        if ( empty( $reservation['reservationFor'] ) || ! is_array( $reservation['reservationFor'] ) ) {
            return '';
        }

        $event = $reservation['reservationFor'];
        $location = isset( $event['location'] ) && is_array( $event['location'] ) ? $event['location'] : [];
        $address = isset( $location['address'] ) && is_array( $location['address'] ) ? $location['address'] : [];
        $under_name = isset( $reservation['underName'] ) && is_array( $reservation['underName'] ) ? $reservation['underName'] : [];

        $html = '<div itemscope itemtype="http://schema.org/EventReservation" style="display:none;">';
        $html .= self::schema_meta( 'reservationNumber', $reservation['reservationNumber'] ?? '' );
        $html .= self::schema_link( 'reservationStatus', $reservation['reservationStatus'] ?? 'http://schema.org/Confirmed' );
        $html .= self::schema_meta( 'modifiedTime', $reservation['modifiedTime'] ?? '' );
        $html .= self::schema_link( 'modifyReservationUrl', $reservation['modifyReservationUrl'] ?? '' );

        if ( ! empty( $under_name ) ) {
            $html .= '<div itemprop="underName" itemscope itemtype="http://schema.org/Person">';
            $html .= self::schema_meta( 'name', $under_name['name'] ?? '' );
            $html .= self::schema_meta( 'email', $under_name['email'] ?? '' );
            $html .= '</div>';
        }

        $html .= '<div itemprop="reservationFor" itemscope itemtype="http://schema.org/Event">';
        $html .= self::schema_meta( 'name', $event['name'] ?? '' );
        $html .= self::schema_meta( 'startDate', $event['startDate'] ?? '' );
        $html .= self::schema_meta( 'endDate', $event['endDate'] ?? '' );
        $html .= self::schema_link( 'url', $event['url'] ?? '' );
        $html .= self::schema_link( 'image', $event['image'] ?? '' );
        $html .= '<div itemprop="performer" itemscope itemtype="http://schema.org/Organization">';
        $html .= self::schema_meta( 'name', get_bloginfo( 'name' ) );
        $html .= self::schema_link( 'image', self::get_schema_logo_url() );
        $html .= '</div>';

        if ( ! empty( $location ) ) {
            $html .= '<div itemprop="location" itemscope itemtype="http://schema.org/Place">';
            $html .= self::schema_meta( 'name', $location['name'] ?? '' );

            if ( ! empty( $address ) ) {
                $html .= '<div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">';
                $html .= self::schema_meta( 'streetAddress', $address['streetAddress'] ?? '' );
                $html .= self::schema_meta( 'addressLocality', $address['addressLocality'] ?? '' );
                $html .= self::schema_meta( 'addressRegion', $address['addressRegion'] ?? '' );
                $html .= self::schema_meta( 'postalCode', $address['postalCode'] ?? '' );
                $html .= self::schema_meta( 'addressCountry', $address['addressCountry'] ?? '' );
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= self::schema_meta( 'ticketNumber', $reservation['ticketNumber'] ?? '' );
        $html .= self::schema_meta( 'ticketToken', $reservation['ticketToken'] ?? '' );
        $html .= self::schema_link( 'ticketDownloadUrl', $reservation['ticketDownloadUrl'] ?? '' );
        $html .= self::schema_link( 'ticketPrintUrl', $reservation['ticketPrintUrl'] ?? '' );
        $html .= self::schema_meta( 'numSeats', $reservation['numSeats'] ?? '' );
        $html .= '</div>';

        return $html;
    }

    protected static function schema_meta( string $property, $value ): string {
        $value = is_scalar( $value ) ? trim( (string) $value ) : '';

        if ( $value === '' ) {
            return '';
        }

        return '<meta itemprop="' . esc_attr( $property ) . '" content="' . esc_attr( self::clean_schema_text( $value ) ) . '"/>';
    }

    protected static function schema_link( string $property, $value ): string {
        $value = is_scalar( $value ) ? trim( (string) $value ) : '';

        if ( $value === '' ) {
            return '';
        }

        return '<link itemprop="' . esc_attr( $property ) . '" href="' . esc_url( $value ) . '"/>';
    }

    protected static function clean_schema_text( string $text ): string {
        return trim( html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
    }

    protected static function extract_postal_code( string $address ): string {
        $address = self::clean_schema_text( $address );

        if ( preg_match( '/\b\d{3}\s?\d{2}\b/u', $address, $matches ) ) {
            return str_replace( ' ', '', $matches[0] );
        }

        return '';
    }

    protected static function format_schema_datetime( string $value ): string {
        $value = trim( $value );

        if ( $value === '' ) {
            return '';
        }

        try {
            return ( new DateTimeImmutable( $value, wp_timezone() ) )->format( DATE_ATOM );
        } catch ( Throwable $e ) {
            return '';
        }
    }

    protected static function get_schema_image_url( int $post_id ): string {
        $image_id = get_post_thumbnail_id( $post_id );

        if ( ! $image_id ) {
            return '';
        }

        $image = wp_get_attachment_image_src( $image_id, 'full' );

        return ! empty( $image[0] ) ? (string) $image[0] : '';
    }

    protected static function get_schema_logo_url(): string {
        $upload = wp_upload_dir();
        if ( ! empty( $upload['baseurl'] ) ) {
            $known_logo_url = trailingslashit( $upload['baseurl'] ) . '2025/05/logo.png';
            if ( self::is_schema_raster_image_url( $known_logo_url ) ) {
                return $known_logo_url;
            }
        }

        $logo_id = (int) get_theme_mod( 'custom_logo' );

        if ( $logo_id > 0 ) {
            $image = wp_get_attachment_image_src( $logo_id, 'full' );

            if ( ! empty( $image[0] ) && self::is_schema_raster_image_url( (string) $image[0] ) ) {
                return (string) $image[0];
            }
        }

        $email_logo = (string) get_option( 'woocommerce_email_header_image' );

        return self::is_schema_raster_image_url( $email_logo ) ? $email_logo : '';
    }

    protected static function is_schema_raster_image_url( string $url ): bool {
        if ( $url === '' ) {
            return false;
        }

        $path = (string) wp_parse_url( $url, PHP_URL_PATH );

        return (bool) preg_match( '/\.(png|jpe?g|webp)$/i', $path );
    }

    protected static function get_pdf_logo_data_uri(): string {
        $logo_path = class_exists( 'IW_Ticketing' )
            ? IW_Ticketing::resolve_path( IW_Ticketing::get_option( 'pdf_logo_path', '' ), dirname( __FILE__, 2 ) )
            : '';
        $logo_path = (string) apply_filters( 'iw_ticketing_pdf_logo_path', $logo_path );

        if ( $logo_path !== '' && file_exists( $logo_path ) && is_readable( $logo_path ) ) {
            return self::file_to_data_uri( $logo_path );
        }

        return '';
    }

    protected static function file_to_data_uri( string $path ): string {
        if ( $path === '' || ! file_exists( $path ) || ! is_readable( $path ) ) {
            return '';
        }

        $ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
        $mime = [
            'svg'  => 'image/svg+xml',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
        ][ $ext ] ?? '';

        if ( $mime === '' ) {
            return '';
        }

        $raw = (string) file_get_contents( $path );
        return $raw !== '' ? 'data:' . $mime . ';base64,' . base64_encode( $raw ) : '';
    }

    protected static function get_brand_name(): string {
        $brand = class_exists( 'IW_Ticketing' )
            ? (string) IW_Ticketing::get_option( 'brand_name', get_bloginfo( 'name' ) )
            : (string) get_bloginfo( 'name' );

        return trim( $brand ) !== '' ? trim( $brand ) : __( 'Ticketing', 'iw-theme' );
    }

    protected static function get_pdf_visit_info_lines( string $side, array $ticket ): array {
        $site_url = home_url( '/' );
        $defaults = [
            'left' => [
                sprintf( __( 'Για τα ωράρια λειτουργίας και την καλύτερη οργάνωση της επίσκεψής σας επισκεφθείτε το %s.', 'iw-theme' ), wp_parse_url( $site_url, PHP_URL_HOST ) ?: $site_url ),
                __( 'Για την είσοδο των κατόχων μειωμένων εισιτηρίων ή εισιτηρίων ελεύθερης εισόδου είναι απαραίτητη η επίδειξη του αντίστοιχου δικαιολογητικού στην είσοδο.', 'iw-theme' ),
                __( 'Το παρόν εισιτήριο ισχύει μόνο για την έκθεση / εκδήλωση για την οποία έχει εκδοθεί.', 'iw-theme' ),
                __( 'Επιστροφές ή αλλαγές εισιτηρίων δεν γίνονται δεκτές. Εισιτήρια που έχουν χαθεί δεν μπορούν να εκδοθούν εκ νέου στα ταμεία.', 'iw-theme' ),
            ],
            'right' => [
                sprintf( __( 'For opening hours and in order to better plan your visit please visit %s.', 'iw-theme' ), wp_parse_url( $site_url, PHP_URL_HOST ) ?: $site_url ),
                __( 'Reduced or free admission ticket holders will be requested to present the relevant document at the entrance.', 'iw-theme' ),
                __( 'This ticket is valid only for the exhibition / event for which it has been issued.', 'iw-theme' ),
                __( 'Refunds or amendments to tickets are not possible. Lost tickets cannot be re-issued at the ticket office.', 'iw-theme' ),
            ],
        ];

        $key = $side === 'right' ? 'pdf_visit_info_right' : 'pdf_visit_info_left';
        $lines = class_exists( 'IW_Ticketing' ) ? IW_Ticketing::get_list_option( $key, $defaults[ $side ] ?? [] ) : ( $defaults[ $side ] ?? [] );
        if ( empty( $lines ) ) {
            $lines = $defaults[ $side ] ?? [];
        }

        return (array) apply_filters( 'iw_ticketing_pdf_visit_info_lines', $lines, $side, $ticket );
    }

    protected static function get_pdf_footer_text( string $side ): string {
        $brand = self::get_brand_name();
        $host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
        $default = trim( $brand . ( $host ? ' | ' . $host : '' ) );
        $key = $side === 'right' ? 'pdf_footer_right' : 'pdf_footer_left';
        $footer = class_exists( 'IW_Ticketing' ) ? (string) IW_Ticketing::get_option( $key, $default ) : $default;

        return (string) apply_filters( 'iw_ticketing_pdf_footer_text', $footer, $side );
    }

    protected static function ensure_ticket_pdf( array $ticket ): string {
        $ticket_uuid = trim( (string) ( $ticket['ticket_uuid'] ?? '' ) );
        if ( $ticket_uuid === '' ) {
            return '';
        }

        $upload = wp_upload_dir();
        if ( empty( $upload['basedir'] ) ) {
            return '';
        }

        $order_id = (int) ( $ticket['order_id'] ?? 0 );
        $base_dir = trailingslashit( $upload['basedir'] ) . 'tickets/pdfs/' . max( 1, $order_id ) . '/';

        if ( ! wp_mkdir_p( $base_dir ) ) {
            return '';
        }

        $pdf_path = $base_dir . sanitize_file_name( $ticket_uuid ) . '-' . self::PDF_TEMPLATE_VERSION . '.pdf';
        if ( file_exists( $pdf_path ) && filesize( $pdf_path ) > 0 ) {
            return $pdf_path;
        }

        try {
            self::render_ticket_pdf( $ticket, $pdf_path );
        } catch ( Throwable $e ) {
            return '';
        }

        return file_exists( $pdf_path ) ? $pdf_path : '';
    }

    protected static function render_ticket_pdf( array $ticket, string $pdf_path ): void {
        if ( ! class_exists( '\chillerlan\QRCode\QRCode' ) || ! class_exists( '\chillerlan\QRCode\QROptions' ) || ! class_exists( '\Dompdf\Dompdf' ) ) {
            throw new RuntimeException( 'Missing PDF/QR/HTML dependencies.' );
        }

        $html = self::build_ticket_template_html( $ticket );

        $options = new DompdfOptions();
        $options->set( 'isRemoteEnabled', true );
        $options->set( 'isHtml5ParserEnabled', true );
        $options->set( 'defaultFont', 'DejaVu Sans' );

        $dompdf = new Dompdf( $options );
        $dompdf->loadHtml( $html, 'UTF-8' );
        $dompdf->setPaper( 'A4', 'portrait' );
        $dompdf->render();

        $output = $dompdf->output();
        file_put_contents( $pdf_path, $output );
    }

    protected static function build_ticket_template_html( array $ticket ): string {
        $ticket_uuid  = (string) ( $ticket['ticket_uuid'] ?? '' );
        $post_id      = (int) ( $ticket['post_id'] ?? 0 );

        $event_title  = $post_id > 0 ? (string) get_the_title( $post_id ) : __( 'Event', 'iw-theme' );
        $attendee     = trim( (string) ( $ticket['attendee_name'] ?? '' ) );
        if ( $attendee === '' ) {
            $attendee = __( 'Participant', 'iw-theme' );
        }
        $price_category = (string) ( $ticket['price_category'] ?? '' );
        $price_parts = explode( ':', $price_category );
        $price_label = isset( $price_parts[1] ) && $price_parts[1] !== '' ? $price_parts[1] : $price_category;
        $unit_price = isset( $ticket['unit_price'] ) ? (float) $ticket['unit_price'] : 0.0;
        $price_human = function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $unit_price ) ) : number_format_i18n( $unit_price, 2 );

        $slot_label = self::format_slot_label( (string) ( $ticket['slot_start'] ?? '' ) );
        $qr_data_uri = self::build_qr_data_uri_for_email( $ticket );
        if ( $qr_data_uri === '' ) {
            throw new RuntimeException( 'Unable to generate QR image for PDF.' );
        }
        $logo_data_uri = self::get_pdf_logo_data_uri();
        $brand_name = self::get_brand_name();

        $kicker = __( 'TICKET', 'iw-theme' );
        $building_data = self::get_ticket_post_building_data( $post_id );
        $building_title = $building_data['building_title'];
        $address_title = $building_data['address_title'];

        $slot_for_table = (string) ( $ticket['slot_start'] ?? '' );
        $slot_for_table_label = $slot_for_table;
        if ( $slot_for_table !== '' ) {
            try {
                $slot_for_table_label = ( new DateTimeImmutable( $slot_for_table, wp_timezone() ) )->format( 'd/m/Y' );
            } catch ( Throwable $e ) {
                $slot_for_table_label = $slot_for_table;
            }
        }

        $labels = [
            'name'      => __( 'Name', 'iw-theme' ),
            'category'  => __( 'Category', 'iw-theme' ),
            'price'     => __( 'Price', 'iw-theme' ),
            'datetime'  => __( 'Date & Time', 'iw-theme' ),
            'ticket_id' => __( 'Ticket ID', 'iw-theme' ),
            'order_id'  => __( 'Order', 'iw-theme' ),
        ];
        $category_label = $price_label !== '' ? $price_label : '-';
        $order_id = (int) ( $ticket['order_id'] ?? 0 );
        $info_rows = [
            [ 'label' => 'ΕΚΘΕΣΗ / ΕΚΔΗΛΩΣΗ | EXHIBITION / EVENT:', 'value' => $event_title ],
            [ 'label' => 'ΑΡΙΘΜΟΣ ΕΙΣΙΤΗΡΙΟΥ | TICKET NUMBER:', 'value' => $ticket_uuid ],
            [ 'label' => 'ΚΤΗΡΙΟ | BUILDING:', 'value' => $building_title !== '' ? $building_title : '-', 'is_html' => true ],
            [ 'label' => 'ΔΙΕΥΘΥΝΣΗ | ADDRESS:', 'value' => $address_title !== '' ? $address_title : '-' ],
            [ 'label' => 'ΟΝΟΜΑΤΕΠΩΝΥΜΟ | FULL NAME:', 'value' => $attendee ],
            [ 'label' => 'ΗΜΕΡΟΜΗΝΙΑ ΕΠΙΣΚΕΨΗΣ | DATE OF VISIT:', 'value' => $slot_for_table_label ],
            [ 'label' => 'ΤΙΜΗ ΕΙΣΙΤΗΡΙΟΥ | TICKET PRICE*:', 'value' => $price_human ],
            [ 'label' => 'ΚΑΤΗΓΟΡΙΑ ΕΙΣΙΤΗΡΙΟΥ | TICKET CATEGORY:', 'value' => $category_label ],
        ];

        $left_info_lines = self::get_pdf_visit_info_lines( 'left', $ticket );
        $right_info_lines = self::get_pdf_visit_info_lines( 'right', $ticket );
        $pdf_footer_left = self::get_pdf_footer_text( 'left' );
        $pdf_footer_right = self::get_pdf_footer_text( 'right' );
        $color_swatches = [ '#56617d', '#6d3b78', '#43735f', '#ef6f52', '#8bb5a9', '#7d9fc4', '#8a3700', '#afcc00' ];

        $template_path = plugin_dir_path( __FILE__ ) . 'views/ticket-pdf-template.php';
        if ( ! file_exists( $template_path ) ) {
            throw new RuntimeException( 'PDF template file not found.' );
        }

        ob_start();
        include $template_path;
        return (string) ob_get_clean();
    }

    protected static function build_qr_data_uri_for_email( array $ticket ): string {
        $barcode_hash = (string) ( $ticket['barcode_hash'] ?? '' );
        $ticket_uuid  = (string) ( $ticket['ticket_uuid'] ?? '' );
        $value = $barcode_hash !== '' ? $barcode_hash : $ticket_uuid;
        if ( $value === '' ) {
            return '';
        }

        if ( ! class_exists( '\chillerlan\QRCode\QRCode' ) || ! class_exists( '\chillerlan\QRCode\QROptions' ) ) {
            return '';
        }

        $branded_qr = self::build_branded_qr_data_uri( $value );
        if ( $branded_qr !== '' ) {
            return $branded_qr;
        }

        try {
            $options = new QROptions(
                [
                    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                    'eccLevel'   => QRCode::ECC_M,
                    'scale'      => 6,
                    'imageBase64'=> true,
                    'addQuietzone' => false,
                    'quietzoneSize' => 0,
                ]
            );
            return (string) ( new QRCode( $options ) )->render( $value );
        } catch ( Throwable $e ) {
            return '';
        }
    }

    protected static function build_branded_qr_data_uri( string $value ): string {
        if ( ! extension_loaded( 'gd' ) || ! class_exists( '\chillerlan\QRCode\QRCode' ) || ! class_exists( '\chillerlan\QRCode\QROptions' ) ) {
            return '';
        }

        try {
            $options = new QROptions(
                [
                    'eccLevel'      => QRCode::ECC_H,
                    'addQuietzone'  => true,
                    'quietzoneSize' => 4,
                ]
            );

            $matrix = ( new QRCode( $options ) )->getMatrix( $value );
            $matrix_size = $matrix->size();
            $logo_space = max( 7, (int) floor( $matrix_size * 0.21 ) );
            $matrix->setLogoSpace( $logo_space, $logo_space );

            $module_size = 8;
            $image_size = $matrix_size * $module_size;
            $image = imagecreatetruecolor( $image_size, $image_size );
            if ( ! $image ) {
                return '';
            }

            imagealphablending( $image, true );
            imagesavealpha( $image, true );

            $white = imagecolorallocate( $image, 255, 255, 255 );
            $dark = imagecolorallocate( $image, 23, 50, 118 );
            imagefill( $image, 0, 0, $white );

            $matrix_data = $matrix->matrix( true );
            foreach ( $matrix_data as $y => $row ) {
                foreach ( $row as $x => $is_dark ) {
                    if ( ! $is_dark ) {
                        continue;
                    }

                    $left = $x * $module_size;
                    $top = $y * $module_size;
                    self::image_filled_rounded_rectangle(
                        $image,
                        $left,
                        $top,
                        $left + $module_size - 1,
                        $top + $module_size - 1,
                        0,
                        $dark
                    );
                }
            }

            $logo_diameter = (int) round( $image_size * 0.24 );
            $logo_center = (int) floor( $image_size / 2 );
            imagefilledellipse( $image, $logo_center, $logo_center, $logo_diameter, $logo_diameter, $white );

            self::place_qr_logo_image( $image, (int) round( $logo_diameter * 0.86 ) );

            ob_start();
            imagepng( $image );
            $png = (string) ob_get_clean();
            imagedestroy( $image );

            return $png !== '' ? 'data:image/png;base64,' . base64_encode( $png ) : '';
        } catch ( Throwable $e ) {
            return '';
        }
    }

    protected static function image_filled_rounded_rectangle( $image, int $x1, int $y1, int $x2, int $y2, int $radius, int $color ): void {
        $radius = max( 0, min( $radius, (int) floor( min( $x2 - $x1, $y2 - $y1 ) / 2 ) ) );

        if ( $radius <= 0 ) {
            imagefilledrectangle( $image, $x1, $y1, $x2, $y2, $color );
            return;
        }

        imagefilledrectangle( $image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color );
        imagefilledrectangle( $image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color );
        imagefilledellipse( $image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color );
        imagefilledellipse( $image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color );
        imagefilledellipse( $image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color );
        imagefilledellipse( $image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color );
    }

    protected static function place_qr_logo_image( $image, int $logo_size ): void {
        $logo_path = self::get_qr_logo_path();
        if ( $logo_path === '' || ! class_exists( '\Imagick' ) ) {
            return;
        }

        try {
            $imagick = new Imagick();
            $imagick->setBackgroundColor( new ImagickPixel( 'transparent' ) );
            $imagick->readImageBlob( (string) file_get_contents( $logo_path ) );
            $imagick->setImageFormat( 'png32' );
            $imagick->resizeImage( $logo_size, $logo_size, Imagick::FILTER_LANCZOS, 1, true );

            $logo = imagecreatefromstring( $imagick->getImagesBlob() );
            $imagick->clear();
            $imagick->destroy();

            if ( ! $logo ) {
                return;
            }

            $logo_width = imagesx( $logo );
            $logo_height = imagesy( $logo );
            $left = (int) floor( ( imagesx( $image ) - $logo_width ) / 2 );
            $top = (int) floor( ( imagesy( $image ) - $logo_height ) / 2 );

            imagealphablending( $image, true );
            imagecopy( $image, $logo, $left, $top, 0, 0, $logo_width, $logo_height );
            imagedestroy( $logo );
        } catch ( Throwable $e ) {
            return;
        }
    }

    protected static function get_qr_logo_path(): string {
        $configured_path = class_exists( 'IW_Ticketing' )
            ? IW_Ticketing::resolve_path( IW_Ticketing::get_option( 'pdf_qr_logo_path', '' ), dirname( __FILE__, 2 ) )
            : '';
        $configured_path = (string) apply_filters( 'iw_ticketing_pdf_qr_logo_path', $configured_path );
        if ( $configured_path !== '' && file_exists( $configured_path ) && is_readable( $configured_path ) ) {
            return $configured_path;
        }

        $bundled_path = dirname( __FILE__, 2 ) . '/assets/images/mesolongi-qr-logo.svg';
        return file_exists( $bundled_path ) && is_readable( $bundled_path ) ? $bundled_path : '';
    }

    protected static function build_email_calendar_links_html( int $post_id, string $slot_start, string $slot_end = '' ): string {
        if ( $post_id <= 0 || trim( $slot_start ) === '' || ! class_exists( 'IW_Ticketing_ICS' ) || ! method_exists( 'IW_Ticketing_ICS', 'get_calendar_links' ) ) {
            return '';
        }

        $links = IW_Ticketing_ICS::get_calendar_links( $post_id, $slot_start, $slot_end !== '' ? $slot_end : null );
        if ( empty( $links ) || ! is_array( $links ) ) {
            return '';
        }

        $items = [
            'google'      => 'Google',
            'ics'         => 'Outlook',
            'outlook_web' => 'Outlook.com',
            'yahoo'       => 'Yahoo',
        ];

        $anchors = [];
        foreach ( $items as $key => $label ) {
            if ( empty( $links[ $key ] ) ) {
                continue;
            }

            $anchors[] = sprintf(
                '<a href="%s" target="_blank" style="color:#173276;text-decoration:underline !important;font-weight:normal !important;">%s</a>',
                esc_url( (string) $links[ $key ] ),
                esc_html( $label )
            );
        }

        if ( empty( $anchors ) ) {
            return '';
        }

        return '<div style="font-size:13px;line-height:1.3;color:#173276;margin:16px 0 0 0;padding:0;">' . esc_html__( 'Προσθήκη στο ημερολόγιο:', 'iw-theme' ) . '<br/>'
                . implode( '<span style="display:inline-block;color:#8185BE;">&nbsp;&nbsp;|&nbsp;&nbsp;</span>', $anchors )
            . '</div>';
    }

    protected static function format_slot_label( string $slot_start ): string {
        $slot_start = trim( $slot_start );
        if ( $slot_start === '' ) {
            return '';
        }

        try {
            $dt = new DateTimeImmutable( $slot_start, wp_timezone() );

            return wp_date( 'd/m/Y', $dt->getTimestamp(), wp_timezone() ) . ' &middot; ' .
                wp_date( 'H:i', $dt->getTimestamp(), wp_timezone() );
        } catch ( Throwable $e ) {
            return $slot_start;
        }
    }

    protected static function format_order_item_slot_label( string $day, string $time ): string {
        $day = trim( $day );
        $time = trim( $time );
        if ( $day === '' && $time === '' ) {
            return '';
        }

        if ( $day === '' ) {
            return $time;
        }

        try {
            $dt = new DateTimeImmutable( trim( $day . ' ' . ( $time !== '' ? $time : '00:00' ) ), wp_timezone() );
            return wp_date( 'd/m/Y H:i', $dt->getTimestamp(), wp_timezone() );
        } catch ( Throwable $e ) {
            return trim( $day . ( $time !== '' ? ' ' . $time : '' ) );
        }
    }

    protected static function get_ticket_post_building_data( int $post_id ): array {
        $building_id = 0;
        $building_title = '';
        $address_title = '';
        $address_url = '';

        if ( $post_id > 0 && function_exists( 'get_field' ) ) {
            $building = get_field( 'building_location', $post_id );
            if ( is_object( $building ) && ! empty( $building->ID ) ) {
                $building_id = (int) $building->ID;
            } elseif ( is_numeric( $building ) ) {
                $building_id = (int) $building;
            } elseif ( is_array( $building ) && ! empty( $building['ID'] ) ) {
                $building_id = (int) $building['ID'];
            }

            if ( $building_id > 0 ) {
                $building_title = (string) get_the_title( $building_id );
                $address = get_field( 'address', $building_id );
                if ( is_array( $address ) ) {
                    $address_title = (string) ( $address['title'] ?? '' );
                    $address_url   = (string) ( $address['url'] ?? '' );
                } elseif ( is_string( $address ) ) {
                    $address_title = $address;
                }
            }
        }

        return [
            'building_id'    => $building_id,
            'building_title' => $building_title,
            'address_title'  => $address_title,
            'address_url'    => $address_url,
        ];;
    }

    protected static function safe_pdf_text( string $text ): string {
        $clean = wp_strip_all_tags( $text );
        return html_entity_decode( $clean, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    }

    protected static function build_post_type_tag_html( int $post_id ): string {
        if ( $post_id <= 0 ) {
            return '';
        }

        $post_type = get_post_type( $post_id );
        if ( ! $post_type ) {
            return '';
        }

        $post_object = get_post_type_object( $post_type );
        if ( ! $post_object ) {
            return '';
        }

        $title = $post_object->labels->singular_name ?? '';

        if ( $post_type === 'building' ) {
            $exhibition_object = get_post_type_object( 'exhibition' );
            $exhibition_title = $exhibition_object->labels->singular_name ?? __( 'Έκθεση', 'iw-theme' );
            $title = sprintf(
                '%s %s',
                __( 'Μόνιμη', 'iw-theme' ),
                $exhibition_title
            );
        } elseif ( $post_type === 'exhibition' && function_exists( 'get_field' ) ) {
            $is_permanent = (bool) get_field( 'is_permanent', $post_id );

            $title = sprintf(
                '%s %s',
                $is_permanent
                    ? __( 'Μόνιμη', 'iw-theme' )
                    : __( 'Περιοδική', 'iw-theme' ),
                $title
            );
        }

        return sprintf(
            '<div style="margin:0 0 16px 0;"><span style="display:inline-block;background:#EBE6D6;color:#173276;padding:5px 9px;border-radius:6px;font-size:10px;line-height:1.3;font-weight:700;letter-spacing:0.05em;">%s</span></div>',
            esc_html( IW_Ticketing::remove_accents( $title ) )
        );
    }

    protected static function get_email_post_type_tag_palette( int $post_id, string $post_type ): array {
        $color_key = '';

        if ( $post_type === 'building' ) {
            $color_key = 'collection-permExhibitions';
        } elseif ( $post_type === 'exhibition' && function_exists( 'get_field' ) ) {
            $color_key = get_field( 'is_permanent', $post_id )
                ? 'collection-permExhibitions'
                : 'arts-tempExhibitions';
        } elseif ( class_exists( 'IW_Theme' ) && method_exists( 'IW_Theme', 'color' ) ) {
            $color_key = (string) IW_Theme::color( $post_type );
        }

        if ( $color_key === '' ) {
            $color_key = 'museum';
        }

        $color_number = '2';
        $background = self::get_email_theme_color( $color_key . $color_number, '#31312F' );
        $text_key = ( $color_key === 'experience' || $color_key === 'white' )
            ? 'white'
            : ( $color_key === 'arts-tempExhibitions' ? 'dark' : 'light' );
        $color = self::get_email_theme_color( $text_key, $text_key === 'white' ? '#FFFFFF' : '#31312F' );

        return [
            'background' => $background,
            'color'      => $color,
        ];
    }

    protected static function get_email_theme_color( string $key, string $fallback ): string {
        static $colors = null;

        if ( $colors === null ) {
            $colors = [];

            if ( function_exists( 'get_stylesheet_directory' ) ) {
                $config_path = trailingslashit( get_stylesheet_directory() ) . 'assets/tailwind/theme.config.json';

                if ( file_exists( $config_path ) ) {
                    $config = json_decode( (string) file_get_contents( $config_path ), true );

                    if ( is_array( $config ) && isset( $config['colors'] ) && is_array( $config['colors'] ) ) {
                        $colors = $config['colors'];
                    }
                }
            }
        }

        $color = isset( $colors[ $key ] ) ? (string) $colors[ $key ] : '';

        return preg_match( '/^#[0-9a-fA-F]{6}$/', $color ) ? $color : $fallback;
    }
}
