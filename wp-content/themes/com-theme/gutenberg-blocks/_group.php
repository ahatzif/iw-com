<?php

extract(wp_parse_args($args, [
    'blocks' => [],
    'type' => 'grid',
    'columnCount' => 2,
    'attrs' => []
]));

$heading = empty($attrs['groupHeading']) ?  false : $attrs['groupHeading'];
$tailwind = "grid-cols-1 grid-cols-2 grid-cols-3 grid-cols-5";

$cols = $args['columnCount'];
$gridCols = '';
if ($cols === "2") {
    $gridCols = 'grid-cols-1 md:grid-cols-' . $cols;
}
if ($type === 'grid' && ! empty($blocks)) { ?>
    <section class="bg-ochre-light relative">
        <div class="relative">
            <?php get_template_part('templates/parts/stroked-text', false, ['text' => $heading, 'vertical' => false, 'strokeClass' => 'text-stroke-white', 'parallax' => false]); ?>
            <div class="group grid <?php echo $gridCols; ?> [&_section_.page-wrapper]:bg-[transparent] [&_section:nth-child(2).page-wrapper]:pr-0 md-max:[&_section:nth-child(3)_.py-90]:pt-0 [&_section:nth-child(3)_.page-wrapper]:pl-0">
                <?php foreach ($blocks  as $block) {
                    echo $block;
                } ?>
            </div>
        </div>

    </section>

<?php
}
