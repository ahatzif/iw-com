<?php
/**
 * Title: Museums List
 * Description: Dynamic list of museum ticket cards.
 *
 * @see wp-content/themes/com-theme/acf-json/museums-list_group_6a6361ad57251.json ACF Fields
 */

$eyebrow = get_field( 'eyebrow' ) ?: __( 'ΤΙΤΛΟΣ', 'com-theme' );
$title = get_field( 'title' );
$anchor_id = sanitize_title( get_field( 'anchor_id' ) ?: 'museums' );
$museums = get_field( 'museums' );
$show_all_card = get_field( 'show_all_card' );
$section_title_id = wp_unique_id( 'museums-title-' );

if ( empty( $museums ) ) {
	$museums = get_posts( [
		'post_type'      => 'museum',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
	] );
}

$all_card_ticket_post = get_field( 'all_card_ticket_post' );
$all_card_ticket_id = com_theme_all_museums_ticket_post_id( $all_card_ticket_post );
$all_card_more_url = $all_card_ticket_id ? get_permalink( $all_card_ticket_id ) : com_theme_link_url( get_field( 'all_card_link' ) );

$all_card = [
	'is_all'      => true,
	'id'          => $all_card_ticket_id,
	'place'       => get_field( 'all_card_place' ) ?: __( 'ΜΕΣΟΛΟΓΓΙ, ΑΙΤΩΛΙΚΟ', 'com-theme' ),
	'title'       => get_field( 'all_card_title' ) ?: ( $all_card_ticket_id ? get_the_title( $all_card_ticket_id ) : __( 'Επίσκεψη σε όλα τα μουσεία', 'com-theme' ) ),
	'description' => get_field( 'all_card_text' ) ?: ( $all_card_ticket_id ? get_the_excerpt( $all_card_ticket_id ) : '' ),
	'url'         => $all_card_more_url ?: com_theme_option_page_url( 'tickets_page', 'tickets' ),
	'ticket_url'  => com_theme_all_museums_ticket_url( $all_card_ticket_post ),
	'price'       => com_theme_all_museums_ticket_price_text( $all_card_ticket_id, get_field( 'all_card_price' ) ?: __( '3€ - 6€', 'com-theme' ) ),
];

$ticket_label = get_field( 'ticket_label' ) ?: __( 'Εισιτήρια', 'com-theme' );
$more_label = get_field( 'more_label' ) ?: __( 'Περισσότερα →', 'com-theme' );
?>
<section id="<?php echo esc_attr( $anchor_id ); ?>" class="<?php echo esc_attr( com_theme_block_style_classes( 'bg-ochre text-blue', [] ) ); ?> py-90" aria-labelledby="<?php echo esc_attr( $section_title_id ); ?>">
	<div class="<?php echo esc_attr( com_theme_block_wrapper_classes() ); ?> flex flex-col gap-60">
		<?php if ( $eyebrow || $title ) : ?>
			<header class="flex w-full flex-col gap-5 md:w-[60rem]">
				<?php if ( $eyebrow ) : ?>
					<p class="text-[1rem] font-medium leading-none tracking-[.18em] opacity-60"><?php echo esc_html( com\theme::remove_accents( $eyebrow ) ); ?></p>
				<?php endif; ?>
				<?php if ( $title ) : ?>
					<h2 id="<?php echo esc_attr( $section_title_id ); ?>" class="text-[2rem] font-normal leading-[1.2]"><?php echo wp_kses_post( $title ); ?></h2>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<div class="flex flex-col gap-40">
			<?php
			if ( $show_all_card !== false ) {
				get_template_part( 'templates/parts/museum-card', null, [
					'museum'      => $all_card,
					'ticket_label' => $ticket_label,
					'more_label'   => $more_label,
				] );
			}

			foreach ( (array) $museums as $museum ) {
				$museum_id = $museum instanceof WP_Post ? $museum->ID : (int) $museum;

				if ( ! $museum_id ) {
					continue;
					}

				if ( $show_all_card !== false && $all_card_ticket_id && $museum_id === $all_card_ticket_id ) {
					continue;
					}

					$museum_ticket_url = com_theme_museum_ticket_url( $museum_id );
					$museum_data = [
					'is_all'      => false,
					'id'          => $museum_id,
					'place'       => get_field( 'place_label', $museum_id ) ?: com_theme_museum_location_label( $museum_id ),
					'title'       => get_the_title( $museum_id ),
					'description' => get_the_excerpt( $museum_id ),
					'url'         => get_permalink( $museum_id ),
						'ticket_url'  => $museum_ticket_url,
					'price'       => get_field( 'ticket_price_text', $museum_id ),
					'image_id'    => com_theme_museum_image_id( $museum_id ),
				];

				get_template_part( 'templates/parts/museum-card', null, [
					'museum'      => $museum_data,
					'ticket_label' => $ticket_label,
					'more_label'   => $more_label,
				] );
			}
			?>
		</div>
	</div>
</section>
