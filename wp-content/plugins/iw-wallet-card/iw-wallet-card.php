<?php
/**
 * Plugin Name:     Iw Wallet Card
 * Description:     Wallet card tools for memberships and tickets.
 * Author:          ahatzif
 * Author URI:      https://interweaveagency.com/
 * Version:         0.1.0
 *
 * @package         Iw_Wallet_Card
 */


$iw_wallet_card_autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $iw_wallet_card_autoload ) ) {
    require_once $iw_wallet_card_autoload;
}
require_once __DIR__ . '/register-member-card-cpt.php';
require_once __DIR__ . '/iw-wallet-common.php';
require_once __DIR__ . '/iw-card-styles.php';
require_once __DIR__ . '/apple-wallet/apple-wallet.php';
require_once __DIR__ . '/google-wallet/google-wallet.php';
require_once __DIR__ . '/iw-wallet-cli.php';
require_once __DIR__ . '/iw-wallet-versioning.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
class IW_Member_Card {

    public static  $verificationRestRouteEndpoint = '/verify-member-card';
    public static  $searchRestRouteEndpoint = '/search-member-card';
    public static  $applePassEndpoint = "/apple-wallet-card";
    public const MEMBER_QR_PREFIX = 'm:';
    const MEMBERS_ANALYTICS_SLUG = 'iw-members-analytics';

    public static function option_defaults(): array {
        $site_name = get_bloginfo( 'name' ) ?: __( 'Membership Program', 'iw-theme' );

        return [
            'org_name'                             => $site_name,
            'program_description'                  => sprintf( __( '%s Membership', 'iw-theme' ), $site_name ),
            'tickets_program_description'          => sprintf( __( '%s Ticket', 'iw-theme' ), $site_name ),
            'apple_pass_type_identifier'           => '',
            'apple_tickets_pass_type_identifier'   => '',
            'apple_team_identifier'                => '',
            'apple_key_id'                         => '',
            'apple_private_key_path'               => '',
            'apple_certificate_path'               => '',
            'apple_certificate_password'           => '',
            'apple_tickets_certificate_path'        => '',
            'apple_tickets_certificate_password'    => '',
            'apple_wwdr_path'                      => '',
            'apple_auth_salt'                      => wp_salt( 'auth' ),
            'google_service_account_path'          => '',
            'google_issuer_id'                     => '',
            'google_class_suffix'                  => 'membership',
            'google_object_prefix'                 => 'member_',
            'google_application_name'              => sprintf( __( '%s Wallet', 'iw-theme' ), $site_name ),
            'google_jwt_origins'                   => home_url(),
        ];
    }

    public static function get_option( string $key, $default = null ) {
        $defaults = self::option_defaults();
        $use_registered_default = null === $default;
        if ( null === $default ) {
            $default = $defaults[ $key ] ?? '';
        }

        $constant = 'IW_WALLET_CARD_' . strtoupper( $key );
        if ( defined( $constant ) ) {
            $value = constant( $constant );
        } else {
            $value = get_option( 'iw_wallet_card_' . $key, $default );
        }

        if ( '' === $value && $use_registered_default && array_key_exists( $key, $defaults ) ) {
            $value = $defaults[ $key ];
        }

        return apply_filters( 'iw_wallet_card_' . $key, $value );
    }

    public static function resolve_path( $path, string $base_dir = '' ): string {
        $path = trim( (string) $path );
        if ( '' === $path ) {
            return '';
        }

        if ( '/' === $path[0] || preg_match( '/^[A-Za-z]:[\/\\\\]/', $path ) ) {
            return $path;
        }

        $base_dir = $base_dir ?: __DIR__;
        return trailingslashit( $base_dir ) . ltrim( $path, '/\\' );
    }

    public static function register_settings(): void {
        foreach ( array_keys( self::option_defaults() ) as $key ) {
            register_setting(
                'iw_wallet_card',
                'iw_wallet_card_' . $key,
                [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => self::option_defaults()[ $key ],
                ]
            );
        }
    }

    public static function orgName() {
        return self::get_option( 'org_name' );
    }
    public static function programDescription(){
        return self::get_option( 'program_description' );
    }

    public static function ticketsProgramDescription(){
        return self::get_option( 'tickets_program_description' );
    }



    public static function init(){
        add_action( 'woocommerce_subscription_status_active', [ __CLASS__, 'on_subscription_activated' ] );
        add_action( 'woocommerce_subscription_status_updated', [ __CLASS__, 'on_subscription_changed' ], 10, 3 );
        add_action( 'show_user_profile', [ 'IW_Member_Card', 'admin_show_member_card' ] );
        add_action( 'edit_user_profile', [ 'IW_Member_Card', 'admin_show_member_card' ] );
        add_action( 'personal_options_update', [ __CLASS__, 'save_user_photo' ] );
        add_action( 'edit_user_profile_update', [ __CLASS__, 'save_user_photo' ] );
        add_action( 'wp_ajax_iw_member_card_update_photo', [ __CLASS__, 'ajax_update_member_photo' ] );
        add_action( 'rest_api_init', [ 'IW_Member_Card', 'register_rest_route' ] );
        add_action( 'admin_menu', [ __CLASS__, 'register_members_admin_menu' ], 9 );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action('user_edit_form_tag', function () { echo ' enctype="multipart/form-data"';});
        add_action('save_post', [__CLASS__, 'flag_users_on_member_card_save'], 10, 3);

        add_action( 'user_register', [ __CLASS__, 'set_default_member_card_on_user_create' ], 10, 1 );


        /*add_action( 'woocommerce_subscription_status_on-hold',    [ 'IW_Member_Card', 'on_subscription_inactive' ], 10, 1 );
        add_action( 'woocommerce_subscription_status_cancelled',  [ 'IW_Member_Card', 'on_subscription_inactive' ], 10, 1 );
        add_action( 'woocommerce_subscription_status_expired',    [ 'IW_Member_Card', 'on_subscription_inactive' ], 10, 1 );
        add_action( 'woocommerce_subscription_status_pending-cancel', [ 'IW_Member_Card', 'on_subscription_inactive' ], 10, 1 );*/
    }


    public static function on_subscription_activated( $subscription, $force = false ){
        $user_id = $subscription->get_user_id();
        if( $force || empty( get_user_meta( $user_id, 'member_card_id', true ) ) ){
            $card_number = self::generate_member_number();
            update_user_meta( $user_id, 'member_card_id', $card_number );
            update_user_meta( $user_id, 'member_qr_svg', self::generate_qr( self::build_member_qr_payload( $card_number ) ) );
        }
    }

    public static function on_subscription_inactive( $subscription ){
        $user_id = $subscription->get_user_id();
        $card_number = get_user_meta( $user_id, 'member_card_id', true );
        if( ! $card_number ) return;
        update_user_meta($user_id, 'apple_wallet_pass_version', time());
        update_user_meta($user_id, 'apple_wallet_pass_voided', true);
        IW_Apple_Wallet_Service::push_update( $user_id  );
    }

    public static function on_subscription_changed( $subscription, $new_status, $old_status ){
        $user_id = $subscription->get_user_id();
        update_user_meta($user_id, 'apple_wallet_pass_voided', $new_status !== 'active' );
        update_user_meta($user_id, 'apple_wallet_pass_version', time());
        IW_Apple_Wallet_Service::push_update( $user_id );
    }

    public static function generate_member_number(){
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        return $uuid;
    }

    public static function build_member_qr_payload( $card_number ): string {
        $card_number = trim( (string) $card_number );
        return $card_number === '' ? '' : self::MEMBER_QR_PREFIX . $card_number;
    }

    public static function normalize_member_card_id( $value ): string {
        $value = trim( (string) $value );
        if ( $value === '' ) {
            return '';
        }

        if ( stripos( $value, self::MEMBER_QR_PREFIX ) === 0 ) {
            return trim( substr( $value, strlen( self::MEMBER_QR_PREFIX ) ) );
        }

        if ( str_starts_with( $value, '{' ) ) {
            $decoded = json_decode( $value, true );
            if ( is_array( $decoded ) && ! empty( $decoded['member_card_id'] ) ) {
                return trim( (string) $decoded['member_card_id'] );
            }
        }

        return $value;
    }

    public static function generate_qr( $string ){

        if ( ! class_exists( QRCode::class ) || ! class_exists( QROptions::class ) ) {
            error_log( 'IW Wallet Card QR generation requires composer install.' );
            return '';
        }

        if( is_array($string) ){
            $string = json_encode($string, JSON_UNESCAPED_SLASHES);
        }
        $options = new QROptions([
            'outputType'     => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel'       => QRCode::ECC_L,
            'scale'          => 1,
            'svgViewBox'     => true,
            'svgWidth'       => 24,
            'quietzoneSize'  => 0,
            'markupDark'     => 'currentColor',
            'markupLight'    => 'none'
        ]);

        $svg = (new QRCode($options))->render($string);
        if( str_starts_with($svg, 'data:image') ){
            $parts = explode(',', $svg, 2);
            if(isset($parts[1])){
                $decoded = base64_decode($parts[1]);
                if($decoded){
                    $svg = $decoded;
                }
            }
        }

        return $svg; // raw inline SVG
    }
    public static function admin_show_member_card( $user ){
        $card_id = get_user_meta( $user->ID, 'member_card_id', true );
        $qr      = get_user_meta( $user->ID, 'member_qr_svg', true );

        if( empty( $card_id ) && empty( $qr ) ){
            return;
        }
        ?>
        <h2>Στοιχεία Κάρτας Μέλους</h2>
        <table class="form-table">
            <tr>
                <th><label>Upload Photo</label></th>
                <td>
                    <input type="file" name="member_photo_upload" accept="image/*">
                </td>
            </tr>
            <?php $photo = get_user_meta( $user->ID, 'member_photo_url', true );
            if( ! empty( $photo ) ): ?>
                <tr>
                    <th><label>Photo</label></th>
                    <td>
                        <img src="<?php echo esc_url( $photo ); ?>" style="width:120px; height:120px; object-fit:cover; border-radius:8px; border:1px solid #ccc;">
                        <p>
                            <label>
                                <input type="checkbox" name="remove_member_photo" value="1">
                                Remove photo
                            </label>
                        </p>
                    </td>
                </tr>
            <?php endif; ?>
            <?php if( $card_id ): ?>
                <tr>
                    <th><label>Member Card ID</label></th>
                    <td>
                        <input type="text" value="<?php echo esc_attr( $card_id ); ?>" class="regular-text" readonly>

                    </td>
                </tr>
            <?php endif; ?>

            <?php
            $card = get_field( 'member_card', 'user_' . $user->ID );
            $card = $card instanceof WP_Post ? $card : get_post( $card );

            if( $card instanceof WP_Post ){ ?>

                <tr>
                    <th><label>Selected Card</label></th>
                    <td>
                        <a target="_blank" href="<?php echo esc_url( get_edit_post_link( $card->ID ) ); ?>"><?php echo esc_html( get_the_title( $card ) ); ?></a>
                    </td>
                </tr>
            <?php } ?>

            <?php if( $qr ): ?>
                <tr>
                    <th><label>QR Code</label></th>
                    <td>
                        <div style="max-width:200px; background:#fff; padding:10px;">
                            <?php echo $qr; ?>
                            <br><br>
                            <a href="data:image/svg+xml;utf8,<?php echo rawurlencode($qr); ?>"
                               download="member-card-<?php echo esc_attr( $user->ID ); ?>.svg"
                               class="button button-primary">
                                Download SVG
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>

            <tr>
                <th><label>Card Snapshot Hash</label></th>
                <td>
                    <input type="text" readonly value="<?php echo esc_attr( get_user_meta( $user->ID, '_member_card_snapshot', true ) ); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th><label>Apple Pass Version</label></th>
                <td>
                    <input type="text" readonly value="<?php echo esc_attr( get_user_meta( $user->ID, 'apple_wallet_pass_version', true ) ); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th><label>Apple Wallet Device Tokens</label></th>
                <td>
                    <pre><?php
                        $tokens = get_user_meta( $user->ID, 'apple_wallet_device_tokens', true );
                        echo esc_textarea( print_r( $tokens, true ) );
                    ?></pre>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function register_rest_route()
    {
        register_rest_route('iw/v1', self::$verificationRestRouteEndpoint , [ 'methods'  => 'GET', 'callback' =>  [ 'IW_Member_Card', 'iw_verify_member_card' ], 'permission_callback' => [ 'IW_Member_Card', 'can_verify_member_card' ] ]);
        register_rest_route('iw/v1', self::$searchRestRouteEndpoint , [ 'methods'  => 'GET', 'callback' =>  [ 'IW_Member_Card', 'iw_search_member_card' ], 'permission_callback' => [ 'IW_Member_Card', 'can_verify_member_card' ] ]);
    }

    public static function can_verify_member_card(): bool {
        return function_exists( 'iw_scanner_user_can_scan' )
            ? iw_scanner_user_can_scan()
            : ( is_user_logged_in() && current_user_can( 'manage_options' ) );
    }

    public static function iw_verify_member_card(WP_REST_Request $request) {

        $card_number = self::normalize_member_card_id( sanitize_text_field( wp_unslash( (string) $request->get_param( 'member-card-id' ) ) ) );

        if ( ! empty( $card_number ) ) {
            $users = get_users(['meta_key' => 'member_card_id', 'meta_value' => $card_number, 'number' => 1, 'fields' => 'ID']);
            if (empty($users)) { return [ 'valid' => false, 'error' => 'Card not found']; }
            $user_id = $users[0];
        } else {
            return [ 'valid' => false, 'error' => 'Card not found' ];
        }

        return self::build_member_verification_response( (int) $user_id );
    }

    public static function iw_search_member_card(WP_REST_Request $request) {
        $query = trim( sanitize_text_field( wp_unslash( (string) $request->get_param( 'q' ) ) ) );

        if ( self::search_query_length( $query ) < 3 ) {
            return [ 'valid' => false, 'error' => __( 'Type at least 3 characters.', 'iw-theme' ) ];
        }

        $matches = self::find_member_search_results( $query );
        if ( empty( $matches ) ) {
            return [ 'valid' => false, 'error' => __( 'Member not found.', 'iw-theme' ) ];
        }

        if ( count( $matches ) === 1 ) {
            return self::build_member_verification_response( (int) $matches[0]['user_id'] );
        }

        return [
            'valid'              => false,
            'requires_selection' => true,
            'error'              => sprintf( __( 'Found %d members. Select one.', 'iw-theme' ), count( $matches ) ),
            'matches'            => array_map(
                static function ( array $match ): array {
                    return self::build_member_search_match( (int) $match['user_id'], (int) $match['score'] );
                },
                $matches
            ),
            'timestamp'          => current_time( 'mysql' ),
        ];
    }

    private static function build_member_verification_response( int $user_id ): array {
        $subscription = IW_Wallet_Common::get_active_or_last_subscription( $user_id );
        $subscription_item = $subscription ? IW_Wallet_Common::get_subscription_membership_item( $subscription ) : null;
        $user = get_userdata( $user_id );
        $first_name = (string) get_user_meta( $user_id, 'first_name', true );
        $last_name = (string) get_user_meta( $user_id, 'last_name', true );
        $name = trim( $first_name . ' ' . $last_name );
        if ( $name === '' && $user instanceof WP_User ) {
            $name = $user->display_name;
        }

        $created_at = $subscription ? $subscription->get_date_created() : null;
        $subscription_data = $subscription ? $subscription->get_data() : [];
        $expires_at = is_array( $subscription_data ) ? ( $subscription_data['schedule_next_payment'] ?? null ) : null;
        $has_valid_subscription = ! empty( $subscription ) && $subscription->has_status(['active', 'trial']);
        $subscription_reason = '';

        if ( ! $has_valid_subscription ) {
            if ( $subscription ) {
                $subscription_reason = sprintf(
                    __( 'Η συνδρομή δεν είναι ενεργή. Κατάσταση: %s.', 'iw-theme' ),
                    function_exists( 'wcs_get_subscription_status_name' )
                        ? wcs_get_subscription_status_name( $subscription->get_status() )
                        : $subscription->get_status()
                );
            } else {
                $subscription_reason = __( 'Δεν βρέθηκε ενεργή συνδρομή για αυτό το μέλος.', 'iw-theme' );
            }
        }

        return [
            'valid' => $has_valid_subscription,
            'user'  => [
                'id'         => $user_id,
                'name'       => $name,
                'email'      => $user instanceof WP_User ? $user->user_email : '',
                'card_id'    => get_user_meta($user_id, 'member_card_id', true),
                'photo'      => self::get_member_photo_square_url( $user_id ),
                'photo_thumb' => self::get_member_photo_thumbnail_url( $user_id ),
            ],
            'subscription' => ! empty( $subscription ) ? [
                'id'         => $subscription->get_id(),
                'status'     => $subscription->get_status(),
                'name'       => $subscription_item ? $subscription_item->get_name() : '',
                'created_at' => $created_at && method_exists( $created_at, 'date_i18n' ) ? $created_at->date_i18n( 'd/m/Y H:i' ) : '',
                'expires'    => $expires_at && method_exists( $expires_at, 'date_i18n' ) ? $expires_at->date_i18n( 'd/m/Y H:i' ) : '',
                'valid'      => $has_valid_subscription,
                'reason'     => $subscription_reason,
            ] : null,
            'subscription_reason' => $subscription_reason,
            'tickets' => self::get_member_active_tickets( $user_id ),
            'timestamp' => current_time('mysql')
        ];
    }

    private static function get_member_active_tickets( int $user_id, int $limit = 20 ): array {
        if ( $user_id <= 0 || ! function_exists( 'wc_get_orders' ) ) {
            return [];
        }

        global $wpdb;

        $order_ids = wc_get_orders(
            [
                'customer_id' => $user_id,
                'limit'       => -1,
                'orderby'     => 'date',
                'order'       => 'DESC',
                'status'      => [ 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-reserved', 'wc-res-confirmed', 'wc-reservation-confirmed', 'wc-reservation-confi' ],
                'return'      => 'ids',
            ]
        );

        if ( empty( $order_ids ) || ! is_array( $order_ids ) ) {
            return [];
        }

        $order_ids = array_values( array_filter( array_map( 'absint', $order_ids ) ) );
        if ( empty( $order_ids ) ) {
            return [];
        }

        $placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );
        $ticket_table = $wpdb->prefix . 'iw_tickets';
        $params = array_merge( $order_ids, [ current_time( 'mysql' ), max( 1, $limit ) ] );
        $sql = "
            SELECT t.*
            FROM {$ticket_table} t
            WHERE t.order_id IN ({$placeholders})
                AND t.slot_start IS NOT NULL
                AND t.slot_start >= %s
                AND (t.status = 'valid' OR t.status = '' OR t.status IS NULL)
            ORDER BY t.slot_start ASC, t.id ASC
            LIMIT %d
        ";

        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
        if ( empty( $rows ) ) {
            return [];
        }

        return array_map(
            static function ( array $ticket ): array {
                $post_id = ! empty( $ticket['post_id'] ) ? (int) $ticket['post_id'] : 0;
                $post = $post_id ? get_post( $post_id ) : null;
                $title = $post instanceof WP_Post ? get_the_title( $post ) : __( 'Ticket', 'iw-theme' );
                $is_permanent = $post instanceof WP_Post && $post->post_type === 'exhibition'
                    ? self::is_ticket_event_permanent_exhibition( $post_id )
                    : false;

                return [
                    'id'             => isset( $ticket['id'] ) ? (int) $ticket['id'] : null,
                    'ticket_uuid'    => (string) ( $ticket['ticket_uuid'] ?? '' ),
                    'barcode_hash'   => (string) ( $ticket['barcode_hash'] ?? '' ),
                    'order_id'       => ! empty( $ticket['order_id'] ) ? (int) $ticket['order_id'] : null,
                    'order_item_id'  => ! empty( $ticket['order_item_id'] ) ? (int) $ticket['order_item_id'] : null,
                    'post_id'        => $post_id ?: null,
                    'post_type'      => $post instanceof WP_Post ? (string) $post->post_type : '',
                    'title'          => wp_strip_all_tags( $title ),
                    'slot_start'     => ! empty( $ticket['slot_start'] ) ? (string) $ticket['slot_start'] : null,
                    'slot_end'       => ! empty( $ticket['slot_end'] ) ? (string) $ticket['slot_end'] : null,
                    'ticket_type'    => ! empty( $ticket['ticket_type'] ) ? (string) $ticket['ticket_type'] : null,
                    'price_category' => ! empty( $ticket['price_category'] ) ? (string) $ticket['price_category'] : null,
                    'unit_price'     => isset( $ticket['unit_price'] ) ? (string) $ticket['unit_price'] : null,
                    'currency'       => ! empty( $ticket['currency'] ) ? (string) $ticket['currency'] : null,
                    'channel'        => ! empty( $ticket['channel'] ) ? (string) $ticket['channel'] : null,
                    'attendee_name'  => ! empty( $ticket['attendee_name'] ) ? (string) $ticket['attendee_name'] : null,
                    'attendee_email' => ! empty( $ticket['attendee_email'] ) ? (string) $ticket['attendee_email'] : null,
                    'status'         => array_key_exists( 'status', $ticket ) ? (string) $ticket['status'] : null,
                    'issued_at'      => ! empty( $ticket['issued_at'] ) ? (string) $ticket['issued_at'] : null,
                    'used_at'        => ! empty( $ticket['used_at'] ) ? (string) $ticket['used_at'] : null,
                    'is_permanent'   => $is_permanent,
                    'exhibition_type' => $is_permanent ? 'permanent' : '',
                    'building'       => $post_id ? self::get_ticket_event_building_payload( $post_id ) : null,
                    'image'          => $post_id ? ( get_the_post_thumbnail_url( $post_id, 'medium' ) ?: get_the_post_thumbnail_url( $post_id, 'large' ) ?: null ) : null,
                    'thumb'          => $post_id ? ( get_the_post_thumbnail_url( $post_id, 'thumbnail' ) ?: get_the_post_thumbnail_url( $post_id, 'medium' ) ?: null ) : null,
                ];
            },
            $rows
        );
    }

    private static function get_ticket_event_building_payload( int $post_id ): ?array {
        $building_id = self::get_ticket_event_building_id( $post_id );
        if ( ! $building_id ) {
            return null;
        }

        return [
            'id'      => $building_id,
            'title'   => get_the_title( $building_id ),
            'url'     => get_permalink( $building_id ),
            'image'   => get_the_post_thumbnail_url( $building_id, 'large' ) ?: null,
            'address' => function_exists( 'get_field' ) ? get_field( 'address', $building_id ) : get_post_meta( $building_id, 'address', true ),
        ];
    }

    private static function get_ticket_event_building_id( int $post_id ): int {
        if ( $post_id <= 0 ) {
            return 0;
        }

        $post = get_post( $post_id );
        if ( $post instanceof WP_Post && $post->post_type === 'building' ) {
            return $post_id;
        }

        $building = function_exists( 'get_field' ) ? get_field( 'building_location', $post_id ) : null;
        $building_id = self::normalize_ticket_event_post_id( $building );

        if ( ! $building_id ) {
            $building_id = self::normalize_ticket_event_post_id( get_post_meta( $post_id, 'building_location', true ) );
        }

        return $building_id;
    }

    private static function is_ticket_event_permanent_exhibition( int $post_id ): bool {
        $value = function_exists( 'get_field' ) ? get_field( 'is_permanent', $post_id ) : get_post_meta( $post_id, 'is_permanent', true );

        return $value === true || $value === 1 || $value === '1' || $value === 'yes' || $value === 'true';
    }

    private static function normalize_ticket_event_post_id( $value ): int {
        if ( $value instanceof WP_Post ) {
            return (int) $value->ID;
        }

        if ( is_object( $value ) && ! empty( $value->ID ) ) {
            return (int) $value->ID;
        }

        if ( is_array( $value ) ) {
            if ( ! empty( $value['ID'] ) ) {
                return (int) $value['ID'];
            }

            $first = reset( $value );
            return self::normalize_ticket_event_post_id( $first );
        }

        return is_numeric( $value ) ? (int) $value : 0;
    }

    private static function build_member_search_match( int $user_id, int $score = 0 ): array {
        $verification = self::build_member_verification_response( $user_id );
        $user_data = is_array( $verification['user'] ?? null ) ? $verification['user'] : [];
        $subscription_data = is_array( $verification['subscription'] ?? null ) ? $verification['subscription'] : [];

        return [
            'id'           => $user_id,
            'name'         => (string) ( $user_data['name'] ?? '' ),
            'email'        => (string) ( $user_data['email'] ?? '' ),
            'phone'        => self::get_member_search_phone( $user_id ),
            'photo'        => (string) ( $user_data['photo'] ?? '' ),
            'photo_thumb'  => self::get_member_photo_thumbnail_url( $user_id ),
            'card_id'      => (string) ( $user_data['card_id'] ?? get_user_meta( $user_id, 'member_card_id', true ) ),
            'subscription' => [
                'status' => (string) ( $subscription_data['status'] ?? '' ),
                'name'   => (string) ( $subscription_data['name'] ?? '' ),
                'valid'  => ! empty( $verification['valid'] ),
            ],
            'verification' => $verification,
            'score'        => $score,
        ];
    }

    private static function find_member_search_results( string $query, int $limit = 8 ): array {
        $candidate_ids = self::find_member_candidate_user_ids( $query );
        if ( empty( $candidate_ids ) ) {
            return [];
        }

        $ranked = [];
        foreach ( $candidate_ids as $candidate_id ) {
            $score = self::score_member_search_candidate( (int) $candidate_id, $query );
            if ( $score > 0 ) {
                $ranked[ (int) $candidate_id ] = $score;
            }
        }

        if ( empty( $ranked ) ) {
            return [];
        }

        arsort( $ranked, SORT_NUMERIC );

        $results = [];
        foreach ( $ranked as $user_id => $score ) {
            $results[] = [
                'user_id' => (int) $user_id,
                'score'   => (int) $score,
            ];

            if ( count( $results ) >= $limit ) {
                break;
            }
        }

        return $results;
    }

    private static function find_member_candidate_user_ids( string $query ): array {
        $ids = [];
        $add_ids = static function ( $users ) use ( &$ids ) {
            foreach ( (array) $users as $user ) {
                $ids[] = $user instanceof WP_User ? (int) $user->ID : (int) $user;
            }
        };

        $card_number = self::normalize_member_card_id( $query );
        if ( $card_number !== '' ) {
            $add_ids( get_users( [ 'meta_key' => 'member_card_id', 'meta_value' => $card_number, 'number' => 5, 'fields' => 'ID' ] ) );
        }

        if ( is_email( $query ) ) {
            $user = get_user_by( 'email', $query );
            if ( $user instanceof WP_User ) {
                $ids[] = (int) $user->ID;
            }
        }

        $add_ids(
            get_users(
                [
                    'number'         => 30,
                    'fields'         => 'ID',
                    'search'         => '*' . $query . '*',
                    'search_columns' => [ 'user_login', 'user_email', 'user_nicename', 'display_name' ],
                ]
            )
        );

        $meta_query = [ 'relation' => 'OR' ];
        foreach ( self::member_search_meta_keys() as $meta_key ) {
            $meta_query[] = [
                'key'     => $meta_key,
                'value'   => $query,
                'compare' => 'LIKE',
            ];
        }

        $phone_digits = self::search_digits( $query );
        if ( strlen( $phone_digits ) >= 3 ) {
            foreach ( self::member_phone_meta_keys() as $meta_key ) {
                $meta_query[] = [
                    'key'     => $meta_key,
                    'value'   => $phone_digits,
                    'compare' => 'LIKE',
                ];
            }
        }

        $add_ids(
            get_users(
                [
                    'number'     => 40,
                    'fields'     => 'ID',
                    'meta_query' => $meta_query,
                ]
            )
        );

        $words = preg_split( '/\s+/', $query );
        if ( is_array( $words ) && count( $words ) > 1 ) {
            foreach ( $words as $word ) {
                $word = trim( $word );
                if ( self::search_query_length( $word ) < 2 ) {
                    continue;
                }

                $add_ids(
                    get_users(
                        [
                            'number'     => 25,
                            'fields'     => 'ID',
                            'meta_query' => [
                                'relation' => 'OR',
                                [
                                    'key'     => 'first_name',
                                    'value'   => $word,
                                    'compare' => 'LIKE',
                                ],
                                [
                                    'key'     => 'last_name',
                                    'value'   => $word,
                                    'compare' => 'LIKE',
                                ],
                            ],
                        ]
                    )
                );
            }
        }

        $ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

        return array_values(
            array_filter(
                $ids,
                static function ( int $user_id ): bool {
                    return get_user_meta( $user_id, 'member_card_id', true ) !== '';
                }
            )
        );
    }

    private static function score_member_search_candidate( int $user_id, string $query ): int {
        $user = get_userdata( $user_id );
        if ( ! $user instanceof WP_User ) {
            return 0;
        }

        $normalized_query = self::normalize_member_search_text( $query );
        $query_digits = self::search_digits( $query );
        $card_number = self::normalize_member_card_id( $query );
        $first_name = (string) get_user_meta( $user_id, 'first_name', true );
        $last_name = (string) get_user_meta( $user_id, 'last_name', true );
        $full_name = trim( $first_name . ' ' . $last_name );
        $name_text = self::normalize_member_search_text( trim( $full_name . ' ' . $user->display_name ) );
        $email_text = self::normalize_member_search_text( $user->user_email );
        $member_card_id = (string) get_user_meta( $user_id, 'member_card_id', true );
        $score = 0;

        if ( $card_number !== '' && hash_equals( $member_card_id, $card_number ) ) {
            $score = max( $score, 1000 );
        }

        if ( $normalized_query !== '' && hash_equals( $email_text, $normalized_query ) ) {
            $score = max( $score, 950 );
        }

        if ( $query_digits !== '' ) {
            foreach ( self::member_phone_meta_keys() as $meta_key ) {
                $phone_digits = self::search_digits( get_user_meta( $user_id, $meta_key, true ) );
                if ( $phone_digits === '' ) {
                    continue;
                }

                if ( hash_equals( $phone_digits, $query_digits ) ) {
                    $score = max( $score, 920 );
                } elseif ( strpos( $phone_digits, $query_digits ) !== false ) {
                    $score = max( $score, 700 );
                }
            }
        }

        if ( $normalized_query !== '' ) {
            if ( hash_equals( self::normalize_member_search_text( $full_name ), $normalized_query ) || hash_equals( self::normalize_member_search_text( $user->display_name ), $normalized_query ) ) {
                $score = max( $score, 900 );
            } elseif ( strpos( $email_text, $normalized_query ) !== false ) {
                $score = max( $score, 720 );
            } elseif ( strpos( $name_text, $normalized_query ) !== false ) {
                $score = max( $score, 680 );
            } else {
                $words = preg_split( '/\s+/', $normalized_query );
                $matched_words = 0;
                foreach ( (array) $words as $word ) {
                    if ( $word !== '' && ( strpos( $name_text, $word ) !== false || strpos( $email_text, $word ) !== false ) ) {
                        $matched_words++;
                    }
                }

                if ( $matched_words && $matched_words === count( array_filter( (array) $words ) ) ) {
                    $score = max( $score, 620 );
                }
            }
        }

        $subscription = IW_Wallet_Common::get_active_or_last_subscription( $user_id );
        if ( $subscription && $subscription->has_status( [ 'active', 'trial' ] ) ) {
            $score += 20;
        }

        return $score;
    }

    private static function member_search_meta_keys(): array {
        return array_values(
            array_unique(
                array_merge(
                    [ 'first_name', 'last_name', 'nickname', 'member_card_id' ],
                    self::member_phone_meta_keys()
                )
            )
        );
    }

    private static function member_phone_meta_keys(): array {
        $keys = [ 'billing_phone', 'shipping_phone' ];

        if ( class_exists( 'IW_Custom_Auth_Activation' ) ) {
            $keys[] = IW_Custom_Auth_Activation::META_PHONE;
            $keys[] = IW_Custom_Auth_Activation::META_PENDING_PHONE;
        }

        return array_values( array_unique( $keys ) );
    }

    private static function get_member_search_phone( int $user_id ): string {
        foreach ( self::member_phone_meta_keys() as $meta_key ) {
            $phone = trim( (string) get_user_meta( $user_id, $meta_key, true ) );
            if ( $phone !== '' ) {
                return $phone;
            }
        }

        return '';
    }

    private static function normalize_member_search_text( $value ): string {
        $value = remove_accents( wp_strip_all_tags( (string) $value ) );
        $value = function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
        $value = preg_replace( '/\s+/', ' ', $value );

        return trim( (string) $value );
    }

    private static function search_digits( $value ): string {
        return preg_replace( '/\D+/', '', (string) $value );
    }

    private static function search_query_length( string $query ): int {
        return function_exists( 'mb_strlen' ) ? mb_strlen( $query, 'UTF-8' ) : strlen( $query );
    }
    /**
     * Set default member-card for newly created users (ACF Options: default_members_card)
     */
    public static function set_default_member_card_on_user_create( $user_id ){
        if( ! empty( get_user_meta( $user_id, 'member_card', true ) ) ) return;
        $default_card_query = new WP_Query([
            'post_type'      => 'member-card',
            'posts_per_page' => 1,
            'meta_query'     => [
                [
                    'key'   => 'members_default',
                    'value' => 1,
                ],
            ],
        ]);

        if ( empty( $default_card_query->posts ) ) return;

        $default_card = $default_card_query->posts[0];
        update_user_meta( $user_id, 'member_card', $default_card->ID );
    }

    public static function generate_for_user( $user_id ){
        $subscriptions = wcs_get_users_subscriptions( $user_id );
        if( empty(  $subscriptions) ) return false;
        foreach( $subscriptions as $subscription ){
            if( $subscription->has_status( array( 'active', 'trial' ) ) ){
                self::on_subscription_activated( $subscription, true );
                return true;
            }
        }
        return false;
    }

    public static function set_member_photo( $user_id, $photo_url ){
        if( empty( $user_id ) || empty( $photo_url ) ){
            return false;
        }
        update_user_meta( $user_id, 'member_photo_url', esc_url_raw( $photo_url ) );
        delete_user_meta( $user_id, 'member_photo_thumb_url' );
        self::get_member_photo_thumbnail_url( $user_id );
        self::touch_wallet_versions( $user_id );
        return true;
    }

    public static function ajax_update_member_photo() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'Δεν έχετε δικαίωμα πρόσβασης.', 'iw-theme' ) ], 403 );
        }

        if ( ! check_ajax_referer( 'iw_member_card_update_photo', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Η συνεδρία έληξε. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.', 'iw-theme' ) ], 403 );
        }

        $user_id = get_current_user_id();
        $result  = self::member_photo_from_request( $user_id, 'member_photo', 'remove_member_photo' );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ], 422 );
        }

        wp_send_json_success(
            [
                'message' => empty( $result ) ? __( 'Η φωτογραφία διαγράφηκε.', 'iw-theme' ) : __( 'Η φωτογραφία ενημερώθηκε.', 'iw-theme' ),
                'image'   => [
                    'url'         => $result,
                    'preview_url' => $result ? self::get_member_photo_preview_url( $user_id ) : '',
                    'name'        => $result ? basename( wp_parse_url( $result, PHP_URL_PATH ) ) : '',
                    'focal_x'     => get_user_meta( $user_id, 'member_photo_focal_x', true ) ?: 50,
                    'focal_y'     => get_user_meta( $user_id, 'member_photo_focal_y', true ) ?: 50,
                    'zoom'        => get_user_meta( $user_id, 'member_photo_zoom', true ) ?: 1,
                    'zoom_max'    => self::get_member_photo_zoom_max( $user_id ),
                ],
            ]
        );
    }

    public static function save_user_photo( $user_id ){
        $has_upload = ! empty( $_FILES['member_photo_upload']['tmp_name'] ) && UPLOAD_ERR_NO_FILE !== (int) ( $_FILES['member_photo_upload']['error'] ?? UPLOAD_ERR_NO_FILE );
        $has_remove = ! empty( $_POST['remove_member_photo'] );

        if ( ! $has_upload && ! $has_remove ) {
            return;
        }

        self::member_photo_from_request( $user_id, 'member_photo_upload', 'remove_member_photo' );
    }

    private static function member_photo_from_request( $user_id, $field_name, $remove_field_name ) {
        if ( ! empty( $_POST[ $remove_field_name ] ) && empty( $_FILES[ $field_name ]['tmp_name'] ) ) {
            delete_user_meta( $user_id, 'member_photo_url' );
            delete_user_meta( $user_id, 'member_photo_thumb_url' );
            delete_user_meta( $user_id, 'member_photo_original_url' );
            delete_user_meta( $user_id, 'member_photo_focal_x' );
            delete_user_meta( $user_id, 'member_photo_focal_y' );
            delete_user_meta( $user_id, 'member_photo_zoom' );
            self::touch_wallet_versions( $user_id );
            return '';
        }

        $focal   = self::member_photo_focal_from_request();
        $zoom    = self::member_photo_zoom_from_request();
        $current = get_user_meta( $user_id, 'member_photo_url', true );

        if ( empty( $_FILES[ $field_name ]['tmp_name'] ) ) {
            if ( $current && self::member_photo_crop_changed( $user_id, $focal, $zoom ) ) {
                $original = get_user_meta( $user_id, 'member_photo_original_url', true );
                $path     = self::member_photo_path_from_url( $original ?: $current );
                if ( $path ) {
                    require_once ABSPATH . 'wp-admin/includes/image.php';

                    $photo_url = self::crop_member_photo_file( $user_id, $path, wp_check_filetype( $path )['type'] ?? '', $focal, '', $zoom );
                    if ( is_wp_error( $photo_url ) ) {
                        return $photo_url;
                    }

                    update_user_meta( $user_id, 'member_photo_url', esc_url_raw( $photo_url ) );
                    self::save_member_photo_crop( $user_id, $focal, $zoom );
                    self::touch_wallet_versions( $user_id );
                    return $photo_url;
                }
            }

            self::save_member_photo_crop( $user_id, $focal, $zoom );
            return $current;
        }

        $photo_url = self::upload_member_photo( $user_id, $field_name, $focal, $zoom );
        if ( is_wp_error( $photo_url ) ) {
            return $photo_url;
        }

        update_user_meta( $user_id, 'member_photo_url', esc_url_raw( $photo_url ) );
        self::save_member_photo_crop( $user_id, $focal, $zoom );
        self::touch_wallet_versions( $user_id );

        return $photo_url;
    }

    private static function upload_member_photo( $user_id, $field_name, array $focal, $zoom ) {
        $file = $_FILES[ $field_name ] ?? [];

        if ( empty( $file['tmp_name'] ) || UPLOAD_ERR_NO_FILE === (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
            return get_user_meta( $user_id, 'member_photo_url', true );
        }

        if ( UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_OK ) ) {
            return new WP_Error( 'invalid_member_photo', __( 'Η φωτογραφία δεν ανέβηκε σωστά. Δοκιμάστε ξανά.', 'iw-theme' ) );
        }

        if ( (int) ( $file['size'] ?? 0 ) > 2 * MB_IN_BYTES ) {
            return new WP_Error( 'member_photo_too_large', __( 'Η φωτογραφία πρέπει να είναι έως 2MB.', 'iw-theme' ) );
        }

        $allowed_mimes = [
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'webp'         => 'image/webp',
        ];
        $file_type     = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed_mimes );

        if ( empty( $file_type['type'] ) || ! in_array( $file_type['type'], $allowed_mimes, true ) ) {
            return new WP_Error( 'invalid_member_photo_type', __( 'Ανεβάστε φωτογραφία σε μορφή JPG, PNG ή WebP.', 'iw-theme' ) );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $extension = $file_type['ext'] ?: pathinfo( $file['name'], PATHINFO_EXTENSION );
        $original  = self::save_member_photo_original_file( $user_id, $file['tmp_name'], $extension );

        if ( is_wp_error( $original ) ) {
            return $original;
        }

        $photo_url = self::crop_member_photo_file( $user_id, $original['path'], $file_type['type'], $focal, $extension, $zoom );
        if ( is_wp_error( $photo_url ) ) {
            return $photo_url;
        }

        update_user_meta( $user_id, 'member_photo_original_url', esc_url_raw( $original['url'] ) );

        return $photo_url;
    }

    private static function save_member_photo_original_file( $user_id, $source_path, $extension ) {
        $uploads = wp_upload_dir();
        $dir     = trailingslashit( $uploads['basedir'] ) . 'member-photos/originals/';
        $url_dir = trailingslashit( $uploads['baseurl'] ) . 'member-photos/originals/';
        wp_mkdir_p( $dir );

        $card = sanitize_file_name( get_user_meta( $user_id, 'member_card_id', true ) );
        if ( ! $card ) {
            $card = 'user-' . (int) $user_id;
        }

        $extension = strtolower( $extension ?: pathinfo( $source_path, PATHINFO_EXTENSION ) ?: 'jpg' );
        if ( 'jpeg' === $extension || 'jpe' === $extension ) {
            $extension = 'jpg';
        }

        $filename = $card . '-original-' . time() . '.' . $extension;
        $dest     = $dir . $filename;

        if ( ! copy( $source_path, $dest ) ) {
            return new WP_Error( 'invalid_member_photo', __( 'Η φωτογραφία δεν αποθηκεύτηκε σωστά. Δοκιμάστε ξανά.', 'iw-theme' ) );
        }

        return [
            'path' => $dest,
            'url'  => esc_url_raw( $url_dir . $filename ),
        ];
    }

    private static function crop_member_photo_file( $user_id, $source_path, $mime_type, array $focal, $extension = '', $zoom = 1 ) {
        $uploads = wp_upload_dir();
        $dir     = trailingslashit( $uploads['basedir'] ) . 'member-photos/';
        $url_dir = trailingslashit( $uploads['baseurl'] ) . 'member-photos/';
        wp_mkdir_p( $dir );

        $card = sanitize_file_name( get_user_meta( $user_id, 'member_card_id', true ) );
        if ( ! $card ) {
            $card = 'user-' . (int) $user_id;
        }

        $extension = $extension ?: pathinfo( $source_path, PATHINFO_EXTENSION );
        $extension = strtolower( $extension ?: 'jpg' );
        if ( 'jpeg' === $extension || 'jpe' === $extension ) {
            $extension = 'jpg';
        }

        $filename = $card . '-' . time() . '.' . $extension;
        $dest     = $dir . $filename;
        $editor   = wp_get_image_editor( $source_path );

        if ( is_wp_error( $editor ) ) {
            return new WP_Error( 'invalid_member_photo', __( 'Η φωτογραφία δεν μπορεί να επεξεργαστεί. Δοκιμάστε άλλο αρχείο.', 'iw-theme' ) );
        }

        $size      = $editor->get_size();
        $width     = max( 1, (int) ( $size['width'] ?? 0 ) );
        $height    = max( 1, (int) ( $size['height'] ?? 0 ) );

        if ( $width < 600 || $height < 600 ) {
            return new WP_Error( 'member_photo_too_small', __( 'Η φωτογραφία πρέπει να είναι τουλάχιστον 600x600px.', 'iw-theme' ) );
        }

        $max_zoom  = self::member_photo_zoom_max_for_dimensions( $width, $height );
        $zoom      = max( 1, min( $max_zoom, (float) $zoom ) );
        $crop_size = max( 600, (int) floor( min( $width, $height ) / $zoom ) );
        $src_x     = (int) round( max( 0, $width - $crop_size ) * ( $focal['x'] / 100 ) );
        $src_y     = (int) round( max( 0, $height - $crop_size ) * ( $focal['y'] / 100 ) );

        $cropped = $editor->crop( $src_x, $src_y, $crop_size, $crop_size, 600, 600, false );
        if ( is_wp_error( $cropped ) ) {
            return new WP_Error( 'invalid_member_photo', __( 'Η φωτογραφία δεν μπορεί να περικοπεί σωστά. Δοκιμάστε άλλο αρχείο.', 'iw-theme' ) );
        }

        $saved = $editor->save( $dest, $mime_type ?: null );

        if ( is_wp_error( $saved ) || empty( $saved['path'] ) ) {
            return new WP_Error( 'invalid_member_photo', __( 'Η φωτογραφία δεν αποθηκεύτηκε σωστά. Δοκιμάστε ξανά.', 'iw-theme' ) );
        }

        $photo_url = esc_url_raw( $url_dir . basename( $saved['path'] ) );
        $thumb_url = self::create_member_photo_thumbnail_file(
            $user_id,
            $saved['path'],
            $saved['mime-type'] ?? $mime_type,
            $card,
            $extension
        );

        if ( $thumb_url ) {
            update_user_meta( $user_id, 'member_photo_thumb_url', esc_url_raw( $thumb_url ) );
        } else {
            delete_user_meta( $user_id, 'member_photo_thumb_url' );
        }

        return $photo_url;
    }

    private static function create_member_photo_thumbnail_file( $user_id, $source_path, $mime_type = '', $card = '', $extension = '' ) {
        if ( empty( $source_path ) || ! file_exists( $source_path ) ) {
            return '';
        }

        $uploads = wp_upload_dir();
        $dir     = trailingslashit( $uploads['basedir'] ) . 'member-photos/thumbs/';
        $url_dir = trailingslashit( $uploads['baseurl'] ) . 'member-photos/thumbs/';
        wp_mkdir_p( $dir );

        if ( ! $card ) {
            $card = sanitize_file_name( get_user_meta( $user_id, 'member_card_id', true ) );
            if ( ! $card ) {
                $card = 'user-' . (int) $user_id;
            }
        }

        $extension = $extension ?: pathinfo( $source_path, PATHINFO_EXTENSION );
        $extension = strtolower( $extension ?: 'jpg' );
        if ( 'jpeg' === $extension || 'jpe' === $extension ) {
            $extension = 'jpg';
        }

        $editor = wp_get_image_editor( $source_path );
        if ( is_wp_error( $editor ) ) {
            return '';
        }

        $resized = $editor->resize( 100, 100, true );
        if ( is_wp_error( $resized ) ) {
            return '';
        }

        $filename = $card . '-thumb-' . time() . '.' . $extension;
        $saved    = $editor->save( $dir . $filename, $mime_type ?: null );

        if ( is_wp_error( $saved ) || empty( $saved['path'] ) ) {
            return '';
        }

        return esc_url_raw( $url_dir . basename( $saved['path'] ) );
    }

    private static function member_photo_focal_from_request() {
        return [
            'x' => max( 0, min( 100, (float) ( $_POST['image_focal_x'] ?? 50 ) ) ),
            'y' => max( 0, min( 100, (float) ( $_POST['image_focal_y'] ?? 50 ) ) ),
        ];
    }

    private static function member_photo_zoom_from_request() {
        return max( 1, (float) ( $_POST['image_zoom'] ?? 1 ) );
    }

    private static function save_member_photo_crop( $user_id, array $focal, $zoom ) {
        $zoom = max( 1, min( self::get_member_photo_zoom_max( $user_id ), (float) $zoom ) );

        update_user_meta( $user_id, 'member_photo_focal_x', $focal['x'] );
        update_user_meta( $user_id, 'member_photo_focal_y', $focal['y'] );
        update_user_meta( $user_id, 'member_photo_zoom', $zoom );
    }

    private static function member_photo_crop_changed( $user_id, array $focal, $zoom ) {
        $current_x = get_user_meta( $user_id, 'member_photo_focal_x', true );
        $current_y = get_user_meta( $user_id, 'member_photo_focal_y', true );
        $current_zoom = get_user_meta( $user_id, 'member_photo_zoom', true );

        if ( '' === $current_x ) {
            $current_x = 50;
        }
        if ( '' === $current_y ) {
            $current_y = 50;
        }
        if ( '' === $current_zoom ) {
            $current_zoom = 1;
        }

        return abs( (float) $current_x - $focal['x'] ) > 0.1 || abs( (float) $current_y - $focal['y'] ) > 0.1 || abs( (float) $current_zoom - (float) $zoom ) > 0.01;
    }

    private static function member_photo_path_from_url( $url ) {
        $uploads  = wp_upload_dir();
        $base_url = trailingslashit( $uploads['baseurl'] );

        if ( 0 !== strpos( $url, $base_url ) ) {
            return '';
        }

        $relative = ltrim( substr( $url, strlen( $base_url ) ), '/' );
        $path     = trailingslashit( $uploads['basedir'] ) . $relative;

        return file_exists( $path ) ? $path : '';
    }

    public static function get_member_photo_zoom_max( $user_id ) {
        $path = self::member_photo_path_from_url( self::get_member_photo_preview_url( $user_id ) );

        if ( ! $path ) {
            return 1;
        }

        $size = getimagesize( $path );
        if ( empty( $size[0] ) || empty( $size[1] ) ) {
            return 1;
        }

        return self::member_photo_zoom_max_for_dimensions( (int) $size[0], (int) $size[1] );
    }

    public static function get_member_photo_preview_url( $user_id ) {
        $original = get_user_meta( $user_id, 'member_photo_original_url', true );
        $current  = get_user_meta( $user_id, 'member_photo_url', true );

        return $original ?: $current;
    }

    public static function get_member_photo_square_url( $user_id ) {
        $current = get_user_meta( $user_id, 'member_photo_url', true );
        if ( empty( $current ) ) {
            return '';
        }

        $current_path = self::member_photo_path_from_url( $current );
        if ( $current_path && self::is_member_photo_square_file( $current_path ) ) {
            return $current;
        }

        $source_url  = get_user_meta( $user_id, 'member_photo_original_url', true ) ?: $current;
        $source_path = self::member_photo_path_from_url( $source_url );
        if ( ! $source_path ) {
            return $current;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';

        $photo_url = self::crop_member_photo_file(
            $user_id,
            $source_path,
            wp_check_filetype( $source_path )['type'] ?? '',
            self::member_photo_crop_from_meta( $user_id ),
            '',
            self::member_photo_zoom_from_meta( $user_id )
        );

        if ( is_wp_error( $photo_url ) ) {
            return $current;
        }

        update_user_meta( $user_id, 'member_photo_url', esc_url_raw( $photo_url ) );
        self::touch_wallet_versions( $user_id );

        return $photo_url;
    }

    public static function get_member_photo_thumbnail_url( $user_id ) {
        $thumb = get_user_meta( $user_id, 'member_photo_thumb_url', true );
        if ( $thumb ) {
            $thumb_path = self::member_photo_path_from_url( $thumb );
            if ( $thumb_path && self::is_member_photo_thumbnail_file( $thumb_path ) ) {
                return $thumb;
            }
        }

        $square = self::get_member_photo_square_url( $user_id );
        if ( ! $square ) {
            delete_user_meta( $user_id, 'member_photo_thumb_url' );
            return '';
        }

        $square_path = self::member_photo_path_from_url( $square );
        if ( ! $square_path ) {
            delete_user_meta( $user_id, 'member_photo_thumb_url' );
            return $square;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';

        $thumb_url = self::create_member_photo_thumbnail_file(
            $user_id,
            $square_path,
            wp_check_filetype( $square_path )['type'] ?? ''
        );

        if ( ! $thumb_url ) {
            delete_user_meta( $user_id, 'member_photo_thumb_url' );
            return $square;
        }

        update_user_meta( $user_id, 'member_photo_thumb_url', esc_url_raw( $thumb_url ) );

        return $thumb_url;
    }

    private static function is_member_photo_square_file( $path ) {
        $size = @getimagesize( $path );
        if ( empty( $size[0] ) || empty( $size[1] ) ) {
            return false;
        }

        return (int) $size[0] === (int) $size[1] && (int) $size[0] >= 600;
    }

    private static function is_member_photo_thumbnail_file( $path ) {
        $size = @getimagesize( $path );
        if ( empty( $size[0] ) || empty( $size[1] ) ) {
            return false;
        }

        return (int) $size[0] === 100 && (int) $size[1] === 100;
    }

    private static function member_photo_crop_from_meta( $user_id ) {
        $x = get_user_meta( $user_id, 'member_photo_focal_x', true );
        $y = get_user_meta( $user_id, 'member_photo_focal_y', true );

        return [
            'x' => max( 0, min( 100, '' === $x ? 50 : (float) $x ) ),
            'y' => max( 0, min( 100, '' === $y ? 50 : (float) $y ) ),
        ];
    }

    private static function member_photo_zoom_from_meta( $user_id ) {
        $zoom = get_user_meta( $user_id, 'member_photo_zoom', true );
        return max( 1, '' === $zoom ? 1 : (float) $zoom );
    }

    private static function member_photo_zoom_max_for_dimensions( $width, $height ) {
        return max( 1, floor( ( min( max( 1, $width ), max( 1, $height ) ) / 600 ) * 100 ) / 100 );
    }

    private static function touch_wallet_versions( $user_id ) {
        update_user_meta( $user_id, 'apple_wallet_pass_version', time() );
        update_user_meta( $user_id, 'google_wallet_pass_version', time() );
        update_user_meta( $user_id, 'needs_wallet_update', true );
    }

    public static function register_members_admin_menu() {
        add_menu_page(
            __( 'Members Analytics', 'iw-theme' ),
            __( 'Members', 'iw-theme' ),
            'edit_member_cards',
            self::MEMBERS_ANALYTICS_SLUG,
            [ __CLASS__, 'render_members_analytics_page' ],
            'dashicons-id-alt',
            55.7
        );

        add_submenu_page(
            self::MEMBERS_ANALYTICS_SLUG,
            __( 'Members Analytics', 'iw-theme' ),
            __( 'Analytics', 'iw-theme' ),
            'edit_member_cards',
            self::MEMBERS_ANALYTICS_SLUG,
            [ __CLASS__, 'render_members_analytics_page' ]
        );

        add_submenu_page(
            self::MEMBERS_ANALYTICS_SLUG,
            __( 'Member card types', 'iw-theme' ),
            __( 'Member card types', 'iw-theme' ),
            'manage_member_card_terms',
            'edit-tags.php?taxonomy=member-card-types&post_type=member-card'
        );
    }

    public static function render_members_analytics_page() {
        if ( ! current_user_can( 'edit_member_cards' ) ) {
            wp_die( esc_html__( 'You do not have permission to view member analytics.', 'iw-theme' ) );
        }

        $stats = self::get_members_analytics_stats();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Members Analytics', 'iw-theme' ); ?></h1>

            <div class="notice notice-info" style="padding:20px; margin-top:20px;">
                <h2 style="margin-top:0;"><?php esc_html_e( 'Members Dashboard', 'iw-theme' ); ?></h2>

                <p><strong><?php esc_html_e( 'Active Apple Wallet cards:', 'iw-theme' ); ?></strong> <?php echo esc_html( $stats['apple_wallet_cards'] ); ?></p>
                <p><strong><?php esc_html_e( 'Active Google Wallet cards:', 'iw-theme' ); ?></strong> <?php echo esc_html( $stats['google_wallet_cards'] ); ?></p>
                <p><strong><?php esc_html_e( 'Total unique wallet cards:', 'iw-theme' ); ?></strong> <?php echo esc_html( $stats['unique_wallet_cards'] ); ?></p>
                <p><strong><?php esc_html_e( 'Member card templates:', 'iw-theme' ); ?></strong> <?php echo esc_html( $stats['member_card_templates'] ); ?></p>
                <p><strong><?php esc_html_e( 'Company subscriptions:', 'iw-theme' ); ?></strong> <?php echo esc_html( $stats['company_subscriptions'] ); ?></p>
                <p><strong><?php esc_html_e( 'Active subscriptions:', 'iw-theme' ); ?></strong> <?php echo esc_html( $stats['active_subscriptions'] ); ?></p>
                <p><strong><?php esc_html_e( 'Total subscriptions:', 'iw-theme' ); ?></strong> <?php echo esc_html( $stats['total_subscriptions'] ); ?></p>

                <form method="post" style="margin-top:15px;">
                    <?php wp_nonce_field( 'iw_refresh_passes' ); ?>
                    <input type="hidden" name="iw_refresh_passes" value="1">
                    <button class="button button-primary"><?php esc_html_e( 'Refresh Wallet Passes', 'iw-theme' ); ?></button>
                </form>
            </div>
        </div>
        <?php
    }

    private static function get_members_analytics_stats() {
        $apple_users  = get_users( [ 'meta_query' => [ [ 'key' => 'apple_wallet_device_tokens', 'compare' => 'EXISTS' ] ], 'fields' => 'ID' ] );
        $google_users = get_users( [ 'meta_query' => [ [ 'key' => 'google_wallet_object_id', 'compare' => 'EXISTS' ] ], 'fields' => 'ID' ] );

        return [
            'apple_wallet_cards'    => count( $apple_users ),
            'google_wallet_cards'   => count( $google_users ),
            'unique_wallet_cards'   => count( array_unique( array_merge( $apple_users, $google_users ) ) ),
            'member_card_templates' => self::count_posts_for_stats( 'member-card' ),
            'company_subscriptions' => self::count_posts_for_stats( 'company-subscription' ),
            'active_subscriptions'  => self::count_posts_for_stats( 'shop_subscription', [ 'wc-active' ] ),
            'total_subscriptions'   => self::count_posts_for_stats( 'shop_subscription' ),
        ];
    }

    private static function count_posts_for_stats( $post_type, $statuses = null ) {
        if ( ! post_type_exists( $post_type ) ) {
            return 0;
        }

        $counts = wp_count_posts( $post_type );
        if ( ! $counts ) {
            return 0;
        }

        if ( null === $statuses ) {
            return array_sum( array_map( 'intval', (array) $counts ) );
        }

        $total = 0;
        foreach ( $statuses as $status ) {
            $total += isset( $counts->{$status} ) ? (int) $counts->{$status} : 0;
        }

        return $total;
    }

    /**
     * Mark users for wallet update when member-card is saved
     */
    public static function flag_users_on_member_card_save($post_id, $post, $update){
        if ($post->post_type !== 'member-card') return;
        $users = get_users( [ 'meta_key' => 'member_card', 'meta_value' => $post_id, 'fields' => 'ID' ]);
        foreach ($users as $user_id) {
            update_user_meta($user_id, 'needs_wallet_update', true);
        }
    }
}

IW_Member_Card::init();

/**
 * Handle refresh button
 */
add_action('admin_init', function () {
    if (!isset($_POST['iw_refresh_passes'])) return;
    if (!current_user_can('edit_member_cards')) return;
    if (!wp_verify_nonce($_POST['_wpnonce'], 'iw_refresh_passes')) return;
    $users = get_users([ 'meta_key' => 'needs_wallet_update', 'meta_value' => true, 'fields' => 'ID' ]);

    foreach ($users as $user_id) {
        error_log( print_r( "Sending push update to user id $user_id", true ) );
        IW_Apple_Wallet_Service::push_update($user_id);
        IW_Google_Wallet_Service::push_update($user_id);
        delete_user_meta($user_id, 'needs_wallet_update');
    }
    add_action('admin_notices', function () {
        echo '<div class="notice notice-success"><p>Wallet passes updated successfully.</p></div>';
    });
});
