<?php

class WCML_Locale {
	private $sitepress;

	public function __construct( $sitepress ) {
		$this->sitepress = $sitepress;

		add_filter( 'locale', [ $this, 'update_product_action_locale_check' ] );
	}

	public static function load_locale() {
		add_filter( 'lang_dir_for_domain', [ __CLASS__, 'force_path_for_embedded_translation_files' ], 10, 3 );
		return load_plugin_textdomain( 'woocommerce-multilingual', false, WCML_PLUGIN_FOLDER . '/locale' );
	}

	public static function force_path_for_embedded_translation_files( $path, $domain, $locale ) {
		if ( 'woocommerce-multilingual' !== $domain ) {
			return $path;
		}

		$isEmbeddedTranslationFile = function( $locale ) {
			return in_array(
				$locale,
				[
					'de_DE',
					'el',
					'es_ES',
					'fr_FR',
					'he_IL',
					'it_IT',
					'ja',
					'nl_NL',
					'pl_PL',
					'pt_BR',
					'sv_SE',
					'zn_CN',
				],
				true
			);
		};

		if ( $isEmbeddedTranslationFile( $locale ) ) {
			return trailingslashit( WCML_LOCALE_PATH );
		}

		return $path;
	}

	public function switch_locale( $lang_code = false ) {
		global $l10n, $st_gettext_hooks;
		static $original_l10n;

		if ( ! empty( $lang_code ) ) {
			if ( null !== $st_gettext_hooks ) {
				$st_gettext_hooks->switch_language_hook( $lang_code );
			}

			$original_l10n = $l10n['woocommerce-multilingual'] ?? null;
			if ( null !== $original_l10n ) {
				unset( $l10n['woocommerce-multilingual'] );
			}

			return load_textdomain(
				'woocommerce-multilingual',
				WCML_LOCALE_PATH . '/woocommerce-multilingual-' . $this->sitepress->get_locale( $lang_code ) . '.mo'
			);

		} else {
			$l10n['woocommerce-multilingual'] = $original_l10n;
		}
	}

	public function update_product_action_locale_check( $locale ) {
		if ( isset( $_POST['action'] ) && 'wpml_translation_dialog_save_job' === $_POST['action'] ) {
			return $this->sitepress->get_locale( $_POST['job_details']['target'] );
		}
		return $locale;
	}
}
