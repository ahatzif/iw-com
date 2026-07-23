<?php
extract( wp_parse_args( $args, [
    'required' => false,
    'label' => '',
    'placeholder' => '&nbsp;',
    'subtype' => 'text',
    'className' => '',
    'name' => 'field_name',
    'value' => false,
    'wrapperClass' => '',
    'validate' => false,
    'description' => '',


    'values' => [],
    'maxOptions' => 6,
    'multiple' => false,
    'searchField' => true

] ));


$rules = [];
if( ! empty( $required ) ) $rules[]= 'required';
if( ! empty( $validate ) ) $rules[]= $validate;
$rules = implode( '|', $rules );

$value = (array) $value;
if( ! empty( $values ) ){
    $values = apply_filters( 'fbs-form-field-values', $values, false,  [ 'name' => $name ]);
}



?>
<div class="group custom-select relative select-none test-green <?php echo $wrapperClass; ?>" data-form-control data-module-select-field <?php if( ! empty( $maxOptions ) ) echo 'data-max-options="' . $maxOptions . '"'; ?>>
    <label class="block relative w-full group field leading-none <?php echo $wrapperClass; ?>" <?php if( $rules ) echo 'data-module-validate data-rules="' . $rules . '"'; ?> >
        <?php get_template_part('templates/parts/form/_label', false, [ 'label' => $label, 'required' => $required ]); ?>

        <select name="<?php echo $name; ?>" class="hidden" <?php if( $multiple ) echo 'multiple' ?> data-validate="target">
            <?php foreach ( $values as $key => $option ) {
                if( ! is_object( $option ) ){
                    $option = ( object ) [ 'value' => $key, 'selected' => false, 'label' => $option];
                }
                ?>
                <option value="<?php echo $option->value; ?>" <?php if( in_array($option->value, $value) || $option->selected ) echo 'selected="selected"' ?>><?php echo $option->label; ?></option>
            <?php } ?>
        </select>

        <span class="block relative w-full outline-none min-h-[50px] md:min-h-[7.6rem] text-paragraph text-gray-dark ">
            <span class="absolute top-0 left-0 w-full h-full bg-white border-2 group-[.focus]:border-green border-green transition group-[.field.error]:border-error group-[.focus]:shadow-[0px_0px_40px_1px_rgba(0,0,0,0.15)]" data-select-field="bg"></span>
            <span class="hidden group-[.custom-select.focus]:block absolute left-0 top-full w-full text-paragraph overflow-hidden transition"  data-select-field="dropdown">
                <?php if( $searchField ) { ?>
                    <span class="block px-20 mb-[20px] relative" data-select-field="input-container">
                    <span class="black relative">
                        <input autocomplete="search-<?php echo $name; if( count($values) > 1) echo '[]'; ?>"  type="text" class="w-full h-[4.8rem] bg-white pl-[4.8rem] pr-20 outline-none text-paragraph text-gray-dark placeholder:text-[#8A8A8A]  border border-black  focus:border-black" data-select-field="input">
                        <svg class="h-[1.5rem] w-[1.5rem] absolute left-[20px] top-1/2 -translate-y-1/2 pointer-events-none"><use xlink:href="#icon-search"></use></svg>
                    </span>
                </span>
                <?php } ?>
                <span class="block overflow-y-hidden mx-[2px]" data-select-field="options" data-module-scrollbar>
                    <?php foreach ( $values as $key => $option ) {
                        if( ! is_object( $option ) ){
                            $option = ( object ) [ 'value' => $key, 'selected' => false, 'label' => $option];
                        }
                        ?>
                        <span class="group option <?php if( in_array($option->value, $value) || $option->selected ) echo 'selected' ?>" data-select-field="option" data-text="<?php echo $option->label; ?>" data-value="<?php echo $option->value ?>">
                        <span class="group-[.option.hovered]:bg-paper group-[.option.selected]:!bg-dove block px-[1.5rem] h-[4.8rem] leading-[40px] cursor-pointer transition duration-300 flex justify-between items-center" >
                            <?php echo $option->label; ?>
                            <svg class="w-[12px] h-[9px] fill-black transition opacity-0 group-[.option.selected]:opacity-100"><use xlink:href="#icon-checkbox"></use></svg>
                        </span>
                    </span>
                    <?php } ?>
                </span>
            </span>
            <span class="relative flex justify-between items-center overflow-hidden min-h-[4.8rem] md:px-[1.5rem] px-[1.5rem] cursor-pointer" data-select-field="selection">
                <span class="py-[5px] truncate transition group-[.field.error]:text-error flex-grow flex flex-wrap items-center text-14" data-select-field="pills">
                    <span class="h-[25px] rounded-full bg-black flex items-center pr-[10px] text-white leading-none my-[5px] mr-[10px] hidden text-12 " data-select-field="pill">
                        <span class="px-[10px] flex items-center cursor-pointer fill-black transition duration-300 " data-pill-remove>
                            <svg class="h-[8px] w-[8px] fill-white transition" ><use xlink:href="#icon-select-pill-remove"></use></svg>
                        </span>
                        <span data-pill-text></span>
                    </span>
                    <span data-select-field="placeholder"><?php echo $placeholder; ?></span>
                </span>
                <svg class="w-[12px] h-[12px] fill-grey-700 transition group-[.custom-select.focus]:-rotate-180 shrink-0 group-[.field.error]:fill-error"><use xlink:href="#icon-select-arrow"></use></svg>
            </span>
        </span>

        <?php get_template_part('templates/parts/form/_description', false, [ 'description' => $description ]); ?>
        <span data-validate="message" class="hidden opacity-0 pt-[0.5rem] text-11 leading-none text-error transition duration-300 group-[.field.error]:block group-[.field.error]:opacity-100">&nbsp;</span>
    </label>
</div>
