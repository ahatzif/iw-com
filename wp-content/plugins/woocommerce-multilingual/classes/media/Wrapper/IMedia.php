<?php

namespace WCML\Media\Wrapper;

interface IMedia {

	public function add_hooks();

	public function product_images_ids( $product_id );

	public function sync_thumbnail_id( $orig_post_id, $trnsl_post_id, $lang );

	public function sync_variation_thumbnail_id( $variation_id, $translated_variation_id, $lang );

	public function sync_product_gallery( $orig_post_id, $trnsl_post_id, $lang );

	public function sync_product_gallery_to_all_languages( $product_id );

	public function create_base_media_translation( $attachment_id, $parent_id, $target_lang );

	public function sync_product_gallery_duplicate_attachment( $att_id, $dup_att_id );
}
