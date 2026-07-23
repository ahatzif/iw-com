<?php

/**
 * Title: Post Content
 * @see wp-content/themes/com-theme/acf-json/post-content_66fc1c1bd11f5.json ACF Fields
 * @see  JS Module File
 *
 **/
?>
<?php if (! empty($text = get_field('text'))) { ?>
  <section>
    <div class="<?php echo get_prose(); ?>"><?php echo $text; ?></div>
  </section>
<?php } ?>