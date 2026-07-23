<?php
extract( wp_parse_args( $args, [ 'subtype' => 'h2', 'label' => '', 'wrapperClass' => '', 'className' => ''] ) );
if( $subtype === 'h1' ) $subtype = 'h2';
if( ! empty( $label ) ){
?>
<div class="<?php echo get_prose(); ?> <?php echo $wrapperClass ?>">
    <<?php echo $subtype; ?> class="<?php echo $className?>"><?php echo $label; ?></<?php echo $subtype; ?>>
</div>
<?php }


