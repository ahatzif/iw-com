<?php

use WCML\Utilities\AdminPages;
use function WCML\functions\getSitePress;
use function WCML\functions\isStandAlone;
use function WPML\Container\make;

class woocommerce_wpml {

	public $settings;
	public $troubleshooting;
	public $endpoints;
	public $products;
	public $sync_product_data;
	public $sync_variations_data;
	public $store;
	public $emails;
	public $terms;
	public $attributes;
	public $orders;
	public $currencies;
	public ?WCML_Multi_Currency $multi_currency = null;
	public $languages_upgrader;
	public $url_translation;
	public $coupons;
	public $locale;
	public $media;
	public $downloadable;
	public $strings;
	public $shipping;
	public ?WCML_WC_Gateways $gateways = null;
	public $cs_templates;
	public $comments;
	public $translation_editor;

	public $cart;
	public $cart_sync_warnings;

	public $requests;
	public $compatibility;
	public $cs_properties;
	public $duplicate_product;
	public $page_builders;

	private $wcml_products_screen;

	public function __construct() {
		$sitepress = getSitePress();

		$this->settings   = $this->get_settings();
		$this->currencies = new WCML_Currencies( $this );

		new WCML_Widgets( $this );

		add_action( 'init', [ $this, 'init' ], 2 );

		$this->cs_properties = new WCML_Currency_Switcher_Properties();
		$wpml_wp_api         = $sitepress->get_wp_api();
		$wpml_wp_api->get_wp_filesystem_direct();

		$this->cs_templates = new WCML_Currency_Switcher_Templates( $this, $wpml_wp_api, make( WPML_File::class ) );
		$this->cs_templates->init_hooks();

		$wc_shortccode_product_category = new WCML_WC_Shortcode_Product_Category( $sitepress );
		$wc_shortccode_product_category->add_hooks();
	}

	private function load_rest_api() {
		$sitepress = getSitePress();

		if ( class_exists( 'WooCommerce' ) && defined( 'WC_VERSION' ) && ( $sitepress instanceof \WPML\Core\ISitePress ) && WCML\Rest\Functions::isRestApiRequest() ) {
			WCML\Rest\Hooks::addHooks();
		}
	}

	public function add_hooks() {
		add_action( 'wpml_loaded', [ $this, 'load' ] );
		add_action( 'init', [ $this, 'init' ], 2 );
	}

	public function load() {
		do_action( 'wcml_loaded' );
	}

	public function init(): void {
		global $wpdb, $woocommerce, $wpml_url_converter, $wpml_post_translations, $wpml_term_translations;

		$sitepress = getSitePress();

		WCML_Admin_Menus::set_up_menus( $this, $sitepress, $wpdb );

		if ( ! \WPML\Container\make( WCML_Dependencies::class )->check() ) {
			if ( is_admin() && AdminPages::isWcmlSettings() ) {
				WCML_Capabilities::set_up_capabilities();

				wp_enqueue_style( 'otgs-icons' );

				WCML_Resources::load_management_css();
				WCML_Resources::load_tooltip_resources();
			}

			return;
		}

		new WCML_Upgrade();

		$actions_that_need_mc = [
			'save-mc-options',
			'wcml_save_currency',
			'wcml_delete_currency',
			'wcml_update_currency_lang',
			'wcml_update_default_currency',
			'wcml_price_preview',
			'wcml_currencies_switcher_preview',
			'wcml_currencies_switcher_save_settings',
			'wcml_delete_currency_switcher',
			'wcml_currencies_order',
			'wcml_set_currency_mode',
			'wcml_set_max_mind_key',
		];

		$this->cart = new WCML_Cart( $this, $sitepress, $woocommerce );

		$this->compatibility = new WCML_Compatibility();
		$this->compatibility->init_before_multicurrency();

		$action = filter_input( INPUT_POST, 'action' );
		if ( WCML_MULTI_CURRENCIES_INDEPENDENT === (int) $this->settings['enable_multi_currency']
			|| AdminPages::isMultiCurrency()
			|| ( in_array( $action, $actions_that_need_mc, true ) )
		) {
			$this->get_multi_currency();
			$wcml_price_filters = new WCML_Price_Filter( $this );
			$wcml_price_filters->add_hooks();
		} else {
			add_shortcode( 'currency_switcher', '__return_empty_string' );
		}

		$this->compatibility->init();

		if ( isStandAlone() ) {
			$this->init_standalone( $sitepress, $wpdb );
			return;
		} else {
			$this->init_full( $sitepress, $wpdb, $woocommerce, $wpml_url_converter, $wpml_post_translations, $wpml_term_translations );
			return;
		}
	}

	private function init_full( SitePress $sitepress, wpdb $wpdb, $woocommerce, $wpml_url_converter, $wpml_post_translations, $wpml_term_translations ): bool {
		$this->currencies = new WCML_Currencies( $this );
		$this->currencies->add_hooks();

		$this->sync_variations_data = new WCML_Synchronize_Variations_Data( $this, $sitepress, $wpdb );

		if ( is_admin() || wpml_is_rest_request() ) {
			$this->translation_editor = new WCML_Translation_Editor( $this, $sitepress, $wpdb );
			$this->translation_editor->add_hooks();
			$tp_support = new WCML_TP_Support( $this, $wpdb, new WPML_Element_Translation_Package(), $sitepress->get_setting( 'translation-management', [] ) );
			$tp_support->add_hooks();
			$sync_downloadable_files_from_ATE = new \WCML\DownloadableFiles\SyncDownloadableFilesFromATE();
			$sync_downloadable_files_from_ATE->add_hooks();
		}

		if ( is_admin() ) {
			$this->sync_variations_data->add_hooks();
			$this->troubleshooting      = new WCML_Troubleshooting( $this, $sitepress, $wpdb );
			$this->languages_upgrader   = new WCML_Languages_Upgrader();
			$this->wcml_products_screen = new WCML_Products_Screen_Options();
			$this->wcml_products_screen->init();
			$wcml_pointers = new WCML_Pointers();
			$wcml_pointers->add_hooks();
		}

		$this->sync_product_data = new WCML_Synchronize_Product_Data( $this, $sitepress, $wpml_post_translations, $wpdb );
		$this->sync_product_data->add_hooks();
		$this->duplicate_product = new WCML_WC_Admin_Duplicate_Product( $this, $sitepress, $wpdb );
		$this->products          = new WCML_Products( $this, $sitepress, $wpml_post_translations, $wpdb );
		$this->products->add_hooks();
		$this->store = new WCML_Store_Pages( $this, $sitepress );
		$this->store->add_hooks();
		$this->strings = new WCML_WC_Strings( $this, $sitepress, $wpdb );
		$this->strings->add_hooks();
		$this->emails = new WCML_Emails( $this->strings, $sitepress, $woocommerce );
		$this->emails->add_hooks();
		$this->terms = new WCML_Terms( $this, $sitepress, $wpdb );
		$this->terms->add_hooks();
		$this->attributes = new WCML_Attributes( $this, $sitepress, $wpml_post_translations, $wpml_term_translations, $wpdb );
		$this->attributes->add_hooks();
		$this->orders   = new WCML_Orders( $this, $sitepress );
		$this->shipping = new WCML_WC_Shipping( $sitepress );
		$this->shipping->add_hooks();
		$this->gateways = new WCML_WC_Gateways( $this, $sitepress );
		$this->gateways->add_hooks();
		$this->url_translation = new WCML_Url_Translation( $this, $sitepress, $wpdb );
		$this->url_translation->set_up();
		$this->endpoints = new WCML_Endpoints( $this, $sitepress, $wpdb );
		$this->endpoints->add_hooks();
		$this->requests = new WCML_Requests();
		$this->cart->add_hooks();
		$this->coupons = new WCML_Coupons( $sitepress );
		$this->coupons->add_hooks();
		$this->locale = new WCML_Locale( $sitepress );
		$this->media  = WCML\Media\Wrapper\Factory::create();
		$this->media->add_hooks();
		$this->downloadable = new WCML_Downloadable_Products( $this, $sitepress, $wpdb );
		$this->downloadable->add_hooks();
		$this->page_builders        = new WCML_Page_Builders( $sitepress );
		$this->wcml_products_screen = new WCML_Products_Screen_Options();
		$this->wcml_products_screen->init();
		$this->cart_sync_warnings = new WCML_Cart_Sync_Warnings( $this, $sitepress );
		$this->cart_sync_warnings->add_hooks();
		$this->comments = new WCML_Comments( $this, $sitepress, $wpml_post_translations, $wpdb );
		$this->comments->add_hooks();

		if ( is_admin() ) {
			$taxonomy_translation_link_filters = new WCML_Taxonomy_Translation_Link_Filters( $this->attributes );
			$taxonomy_translation_link_filters->add_filters();
		}

		$payment_method_filter = new WCML_Payment_Method_Filter();
		$payment_method_filter->add_hooks();

		$wcml_ajax_setup = new WCML_Ajax_Setup( $sitepress );
		$wcml_ajax_setup->add_hooks();
		WCML_Install::initialize( $this, $sitepress );

		WCML_Resources::set_up_resources( $this, $sitepress );
		WCML_Resources::add_hooks();

		$url_filters_redirect_location = new WCML_Url_Filters_Redirect_Location( $wpml_url_converter );
		$url_filters_redirect_location->add_hooks();

		$this->load_rest_api();

		return true;
	}

	private function init_standalone( \WPML\Core\ISitePress $sitepress, wpdb $wpdb ): bool {
		$this->currencies = new WCML_Currencies( $this );
		$this->currencies->add_hooks();

		if ( is_admin() ) {
			( new WCML_Pointers() )->add_hooks();
		}

		$this->products = new WCML_Products( $this, $sitepress, null, $wpdb );
		$this->products->add_hooks();
		$this->gateways = new WCML_WC_Gateways( $this, $sitepress );
		$this->gateways->add_hooks();
		$this->cart->add_hooks();

		WCML_Install::initialize( $this, $sitepress );

		WCML_Resources::set_up_resources( $this, $sitepress );
		WCML_Resources::add_hooks();

		return true;
	}

	public function get_settings() {
		$defaults = [
			\WCML_Downloadable_Products::SYNC_MODE_SETTING_KEY => (int) \WCML_Downloadable_Products::SYNC_MODE_SETTING_AUTO,
			'is_term_order_synced'                             => 0,
			'enable_multi_currency'                            => WCML_MULTI_CURRENCIES_DISABLED,
			'dismiss_doc_main'                                 => 0,
			'trnsl_interface'                                  => 1,
			'currency_options'                                 => [],
			'currency_switcher_product_visibility'             => 1,
			'dismiss_tm_warning'                               => 0,
			'dismiss_cart_warning'                             => 0,
			'cart_sync'                                        => [
				'lang_switch'     => WCML_CART_SYNC,
				'currency_switch' => WCML_CART_SYNC,
			],
		];

		if ( empty( $this->settings ) ) {
			$this->settings = get_option( '_wcml_settings', [] );
		}

		foreach ( $defaults as $key => $value ) {
			if ( ! isset( $this->settings[ $key ] ) ) {
				$this->settings[ $key ] = $value;
			}
		}

		return $this->settings;
	}

	public function get_setting( $key, $fallback = null ) {
		if ( array_key_exists( $key, $this->settings ) ) {
			return $this->settings[ $key ];
		}
		return get_option( 'wcml_' . $key, $fallback );
	}

	public function update_settings( $settings = null ) {
		if ( ! is_null( $settings ) ) {
			$this->settings = $settings;
		}
		update_option( '_wcml_settings', $this->settings );
	}

	public function update_setting( $key, $value, $autoload = false ) {
		if ( array_key_exists( $key, $this->settings ) ) {
			$this->settings [ $key ] = $value;
			$this->update_settings( $this->settings );
		} else {
			update_option( 'wcml_' . $key, $value, $autoload );
		}
	}

	public function get_stable_wc_version() {
		$file    = WC()->plugin_path() . '/readme.txt';
		$values  = file( $file );
		$wc_info = explode( ':', $values[5] );
		$version = '';

		if ( 'Stable tag' === $wc_info[0] ) {
			$version = trim( $wc_info[1] );
		} else {
			foreach ( $values as $value ) {
				$wc_info = explode( ':', $value );

				if ( 'Stable tag' === $wc_info[0] ) {
					$version = trim( $wc_info[1] );
				}
			}
		}

		return $version;
	}

	public function get_supported_wp_version() {
		$file = WCML_PLUGIN_PATH . '/readme.txt';

		$values = file( $file );

		$version = explode( ':', $values[6] );

		if ( 'Tested up to' === $version[0] ) {
			return $version[1];
		}

		foreach ( $values as $value ) {
			$version = explode( ':', $value );

			if ( 'Tested up to' === $version[0] ) {
				return $version[1];
			}
		}

		return '';
	}

	public function get_wc_query_vars() {
		return WooCommerce::instance()->query->query_vars;
	}

	public function get_multi_currency() {
		if ( ! ( $this->multi_currency instanceof WCML_Multi_Currency ) ) {
			$this->multi_currency = make( WCML_Multi_Currency::class );
		}
		return $this->multi_currency;
	}

	public function version() {
		return get_option( '_wcml_version' );
	}

	public function plugin_url() {
		return WCML_PLUGIN_URL;
	}

	public function js_min_suffix() {
		return WCML_JS_MIN;
	}

	public function is_wpml_prior_4_2() {
		$sitepress = getSitePress();

		return $sitepress->get_wp_api()->version_compare( $this->get_constant( 'ICL_SITEPRESS_VERSION' ), '4.2.0', '<' ) ||
			$sitepress->get_wp_api()->version_compare( $this->get_constant( 'WPML_TM_VERSION' ), '2.8.0', '<' );
	}

	private function get_constant( $name ) {
		return getSitePress()->get_wp_api()->constant( $name );
	}
}
