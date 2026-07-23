<?php
extract( wp_parse_args( $args, [
    'description' => '',
] ));
if( ! empty ( $description ) ){
    ?>
    <span class="block pt-[0.5rem] text-11 leading-none"><?php echo $description; ?></span>
<?php }
