<?php

class WCML_Currencies {

	private $woocommerce_wpml;

	public function __construct( woocommerce_wpml $woocommerce_wpml ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	public function add_hooks() {
		if ( is_admin() ) {
			add_action(
				'update_option_woocommerce_currency',
				[
					$this,
					'setup_multi_currency_on_currency_update',
				],
				10,
				2
			);
		}
	}

	public function setup_multi_currency_on_currency_update( $old_value, $new_value ) {
		if ( wcml_is_multi_currency_on() ) {
			$multi_currency_install = new WCML_Multi_Currency_Install( new WCML_Multi_Currency(), $this->woocommerce_wpml );
			$multi_currency_install->set_default_currencies_languages( $old_value, $new_value );
		} else {
			$currency_options               = $this->woocommerce_wpml->get_setting( 'currency_options' );
			if ( isset( $currency_options[ $old_value ] ) ) {
				$currency_options[ $new_value ] = $currency_options[ $old_value ];
				unset( $currency_options[ $old_value ] );
				$this->woocommerce_wpml->update_setting( 'currency_options', $currency_options );
			}
		}

	}

}
