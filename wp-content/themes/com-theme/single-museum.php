<?php
get_header( null, [
	'header_theme'    => 'dark',
	'active_nav'      => 'museums',
	'page_background' => 'ochre',
	'barba_namespace' => 'museum',
] );

while ( have_posts() ) :
	the_post();

	$museum_id = get_the_ID();
	$is_all_museums_ticket = com_theme_is_all_museums_ticket( $museum_id );
	$all_museums_slider_items = $is_all_museums_ticket ? com_theme_all_museums_slider_items( $museum_id ) : [];
	$title_id = wp_unique_id( 'museum-title-' );
	$hero_image_id = com_theme_museum_image_id( $museum_id, 'hero_image' );
	$hero_background_id = com_theme_attachment_id( get_field( 'hero_background', $museum_id ) );
	$gallery_image_id = com_theme_attachment_id( get_field( 'gallery_image', $museum_id ) ) ?: $hero_image_id;
	$ticket_link = get_field( 'ticket_link', $museum_id );
	$ticket_url = com_theme_museum_ticket_url( $museum_id, $ticket_link );
	$ticket_label = ! empty( $ticket_link['title'] ) ? $ticket_link['title'] : __( 'Εισιτήρια', 'com-theme' );
	$ticket_target = $ticket_link['target'] ?? '';
	$address = (string) get_field( 'address', $museum_id );
	$map_url = (string) get_field( 'map_url', $museum_id );
	$opening_hours = (string) get_field( 'opening_hours', $museum_id );
	$phone = (string) get_field( 'phone', $museum_id );
	$email = (string) get_field( 'email', $museum_id );
	$price = (string) get_field( 'ticket_price_text', $museum_id );
	$features = get_field( 'features', $museum_id );
	$related_museums = get_field( 'related_museums', $museum_id );
	$content = trim( (string) get_post_field( 'post_content', $museum_id ) );

	if ( empty( $related_museums ) ) {
		$related_museums = get_posts( [
			'post_type'      => 'museum',
			'post_status'    => 'publish',
			'posts_per_page' => 3,
			'post__not_in'   => [ $museum_id ],
			'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
		] );
	}
	?>
	<main>
		<section class="relative grid overflow-hidden lg:h-[110rem]" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
			<div class="col-start-1 row-start-1 mt-[30rem] h-[54rem] overflow-hidden mix-blend-multiply opacity-40 lg:h-[74rem]" aria-hidden="true">
				<?php if ( $is_all_museums_ticket && $all_museums_slider_items ) : ?>
					<?php get_template_part( 'templates/parts/all-museums-slider', null, [
						'items'         => $all_museums_slider_items,
						'classes'       => 'size-full overflow-hidden',
						'image_size'    => 'full',
						'image_classes' => 'relative left-0 top-[-8.97%] h-[129.79%] w-full max-w-none object-cover',
					] ); ?>
				<?php elseif ( $hero_background_id ) : ?>
					<?php get_template_part( 'templates/parts/image', null, [
						'id'       => $hero_background_id,
						'size'     => 'full',
						'classes'  => 'relative left-0 top-[-8.97%] h-[129.79%] w-full max-w-none object-cover',
						'alt'      => '',
						'lazy'     => false,
						'parallax' => false,
					] ); ?>
				<?php else : ?>
					<img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/com/museums-hero-background.png' ) ); ?>" alt="" class="relative left-0 top-[-8.97%] h-[129.79%] w-full max-w-none object-cover">
				<?php endif; ?>
			</div>

			<div class="page-wrapper col-start-1 row-start-1 h-full">
				<div class="relative z-1 flex h-full w-full flex-col pb-60 pt-[16rem] lg:block lg:pb-0">
					<div class="flex w-full flex-col gap-40 lg:w-[60rem]">
						<div class="flex flex-col gap-20 font-medium">
							<a href="<?php echo esc_url( home_url( '/#museums' ) ); ?>" class="text-[1rem] leading-none tracking-[.18em]"><?php esc_html_e( 'ΤΑ ΜΟΥΣΕΙΑ ΜΑΣ', 'com-theme' ); ?></a>
							<h1 id="<?php echo esc_attr( $title_id ); ?>" class="text-[4.4rem] leading-[1.1] sm:text-[5.2rem] lg:text-[6rem] lg:leading-[7rem]"><?php the_title(); ?></h1>
						</div>
						<div class="flex flex-wrap items-center gap-40">
							<?php
							get_template_part( 'templates/parts/com-button', null, [
								'href'    => $ticket_url,
								'label'   => $ticket_label,
								'variant' => 'blue-outline',
								'size'    => 'small',
								'icon'    => 'com-ticket-button',
								'target'  => $ticket_target,
							] );
							?>
							<a href="#museum-details" class="text-[1.6rem] leading-[normal]" data-module-scroll-to-anchor data-selector="#museum-details"><?php esc_html_e( 'Περισσότερα ↓', 'com-theme' ); ?></a>
						</div>
					</div>

					<?php if ( $is_all_museums_ticket && $all_museums_slider_items ) : ?>
						<div class="mt-60 aspect-[calc(599/540)] w-full overflow-hidden rounded-[2rem] lg:absolute lg:bottom-0 lg:right-0 lg:mt-0 lg:w-[59.9rem]">
							<?php get_template_part( 'templates/parts/all-museums-slider', null, [
								'items'           => $all_museums_slider_items,
								'classes'         => 'relative size-full overflow-hidden bg-blue',
								'image_size'      => 'full',
								'image_classes'   => 'size-full object-cover',
								'overlay_classes' => 'absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-blue/35 to-transparent',
								'show_caption'    => true,
								'caption_classes' => 'absolute bottom-25 left-25 right-25 z-1 text-[1.1rem] font-medium leading-none tracking-[.18em] text-white',
							] ); ?>
						</div>
					<?php elseif ( $hero_image_id ) : ?>
						<div class="mt-60 aspect-[calc(599/540)] w-full overflow-hidden rounded-[2rem] lg:absolute lg:bottom-0 lg:right-0 lg:mt-0 lg:w-[59.9rem]">
							<?php get_template_part( 'templates/parts/image', null, [
								'id'       => $hero_image_id,
								'size'     => 'full',
								'classes'  => 'size-full object-cover',
								'alt'      => get_the_title(),
								'lazy'     => false,
								'parallax' => false,
							] ); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>

		<section id="museum-details" class="pb-60 lg:pb-50" aria-label="<?php esc_attr_e( 'Πληροφορίες μουσείου', 'com-theme' ); ?>">
			<div class="page-wrapper">
				<dl class="flex w-full flex-col gap-10 text-[1.6rem] leading-[normal] lg:ml-[12rem] lg:w-[41.9rem]">
					<?php if ( $address ) : ?>
						<div>
							<dt class="inline font-bold"><?php esc_html_e( 'Τοποθεσία:', 'com-theme' ); ?></dt>
							<dd class="ml-[.25em] inline">
								<?php echo esc_html( $address ); ?>
								<?php if ( $map_url ) : ?> / <a href="<?php echo esc_url( $map_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Δείτε στο χάρτη', 'com-theme' ); ?></a><?php endif; ?>
							</dd>
						</div>
					<?php endif; ?>
					<?php if ( $opening_hours ) : ?>
						<div><dt class="inline font-bold"><?php esc_html_e( 'Ωράριο:', 'com-theme' ); ?></dt><dd class="ml-[.25em] inline"><?php echo nl2br( esc_html( $opening_hours ) ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $phone ) : ?>
						<div><dt class="inline font-bold"><?php esc_html_e( 'Τηλέφωνο:', 'com-theme' ); ?></dt><dd class="ml-[.25em] inline"><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></dd></div>
					<?php endif; ?>
					<?php if ( $email ) : ?>
						<div><dt class="inline font-bold"><?php esc_html_e( 'Email:', 'com-theme' ); ?></dt><dd class="ml-[.25em] inline"><a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a></dd></div>
					<?php endif; ?>
					<?php if ( $price ) : ?>
						<div><dt class="inline font-bold"><?php esc_html_e( 'Τιμή εισιτηρίου:', 'com-theme' ); ?></dt><dd class="ml-[.25em] inline"><?php echo esc_html( $price ); ?></dd></div>
					<?php endif; ?>
				</dl>
			</div>
		</section>

		<section id="museum-content" class="px-1/12 md:px-4/24 lg:px-0" aria-label="<?php esc_attr_e( 'Περιγραφή μουσείου', 'com-theme' ); ?>">
			<div class="mx-auto w-full space-y-20 text-[1.8rem] font-normal leading-[1.2] lg:w-[96rem] lg:text-[2rem]">
				<?php if ( $content ) : ?>
					<?php echo apply_filters( 'the_content', $content ); ?>
				<?php elseif ( has_excerpt() ) : ?>
					<?php echo wp_kses_post( wpautop( get_the_excerpt() ) ); ?>
				<?php endif; ?>
			</div>
		</section>

		<?php if ( $features ) : ?>
			<section class="px-1/12 pt-40 md:px-4/24 lg:px-0" aria-labelledby="museum-features-title">
				<div class="mx-auto flex w-full flex-col gap-40 lg:w-[96rem]">
					<p id="museum-features-title" class="text-[1rem] font-medium leading-none tracking-[.18em] text-blue/40"><?php esc_html_e( 'ΤΙ ΘΑ ΔΕΙΤΕ', 'com-theme' ); ?></p>
					<ol class="flex flex-col gap-20">
						<?php foreach ( $features as $index => $feature ) : ?>
							<li class="flex items-start gap-[3.1rem] border-t border-blue pt-30">
								<span class="w-[1.5rem] shrink-0 text-[1.1rem] font-light leading-[normal] tracking-[.02em]"><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
								<div class="flex w-[35.4rem] max-w-full flex-col gap-[.7rem]">
									<h2 class="text-[2rem] font-normal leading-[1.2]"><?php echo esc_html( $feature['title'] ?? '' ); ?></h2>
									<?php if ( ! empty( $feature['text'] ) ) : ?><p class="text-[1.2rem] leading-[normal]"><?php echo esc_html( $feature['text'] ); ?></p><?php endif; ?>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $ticket_url ) : ?>
			<section class="px-1/12 pt-75 md:px-4/24 lg:px-0" aria-label="<?php esc_attr_e( 'Εισιτήρια μουσείου', 'com-theme' ); ?>">
				<div class="mx-auto flex w-full justify-end lg:w-[96rem]">
					<?php
					get_template_part( 'templates/parts/com-button', null, [
						'href'    => $ticket_url,
						'label'   => $ticket_label,
						'variant' => 'blue-outline',
						'size'    => 'small',
						'icon'    => 'com-ticket-button',
						'target'  => $ticket_target,
					] );
					?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $is_all_museums_ticket && $all_museums_slider_items ) : ?>
			<section class="px-1/12 pb-100 pt-100 md:px-4/24 lg:px-0" aria-label="<?php esc_attr_e( 'Φωτογραφίες μουσείων', 'com-theme' ); ?>">
				<div class="mx-auto aspect-[calc(960/540)] w-full overflow-hidden rounded-[1rem] lg:w-[96rem]">
					<?php get_template_part( 'templates/parts/all-museums-slider', null, [
						'items'           => $all_museums_slider_items,
						'classes'         => 'relative size-full overflow-hidden bg-blue',
						'image_size'      => 'full',
						'image_classes'   => 'size-full object-cover',
						'overlay_classes' => 'absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-blue/35 to-transparent',
						'show_caption'    => true,
						'caption_classes' => 'absolute bottom-25 left-25 right-25 z-1 text-[1.1rem] font-medium leading-none tracking-[.18em] text-white',
					] ); ?>
				</div>
			</section>
		<?php elseif ( $gallery_image_id ) : ?>
			<section class="px-1/12 pb-100 pt-100 md:px-4/24 lg:px-0" aria-label="<?php esc_attr_e( 'Εικόνα μουσείου', 'com-theme' ); ?>">
				<div class="mx-auto aspect-[calc(960/540)] w-full overflow-hidden rounded-[1rem] lg:w-[96rem]">
					<?php get_template_part( 'templates/parts/image', null, [
						'id'       => $gallery_image_id,
						'size'     => 'full',
						'classes'  => 'size-full object-cover',
						'alt'      => get_the_title(),
						'parallax' => false,
					] ); ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $related_museums ) : ?>
			<section class="px-1/12 pb-90 md:px-4/24 lg:px-0 lg:pb-[16rem]" aria-labelledby="related-title">
				<div class="mx-auto w-full lg:w-[95.7rem]">
					<div class="flex w-full flex-col gap-5 md:w-[60rem]">
						<p class="text-[1rem] font-medium leading-none tracking-[.18em] text-blue/60"><?php echo esc_html( $is_all_museums_ticket ? com\theme::remove_accents( __( 'Περιλαμβάνει', 'com-theme' ) ) : __( 'ΔΕΙΤΕ ΕΠΙΣΗΣ', 'com-theme' ) ); ?></p>
						<h2 id="related-title" class="text-[2rem] font-normal leading-[1.2]"><?php echo esc_html( $is_all_museums_ticket ? __( 'Τα μουσεία που μπορείτε να επισκεφθείτε', 'com-theme' ) : __( 'Άλλα Μουσεία του τόπου μας', 'com-theme' ) ); ?></h2>
					</div>
					<div class="mt-40 min-w-0" data-module-embla-carousel data-options='{"mobileOnly":true,"loop":true,"align":"start","dragFree":true}'>
						<div class="w-full overflow-hidden md:overflow-visible" data-embla-carousel="swiper">
							<div class="wrapper flex md:grid md:grid-cols-3 md:gap-[1.35rem]">
								<?php foreach ( $related_museums as $related_museum ) : ?>
									<?php
									$related_id = $related_museum instanceof WP_Post ? $related_museum->ID : (int) $related_museum;

									if ( ! $related_id || $related_id === $museum_id ) {
										continue;
									}

									get_template_part( 'templates/parts/related-museum-card', null, [
										'museum' => [
											'id'       => $related_id,
											'place'    => get_field( 'place_label', $related_id ) ?: com_theme_museum_location_label( $related_id ),
											'title'    => get_the_title( $related_id ),
											'url'      => get_permalink( $related_id ),
											'image_id' => com_theme_museum_image_id( $related_id ),
										],
									] );
									?>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>
			</section>
		<?php endif; ?>
	</main>
	<?php
endwhile;

get_footer( null, [
	'footer_description_font' => 'font-main',
] );
