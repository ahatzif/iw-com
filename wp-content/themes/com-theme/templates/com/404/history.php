<?php
$title = (string) ( $args['title'] ?? '' );
$description = (string) ( $args['description'] ?? '' );
$button_text = (string) ( $args['button_text'] ?? '' );
$background_id = absint( $args['background_id'] ?? 0 );
$overlay_opacity = (float) ( $args['overlay_opacity'] ?? 0 );
$image_classes = 'absolute inset-0 size-full object-cover';
?>

<main class="com-404 com-404--history relative isolate min-h-[82rem] overflow-hidden bg-blue pt-[13rem] text-ochre md:min-h-[82.5rem] md:pt-[13.841464rem]">
	<div class="com-404__history-number pointer-events-none absolute -left-[1.5rem] top-[9rem] z-[-1] select-none text-[17rem] font-light leading-none tracking-[-.08em] text-transparent opacity-20 md:-left-[2rem] md:top-[7rem] md:text-[30rem]" aria-hidden="true">404</div>

	<div class="page-wrapper relative flex min-h-[69rem] flex-col py-40 md:min-h-[68.6rem] md:justify-between md:py-60">
		<div class="flex items-center gap-20 text-[1rem] font-medium tracking-[.18em] text-ochre/70">
			<span>1826—2026</span>
			<span class="h-px w-60 bg-ochre/40 md:w-120" aria-hidden="true"></span>
			<span><?php esc_html_e( '200 ΧΡΟΝΙΑ ΑΠΟ ΤΗΝ ΕΞΟΔΟ', 'com-theme' ); ?></span>
		</div>

		<div class="mt-60 grid flex-1 items-center gap-50 md:mt-30 md:grid-cols-24 md:gap-0">
			<div class="relative z-2 md:col-span-10 md:pr-50">
				<p class="mb-15 text-[1rem] font-medium tracking-[.18em] text-blue-soft"><?php esc_html_e( 'ΣΦΑΛΜΑ 404', 'com-theme' ); ?></p>
				<h1 class="max-w-[64rem] text-[4.4rem] font-normal leading-[1.04] tracking-[-.02em] md:text-[6rem] md:leading-[1.06]"><?php echo esc_html( $title ); ?></h1>
				<p class="mt-25 max-w-[50rem] text-[1.6rem] font-normal leading-[1.35] text-ochre/80 md:mt-30 md:text-[2rem] md:leading-[1.2]"><?php echo esc_html( $description ); ?></p>

				<div class="mt-40 flex flex-col items-start gap-15 xs:flex-row xs:items-center md:mt-50 md:gap-30">
					<?php get_template_part( 'templates/parts/com-button', null, [
						'href'    => home_url( '/' ),
						'label'   => $button_text . ' →',
						'variant' => 'light',
					] ); ?>
					<?php get_template_part( 'templates/parts/com-button', null, [
						'href'    => home_url( '/#museums' ),
						'label'   => __( 'Ανακαλύψτε τα μουσεία', 'com-theme' ),
						'variant' => 'light-outline',
					] ); ?>
				</div>
			</div>

			<figure class="com-404__history-figure relative mx-auto w-full max-w-[55rem] md:col-span-12 md:col-start-13 md:mx-0 md:max-w-none">
				<div class="com-404__history-image relative aspect-[4/3] overflow-hidden rounded-[2rem] bg-blue-soft md:aspect-[599/540]">
					<?php if ( $background_id ) : ?>
						<?php get_template_part( 'templates/parts/image', null, [
							'id'       => $background_id,
							'size'     => 'full',
							'classes'  => $image_classes,
							'alt'      => '',
							'parallax' => false,
						] ); ?>
					<?php else : ?>
						<img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/com/raw-08.jpeg' ) ); ?>" alt="" class="<?php echo esc_attr( $image_classes ); ?>">
					<?php endif; ?>
					<div class="absolute inset-0 bg-blue mix-blend-multiply" style="opacity: <?php echo esc_attr( (string) max( 0.18, $overlay_opacity ) ); ?>;" aria-hidden="true"></div>
					<div class="absolute inset-0 bg-gradient-to-t from-blue/80 via-transparent to-transparent" aria-hidden="true"></div>
					<span class="absolute right-20 top-20 rounded-full border border-ochre/60 bg-blue/30 px-20 py-10 text-[1rem] font-medium tracking-[.16em] backdrop-blur-sm"><?php esc_html_e( 'Ι.Π. ΜΕΣΟΛΟΓΓΙΟΥ', 'com-theme' ); ?></span>
					<div class="absolute bottom-25 left-25 right-25 flex items-end justify-between gap-20">
						<p class="max-w-[26rem] text-[1.2rem] leading-[1.3] text-ochre"><?php esc_html_e( 'Ένας τόπος όπου η ιστορία παραμένει ζωντανή.', 'com-theme' ); ?></p>
						<span class="font-asty text-[5rem] leading-none text-ochre/90" aria-hidden="true">’26</span>
					</div>
				</div>
				<figcaption class="mt-15 flex items-center justify-between text-[1rem] tracking-[.12em] text-ochre/60">
					<span><?php esc_html_e( 'ΛΙΜΝΟΘΑΛΑΣΣΑ ΜΕΣΟΛΟΓΓΙΟΥ', 'com-theme' ); ?></span>
					<span>38.3689° N</span>
				</figcaption>
			</figure>
		</div>

		<div class="mt-50 grid grid-cols-3 border-t border-ochre/35 pt-20 text-[1rem] leading-[1.25] tracking-[.08em] text-ochre/60 md:mt-30">
			<div>
				<strong class="mb-5 block text-[1.4rem] font-normal text-ochre">1826</strong>
				<span><?php esc_html_e( 'Η Έξοδος', 'com-theme' ); ?></span>
			</div>
			<div class="text-center">
				<strong class="mb-5 block text-[1.4rem] font-normal text-ochre">2026</strong>
				<span><?php esc_html_e( '200 χρόνια', 'com-theme' ); ?></span>
			</div>
			<div class="text-right">
				<strong class="mb-5 block text-[1.4rem] font-normal text-ochre"><?php esc_html_e( 'ΣΗΜΕΡΑ', 'com-theme' ); ?></strong>
				<span><?php esc_html_e( 'Η ιστορία συνεχίζεται', 'com-theme' ); ?></span>
			</div>
		</div>
	</div>
</main>
