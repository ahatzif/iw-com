<?php

namespace WCML\Rest\Wrapper\Orders;

use WCML\Rest\Wrapper\Handler;
use WCML\Rest\Exceptions\InvalidLanguage;
use WPML\FP\Obj;

use function WCML\functions\getId;

class Languages extends Handler {

	public function query( $args, $request ) {
		$lang = $request->get_param( 'lang' );

		if ( ! is_null( $lang ) && 'all' !== $lang ) {
			$args['meta_query'][] = [
				'key'   => \WCML_Orders::KEY_LANGUAGE,
				'value' => strval( $lang ),
			];
		}

		return $args;
	}


	public function prepare( $response, $object, $request ) {
		$language = $request->get_param( 'lang' );
		if ( empty( $language ) ) {
			$language = apply_filters( 'wpml_default_language', null );
		}

		$orderLanguage = \WCML_Orders::getLanguage( $this->get_id( $object ) );

		if ( $orderLanguage !== $language ) {
			$lineItems = Obj::propOr( [], 'line_items', $response->data );
			foreach ( $lineItems as $k => $item ) {
				$translatedProductId   = wpml_object_id_filter( $item['product_id'], 'product', false, $language );
				$translatedVariationId = ( empty( $item['variation_id'] ) ) ? 0 : wpml_object_id_filter( $item['variation_id'], 'product_variation', false, $language );

				if ( $translatedProductId ) {
					$response->data['line_items'][ $k ]['product_id'] = $translatedProductId;

					$translatedProduct = get_post( $translatedProductId );
					$postName          = $translatedProduct->post_title;

					if ( $translatedVariationId ) {
						$response->data['line_items'][ $k ]['variation_id'] = $translatedVariationId;

						$translatedVariation = get_post( $translatedVariationId );
						$postName            = $translatedVariation->post_title;
					}

					$response->data['line_items'][ $k ]['name'] = $postName;
				}
			}
		}

		return $response;
	}

	private function get_id( $object ) {
		try {
			return getId( $object );
		} catch ( \Exception $err ) {
			throw new \Exception( 'Order has no ID set.' );
		}
	}


	public function insert( $object, $request, $creating ) {
		$data = $request->get_params();
		if ( isset( $data['lang'] ) ) {

			if ( ! apply_filters( 'wpml_language_is_active', false, $data['lang'] ) ) {
				throw new InvalidLanguage( $data['lang'] );
			}

			\WCML_Orders::setLanguage( $object->get_id(), $data['lang'] );
		}
	}
}
