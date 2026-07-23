<?php
    extract( wp_parse_args( $args, [
        'required' => false,
        'label' => '',
        'placeholder' => '&nbsp;',
        'className' => '',
        'name' => 'field_name',
        'value' => '',
        'wrapperClass' => '',
        'validate' => false,
        'height' => 'h-[11.1rem]',
        'description' => '',
        'maxlength' => false,
        'rows' => false,
        'hideLabel' => false,
    ] ));

    $rules = [];
    if( ! empty( $required ) ) $rules[]= 'required';
    if( ! empty( $validate ) ) $rules[]= $validate;
    $rules = implode( '|', $rules );

?>
<div class="group field <?php echo $wrapperClass; ?>" <?php if( $rules ) echo 'data-module-validate data-rules="' . $rules . '"'; ?>>
    <label class="block w-full relative leading-none">
        <?php get_template_part('templates/parts/form/_label', false, [ 'label' => $label, 'required' => $required, 'hideLabel' => $hideLabel ]); ?>
        <textarea data-validate="target"
                  name="<?php echo $name;?>"
                  placeholder="<?php echo $placeholder; ?>"
                  <?php if( ! empty( $maxlength) ) { ?>
                      maxlength="<?php echo $maxlength; ?>"
                  <?php } ?>
                  <?php if( ! empty( $rows) ) { ?>
                      rows="<?php echo $rows; ?>"
                  <?php } ?>
                  class="peer w-full block <?php echo $height; ?>  border-[0.5px] [&:focus]:border-[1px] border-black transition duration-300 text-black bg-white outline-0 pl-[1.5rem] pt-[2rem] group-[.field.error]:text-error group-[.field.error]:border-error"><?php echo $value; ?></textarea>


        <!--<span class="md:transition-all md:duration-300
                        peer-focus:pl-[0.8rem]
                        peer-focus:text-12
                        peer-focus:-translate-y-full
                        peer-[:not(:placeholder-shown)]:pl-[0.8rem]
                        peer-[:not(:placeholder-shown)]:text-12
                        peer-[:not(:placeholder-shown)]:-translate-y-full
                        absolute top-0 left-0 pl-[1.5rem] translate-y-[2rem] text-16i leading-[2.3rem] group-[.field.error]:text-error"><?php /*echo $label; if( $required ) { */?><span>*</span><?php /*} */?></span>-->

        <?php get_template_part('templates/parts/form/_description', false, [ 'description' => $description ]); ?>
        <span data-validate="message" class="hidden opacity-0 pt-[0.5rem] text-11 leading-none text-error transition duration-300 group-[.field.error]:block group-[.field.error]:opacity-100">&nbsp;</span>
    </label>


</div>
