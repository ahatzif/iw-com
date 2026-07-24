<?php // Title: Post Categories

if( is_singular() ){
    $postType = get_post_type();
    $taxonomy = $postType . 's';
    $terms = get_the_terms( get_the_ID(), $taxonomy );
    if( ! empty( $terms ) && ! is_wp_error( $terms )) {
?>
    <section class="<?php echo esc_attr( com_theme_block_style_classes( 'text-blue', [ 'desktop' => [ 'mt' => '100', 'mb' => 'small' ] ] ) ); ?>">
    <div class="<?php echo esc_attr( com_theme_block_wrapper_classes() ); ?>">
        <div class="mx-1/12 md:mx-2/12 md:w-8/12 text-12 font-bold leading-[1.33333]">
            <?php foreach ( $terms as $key => $term ) { ?>
                <a href="<?php echo get_term_link( $term ); ?>"><?php echo $term->name; ?></a><?php if( $key < count( $terms ) - 1 ) echo ', '; ?>
            <?php } ?>
        </div>
    </div>
    </section>
<?php
    }
}
?>
