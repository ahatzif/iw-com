<?php
require_once 'inc/theme.php';

add_filter( 'get_per_page', function( $post_type ){
    if( $post_type == 'article' ){
        return 12;
    } else if( $post_type == 'guide' ){
        return 12;
    } else if( $post_type == 'experience' ){
        return 10;
    } else {
        return 10;
    }
} );

add_action('pre_get_posts', function ($query) {
    if ( ! is_admin() && $query->is_main_query() && is_tax() ) {
        $taxonomy = get_queried_object();
        $taxonomySlug = $taxonomy->taxonomy;
        $taxonomyObj = get_taxonomy( $taxonomySlug );
        $query->set('posts_per_page', apply_filters( 'get_per_page',  $taxonomyObj->object_type[0] ) );
    }
});


