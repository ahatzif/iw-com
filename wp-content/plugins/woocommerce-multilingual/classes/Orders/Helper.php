<?php

namespace WCML\Orders;

use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Maybe;
use WPML\FP\Relation;
use WPML\LIB\WP\Cache;
use function WPML\FP\invoke;
use function WPML\FP\pipe;
use WCML\COT\Helper as COTHelper;
use WCML\Orders\Legacy\Helper as LegacyHelper;

class Helper {

	const CACHE_GROUP         = 'wcml_order_currency';
	const KEY_LEGACY_CURRENCY = '_order_currency';

	public static function getCurrency( $orderId, $useDB = false ) {
		$useDB = $useDB || ! did_action( 'woocommerce_after_register_post_type' );

		if ( $useDB ) {
			return self::getCurrencyFromDB( $orderId );
		} else {
			return self::getCurrencyFromOrderObject( $orderId );
		}
	}

	private static function getCurrencyFromDB( $orderId ) {
		$getCurrency = Cache::memorize( self::CACHE_GROUP, MINUTE_IN_SECONDS, function( $orderId ) {
			global $wpdb;

			if ( \WCML\COT\Helper::isUsageEnabled() ) {
				$orderTable = \WCML\COT\Helper::getTableName();

				$currency = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT currency FROM {$orderTable} WHERE id = %d",
						$orderId
					)
				);
			} else {
				$currency = get_post_meta( $orderId, self::KEY_LEGACY_CURRENCY, true );
			}

			return $currency ?: null;
		} );

		return $getCurrency( $orderId );
	}

	private static function getCurrencyFromOrderObject( $orderId ) {
		$isNotAutoDraft = pipe(
			invoke( 'get_status' ),
			Relation::equals( 'auto-draft' ),
			Logic::not()
		);

		return Maybe::fromNullable( wc_get_order( $orderId ) )
			->filter( $isNotAutoDraft )
			->map( invoke( 'get_currency' ) )
			->getOrElse( null );
	}

	public static function setCurrency( $orderId, $currency ) {
		Maybe::fromNullable( wc_get_order( $orderId ) )
			->map( Fns::tap( invoke( 'set_currency' )->with( $currency ) ) )
			->map( invoke( 'save' ) );
	}

	public static function isOrderCreateAdminScreen(): bool {
		return COTHelper::isOrderCreateAdminScreen() || LegacyHelper::isOrderCreateAdminScreen();
	}

	public static function isOrderListAdminScreen(): bool {
		return COTHelper::isOrderListAdminScreen() || LegacyHelper::isOrderListAdminScreen();
	}

	public static function isOrderEditAdminScreen(): bool {
		return COTHelper::isOrderEditAdminScreen() || LegacyHelper::isOrderEditAdminScreen();
	}

	public static function isEditingNewOrderItems() {
		return (
				isset( $_POST['action'] )
				&& in_array(
					$_POST['action'],
					[
						'woocommerce_add_order_item',
						'woocommerce_remove_order_item',
						'woocommerce_calc_line_taxes',
						'woocommerce_save_order_items',
					],
					true
				)
			)
			||
			(
				isset( $_GET['action'] )
				&& $_GET['action'] === 'woocommerce_json_search_products_and_variations'
			);
	}
}
