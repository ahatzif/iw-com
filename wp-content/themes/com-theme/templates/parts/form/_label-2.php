<?php
extract( wp_parse_args( $args, [
    'required' => false,
    'label' => 'FIELD LABEL',
    'hideLabel' => false,
] ));
if ( ! empty ( $label ) && ! $hideLabel ){

    //

    /*
    */

?>


    <span class="

                transition-all duration-300

                absolute top-0 left-0 w-full

                peer-focus:pl-0
                peer-[:not(:placeholder-shown)]:pl-0
                peer-[:not(:placeholder-shown)]:text-H10-Regular
                peer-focus:translate-none peer-[:not(:placeholder-shown)]:translate-none peer-focus:text-H10-Regular

                peer-[:not(:placeholder-shown)]:h-[1.8rem]
                peer-focus:h-[1.8rem]


                text-[1.6rem] leading-[2.3rem] group-[.field.error]:text-error
                order-first
                translate-y-[2.8rem] translate-x-[1.5rem]
                 h-[4.8rem] flex items-center



                text-current




            ">
                <span class="block"><?php echo com\theme::remove_accents( $label ); if( $required ) { ?> <span>*</span><?php } ?></span>
        </span>

<?php }
