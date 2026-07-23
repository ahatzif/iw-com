<?php
extract( wp_parse_args( $args, [ 'html' => '', 'label' => '', 'wrapperClass' => '', 'className' => ''] ) );

if( ! empty( $html ) ){
?>
<div class="<?php echo get_prose(); ?> <?php echo $wrapperClass ?>">
    <?php echo $html; ?>
</div>
<?php }


