<?php

use WCML\MultiCurrency\Resolver\Factory as ResolverFactory;
use WCML\MultiCurrency\Resolver\HelperByLanguage as ResolverHelperByLang;
use WCML\Rest\Functions;
use WPML\FP\Obj;
use function WCML\functions\getSitePress;
use function WPML\Container\make;
use WCML\MultiCurrency\ExchangeRateServices\Service;
use WCML\MultiCurrency\ExchangeRateServices\Fixerio;
use WCML\MultiCurrency\ExchangeRateServices\CurrencyLayer;
use WCML\MultiCurrency\ExchangeRateServices\ExchangeRatesApi;
use WCML\MultiCurrency\ExchangeRateServices\OpenExchangeRates;
use WPML\API\Sanitize;

class WCML_Multi_Currency {

	const CURRENCY_STORAGE_KEY          = 'client_currency';
	const CURRENCY_LANGUAGE_STORAGE_KEY = 'client_currency_language';

	public $currencies = [];
	public $currency_codes = [];

	private $default_currency;
	private $client_currency;
	private $exchange_rates = [];
	public $currencies_without_cents = [ 'JPY', 'TWD', 'KRW', 'BIF', 'BYR', 'CLP', 'GNF', 'ISK', 'KMF', 'PYG', 'RWF', 'VUV', 'XAF', 'XOF', 'XPF' ];

	public $prices;
	public $coupons;
	public $shipping;

	public $reports;
	public $orders;
	public $admin_currency_selector;
	public $custom_prices;
	public $currency_switcher;
	public $currency_switcher_ajax;
	public $install;
	public $W3TC = null;

	public $woocommerce_wpml;

	public $woocommerce;

	public $exchange_rate_services;

	public $currencies_payment_gateways;

	public $load_filters;

	public $switching_currency_html;

	private $rest_currency;

	public function __construct() {
		global $woocommerce_wpml, $woocommerce, $wpdb, $wp;

		$sitepress = getSitePress();

		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->woocommerce      = $woocommerce;

		$this->install = new WCML_Multi_Currency_Install( $this, $woocommerce_wpml );

		$this->init_currencies();

		$this->load_filters = $this->are_filters_need_loading();
		$this->prices       = new WCML_Multi_Currency_Prices( $this, $woocommerce_wpml->get_setting( 'currency_options' ) );
		$this->prices->add_hooks();
		if ( $this->load_filters ) {
			$table_rate_shipping_multi_currency = new WCML_Multi_Currency_Table_Rate_Shipping();
			$table_rate_shipping_multi_currency->add_hooks();

			$this->coupons  = new WCML_Multi_Currency_Coupons();
			$this->shipping = new WCML_Multi_Currency_Shipping( $this, $wpdb );
			$this->shipping->add_hooks();
		}
		$this->reports = new WCML_Multi_Currency_Reports( $woocommerce_wpml, $wpdb );
		$this->reports->add_hooks();
		$this->orders                  = new WCML_Multi_Currency_Orders( $this, $woocommerce_wpml, $wp );
		$this->admin_currency_selector = new WCML_Admin_Currency_Selector(
			$woocommerce_wpml,
			new WCML_Admin_Cookie( '_wcml_dashboard_currency' )
		);
		$this->admin_currency_selector->add_hooks();
		$this->custom_prices = new WCML_Custom_Prices( $woocommerce_wpml, $wpdb );
		$this->custom_prices->add_hooks();
		$this->currency_switcher = new WCML_Currency_Switcher( $woocommerce_wpml, $sitepress );
		$this->currency_switcher->add_hooks();
		$this->currency_switcher_ajax = new WCML_Currency_Switcher_Ajax( $woocommerce_wpml );

		$this->exchange_rate_services = make( \WCML_Exchange_Rates::class );
		$this->exchange_rate_services->initialize_settings();
		$this->exchange_rate_services->add_actions();

		wpml_collect(
			[
				new CurrencyLayer(),
				new ExchangeRatesApi(),
				new Fixerio(),
				new OpenExchangeRates(),
			]
		)->each(
			function( Service $service ) {
				$this->exchange_rate_services->add_service( $service->getId(), $service );
			}
		);

		$this->currencies_payment_gateways = make( WCML_Currencies_Payment_Gateways::class );
		$this->currencies_payment_gateways->add_hooks();

		if ( defined( 'W3TC' ) ) {
			$this->W3TC = new WCML_W3TC_Multi_Currency();
		}

		WCML_Multi_Currency_Resources::set_up( $this, $this->woocommerce_wpml );
		WCML_Multi_Currency_Configuration::set_up( $this, $woocommerce_wpml );

		add_filter( 'init', [ $this, 'init' ], 5 );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_nopriv_wcml_switch_currency', [ $this, 'switch_currency' ] );
			add_action( 'wp_ajax_wcml_switch_currency', [ $this, 'switch_currency' ] );
		}

	}

	public function are_filters_need_loading() {
		$load = false;

		if ( ! is_admin() && $this->get_client_currency() !== wcml_get_woocommerce_currency_option() ) {
			$load = true;
		} else {
			if ( wp_doing_ajax() && $this->get_client_currency() !== wcml_get_woocommerce_currency_option() ) {

				$ajax_actions = apply_filters(
					'wcml_multi_currency_ajax_actions',
					[
						'woocommerce_get_refreshed_fragments',
						'woocommerce_update_order_review',
						'woocommerce_checkout',
						'woocommerce_add_to_cart',
						'woocommerce_update_shipping_method',
						'woocommerce_json_search_products_and_variations',
						'woocommerce_add_coupon_discount',

					]
				);

				if ( ( isset( $_POST['action'] ) && in_array( $_POST['action'], $ajax_actions ) ) ||
					 ( isset( $_GET['action'] ) && in_array( $_GET['action'], $ajax_actions ) ) ) {
					$load = true;
				}
			}
		}

		$load = apply_filters( 'wcml_load_multi_currency', $load );

		$load = apply_filters( 'wcml_load_multi_currency_in_ajax', $load );

		return $load;
	}

	public function init() {

		add_filter( 'wcml_get_client_currency', [ $this, 'get_client_currency' ] );
		add_action( 'wcml_multi_currency_set_switching_currency_html', function( $html ) {
			$this->switching_currency_html = $html;
		} );
		add_action( 'wp_footer', [ $this, 'maybe_show_switching_currency_prompt_dialog' ] );
		add_action( 'wp_footer', [ $this, 'maybe_reset_cart_fragments' ] );

		if ( WCML\Rest\Functions::isRestApiRequest() ) {
			add_filter( 'rest_request_before_callbacks', [ $this, 'set_request_currency' ], 10, 3 );
		}
	}

	public function enable() {
		$this->woocommerce_wpml->settings['enable_multi_currency'] = WCML_MULTI_CURRENCIES_INDEPENDENT;
		$this->woocommerce_wpml->update_settings();
	}

	public function disable() {
		$this->woocommerce_wpml->settings['enable_multi_currency'] = WCML_MULTI_CURRENCIES_DISABLED;
		$this->woocommerce_wpml->update_settings();
	}

	public function init_currencies() {
		$sitepress = getSitePress();

		$this->default_currency = wcml_get_woocommerce_currency_option();
		$this->currencies       = $this->woocommerce_wpml->settings['currency_options'];

		if ( ! empty( $this->default_currency ) && ! isset( $this->currencies[ $this->default_currency ] ) ) {
			$this->currencies[ $this->default_currency ] = [];
		}

		$save_to_db = false;

		$active_languages = $sitepress->get_active_languages();

		$currency_defaults = [
			'rate'               => 0,
			'position'           => 'left',
			'thousand_sep'       => ',',
			'decimal_sep'        => '.',
			'num_decimals'       => 2,
			'rounding'           => 'disabled',
			'rounding_increment' => 1,
			'auto_subtract'      => 0,
			'location_mode'      => 'all',
			'countries'          => [],
		];

		foreach ( $this->currencies as $code => $currency ) {
			foreach ( $currency_defaults as $key => $val ) {
				if ( ! isset( $currency[ $key ] ) ) {
					$this->currencies[ $code ][ $key ] = $val;
					$save_to_db                        = true;
				}
			}

			foreach ( $active_languages as $language ) {
				if ( ! isset( $currency['languages'][ $language['code'] ] ) ) {
					$this->currencies[ $code ]['languages'][ $language['code'] ] = 1;
					$save_to_db = true;
				}
			}
		}

		$this->currency_codes = array_keys( $this->currencies );

		foreach ( $active_languages as $language ) {
			if ( ! isset( $this->woocommerce_wpml->settings['default_currencies'][ $language['code'] ] ) ) {
				$this->woocommerce_wpml->settings['default_currencies'][ $language['code'] ] = 0;
				$save_to_db = true;
			}
		}

		if ( isset( $this->woocommerce_wpml->settings['default_currencies'] ) ) {
			foreach ( $this->woocommerce_wpml->settings['default_currencies'] as $language => $value ) {
				if ( ! isset( $active_languages[ $language ] ) ) {
					unset( $this->woocommerce_wpml->settings['default_currencies'][ $language ] );
					$save_to_db = true;
				}
				if ( ! empty( $value ) && ! in_array( $value, $this->currency_codes ) && $value !== 'location' ) {
					$this->woocommerce_wpml->settings['default_currencies'][ $language ] = 0;
					$save_to_db = true;
				}
			}
		}

		if ( isset( $this->woocommerce_wpml->settings['currencies_order'] ) ) {
			foreach ( $this->currency_codes as $currency ) {
				if ( ! in_array( $currency, $this->woocommerce_wpml->settings['currencies_order'] ) ) {
					$this->woocommerce_wpml->settings['currencies_order'][] = $currency;
					$save_to_db = true;
				}
			}
		}

		if ( $save_to_db && is_admin() ) {
			$this->woocommerce_wpml->settings['currency_options'] = $this->currencies;
			$this->woocommerce_wpml->update_settings();
		}

		if ( empty( $this->default_currency ) ) {
			$this->woocommerce_wpml->settings['enable_multi_currency'] = WCML_MULTI_CURRENCIES_DISABLED;
		}

	}

	public function get_default_currency() {
		return $this->default_currency;
	}

	public function get_currencies( $include_default = false ) {

		$currencies       = [];
		$default_currency = wcml_get_woocommerce_currency_option();

		foreach ( $this->currencies as $key => $value ) {
			if ( $default_currency != $key || $include_default ) {
				$currencies[ $key ] = $value;
			}
		}

		return $currencies;
	}

	public function get_currency_codes() {
		return $this->currency_codes;
	}

	public function is_currency_active( $code ) {
		return in_array( $code, $this->get_currency_codes(), true );
	}

	public function get_currency_code() {
		$currency_code  = wcml_get_woocommerce_currency_option();
		$currency_codes = $this->get_currency_codes();
		if ( ! in_array( $currency_code, $currency_codes, true ) ) {
			$currency_code = $this->woocommerce_wpml->multi_currency->get_default_currency();
		}

		return $currency_code;
	}

	public function get_currency_details_by_code( $code ) {

		if ( isset( $this->currencies[ $code ] ) ) {
			return $this->currencies[ $code ];
		}

		return false;
	}

	public function delete_currency_by_code( $code, $settings = false, $update = true ) {
		$settings = $settings ?: $this->woocommerce_wpml->get_settings();
		unset( $settings['currency_options'][ $code ] );

		if ( isset( $settings['currencies_order'] ) ) {
			foreach ( $settings['currencies_order'] as $key => $cur_code ) {
				if ( $cur_code == $code ) {
					unset( $settings['currencies_order'][ $key ] );
				}
			}
		}

		if ( $update ) {
			$this->woocommerce_wpml->update_settings( $settings );
		}

		return $settings;
	}

	public function get_exchange_rates() {

		if ( empty( $this->exchange_rates ) ) {

			$this->exchange_rates = [ wcml_get_woocommerce_currency_option() => 1 ];
			$woo_currencies       = get_woocommerce_currencies();

			$currencies = $this->get_currencies();
			foreach ( $currencies as $code => $currency ) {
				if ( ! empty( $woo_currencies[ $code ] ) ) {
					$this->exchange_rates[ $code ] = $currency['rate'];
				}
			}
		}

		return apply_filters( 'wcml_exchange_rates', $this->exchange_rates );
	}

	public function get_client_currency() {
		if ( Functions::isRestApiRequest() ) {
			return $this->get_rest_currency();
		} elseif ( ! wcml_is_multi_currency_on() ) {
			return apply_filters( 'wcml_client_currency', wcml_get_woocommerce_currency_option() );
		} elseif ( null === $this->client_currency ) {
			$this->client_currency = ResolverFactory::create()->getClientCurrency();

			if ( $this->client_currency ) {
				wcml_user_store_set( self::CURRENCY_STORAGE_KEY, $this->client_currency );
				wcml_user_store_set( self::CURRENCY_LANGUAGE_STORAGE_KEY, ResolverHelperByLang::getCurrentLanguage() );
			}
		}

		$filteredCurrency = apply_filters( 'wcml_client_currency', $this->client_currency );

		if ( $filteredCurrency !== $this->client_currency ) {
			$this->client_currency = $filteredCurrency;
			wcml_user_store_set( self::CURRENCY_STORAGE_KEY, $this->client_currency );
		}

		return $this->client_currency;
	}

	public function maybe_show_switching_currency_prompt_dialog() {
		if ( $this->switching_currency_html ) {
			echo $this->switching_currency_html;
		}
	}

	public function maybe_reset_cart_fragments() {
		if ( wcml_user_store_get( 'client_currency_switched' ) ) {
			?>
			<script type="text/javascript">
				jQuery(function () {
					wcml_reset_cart_fragments();
				});
			</script>
			<?php
			wcml_user_store_set( 'client_currency_switched', false );
		}

	}

	public function set_client_currency( $currency ) {
		$this->client_currency = $currency;

		wcml_user_store_set( self::CURRENCY_STORAGE_KEY, $currency );
		wcml_user_store_set( self::CURRENCY_LANGUAGE_STORAGE_KEY, getSitePress()->get_current_language() );

		do_action( 'wcml_set_client_currency', $currency );

	}

	public function switch_currency() {
		$sitepress = getSitePress();

		$currency     = filter_input( INPUT_POST, 'currency', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$force_switch = filter_input( INPUT_POST, 'force_switch', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		parse_str( Sanitize::stringProp( 'params', $_POST ), $params );
		$from_currency = $this->client_currency;

		do_action( 'wcml_before_switch_currency', $currency, $force_switch );

		if ( ! $force_switch && apply_filters( 'wcml_switch_currency_exception', false, $from_currency, $currency ) ) {
			die();
		}

		$lang = Obj::prop( 'lang', $params );

		if ( $lang ) {
			$sitepress->switch_lang( $lang );
		}

		$this->set_client_currency( $currency );

		global $woocommerce, $current_user;
		if ( empty( $woocommerce->session->data ) && empty( $current_user->ID ) ) {
			$woocommerce->session->set_customer_session_cookie( true );
		}

		wcml_user_store_set( 'client_currency_switched', true );

		do_action( 'wcml_switch_currency', $currency, $from_currency );

		$response = $this->prices->filter_pre_selected_widget_prices_in_new_currency( [], $currency, $from_currency, $params );

		wp_send_json_success( $response );
	}

	public function get_currencies_without_cents() {

		return apply_filters( 'wcml_currencies_without_cents', $this->currencies_without_cents );
	}

	public function set_request_currency( $response, $handler, $request ) {
		$this->rest_currency   = Obj::prop( 'currency', $request->get_params() ) ?: wcml_get_woocommerce_currency_option();
		$this->client_currency = $this->rest_currency;

		return $response;
	}

	public function get_rest_currency() {
		return $this->rest_currency ?: wcml_get_woocommerce_currency_option();
	}

}
