<?php

/**
 * Title: Post Content
 * @see wp-content/themes/com-theme/acf-json/post-content_66fc1c1bd11f5.json ACF Fields
 * @see  JS Module File
 *
 **/
?>
<?php if (! empty($text = get_field('text'))) { ?>
  <section class="<?php echo esc_attr( com_theme_block_style_classes() ); ?>">
    <div class="<?php echo esc_attr( com_theme_block_wrapper_classes( 'mx-auto w-full px-1/12 md:px-1/24 lg:px-2/24' ) ); ?>">
        <div class="<?php echo get_prose(); ?>"><?php echo $text; ?></div>
    </div>
  </section>
<?php } ?>
