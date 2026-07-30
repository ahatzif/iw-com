<?php

namespace WCML\Synchronization\Component;

class Attachments extends Synchronizer {

	public function run( $product, $translationsIds, $translationsLanguages ) {
		foreach ( $translationsLanguages as $translationId => $language ) {
			$this->woocommerceWpml->media->sync_thumbnail_id( $product->ID, $translationId, $language );
			$this->woocommerceWpml->media->sync_product_gallery( $product->ID, $translationId, $language );
		}
	}

}
