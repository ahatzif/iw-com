<?php

class WCML_WC_Product_Bundles_Items {

	public function get_items( $product_id ) {
		$product_bundle = new WC_Product_Bundle( $product_id );

		return $product_bundle->get_bundled_items();
	}

	public function get_item_data( $bundled_item ) {
		$item_data = $bundled_item->get_data();
		if ( $item_data['max_stock'] === null ) {
			$item_data['max_stock'] = '';
		}
		return $item_data;
	}

	public function copy_item_data( $item_id_1, $item_id_2 ) {

		$item_1_data = $this->get_item_data_object( $item_id_1 );
		$item_2_data = $this->get_item_data_object( $item_id_2 );

		$meta_data = $item_1_data->get_meta_data();

		foreach ( $meta_data as $key => $value ) {
			$item_2_data->update_meta( $key, $value );
		}

		$item_2_data->save();
	}

	public function get_item_data_object( $item_id ) {
		return new WC_Bundled_Item_Data( $item_id );
	}

	public function update_item_meta( $bundled_item_data, $key, $value ) {
		$bundled_item_data->update_meta( $key, $value );
	}

	public function save_item_meta( $bundled_item_data ) {
		$bundled_item_data->save();
	}


}


