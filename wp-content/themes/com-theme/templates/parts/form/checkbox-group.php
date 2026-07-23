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
<div class="group field text-11 leading-[1.0909090909] <?php echo $wrapperClass; ?>" <?php if( $required ) echo 'data-module-validate data-rules="required"'; ?>>
    <?php get_template_part('templates/parts/form/_label', false, [ 'label' => $label, 'required' => $required, 'hideLabel' => $hideLabel ]); ?>
    <div class="flex <?php echo $inline ? '' : 'flex-col' ?> gap-y-10 gap-x-30">
        <?php foreach ( $values as $checkbox ) {
            $checkbox = (array) $checkbox;
            if( ! empty( $replacements ) ) {
                foreach ($replacements as $replacement) {
                    $checkbox['label'] = str_replace($replacement['replacement'], $replacement['text'], $checkbox['label']);
                }
                $checkbox['label'] = nl2br($checkbox['label']);
            }
            ?>
        <label class="flex select-none cursor-pointer relative before:block before:transition before:duration-300 before:w-[2rem] before:h-[2rem] before:border-[0.5px] before:border-black before:absolute before:top-0 before:left-0 group-[.field.error]:before:border-error">
            <input type="checkbox" class="hidden peer" data-validate="target"
                   <?php if( ! empty( $checkbox['selected'] ) ) echo 'checked="checked"' ?>
                   name="<?php echo $name; if( count($values) > 1) echo '[]'; ?>"
                   value="<?php echo $checkbox['value']; ?>">
            <span class="flex shrink-0 items-center justify-center w-[2rem] h-[2rem] mr-[1.2rem] opacity-0 transition duration-300 peer-checked:opacity-100">
                <svg class="w-full h-[0.8rem]"><use xlink:href="#icon-checkbox"></use></svg>
            </span>
            <span class="flex min-h-[2rem] items-center transition duration-300 group-[.field.error]:text-error"><?php echo com\theme::remove_accents($checkbox['label']); if( false && $required ) { ?> <span>*</span><?php } ?></span>
        </label>
        <?php } ?>
    </div>
    <?php get_template_part('templates/parts/form/_description', false, [ 'description' => $description ]); ?>
    <span data-validate="message" class="hidden mt-[0.5rem] text-11 text-error transition duration-300 group-[.field.error]:block"></span>
</div>

