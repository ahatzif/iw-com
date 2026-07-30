<?php

namespace WCML\MultiCurrency\ExchangeRateServices;

class OpenExchangeRates extends Service {

	public function getId() {
		return 'openexchangerates';
	}

	public function getName() {
		return 'Open Exchange Rates';
	}

	public function getUrl() {
		return 'https://openexchangerates.org/';
	}

	public function getApiUrl() {
		return 'https://openexchangerates.org/api/latest.json?app_id=%1$s&base=%2$s&symbols=%3$s';
	}

	public function isKeyRequired() {
		return true;
	}

}
