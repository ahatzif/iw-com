<?php
require_once 'inc/theme.php';

add_filter( 'get_per_page', function( $post_type ){
    if( $post_type == 'article' ){
        return 12;
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



function get_prose(){
    return  "   text-[1.6rem] leading-[1.5] text-current
                [&_*:first-child]:mt-0
                [&_*:last-child]:mb-0
                
                [&_a]:underline 
                [&_p]:my-[1em]
                    
                    
                [&_h1]:text-[2.986rem] [&_h1]:mt-[2em] [&_h1]:leading-none
                [&_h2]:text-[2.488rem] [&_h2]:mt-[2em] [&_h2]:leading-none
                [&_h3]:text-[2.074rem] [&_h3]:mt-[1em] [&_h3]:leading-none
                [&_h4]:text-[1.728rem] [&_h4]:mt-[1em] [&_h4]:leading-none
                [&_h5]:text-[1.44rem] [&_h5]:mt-[1em] [&_h5]:leading-none
                [&_h6]:text-[1.2rem] [&_h6]:mt-[1em] [&_h6]:leading-none
                
                [&_ul]:list-none [&_ul]:px-0 [&_ul]:my-[3rem]
                [&_ul_li]:pl-0
                [&_ul_li]:relative [&_ul_li]:pl-[0.75em]
                [&_ul_li]:before:absolute [&_ul_li]:before:left-0 [&_ul_li]:before:inline-block [&_ul_li]:before:w-[0.3125em] [&_ul_li]:before:h-[0.3125em] [&_ul_li]:before:mt-[0.6em] [&_ul_li]:before:rounded-full [&_ul_li]:before:bg-current [&_ul_li]:before:shrink-0
                
                [&_ol]:[list-style-position:inside] [&_ol]:[list-style-type:auto]
            ";
}
