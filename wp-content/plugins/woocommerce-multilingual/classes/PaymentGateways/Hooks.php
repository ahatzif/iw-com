<?php

namespace WCML\PaymentGateways;

use IWPML_Backend_Action;
use IWPML_Frontend_Action;
use IWPML_DIC_Action;
use WCML\MultiCurrency\Geolocation;
use WCML\StandAlone\IStandAloneAction;
use WCML\Utilities\Resources;
use WCML\Utilities\AdminUrl;
use WPML\API\Sanitize;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Str;
use WPML\FP\Type;

class Hooks implements IWPML_Backend_Action, IWPML_Frontend_Action, IWPML_DIC_Action, IStandAloneAction {

	const OPTION_KEY = 'wcml_payment_gateways';
	const PRIORITY = 1000;

	public function add_hooks() {

		if ( is_admin() ) {
			if ( $this->isWCGatewaysSettingsScreen() ) {
				add_action( 'woocommerce_update_options_checkout', [ $this, 'updateSettingsOnSave' ], self::PRIORITY );
				add_action( 'woocommerce_settings_checkout', [ $this, 'outputInSettings' ], self::PRIORITY );
				add_action( 'woocommerce_after_settings_checkout', [ $this, 'outputAfterSettings' ], self::PRIORITY );
				if( $this->isWooC10_1_Spa() ) {
					add_action( 'admin_enqueue_scripts', [ $this, 'loadSPAAssets' ] );
				} else {
					add_action( 'admin_enqueue_scripts', [ $this, 'loadClassicAssets' ] );
				}
			}
			add_action( 'admin_notices', [ $this, 'maybeAddNotice' ] );
			add_action( 'wp_ajax_wcml_save_payment_gateways', [ $this, 'updateSettingsOnAjaxSave' ] );
		} else {
			add_filter( 'woocommerce_available_payment_gateways', [ $this, 'filterByCountry' ], self::PRIORITY );
		}
	}

	public function updateSettingsOnSave() {

		if ( isset( $_POST[ self::OPTION_KEY ] ) ) {

			$gatewaySettings = $_POST[ self::OPTION_KEY ];
			$gatewayId       = Sanitize::stringProp( 'ID', $gatewaySettings );

			$this->updateGatewaySettings( $gatewayId, $gatewaySettings );
		}
	}

	public function updateSettingsOnAjaxSave() {
		$nonce = array_key_exists( 'nonce', $_POST ) ? sanitize_text_field( $_POST['nonce'] ) : false;
		if ( ! $nonce || ! wp_verify_nonce( $nonce, self::OPTION_KEY ) ) {
			wp_send_json_error();
		}

		$data            = json_decode( stripslashes( Obj::propOr( '{}', 'data', $_POST ) ), true );
		$gatewayId       = Obj::prop( 'gatewayId', $data );
		$gatewaySettings = Obj::prop( 'settings', $data );

		$updated = $this->updateGatewaySettings( $gatewayId, $gatewaySettings );

		if ( $updated ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}

	}

	private function updateGatewaySettings( $gatewayId, $gatewaySettings ) {
		$settings          = $this->getSettings();
		$settingsSignature = maybe_serialize( $settings );

		$settings[ $gatewayId ]['mode'] = isset( $gatewaySettings['mode'] ) && in_array( $gatewaySettings['mode'], [
			'all',
			'exclude',
			'include'
		], true ) ? $gatewaySettings['mode'] : 'all';

		$countries = Logic::ifElse(
			Type::isString(),
			Str::split( ',' ),
			Fns::identity(),
			Obj::propOr( [], 'countries', $gatewaySettings )
		);

		$validCountries = wpml_collect( WC()->countries->get_countries() )->keys()->toArray();

		$isValidCountry = function( $country ) use ( $validCountries ) {
			return in_array( $country, $validCountries, true );
		};

		$settings[ $gatewayId ]['countries'] = wpml_collect( $countries )
			->filter( $isValidCountry )
			->values()
			->toArray();

		$updatedSettingsSignature = maybe_serialize( $settings );

		if ( $settingsSignature === $updatedSettingsSignature ) {
			return true;
		}

		return $this->updateSettings( $settings );
	}

	public function loadClassicAssets() {
		$enqueue   = Resources::enqueueApp( 'paymentGatewaysAdmin' );
		$gatewayId = sanitize_title( $_GET['section'] );

		$enqueue( [
			'name' => 'wcmlPaymentGateways',
			'data' => [
				'endpoint'     => self::OPTION_KEY,
				'gatewayId'    => $gatewayId,
				'allCountries' => $this->getAllCountries(),
				'strings'      => $this->getStrings(),
				'settings'     => $this->getGatewaySettings( $gatewayId ),
			],
		] );

		wp_register_style( 'wcml-payment-gateways', WCML_PLUGIN_URL . '/res/css/wcml-payment-gateways.css', [], WCML_VERSION );
		wp_enqueue_style( 'wcml-payment-gateways' );
	}

	public function loadSpaAssets() {
		$enqueue   = Resources::enqueueApp( 'paymentGatewaysAdminSPA' );

		$allGatewaySettings             = get_option( self::OPTION_KEY, [] );
		$allGatewaySettings['_default'] = [ 'mode' => 'all', 'countries' => [] ];

		$enqueue( [
			'name' => 'wcmlPaymentGateways',
			'data' => [
				'endpoint'     => self::OPTION_KEY,
				'gatewayId'    => null,
				'allCountries' => $this->getAllCountries(),
				'strings'      => $this->getStrings(),
				'settings'     => $allGatewaySettings,
			],
		] );

		wp_register_style( 'wcml-payment-gateways', WCML_PLUGIN_URL . '/res/css/wcml-payment-gateways.css', [], WCML_VERSION );
		wp_enqueue_style( 'wcml-payment-gateways' );
	}

	private function getStrings() {
		return [
			'translationInstructions'     => Strings::getTranslationInstructions(),
			'labelAvailability'           => __( 'Country availability', 'woocommerce-multilingual' ),
			'labelAllCountries'           => __( 'All countries', 'woocommerce-multilingual' ),
			'labelAllCountriesExcept'     => __( 'All countries except', 'woocommerce-multilingual' ),
			'labelAllCountriesExceptDots' => __( 'All countries except...', 'woocommerce-multilingual' ),
			'labelSpecificCountries'      => __( 'Specific countries', 'woocommerce-multilingual' ),
			'tooltip'                     => __( 'Configure per country availability for this payment gateway', 'woocommerce-multilingual' ),
			'submitButton'                => [
				'label'   => __( 'Save changes', 'woocommerce-multilingual' ),
				'success' => __( 'Settings saved.', 'woocommerce-multilingual' ),
				'error'   => __( 'Error saving settings.', 'woocommerce-multilingual' ),
			],
		];
	}

	private function getAllCountries() {

		$buildCountry = function ( $label, $code ) {
			return (object) [
				'code'  => $code,
				'label' => html_entity_decode( $label ),
			];
		};

		return wpml_collect( WC()->countries->get_countries() )->map( $buildCountry )->values()->toArray();
	}

	private function isSubmitButtonHidden() {
		return ! empty( $GLOBALS['hide_save_button'] );
	}

	public function outputInSettings() {
		if ( ! $this->isSubmitButtonHidden() ) {
			$this->output();
		}
	}

	public function outputAfterSettings() {
		if ( $this->isSubmitButtonHidden() ) {
			?>
			<div id="wcml-after-mainform">
			<?php
			$this->output();
			?>
			</div>
			<?php
		}
	}

	public function output() {
		?><h2>WPML Multilingual & Multicurrency for WooCommerce</h2><div id="wcml-payment-gateways"></div><?php
	}

	public function filterByCountry( $payment_gateways ) {

		$customer_country = Geolocation::getUserCountry();

		if ( $customer_country ) {

			$ifExceptCountries = function ( $gateway ) use ( $customer_country ) {
				$gatewaySettings = $this->getGatewaySettings( $gateway->id );

				return $gatewaySettings['mode'] == 'exclude' && in_array( $customer_country, $gatewaySettings['countries'] );
			};

			$ifNotIncluded = function ( $gateway ) use ( $customer_country ) {
				$gatewaySettings = $this->getGatewaySettings( $gateway->id );

				return $gatewaySettings['mode'] == 'include' && ! in_array( $customer_country, $gatewaySettings['countries'] );
			};

			return wpml_collect( $payment_gateways )
				->reject( $ifExceptCountries )
				->reject( $ifNotIncluded )
				->toArray();
		}

		return $payment_gateways;
	}

	public function maybeAddNotice(){
		if( class_exists( 'WooCommerce_Gateways_Country_Limiter' ) ) {
			echo $this->getNoticeText();
		}
	}

	private function getNoticeText(){

		$text = '<div id="message" class="updated error">';
		$text .= '<p>';
		$text .= __( 'We noticed that you\'re using WooCommerce Gateways Country Limiter plugin which is now integrated into WPML Multilingual & Multicurrency for WooCommerce. Please remove it!', 'woocommerce-multilingual' );
		$text .= '</p>';
		$text .= '</div>';

		return $text;
	}

	private function getGatewaySettings( $gatewayId ) {
		return Maybe::fromNullable( get_option( self::OPTION_KEY, false ) )
			->map( Obj::prop( $gatewayId ) )
			->getOrElse( [ 'mode' => 'all', 'countries' => [] ] );
	}

	private function getSettings() {
		return get_option( self::OPTION_KEY, [] );
	}

	private function updateSettings( $settings ) {
		return update_option( self::OPTION_KEY, $settings );
	}

	private function isWCGatewaysSettingsScreen() {
		if ( $this->isWooC10_1_Spa() ) {
			return $this->isWCGatewaysSettingsScreenSPA();
		}

		return Obj::prop( 'section', $_GET )
			&& Relation::equals( AdminUrl::PAGE_WOO_SETTINGS, Obj::prop( 'page', $_GET ) )
			&& Relation::equals( 'checkout', Obj::prop( 'tab', $_GET ) )
			&& ! Relation::propEq( 'section', 'offline', $_GET );
	}

	private function isWooC10_1_Spa(): bool {
		return (bool) Obj::prop( 'path', $_GET );
	}

	private function isWCGatewaysSettingsScreenSPA(): bool {
		return Obj::prop( 'path', $_GET )
			   && Relation::equals( AdminUrl::PAGE_WOO_SETTINGS, Obj::prop( 'page', $_GET ) )
			   && Relation::equals( 'checkout', Obj::prop( 'tab', $_GET ) );
	}

}
