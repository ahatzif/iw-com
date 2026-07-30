<?php

namespace WCML\Compatibility\WcBulkStockManagement;

use WCML\Compatibility\ComponentFactory;
use WCML_Bulk_Stock_Management;

class Factory extends ComponentFactory {

	public function create() {
		return new WCML_Bulk_Stock_Management();
	}
}