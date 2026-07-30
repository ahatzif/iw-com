<?php

namespace WCML\Compatibility\TableRateShipping;

use WCML\Compatibility\ComponentFactory;
use WCML\StandAlone\IStandAloneAction;
use WCML_Table_Rate_Shipping;
use function WCML\functions\getSitePress;
use function WCML\functions\getWooCommerceWpml;
use function WCML\functions\isStandAlone;

class Factory extends ComponentFactory implements IStandAloneAction {

	public function create() {
		$hooks = [];

		$woocommerce_wpml = getWooCommerceWpml();

		if ( wcml_is_multi_currency_on() ) {
			$hooks[] = new MulticurrencyHooks( $woocommerce_wpml, $woocommerce_wpml->get_multi_currency() );
		}

		if ( ! isStandAlone() ) {
			$hooks[] = new WCML_Table_Rate_Shipping( getSitePress(), $woocommerce_wpml, self::getWpdb() );
		}

		return $hooks;
	}

}
