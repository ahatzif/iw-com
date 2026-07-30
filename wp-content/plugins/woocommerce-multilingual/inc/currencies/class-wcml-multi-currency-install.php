<?php

use function WCML\functions\getSitePress;

class WCML_Multi_Currency_Install {

	private $multi_currency;
	private $woocommerce_wpml;

	public function __construct( WCML_Multi_Currency $multi_currency, woocommerce_wpml $woocommerce_wpml ) {

		$this->multi_currency   = $multi_currency;
		$this->woocommerce_wpml = $woocommerce_wpml;

		$wcml_settings = $this->woocommerce_wpml->get_settings();

		if ( empty( $wcml_settings['multi_currency']['set_up'] ) ) {
			$wcml_settings['multi_currency']['set_up'] = 1;
			$this->woocommerce_wpml->update_settings( $wcml_settings );

			$this->set_default_currencies_languages();
		}
	}

	public function set_default_currencies_languages( $old_value = false, $new_value = false ) {
		$settings         = $this->woocommerce_wpml->get_settings();
		$active_languages = getSitePress()->get_active_languages();
		$wc_currency      = $new_value ?: wcml_get_woocommerce_currency_option();

		if ( $old_value !== $new_value ) {
			$settings = WCML_Multi_Currency_Configuration::currency_options_update_default_currency( $settings, $old_value, $new_value );
		}

		foreach ( $this->multi_currency->get_currency_codes() as $code ) {
			if ( $code === $old_value ) {
				continue;
			}
			foreach ( $active_languages as $language ) {
				if ( ! isset( $settings['currency_options'][ $code ]['languages'][ $language['code'] ] ) ) {
					$settings['currency_options'][ $code ]['languages'][ $language['code'] ] = 1;
				}
			}
		}

		foreach ( $active_languages as $language ) {
			if ( ! isset( $settings['default_currencies'][ $language['code'] ] ) ) {
				$settings['default_currencies'][ $language['code'] ] = false;
			}

			if ( ! isset( $settings['currency_options'][ $wc_currency ]['languages'][ $language['code'] ] ) ) {
				$settings['currency_options'][ $wc_currency ]['languages'][ $language['code'] ] = 1;
			}
		}

		$this->woocommerce_wpml->update_settings( $settings );

	}

}
