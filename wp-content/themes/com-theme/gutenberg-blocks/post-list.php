<?php  // Title: Post List


$postSelection =                get_field('post_selection');
$allowMultiple =                get_field('allow_multiple');
$listViewTemplateSelection =    get_field('list_view_template');
$postType =                     get_field('post_type_select');
$showFilters =                  get_field('show_filters');
$howMany =                      empty(get_field('how_many')) ? apply_filters('get_per_page', $postType) : get_field('how_many');
$taxonomyName =                 get_field($postType .  '_taxonomy'); // array of selected taxonomies ( Main Taxonomy )
$taxQuery =                     [];
$loadMoreParams =               [];
$paged =                        get_query_var('paged') ? get_query_var(query_var: 'paged') : 1;
$perPage =                      apply_filters('get_per_page', $postType);
$isSearch =                     isset($_GET['search']);
$listViewTemplate =             'loop';
$filters =                      [];

$queryArgs = ['posts_per_page' => $howMany, 'paged' => $paged, 'post_type' => $postType, 'post_status' => 'publish'];

if (! empty($listViewTemplateSelection) && $listViewTemplateSelection !== 'default') {
    $listViewTemplate = $listViewTemplateSelection;
}

if ($listViewTemplate === 'custom') {
    $listViewTemplate = get_field('list_view_template_custom');
}

if (isset($_GET['type'])) { // eg for search pages
    if (! empty($searchQuery = trim(apply_filters('get_search_query', $_GET['type'])))) {
        $searchingPostTypes = explode(',', $searchQuery);
        if (! in_array($postType, $searchingPostTypes)) {
            return;
        }
    }


    if (false && is_tax()) {
        global $wp_query;
        $query = $wp_query;
        $taxonomyName = get_query_var('taxonomy');
    } else {
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;

        $taxonomyName = (array) get_field($postType .  '_taxonomy');
        $perPage = apply_filters('get_per_page', $postType);
        $howMany = get_field('how_many');

        if (empty($howMany)) $howMany = $perPage;
        $queryArgs = [
            'posts_per_page' => $howMany,
            'paged' => $paged,
            'post_type' => $postType,
            'post_status' => 'publish',
        ];
        if (is_singular()) {
            $queryArgs['post__not_in'] = apply_filters('post__not_in_' . $postType, [get_the_ID()]);
        }

        if ($postSelection === 'auto') {
            if ($isSearch) {
                $queryArgs['s'] = apply_filters('get_search_query', $_GET['search']);
                if ($postType === 'recipe') {
                    $searchTerms = explode(' ', apply_filters('get_search_query', $_GET['search']));
                    $queryArgs['meta_query'] = ['relation' => 'OR'];
                    foreach ($searchTerms as $searchTerm) {
                        $queryArgs['meta_query'][] = ['key' => 'execution', 'value' => $searchTerm, 'compare' => 'LIKE'];
                        $queryArgs['meta_query'][] = ['key' => 'ingredients_menu_recipe_$_ingredinets_name_and_quantity', 'value' => $searchTerm, 'compare' => 'LIKE'];
                    }
                    add_filter('posts_where', function ($where) {
                        return str_replace("meta_key = 'ingredients_menu_recipe_$", "meta_key LIKE 'ingredients_menu_recipe_%", $where);
                    });
                }
            }

            $taxQuery = [];

            if (!empty($filters)) {
                $queryArgs['tax_query'] = $filters['tax_query'];
                if (get_field('terms_relation')) {
                    $queryArgs['tax_query']['relation'] = 'OR';
                }
            } else {
                foreach (get_object_taxonomies($postType) as $taxName) {
                    if (!empty($terms = get_field($postType .  '_' . $taxName))) {
                        $taxQuery[] = ['taxonomy' => $taxName, 'field' => 'id', 'terms' => $terms];
                    }
                }
                if (!empty($taxQuery)) {
                    $taxQuery['relation'] = 'AND';
                    $queryArgs['tax_query'] = $taxQuery;
                }
            }
        } else if ($postSelection === 'related') {
            if (is_singular()) {
                $singularPostType = get_post_type();
                if ($singularPostType !== $postType) {
                    // for recipes we assume that the the corresponding field is related_products
                    $queryArgs['meta_query'] =  [['key' => 'related_' . $singularPostType . 's', 'value' => '"' . get_the_ID() . '"', 'compare' => 'LIKE']];
                } else {
                    $queryArgs['tax_query'] =  [
                        'relation' => 'OR',
                    ];
                    foreach ($taxonomyName as $taxName) {
                        $postTerms = wp_get_post_terms(get_the_ID(), $taxName,  array('fields' => 'ids'));
                        if (!empty($postTerms)) {
                            $queryArgs['tax_query'][] =  [
                                [
                                    'taxonomy' => $taxName,
                                    'field' => 'id',
                                    'terms' => $postTerms
                                ]
                            ];
                        }
                    }
                }
            }
        } else if ($postSelection === 'selection') {
            $queryArgs = wp_parse_args([
                'paged' => 1,
                'posts_per_page' => -1,
                'post__in' => get_field('selected_posts'),
                'orderby' => 'post__in'

            ], $queryArgs);
        }

        $query = new WP_Query($queryArgs);
    }
} // else {
//     $query = $args['query'];
//     $postType = $args['postType'];
//     $taxonomyName = $args['taxonomyName'];
// }

if (is_singular()) {
    $queryArgs['post__not_in'] = apply_filters('post__not_in_' . $postType, [get_the_ID()]);
}

if ($postSelection === 'auto') {
    if ($isSearch) {
        $queryArgs['s'] = apply_filters('get_search_query', $_GET['search']);
        $loadMoreParams['search'] = apply_filters('get_search_query', $_GET['search']);
    }
    if ($showFilters && class_exists('IW_Posts_Filters')) {
        $taxQuery = IW_Posts_Filters::get_tax_query($taxonomyName);
        // TODO: Meta Query
    } else { // show posts from selected specified taxonomies in the block options
        foreach ($taxonomyName as $taxName) {
            if (! empty($specifiedTerms = get_field($postType .  '_' . $taxName))) {
                $taxQuery[] = ['taxonomy' => $taxName, 'field' => 'id', 'terms' => $specifiedTerms];
            }
        }
    }
} else if ($postSelection === 'related') { // Show related posts
    if (is_singular()) {
        $singularPostType = get_post_type();
        if ($singularPostType !== $postType) { // eg for recipes we assume that the the corresponding field is related_recipes
            $queryArgs['meta_query'] =  [['key' => 'related_' . $singularPostType . 's', 'value' => '"' . get_the_ID() . '"', 'compare' => 'LIKE']];
        } else {
            $queryArgs['tax_query'] =  ['relation' => 'OR'];
            foreach ($taxonomyName as $taxName) {
                $postTerms = wp_get_post_terms(get_the_ID(), $taxName,  array('fields' => 'ids'));
                if (! empty($postTerms)) {
                    $taxQuery[] =  [['taxonomy' => $taxName, 'field' => 'id', 'terms' => $postTerms]];
                }
            }
        }
    }
} else if ($postSelection === 'selection') {
    $queryArgs = wp_parse_args(['paged' => 1, 'posts_per_page' => -1, 'post__in' => get_field('selected_posts'), 'orderby' => 'post__in'], $queryArgs);
}


if (! empty($taxQuery)) {
    $queryArgs['tax_query'] = $taxQuery;
    if ($postSelection === 'auto' && get_field('terms_relation')) {
        $queryArgs['tax_query']['relation'] = 'OR';
    }
}

$query = new WP_Query($queryArgs);
$terms = [];

if (!empty($taxonomyName) && $taxonomyName !== 'no') {
    foreach ($taxonomyName as $taxName) {
        $terms[$taxName] = get_terms(['taxonomy' => $taxName, 'hide_empty' => false]);
    }
}


$showFilters = get_field('show_filters');


if ($postSelection === 'auto' && $showFilters) {
    get_template_part('templates/parts/post-list/filters', $postType, ['postType' => $postType, 'allowMultiple' => $allowMultiple, 'terms' => $terms, 'filters' => $filters]);
}
if ($query->have_posts() || $showFilters) {
    get_template_part('templates/parts/post-list/' . $listViewTemplate, $postType, ['query' => $query, 'postType' => $postType, 'params' => $loadMoreParams, 'taxonomyName' => $taxonomyName, 'blockFields' => get_fields()]);
}
