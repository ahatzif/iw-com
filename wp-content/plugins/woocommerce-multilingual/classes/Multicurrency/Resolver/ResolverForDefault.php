<?php

namespace WCML\MultiCurrency\Resolver;

class ResolverForDefault implements Resolver {

	public function getClientCurrency() {
		return wcml_get_woocommerce_currency_option();
	}
}
