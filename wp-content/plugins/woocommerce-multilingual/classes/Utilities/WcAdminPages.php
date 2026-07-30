<?php

namespace WCML\Utilities;

use WPML\FP\Lst;
use WPML\FP\Obj;

class WcAdminPages {

	const SECTION_BACS = 'bacs';

	public static function isSection( $sections ) {
		return Lst::includes( Obj::prop( 'section', $_GET ), (array) $sections );
	}

	public static function hasSection() {
		return (bool) Obj::prop( 'section', $_GET );
	}

	private static function isSettingsPage() {
		return self::isAdminPhpPage( AdminUrl::PAGE_WOO_SETTINGS );
	}

	public static function isHomeScreen() {
		return self::isAdminPhpPage( 'wc-admin' );
	}

	public static function isPaymentSettings() {
		return self::isSettingsPage() && AdminPages::isTab( 'checkout' );
	}

	public static function isEmailSettings() {
		return self::isSettingsPage() &&  AdminPages::isTab( 'email' );
	}

	public static function isShippingSettings() {
		return self::isSettingsPage() && AdminPages::isTab( 'shipping' );
	}

	public static function isAdvancedSettings() {
		return self::isSettingsPage() && AdminPages::isTab( 'advanced' );
	}

	private static function isAdminPhpPage( $page ) {
		global $pagenow;

		return is_admin() && 'admin.php' === $pagenow && AdminPages::isPage( $page );
	}

}
