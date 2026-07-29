<?php

extract( wp_parse_args( $args, [
    'id' => false,
    'alt' => false,
    'size' => 'com-theme-featured-image',
    'classes' => 'object-cover absolute top-0 left-0 w-full h-full',
    'additionalClasses' => '',
    'lazy' => true,
    'parallax' => true,
    'attrs' => ''
]));



if( ! empty( $id ) && ! empty( $imageData = acf_get_attachment( $id ) ) )  {

    $size = $size === 'full' ? '' : $size . '-';
    $sizes = empty( $size ) ? $imageData : $imageData[ 'sizes' ];
    $url = empty( $size ) ? $imageData['url'] : $sizes[ trim( $size , '-' ) ];

    ?>

    <img <?php if( $parallax) {  ?>data-parallax<?php } ?> <?php echo $attrs; ?>
         alt="<?php echo esc_attr( false !== $alt ? $alt : $imageData[ 'title' ] ); ?>"
         width="<?php echo $sizes[ $size . 'width' ]; ?>"
         height="<?php echo $sizes[ $size . 'height' ]; ?>"
        <?php if( $lazy ) { ?>
            data-lazy
            src="<?php echo com\theme::blank_image( $sizes[ $size . 'width' ], $sizes[ $size . 'height' ] ) ?>"
            data-src="<?php echo $url; ?>" class="opacity-0 origin-center transition-opacity duration-[1200ms] ease-in-out [&.loaded]:opacity-100  <?php echo trim( $classes . ' ' . $additionalClasses ) ; ?>"
        <?php } else { ?>
            src="<?php echo $url; ?>"  class="<?php echo trim( $classes . ' ' . $additionalClasses ) ; ?>"
        <?php } ?>
    >

    <?php
}
