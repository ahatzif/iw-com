<?php
extract( wp_parse_args( $args, [
    'value' => '',
    'name' => 'field_name',
    'label' => 'FIELD LABEL',
    'required' => false,
    'validate' => false,
    'placeholder' => '&nbsp;',
] ));
?>
<div class="group field" data-module-validate <?php if( $validate ) echo 'data-rules="' . $validate . '"'; ?>>
    <label class="inline-flex select-none cursor-pointer items-center relative before:block before:w-[2.4rem] before:h-[2.4rem] before:rounded-[0.6rem] before:border before:border-black before:absolute before:top-0 before:left-0 group-[.field.error]:before:border-error">
        <input type="checkbox" class="hidden peer" data-validate="target" name="<?php echo $name;?>" value="<?php echo $value; ?>">
        <span class="flex items-center justify-center w-[2.4rem] h-[2.4rem] mr-[1.2rem] opacity-0 transition duration-300 peer-checked:opacity-100">
        <svg class="w-[1.5rem] h-[1.5rem] transition duration-300"><use xlink:href="#icon-checkbox"></use></svg>
    </span>
        <span class="block text-12 leading-[1.3333333333] transition duration-300 group-[.field.error]:text-error"><?php echo $label; if( $required ) { ?><span>*</span><?php } ?></span>
    </label>
    <span data-validate="message" class="hidden mt-[0.5rem] text-11 text-error transition duration-300 group-[.field.error]:block"></span>
</div>

