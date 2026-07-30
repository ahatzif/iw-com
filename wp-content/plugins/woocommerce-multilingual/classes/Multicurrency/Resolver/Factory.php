<?php

namespace WCML\MultiCurrency\Resolver;

use WCML\MultiCurrency\Settings;

class Factory {

	public static function create() {
		$getOriginalProductLang = function( $productId ) {
			global $woocommerce_wpml;

			return $woocommerce_wpml->products->get_original_product_language( $productId );
		};

		return new ComposedResolver( [
			new ResolverForContext( $getOriginalProductLang ),
			Settings::isModeByLocation() ? new ResolverForModeLocation() : new ResolverForModeLanguage(),
			new ResolverForDefault(),
		] );
	}
}
