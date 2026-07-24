<?php extract(wp_parse_args($args, ['terms' => false, 'postType' => 'post', 'filters' => false, 'allowMultiple' => false]));

$currentTermId = false;
if (is_tax()) {
    $cat = get_queried_object();
    $currentTermId = $cat->term_id;
}

$parentPage = get_field($postType . 's_page', 'options');

$allIsActive = !is_tax();
if ($allowMultiple && !empty($filters)) {
    $allIsActive = false;
    $filtersIds = array_column($filters['terms'], 'term_id');
} ?>
<?php if (!empty($terms)) { ?>
    <div data-module-load-more-filters data-module-swiper data-href="<?php echo get_permalink($parentPage); ?>" data-target="#<?php echo "ajax-results-" . $postType; ?>-container" <?php if ($allowMultiple) echo 'data-multiple="true"'; ?>>
        <div class="mb-30 swiper" data-swiper="swiper">
            <div class="flex space-x-20 wrapper">
                <?php get_template_part('templates/parts/button', null, [
                    'tag' => 'a',
                    'link' => '' . get_permalink($parentPage) . '',
                    'attrs' => 'data-barba-prevent data-load-more-filters="' . ($allowMultiple ? 'clear' : 'button') . '"',
                    'classes' => 'slide [&.active]:bg-blue [&.active]:text-white ' . ($allIsActive ? 'active' : ''),
                    'text' => __('ΟΛΑ', 'com-theme'),
                    'outline' => true,
                    'color' => 'blue',
                    'size' => 'small',
                    'hover' => false
                ]);

                foreach ($terms as $term) {

                    $termIsActive = $currentTermId === $term->term_id;
                    if ($allowMultiple && !empty($filters)) {
                        $termIsActive = in_array($term->term_id, $filtersIds);
                    }

                    get_template_part('templates/parts/button', null, [
                        'tag' => 'a',
                        'link' => '' . get_term_link($term) . '',
                        'attrs' => 'data-barba-prevent data-load-more-filters="button" data-name="' . esc_attr($term->name) . '" data-id="' . $term->term_id . '" data-slug="' . $term->slug . '"',
                        'classes' => 'slide  [&.active]:bg-blue [&.active]:text-white ' . ($termIsActive ? 'active' : ''),
                        'text' => $term->name,
                        'outline' => true,
                        'size' => 'small',
                        'hover' => false
                    ]);
                } ?>
            </div>
        </div>

        <?php if ($allowMultiple) { ?>
            <div class="text-button-small text-blue md:space-x-20 hidden [&.active]:block md:[&.active]:flex <?php if (!empty($filters)) echo 'active'; ?>" data-load-more-filters="pills-outer">
                <div class="shrink-0"><?php _e('ΕΧΕΤΕ ΕΠΙΛΕΞΕΙ', 'com-theme'); ?>:</div>
                <div data-load-more-filters="pills" class="flex flex-wrap">
                    <?php if( ! empty( $filters) ) { foreach ( $filters[ 'terms' ] as $term  ) { ?>
                        <div class="mr-20"><span class="cursor-pointer" data-id="<?php echo $term->term_id; ?>" data-remove>x</span> <?php echo $term->name; ?></div>
                    <?php } } ?>
                </div>
            </div>
        <?php } ?>
    </div>
<?php }
