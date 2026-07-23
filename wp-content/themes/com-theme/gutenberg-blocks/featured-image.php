<?php // Title: Featured Image ?>
<div class="bg-dove text-white mb-100 relative" >
    <div class="aspect-[2.4] relative min-h-[400px]">
        <?php get_template_part('templates/parts/image', false, [ 'lazy' => false, 'id' => get_post_thumbnail_id(get_the_ID()), 'size' => 'com-theme-featured-image', 'parallax' => false ] ); ?>
    </div>
    <div class="absolute top-0 left-0 w-full">
        <?php get_template_part( 'templates/parts/breadcrumbs', false, []); ?>
    </div>
    <div data-main-menu-trigger class="h-header-height absolute bottom-0 left-0 w-full text-base-heading"></div>
</div>
