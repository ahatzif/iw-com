<?php
extract( wp_parse_args( $args, [
    'required' => false,
    'label' => 'FIELD LABEL',
    'hideLabel' => false,
    'className' => 'text-12 leading-[1.33333rem] inline-block mb-10 transition-all duration-300 font-bold group-[.field.error]:text-error',
] ));
if( ! empty ( $label ) && ! $hideLabel ){
?>
<span class="<?php echo esc_attr( $className ); ?>"><?php echo com\theme::remove_accents( $label ); if( $required ) { ?> <span>*</span><?php } ?></span>
<?php }
