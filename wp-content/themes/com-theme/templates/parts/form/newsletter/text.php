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

<label class="block w-full relative group field leading-[1]  <?php echo $wrapperClass; ?>" <?php if( $rules ) echo 'data-module-validate data-rules="' . $rules . '"'; ?>>
    <?php get_template_part('templates/parts/form/_label', false, [ 'label' => $label, 'required' => $required, 'hideLabel' => $hideLabel ]); ?>

    <span class="relative h-60 block">
        <input data-validate="target"  name="<?php echo $name;?>"  type="<?php echo $subtype;?>"  value="<?php echo $value; ?>"  placeholder="<?php echo $placeholder; ?>"
               <?php if( ! empty( $maxlength) ) { ?>
                   maxlength="<?php echo $maxlength; ?>"
               <?php } ?>
            <?php if( $subtype === 'password' ) { ?>
                autocomplete="<?php echo $name;?>"
            <?php } ?>
               class="w-full block h-full rounded-full border border-current transition duration-300 text-current bg-transparent outline-0 px-30 text-[14px] leading-none placeholder-black
               peer group-[.field.error]:text-error group-[.field.error]:border-error [&:focus]:border-[1px] group-[.field.error]:placeholder-error" />

        <?php if( $name === 'email_address' ) { ?>
        <button type="submit" class="absolute h-full w-70 rounded-full right-0 top-0 flex items-center justify-center cursor-pointer bg-current fill-black">
            <svg class="w-[1.8rem] h-[1.7rem]" width="18" height="17" viewBox="0 0 18 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.45099 16.5774L7.55611 14.6988L12.5302 9.72474H0.875V6.96409H12.5302L7.55611 1.99818L9.45099 0.111461L17.6839 8.34442L9.45099 16.5774Z" fill="#244C2F"/></svg>
        </button>
        <?php } ?>
    </span>


    <?php get_template_part('templates/parts/form/_description', false, [ 'description' => $description ]); ?>
    <span data-validate="message" class="hidden mt-[1rem] text-[1.1rem] text-error transition duration-300 group-[.field.error]:block"></span>
</label>
