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
    'searchField' => true,
    'selectAttrs' => '',
    'variant' => 'default',

] ));


$rules = [];
if( ! empty( $required ) ) $rules[]= 'required';
if( ! empty( $validate ) ) $rules[]= $validate;
$rules = implode( '|', $rules );

$value = (array) $value;
if( ! empty( $values ) ){
    $values = apply_filters( 'fbs-form-field-values', $values, false,  [ 'name' => $name ]);
}

$is_dark_outline = $variant === 'dark-outline';

$label_class = $is_dark_outline
    ? 'text-[1.2rem] leading-none inline-block mb-15 transition-all duration-300 font-normal tracking-[.04em] group-[.field.error]:text-error'
    : 'text-12 leading-[1.33333rem] inline-block mb-10 transition-all duration-300 font-bold group-[.field.error]:text-error';
$control_class = $is_dark_outline
    ? 'min-h-65 text-[2rem] text-ochre'
    : 'min-h-[50px] md:min-h-[7.6rem] text-paragraph text-gray-dark';
$background_class = $is_dark_outline
    ? 'rounded-[.8rem] border border-white bg-blue transition-colors group-[.field.error]:border-error'
    : 'border-2 border-green bg-white transition group-[.focus]:border-green group-[.field.error]:border-error group-[.focus]:shadow-[0px_0px_40px_1px_rgba(0,0,0,0.15)]';
$dropdown_class = $is_dark_outline
    ? 'text-[2rem] text-ochre'
    : 'text-paragraph';
$options_class = $is_dark_outline
    ? 'mx-px overflow-y-hidden border-t border-white/30'
    : 'mx-[2px] overflow-y-hidden';
$option_class = $is_dark_outline
    ? 'border-t border-white/30 first:border-t-0'
    : '';
$option_inner_class = $is_dark_outline
    ? 'flex h-65 cursor-pointer items-center justify-between px-20 transition-colors duration-300 group-[.option.hovered]:bg-white/10 group-[.option.selected]:bg-white/15'
    : 'group-[.option.hovered]:bg-paper group-[.option.selected]:!bg-dove block px-[1.5rem] h-[4.8rem] leading-[40px] cursor-pointer transition duration-300 flex justify-between items-center';
$selection_class = $is_dark_outline
    ? 'h-65 px-20'
    : 'min-h-[4.8rem] px-[1.5rem]';
$selection_text_class = $is_dark_outline
    ? 'text-[2rem] leading-none'
    : 'text-14';
$arrow_class = $is_dark_outline
    ? 'size-[1.2rem] fill-ochre'
    : 'h-[12px] w-[12px] fill-grey-700';
$check_class = $is_dark_outline
    ? 'fill-ochre'
    : 'fill-black';


?>
<div class="group custom-select relative select-none test-green <?php echo $wrapperClass; ?>" data-form-control data-module-select-field <?php if( ! empty( $maxOptions ) ) echo 'data-max-options="' . $maxOptions . '"'; ?>>
    <label class="block relative w-full group field leading-none <?php echo $wrapperClass; ?>" <?php if( $rules ) echo 'data-module-validate data-rules="' . $rules . '"'; ?> >
        <?php get_template_part('templates/parts/form/_label', false, [ 'label' => $label, 'required' => $required, 'className' => $label_class ]); ?>

        <select name="<?php echo $name; ?>" class="hidden" <?php if( $multiple ) echo 'multiple' ?> data-validate="target" <?php echo $selectAttrs; ?>>
            <?php foreach ( $values as $key => $option ) {
                if( ! is_object( $option ) ){
                    $option = ( object ) [ 'value' => $key, 'selected' => false, 'label' => $option];
                }
                ?>
                <option value="<?php echo $option->value; ?>" <?php if( in_array($option->value, $value) || ! empty( $option->selected ) ) echo 'selected="selected"' ?> <?php if( ! empty( $option->attrs ) ) echo $option->attrs; ?>><?php echo $option->label; ?></option>
            <?php } ?>
        </select>

        <span class="relative block w-full outline-none <?php echo esc_attr( $control_class ); ?>">
            <span class="absolute left-0 top-0 h-full w-full <?php echo esc_attr( $background_class ); ?>" data-select-field="bg"></span>
            <span class="absolute left-0 top-full hidden w-full overflow-hidden transition group-[.custom-select.focus]:block <?php echo esc_attr( $dropdown_class ); ?>" data-select-field="dropdown">
                <?php if( $searchField ) { ?>
                    <span class="block px-20 mb-[20px] relative" data-select-field="input-container">
                    <span class="black relative">
                        <input autocomplete="search-<?php echo $name; if( count($values) > 1) echo '[]'; ?>"  type="text" class="w-full h-[4.8rem] bg-white pl-[4.8rem] pr-20 outline-none text-paragraph text-gray-dark placeholder:text-[#8A8A8A]  border border-black  focus:border-black" data-select-field="input">
                        <svg class="h-[1.5rem] w-[1.5rem] absolute left-[20px] top-1/2 -translate-y-1/2 pointer-events-none"><use xlink:href="#icon-search"></use></svg>
                    </span>
                </span>
                <?php } ?>
                <span class="block <?php echo esc_attr( $options_class ); ?>" data-select-field="options" data-module-scrollbar>
                    <?php foreach ( $values as $key => $option ) {
                        if( ! is_object( $option ) ){
                            $option = ( object ) [ 'value' => $key, 'selected' => false, 'label' => $option];
                        }
                        ?>
                        <span class="group option <?php echo esc_attr( $option_class ); ?> <?php if( in_array($option->value, $value) || ! empty( $option->selected ) ) echo 'selected' ?>" data-select-field="option" data-text="<?php echo $option->label; ?>" data-value="<?php echo $option->value ?>" <?php if( ! empty( $option->attrs ) ) echo $option->attrs; ?>>
                        <span class="<?php echo esc_attr( $option_inner_class ); ?>">
                            <?php echo $option->label; ?>
                            <svg class="h-[9px] w-[12px] opacity-0 transition group-[.option.selected]:opacity-100 <?php echo esc_attr( $check_class ); ?>"><use xlink:href="#icon-checkbox"></use></svg>
                        </span>
                    </span>
                    <?php } ?>
                </span>
            </span>
            <span class="relative flex cursor-pointer items-center justify-between overflow-hidden <?php echo esc_attr( $selection_class ); ?>" data-select-field="selection">
                <span class="flex flex-grow flex-wrap items-center truncate py-[5px] transition group-[.field.error]:text-error <?php echo esc_attr( $selection_text_class ); ?>" data-select-field="pills">
                    <span class="h-[25px] rounded-full bg-black flex items-center pr-[10px] text-white leading-none my-[5px] mr-[10px] hidden text-12 " data-select-field="pill">
                        <span class="px-[10px] flex items-center cursor-pointer fill-black transition duration-300 " data-pill-remove>
                            <svg class="h-[8px] w-[8px] fill-white transition" ><use xlink:href="#icon-select-pill-remove"></use></svg>
                        </span>
                        <span data-pill-text></span>
                    </span>
                    <span data-select-field="placeholder"><?php echo $placeholder; ?></span>
                </span>
                <svg class="shrink-0 transition group-[.custom-select.focus]:-rotate-180 group-[.field.error]:fill-error <?php echo esc_attr( $arrow_class ); ?>"><use xlink:href="#icon-select-arrow"></use></svg>
            </span>
        </span>

        <?php get_template_part('templates/parts/form/_description', false, [ 'description' => $description ]); ?>
        <span data-validate="message" class="hidden opacity-0 pt-[0.5rem] text-11 leading-none text-error transition duration-300 group-[.field.error]:block group-[.field.error]:opacity-100">&nbsp;</span>
    </label>
</div>
