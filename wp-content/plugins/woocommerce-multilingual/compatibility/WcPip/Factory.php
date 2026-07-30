<?php

namespace WCML\Compatibility\WcPip;

use WCML\Compatibility\ComponentFactory;
use WCML\StandAlone\IStandAloneAction;
use WCML_Pip;
use function WCML\functions\isStandAlone;

class Factory extends ComponentFactory implements IStandAloneAction {

	public function create() {
		$hooks = [];

		if ( wcml_is_multi_currency_on() ) {
			$hooks[] = new MulticurrencyHooks();
		}

		if ( ! isStandAlone() ) {
			$hooks[] = new WCML_Pip();
		}

		return $hooks;
	}
}
