<?php // Title: Form
$action = 'fbs-form';
$formID = get_field('form');
$replacements = get_field('replacements', $formID);
$repeater = 'replacements';
$sub_fields = array('replacement', 'text',);
$replacements = array();
for ($i = 0; $i < intval(get_post_meta($formID, 'replacements', true)); $i++) {
    $row = array();
    foreach ($sub_fields as $sub_field) {
        $row[$sub_field] = get_post_meta($formID, $repeater . '_' . $i . '_' . $sub_field, true);
    }
    $replacements[] = $row;
}
$hasFormMessages = false;
if (!empty($formID)) {
    $formConfig = get_post_meta($formID, '_form_config', true);
    $formConfig = json_decode($formConfig); ?>
    <section class="<?php echo esc_attr( com_theme_block_style_classes( 'text-blue', [ 'desktop' => [ 'mt' => '80', 'mb' => '80' ] ] ) ); ?>">
    <div class="<?php echo esc_attr( com_theme_block_wrapper_classes() ); ?>">
        <div class="flex flex-gap-20 flex-wrap">
            <div class="w-full  md:w-10/12 lg:w-5/12 md:ml-1/12 space-y-40 mb-50 lg:pr-[4.3rem] prose">
                <?php if (!empty($title = get_field('title'))) { ?>
                    <div class="text-m-text text-current"><?php echo $title; ?></div>
                <?php } ?>
                <?php if (!empty($text = get_field('text'))) { ?>
                    <div class="text-s-text-regular text-current prose-p:mb-[1.6rem] prose-strong:text-current prose-strong:font-bold prose-a:text-current prose-a:underline"><?php echo $text; ?></div>
                <?php } ?>
            </div>
            <form action="<?php echo admin_url('admin-ajax.php'); ?>" data-module-form enctype="multipart/form-data" class="w-full md:w-10/12 md:ml-1/12 lg:ml-0 lg:w-5/12 xl:pl-[6.3rem] group form peer [&.loading]:cursor-wait"  data-reset-on-success="true">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <input type="hidden" name="form-id" value="<?php echo $formID; ?>">
                <input type="hidden" name="<?php echo $action; ?>-nonce-<?php echo $formID; ?>" value="<?php echo wp_create_nonce($action . "-nonce-" . $formID) ?>">
                <div class="grid gap-[1.3rem] group-[.loading]:pointer-events-none group-[&_input]:rounded-full :rounded-full group-[&_textarea]:rounded-[4rem] group-[.form.error]:hidden group-[.form.success]:hidden">
                    <?php foreach ($formConfig as $fieldsRow) {
                        if ($fieldsRow->type === 'form-messages') $hasFormMessages = true; ?>
                        <?php $fieldsRow->replacements = $replacements; ?>
                        <?php get_template_part('templates/parts/form/'  . $fieldsRow->type, false, $fieldsRow); ?>
                    <?php } ?>
                    <div class="place-self-end pt-[2.7rem]">
                        <?php get_template_part('templates/parts/button', false, [
                            'tag' => 'button',
                            'attrs' => 'type="submit"',
                            'size' => 'large',
                            'text' => __('ΑΠΟΣΤΟΛΗ', 'com-theme'),
                        ]) ?>
                    </div>
                    <?php if( get_field('cloudflare_turnstile', $formID) ) { ?>
                        <div id="turnstile-container-<?php echo $formID; ?>" data-form="turnstile"></div>
                    <?php } ?>
                </div>

                <?php if (!$hasFormMessages) {
                    get_template_part('templates/parts/form/form-messages');
                } ?>
            </form>
        </div>
    </div>
    </section>
<?php }
