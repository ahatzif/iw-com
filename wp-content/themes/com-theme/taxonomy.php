<?php

$block_pattern = get_field('block_pattern', get_queried_object());
if (empty($block_pattern)) {
    $black_pattern_query = new WP_Query(['post_type' => 'wp_block', 'posts_per_page' => 1, 'title' => ucfirst(get_query_var('taxonomy')) . ' Pattern']);
    if ($black_pattern_query->have_posts()) {
        $block_pattern = $black_pattern_query->posts[0];
    }
}
get_header();
if (! empty($block_pattern)) {
    global $post;
    $post = $block_pattern;
    setup_postdata($block_pattern);
    the_content();
    wp_reset_postdata();
} else {
    $taxonomy = get_queried_object();
    $taxonomySlug = $taxonomy->taxonomy;
    $taxonomyObj = get_taxonomy($taxonomySlug);
    $page = get_field($taxonomyObj->object_type[0] . 's_page', 'options');

    if (! empty($page)) {
        echo apply_filters('the_content', $page->post_content);
    }
}
get_footer();
