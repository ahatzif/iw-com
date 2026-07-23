<?php
/**
 * Plugin Name:     Iw Recipes Filters
 * Plugin URI:      https://interweaveagency.com/
 * Description:
 * Author:          Interweave Agency
 * Author URI:      YOUR SITE HERE
 * Text Domain:     iw-recipes-filters
 * Version:         0.1.0
 *
 * @package         Iw_Recipes_Filters
 */
// Your code starts here.
class IW_Posts_Filters
{
    public function __construct()
    {
        add_filter('query_vars', [$this, 'query_vars']);
        add_action('init', [$this, 'rewrite_rules']);
        add_action('template_redirect', [$this, 'news_filters']);
    }
    public function rewrite_rules()
    {
        add_rewrite_rule('news/(.+)/?', 'index.php?pagename=news&posts-filters=$matches[1]&posts-filters-tax=news', 'bottom');
        add_rewrite_rule('nea/(.+)/?', 'index.php?pagename=nea&posts-filters=$matches[1]&posts-filters-tax=news', 'bottom');

        add_rewrite_rule('recipes/(.+)/?', 'index.php?pagename=recipes&posts-filters=$matches[1]&posts-filters-tax=recipes', 'bottom');
        add_rewrite_rule('sintages/(.+)/?', 'index.php?pagename=sintages&posts-filters=$matches[1]&posts-filters-tax=recipes', 'bottom');

        add_rewrite_rule('producers/(.+)/?', 'index.php?pagename=producers&posts-filters=$matches[1]&posts-filters-tax=producers', 'bottom');
        add_rewrite_rule('producers/(.+)/?', 'index.php?pagename=producers&posts-filters=$matches[1]&posts-filters-tax=producers', 'bottom');


        flush_rewrite_rules();
    }
    function news_filters()
    {
        $filters = get_query_var('posts-filters');
        if ( ! empty( $filters )) {
            $filterParts = explode('/page/', $filters);

            if ( ! empty($filterParts[1]) ) {
                set_query_var('paged', (int) $filterParts[1]);
            }
            if ( ! empty($filterParts[0])) {
                set_query_var('terms-slugs', explode('/', $filterParts[0]) );
            }
        }
    }
    public function query_vars($vars)
    {
        $vars[] = 'posts-filters';
        $vars[] = 'posts-filters-tax';
        $vars[] = 'terms-slugs';
        return $vars;
    }
    public static function get_filters()
    {
        $termsSlugs = get_query_var('terms-slugs');
        $taxonomyName = get_query_var('posts-filters-tax');




        if (is_tax()) {
            $term = get_queried_object();
            $termsSlugs = [$term->slug];
            $taxonomyName = $term->taxonomy;
;        }

        if (!empty($termsSlugs)) {
            $terms = $taxQuery = [];
            foreach ($termsSlugs as  $termSlug) {
                if ( ! empty($termSlug) && ! empty( $term = get_term_by('slug',  $termSlug, $taxonomyName ) ) ) {
                    $terms[] = $term;
                    $taxQuery[] = ['taxonomy' => $taxonomyName, 'terms' => $term->term_id, 'field' => 'id', 'operator' => 'IN'];
                }
            }
            if (!empty($taxQuery)) {
                $taxQuery['relation'] = 'AND';
            }
            return ['terms' => $terms, 'tax_query' => $taxQuery];
        }
        return false;
    }
}
new IW_Posts_Filters();
