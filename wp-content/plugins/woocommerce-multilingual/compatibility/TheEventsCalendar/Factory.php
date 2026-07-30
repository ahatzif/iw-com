<?php

namespace WCML\Compatibility\TheEventsCalendar;

use WCML\Compatibility\ComponentFactory;
use WCML\StandAlone\IStandAloneAction;

use function WCML\functions\getSitePress;
use function WCML\functions\getWooCommerceWpml;
use function WCML\functions\isStandAlone;

class Factory extends ComponentFactory implements IStandAloneAction {

	public function create() {
		$hooks = [];

		if ( wcml_is_multi_currency_on() ) {
			$hooks[] = new MulticurrencyHooks();
		}

		if ( ! isStandAlone() ) {
			$hooks[] = new \WCML_The_Events_Calendar( getSitePress(), getWooCommerceWpml(), new \WPML_Translation_Job_Helper() );
		}

		return $hooks;
	}

}
