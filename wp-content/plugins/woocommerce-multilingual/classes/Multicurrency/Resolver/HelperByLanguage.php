<?php

namespace WCML\MultiCurrency\Resolver;

use WCML\MultiCurrency\Geolocation;
use WCML\MultiCurrency\Settings;
use WCML\StandAlone\NullSitePress;
use WPML\FP\Fns;
use function WCML\functions\getSitePress;

class HelperByLanguage {

	private static $getCurrency;

	public static function getCurrencyByUserCountry( $currentLang ) {
		if ( ! self::$getCurrency ) {
			self::$getCurrency = Fns::memorize( function() use ( $currentLang ) {
				$clientCountry = Geolocation::getUserCountry();
				$currency      = Geolocation::getOfficialCurrencyCodeByCountry( $clientCountry );

				if ( ! Settings::isValidCurrencyForLang( $currency, $currentLang ) ) {
					$currency = Settings::getFirstAvailableCurrencyForLang( $currentLang );
				}

				return $currency ?: null;
			} );
		}

		return call_user_func( self::$getCurrency );
	}


	public static function getCurrentLanguage() {
		$currentLang = getSitePress()->get_current_language();

		if ( in_array( $currentLang, [ 'all', null, false ], true ) ) {
			$currentLang = getSitePress()->get_default_language();
		}

		if ( ! is_string( $currentLang ) ) {
			$currentLang = ( new NullSitePress() )->get_current_language();
		}

		return $currentLang;
	}
}
