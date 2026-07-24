<?php
// Title: Photo
$photoType = get_field( 'type');
$classes = $photoType === 'inline' ? '' : 'w-full h-auto';
$size = $photoType === 'full' ? 'com-theme-content-photo-full' : 'full';
// TODO: maybe we could add alignment for the inline type
$photoFieldName = $photoType === 'default' ? 'photo_cropped' : 'photo';
?>
<?php if( ! empty( $photoID = get_field( $photoFieldName ) ) ) { ?>
<section class="<?php echo esc_attr( com_theme_block_style_classes( '', [ 'desktop' => [ 'mb' => '80' ] ] ) ); ?>">
<div class="<?php echo esc_attr( com_theme_block_wrapper_classes( 'px-2/12' ) ); ?> [&+.wysiwyg]:mt-0">
    <?php get_template_part('templates/parts/image', false, [ 'id' => $photoID, 'size' => $size, 'classes' => $classes  ]); ?>
</div>
</section>
<?php } ?>
