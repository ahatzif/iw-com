<?php

namespace WCML\Compatibility\WcExporter;

use WCML\Compatibility\ComponentFactory;
use WCML_wcExporter;
use function WCML\functions\getSitePress;
use function WCML\functions\getWooCommerceWpml;

class Factory extends ComponentFactory {

	public function create() {
		return new WCML_wcExporter( getSitePress(), getWooCommerceWpml() );
	}
}
