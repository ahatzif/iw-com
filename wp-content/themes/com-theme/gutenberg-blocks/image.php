<?php // Title: Image

$size = get_field( 'size' );
?>
<section class="<?php echo esc_attr( com_theme_block_style_classes( '' ) ); ?>">
<div class="<?php echo esc_attr( com_theme_block_wrapper_classes() ); ?>">
    <div class="<?php echo $size === 'default' ? 'mx-1/12 md:mx-2/12' : 'mx-1/12'; ?>">
        <div class="relative aspect-[1] md:aspect-[1.5]">
            <?php get_template_part('templates/parts/image', false, [ 'id' => get_field( 'image_' . $size,  )  ] ); ?>
        </div>
    </div>
</div>
</section>
