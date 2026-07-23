<?php

get_header();

IW_Custom_Auth::get_part('form-section-start' );

IW_Custom_Auth::get_part('form-header', [
    'title' => get_the_title(),
    'description' => apply_filters( 'the_content', get_the_content() ),
]);


ob_start(); ?>
<a href="<?php echo get_permalink( iw_get_user_page( 'login' ) ) ?>" class="font-bold"><?php _e( 'Login to your account', 'iw-theme') ?></a>
<?php
$html = ob_get_contents();
ob_end_clean();

IW_Custom_Auth::get_part('form-end', [ 'html' => $html]);

IW_Custom_Auth::get_part('form-section-end' );

get_footer();
