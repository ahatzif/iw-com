<?php

namespace WCML\Compatibility\WcBookings;

use WPML\FP\Fns;
use WPML\FP\Maybe;
use WPML\FP\Obj;

use function WPML\FP\partial;
use function WPML\FP\partialRight;

class Emails implements \IWPML_Action {

	const DOMAIN = 'woocommerce-bookings';

	const PRIORITY_BEFORE_EMAIL_TRIGGER = 9;

	private $sitepress;

	private $woocommerce_wpml;

	private $woocommerce;

	private $classes;

	private $initialStates = [];

	public function __construct( \SitePress $sitepress, \woocommerce_wpml $woocommerce_wpml, \WooCommerce $woocommerce ) {
		$this->sitepress        = $sitepress;
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->woocommerce      = $woocommerce;
	}

	public function add_hooks() {
		$this->init();

		add_filter( 'wcml_emails_options_to_translate', [ $this, 'optionsToTranslate' ] );
		add_filter( 'wcml_emails_text_keys_to_translate', [ $this, 'keysToTranslate' ] );
		add_filter( 'woocommerce_email_get_option', [ $this, 'translateHeadingAndSubject' ], 20, 4 );

		wpml_collect( [
			'woocommerce_admin_new_booking_notification'              => [ \WC_Email_New_Booking::class ],
			'woocommerce_booking_pending-confirmation'                => [ \WC_Email_Booking_Pending_Confirmation::class ],
			'woocommerce_booking_confirmed_notification'              => [ \WC_Email_Booking_Confirmed::class ],
			'wc-booking-reminder'                                     => [ \WC_Email_Booking_Reminder::class ],
			'woocommerce_booking_pending-confirmation_to_cancelled_notification' => [
				\WC_Email_Booking_Cancelled::class,
				\WC_Email_Admin_Booking_Cancelled::class,
			],
			'woocommerce_booking_confirmed_to_cancelled_notification' => [
				\WC_Email_Booking_Cancelled::class,
				\WC_Email_Admin_Booking_Cancelled::class,
			],
			'woocommerce_booking_paid_to_cancelled_notification'      => [
				\WC_Email_Booking_Cancelled::class,
				\WC_Email_Admin_Booking_Cancelled::class,
			],
			'woocommerce_booking_unpaid_to_cancelled_notification'    => [
				\WC_Email_Booking_Cancelled::class,
				\WC_Email_Admin_Booking_Cancelled::class,
			],
		] )->each( function( $classes, $hook ) {
			add_action( $hook, $this->handle( $classes ), self::PRIORITY_BEFORE_EMAIL_TRIGGER );
		} );
	}

	public function init() {
		$this->classes = wpml_collect( [
			\WC_Email_New_Booking::class             => true,
			\WC_Email_Booking_Confirmed::class       => false,
			\WC_Email_Booking_Reminder::class        => false,
			\WC_Email_Booking_Cancelled::class       => false,
			\WC_Email_Admin_Booking_Cancelled::class => true,
		] );
	}

	public function optionsToTranslate( $options ) {
		$options[] = 'woocommerce_new_booking_settings';
		$options[] = 'woocommerce_booking_reminder_settings';
		$options[] = 'woocommerce_booking_confirmed_settings';
		$options[] = 'woocommerce_booking_cancelled_settings';
		$options[] = 'woocommerce_admin_booking_cancelled_settings';

		return $options;
	}

	public function keysToTranslate( $keys ) {
		$keys[] = 'subject_confirmation';
		$keys[] = 'heading_confirmation';

		return $keys;
	}

	public function translateHeadingAndSubject( $value, $object, $oldValue, $key ) {
		$class = get_class( $object );
		$keys  = [
			'subject',
			'subject_confirmation',
			'heading',
			'heading_confirmation',
		];

		$translatedValue = false;

		if ( in_array( $key, $keys, true ) && $this->classes->has( $class ) ) {
			$isAdmin         = $this->classes->get( $class );
			$translatedValue = $this->woocommerce_wpml->emails->get_email_translated_string( $key, $object, $isAdmin, $value, self::DOMAIN );
		}

		return $translatedValue ?: $value;
	}

	public function handle( $classes ) {
		return function( $bookingId ) use ( $classes ) {
			wpml_collect( $classes )
				->map( partial( [ $this, 'sendWithoutDuplicates' ], $bookingId ) );
		};
	}

	public function sendWithoutDuplicates( $bookingId, $class ) {
		if ( $this->classes->get( $class ) ) {
			Maybe::fromNullable( $this->getAdminUserLanguage( $class ) )
				->map( [ $this->woocommerce_wpml->emails, 'change_email_language' ] );
		} else {
			$this->woocommerce_wpml->emails->refresh_email_lang( $bookingId );
		}

		$emailObject = $this->getEmailObject( $class );
		if ( $emailObject ) {
			if ( ! array_key_exists( $class, $this->initialStates ) ) {
				$this->initialStates[ $class ] = $emailObject->enabled;
			} else {
				$emailObject->enabled = $this->initialStates[ $class ];
			}
			if ( method_exists( $emailObject, 'trigger' ) ) {
				$emailObject->trigger( $bookingId );
			}
			$emailObject->enabled = 'no';
		}
	}

	private function getAdminUserLanguage( $class ) {
		return Maybe::fromNullable( $this->getEmailObject( $class ) )
			->map( Obj::prop( 'recipient' ) )
			->map( partial( 'get_user_by', 'email' ) )
			->map( Obj::prop( 'ID' ) )
			->map( partialRight( [ $this->sitepress, 'get_user_admin_language' ], true ) )
			->getOrElse( null );
	}

	private function getEmailObject( $emailClass ) {
		return Maybe::of( $emailClass )
			->map( Obj::path( [ 'emails', Fns::__ ], $this->woocommerce->mailer() ) )
			->getOrElse( null );
	}

}
