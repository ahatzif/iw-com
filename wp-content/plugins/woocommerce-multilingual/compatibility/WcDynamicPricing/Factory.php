<?php

namespace WCML\Compatibility\WcDynamicPricing;

use WCML\Compatibility\ComponentFactory;
use WCML\StandAlone\IStandAloneAction;
use function WCML\functions\getSitePress;
use function WCML\functions\isStandAlone;

class Factory extends ComponentFactory implements IStandAloneAction {

	public function create() {
		$hooks = [];

		if ( wcml_is_multi_currency_on() ) {
			$hooks[] = new MulticurrencyHooks();
		}

		if ( ! isStandAlone() ) {
			$hooks[] = new \WCML_Dynamic_Pricing( getSitePress() );
		}

		return $hooks;
	}
}
