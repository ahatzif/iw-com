<?php

namespace WCML\Email\Settings;

use WCML\TranslationControls\Hooks as TranslationControlsBase;
use WCML\Utilities\WcAdminPages;
use WCML_Tracking_Link;
use WPML\FP\Obj;

class TranslationControls extends TranslationControlsBase {

	const OPTION_NAMES = [
		'woocommerce_new_order_settings',
		'woocommerce_cancelled_order_settings',
		'woocommerce_failed_order_settings',
		'woocommerce_customer_failed_order_settings',
		'woocommerce_customer_on_hold_order_settings',
		'woocommerce_customer_processing_order_settings',
		'woocommerce_customer_completed_order_settings',
		'woocommerce_customer_refunded_order_settings',
		'woocommerce_customer_invoice_settings',
		'woocommerce_customer_note_settings',
		'woocommerce_customer_reset_password_settings',
		'woocommerce_customer_new_account_settings',
	];

	const EMAIL_TEXT_KEYS = [
		'subject',
		'heading',
		'subject_downloadable',
		'heading_downloadable',
		'subject_full',
		'subject_partial',
		'heading_full',
		'heading_partial',
		'subject_paid',
		'heading_paid',
		'additional_content',
	];

	protected function addAdminPageHooks() {
		add_action( 'woocommerce_settings_email', [ $this, 'translationInstructions' ] );
		add_action( 'woocommerce_after_settings_email', [ $this, 'translationControls' ] );
		add_action( 'woocommerce_update_options_email', [ $this, 'registerStringsOnSave' ] );
	}

	protected function isAdminPage() {
		return WcAdminPages::isEmailSettings() && WcAdminPages::hasSection();
	}

	private function getCurrentEmailOptionName() {
		static $currentEmailOptionName = null;
		if ( ! is_null( $currentEmailOptionName ) ) {
			return $currentEmailOptionName;
		}

		$optionNames = apply_filters( 'wcml_emails_options_to_translate', self::OPTION_NAMES );

		$isCurrentOptionSection = function( $option ) {
			$sectionPrefix = apply_filters( 'wcml_emails_section_name_prefix', 'wc_email_', $option );
			$sectionName   = str_replace( 'woocommerce_', $sectionPrefix, $option );
			$sectionName   = str_replace( '_settings', '', $sectionName );
			$sectionName = apply_filters( 'wcml_emails_section_name_to_translate', $sectionName );
			return WcAdminPages::isSection( $sectionName );
		};

		$currentEmailOptionName = (string) wpml_collect( $optionNames )->filter( $isCurrentOptionSection )->first();
		return $currentEmailOptionName;
	}

	protected function getInstructionsWithRegisteredStrings( $domain, $search = '' ) {
		return sprintf(
			/* translators: %1$s and %2$s are opening and closing HTML link tags */
			esc_html__( 'To translate custom WooCommerce email notifications, go to the %1$sTranslation Dashboard%2$s.', 'woocommerce-multilingual' ),
			'<a href="' . esc_url( $this->getInstructionsLink( $domain, $search ) ) . '">',
			'</a>'
		);
	}

	public function translationInstructions() {
		$optionName = $this->getCurrentEmailOptionName();
		if ( ! $optionName ) {
			return;
		}

		$this->pointerFactory
			->create( [
				'content'    => $this->getInstructions( $this->getStringDomain( $optionName ) ),
				'selectorId' => 'wpbody-content .woocommerce table.form-table:nth-of-type(1)',
				'method'     => 'before',
				'docLink'    => WCML_Tracking_Link::getWcmlTranslateEmailsDoc(),
			] )->show();
	}

	protected function getTranslationControls() {
		$translationControls = [];
		$optionName          = $this->getCurrentEmailOptionName();
		if ( ! $optionName ) {
			return $translationControls;
		}

		$emailSettings = $this->getEmailSettings( $optionName );

		foreach ( $emailSettings as $settingKey => $settingValue ) {
			$translationControls[] = $this->getTranslationControl(
				$optionName,
				$settingKey,
				$settingValue,
				$this->getStringDomain( $optionName ),
				$this->getStringName( $optionName, $settingKey )
			);
		}

		return $translationControls;
	}

	public function registerStringsOnSave() {
		$itemsToProcess = wpml_collect( $_POST )
			->filter( function( $value, $key ) {
				return substr( $key, 0, 9 ) === self::KEY_PREFIX;
			} )
			->toArray();

		array_walk( $itemsToProcess, function( $language, $key ) {
			$keyParts = explode( '-', $key );
			if ( count( $keyParts ) < 3 ) {
				return;
			}

			list( , $optionName, $emailTextKey ) = $keyParts;
			$domain                              = $this->getStringDomain( $optionName );
			$name                                = $this->getStringName( $optionName, $emailTextKey );
			$stringValue                         = wp_kses_post( Obj::propOr(
				'',
				$this->getInputName( $optionName, $emailTextKey ),
				$_POST
			) );
			if ( empty( $stringValue ) ) {
				return;
			}
			$this->replaceStringAndLanguage( $stringValue, $domain, $name, $language );
		} );
	}

	private function getEmailTextKeys() {
		return apply_filters( 'wcml_emails_text_keys_to_translate', self::EMAIL_TEXT_KEYS );
	}

	private function getEmailSettings( $optionName ) {
		$emailSettings = get_option( $optionName, [] );
		if ( ! is_array( $emailSettings ) ) {
			return [];
		}

		if ( ! isset( $emailSettings['additional_content'] ) ) {
			$emailSettings['additional_content'] = '';
		}

		$emailTextKeys    = $this->getEmailTextKeys();
		$relevantSettings = wpml_collect( $emailSettings )
			->filter( function( $settingValue, $setttingKey ) use ( $emailTextKeys ) {
				return in_array( $setttingKey, $emailTextKeys );
			} )
			->toArray();

		return $relevantSettings;
	}

	protected function getStringDomain( $optionName ) {
		return 'admin_texts_' . $optionName;
	}

	protected function getStringName( $optionName, $emailTextKey ) {
		return '[' . $optionName . ']' . $emailTextKey;
	}

	protected function getInputId( $optionName, $emailTextKey ) {
		return str_replace( '_settings', '', $optionName ) . '_' . $emailTextKey;
	}

	protected function getLanguageSelectorId( $optionName, $settingKey ) {
		return $optionName . '_' . $settingKey . '_' . self::LANGUAGE_SELECTOR_ID_SUFFIX;
	}

	protected function getLanguageSelectorName( $optionName, $settingKey ) {
		return self::KEY_PREFIX . '-' . $optionName . '-' . $settingKey;
	}

}
