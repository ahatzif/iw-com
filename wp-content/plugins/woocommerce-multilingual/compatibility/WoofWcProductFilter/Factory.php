<?php

namespace WCML\Compatibility\WoofWcProductFilter;

use WCML\Compatibility\ComponentFactory;
use WCML\StandAlone\IStandAloneAction;

class Factory extends ComponentFactory implements IStandAloneAction {

	public function create() {
		$hooks = [];

		if ( wcml_is_multi_currency_on() ) {
			$hooks[] = new MulticurrencyHooks();
		}

		return $hooks;
	}

}
