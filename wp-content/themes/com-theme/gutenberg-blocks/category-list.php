<?php // Title: Category List ?>
<?php $terms = get_terms(['taxonomy' => get_field('taxonomy_type'), 'parent' => 0,  'hide_empty' => get_field('hide_empty')]); ?>
<?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) { ?>
    <section class="<?php echo esc_attr( com_theme_block_style_classes() ); ?>">
        <?php get_template_part('templates/taxonomy-products-sub', false, ['terms' => $terms]); ?>
    </section>
<?php } ?>
