<?php
    $params = wp_parse_args( $args, [
        'label' => 'Field Label', 
        'type' => 'text',
        'name' => 'field_name',
        'value' => '',
        'validate' => ''
    ]);
    extract($params);
?>


<label class="block mb-8">
    <span class="block mb-4 font-bold uppercase">
        <?php echo $label; ?>
        <?php if( strpos( $validate, 'required') !== false ) { ?>
            <span class="text-red">*</span>
        <?php }?>
    </span>
    <input 
        <?php if( ! empty( $validate ) ) echo 'data-validate="' . $validate . '"'; ?> 
        value="<?php echo $value;?>" 
        type="<?php echo $type; ?>" 
        name="<?php echo $name; ?>" 
        class="form-input block w-full px-4 py-3  h-[6rem] text-16 focus:border-green focus:ring-0">
    <span class="text-red text-[12px]" data-error-message></span>
</label>