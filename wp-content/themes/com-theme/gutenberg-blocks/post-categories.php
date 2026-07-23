<?php // Title: Post Categories

if( is_singular() ){
    $postType = get_post_type();
    $taxonomy = $postType . 's';
    $terms = get_the_terms( get_the_ID(), $taxonomy );
    if( ! empty( $terms ) && ! is_wp_error( $terms )) {
?>
    <div class="page-wrapper mt-100 [&+.my-100]:mt-0 mb-50">
        <div class="mx-1/12 md:mx-2/12 md:w-8/12 text-12 font-bold leading-[1.33333]">
            <?php foreach ( $terms as $key => $term ) { ?>
                <a href="<?php echo get_term_link( $term ); ?>"><?php echo $term->name; ?></a><?php if( $key < count( $terms ) - 1 ) echo ', '; ?>
            <?php } ?>
        </div>
    </div>
<?php
    }
}
?>
