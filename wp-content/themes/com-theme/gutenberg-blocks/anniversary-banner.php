<?php
/**
 * Title: Anniversary Banner
 * Description: Compact highlighted callout with a link.
 *
 * @see wp-content/themes/com-theme/acf-json/anniversary-banner_group_6a6361ad579bd.json ACF Fields
 */

$eyebrow = get_field( 'eyebrow' );
$title = get_field( 'title' );
$text = get_field( 'text' );
$link = get_field( 'link' );
$section_title_id = wp_unique_id( 'anniversary-title-' );
?>
<section class="<?php echo esc_attr( com_theme_block_style_classes( 'bg-blue text-ochre-light', [] ) ); ?> flex py-60 lg:min-h-[28.1rem] lg:items-center" aria-labelledby="<?php echo esc_attr( $section_title_id ); ?>">
	<div class="<?php echo esc_attr( com_theme_block_wrapper_classes() ); ?> flex flex-col items-start justify-between gap-40 md:flex-row md:items-center">
		<div class="flex w-full flex-col gap-20 md:w-[48.3rem]">
			<div class="flex flex-col gap-10">
				<?php if ( $eyebrow ) : ?>
					<p class="text-[1rem] font-medium leading-none tracking-[.18em]"><?php echo esc_html( com\theme::remove_accents( $eyebrow ) ); ?></p>
				<?php endif; ?>
				<?php if ( $title ) : ?>
					<h2 id="<?php echo esc_attr( $section_title_id ); ?>" class="text-[3.6rem] font-light leading-none"><?php echo wp_kses_post( $title ); ?></h2>
				<?php endif; ?>
			</div>
			<?php if ( $text ) : ?>
				<p class="text-[1.4rem] font-light leading-[1.3] opacity-70"><?php echo esc_html( $text ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $link['url'] ) ) : ?>
			<?php
			get_template_part( 'templates/parts/com-button', null, [
				'href'    => $link['url'],
				'label'   => $link['title'],
				'variant' => 'light-outline',
				'target'  => $link['target'] ?? '',
			] );
			?>
		<?php endif; ?>
	</div>
</section>
