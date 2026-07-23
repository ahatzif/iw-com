<?php
/**
 * Plugin Name: IW Admin UI
 * Description: Shared admin CSS primitives for IW tools.
 * Version: 0.1.0
 * Author: Andreas Hatzifotis
 * Text Domain: iw-admin-ui
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class IW_Admin_UI {
	public static function asset_url( string $path = 'assets/admin-ui.css' ): string {
		return plugins_url( ltrim( $path, '/' ), __FILE__ );
	}

	public static function asset_path( string $path = 'assets/admin-ui.css' ): string {
		return __DIR__ . '/' . ltrim( $path, '/' );
	}
}
