<?php
    $params = wp_parse_args( $args, [
        'label' => 'Field Label',
        'name' => 'field_name',
        'validate' => '',
        'instructions' => true,
        'showPass' => true
    ]);
    extract($params);
?>


<label class="block mb-8 ">
    <span class="block mb-4 font-bold uppercase">
        <?php echo $label; ?>
        <?php if( strpos( $validate, 'required') !== false ) { ?>
            <span class="text-red">*</span>
        <?php }?>
    </span>
    <span class="block relative">
        <input
        <?php if( ! empty( $validate ) ) echo 'data-validate="' . $validate . '"'; ?>
        type="password"
        name="<?php echo $name; ?>"
        class="form-input block w-full px-4 py-3  h-[6rem] text-16 focus:border-green focus:ring-0">
        <?php if(  $showPass ) { ?>
        <span class="absolute right-0 top-1/2 -translate-y-1/2 p-[2rem] text-[10px] font-bold cursor-pointer select-none" data-toggle-password>SHOW PASSWORD</span>
        <?php } ?>
    </span>
    <?php if ($instructions) { ?>
        <span class="block text-[12px] pt-[1rem]">
            <span class="font-bold"><?php _e('Password must', 'iw-theme');?></span>
            <span data-password-confirm="length"><?php _e( 'be at least 12 characters long', 'iw-theme'); ?></span>,
            <span data-password-confirm="uppercase"><?php _e( 'have at least one uppercase', 'iw-theme'); ?></span>,
            <span data-password-confirm="lowercase"><?php _e( 'have at least one lowercase', 'iw-theme'); ?></span>,
            <span data-password-confirm="number"><?php _e( 'have at least one number', 'iw-theme'); ?></span>,
            <span data-password-confirm="special"><?php _e( 'have at least one special character !@#$%^&*', 'iw-theme'); ?></span>.
        </span>
    <?php }?>
    <span class="text-red text-[12px]" data-error-message></span>
</label>
