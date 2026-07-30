<?php

namespace WCML\Media\Wrapper;

use WCML\StandAlone\NullSitePress;

class Factory {

	public static function create() {
		global $sitepress, $wpdb;

		$settingsFactory = new \WPML_Element_Sync_Settings_Factory();

		if ( $settingsFactory->create( 'post' )->is_sync( 'attachment' ) ) {
			return new Translatable( $sitepress, $wpdb );
		}

		return new NonTranslatable();
	}
}
