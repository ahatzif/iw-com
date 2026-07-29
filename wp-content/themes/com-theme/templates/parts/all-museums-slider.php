<?php
$items = $args['items'] ?? com_theme_all_museums_slider_items();
$items = is_array( $items ) ? array_values( $items ) : [];
$classes = (string) ( $args['classes'] ?? 'relative size-full overflow-hidden bg-blue' );
$viewport_classes = (string) ( $args['viewport_classes'] ?? 'size-full overflow-hidden' );
$wrapper_classes = (string) ( $args['wrapper_classes'] ?? 'wrapper flex size-full' );
$slide_classes = (string) ( $args['slide_classes'] ?? 'relative min-w-0 shrink-0 basis-full' );
$image_size = (string) ( $args['image_size'] ?? 'large' );
$image_classes = (string) ( $args['image_classes'] ?? 'size-full object-cover' );
$overlay_classes = (string) ( $args['overlay_classes'] ?? '' );
$show_caption = ! empty( $args['show_caption'] );
$caption_classes = (string) ( $args['caption_classes'] ?? 'absolute bottom-15 left-15 right-15 z-1 text-[1rem] font-medium leading-none tracking-[.18em] text-white' );
$label = (string) ( $args['label'] ?? __( 'Φωτογραφίες μουσείων', 'com-theme' ) );
$options = wp_parse_args( $args['options'] ?? [], [
	'loop'               => true,
	'align'              => 'start',
	'dragFree'           => false,
	'fade'               => true,
	'autoplay'           => true,
	'autoplayStartDelay' => 800,
	'autoplayDelay'      => 3200,
] );

$items = array_values( array_filter( $items, static function ( $item ): bool {
	return is_array( $item ) && ! empty( $item['image_id'] );
} ) );

if ( empty( $items ) ) {
	get_template_part( 'templates/parts/all-museums-art', null, $args );
	return;
}
?>
<div
	class="<?php echo esc_attr( $classes ); ?>"
	data-module-embla-carousel
	data-options="<?php echo esc_attr( rawurlencode( wp_json_encode( $options ) ) ); ?>"
	aria-label="<?php echo esc_attr( $label ); ?>"
>
	<div class="<?php echo esc_attr( $viewport_classes ); ?>" data-embla-carousel="swiper">
		<div class="<?php echo esc_attr( $wrapper_classes ); ?>">
			<?php foreach ( $items as $item ) : ?>
				<div class="<?php echo esc_attr( $slide_classes ); ?>">
					<?php get_template_part( 'templates/parts/image', null, [
						'id'       => (int) $item['image_id'],
						'size'     => $image_size,
						'classes'  => $image_classes,
						'alt'      => (string) ( $item['title'] ?? '' ),
						'parallax' => false,
					] ); ?>
					<?php if ( $overlay_classes ) : ?><span class="<?php echo esc_attr( $overlay_classes ); ?>" aria-hidden="true"></span><?php endif; ?>
					<?php if ( $show_caption && ! empty( $item['title'] ) ) : ?>
						<span class="<?php echo esc_attr( $caption_classes ); ?>"><?php echo esc_html( com\theme::remove_accents( (string) $item['title'] ) ); ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
