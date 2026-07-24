<?php // Title: Cookiebot Declaration ?>
<section class="<?php echo esc_attr( com_theme_block_style_classes() ); ?>">
<div class="<?php echo esc_attr( com_theme_block_wrapper_classes() ); ?>">
    <div class="mx-1/12 md:mx-2/12 md:w-8/12 <?php echo get_prose(); ?>">
        <div data-module-cookie-declaration <?php if( defined( 'ICL_LANGUAGE_CODE') ) {
            echo 'data-culture="' . ICL_LANGUAGE_CODE . '"';
        } ?>></div>
    </div>
</div>
</section>
