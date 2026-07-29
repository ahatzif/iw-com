<?php
/**
 * Member Card WebApp - Scanner.
 */

require_once dirname( __DIR__ ) . '/wp-load.php';

nocache_headers();

$script_dir          = rtrim( str_replace( '\\', '/', dirname( $_SERVER['SCRIPT_NAME'] ?? '' ) ), '/' );
$scanner_assets_uri  = ( $script_dir === '' ? '' : $script_dir ) . '/assets';
$scanner_assets_path = __DIR__ . '/assets';

$scanner_css_path = $scanner_assets_path . '/css/scanner.css';
$scanner_js_path  = $scanner_assets_path . '/js/scanner.js';
$scanner_auth_js_path = $scanner_assets_path . '/js/auth.js';
$scanner_graphic_path = $scanner_assets_path . '/images/scanner.svg';
$scanner_frame_path = $scanner_assets_path . '/images/frame.svg';
$scanner_iw_path = $scanner_assets_path . '/images/iw.svg';
$scanner_logo_path = $scanner_assets_path . '/images/com-logo.svg';
$scanner_favicon_path = $scanner_assets_path . '/images/favicon-32.png';
$scanner_apple_touch_icon_path = $scanner_assets_path . '/images/apple-touch-icon.png';
$scanner_app_icon_path = $scanner_assets_path . '/images/app-icon-192.png';
$scanner_manifest_path = __DIR__ . '/manifest.webmanifest';
$scanner_sw_path = __DIR__ . '/sw.js';

$scanner_css_version = file_exists( $scanner_css_path ) ? filemtime( $scanner_css_path ) : time();
$scanner_js_version  = file_exists( $scanner_js_path ) ? filemtime( $scanner_js_path ) : time();
$scanner_app_version_label = date_i18n( 'Y.m.d.Hi', $scanner_js_version );
$scanner_auth_js_version = file_exists( $scanner_auth_js_path ) ? filemtime( $scanner_auth_js_path ) : $scanner_js_version;
$scanner_graphic_version = file_exists( $scanner_graphic_path ) ? filemtime( $scanner_graphic_path ) : $scanner_css_version;
$scanner_frame_version = file_exists( $scanner_frame_path ) ? filemtime( $scanner_frame_path ) : $scanner_graphic_version;
$scanner_iw_version = file_exists( $scanner_iw_path ) ? filemtime( $scanner_iw_path ) : $scanner_css_version;
$scanner_logo_version = file_exists( $scanner_logo_path ) ? filemtime( $scanner_logo_path ) : $scanner_css_version;
$scanner_favicon_version = file_exists( $scanner_favicon_path ) ? filemtime( $scanner_favicon_path ) : $scanner_css_version;
$scanner_apple_touch_icon_version = file_exists( $scanner_apple_touch_icon_path ) ? filemtime( $scanner_apple_touch_icon_path ) : $scanner_css_version;
$scanner_app_icon_version = file_exists( $scanner_app_icon_path ) ? filemtime( $scanner_app_icon_path ) : $scanner_css_version;
$scanner_manifest_version = file_exists( $scanner_manifest_path ) ? filemtime( $scanner_manifest_path ) : $scanner_css_version;
$scanner_sw_version = file_exists( $scanner_sw_path ) ? filemtime( $scanner_sw_path ) : $scanner_js_version;

$scanner_url = ( is_ssl() ? 'https://' : 'http://' ) . ( $_SERVER['HTTP_HOST'] ?? '' ) . strtok( $_SERVER['REQUEST_URI'] ?? '/scanner/', '?' );
$scanner_cap = defined( 'IW_SCANNER_CAP' ) ? IW_SCANNER_CAP : 'iw_use_scanner';
$login_error = '';

function iw_scanner_can_current_user_scan(): bool {
	if ( function_exists( 'iw_scanner_user_can_scan' ) ) {
		return iw_scanner_user_can_scan();
	}

	return is_user_logged_in() && current_user_can( 'manage_options' );
}

function iw_scanner_same_origin_path( string $url ): string {
	$path     = (string) wp_parse_url( $url, PHP_URL_PATH );
	$query    = (string) wp_parse_url( $url, PHP_URL_QUERY );
	$fragment = (string) wp_parse_url( $url, PHP_URL_FRAGMENT );

	return ( $path ?: '/' ) . ( $query ? '?' . $query : '' ) . ( $fragment ? '#' . $fragment : '' );
}

function iw_scanner_lost_password_url(): string {
	if ( function_exists( 'iw_get_user_page' ) ) {
		$page_id = iw_get_user_page( 'lost-password' );
		if ( $page_id ) {
			return iw_scanner_same_origin_path( get_permalink( $page_id ) );
		}
	}

	return iw_scanner_same_origin_path( wp_lostpassword_url() );
}

function iw_scanner_site_path(): string {
	$path = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	return $path === '' ? '' : '/' . $path;
}

function iw_scanner_logo_svg(): string {
	return '<svg class="h-auto w-[112px] fill-current" width="171" height="35" viewBox="0 0 171 35" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M14.941 5.40941C11.3481 5.40941 8.05377 6.66377 5.47573 8.75052V0H0V20.1758C0 28.3314 6.68893 34.9421 14.941 34.9421C23.1931 34.9421 29.882 28.3314 29.882 20.1758C29.882 12.0202 23.1931 5.40941 14.941 5.40941ZM14.983 29.5742C9.73126 29.5742 5.47339 25.3661 5.47339 20.1758C5.47339 14.9854 9.73126 10.7773 14.983 10.7773C20.2348 10.7773 24.4926 14.9854 24.4926 20.1758C24.4926 25.3661 20.2348 29.5742 14.983 29.5742Z"/><path d="M82.2656 5.41016C74.8207 5.41016 68.7617 11.3983 68.7617 18.7561V34.9452H74.2118V18.7561C74.2118 14.3682 77.8234 10.7965 82.2656 10.7965C86.7077 10.7965 90.3193 14.3659 90.3193 18.7561V34.9452H95.7694V18.7561C95.7694 11.396 89.7127 5.41016 82.2656 5.41016Z"/><path d="M170.243 5.41016H164.754V34.9383H170.243V5.41016Z"/><path d="M154.655 5.41016L146.793 16.2013C146.233 16.9853 145.32 17.4511 144.35 17.4511H141.434V5.41016H135.953V20.1765V34.9429H141.434V22.902H144.35C145.32 22.902 146.233 23.3677 146.793 24.1517L154.655 34.9429H160.583L150.038 20.1765L160.583 5.41016H154.655Z"/><path d="M115.535 5.41016C107.283 5.41016 100.594 12.0209 100.594 20.1765C100.594 28.3321 107.283 34.9429 115.535 34.9429C119.128 34.9429 122.424 33.6885 125 31.6018V34.9429H130.476V20.1765C130.476 12.0209 123.787 5.41016 115.535 5.41016ZM115.535 29.6188C110.26 29.6188 105.981 25.3922 105.981 20.1765C105.981 14.9608 110.257 10.7343 115.535 10.7343C120.812 10.7343 125.089 14.9608 125.089 20.1765C125.089 25.3922 120.812 29.6188 115.535 29.6188Z"/><path d="M48.998 5.41016C40.7459 5.41016 34.0547 12.0232 34.0547 20.1788C34.0547 28.3344 40.7459 34.9475 48.998 34.9475C54.7117 34.9475 59.6625 31.7724 62.1776 27.117H55.47C53.7692 28.6711 51.4968 29.6257 48.998 29.6257C44.6608 29.6257 41.0026 26.755 39.8407 22.8397H63.6941C63.8527 21.9773 63.9414 21.0873 63.9414 20.1788C63.9414 12.0232 57.2524 5.41016 48.998 5.41016ZM39.8454 17.5064C41.0142 13.6027 44.6678 10.755 48.9957 10.755C53.3236 10.755 56.9771 13.6027 58.146 17.5064H39.843H39.8454Z"/></svg>';
}

function iw_scanner_com_logo_markup( string $assets_uri, int $version ): string {
	return sprintf(
		'<img class="scanner-logo scanner-logo--default" src="%1$s" width="112" height="51" alt="COM"><img class="scanner-logo scanner-logo--light" src="%2$s" width="112" height="51" alt="" aria-hidden="true">',
		esc_url( $assets_uri . '/images/com-logo.svg?ver=' . $version ),
		esc_url( $assets_uri . '/images/com-logo-light.svg?ver=' . $version )
	);
}

function iw_scanner_icon_svg( string $class = 'scanner-icon-blob' ): string {
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 136 136" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="var(--scanner-brand-soft)" d="M74.9 7.8c20.4 4.6 36.9 21.1 43.9 40.8 7 19.6 4.5 42.4-9.8 57.6-14.4 15.3-40.6 23.2-61.6 15.1C26.5 113.2 10.8 89.1 13 66.4 15.2 43.7 35.4 22.5 57 12c6.6-3.2 11.5-5.5 17.9-4.2Z"/><g fill="none" stroke="currentColor" stroke-linecap="square" stroke-width="6"><path d="M43 51V39h12"/><path d="M81 39h12v12"/><path d="M93 81v12H81"/><path d="M55 93H43V81"/></g><path fill="currentColor" d="M49 61h38v18H49z"/><path fill="none" stroke="currentColor" stroke-width="4" d="M46 61h44"/><path fill="none" stroke="currentColor" stroke-width="4" d="M46 79h44"/></svg>';
}

function iw_scanner_auth_screen_class( string $screen, string $active_screen ): string {
	$order        = [
		'login'   => 0,
		'loading' => 1,
	];
	$screen_index = $order[ $screen ] ?? 0;
	$active_index = $order[ $active_screen ] ?? 0;

	if ( $screen_index === $active_index ) {
		return 'scanner-screen is-active';
	}

	return 'scanner-screen ' . ( $screen_index < $active_index ? 'is-left' : 'is-right' );
}

function iw_scanner_get_building_address_data( int $building_id ): array {
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

function iw_scanner_get_buildings(): array {
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
					$media = $building['_embedded']['wp:featuredmedia'][0] ?? [];
					$sizes = is_array( $media ) ? ( $media['media_details']['sizes'] ?? [] ) : [];
					$image = $sizes['medium_large']['source_url']
						?? $sizes['large']['source_url']
						?? $sizes['medium']['source_url']
						?? ( $media['source_url'] ?? '' );
					$address = $building_id ? iw_scanner_get_building_address_data( $building_id ) : [];

					return [
						'id'        => $building_id,
						'title'     => html_entity_decode(
							wp_strip_all_tags( $building['title']['rendered'] ?? __( 'Museum space', 'iw-theme' ) ),
							ENT_QUOTES | ENT_HTML5,
							get_bloginfo( 'charset' )
						),
						'image'     => esc_url_raw( $image ),
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

if ( isset( $_GET['scanner_logout'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'iw_scanner_logout' ) ) {
	wp_logout();
	wp_safe_redirect( $scanner_url );
	exit;
}

if ( ( $_SERVER['REQUEST_METHOD'] ?? '' ) === 'POST' && ( $_POST['iw_scanner_action'] ?? '' ) === 'login' ) {
	if ( ! isset( $_POST['iw_scanner_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['iw_scanner_nonce'] ) ), 'iw_scanner_login' ) ) {
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
			$login_error = $user->get_error_code() === 'activation_failed'
				? $user->get_error_message()
				: __( 'Invalid username or password.', 'iw-theme' );
		} elseif ( ! user_can( $user, $scanner_cap ) && ! user_can( $user, 'manage_options' ) ) {
			wp_logout();
			$login_error = __( 'This account does not have scanner access.', 'iw-theme' );
		} else {
			$redirect_to = apply_filters( 'iw_custom_auth_login_redirect', $scanner_url, $user );
			wp_safe_redirect( $redirect_to ?: $scanner_url );
			exit;
		}
	}
}

$is_logged_in = is_user_logged_in();
$can_scan     = iw_scanner_can_current_user_scan();
$current_user = wp_get_current_user();
$scanner_buildings = iw_scanner_get_buildings();
$scanner_default_building = $scanner_buildings[0] ?? null;
$scanner_account_phone = '';
if ( $current_user instanceof WP_User && $current_user->ID ) {
	$scanner_account_phone = class_exists( 'IW_Custom_Auth_Activation' )
		? (string) get_user_meta( $current_user->ID, IW_Custom_Auth_Activation::META_PHONE, true )
		: '';
	$scanner_account_phone = $scanner_account_phone ?: (string) get_user_meta( $current_user->ID, 'billing_phone', true );
}
$scanner_logout_url = add_query_arg(
	[
		'scanner_logout' => '1',
		'_wpnonce'       => wp_create_nonce( 'iw_scanner_logout' ),
	],
	$scanner_url
);

if ( $is_logged_in && ! $can_scan ) {
	status_header( 403 );
}

$scanner_config = [
	'restNonce' => wp_create_nonce( 'wp_rest' ),
	'endpoints' => [
		'verifyTicket'     => iw_scanner_site_path() . '/' . rest_get_url_prefix() . '/iw/v1/verify-ticket',
		'resetTicket'      => iw_scanner_site_path() . '/' . rest_get_url_prefix() . '/iw/v1/reset-ticket',
		'verifyMemberCard' => iw_scanner_site_path() . '/' . rest_get_url_prefix() . '/iw/v1/verify-member-card',
		'searchMemberCard' => iw_scanner_site_path() . '/' . rest_get_url_prefix() . '/iw/v1/search-member-card',
		'heartbeat'        => iw_scanner_site_path() . '/' . rest_get_url_prefix() . '/iw/v1/scanner-heartbeat',
		'notices'          => iw_scanner_site_path() . '/' . rest_get_url_prefix() . '/iw/v1/scanner-notices',
		'notifications'    => iw_scanner_site_path() . '/' . rest_get_url_prefix() . '/iw/v1/scanner-notifications',
	],
	'currentUser' => [
		'id'        => get_current_user_id(),
		'name'      => $current_user instanceof WP_User ? $current_user->display_name : '',
		'firstName' => $current_user instanceof WP_User ? $current_user->first_name : '',
		'lastName'  => $current_user instanceof WP_User ? $current_user->last_name : '',
		'email'     => $current_user instanceof WP_User ? $current_user->user_email : '',
		'phone'     => $scanner_account_phone,
	],
	'buildings' => $scanner_buildings,
	'selectedBuilding' => $scanner_default_building,
	'ajaxUrl' => iw_scanner_site_path() . '/wp-admin/admin-ajax.php',
	'accountPhoneNonce' => wp_create_nonce( 'iw-auth-account-phone' ),
	'serviceWorkerUrl' => ( $script_dir === '' ? '' : $script_dir ) . '/sw.js?ver=' . $scanner_sw_version,
	'appVersion' => (string) $scanner_js_version,
	'appVersionLabel' => $scanner_app_version_label,
	'i18nBaseUrl' => $scanner_assets_uri . '/i18n',
	'defaultLocale' => 'el',
	'supportedLocales' => [ 'el', 'en' ],
];

$scanner_auth_config = [
	'ajaxUrl'    => iw_scanner_site_path() . '/wp-admin/admin-ajax.php',
	'scannerUrl' => $scanner_url,
];

$initial_auth_screen   = 'login';
$scanner_uses_loader   = ! $is_logged_in || ( $is_logged_in && $can_scan );
$scanner_shell_classes = [ 'scanner-phone' ];

if ( $is_logged_in && $can_scan ) {
	$scanner_shell_classes[] = 'scanner-phone--app';
}

if ( $scanner_uses_loader ) {
	$scanner_shell_classes[] = 'scanner-phone--booting';
}

$scanner_shell_class = implode( ' ', $scanner_shell_classes );
?>
<!DOCTYPE html>
<html lang="el" class="h-full bg-scanner-bg text-ink">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
	<meta name="theme-color" content="#173276" />
	<meta name="mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-title" content="COM Scanner" />
	<meta name="apple-mobile-web-app-status-bar-style" content="default" />
	<title>COM Scanner</title>
	<script>
		(function () {
			try {
				var mode = window.localStorage.getItem('iwScannerThemeMode') || 'default';
				var isDark = mode === 'dark' || (mode === 'default' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
				document.documentElement.dataset.scannerThemeMode = mode;
				document.documentElement.dataset.scannerTheme = isDark ? 'dark' : 'light';
			} catch (error) {}
		}());
	</script>
	<link rel="manifest" href="<?php echo esc_url( ( $script_dir === '' ? '' : $script_dir ) . '/manifest.webmanifest?ver=' . $scanner_manifest_version ); ?>">
	<link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url( $scanner_assets_uri . '/images/favicon-32.png?ver=' . $scanner_favicon_version ); ?>">
	<link rel="icon" type="image/png" sizes="192x192" href="<?php echo esc_url( $scanner_assets_uri . '/images/app-icon-192.png?ver=' . $scanner_app_icon_version ); ?>">
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( $scanner_assets_uri . '/images/apple-touch-icon.png?ver=' . $scanner_apple_touch_icon_version ); ?>">
	<link rel="stylesheet" href="<?php echo esc_url( $scanner_assets_uri . '/css/scanner.css?ver=' . $scanner_css_version ); ?>">
</head>
<body class="min-h-[100dvh] overflow-hidden bg-scanner-bg font-sans text-ink antialiased">
	<main class="scanner-page">
		<div class="<?php echo esc_attr( $scanner_shell_class ); ?>" data-scanner-shell>
			<?php if ( $scanner_uses_loader ) : ?>
				<div class="scanner-boot-loader" data-scanner-boot-loader role="status" aria-live="polite">
					<div class="scanner-boot-loader-mark" aria-hidden="true">
						<span class="scanner-boot-loader-spinner"></span>
					</div>
					<span class="scanner-boot-loader-text"><?php esc_html_e( 'Loading scanner', 'iw-theme' ); ?></span>
				</div>
				<script>
					window.setTimeout(function () {
						var shell = document.querySelector('[data-scanner-shell]');
						if (shell) shell.classList.add('scanner-phone--boot-timeout');
					}, 6500);
				</script>
			<?php endif; ?>
			<header class="scanner-header">
                <div class="scanner-header-inner relative">
                    <div class="scanner-header-logo">
                        <?php echo iw_scanner_com_logo_markup( $scanner_assets_uri, $scanner_logo_version ); ?>
                    </div>
                    <?php if ( $is_logged_in && $can_scan ) : ?>
                        <button class="scanner-header-button" type="button" data-logout-open aria-label="<?php esc_attr_e( 'Log out', 'iw-theme' ); ?>" data-i18n-aria-label="settings.logout">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-[16px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <path d="M16 17l5-5-5-5"></path>
                                <path d="M21 12H9"></path>
                            </svg>
                        </button>
                    <?php endif; ?>
                </div>
			</header>

			<section class="scanner-body">
				<?php if ( ! $is_logged_in ) : ?>
					<div class="scanner-screen-stack" data-auth-stack data-initial-screen="<?php echo esc_attr( $initial_auth_screen ); ?>">
						<article class="<?php echo esc_attr( iw_scanner_auth_screen_class( 'login', $initial_auth_screen ) ); ?>" data-auth-screen="login">
							<div class="scanner-screen-content scanner-login-content">
								<div class="scanner-login-hero">
									<div class="scanner-login-visual" aria-hidden="true">
										<img class="scanner-login-graphic" src="<?php echo esc_url( $scanner_assets_uri . '/images/scanner.svg?ver=' . $scanner_graphic_version ); ?>" alt="">
										<div class="scanner-login-frame-wrap">
											<img class="scanner-login-frame" src="<?php echo esc_url( $scanner_assets_uri . '/images/frame.svg?ver=' . $scanner_frame_version ); ?>" alt="">
											<span class="scanner-login-scan-line"></span>
										</div>
									</div>

									<h1 class="scanner-title"><?php esc_html_e( 'Welcome to Scanner', 'iw-theme' ); ?></h1>
									<p class="scanner-copy">
										<?php esc_html_e( 'Sign in with an authorized scanner account to continue.', 'iw-theme' ); ?>
									</p>
								</div>

								<form id="scanner-login-form" class="scanner-login-form" method="post" action="<?php echo esc_url( $scanner_url ); ?>">
									<input type="hidden" name="iw_scanner_action" value="login">
									<input type="hidden" name="iw_scanner_nonce" value="<?php echo esc_attr( wp_create_nonce( 'iw_scanner_login' ) ); ?>">



									<label class="scanner-field-label">
										<span><?php esc_html_e( 'Username', 'iw-theme' ); ?></span>
										<input class="scanner-field-input" type="text" name="user_email" autocomplete="username" required>
									</label>
									<label class="scanner-field-label">
										<span><?php esc_html_e( 'Password', 'iw-theme' ); ?></span>
										<input class="scanner-field-input" type="password" name="user_password" autocomplete="current-password" required>
									</label>
                                    <div class="flex justify-between items-start">
                                        <label class="scanner-checkbox-row">
                                            <input type="checkbox" name="rememberme" value="1">
                                            <?php esc_html_e( 'Keep me signed in', 'iw-theme' ); ?>
                                        </label>
                                        <p class="scanner-forgot text-right">
                                            <?php esc_html_e( 'Forgot your password?', 'iw-theme' ); ?><br/>
                                            <a href="<?php echo esc_url( iw_scanner_lost_password_url() ); ?>">
                                                <?php esc_html_e( 'Visit the website to reset it.', 'iw-theme' ); ?>
                                            </a>
                                        </p>
                                    </div>

                                    <div class="scanner-login-error <?php echo $login_error ? '' : 'is-empty'; ?>" data-scanner-login-error aria-live="polite" aria-hidden="<?php echo $login_error ? 'false' : 'true'; ?>">
                                        <?php echo $login_error ? wp_kses_post( $login_error ) : ''; ?>
                                    </div>
                                    <button class="scanner-primary-button mt-10" type="submit">
                                        <?php esc_html_e( 'Log in', 'iw-theme' ); ?>
                                    </button>

								</form>
                                <div class="scanner-production-footer" aria-label="<?php esc_attr_e( 'Interweave production', 'iw-theme' ); ?>">
                                    <!--<img src="<?php /*echo esc_url( $scanner_assets_uri . '/images/iw.svg?ver=' . $scanner_iw_version ); */?>" alt="" aria-hidden="true">-->
                                    created by
                                    <a href="https://interweaveagency.com/">INTERWEAVE</a>
                                </div>
							</div>
						</article>

						<article class="<?php echo esc_attr( iw_scanner_auth_screen_class( 'loading', $initial_auth_screen ) ); ?>" data-auth-screen="loading">
							<div class="scanner-screen-content scanner-screen-content--center scanner-loading-content">
								<div class="scanner-boot-loader-mark" aria-hidden="true">
									<span class="scanner-boot-loader-spinner"></span>
								</div>
								<span class="scanner-boot-loader-text"><?php esc_html_e( 'Loading scanner', 'iw-theme' ); ?></span>
							</div>
						</article>
					</div>

					<script type="application/json" id="iw-scanner-auth-config"><?php echo wp_json_encode( $scanner_auth_config ); ?></script>
					<script type="module" src="<?php echo esc_url( $scanner_assets_uri . '/js/auth.js?ver=' . $scanner_auth_js_version ); ?>"></script>
				<?php elseif ( ! $can_scan ) : ?>
					<div class="scanner-auth-screen">
						<div class="scanner-screen-content scanner-screen-content--center">
							<div class="mb-[28px] flex size-[118px] items-center justify-center rounded-[42px] bg-error/10 text-error">
								<svg xmlns="http://www.w3.org/2000/svg" class="size-[58px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
									<path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
								</svg>
							</div>
							<h1 class="scanner-title"><?php esc_html_e( 'No scanner access', 'iw-theme' ); ?></h1>
							<p class="scanner-copy">
								<?php esc_html_e( 'Ask an administrator to assign the Scanner Operator role to this account.', 'iw-theme' ); ?>
							</p>
						</div>
						<div class="scanner-bottom-rail">
							<a class="scanner-primary-button" href="<?php echo esc_url( $scanner_logout_url ); ?>">
								<?php esc_html_e( 'Use another account', 'iw-theme' ); ?>
							</a>
						</div>
					</div>
				<?php else : ?>
					<div class="scanner-auth-screen scanner-app-screen" data-scanner-auth-screen>
						<div class="scanner-app-shell" data-scanner-app>
							<div class="scanner-ribbon-stack" data-scanner-ribbons aria-live="polite"></div>
							<div class="scanner-app-main">
								<section class="scanner-app-view is-active" data-scanner-view="scan" aria-labelledby="scanner-scan-title">
									<div class="scanner-app-titlebar">
										<div class="scanner-scan-heading">
											<div class="scanner-title-rule" aria-hidden="true"></div>
											<h1 id="scanner-scan-title" class="scanner-app-title" data-i18n="scan.title"><?php esc_html_e( 'Scan QR code', 'iw-theme' ); ?></h1>
											<p id="scanner-status" class="scanner-app-status"><?php esc_html_e( 'Ready to scan', 'iw-theme' ); ?></p>
										</div>
										<button class="scanner-location-button" type="button" data-location-open aria-haspopup="dialog">
											<span class="scanner-location-icon" aria-hidden="true">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s7-5.1 7-11a7 7 0 1 0-14 0c0 5.9 7 11 7 11z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
											</span>
											<span>
												<strong data-selected-building-name><?php echo esc_html( $scanner_default_building['title'] ?? __( 'Choose museum', 'iw-theme' ) ); ?></strong>
												<small data-selected-building-address><?php echo esc_html( $scanner_default_building['address'] ?? __( 'Tap to select location', 'iw-theme' ) ); ?></small>
											</span>
											<svg class="scanner-location-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
										</button>
									</div>

									<div class="scanner-camera-frame">
										<video id="preview" class="h-full w-full object-cover" autoplay playsinline muted></video>

										<div id="camera-placeholder" class="absolute inset-0 flex flex-col items-center justify-center transition duration-300 space-y-20">
                                            <div class="scanner-login-visual w-[138px] h-[138px]" aria-hidden="true">
                                                <img class="scanner-login-graphic" src="<?php echo esc_url( $scanner_assets_uri . '/images/scanner.svg?ver=' . $scanner_graphic_version ); ?>" alt="">
                                                <div class="scanner-login-frame-wrap">
                                                    <img class="scanner-login-frame" src="<?php echo esc_url( $scanner_assets_uri . '/images/frame.svg?ver=' . $scanner_frame_version ); ?>" alt="">
                                                    <span class="scanner-login-scan-line"></span>
                                                </div>
                                            </div>
                                            <div class="scanner-camera-placeholder-actions space-y-5 flex flex-col items-center">
                                                <div class="text-[14px] text-[var(--scanner-muted)] text-center leading-[1.2]">Κάντε κλικ για να επαληθεύσετε<br/> εισιτήριο ή κάρτα μέλους</div>
                                                <div class="scanner-scan-action-row">
                                                    <button id="scanBtn" class="scanner-primary-button w-auto">
                                                        <span data-i18n="scan.start"><?php esc_html_e( 'Start scan', 'iw-theme' ); ?></span>
                                                    </button>
                                                    <button class="scanner-secondary-button scanner-manual-search-trigger" type="button" data-manual-search-open aria-label="<?php esc_attr_e( 'Search member', 'iw-theme' ); ?>" data-i18n-aria-label="manualSearch.open">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <circle cx="11" cy="11" r="7"></circle>
                                                            <path d="m20 20-4-4"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
										</div>

										<div id="loading" class="absolute inset-0 hidden flex flex-col items-center justify-center bg-app-top/75 text-center text-white backdrop-blur-[2px]">
											<div class="mx-auto size-[34px] animate-spin rounded-full border-2 border-current border-t-white/0"></div>
											<p class="mt-[10px] text-[13px] opacity-80" data-i18n="scan.checking"><?php esc_html_e( 'Checking...', 'iw-theme' ); ?></p>
										</div>

										<div id="result" class="absolute inset-0 hidden bg-white">
											<div id="result-message" class="h-full"></div>
										</div>

										<button class="scanner-scan-close" type="button" data-scanner-close aria-label="<?php esc_attr_e( 'Back to scanner', 'iw-theme' ); ?>" data-i18n-aria-label="scan.back" hidden>
											<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
												<path d="m15 18-6-6 6-6"></path>
											</svg>
										</button>
									</div>


								</section>

								<section class="scanner-app-view" data-scanner-view="history" aria-labelledby="scanner-history-title" aria-hidden="true">
									<div class="scanner-section-heading">
										<h1 id="scanner-history-title" class="scanner-app-title" data-i18n="history.title"><?php esc_html_e( 'Scan history', 'iw-theme' ); ?></h1>
										<p class="scanner-app-status" data-i18n="history.copy"><?php esc_html_e( 'Recent validations on this device.', 'iw-theme' ); ?></p>
									</div>
									<div class="scanner-history-list" data-scan-history-list>
										<article class="scanner-history-row scanner-history-row--empty">
											<span class="scanner-history-icon scanner-history-icon--scan"></span>
											<div>
												<strong data-i18n="history.emptyTitle"><?php esc_html_e( 'No scans yet', 'iw-theme' ); ?></strong>
												<span data-i18n="history.emptyCopy"><?php esc_html_e( 'Completed validations will appear here.', 'iw-theme' ); ?></span>
											</div>
										</article>
									</div>
								</section>

								<section class="scanner-app-view" data-scanner-view="notifications" aria-labelledby="scanner-notifications-title" aria-hidden="true">
									<div class="scanner-notifications-list-view" data-notifications-list-view>
										<div class="scanner-section-heading scanner-section-heading--actions">
											<div class="scanner-section-heading-row">
												<h1 id="scanner-notifications-title" class="scanner-app-title" data-i18n="notifications.title"><?php esc_html_e( 'Updates', 'iw-theme' ); ?></h1>
												<button class="scanner-notifications-refresh" type="button" data-notifications-refresh aria-label="<?php esc_attr_e( 'Refresh notifications', 'iw-theme' ); ?>" data-i18n-aria-label="notifications.refresh">
													<svg xmlns="http://www.w3.org/2000/svg" class="scanner-notifications-refresh-icon scanner-notifications-refresh-icon--arrow" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M11.433 2.20898C11.431 2.20781 11.4292 2.20625 11.4272 2.20508C11.4181 2.19984 11.409 2.19465 11.3998 2.18945C10.9138 1.91248 10.3965 1.69583 9.86078 1.5459C9.63157 1.48172 9.39913 1.43017 9.16449 1.39062C7.77393 1.15633 6.30773 1.36033 4.99359 2.08984C4.69651 2.25446 4.39018 2.45775 4.08734 2.69433L4.35492 2.49316C4.25165 2.56654 4.14917 2.64509 4.04633 2.72656C2.72668 3.77233 1.48423 5.47594 1.37543 7.63183C1.36379 7.86401 1.36539 8.10138 1.38129 8.34375L1.41547 8.71094C1.4477 8.97069 1.49676 9.23571 1.5639 9.50586C1.56076 9.49323 1.5572 9.48038 1.55414 9.46777C1.57754 9.5642 1.6051 9.66108 1.63324 9.75879C1.75498 10.1819 1.92007 10.6177 2.13715 11.0635C2.28818 11.3735 2.46163 11.665 2.65472 11.9375C2.97661 12.3918 3.35331 12.7926 3.77094 13.1377C4.4252 13.6783 5.18056 14.0809 5.98676 14.334C6.09593 14.3682 6.20593 14.4 6.31683 14.4287C6.33732 14.434 6.35782 14.4392 6.37836 14.4443C6.36863 14.4419 6.35878 14.44 6.34906 14.4375C6.36107 14.4406 6.37316 14.4433 6.38519 14.4463C6.38289 14.4457 6.38066 14.4449 6.37836 14.4443C6.48895 14.4719 6.60033 14.4965 6.71234 14.5186C6.99666 14.5745 7.285 14.6132 7.57562 14.6328C7.69737 14.641 7.81948 14.6467 7.94183 14.6484C7.95811 14.6487 7.97438 14.6483 7.99066 14.6484C8.10868 14.6493 8.22689 14.6469 8.34515 14.6416C8.63943 14.6285 8.93405 14.5965 9.22699 14.5449C9.47439 14.5013 9.72056 14.4437 9.96429 14.3721C10.2549 14.2866 10.5421 14.181 10.8237 14.0547C10.9428 14.0012 11.0612 13.9437 11.1782 13.8828C11.5183 13.7129 11.8909 13.8469 12.0608 14.1543C12.2307 14.4939 12.0967 14.8664 11.7895 15.0371C7.88138 17.0754 2.86545 15.6245 0.947693 11.6348C-1.9152 5.69018 2.44392 1.84409 4.34223 0.864257C8.82248 -1.19487 12.5117 0.778822 14.3871 3.09277L14.1928 1.45801C14.1586 1.06965 14.4201 0.661137 14.8383 0.661133C15.1782 0.661137 15.4527 0.899728 15.4858 1.24023L15.9955 5.28516C16.031 5.49086 15.8873 6.09865 15.2143 6.03418L11.2016 5.21777C10.8289 5.14775 10.623 4.77523 10.6918 4.43652C10.7602 4.06287 11.1328 3.857 11.4721 3.92676L13.7368 4.39941C13.1204 3.4364 12.3113 2.71591 11.433 2.20898Z" fill="currentColor"/></svg>
													<svg xmlns="http://www.w3.org/2000/svg" class="scanner-notifications-refresh-icon scanner-notifications-refresh-icon--spinner size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 1-8 4.9"></path></svg>
												</button>
											</div>
											<p class="scanner-app-status" data-i18n="notifications.copy"><?php esc_html_e( 'Updates and instructions for scanner operators.', 'iw-theme' ); ?></p>
										</div>
										<div class="scanner-notifications-pull-hint" data-notifications-pull-hint aria-hidden="true">
											<span data-i18n="notifications.pullRefresh"><?php esc_html_e( 'Pull to refresh', 'iw-theme' ); ?></span>
										</div>
										<div class="scanner-notifications-list" data-notifications-list>
											<article class="scanner-history-row scanner-history-row--empty">
												<span class="scanner-history-icon scanner-history-icon--scan"></span>
												<div>
													<strong data-i18n="notifications.loadingTitle"><?php esc_html_e( 'Loading notifications', 'iw-theme' ); ?></strong>
													<span data-i18n="notifications.loadingCopy"><?php esc_html_e( 'Checking for operator updates.', 'iw-theme' ); ?></span>
												</div>
											</article>
										</div>
									</div>
								</section>

								<section class="scanner-app-view" data-scanner-view="settings" aria-labelledby="scanner-settings-title" aria-hidden="true">
									<div class="scanner-section-heading">
										<h1 id="scanner-settings-title" class="scanner-app-title" data-i18n="settings.title"><?php esc_html_e( 'Settings', 'iw-theme' ); ?></h1>
										<p class="scanner-app-status" data-i18n="settings.copy"><?php esc_html_e( 'Scanner context and account.', 'iw-theme' ); ?></p>
									</div>
									<div class="scanner-settings-list">
										<button class="scanner-settings-row" type="button" data-location-open>
											<span class="scanner-settings-icon" aria-hidden="true">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s7-5.1 7-11a7 7 0 1 0-14 0c0 5.9 7 11 7 11z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
											</span>
											<span>
												<strong data-i18n="settings.museum"><?php esc_html_e( 'Museum location', 'iw-theme' ); ?></strong>
												<small data-selected-building-name><?php echo esc_html( $scanner_default_building['title'] ?? __( 'Choose museum', 'iw-theme' ) ); ?></small>
											</span>
										</button>
										<button class="scanner-settings-row" type="button" data-account-open>
											<span class="scanner-settings-icon" aria-hidden="true">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
											</span>
											<span>
												<strong data-i18n="settings.operator"><?php esc_html_e( 'Operator', 'iw-theme' ); ?></strong>
												<small data-account-summary><?php echo esc_html( $current_user instanceof WP_User ? $current_user->display_name : '' ); ?></small>
											</span>
										</button>
										<button class="scanner-settings-row" type="button" data-password-open>
											<span class="scanner-settings-icon" aria-hidden="true">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path><path d="M12 14.5v2"></path></svg>
											</span>
											<span>
												<strong data-i18n="settings.password"><?php esc_html_e( 'Change password', 'iw-theme' ); ?></strong>
												<small data-i18n="settings.passwordCopy"><?php esc_html_e( 'Update scanner account password.', 'iw-theme' ); ?></small>
											</span>
										</button>
										<button class="scanner-settings-row" type="button" data-fullscreen-action>
											<span class="scanner-settings-icon" aria-hidden="true">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 3H5a2 2 0 0 0-2 2v3"></path><path d="M16 3h3a2 2 0 0 1 2 2v3"></path><path d="M21 16v3a2 2 0 0 1-2 2h-3"></path><path d="M8 21H5a2 2 0 0 1-2-2v-3"></path></svg>
											</span>
											<span>
												<strong data-i18n="settings.fullscreen"><?php esc_html_e( 'Fullscreen mode', 'iw-theme' ); ?></strong>
												<small data-fullscreen-status><?php esc_html_e( 'Install or enter fullscreen on this device.', 'iw-theme' ); ?></small>
											</span>
										</button>
										<button class="scanner-settings-row" type="button" data-theme-open>
											<span class="scanner-settings-icon" data-theme-mode-icon aria-hidden="true">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3a7 7 0 0 0 0 14 5.8 5.8 0 0 1 0-14Z"></path><path d="M12 3a7 7 0 0 1 0 14"></path><path d="M12 17v4"></path><path d="M8.5 21h7"></path></svg>
											</span>
											<span>
												<strong data-i18n="settings.theme"><?php esc_html_e( 'Theme mode', 'iw-theme' ); ?></strong>
												<small data-theme-mode-summary><?php esc_html_e( 'Default', 'iw-theme' ); ?></small>
											</span>
										</button>
										<button class="scanner-settings-row" type="button" data-language-open>
											<span class="scanner-settings-icon" aria-hidden="true">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h10"></path><path d="M9 3v2"></path><path d="M12 5c-.8 4.2-3.2 7-7 9"></path><path d="M6 9c1.3 2.1 3 3.8 5.2 5"></path><path d="M14 21l4-10 4 10"></path><path d="M15.4 17h5.2"></path></svg>
											</span>
											<span>
												<strong data-i18n="settings.language"><?php esc_html_e( 'Language', 'iw-theme' ); ?></strong>
												<small data-language-summary><?php esc_html_e( 'Ελληνικά', 'iw-theme' ); ?></small>
											</span>
										</button>
										<button class="scanner-settings-row" type="button" data-app-update-action>
											<span class="scanner-settings-icon" aria-hidden="true">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 12a8 8 0 1 1-2.3-5.7"></path><path d="M20 4v6h-6"></path></svg>
											</span>
											<span>
												<strong data-i18n="settings.version"><?php esc_html_e( 'App version', 'iw-theme' ); ?></strong>
												<small data-app-version-status><?php echo esc_html( $scanner_app_version_label ); ?></small>
											</span>
										</button>
										<button class="scanner-settings-row" type="button" data-logout-open>
											<span class="scanner-settings-icon" aria-hidden="true">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="M16 17l5-5-5-5"></path><path d="M21 12H9"></path></svg>
											</span>
											<span>
												<strong data-i18n="settings.logout"><?php esc_html_e( 'Log out', 'iw-theme' ); ?></strong>
												<small data-i18n="settings.logoutCopy"><?php esc_html_e( 'Leave this scanner session.', 'iw-theme' ); ?></small>
											</span>
										</button>
									</div>
								</section>
							</div>

							<nav class="scanner-app-nav" aria-label="<?php esc_attr_e( 'Scanner navigation', 'iw-theme' ); ?>" data-i18n-aria-label="nav.aria">
								<button class="scanner-app-nav-item is-active" type="button" data-scanner-nav="scan" aria-current="page">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M7.5 4.5h-2a1 1 0 0 0-1 1v2"></path><path d="M16.5 4.5h2a1 1 0 0 1 1 1v2"></path><path d="M19.5 16.5v2a1 1 0 0 1-1 1h-2"></path><path d="M7.5 19.5h-2a1 1 0 0 1-1-1v-2"></path><path d="M9.25 9.25h5.5v5.5h-5.5z"></path></svg>
									<span data-i18n="nav.scan"><?php esc_html_e( 'Scan', 'iw-theme' ); ?></span>
								</button>
								<button class="scanner-app-nav-item" type="button" data-scanner-nav="history">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M4.75 9.25a7.6 7.6 0 1 1 1.42 6.84"></path><path d="M4.75 5.25v4h4"></path><path d="M12 8.25v4.2l2.7 1.6"></path></svg>
									<span data-i18n="nav.history"><?php esc_html_e( 'History', 'iw-theme' ); ?></span>
								</button>
								<button class="scanner-app-nav-item" type="button" data-scanner-nav="notifications">
									<span class="scanner-app-nav-icon">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8.8a6 6 0 1 0-12 0c0 7.2-2.5 7.2-2.5 7.2h17S18 16 18 8.8Z"></path><path d="M9.8 19a2.4 2.4 0 0 0 4.4 0"></path></svg>
										<span class="scanner-notifications-badge" data-notifications-badge hidden>0</span>
									</span>
									<span data-i18n="nav.notices"><?php esc_html_e( 'Updates', 'iw-theme' ); ?></span>
								</button>
								<button class="scanner-app-nav-item" type="button" data-scanner-nav="settings">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7.5h7.2"></path><path d="M15.8 7.5H19"></path><circle cx="14" cy="7.5" r="1.8"></circle><path d="M5 12h3.2"></path><path d="M11.8 12H19"></path><circle cx="10" cy="12" r="1.8"></circle><path d="M5 16.5h8.2"></path><path d="M16.8 16.5H19"></path><circle cx="15" cy="16.5" r="1.8"></circle></svg>
									<span data-i18n="nav.settings"><?php esc_html_e( 'Settings', 'iw-theme' ); ?></span>
								</button>
							</nav>
						</div>

						<div class="scanner-location-sheet" data-notification-sheet aria-hidden="true">
							<button class="scanner-location-backdrop" type="button" data-notification-close aria-label="<?php esc_attr_e( 'Close notification', 'iw-theme' ); ?>" data-i18n-aria-label="notifications.closeDetail"></button>
							<div class="scanner-location-panel scanner-notification-panel" role="dialog" aria-modal="true" aria-labelledby="scanner-notification-detail-title" data-notification-detail>
								<div class="scanner-location-panel-header">
									<h2 id="scanner-notification-detail-title" class="scanner-notification-detail-title" data-notification-detail-title><?php esc_html_e( 'Loading...', 'iw-theme' ); ?></h2>
									<button class="scanner-location-close" type="button" data-notification-close aria-label="<?php esc_attr_e( 'Close', 'iw-theme' ); ?>" data-i18n-aria-label="app.close">
										<svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
									</button>
								</div>
								<div class="scanner-notification-detail-scroll">
									<div class="scanner-notification-detail-meta-row">
										<div class="scanner-notification-detail-meta" data-notification-detail-meta></div>
										<button class="scanner-notification-meta-action" type="button" data-notification-unread aria-label="<?php esc_attr_e( 'Mark as unread', 'iw-theme' ); ?>" data-i18n-aria-label="notifications.markUnread" hidden>
											<svg xmlns="http://www.w3.org/2000/svg" class="scanner-notification-meta-icon scanner-notification-meta-icon--open size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10.2V18h16v-7.8"></path><path d="m4 10.2 8 5.2 8-5.2"></path><path d="M4 10.2 12 5l8 5.2"></path></svg>
											<svg xmlns="http://www.w3.org/2000/svg" class="scanner-notification-meta-icon scanner-notification-meta-icon--closed size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6.5h16v11H4z"></path><path d="m4.7 7.2 7.3 6 7.3-6"></path></svg>
										</button>
									</div>
									<div class="scanner-notification-detail-image" data-notification-detail-image hidden></div>
									<div class="scanner-notification-content" data-notification-detail-content></div>
								</div>
							</div>
						</div>

						<div class="scanner-location-sheet" data-location-sheet aria-hidden="true">
							<button class="scanner-location-backdrop" type="button" data-location-close aria-label="<?php esc_attr_e( 'Close museum selector', 'iw-theme' ); ?>" data-i18n-aria-label="location.closeSelector"></button>
							<div class="scanner-location-panel" role="dialog" aria-modal="true" aria-labelledby="scanner-location-title">
								<div class="scanner-location-panel-header">
									<h2 id="scanner-location-title" data-i18n="location.choose"><?php esc_html_e( 'Choose museum', 'iw-theme' ); ?></h2>
									<button class="scanner-location-close" type="button" data-location-close aria-label="<?php esc_attr_e( 'Close', 'iw-theme' ); ?>" data-i18n-aria-label="app.close">
										<svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
									</button>
								</div>
								<label class="scanner-location-search">
									<svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg>
									<input type="search" data-location-search placeholder="<?php esc_attr_e( 'Search museum', 'iw-theme' ); ?>" data-i18n-placeholder="location.search">
								</label>
								<button class="scanner-location-detect" type="button" data-location-detect>
									<span class="scanner-location-icon" aria-hidden="true">
                                        <svg  width="800px" height="800px"  viewBox="0 0 32 32" class="fill-current" xmlns="http://www.w3.org/2000/svg"><path d="M4,12.9835a1,1,0,0,0,.6289.9448l9.6015,3.8409,3.8407,9.6019A1,1,0,0,0,19,28h.0162a1.0009,1.0009,0,0,0,.9238-.6582l8-22.0007A1,1,0,0,0,26.658,4.0594l-22,8A1.0011,1.0011,0,0,0,4,12.9835Z" /></svg>
									</span>
									<span>
										<strong data-i18n="location.useMine"><?php esc_html_e( 'Use my location', 'iw-theme' ); ?></strong>
										<small data-location-status><?php esc_html_e( 'Select the nearest museum automatically.', 'iw-theme' ); ?></small>
									</span>
								</button>
								<div class="scanner-location-list" data-location-list></div>
							</div>
						</div>

						<div class="scanner-location-sheet" data-manual-search-sheet aria-hidden="true">
							<button class="scanner-location-backdrop" type="button" data-manual-search-close aria-label="<?php esc_attr_e( 'Close member search', 'iw-theme' ); ?>" data-i18n-aria-label="manualSearch.close"></button>
							<div class="scanner-location-panel scanner-form-panel scanner-manual-search-panel" role="dialog" aria-modal="true" aria-labelledby="scanner-manual-search-title">
								<div class="scanner-location-panel-header">
									<span class="scanner-sheet-title-icon" aria-hidden="true">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
											<circle cx="11" cy="11" r="7"></circle>
											<path d="m20 20-4-4"></path>
										</svg>
									</span>
									<h2 id="scanner-manual-search-title" data-i18n="manualSearch.title"><?php esc_html_e( 'Member search', 'iw-theme' ); ?></h2>
									<button class="scanner-location-close" type="button" data-manual-search-close aria-label="<?php esc_attr_e( 'Close', 'iw-theme' ); ?>" data-i18n-aria-label="app.close">
										<svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
									</button>
								</div>
								<form class="scanner-sheet-form scanner-manual-search-form" data-manual-search-form>
									<p class="scanner-sheet-helper" data-i18n="manualSearch.copy"><?php esc_html_e( 'Search by full name, email, phone, or member card number.', 'iw-theme' ); ?></p>
									<label class="scanner-field-label">
										<span data-i18n="manualSearch.label"><?php esc_html_e( 'Full name, email, or phone', 'iw-theme' ); ?></span>
										<input class="scanner-field-input" type="search" name="q" autocomplete="off" inputmode="search" data-manual-search-input data-i18n-placeholder="manualSearch.placeholder" placeholder="<?php esc_attr_e( 'Name, email, or phone', 'iw-theme' ); ?>" required>
									</label>
									<p class="scanner-sheet-message" data-manual-search-message aria-live="polite"></p>
									<div class="scanner-manual-search-results" data-manual-search-results hidden></div>
									<div class="scanner-sheet-actions">
										<button class="scanner-primary-button" type="submit" data-i18n="manualSearch.submit"><?php esc_html_e( 'Search', 'iw-theme' ); ?></button>
									</div>
								</form>
							</div>
						</div>

						<div class="scanner-location-sheet" data-member-tickets-sheet aria-hidden="true">
							<button class="scanner-location-backdrop" type="button" data-member-tickets-close aria-label="<?php esc_attr_e( 'Close member tickets', 'iw-theme' ); ?>" data-i18n-aria-label="memberTickets.close"></button>
							<div class="scanner-location-panel scanner-form-panel scanner-member-tickets-panel" role="dialog" aria-modal="true" aria-labelledby="scanner-member-tickets-title">
								<div class="scanner-location-panel-header">
									<span class="scanner-sheet-title-icon" aria-hidden="true">
										<svg width="26" height="26" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg">
											<path d="M14.7527 2.38969C14.4579 2.09618 13.9777 2.09518 13.6817 2.38969L2.09242 13.9346C1.79669 14.2294 1.79706 14.7084 2.09173 15.0022L3.75634 16.6604C5.14815 16.1457 6.77392 16.4437 7.89296 17.5585C9.01167 18.6731 9.30951 20.2916 8.79307 21.6778L10.4598 23.3381C10.7548 23.6305 11.2345 23.6303 11.5301 23.3361L23.1194 11.7912C23.4151 11.4963 23.4142 11.018 23.1194 10.7243L21.4458 9.05708C20.0607 9.55813 18.448 9.25752 17.3368 8.15078C16.2247 7.04293 15.9243 5.43669 16.4263 4.05689L14.7527 2.38969ZM18.0481 3.27257C18.3196 3.54307 18.3762 3.96115 18.1857 4.29328C17.7004 5.13841 17.8207 6.23301 18.5413 6.95087C19.2627 7.6692 20.3619 7.78919 21.2071 7.30581C21.5407 7.11498 21.9611 7.1706 22.2331 7.44158L24.3239 9.52437C25.2846 10.4814 25.2841 12.0342 24.3239 12.9911L12.7346 24.536C11.7966 25.4702 10.2901 25.4902 9.3251 24.5994C9.30125 24.5803 9.27806 24.5594 9.25592 24.5374L7.16858 22.458C6.89488 22.1854 6.83981 21.7634 7.03505 21.4304C7.53158 20.5838 7.41461 19.4806 6.68913 18.7577C5.96342 18.0347 4.8554 17.9177 4.00541 18.4124C3.6712 18.6068 3.24753 18.552 2.97385 18.2794L0.887896 16.2014C-0.0726209 15.2443 -0.0716232 13.6921 0.888587 12.7354L12.4779 1.19047C13.4384 0.234024 14.9965 0.232974 15.9573 1.18978L18.0481 3.27257Z"/>
											<path d="M10.9602 7.64199L9.09423 5.78319C8.76165 5.45189 8.76165 4.91528 9.09423 4.58397C9.42681 4.25266 9.96548 4.25266 10.2981 4.58397L12.164 6.44276C12.4965 6.77408 12.4966 7.31071 12.164 7.64199C11.8315 7.97326 11.2928 7.9732 10.9602 7.64199Z"/>
											<path d="M15.3352 12.0053L13.4692 10.1465C13.1366 9.81517 13.1366 9.27856 13.4692 8.94725C13.8018 8.61595 14.3405 8.61595 14.6731 8.94725L16.539 10.806C16.8715 11.1374 16.8716 11.674 16.539 12.0053C16.2065 12.3365 15.6678 12.3365 15.3352 12.0053Z"/>
											<path d="M19.7258 16.3725L17.8599 14.5137C17.5273 14.1824 17.5273 13.6457 17.8599 13.3144C18.1924 12.9831 18.7311 12.9831 19.0637 13.3144L20.9296 15.1732C21.2621 15.5045 21.2622 16.0412 20.9296 16.3725C20.5971 16.7037 20.0584 16.7037 19.7258 16.3725Z"/>
										</svg>
									</span>
									<h2 id="scanner-member-tickets-title" data-i18n="memberTickets.title"><?php esc_html_e( 'Member tickets', 'iw-theme' ); ?></h2>
									<button class="scanner-location-close" type="button" data-member-tickets-close aria-label="<?php esc_attr_e( 'Close', 'iw-theme' ); ?>" data-i18n-aria-label="app.close">
										<svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
									</button>
								</div>
								<div class="scanner-member-tickets-list" data-member-tickets-list></div>
							</div>
						</div>

						<div class="scanner-location-sheet" data-account-sheet aria-hidden="true">
							<button class="scanner-location-backdrop" type="button" data-account-close aria-label="<?php esc_attr_e( 'Close operator details', 'iw-theme' ); ?>" data-i18n-aria-label="account.close"></button>
							<div class="scanner-location-panel scanner-form-panel" role="dialog" aria-modal="true" aria-labelledby="scanner-account-title">
								<div class="scanner-location-panel-header">
									<h2 id="scanner-account-title" data-i18n="account.title"><?php esc_html_e( 'Operator details', 'iw-theme' ); ?></h2>
									<button class="scanner-location-close" type="button" data-account-close aria-label="<?php esc_attr_e( 'Close', 'iw-theme' ); ?>" data-i18n-aria-label="app.close">
										<svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
									</button>
								</div>
								<form class="scanner-sheet-form" method="post" action="<?php echo esc_url( iw_scanner_site_path() . '/wp-admin/admin-ajax.php' ); ?>" data-account-form>
									<label class="scanner-field-label">
										<span data-i18n="account.firstName"><?php esc_html_e( 'First name', 'iw-theme' ); ?></span>
										<input class="scanner-field-input" type="text" name="user_fields[first_name]" value="<?php echo esc_attr( $current_user instanceof WP_User ? $current_user->first_name : '' ); ?>" autocomplete="given-name" required>
									</label>
									<label class="scanner-field-label">
										<span data-i18n="account.lastName"><?php esc_html_e( 'Last name', 'iw-theme' ); ?></span>
										<input class="scanner-field-input" type="text" name="user_fields[last_name]" value="<?php echo esc_attr( $current_user instanceof WP_User ? $current_user->last_name : '' ); ?>" autocomplete="family-name" required>
									</label>
									<label class="scanner-field-label">
										<span data-i18n="account.email"><?php esc_html_e( 'Email', 'iw-theme' ); ?></span>
										<input class="scanner-field-input" type="email" name="user_fields[user_email]" value="<?php echo esc_attr( $current_user instanceof WP_User ? $current_user->user_email : '' ); ?>" autocomplete="email" required>
									</label>
									<label class="scanner-field-label">
										<span data-i18n="account.mobilePhone"><?php esc_html_e( 'Mobile phone', 'iw-theme' ); ?></span>
										<input class="scanner-field-input" type="tel" name="user_fields[activation_phone]" value="<?php echo esc_attr( $scanner_account_phone ); ?>" inputmode="tel" autocomplete="tel" placeholder="+3069XXXXXXXX">
									</label>
									<p class="scanner-sheet-message" data-account-message aria-live="polite"></p>
									<div class="scanner-sheet-actions">
										<button class="scanner-primary-button" type="submit" data-i18n="account.saveDetails"><?php esc_html_e( 'Save details', 'iw-theme' ); ?></button>
									</div>
									<input type="hidden" name="action" value="iw-auth-edit-account">
								</form>
								<form class="scanner-sheet-form is-hidden" method="post" action="<?php echo esc_url( iw_scanner_site_path() . '/wp-admin/admin-ajax.php' ); ?>" data-account-phone-form>
									<div class="scanner-sheet-copy">
										<strong data-i18n="account.verifyPhoneTitle"><?php esc_html_e( 'Verify mobile phone', 'iw-theme' ); ?></strong>
										<span><span data-i18n="account.verifyPhoneCopy"><?php esc_html_e( 'Enter the code sent to', 'iw-theme' ); ?></span> <b data-account-pending-phone></b>.</span>
									</div>
									<label class="scanner-field-label">
										<span data-i18n="account.verificationCode"><?php esc_html_e( 'Verification code', 'iw-theme' ); ?></span>
										<input class="scanner-field-input" type="text" name="activation_code" inputmode="numeric" autocomplete="one-time-code" required>
									</label>
									<button class="scanner-sheet-link" type="button" data-account-phone-resend data-label="<?php echo esc_attr__( 'Resend code', 'iw-theme' ); ?>" data-countdown-label="<?php echo esc_attr__( 'Resend in {seconds}s', 'iw-theme' ); ?>">
										<?php esc_html_e( 'Resend code', 'iw-theme' ); ?>
									</button>
									<p class="scanner-sheet-message" data-account-phone-message aria-live="polite"></p>
									<div class="scanner-sheet-actions">
										<button class="scanner-primary-button" type="submit" data-i18n="account.verifyPhone"><?php esc_html_e( 'Verify phone', 'iw-theme' ); ?></button>
									</div>
									<input type="hidden" name="security" value="<?php echo esc_attr( wp_create_nonce( 'iw-auth-account-phone' ) ); ?>">
									<input type="hidden" name="action" value="iw-auth-verify-account-phone">
								</form>
							</div>
						</div>

						<div class="scanner-location-sheet" data-password-sheet aria-hidden="true">
							<button class="scanner-location-backdrop" type="button" data-password-close aria-label="<?php esc_attr_e( 'Close password change', 'iw-theme' ); ?>" data-i18n-aria-label="password.close"></button>
							<div class="scanner-location-panel scanner-form-panel" role="dialog" aria-modal="true" aria-labelledby="scanner-password-title">
								<div class="scanner-location-panel-header">
									<h2 id="scanner-password-title" data-i18n="password.title"><?php esc_html_e( 'Change password', 'iw-theme' ); ?></h2>
									<button class="scanner-location-close" type="button" data-password-close aria-label="<?php esc_attr_e( 'Close', 'iw-theme' ); ?>" data-i18n-aria-label="app.close">
										<svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
									</button>
								</div>
								<form class="scanner-sheet-form" method="post" action="<?php echo esc_url( iw_scanner_site_path() . '/wp-admin/admin-ajax.php' ); ?>" data-password-form>
									<label class="scanner-field-label">
										<span data-i18n="password.new"><?php esc_html_e( 'New password', 'iw-theme' ); ?></span>
										<input class="scanner-field-input" type="password" name="user_fields[new_user_pass]" autocomplete="new-password" required>
									</label>
									<label class="scanner-field-label">
										<span data-i18n="password.confirm"><?php esc_html_e( 'Confirm password', 'iw-theme' ); ?></span>
										<input class="scanner-field-input" type="password" name="user_fields[new_user_pass_confirmation]" autocomplete="new-password" required>
									</label>
									<p class="scanner-sheet-helper" data-i18n="password.helper"><?php esc_html_e( 'Use at least 8 characters, one lowercase letter, and one special character.', 'iw-theme' ); ?></p>
									<p class="scanner-sheet-message" data-password-message aria-live="polite"></p>
									<div class="scanner-sheet-actions">
										<button class="scanner-primary-button" type="submit" data-i18n="password.update"><?php esc_html_e( 'Update password', 'iw-theme' ); ?></button>
									</div>
									<input type="hidden" name="action" value="iw-auth-change-password">
								</form>
							</div>
						</div>

						<div class="scanner-location-sheet" data-theme-sheet aria-hidden="true">
							<button class="scanner-location-backdrop" type="button" data-theme-close aria-label="<?php esc_attr_e( 'Close theme settings', 'iw-theme' ); ?>" data-i18n-aria-label="theme.close"></button>
							<div class="scanner-location-panel scanner-form-panel scanner-theme-panel" role="dialog" aria-modal="true" aria-labelledby="scanner-theme-title">
								<div class="scanner-location-panel-header">
									<h2 id="scanner-theme-title" data-i18n="settings.theme"><?php esc_html_e( 'Theme mode', 'iw-theme' ); ?></h2>
									<button class="scanner-location-close" type="button" data-theme-close aria-label="<?php esc_attr_e( 'Close', 'iw-theme' ); ?>" data-i18n-aria-label="app.close">
										<svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
									</button>
								</div>
								<div class="scanner-theme-options" role="radiogroup" aria-label="<?php esc_attr_e( 'Theme mode', 'iw-theme' ); ?>" data-i18n-aria-label="settings.theme">
									<button class="scanner-theme-option" type="button" role="radio" data-theme-mode="default">
										<span class="scanner-location-radio" aria-hidden="true"></span>
										<span class="scanner-theme-option-copy">
											<strong data-i18n="theme.default"><?php esc_html_e( 'Default', 'iw-theme' ); ?></strong>
											<span data-i18n="theme.defaultCopy"><?php esc_html_e( 'Follow this device where possible.', 'iw-theme' ); ?></span>
										</span>
									</button>
									<button class="scanner-theme-option" type="button" role="radio" data-theme-mode="light">
										<span class="scanner-location-radio" aria-hidden="true"></span>
										<span class="scanner-theme-option-copy">
											<strong data-i18n="theme.light"><?php esc_html_e( 'Light', 'iw-theme' ); ?></strong>
											<span data-i18n="theme.lightCopy"><?php esc_html_e( 'Use the bright scanner interface.', 'iw-theme' ); ?></span>
										</span>
									</button>
									<button class="scanner-theme-option" type="button" role="radio" data-theme-mode="dark">
										<span class="scanner-location-radio" aria-hidden="true"></span>
										<span class="scanner-theme-option-copy">
											<strong data-i18n="theme.dark"><?php esc_html_e( 'Dark', 'iw-theme' ); ?></strong>
											<span data-i18n="theme.darkCopy"><?php esc_html_e( 'Use a darker interface in low light.', 'iw-theme' ); ?></span>
										</span>
									</button>
								</div>
							</div>
						</div>

						<div class="scanner-location-sheet" data-language-sheet aria-hidden="true">
							<button class="scanner-location-backdrop" type="button" data-language-close aria-label="<?php esc_attr_e( 'Close language settings', 'iw-theme' ); ?>" data-i18n-aria-label="language.close"></button>
							<div class="scanner-location-panel scanner-form-panel scanner-theme-panel" role="dialog" aria-modal="true" aria-labelledby="scanner-language-title">
								<div class="scanner-location-panel-header">
									<h2 id="scanner-language-title" data-i18n="language.title"><?php esc_html_e( 'Language', 'iw-theme' ); ?></h2>
									<button class="scanner-location-close" type="button" data-language-close aria-label="<?php esc_attr_e( 'Close', 'iw-theme' ); ?>" data-i18n-aria-label="app.close">
										<svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
									</button>
								</div>
								<div class="scanner-theme-options" role="radiogroup" aria-label="<?php esc_attr_e( 'Language', 'iw-theme' ); ?>" data-i18n-aria-label="language.title">
									<button class="scanner-theme-option" type="button" role="radio" data-language-mode="el">
										<span class="scanner-location-radio" aria-hidden="true"></span>
										<span class="scanner-theme-option-copy">
											<strong data-i18n="language.el"><?php esc_html_e( 'Ελληνικά', 'iw-theme' ); ?></strong>
											<span data-i18n="language.elCopy"><?php esc_html_e( 'Χρήση ελληνικών κειμένων στο scanner.', 'iw-theme' ); ?></span>
										</span>
									</button>
									<button class="scanner-theme-option" type="button" role="radio" data-language-mode="en">
										<span class="scanner-location-radio" aria-hidden="true"></span>
										<span class="scanner-theme-option-copy">
											<strong data-i18n="language.en"><?php esc_html_e( 'English', 'iw-theme' ); ?></strong>
											<span data-i18n="language.enCopy"><?php esc_html_e( 'Use English scanner text.', 'iw-theme' ); ?></span>
										</span>
									</button>
								</div>
							</div>
						</div>

						<div class="scanner-location-sheet" data-logout-sheet aria-hidden="true">
							<button class="scanner-location-backdrop" type="button" data-logout-close aria-label="<?php esc_attr_e( 'Cancel logout', 'iw-theme' ); ?>" data-i18n-aria-label="logout.cancel"></button>
							<div class="scanner-location-panel scanner-form-panel scanner-confirm-panel" role="dialog" aria-modal="true" aria-labelledby="scanner-logout-title">
								<div class="scanner-location-panel-header">
									<h2 id="scanner-logout-title" data-i18n="logout.title"><?php esc_html_e( 'Log out?', 'iw-theme' ); ?></h2>
									<button class="scanner-location-close" type="button" data-logout-close aria-label="<?php esc_attr_e( 'Cancel', 'iw-theme' ); ?>" data-i18n-aria-label="app.cancel">
										<svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
									</button>
								</div>
								<p class="scanner-sheet-helper" data-i18n="logout.copy"><?php esc_html_e( 'Are you sure you want to leave this scanner session?', 'iw-theme' ); ?></p>
								<div class="scanner-confirm-actions">
									<button class="scanner-secondary-button" type="button" data-logout-close data-i18n="app.cancel"><?php esc_html_e( 'Cancel', 'iw-theme' ); ?></button>
									<a class="scanner-primary-button" href="<?php echo esc_url( $scanner_logout_url ); ?>" data-i18n="logout.confirm"><?php esc_html_e( 'Log out', 'iw-theme' ); ?></a>
								</div>
							</div>
						</div>
					</div>

					<script>
						window.IWScanner = <?php echo wp_json_encode( $scanner_config ); ?>;
					</script>
					<script type="module" src="<?php echo esc_url( $scanner_assets_uri . '/js/scanner.js?ver=' . $scanner_js_version ); ?>"></script>
				<?php endif; ?>
			</section>

		</div>
	</main>
</body>
</html>
