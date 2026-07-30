<?php

namespace WCML\Utilities;

class WCTaxonomies {

	const TAXONOMY_PREFIX_ATTRIBUTE = 'pa_';
	const TAXONOMY_PRODUCT_CATEGORY = 'product_cat';
	const TAXONOMY_PRODUCT_TAG = 'product_tag';

	public static function isProductAttribute( $taxonomy ) : bool {
		return substr( $taxonomy, 0, 3 ) === self::TAXONOMY_PREFIX_ATTRIBUTE;
	}

	public static function isProductCategory( $taxonomy ) : bool {
		return self::TAXONOMY_PRODUCT_CATEGORY === $taxonomy;
	}

	public static function isProductTag( $taxonomy ) : bool {
		return self::TAXONOMY_PRODUCT_TAG === $taxonomy;
	}	

	public static function isProductCategoryOrAttribute( $taxonomy ) : bool {
		return self::isProductAttribute( $taxonomy ) || self::isProductCategory( $taxonomy );
	}
}
