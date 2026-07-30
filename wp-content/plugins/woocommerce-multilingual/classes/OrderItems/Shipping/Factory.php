<?php

namespace WCML\OrderItems\Shipping;

use function WPML\Container\make;
use WCML\OrderItems\Translator;
use WCML\OrderItems\TranslatorFactory;

class Factory implements TranslatorFactory {

	const ORDER_ITEM_TYPE = 'shipping';

	public function getTranslator( $item ) {
		if ( ! $item instanceof \WC_Order_Item_Shipping ) {
			return null;
		}

		if ( self::ORDER_ITEM_TYPE !== $item->get_type() ) {
			return null;
		}

		$shippingId = $item->get_method_id();
		if ( ! $shippingId ) {
			return null;
		}

		$orderItemShippingTranslators = [];
		$orderItemShippingTranslators = apply_filters( 'wcml_order_item_shipping_method_translators', $orderItemShippingTranslators );

		if ( array_key_exists( $shippingId, $orderItemShippingTranslators ) ) {
			return make( $orderItemShippingTranslators[ $shippingId ] );
		}

		return make( ShippingMethod::class );
	}
}
