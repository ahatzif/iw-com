<?php
if( get_field( 'hide_breadcrumb') ) return;
if ( apply_filters('show_breadcrumb', true) ) {
    global $post;
    $separator = ' &rsaquo; ';
    $breadcrumbs = [];
    if (is_singular('page')) {
        $parentId = $post->post_parent;
        $breadcrumbs = [com\theme::load_template_part('templates/parts/breadcrumbs/item', ['url' => get_permalink($post), 'title' => $post->post_title])];
        //$breadcrumbs = [];
        while ($parentId) {
            $parent = get_post($parentId);
            $breadCrumbs = '<a href="' . get_permalink($parent) . '">' . $parent->post_title . "</a> / " . $breadCrumbs;
            $breadcrumbs[] = com\theme::load_template_part('templates/parts/breadcrumbs/item', ['url' => get_permalink($parent), 'title' => $parent->post_title]);
            $parentId = $parent->post_parent;
        }
        $breadcrumbs = array_reverse($breadcrumbs);
    } else if (is_singular()) {
        $postType = get_post_type();
        if (in_array($postType, ['products', 'recipe', 'new']))
            $parentPage = get_field(get_post_type() . 's_page', 'options');

        $breadcrumbs = [];

        if (!empty($parentPage)) {
            $breadcrumbs[] = com\theme::load_template_part('templates/parts/breadcrumbs/item', ['url' => get_permalink($parentPage), 'title' => $parentPage->post_title]);
        }
        $breadcrumbs = array_reverse($breadcrumbs);
    } else if (is_tax()) {
        $cat = get_queried_object();
        $parentPage = get_field($cat->taxonomy . '_page', 'options');
        //$breadcrumbs = [ com\theme::load_template_part( 'templates/parts/breadcrumbs/item' , [ 'url' => get_term_link( $cat ), 'title' => $cat->name ] )  ];
        $breadcrumbs = [];
        $parentId = $cat->parent;
        while ($parentId) {
            $term = get_term($parentId, $cat->taxonomy);
            $breadcrumbs[] = com\theme::load_template_part('templates/parts/breadcrumbs/item', ['url' => get_term_link($term), 'title' => $term->name]);
            $parentId = $term->parent;
        }

        if (!empty($parentPage)) {
            $breadcrumbs[] = com\theme::load_template_part('templates/parts/breadcrumbs/item', ['url' => get_permalink($parentPage), 'title' => $parentPage->post_title]);
        }
        $breadcrumbs = array_reverse($breadcrumbs);
    } else if (is_search()) {
        $breadcrumbs[] = com\theme::load_template_part('templates/parts/breadcrumbs/item', ['url' => home_url('/') . '?s=' . get_search_query(), 'title' => __('Search Results ')]);
    }

    array_unshift($breadcrumbs, com\theme::load_template_part('templates/parts/breadcrumbs/item', ['url' => home_url('/'), 'title' => __('Home', 'com-theme')])); ?>
    <ul class="px-1/24 my-20 text-[14px] flex flex-wrap items-center gap-10"><?php echo implode($separator, $breadcrumbs); ?></ul>
<?php }
?>
