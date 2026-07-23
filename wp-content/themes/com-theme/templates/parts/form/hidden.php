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
    ] ));
?>
<input name="<?php echo $name;?>" type="hidden" value="<?php echo $value; ?>" />


