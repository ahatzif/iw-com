<?php

namespace WCML\Synchronization\Component;

class VariationAttachments extends Synchronizer {

	public function run( $variation, $translationsIds, $translationsLanguages ) {
		foreach ( $translationsLanguages as $translationId => $language ) {
			$this->woocommerceWpml->media->sync_variation_thumbnail_id( $variation->ID, $translationId, $language );
		}
	}

}
