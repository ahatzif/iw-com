<?php
extract( wp_parse_args( $args, [ 'query' => false, 'postType' => 'post' ] ) );
if( ! empty( $query ) && ! empty( $postType ) ){
?>
<div data-module-swiper data-options='{ "slideClass" : "item-<?php echo $postType; ?>" }'>
    <div data-swiper="swiper">
        <div class="wrapper flex">
            <?php  while ( $query->have_posts()) { $query->the_post();
                get_template_part('templates/parts/post-list/item', $postType, [ 'wrapperClass' => 'w-8/12 shrink-0 md:w-auto md:shrink']);
            } wp_reset_query(); ?>
        </div>
    </div>
</div>
<?php }
