<?php

namespace WCML\Multicurrency\WpQueryMcPrice;

use WCML\StandAlone\IStandAloneAction;

class Factory implements \IWPML_Frontend_Action_Loader, \IWPML_Deferred_Action_Loader, IStandAloneAction {
	public function get_load_action() {
		return 'init';
	}

	public function create() {
		global $woocommerce_wpml;

		global $wpdb;

		$hooks = [];

		if ( wcml_is_multi_currency_on() ) {
			$hooks[] = new PriceFilteringByPostMeta( $woocommerce_wpml, $wpdb );
			$hooks[] = new PriceOrderByPostMeta( $woocommerce_wpml, $wpdb );
		}

		return $hooks;
	}
}
