<?php

namespace WCML\Reports;

use WCML\Rest\Functions;

class Hooks implements \IWPML_Backend_Action {

	public function add_hooks() {
		if ( Functions::isAnalyticsPage() ) {
			add_filter( 'wpml_admin_language_switcher_items', [ $this, 'removeAllFromAnalytics' ] );
		}
	}

	public function removeAllFromAnalytics( $items ) {
		unset( $items['all'] );

		return $items;
	}

}
