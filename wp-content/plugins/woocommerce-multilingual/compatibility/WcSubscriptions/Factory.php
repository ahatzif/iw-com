<?php

namespace WCML\Compatibility\WcSubscriptions;

use WCML\Compatibility\ComponentFactory;
use WCML\StandAlone\IStandAloneAction;
use WCML_WC_Subscriptions;
use function WCML\functions\getWooCommerceWpml;
use function WCML\functions\getSitePress;
use function WCML\functions\isStandAlone;

class Factory extends ComponentFactory implements IStandAloneAction {

	public function create() {
		$hooks = [
			new SharedHooks(),
		];

		if ( wcml_is_multi_currency_on() ) {
			$hooks[] = new MulticurrencyHooks( getWooCommerceWpml(), self::getWpdb() );
		}

		if ( ! isStandAlone() ) {
			$hooks[] = new WCML_WC_Subscriptions( getWooCommerceWpml(), self::getWpdb(), getSitePress(), \WPML_URL_Converter::getGlobalInstance() );
		}

		return $hooks;
	}
}
