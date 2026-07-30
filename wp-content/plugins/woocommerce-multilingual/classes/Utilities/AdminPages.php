<?php

namespace WCML\Utilities;

use WCML_Admin_Menus;
use WCML_Capabilities;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use function sanitize_key;
use function WCML\functions\isStandAlone;

class AdminPages {

	const TAB_MULTICURRENCY = 'multi-currency';
	const TAB_MULTILINGUAL  = 'multilingual';
	const TAB_MULTILINGUAL_STANDALONE  = 'multilingual-standalone';

	public static function getDefaultTab() {
		return isStandAlone() ? self::TAB_MULTICURRENCY : self::TAB_MULTILINGUAL;
	}

	public static function getCurrentTab( $fallback = null ) {
		return sanitize_key( Obj::prop( 'tab', $_GET ) ) ?: $fallback;
	}

	public static function getTabToDisplay() {
		return WCML_Capabilities::canAccessAllWcmlTabs()
			? self::getCurrentTab( self::getDefaultTab() )
			: self::getDefaultTab();
	}

	public static function isTab( $tabs ) {
		return Lst::includes( self::getCurrentTab(), (array) $tabs );
	}

	public static function isPage( $page ) {
		return Relation::propEq( 'page', $page, $_GET );
	}

	public static function isWcmlSettings() {
		return self::isPage( WCML_Admin_Menus::SLUG );
	}

	public static function isMultiCurrency() {
		$tabs = [ self::TAB_MULTICURRENCY ];

		if ( isStandAlone() ) {
			$tabs[] = null;
		}

		return self::isWcmlSettings() && self::isTab( $tabs );
	}

	public static function isTranslationQueue() {
		return ! isStandAlone() && self::isTmPage( '/menu/translations-queue.php' );
	}

	public static function isTranslationsDashboard() {
		return ! isStandAlone() && self::isTmPage( '/menu/main.php' );
	}

	public static function isWcProductAttributesPage(): bool {
		return self::isPage( 'product_attributes' ) && Relation::propEq( 'post_type', 'product', $_GET );
	}

	private static function isTmPage( $path ) {
		return defined( 'WPML_TM_FOLDER' ) && self::isPage( WPML_TM_FOLDER . $path );
	}
}
