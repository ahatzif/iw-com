<?php
$title = (string) ( $args['title'] ?? '' );
$description = (string) ( $args['description'] ?? '' );
$button_text = (string) ( $args['button_text'] ?? '' );
?>

<main class="com-404 com-404--playful relative isolate min-h-[82rem] overflow-hidden bg-ochre pt-[13rem] text-blue md:min-h-[82.5rem] md:pt-[13.841464rem]">
	<div class="pointer-events-none absolute -right-[8rem] top-[8rem] z-[-1] size-[32rem] rounded-full border border-blue/15 md:-right-[12rem] md:-top-[2rem] md:size-[58rem]" aria-hidden="true"></div>
	<div class="pointer-events-none absolute -bottom-[12rem] -left-[12rem] z-[-1] size-[34rem] rounded-full border border-blue/15 md:size-[48rem]" aria-hidden="true"></div>

	<div class="page-wrapper relative flex min-h-[69rem] flex-col py-40 md:min-h-[68.6rem] md:justify-between md:py-60">
		<div class="flex items-center justify-between gap-30">
			<div class="flex items-center gap-20 text-[1rem] font-medium tracking-[.18em] text-blue/60">
				<span><?php esc_html_e( 'ΧΜ… ΛΑΘΟΣ ΣΤΕΝΟ', 'com-theme' ); ?></span>
				<span class="h-px w-60 bg-blue/30 md:w-120" aria-hidden="true"></span>
				<span>404</span>
			</div>
			<span class="com-404__wandering-dot hidden size-10 rounded-full bg-blue md:block" aria-hidden="true"></span>
		</div>

		<div class="mt-40 grid flex-1 items-center gap-30 md:mt-10 md:grid-cols-24 md:gap-0">
			<div class="relative z-2 md:col-span-10 md:pr-40">
				<div class="com-404__playful-number relative -ml-[.08em] text-[12rem] font-light leading-[.76] tracking-[-.09em] md:text-[20rem]" aria-hidden="true">404</div>
				<p class="mt-25 text-[1rem] font-medium tracking-[.18em] text-blue/60 md:mt-35"><?php esc_html_e( 'Η ΣΕΛΙΔΑ ΔΕΝ ΒΡΕΘΗΚΕ', 'com-theme' ); ?></p>
				<h1 class="mt-10 max-w-[60rem] text-[3.8rem] font-normal leading-[1.06] tracking-[-.02em] md:text-[5rem]"><?php echo esc_html( $title ); ?></h1>
				<p class="mt-20 max-w-[48rem] text-[1.6rem] font-normal leading-[1.35] text-blue/75 md:text-[1.8rem]"><?php echo esc_html( $description ); ?></p>

				<div class="mt-35 flex flex-col items-start gap-15 xs:flex-row xs:items-center md:mt-40 md:gap-30">
					<?php get_template_part( 'templates/parts/com-button', null, [
						'href'    => home_url( '/' ),
						'label'   => $button_text . ' →',
						'variant' => 'blue',
					] ); ?>
					<?php get_template_part( 'templates/parts/com-button', null, [
						'href'    => home_url( '/#museums' ),
						'label'   => __( 'Ή δείτε τα μουσεία', 'com-theme' ),
						'variant' => 'blue-outline',
					] ); ?>
				</div>
			</div>

			<div class="relative mx-auto mt-20 w-[92%] max-w-[58rem] md:col-span-12 md:col-start-13 md:mt-0 md:w-full md:max-w-none">
				<div class="com-404__playful-card relative rotate-[1.5deg]">
					<div class="relative aspect-[4/3] overflow-hidden rounded-[2rem] border-[.8rem] border-white bg-white shadow-[0_2rem_5rem_rgba(23,50,118,.16)] md:aspect-[599/540]">
						<img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/com/raw-06.jpeg' ) ); ?>" alt="" class="absolute inset-0 size-full object-cover">
						<div class="absolute inset-0 bg-blue/10 mix-blend-multiply" aria-hidden="true"></div>
					</div>

					<div class="com-404__road-sign absolute -left-20 top-20 -rotate-[5deg] rounded-[1rem] border border-blue bg-ochre-light px-20 py-15 text-[1.2rem] font-bold leading-[1.2] shadow-[0_.8rem_2rem_rgba(23,50,118,.12)] md:-left-50 md:top-50 md:px-25 md:py-20 md:text-[1.4rem]">
						<span class="mb-5 block text-[2.2rem] font-normal leading-none" aria-hidden="true">←</span>
						<?php esc_html_e( 'Το Μεσολόγγι είναι από εδώ', 'com-theme' ); ?>
					</div>

					<div class="absolute -bottom-20 right-15 rotate-[3deg] rounded-full bg-blue px-25 py-15 text-[1.2rem] font-normal text-white shadow-[0_.8rem_2rem_rgba(23,50,118,.2)] md:-bottom-25 md:right-30 md:text-[1.4rem]">
						<?php esc_html_e( 'Η σελίδα; Κανείς δεν ξέρει.', 'com-theme' ); ?>
					</div>
				</div>
			</div>
		</div>

		<div class="mt-70 flex flex-col gap-10 border-t border-blue/35 pt-20 text-[1.1rem] tracking-[.08em] text-blue/60 md:mt-30 md:flex-row md:items-center md:justify-between">
			<p><?php esc_html_e( 'ΔΕΝ ΧΑΘΗΚΕ ΤΙΠΟΤΑ — ΕΚΤΟΣ ΑΠΟ ΑΥΤΗ ΤΗ ΣΕΛΙΔΑ.', 'com-theme' ); ?></p>
			<p><?php esc_html_e( 'ΜΙΚΡΗ ΠΑΡΑΚΑΜΨΗ · ΜΕΓΑΛΗ ΙΣΤΟΡΙΑ', 'com-theme' ); ?></p>
		</div>
	</div>
</main>
