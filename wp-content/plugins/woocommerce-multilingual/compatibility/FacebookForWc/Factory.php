<?php

namespace WCML\Compatibility\FacebookForWc;

use WCML\Compatibility\ComponentFactory;

class Factory extends ComponentFactory {

	public function create() {
		return new MultilingualHooks();
	}
}
