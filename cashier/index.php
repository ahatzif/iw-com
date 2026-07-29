<?php
/**
 * COM Cashier WebApp.
 */

require_once dirname( __DIR__ ) . '/wp-load.php';

nocache_headers();

$script_dir          = rtrim( str_replace( '\\', '/', dirname( $_SERVER['SCRIPT_NAME'] ?? '' ) ), '/' );
$cashier_assets_uri  = ( $script_dir === '' ? '' : $script_dir ) . '/assets';
$cashier_assets_path = __DIR__ . '/assets';

$cashier_css_path = $cashier_assets_path . '/css/cashier.css';
$cashier_js_path  = $cashier_assets_path . '/js/cashier.js';
$cashier_manifest_path = __DIR__ . '/manifest.webmanifest';
$cashier_sw_path = __DIR__ . '/sw.js';
$cashier_favicon_path = $cashier_assets_path . '/images/favicon-32.png';
$cashier_app_icon_path = $cashier_assets_path . '/images/app-icon-192.png';
$cashier_apple_touch_icon_path = $cashier_assets_path . '/images/apple-touch-icon.png';
$cashier_logo_path = $cashier_assets_path . '/images/com-logo.svg';

$cashier_css_version = file_exists( $cashier_css_path ) ? filemtime( $cashier_css_path ) : time();
$cashier_js_version  = file_exists( $cashier_js_path ) ? filemtime( $cashier_js_path ) : time();
$cashier_app_version_label = date_i18n( 'Y.m.d.Hi', $cashier_js_version );
$cashier_manifest_version = file_exists( $cashier_manifest_path ) ? filemtime( $cashier_manifest_path ) : $cashier_css_version;
$cashier_sw_version = file_exists( $cashier_sw_path ) ? filemtime( $cashier_sw_path ) : $cashier_js_version;
$cashier_favicon_version = file_exists( $cashier_favicon_path ) ? filemtime( $cashier_favicon_path ) : $cashier_css_version;
$cashier_app_icon_version = file_exists( $cashier_app_icon_path ) ? filemtime( $cashier_app_icon_path ) : $cashier_css_version;
$cashier_apple_touch_icon_version = file_exists( $cashier_apple_touch_icon_path ) ? filemtime( $cashier_apple_touch_icon_path ) : $cashier_css_version;
$cashier_logo_version = file_exists( $cashier_logo_path ) ? filemtime( $cashier_logo_path ) : $cashier_css_version;

$cashier_url = ( is_ssl() ? 'https://' : 'http://' ) . ( $_SERVER['HTTP_HOST'] ?? '' ) . strtok( $_SERVER['REQUEST_URI'] ?? '/cashier/', '?' );
$scanner_cap = defined( 'IW_SCANNER_CAP' ) ? IW_SCANNER_CAP : 'iw_use_scanner';
$login_error = '';

function iw_cashier_can_current_user_use(): bool {
    if ( function_exists( 'iw_scanner_user_can_scan' ) ) {
        return iw_scanner_user_can_scan();
    }

    $scanner_cap = defined( 'IW_SCANNER_CAP' ) ? IW_SCANNER_CAP : 'iw_use_scanner';
    return is_user_logged_in() && ( current_user_can( 'manage_options' ) || current_user_can( $scanner_cap ) );
}

function iw_cashier_user_can_use( WP_User $user ): bool {
    $scanner_cap = defined( 'IW_SCANNER_CAP' ) ? IW_SCANNER_CAP : 'iw_use_scanner';

    if ( user_can( $user, 'manage_options' ) ) {
        return true;
    }

    if ( defined( 'IW_SCANNER_META_REVOKED' ) && get_user_meta( $user->ID, IW_SCANNER_META_REVOKED, true ) ) {
        return false;
    }

    return user_can( $user, $scanner_cap );
}

function iw_cashier_same_origin_path( string $url ): string {
    $path     = (string) wp_parse_url( $url, PHP_URL_PATH );
    $query    = (string) wp_parse_url( $url, PHP_URL_QUERY );
    $fragment = (string) wp_parse_url( $url, PHP_URL_FRAGMENT );

    return ( $path ?: '/' ) . ( $query ? '?' . $query : '' ) . ( $fragment ? '#' . $fragment : '' );
}

function iw_cashier_lost_password_url(): string {
    if ( function_exists( 'iw_get_user_page' ) ) {
        $page_id = iw_get_user_page( 'lost-password' );
        if ( $page_id ) {
            return iw_cashier_same_origin_path( get_permalink( $page_id ) );
        }
    }

    return iw_cashier_same_origin_path( wp_lostpassword_url() );
}

function iw_cashier_site_path(): string {
    $path = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
    return $path === '' ? '' : '/' . $path;
}

function iw_cashier_legacy_logo_svg(): string {
    return '<svg class="cashier-auth-logo" width="171" height="35" viewBox="0 0 171 35" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M14.941 5.40941C11.3481 5.40941 8.05377 6.66377 5.47573 8.75052V0H0V20.1758C0 28.3314 6.68893 34.9421 14.941 34.9421C23.1931 34.9421 29.882 28.3314 29.882 20.1758C29.882 12.0202 23.1931 5.40941 14.941 5.40941ZM14.983 29.5742C9.73126 29.5742 5.47339 25.3661 5.47339 20.1758C5.47339 14.9854 9.73126 10.7773 14.983 10.7773C20.2348 10.7773 24.4926 14.9854 24.4926 20.1758C24.4926 25.3661 20.2348 29.5742 14.983 29.5742Z"/><path d="M82.2656 5.41016C74.8207 5.41016 68.7617 11.3983 68.7617 18.7561V34.9452H74.2118V18.7561C74.2118 14.3682 77.8234 10.7965 82.2656 10.7965C86.7077 10.7965 90.3193 14.3659 90.3193 18.7561V34.9452H95.7694V18.7561C95.7694 11.396 89.7127 5.41016 82.2656 5.41016Z"/><path d="M170.243 5.41016H164.754V34.9383H170.243V5.41016Z"/><path d="M154.655 5.41016L146.793 16.2013C146.233 16.9853 145.32 17.4511 144.35 17.4511H141.434V5.41016H135.953V20.1765V34.9429H141.434V22.902H144.35C145.32 22.902 146.233 23.3677 146.793 24.1517L154.655 34.9429H160.583L150.038 20.1765L160.583 5.41016H154.655Z"/><path d="M115.535 5.41016C107.283 5.41016 100.594 12.0209 100.594 20.1765C100.594 28.3321 107.283 34.9429 115.535 34.9429C119.128 34.9429 122.424 33.6885 125 31.6018V34.9429H130.476V20.1765C130.476 12.0209 123.787 5.41016 115.535 5.41016ZM115.535 29.6188C110.26 29.6188 105.981 25.3922 105.981 20.1765C105.981 14.9608 110.257 10.7343 115.535 10.7343C120.812 10.7343 125.089 14.9608 125.089 20.1765C125.089 25.3922 120.812 29.6188 115.535 29.6188Z"/><path d="M48.998 5.41016C40.7459 5.41016 34.0547 12.0232 34.0547 20.1788C34.0547 28.3344 40.7459 34.9475 48.998 34.9475C54.7117 34.9475 59.6625 31.7724 62.1776 27.117H55.47C53.7692 28.6711 51.4968 29.6257 48.998 29.6257C44.6608 29.6257 41.0026 26.755 39.8407 22.8397H63.6941C63.8527 21.9773 63.9414 21.0873 63.9414 20.1788C63.9414 12.0232 57.2524 5.41016 48.998 5.41016ZM39.8454 17.5064C41.0142 13.6027 44.6678 10.755 48.9957 10.755C53.3236 10.755 56.9771 13.6027 58.146 17.5064H39.843H39.8454Z"/></svg>';
}

function iw_cashier_logo_markup( string $assets_uri, int $version ): string {
    return sprintf(
        '<img class="cashier-auth-logo cashier-auth-logo--light" src="%1$s" width="112" height="51" alt="COM"><img class="cashier-auth-logo cashier-auth-logo--dark" src="%2$s" width="112" height="51" alt="" aria-hidden="true">',
        esc_url( $assets_uri . '/images/com-logo.svg?ver=' . $version ),
        esc_url( $assets_uri . '/images/com-logo-light.svg?ver=' . $version )
    );
}

function iw_cashier_get_building_address_data( int $building_id ): array {
    $address = function_exists( 'get_field' ) ? get_field( 'address', $building_id ) : get_post_meta( $building_id, 'address', true );
    $data    = [
        'label'     => '',
        'latitude'  => null,
        'longitude' => null,
    ];

    if ( is_array( $address ) ) {
        $data['label'] = wp_strip_all_tags(
            (string) (
                $address['title']
                ?? $address['address']
                ?? $address['formatted_address']
                ?? ''
            )
        );

        $latitude  = $address['lat'] ?? ( $address['latitude'] ?? null );
        $longitude = $address['lng'] ?? ( $address['longitude'] ?? null );

        if ( is_numeric( $latitude ) && is_numeric( $longitude ) ) {
            $data['latitude']  = (float) $latitude;
            $data['longitude'] = (float) $longitude;
        }
    } elseif ( is_string( $address ) ) {
        $data['label'] = wp_strip_all_tags( $address );
    }

    return $data;
}

function iw_cashier_get_buildings(): array {
    $buildings = [];
    $per_page  = 100;
    $page      = 1;
    $supported_post_types = class_exists( 'IW_Ticketing' )
        ? IW_Ticketing::get_supported_post_types()
        : [];
    $location_post_type = in_array( 'museum', $supported_post_types, true )
        ? 'museum'
        : 'building';

    do {
        $request = new WP_REST_Request( 'GET', '/wp/v2/' . $location_post_type );
        $request->set_query_params(
            [
                'per_page' => $per_page,
                'page'     => $page,
                '_embed'   => 1,
            ]
        );

        $response = rest_do_request( $request );
        if ( $response->is_error() ) {
            break;
        }

        $data = rest_get_server()->response_to_data( $response, true );
        if ( ! is_array( $data ) || ! $data ) {
            break;
        }

        $buildings = array_merge( $buildings, $data );
        $page++;
    } while ( count( $data ) === $per_page );

    return array_values(
        array_filter(
            array_map(
                static function ( array $building ): array {
                    $building_id = (int) ( $building['id'] ?? 0 );
                    $address     = $building_id ? iw_cashier_get_building_address_data( $building_id ) : [];

                    return [
                        'id'        => $building_id,
                        'title'     => html_entity_decode(
                            wp_strip_all_tags( $building['title']['rendered'] ?? __( 'Museum space', 'iw-theme' ) ),
                            ENT_QUOTES | ENT_HTML5,
                            get_bloginfo( 'charset' )
                        ),
                        'address'   => (string) ( $address['label'] ?? '' ),
                        'latitude'  => $address['latitude'] ?? null,
                        'longitude' => $address['longitude'] ?? null,
                    ];
                },
                $buildings
            )
        )
    );
}

function iw_cashier_logout_url( string $cashier_url ): string {
    return add_query_arg(
        [
            'cashier_logout' => '1',
            '_wpnonce'       => wp_create_nonce( 'iw_cashier_logout' ),
        ],
        $cashier_url
    );
}

if ( isset( $_GET['cashier_logout'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'iw_cashier_logout' ) ) {
    wp_logout();
    wp_safe_redirect( $cashier_url );
    exit;
}

if ( ( $_SERVER['REQUEST_METHOD'] ?? '' ) === 'POST' && ( $_POST['iw_cashier_action'] ?? '' ) === 'login' ) {
    if ( ! isset( $_POST['iw_cashier_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['iw_cashier_nonce'] ) ), 'iw_cashier_login' ) ) {
        $login_error = __( 'Your session expired. Refresh and try again.', 'iw-theme' );
    } else {
        $user_login = sanitize_text_field( wp_unslash( $_POST['user_email'] ?? '' ) );
        $password   = (string) wp_unslash( $_POST['user_password'] ?? '' );
        $user       = wp_signon(
            [
                'user_login'    => $user_login,
                'user_password' => $password,
                'rememberme'    => ! empty( $_POST['rememberme'] ),
            ],
            is_ssl()
        );

        if ( is_wp_error( $user ) ) {
            $login_error = __( 'Invalid username or password.', 'iw-theme' );
        } elseif ( ! iw_cashier_user_can_use( $user ) ) {
            wp_logout();
            $login_error = __( 'This account does not have cashier access.', 'iw-theme' );
        } else {
            wp_safe_redirect( $cashier_url );
            exit;
        }
    }
}

$is_logged_in = is_user_logged_in();
$can_cashier  = iw_cashier_can_current_user_use();
$current_user = wp_get_current_user();
$cashier_buildings = iw_cashier_get_buildings();
$cashier_default_building = $cashier_buildings[0] ?? null;
$cashier_logout_url = iw_cashier_logout_url( $cashier_url );

if ( $is_logged_in && ! $can_cashier ) {
    status_header( 403 );
}

$cashier_config = [
    'ajaxUrl' => iw_cashier_site_path() . '/wp-admin/admin-ajax.php',
    'restNonce' => wp_create_nonce( 'wp_rest' ),
    'passwordNonce' => wp_create_nonce( 'iw-auth-change-password' ),
    'endpoints' => [
        'config' => iw_cashier_site_path() . '/' . rest_get_url_prefix() . '/iw/v1/cashier/config',
        'tickets' => iw_cashier_site_path() . '/' . rest_get_url_prefix() . '/iw/v1/cashier/tickets',
        'ticket' => iw_cashier_site_path() . '/' . rest_get_url_prefix() . '/iw/v1/cashier/tickets/',
        'order' => iw_cashier_site_path() . '/' . rest_get_url_prefix() . '/iw/v1/cashier/orders/',
        'zebraJob' => iw_cashier_site_path() . '/' . rest_get_url_prefix() . '/iw/v1/cashier/zebra/jobs/',
        'issue' => iw_cashier_site_path() . '/' . rest_get_url_prefix() . '/iw/v1/cashier/issue',
        'verifyMemberCard' => iw_cashier_site_path() . '/' . rest_get_url_prefix() . '/iw/v1/verify-member-card',
        'searchMemberCard' => iw_cashier_site_path() . '/' . rest_get_url_prefix() . '/iw/v1/search-member-card',
    ],
    'currentUser' => [
        'id' => get_current_user_id(),
        'name' => $current_user instanceof WP_User ? $current_user->display_name : '',
        'firstName' => $current_user instanceof WP_User ? $current_user->first_name : '',
        'lastName' => $current_user instanceof WP_User ? $current_user->last_name : '',
        'email' => $current_user instanceof WP_User ? $current_user->user_email : '',
    ],
    'zebraPrint' => class_exists( 'IW_Zebra_Print_Queue' ) ? IW_Zebra_Print_Queue::get_public_config() : [
        'mode' => 'direct',
        'defaultPrinterId' => 'default',
        'targets' => [],
    ],
    'buildings' => $cashier_buildings,
    'selectedBuilding' => $cashier_default_building,
    'assetsUrl' => $cashier_assets_uri,
    'serviceWorkerUrl' => ( $script_dir === '' ? '' : $script_dir ) . '/sw.js?ver=' . $cashier_sw_version,
    'logoutUrl' => $cashier_logout_url,
    'appVersion' => (string) $cashier_js_version,
    'appVersionLabel' => $cashier_app_version_label,
];
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#f6f4ef">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="COM Cashier">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>COM Cashier</title>
    <link rel="manifest" href="<?php echo esc_url( ( $script_dir === '' ? '' : $script_dir ) . '/manifest.webmanifest?ver=' . $cashier_manifest_version ); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url( $cashier_assets_uri . '/images/favicon-32.png?ver=' . $cashier_favicon_version ); ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo esc_url( $cashier_assets_uri . '/images/app-icon-192.png?ver=' . $cashier_app_icon_version ); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( $cashier_assets_uri . '/images/apple-touch-icon.png?ver=' . $cashier_apple_touch_icon_version ); ?>">
    <link rel="stylesheet" href="<?php echo esc_url( $cashier_assets_uri . '/css/cashier.css?ver=' . $cashier_css_version ); ?>">
</head>
<body class="<?php echo esc_attr( $is_logged_in && $can_cashier ? 'cashier-is-app' : 'cashier-is-auth' ); ?>">
    <main class="cashier-shell">
        <header class="cashier-topbar">
            <a class="cashier-brand" href="<?php echo esc_url( $cashier_url ); ?>" aria-label="COM Cashier">
                <span class="cashier-brand-mark" aria-hidden="true">C</span>
                <span>
                    <strong>COM</strong>
                    <small>Cashier</small>
                </span>
            </a>
            <?php if ( $is_logged_in && $can_cashier ) : ?>
                <div class="cashier-user">
                    <span><?php echo esc_html( $current_user instanceof WP_User ? $current_user->display_name : '' ); ?></span>
                    <a class="cashier-icon-button" href="<?php echo esc_url( $cashier_logout_url ); ?>" aria-label="<?php esc_attr_e( 'Log out', 'iw-theme' ); ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>
                    </a>
                </div>
            <?php endif; ?>
        </header>

        <?php if ( ! $is_logged_in ) : ?>
            <section class="cashier-auth">
                <header class="cashier-auth-header">
                    <div class="cashier-auth-logo-wrap">
                        <?php echo iw_cashier_logo_markup( $cashier_assets_uri, $cashier_logo_version ); ?>
                    </div>
                </header>
                <div class="cashier-auth-body">
                    <div class="cashier-login-content">
                        <div class="cashier-login-hero">
                            <div class="cashier-ticket-printer" aria-hidden="true">
                                <span class="cashier-printer-ticket">
                                    <svg class="cashier-printer-ticket-icon" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4Z"></path>
                                        <path d="M13 5v14"></path>
                                    </svg>
                                    <span></span>
                                    <span></span>
                                </span>
                                <span class="cashier-printer-body">
                                    <span class="cashier-printer-slot"></span>
                                    <span class="cashier-printer-light"></span>
                                </span>
                            </div>

                            <h1><?php esc_html_e( 'Welcome to Cashier', 'iw-theme' ); ?></h1>
                            <p><?php esc_html_e( 'Sign in with an authorized cashier account to continue.', 'iw-theme' ); ?></p>
                        </div>

                        <form class="cashier-login" method="post" action="<?php echo esc_url( $cashier_url ); ?>">
                            <input type="hidden" name="iw_cashier_action" value="login">
                            <input type="hidden" name="iw_cashier_nonce" value="<?php echo esc_attr( wp_create_nonce( 'iw_cashier_login' ) ); ?>">
                            <label>
                                <span><?php esc_html_e( 'Username', 'iw-theme' ); ?></span>
                                <input type="text" name="user_email" autocomplete="username" required>
                            </label>
                            <label>
                                <span><?php esc_html_e( 'Password', 'iw-theme' ); ?></span>
                                <input type="password" name="user_password" autocomplete="current-password" required>
                            </label>
                            <div class="cashier-login-row">
                                <label class="cashier-check">
                                    <input type="checkbox" name="rememberme" value="1">
                                    <span><?php esc_html_e( 'Keep me signed in', 'iw-theme' ); ?></span>
                                </label>
                                <p class="cashier-forgot">
                                    <?php esc_html_e( 'Forgot your password?', 'iw-theme' ); ?><br>
                                    <a href="<?php echo esc_url( iw_cashier_lost_password_url() ); ?>"><?php esc_html_e( 'Visit the website to reset it.', 'iw-theme' ); ?></a>
                                </p>
                            </div>
                            <?php if ( $login_error ) : ?>
                                <p class="cashier-error"><?php echo esc_html( $login_error ); ?></p>
                            <?php endif; ?>
                            <button class="cashier-primary" type="submit"><?php esc_html_e( 'Log in', 'iw-theme' ); ?></button>
                        </form>
                        <div class="cashier-production-footer" aria-label="<?php esc_attr_e( 'Interweave production', 'iw-theme' ); ?>">
                            <?php esc_html_e( 'created by', 'iw-theme' ); ?>
                            <a href="https://interweaveagency.com/">INTERWEAVE</a>
                        </div>
                    </div>
                </div>
            </section>
        <?php elseif ( ! $can_cashier ) : ?>
            <section class="cashier-empty">
                <div class="cashier-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg>
                </div>
                <h1><?php esc_html_e( 'No cashier access', 'iw-theme' ); ?></h1>
                <p><?php esc_html_e( 'Ask an administrator to assign the Scanner Operator role to this account.', 'iw-theme' ); ?></p>
                <a class="cashier-primary" href="<?php echo esc_url( $cashier_logout_url ); ?>"><?php esc_html_e( 'Use another account', 'iw-theme' ); ?></a>
            </section>
        <?php else : ?>
            <div id="cashier-app"></div>
            <script type="application/json" id="iw-cashier-config"><?php echo wp_json_encode( $cashier_config ); ?></script>
            <script type="module" src="<?php echo esc_url( $cashier_assets_uri . '/js/cashier.js?ver=' . $cashier_js_version ); ?>"></script>
        <?php endif; ?>
    </main>
</body>
</html>
