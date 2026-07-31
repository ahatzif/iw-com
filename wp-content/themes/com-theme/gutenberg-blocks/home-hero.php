<?php
/**
 * Title: Home Hero
 * Description: Homepage introduction with a museum image carousel.
 *
 * @see wp-content/themes/com-theme/acf-json/home-hero_group_6a63618c43922.json ACF Fields
 */

$eyebrow = get_field( 'eyebrow' ) ?: __( 'ΤΑ ΜΟΥΣΕΙΑ ΜΑΣ', 'com-theme' );
$title = get_field( 'title' ) ?: __( "Επισκεφθείτε\nτα μουσεία μας", 'com-theme' );
$title = preg_replace( '/<br\s*\/?>\s*/i', "\n", (string) $title );
$title = preg_replace( "/\n+/", "\n", (string) $title );
$text = get_field( 'text' );
$museums = get_field( 'museums' );
$background_id = com_theme_attachment_id( get_field( 'background_image' ) );
$section_title_id = wp_unique_id( 'home-hero-title-' );

if ( empty( $museums ) ) {
	$museums = get_posts( [
		'post_type'      => 'museum',
		'post_status'    => 'publish',
		'posts_per_page' => 8,
		'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
	] );
}

$slides = [];

foreach ( (array) $museums as $museum ) {
	$museum_id = $museum instanceof WP_Post ? $museum->ID : (int) $museum;

	if ( ! $museum_id ) {
		continue;
	}

	$image_id = com_theme_museum_image_id( $museum_id, 'hero_image' );

	if ( ! $image_id ) {
		continue;
	}

	$slides[] = [
		'id'      => $image_id,
		'title'   => get_the_title( $museum_id ),
		'url'     => get_permalink( $museum_id ),
	];
}

$primary_link = [
	'url'    => com_theme_option_page_url( 'buy_tickets_page', 'buy-tickets' ),
	'title'  => __( 'Αγορά εισιτηρίων →', 'com-theme' ),
	'target' => '',
];
$secondary_link = get_field( 'secondary_link' );

if ( empty( $secondary_link['url'] ) ) {
	$secondary_link = [
		'url'    => '#museums',
		'title'  => __( 'Ανακαλύψτε τα μουσεία ↓', 'com-theme' ),
		'target' => '',
	];
}

$secondary_selector = str_starts_with( (string) $secondary_link['url'], '#' )
	? (string) $secondary_link['url']
	: '';
$block_classes = com_theme_block_style_classes( 'bg-blue text-ochre', [] );
$carousel_options = wp_json_encode( [
	'loop'               => true,
	'align'              => 'center',
	'dragFree'           => false,
	'fade'               => true,
	'autoplay'           => true,
	'autoplayStartDelay' => 3000,
	'autoplayDelay'      => 5000,
] );
?>
<section class="<?php echo esc_attr( $block_classes ); ?> relative overflow-hidden lg:h-[82rem]" aria-labelledby="<?php echo esc_attr( $section_title_id ); ?>">
	<div class="absolute inset-0 mix-blend-overlay opacity-[.12]" aria-hidden="true">
		<?php if ( $background_id ) : ?>
			<?php get_template_part( 'templates/parts/image', null, [
				'id'       => $background_id,
				'size'     => 'full',
				'classes'  => 'absolute -left-[10.59%] -top-[10.6%] h-[134.05%] w-[110.59%] max-w-none object-cover',
				'alt'      => '',
				'lazy'     => false,
				'parallax' => false,
			] ); ?>
		<?php else : ?>
			<img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/com/museums-hero-background.png' ) ); ?>" alt="" class="absolute -left-[10.59%] -top-[10.6%] h-[134.05%] w-[110.59%] max-w-none object-cover">
		<?php endif; ?>
	</div>

	<div class="<?php echo esc_attr( com_theme_block_wrapper_classes() ); ?> min-h-[82rem] lg:h-full lg:min-h-0">
		<div class="relative z-1 flex min-h-[82rem] w-full flex-col gap-50 pb-60 pt-[16rem] lg:block lg:h-full lg:min-h-0 lg:py-0">
			<div class="flex w-full flex-col gap-60 lg:absolute lg:left-0 lg:top-[16rem] lg:w-[54.2rem]">
				<div class="flex flex-col gap-20">
					<?php if ( $eyebrow ) : ?>
						<p class="text-[1rem] font-medium leading-none tracking-[.18em] text-current"><?php echo esc_html( com\theme::remove_accents( $eyebrow ) ); ?></p>
					<?php endif; ?>
					<h1 id="<?php echo esc_attr( $section_title_id ); ?>" class="text-[4.6rem] font-medium leading-[1.05] sm:text-[5.2rem] lg:text-[6rem] lg:leading-[7rem]"><?php echo nl2br( esc_html( $title ) ); ?></h1>
					<?php if ( $text ) : ?>
						<div class="max-w-[54.2rem] text-[1.8rem] font-normal leading-[1.2] lg:text-[2rem]"><?php echo wp_kses_post( wpautop( $text ) ); ?></div>
					<?php endif; ?>
				</div>

				<div class="flex flex-wrap items-center gap-30 sm:gap-45">
					<?php
					get_template_part( 'templates/parts/com-button', null, [
						'href'    => $primary_link['url'],
						'label'   => $primary_link['title'],
						'variant' => 'light',
						'target'  => $primary_link['target'] ?? '',
					] );
					?>
					<a href="<?php echo esc_url( $secondary_link['url'] ); ?>" class="text-[1.6rem] text-blue-soft transition-colors hover:text-current" <?php echo $secondary_selector ? 'data-module-scroll-to-anchor data-selector="' . esc_attr( $secondary_selector ) . '"' : ''; ?> <?php echo ( $secondary_link['target'] ?? '' ) === '_blank' ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
						<?php echo esc_html( $secondary_link['title'] ); ?>
					</a>
				</div>
			</div>

			<?php if ( $slides ) : ?>
				<div class="relative mt-auto w-full select-none lg:absolute lg:right-0 lg:top-[16rem] lg:mt-0 lg:w-[59.9rem]" data-module-embla-carousel="hero" data-options="<?php echo esc_attr( $carousel_options ); ?>">
					<div class="aspect-[calc(599/540)] w-full cursor-grab overflow-hidden rounded-[2rem] active:cursor-grabbing" data-embla-carousel="swiper">
						<div class="wrapper flex h-full touch-pan-y">
							<?php foreach ( $slides as $index => $slide ) : ?>
								<figure class="h-full min-w-0 flex-[0_0_100%]" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( sprintf( __( '%1$d από %2$d', 'com-theme' ), $index + 1, count( $slides ) ) ); ?>">
									<a href="<?php echo esc_url( $slide['url'] ); ?>" class="block size-full">
										<?php get_template_part( 'templates/parts/image', null, [
											'id'       => $slide['id'],
											'size'     => 'large',
											'classes'  => 'pointer-events-none size-full object-cover',
											'alt'      => $slide['title'],
											'lazy'     => 0 !== $index,
											'parallax' => false,
										] ); ?>
									</a>
								</figure>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="mt-20 flex items-center justify-between px-30">
						<div class="flex h-[.3rem] w-[12rem] gap-5" aria-label="<?php esc_attr_e( 'Επιλογή διαφάνειας', 'com-theme' ); ?>">
							<?php foreach ( $slides as $index => $slide ) : ?>
								<button type="button" data-embla-carousel="dot" class="relative h-[.2rem] flex-1 bg-blue-soft transition-colors after:absolute after:-inset-x-[.2rem] after:-inset-y-[2.1rem] after:content-[''] [&.active]:bg-white" aria-label="<?php echo esc_attr( sprintf( __( 'Μετάβαση στη διαφάνεια %d', 'com-theme' ), $index + 1 ) ); ?>" aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>"></button>
							<?php endforeach; ?>
						</div>
						<div class="relative hidden h-[1.2rem] flex-1 lg:block">
							<?php foreach ( $slides as $index => $slide ) : ?>
								<p data-embla-carousel="caption" class="absolute inset-0 text-right text-[1rem] font-medium leading-none tracking-[.18em] text-current opacity-0 transition-opacity [&.active]:opacity-100" aria-hidden="<?php echo $index === 0 ? 'false' : 'true'; ?>"><?php echo esc_html( $slide['title'] ); ?></p>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
