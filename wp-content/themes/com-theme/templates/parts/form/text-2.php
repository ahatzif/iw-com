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

    if( ! in_array( $subtype, [ 'text', 'password' ] ) ){ $subtype = 'text'; }
?>

<label class="relative block w-full group field text-blue  group-[.field.error]:blockleading-[1] <?php echo $wrapperClass; ?>" <?php if( $rules ) echo 'data-module-validate data-rules="' . $rules . '"'; ?>>
    <span class="block relative pt-[2.8rem]">
        <input type="<?php echo $subtype; ?>" name="<?php echo $name; ?>" value="<?php echo $value; ?>" placeholder="<?php echo $placeholder; ?>" <?php if ( ! empty( $maxlength ) ) { ?> maxlength="<?php echo $maxlength; ?>" <?php } ?> <?php if ( $subtype === 'password' ) { ?> autocomplete="<?php echo $name; ?>" <?php } ?> class="rounded-10 bg-white peer group-[.field.error]:text-error group-[.field.error]:border-error block h-[4.8rem] w-full border-b border-blue pl-10 text-[1.6rem] outline-0 transition-all duration-300" data-validate="target" />
        <?php get_template_part( 'templates/parts/form/_label', false, [ 'label' => $label, 'required' => $required, 'hideLabel' => $hideLabel ] ); ?>
    </span>
        <?php if ( $subtype === 'password' ) { ?>
			<span class="absolute top-0 right-0 px-25 h-full flex items-center cursor-pointer select-none transition-all duration-300 group-[.field.error]:text-error" data-module-password-toggle>
				<svg class="fill-current w-[1.77rem] h-[1.53rem] opacity-0 group-[.field.show-password]:opacity-100"><use xlink:href="#icon-hide-password"></use></svg>
				<svg class="fill-current w-[1.72rem] h-[1.38rem] absolute top-1/2 -translate-y-1/2 left-1/2 -translate-x-1/2 group-[.field.show-password]:opacity-0"><use xlink:href="#icon-show-password"></use></svg>
			</span>
		<?php } else { ?>
			<svg class="size-[1.7rem] opacity-0 pointer-events-none group-[.field.error]:opacity-100 absolute right-25 top-1/2 -translate-y-1/2 fill-error"><use xlink:href="#icon-close-form-field"></use></svg>
		<?php } ?>
    <?php get_template_part('templates/parts/form/_description', false, [ 'description' => $description ]); ?>
    <span class="block opacity-0 pt-10 text-H11 text-error transition-all duration-300  group-[.field.error]:block group-[.field.error]:opacity-100" data-validate="message">&nbsp;</span>
</label>
