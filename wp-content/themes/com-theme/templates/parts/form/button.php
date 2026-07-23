<?php

extract( wp_parse_args( $args, [
    'label' => 'FIELD LABEL',
    'className' => '',
    'name' => 'field_name',
    'value' => '',
    'wrapperClass' => '',
    'validate' => false,
    'height' => 'h-[11.1rem]'
] ));

?>
<div class="flex justify-end">
<?php  get_template_part('templates/parts/button', false, ['tag' => 'button', 'attrs' => 'type="submit"', 'text' => $label]);?>
</div>
<?php


