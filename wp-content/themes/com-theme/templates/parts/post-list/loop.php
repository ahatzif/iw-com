<?php
extract(wp_parse_args($args, ['query' => false, 'postType' => 'post', 'taxonomyName' => '']));
if (!empty($query) && !empty($postType)) { ?>

    <div class="my-80" id="<?php echo "ajax-results-" . $postType; ?>-container">
        <div class="grid grid-col-1 md:grid-cols-2 xl:grid-cols-3 gap-x-[4.166666666666667vw] gap-y-80 md:gap-y-[5.555555555555556vw]" id="ajax-results-<?php echo $postType; ?>">
            <?php while ($query->have_posts()) {
                $query->the_post();
                get_template_part('templates/parts/post-list/item', $postType, ['taxonomyName' => $taxonomyName]);
            }
            wp_reset_query(); ?>
        </div>
        <?php if ($query->max_num_pages > 1 && get_field('has_load_more_button')) { ?>
            <div class="pt-40">
                <?php
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $domain = $_SERVER['HTTP_HOST'];
                $path = explode('page/', $_SERVER['REQUEST_URI']);
                $path = $path[0];
                $url = $protocol . $domain . $path;
                if (isset($_GET['search'])) {
                    $params['search'] = esc_html(esc_attr($_GET['search']));
                }
                if ($type = get_field('load_more_type')) {
                    get_template_part('templates/parts/button', false, [
                        'link' => is_tax() ? get_term_link(get_queried_object()) : $url,
                        'text' => __('ΠΕΡΙΣΣΟΤΕΡΑ ΑΡΘΡΑ', 'com-theme'),
                        'color' => 'black',
                        'outline' => 'true',
                        'attrs' => 'data-module-load-more data-barba-prevent data-target="#ajax-results-' . $postType . '" data-max-num-pages="' . $query->max_num_pages . '" data-loading-text="' . __('ΠΑΡΑΚΑΛΩ ΠΕΡΙΜΕΝΕΤΕ', 'com-theme') . '" data-params="' . (empty($params) ? '?' : build_query($params)) . '"'
                    ]);
                } else {
                    get_template_part('templates/parts/post-list/paging', $postType, ['query' => $query, 'postType' => $postType]);
                } ?>
            </div>
        <?php } ?>
    </div>
<?php }
