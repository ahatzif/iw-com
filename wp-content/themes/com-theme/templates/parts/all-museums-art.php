<?php
$label_classes = (string) ( $args['label_classes'] ?? 'absolute left-[3.5rem] top-[4.2rem] text-[5.2rem] font-light leading-[.895] text-ochre-light lg:text-[7.6rem]' );
?>
<span class="absolute inset-0 bg-blue" aria-hidden="true"></span>
<svg class="absolute inset-0 size-full" aria-hidden="true"><use xlink:href="#icon-com-all-museums-mask"></use></svg>
<span class="<?php echo esc_attr( $label_classes ); ?>"><?php echo nl2br( esc_html( __( "ΜΟΥ\nΣΕΙΑ", 'com-theme' ) ) ); ?></span>
