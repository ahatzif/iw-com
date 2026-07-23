<?php
    $params = wp_parse_args( $args, ['label' => 'Field Label', 'name' => 'field_name','validate' => '']);
    extract($params);
?>

<label class="block mb-8">
    <input 
        <?php if( ! empty( $validate ) ) echo 'data-validate="' . $validate . '"'; ?>
        type="checkbox" 
        name="<?php echo $name; ?>"
        class="!shadow-none w-[30px] h-[30px]">
    <span>
        <?php echo $label; ?>
        <?php if( strpos( $validate, 'required') !== false ) { ?>
            <span class="text-red">*</span>
        <?php }?>
    </span>
    <span class="block">
        <span class="text-red text-[12px]" data-error-message></span>
    </span>
</label>