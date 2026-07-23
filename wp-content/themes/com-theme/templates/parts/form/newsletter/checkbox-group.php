<?php
extract( wp_parse_args( $args, [
    'required' => false,
    'label' => '',
    'placeholder' => '&nbsp;',
    'className' => '',
    'name' => 'field_name',
    'values' => [
        [
            'value' => 'option-1',
            'label' => 'Label',
            'selected' => false
        ]
    ],
    'wrapperClass' => '',
    'selected' => false,
    'inline' => false,
    'description' => '',
    'hideLabel' => false,
    'replacements' => []
] ));
?>
<div class="group field text-[12px] leading-[1.0909090909] <?php echo $wrapperClass; ?>" <?php if( $required ) echo 'data-module-validate data-rules="required"'; ?>>
    <?php get_template_part('templates/parts/form/_label', false, [ 'label' => $label, 'required' => $required, 'hideLabel' => $hideLabel ]); ?>
    <div class="flex <?php echo $inline ? '' : 'flex-col' ?> gap-y-10 gap-x-30">
        <?php foreach ( $values as $checkbox ) { $checkbox = (array) $checkbox;
            if ( ! empty( $replacements ) ) {
                foreach ( $replacements as $replacement ) {
                    $checkbox['label'] = str_replace( $replacement['replacement'], $replacement['text'], $checkbox['label'] );
                }
                $checkbox['label'] = nl2br( $checkbox['label'] );
            }
            ?>
        <label class="flex select-none cursor-pointer relative">
            <span class="block transition duration-300 w-[2rem] h-[2rem] rounded-full border border-current group-[.field.error]:border-error flex items-center justify-center ">
                <input type="checkbox" class="hidden peer" data-validate="target" <?php if( ! empty( $checkbox['selected'] ) ) echo 'checked="checked"' ?> name="<?php echo $name; if( count($values) > 1) echo '[]'; ?>" value="<?php echo $checkbox['value']; ?>">
                <svg class="w-[2rem] h-[0.8rem] fill-current opacity-0 transition duration-300 peer-checked:opacity-100"><use xlink:href="#icon-checkbox"></use></svg>
            </span>
            <span class="min-h-[2rem] items-center transition duration-300 group-[.field.error]:text-error pt-[0.5rem] pl-[0.5rem]"><?php echo $checkbox['label']; if( false && $required ) { ?> <span>*</span><?php } ?></span>
        </label>
        <?php } ?>
    </div>
    <?php get_template_part('templates/parts/form/_description', false, [ 'description' => $description ]); ?>
    <span data-validate="message" class="hidden mt-[1rem] text-[1.1rem] text-error transition duration-300 group-[.field.error]:block"></span>
</div>

