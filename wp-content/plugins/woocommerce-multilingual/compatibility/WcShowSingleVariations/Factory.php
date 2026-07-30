<?php

namespace WCML\Compatibility\WcShowSingleVariations;

use WCML\Compatibility\ComponentFactory;
use WCML_JCK_WSSV;

class Factory extends ComponentFactory {

	public function create() {
		return new WCML_JCK_WSSV();
	}
}
