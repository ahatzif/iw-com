<?php

namespace WCML\MultiCurrency;

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use function WCML\functions\getSetting;
use function WCML\functions\isStandAlone;
use function WCML\functions\updateSetting;

class Settings {

	const MODE_BY_LANGUAGE = 'by_language';
	const MODE_BY_LOCATION = 'by_location';

	public static function getMode() {
		$persistedMode = getSetting( 'currency_mode' );

		if ( self::MODE_BY_LANGUAGE === $persistedMode && isStandAlone() ) {
			return self::MODE_BY_LOCATION;
		}

		return $persistedMode;
	}

	public static function isModeByLanguage() {
		return self::getMode() === self::MODE_BY_LANGUAGE;
	}

	public static function isModeByLocation() {
		return self::getMode() === self::MODE_BY_LOCATION;
	}

	public static function setMode( $mode ) {
		updateSetting( 'currency_mode', $mode );
	}

	public static function isDisplayOnlyCustomPrices() {
		return (bool) getSetting( 'display_custom_prices' );
	}

	public static function isActiveCurrency( $code ) {
		return Lst::includes( $code, self::getActiveCurrencyCodes() );
	}

	public static function getActiveCurrencyCodes() {
		return Obj::keys( self::getCurrenciesOptions() );
	}

	public static function getCurrenciesOptions() {
		return (array) getSetting( 'currency_options' );
	}

	public static function getCurrenciesOption( $keyOrPath, $default = null ) {
		return Obj::pathOr( $default, (array) $keyOrPath, self::getCurrenciesOptions() );
	}

	public static function isValidCurrencyByCountry( $currency, $clientCountry ) {
		$currencySettings = self::getCurrenciesOption( $currency );

		$isLocationMode = Relation::propEq( 'location_mode', Fns::__, $currencySettings );

		$containsCountry = Lst::includes( Fns::__, (array) Obj::prop( 'countries', $currencySettings ) );

		if ( $isLocationMode( 'all' ) ) {
			return true;
		} elseif ( $isLocationMode( 'include' ) && $containsCountry( $clientCountry ) ) {
			return true;
		} elseif ( $isLocationMode( 'exclude' ) && ! $containsCountry( $clientCountry ) ) {
			return true;
		}

		return false;
	}

	public static function getFirstAvailableCurrencyByCountry( $country ) {
		$isValidCurrency = function( $currency ) use ( $country ) {
			return self::isValidCurrencyByCountry( $currency, $country );
		};

		return wpml_collect( self::getActiveCurrencyCodes() )->first( $isValidCurrency );
	}

	public static function getDefaultCurrencies() {
		return (array) getSetting( 'default_currencies' );
	}

	public static function getOrderedCurrencyCodes() {
		return (array) getSetting( 'currencies_order' ) ?: self::getActiveCurrencyCodes();
	}

	public static function isDefaultCurrencyByLocationForLang( $lang ) {
		return 'location' === self::getDefaultCurrencyForLang( $lang );
	}

	public static function getDefaultCurrencyForLang( $lang ) {
		return (string) Obj::propOr( '', $lang, self::getDefaultCurrencies() );
	}

	public static function isValidCurrencyForLang( $currency, $lang ) {
		return (bool) self::getCurrenciesOption( [ $currency, 'languages', $lang ] );
	}

	public static function getFirstAvailableCurrencyForLang( $lang ) {
		return (string) wpml_collect( self::getCurrenciesOptions() )
			->filter( Obj::path( [ 'languages', $lang ] ) )
			->keys()
			->first();
	}

	public static function isAutomaticRateEnabled() {
		return (bool) Obj::prop( 'automatic', self::getAutomaticRateSettings() );
	}

	private static function getAutomaticRateSettings() {
		return Obj::propOr( [], 'exchange_rates', getSetting( 'multi_currency', [] ) );
	}
}
