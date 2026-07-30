<?php

namespace WCML\Rest\Wrapper;

use WCML_WC_Strings;
use WPML\FP\Obj;

class ProductAttributes extends Handler {

	private $strings;

	public function __construct(
		WCML_WC_Strings $strings
	) {
		$this->strings = $strings;
	}

	public function prepare( $response, $object, $request ) {
		$langCode = Obj::prop( 'lang', $request->get_params() );

		$response->data['name'] = $this->strings->get_translated_string_by_name_and_context( \WCML_WC_Strings::DOMAIN_WORDPRESS, \WCML_WC_Strings::TAXONOMY_SINGULAR_NAME_PREFIX . $object->attribute_label, $langCode, $object->attribute_label );

		return $response;
	}

}
