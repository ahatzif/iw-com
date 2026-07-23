<?php
extract( wp_parse_args( $args, [
    'value' => '',
    'name' => 'field_name',
    'label' => 'FIELD LABEL',
    'required' => false,
    'validate' => false,
    'placeholder' => '&nbsp;',
    'accept' => ".pdf, .doc, .docx",
    'acceptLabel' => __('Supported Files: pdf, doc, docx', 'com-theme'),
    'multiple' => false
] ));
?>

<div>
    <label class="inline-block relative mb-25r group field" data-module-file-field data-module-validate <?php if( $validate ) echo 'data-rules="' . $validate . '"'; ?>>
        <input type="file" data-validate="target" name="<?php echo $name;?>" value="<?php echo $value; ?>" class="opacity-0 absolute w-0 h-0" accept="<?php echo $accept; ?>" <?php if( $multiple ) echo 'multiple'; ?>>
        <span class="inline-flex cursor-hover cursor-pointer">
            <span class="block transition duration-300 w-40 h-40 rounded-10 border border-1 border-black flex items-center justify-center group-[.field:hover]:border-black group-[.field:hover]:bg-black group-[.field:hover]:fill-white transition duration-300 group-[.field.error]:border-error group-[.field.error]:bg-error group-[.field.error]:fill-white">
                <svg class="w-20 h-20"><use xlink:href="#icon-attachment"></use></svg>
            </span>
            <span class="flex flex-col justify-center ml-[1.2rem] group-[.field.error]:text-error transition duration-300">
                <span class="block text-14 leading-none"><?php echo $label; if( $required ) { ?><span>*</span><?php } ?></span>
                <span class="block text-10 leading-none mt-[0.5rem]"><?php echo $acceptLabel; ?></span>
            </span>
        </span>
        <span class="flex flex-col items-start pt-10 space-y-[0.5rem] hidden" data-file-field="files"></span>
        <span data-validate="message" class="block opacity-0 pt-[0.5rem] text-11 leading-none text-error transition duration-300 group-[.field.error]:opacity-100">&nbsp;</span>
    </label>
</div>
