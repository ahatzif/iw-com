<?php

namespace WCML\OrderItems\Shipping;

trait StoreInDefaultLanguage {

	public function maybeSaveItem( $item, $titleInDefaultLanguage, $targetLanguage ) {
		if ( ! $item instanceof \WC_Order_Item_Shipping ) {
			return;
		}

		$forceSaveInDefaultLanguage = true;
		$forceSaveInDefaultLanguage = apply_filters( 'wcml_should_save_adjusted_order_item_in_language', $forceSaveInDefaultLanguage, $item, $targetLanguage );

		if ( $forceSaveInDefaultLanguage ) {
			$originalMethodTitle = $item->get_method_title();
			$item->set_method_title( $titleInDefaultLanguage );
			$item->save();
			$item->set_method_title( $originalMethodTitle );
		}
	}

}
