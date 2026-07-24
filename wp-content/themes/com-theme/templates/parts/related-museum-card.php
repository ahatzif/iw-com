<?php
$museum = wp_parse_args( $args['museum'] ?? [], [
	'id'       => 0,
	'place'    => '',
	'title'    => '',
	'url'      => '',
	'image_id' => 0,
] );
?>
<article class="mr-20 flex w-9/12 min-w-0 shrink-0 flex-col gap-40 text-blue md:mr-0 md:h-full md:w-auto">
	<div class="flex flex-col gap-20 md:flex-1">
		<div class="flex flex-col gap-10">
			<?php if ( $museum['place'] ) : ?>
				<p class="text-[1.4rem] font-normal leading-[normal]"><?php echo esc_html( $museum['place'] ); ?></p>
			<?php endif; ?>
			<h3 class="text-[2.8rem] font-bold leading-[normal]">
				<a href="<?php echo esc_url( $museum['url'] ); ?>"><?php echo esc_html( $museum['title'] ); ?></a>
			</h3>
		</div>
		<a href="<?php echo esc_url( $museum['url'] ); ?>" class="text-[1.6rem] font-normal leading-[normal]"><?php esc_html_e( 'Περισσότερα →', 'com-theme' ); ?></a>
	</div>
	<a href="<?php echo esc_url( $museum['url'] ); ?>" class="aspect-[calc(310/220)] w-full overflow-hidden rounded-[1rem]" aria-label="<?php echo esc_attr( $museum['title'] ); ?>">
		<?php if ( $museum['image_id'] ) : ?>
			<?php echo wp_get_attachment_image( $museum['image_id'], 'large', false, [ 'class' => 'size-full object-cover transition-transform duration-500 hover:scale-105', 'alt' => $museum['title'] ] ); ?>
		<?php endif; ?>
	</a>
</article>
