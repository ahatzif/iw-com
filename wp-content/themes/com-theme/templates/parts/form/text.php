<?php
    extract( wp_parse_args( $args, [
        'required' => false,
        'label' => '',
        'placeholder' => '&nbsp;',
        'subtype' => 'text',
        'className' => '',
        'name' => 'field_name',
        'value' => '',
        'wrapperClass' => '',
        'validate' => false,
        'description' => '',
        'maxlength' => false,
        'hideLabel' => false,
    ] ));


    $rules = [];
    if( ! empty( $required ) ) $rules[] = 'required';
    if( $subtype === 'email' ) $rules[] = 'email';
    if( ! empty( $validate ) ) $rules[] = $validate;

    $rules = implode( '|', $rules );

    if( ! in_array( $subtype, [ 'text', 'password' ] ) ){
        $subtype = 'text';
    }



?>

<label class="block relative w-full relative group field leading-none <?php echo $wrapperClass; ?>" <?php if( $rules ) echo 'data-module-validate data-rules="' . $rules . '"'; ?>>
    <?php get_template_part('templates/parts/form/_label', false, [ 'label' => $label, 'required' => $required, 'hideLabel' => $hideLabel ]); ?>
    <input data-validate="target"
           name="<?php echo $name;?>"
           type="<?php echo $subtype;?>"
           value="<?php echo $value; ?>"
           placeholder="<?php echo $placeholder; ?>"
           <?php if( ! empty( $maxlength) ) { ?>
           maxlength="<?php echo $maxlength; ?>"
           <?php } ?>
           <?php if( $subtype === 'password' ) { ?>
           autocomplete="<?php echo $name;?>"
            <?php } ?>
           class="w-full block h-[4.8rem] rounded-10 border-[0.5px] border-black transition duration-300 text-black bg-white outline-0 pl-[1.5rem] text-16 md:text-14 leading-none
           peer group-[.field.error]:text-error group-[.field.error]:border-error [&:focus]:border-[1px]" />
    <?php get_template_part('templates/parts/form/_description', false, [ 'description' => $description ]); ?>
    <span data-validate="message" class="hidden opacity-0 pt-[0.5rem] text-11 leading-none text-error transition duration-300 group-[.field.error]:block group-[.field.error]:opacity-100">&nbsp;</span>


    <!--<span class="md:transition-all md:duration-300
                        peer-focus:pl-[0.8rem]
                        peer-focus:text-12
                        peer-focus:-translate-y-full
                        peer-[:not(:placeholder-shown)]:pl-[0.8rem]
                        peer-[:not(:placeholder-shown)]:text-12
                        peer-[:not(:placeholder-shown)]:-translate-y-full
                        absolute top-0 left-0 pl-[1.5rem] translate-y-[2rem] text-16i leading-[2.3rem] group-[.field.error]:text-error"><?php /*echo $label; if( $required ) { */?><span>*</span><?php /*} */?></span>-->



</label>
