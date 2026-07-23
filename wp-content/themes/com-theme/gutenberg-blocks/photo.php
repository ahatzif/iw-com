<?php
// Title: Photo
$photoType = get_field( 'type');
$classes = $photoType === 'inline' ? '' : 'w-full h-auto';
$size = $photoType === 'full' ? 'com-theme-content-photo-full' : 'full';
// TODO: maybe we could add alignment for the inline type
$photoFieldName = $photoType === 'default' ? 'photo_cropped' : 'photo';
?>
<?php if( ! empty( $photoID = get_field( $photoFieldName ) ) ) { ?>
<div class="px-2/12 mb-[7.5rem] [&+.wysiwyg]:mt-0">
    <?php get_template_part('templates/parts/image', false, [ 'id' => $photoID, 'size' => $size, 'classes' => $classes  ]); ?>
</div>
<?php } ?>
