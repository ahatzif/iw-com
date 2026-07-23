<?php
extract(wp_parse_args($args, ['query' => false, 'postType' => 'post', 'taxonomyName' => '', 'blockFields' => []]));


$isWhiteBg = ! empty($blockFields['background_color']) && ($blockFields['background_color'] === 'bg-white' || $blockFields['background_color'] === 'default');
$background = isset( $blockFields['background_color'] ) ?? '';
$strokeClass = $isWhiteBg ? 'text-stroke-white' : 'text-stroke-blue';
$hoverClass = $isWhiteBg ? '[&_a:hover]:text-blue' : '[&_a:hover]:text-yellow';
$buttonColor = $isWhiteBg ? 'blue' : 'white';

if (! empty($query) && ! empty($postType)) {
?>
    <div class="relative <?php echo $background; ?> ">
        <?php if ($blockFields['title'] ) { ?>
        <div class="text-section-inter-regular font-secondary mb-60"><?php echo $blockFields['title']; ?></div>
        <?php } ?>
        <div class="space-y-40">
            <?php while ($query->have_posts()) {
                $query->the_post();
                get_template_part('templates/parts/post-list/item', $postType, [
                    'taxonomyName' => $taxonomyName,
                    'hoverClass' => $hoverClass,
                    'wrapperClass' => ''
                ]);
            }
            wp_reset_query(); ?>
        </div>
    </div>
<?php }
