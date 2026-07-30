<?php

namespace WCML\Synchronization\Component;

class Meta extends SynchronizerForMeta {

	public function run( $product, $translationsIds, $translationsLanguages ) {
		$this->deleteOrphanedFields( $product->ID, $translationsIds );

		foreach ( $translationsIds as $translationId ) {
			do_action( 'wcml_after_duplicate_product_post_meta', $product->ID, $translationId, false );
		}
	}

}
