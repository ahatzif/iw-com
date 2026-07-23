<?php
extract( wp_parse_args( $args, [ 'title' => false, 'url' => false] ) );
if( ! empty( $title ) && ! empty( $url ) ){
?>
<a href="<?php echo $url; ?>"><?php echo mb_strtolower( $title ); ?></a>
<?php } ?>
