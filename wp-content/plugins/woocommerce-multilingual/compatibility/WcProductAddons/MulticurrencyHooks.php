<?php

namespace WCML\Compatibility\WcProductAddons;

use WC_Product_Booking;
use WCML_Product_Addons;
use woocommerce_wpml;
use WPML_Twig_Template_Loader;

class MulticurrencyHooks implements \IWPML_Action {
	const ADDON_FIELD_TYPE_INPUT_MULTIPLIER = 'input_multiplier';
	const ADDON_PRICE_TYPE_QUANTITY_BASED   = 'quantity_based';
	const ADDON_PRICE_TYPE_FLAT_FEE         = 'flat_fee';

	const CONVERTABLE_ADDON_PRICE_TYPE_LIST = [
		self::ADDON_PRICE_TYPE_QUANTITY_BASED,
		self::ADDON_PRICE_TYPE_FLAT_FEE,
	];

	const TEMPLATE_FOLDER   = '/templates/compatibility/';
	const DIALOG_TEMPLATE   = 'product-addons-prices-dialog.twig';
	const SETTINGS_TEMPLATE = 'product-addons-prices-settings.twig';
	const PRICE_OPTION_KEY  = '_product_addon_prices';

	private $woocommerce_wpml;

	public function __construct( woocommerce_wpml $woocommerce_wpml ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	public function add_hooks() {
		add_filter( 'get_product_addons_fields', [ $this, 'product_addons_price_filter' ], 10, 2 );
		add_filter( 'wcml_cart_contents_not_changed', [ $this, 'filter_booking_addon_product_in_cart_contents' ], 20 );
		add_filter( 'wcml_product_addons_global_updated', [ $this, 'onGlobalAddonsUpdated' ], 10, 2 );

		if ( wp_doing_ajax() ) {
			add_action( 'wcml_switch_currency', [ $this, 'convertAddonPriceSavedInSession' ], 10, 2 );
		}

		if ( is_admin() ) {
			add_action( 'woocommerce_product_addons_panel_start', [ $this, 'load_dialog_resources' ] );
			add_action( 'woocommerce_product_addons_panel_option_row', [ $this, 'dialog_button_after_option_row' ], 10, 4 );
			add_action( 'woocommerce_product_addons_panel_before_options', [ $this, 'dialog_button_before_options' ], 10, 3 );
			add_action( 'wcml_before_sync_product', [ $this, 'update_custom_prices_values' ] );
			add_action( 'save_post', [ $this, 'maybeUpdateCustomPricesValues' ], 10, 2 );
			add_action( 'woocommerce_product_addons_global_edit_objects', [ $this, 'custom_prices_settings_block' ] );
		}
	}

	public function convertAddonPriceSavedInSession( $toCurrency, $fromCurrency ) {

		$cart = WC()->session->get( 'cart', null );

		if ( is_array( $cart ) ) {
			$save = false;
			foreach ( $cart as $itemId => $item ) {
				if ( isset( $item['addons'] ) && is_array( $item['addons'] ) ) {
					foreach ( $item['addons'] as $addonId => $addon ) {
						$addonFieldType = $addon['field_type'] ?? null;
						$addonPriceType = $addon['price_type'] ?? null;
						$addonPrice     = $addon['price'] ?? null;

						if ( self::ADDON_FIELD_TYPE_INPUT_MULTIPLIER === $addonFieldType ) {
							if ( in_array( $addonPriceType, self::CONVERTABLE_ADDON_PRICE_TYPE_LIST, true ) ) {
								$orgPrice = $this->woocommerce_wpml->multi_currency->prices->unconvert_price_amount( $addonPrice, $fromCurrency );
								$newPrice = $this->woocommerce_wpml->multi_currency->prices->convert_price_amount( $orgPrice, $toCurrency );

								$cart[ $itemId ]['addons'][ $addonId ]['price'] = $newPrice;

								$save = true;
							}
						}
					}
				}
			}
			if ( $save ) {
				WC()->session->set( 'cart', $cart );
			}
		}
	}

	public function maybeUpdateCustomPricesValues( $productId, $arg ) {
		if ( 'product' === get_post_type( $productId ) ) {
			$this->update_custom_prices_values( $productId );
		}
	}

	public function product_addons_price_filter( $addons, $postId ) {
		foreach ( $addons as $addonId => $addon ) {

			$addon_data = wpml_collect( $addon );

			if ( $addon_data->offsetExists( 'price' ) && $addon_data->get( 'price' ) ) {
				$addons[ $addonId ]['price'] = $this->converted_addon_price( $addon, $postId );
			}

			if ( $addon_data->offsetExists( 'options' ) ) {
				foreach ( $addon_data->get( 'options' ) as $key => $option ) {
					$addons[ $addonId ]['options'][ $key ]['price'] = $this->converted_addon_price( $option, $postId );
				}
			}
		}

		return $addons;
	}

	public function filter_booking_addon_product_in_cart_contents( $cartItem ) {
		$isBookingProductWithAddons = $cartItem['data'] instanceof WC_Product_Booking && isset( $cartItem['addons'] );

		if ( $isBookingProductWithAddons ) {
			$cost = $cartItem['data']->get_price();

			foreach ( $cartItem['addons'] as $addon ) {
				$cost += $addon['price'];
			}

			$cartItem['data']->set_price( $cost );
		}

		return $cartItem;
	}

	private function converted_addon_price( $addon, $postId ) {
		$addonData = wpml_collect( $addon );

		$isCustomPricesOn = $this->isProductCustomPricesOn( $postId );
		$field            = 'price_' . $this->woocommerce_wpml->multi_currency->get_client_currency();

		if (
			$isCustomPricesOn &&
			$addonData->get( $field )
		) {
			return $addonData->get( $field );
		}

		if ( wpml_collect( self::CONVERTABLE_ADDON_PRICE_TYPE_LIST )->contains( $addonData->get( 'price_type' ) ) ) {
			return apply_filters( 'wcml_raw_price_amount', $addonData->get( 'price' ) );
		}

		return $addonData->get( 'price' );
	}

	private function isProductCustomPricesOn( $productId ) {
		if ( $productId ) {
			return get_post_meta( $productId, '_wcml_custom_prices_status', true );
		}

		if ( SharedHooks::isGlobalAddonEditPage() ) {
			return $this->getGlobalAddonPricesStatus();
		}

		return false;
	}

	private function getGlobalAddonPricesStatus() {
		if ( isset( $_GET['edit'] ) ) {
			return get_post_meta( $_GET['edit'], '_wcml_custom_prices_status', true );
		} elseif ( isset( $_POST['_wcml_custom_prices'] ) ) {
			return $_POST['_wcml_custom_prices'];
		}

		return false;
	}

	public function load_dialog_resources() {
		wp_enqueue_script( 'wcml-dialogs', WCML_PLUGIN_URL . '/res/js/dialogs' . WCML_JS_MIN . '.js', [ 'jquery-ui-dialog', 'underscore' ], WCML_VERSION );
	}

	public function dialog_button_after_option_row( $product, $productAddons, $loop, $option ) {
		if ( $option ) {
			$this->renderEditPriceElement( $this->getPricesDialogModel( $productAddons, $option, $loop, $this->isProductCustomPricesOn( $product ? $product->ID : false ) ) );
		}
	}

	public function dialog_button_before_options( $product, $productAddons, $loop ) {
		$this->renderEditPriceElement( $this->getPricesDialogModel( [], $productAddons, $loop, $this->isProductCustomPricesOn( $product ? $product->ID : false ) ) );
	}

	public function onGlobalAddonsUpdated( $metaId, $id ) {
		$this->update_custom_prices_values( $id );
	}

	public function update_custom_prices_values( $productId ) {
		$this->saveGlobalAddonPricesSetting( $productId );
		$productAddons = SharedHooks::getProductAddons( $productId );

		if ( $productAddons ) {
			$activeCurrencies = $this->woocommerce_wpml->multi_currency->get_currencies();

			foreach ( $productAddons as $addonKey => $productAddon ) {

				foreach ( $activeCurrencies as $code => $currency ) {
					$priceOptionKey = self::PRICE_OPTION_KEY;

					if ( in_array( $productAddon['type'], self::getOnePriceTypes(), true ) ) {
						$productAddons = $this->updateSingleOptionPrices( $productAddons, $priceOptionKey, $addonKey, $code );
					} else {
						$productAddons = $this->updateMultipleOptionsPrices( $productAddons, $priceOptionKey, $addonKey, $code );
					}
				}
			}

			update_post_meta( $productId, WCML_Product_Addons::ADDONS_OPTION_KEY, $productAddons );
		}
	}

	private function updateSingleOptionPrices( $productAddons, $priceOptionKey, $addonKey, $code ) {
		if ( isset( $_POST[ $priceOptionKey ][ $addonKey ][ 'price_' . $code ][0] ) ) {
			$productAddons[ $addonKey ][ 'price_' . $code ] = wc_format_decimal( $_POST[ $priceOptionKey ][ $addonKey ][ 'price_' . $code ][0] );
		}

		return $productAddons;
	}

	private function updateMultipleOptionsPrices( $productAddons, $priceOptionKey, $addonKey, $code ) {
		$addon_data = wpml_collect( $productAddons[ $addonKey ] );

		if ( $addon_data->offsetExists( 'options' ) ) {
			foreach ( $addon_data->get( 'options' ) as $option_key => $option ) {
				if ( isset( $_POST[ $priceOptionKey ][ $addonKey ][ 'price_' . $code ][ $option_key ] ) ) {
					$productAddons[ $addonKey ]['options'][ $option_key ][ 'price_' . $code ] = wc_format_decimal( $_POST[ $priceOptionKey ][ $addonKey ][ 'price_' . $code ][ $option_key ] );
				}
			}
		}

		return $productAddons;
	}

	public function custom_prices_settings_block() {
		echo $this->getTwigLoader()->get_template()->show( $this->getCustomPricesSettingsModel(), self::SETTINGS_TEMPLATE );
	}

	private function renderEditPriceElement( $model ) {
		echo $this->getTwigLoader()->get_template()->show( $model, self::DIALOG_TEMPLATE );
	}

	private function getTwigLoader() {
		return new WPML_Twig_Template_Loader( [ WCML_PLUGIN_PATH . SharedHooks::TEMPLATE_FOLDER ] );
	}

	private function getCustomPricesSettingsModel() {
		return [
			'strings'          => [
				'label'    => __( 'Multi-currency settings', 'woocommerce-multilingual' ),
				'auto'     => __( 'Calculate prices in other currencies automatically', 'woocommerce-multilingual' ),
				'manually' => __( 'Set prices in other currencies manually', 'woocommerce-multilingual' ),
			],
			'custom_prices_on' => $this->getGlobalAddonPricesStatus(),
			'nonce'            => wp_create_nonce( 'wcml_save_custom_prices' ),
		];
	}

	private function getPricesDialogModel( $productAddons, $option, $loop, $customPricesOn ) {

		$label = isset( $option['label'] ) ? $option['label'] : $option['name'];

		return [
			'strings'           => [
				'dialog_title' => __( 'Multi-currency settings', 'woocommerce-multilingual' ),
				/* translators: %s is an option label */
				'description'  => sprintf( __( 'Here you can set different prices for the %s in multiple currencies:', 'woocommerce-multilingual' ), '<strong>' . $label . '</strong>' ),
				'apply'        => __( 'Apply', 'woocommerce-multilingual' ),
				'cancel'       => __( 'Cancel', 'woocommerce-multilingual' ),
			],
			'custom_prices_on'  => $customPricesOn,
			'dialog_id'         => '_product_addon_option_' . md5( uniqid( $loop . $label ) ),
			'option_id'         => isset( $productAddons[ $loop ]['options'] ) ? array_search( $option, $productAddons[ $loop ]['options'] ) : '',
			'addon_id'          => $loop,
			'option_details'    => $option,
			'default_currency'  => wcml_get_woocommerce_currency_option(),
			'active_currencies' => $this->woocommerce_wpml->multi_currency->get_currencies(),
		];
	}

	private function saveGlobalAddonPricesSetting( $productId ) {
		if ( SharedHooks::isGlobalAddon( $productId ) ) {
			$nonce = filter_var( isset( $_POST['_wcml_custom_prices_nonce'] ) ? $_POST['_wcml_custom_prices_nonce'] : '', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

			if ( isset( $_POST['_wcml_custom_prices'] ) && isset( $nonce ) && wp_verify_nonce( $nonce, 'wcml_save_custom_prices' ) ) {
				update_post_meta( $productId, '_wcml_custom_prices_status', $_POST['_wcml_custom_prices'] );
			}
		}
	}

	private static function getOnePriceTypes() {
		return [
			'custom_text',
			'custom_textarea',
			'file_upload',
			self::ADDON_FIELD_TYPE_INPUT_MULTIPLIER,
		];
	}
}
