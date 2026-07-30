<?php

namespace WCML\AdminNotices;

use woocommerce_wpml;
use WPML\FP\Obj;
use WPML_Notices;

class MultiCurrencyMissing implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	const NOTICE_ID = 'wcml-multi-currency-missing';

	private $wcml;

	private $notices;

	public function __construct( woocommerce_wpml $wcml, WPML_Notices $notices ) {
		$this->wcml    = $wcml;
		$this->notices = $notices;
	}

	public function add_hooks() {
		if ( wcml_is_multi_currency_on() ) {
			add_action( 'admin_init', [ $this, 'initNotice' ] );
		}
	}

	public function initNotice() {
		$notice      = $this->notices->get_notice( self::NOTICE_ID );
		$needsNotice = $this->hasOneUniqueCurrency();

		if ( $needsNotice && ! $notice ) {
			$this->addNotice();
		} elseif ( ! $needsNotice && $notice ) {
			$this->removeNotice();
		}
	}

	private function addNotice() {
		$text  = '<h2>' . esc_html__( "You haven't added any secondary currencies", 'woocommerce-multilingual' ) . '</h2>';
		$text .= '<p>' . esc_html__( "Please add another currency to fully utilize multicurrency mode. If you do not need multiple currencies, you can disable this setting to improve your site's performance.", 'woocommerce-multilingual' ) . '</p>';
		$text .= sprintf('<a href="%s">%s</a>',
			\WCML\Utilities\AdminUrl::getMultiCurrencyTab(),
			esc_html__( 'Configure multicurrency mode', 'woocommerce-multilingual' )
		);

		$notice = $this->notices->create_notice( self::NOTICE_ID, $text );
		$notice->set_css_class_types( 'notice-warning' );
		$notice->set_restrict_to_screen_ids( RestrictedScreens::get() );
		$notice->set_dismissible( true );

		$this->notices->add_notice( $notice );
	}

	private function removeNotice() {
		$notice = $this->notices->get_notice( self::NOTICE_ID );
		$this->notices->remove_notice( $notice->get_group(), $notice->get_id() );
	}

	private function hasOneUniqueCurrency() {
		return count( (array) Obj::path( [ 'settings', 'currency_options' ], $this->wcml ) ) <= 1;
	}
}
