<?php

namespace WCML\Synchronization\Component;

use WCML_Downloadable_Products;

class DownloadableFiles extends SynchronizerForMeta {

	public function run( $product, $translationsIds, $translationsLanguages ) {
		$settingsFactory      = wpml_load_core_tm()->settings_factory();
		$postmetaFieldSetting = $settingsFactory->post_meta_setting( '_downloadable_files' );
		$postmetaFieldStatus  = $postmetaFieldSetting->status();
		if ( WPML_IGNORE_CUSTOM_FIELD === $postmetaFieldStatus ) {
			return;
		}

		$this->syncFiles( $product->ID, $translationsIds );
	}

	public function syncFiles( $productId, $translationsIds ) {
		$generalProductSync = $this->woocommerceWpml->settings[ WCML_Downloadable_Products::SYNC_MODE_SETTING_KEY ];
		$customProductSync  = get_post_meta( $productId, WCML_Downloadable_Products::SYNC_MODE_META, true );

		if ( $this->isSyncOn( $generalProductSync, $customProductSync ) ) {
			$this->synchronizeMeta( $productId, $translationsIds, WCML_Downloadable_Products::DOWNLOADABLE_FILES_META );
		}
	}

	private function isSyncOn( $generalProductSync, $customProductSync ): bool {
		if ( WCML_Downloadable_Products::SYNC_MODE_META_AUTO === $customProductSync ) {
			return true;
		}

		if ( WCML_Downloadable_Products::SYNC_MODE_META_SELF === $customProductSync ) {
			return false;
		}

		if ( WCML_Downloadable_Products::SYNC_MODE_SETTING_AUTO === $generalProductSync ) {
			return true;
		}

		return false;
	}
}
