<?php
extract( wp_parse_args( $args, [ 'html' => '', 'label' => '', 'wrapperClass' => '', 'className' => ''] ) );
$tailwind = "text-right";
if( ! empty( $html ) ){
?>
<div><?php echo $html ?></div>
<?php }


