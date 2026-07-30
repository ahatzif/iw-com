<?php

namespace WCML\Attributes;

class LookupFiltersFactory implements \IWPML_Frontend_Action_Loader {
	
	public function create() {
		global $sitepress, $wpdb;
		
		if ( self::isEnabled() ) {
			return new LookupFilters( $sitepress, $wpdb );
		}

		return null;
	}
	
	public static function isEnabled() {
		return 'yes' === get_option( 'woocommerce_attribute_lookup_enabled' );
	}

}
