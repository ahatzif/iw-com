<?php
/**
 * Title: Featured Experiences
 * Description: Responsive editorial cards with mobile carousel behavior.
 *
 * @see wp-content/themes/com-theme/acf-json/featured-experiences_group_6a6361b4528a3.json ACF Fields
 */

$eyebrow = get_field( 'eyebrow' );
$title = get_field( 'title' );
$items = get_field( 'items' );
$section_title_id = wp_unique_id( 'featured-experiences-title-' );
$carousel_options = wp_json_encode( [
	'mobileOnly' => true,
	'loop'       => true,
	'align'      => 'start',
	'dragFree'   => true,
] );
?>
<section class="<?php echo esc_attr( com_theme_block_style_classes( 'bg-ochre text-blue', [] ) ); ?> py-90 " aria-labelledby="<?php echo esc_attr( $section_title_id ); ?>">
	<div class="<?php echo esc_attr( com_theme_block_wrapper_classes() ); ?> flex h-full flex-col">
		<?php if ( $eyebrow || $title ) : ?>
			<header class="flex flex-col gap-5">
				<?php if ( $eyebrow ) : ?>
					<p class="text-[1rem] font-medium uppercase leading-none tracking-[.18em] opacity-60"><?php echo esc_html( $eyebrow ); ?></p>
				<?php endif; ?>
				<?php if ( $title ) : ?>
					<h2 id="<?php echo esc_attr( $section_title_id ); ?>" class="text-[2rem] font-normal leading-[1.2]"><?php echo wp_kses_post( $title ); ?></h2>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<?php if ( $items ) : ?>
			<div class="mt-40 min-w-0 flex-1" data-module-embla-carousel data-options="<?php echo esc_attr( $carousel_options ); ?>">
				<div class="h-full" data-embla-carousel="swiper">
					<div class="wrapper flex h-full md:grid md:grid-cols-3 md:gap-30">
						<?php foreach ( $items as $item ) : ?>
							<?php
							$image_id = com_theme_attachment_id( $item['image'] ?? 0 );
							$link = $item['link'] ?? [];

							if ( ! $image_id && empty( $item['title'] ) ) {
								continue;
							}
							?>
							<article class="mr-20 flex w-9/12 min-w-0 shrink-0 flex-col md:mr-0 md:w-auto">
								<?php if ( ! empty( $link['url'] ) ) : ?><a href="<?php echo esc_url( $link['url'] ); ?>" class="block" <?php echo ( $link['target'] ?? '' ) === '_blank' ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>><?php endif; ?>
									<?php if ( $image_id ) : ?>
										<span class="block aspect-[calc(380/250)] w-full overflow-hidden rounded-[.5rem]">
											<?php echo wp_get_attachment_image( $image_id, 'large', false, [ 'class' => 'size-full object-cover transition-transform duration-500 hover:scale-105' ] ); ?>
										</span>
									<?php endif; ?>
									<?php if ( ! empty( $item['title'] ) ) : ?>
										<span class="mt-20 block text-[1.6rem] font-normal leading-[1.22]"><?php echo esc_html( $item['title'] ); ?></span>
									<?php endif; ?>
								<?php if ( ! empty( $link['url'] ) ) : ?></a><?php endif; ?>

								<?php if ( ! empty( $link['url'] ) ) : ?>
									<a href="<?php echo esc_url( $link['url'] ); ?>" class="mt-auto pt-30 text-[1.2rem] opacity-70 transition-opacity hover:opacity-100" <?php echo ( $link['target'] ?? '' ) === '_blank' ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
										<?php echo esc_html( $link['title'] ?: __( 'Δες εδώ →', 'com-theme' ) ); ?>
									</a>
								<?php endif; ?>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
