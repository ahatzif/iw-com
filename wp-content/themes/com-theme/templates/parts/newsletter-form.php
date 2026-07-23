<?php // Title: Form

$action = 'fbs-form';
$formID = get_field( 'newsletter_form', 'options' );

$replacements = [ [ "replacement" => "{{terms-of-use}}", "text" => com\theme::load_template_part( "templates/parts/terms-of-use" ) ] ];

if( ! empty( $formID ) ) {
    $formConfig = get_post_meta( $formID, '_form_config', true);
    $formConfig = json_decode( $formConfig );
    ?>

    <div class="space-y-10">
        <div class="text-[18px] font-heading font-bold"><?php _e('Subscribe to our newsletter', 'com-theme') ?></div>
        <?php $hasFormMessages = false; ?>
        <form action="<?php echo admin_url( 'admin-ajax.php' ); ?>" data-module-form enctype="multipart/form-data" class="group form peer [&.loading]:cursor-wait">
            <input type="hidden" name="action" value="<?php echo $action;?>">
            <input type="hidden" name="form-id" value="<?php echo $formID; ?>">
            <input type="hidden" name="<?php echo $action;?>-nonce-<?php echo $formID;?>" value="<?php echo wp_create_nonce( $action . "-nonce-" . $formID ) ?>">
            <div class="grid gap-10 group-[.loading]:pointer-events-none group-[.form.error]:hidden group-[.form.success]:hidden">
                <?php foreach ( $formConfig as $fieldsRow ) {
                    if( $fieldsRow->type === 'form-messages' ) $hasFormMessages = true;
                    $fieldsRow->replacements = $replacements;
                    get_template_part( 'templates/parts/form/newsletter/'  . $fieldsRow->type , false, $fieldsRow  );
                } ?>
            </div>
            <?php if( ! $hasFormMessages ) {
                ?>
                <div class="hidden group-[.form.error]:block group-[.form.success]:block" data-form="messages">
                    <div class="hidden group-[.form.success]:block space-y-10">
                        <div class="bold text-paragraph"><?php _e('Επιτυχής Εγγραφή! ', 'com-theme'); ?></div>
                        <div><?php _e('Έλεγξε το email σου και ακολούθησε τις οδηγίες για να ολοκληρώσεις την εγγραφή σου ', 'com-theme'); ?></div>
                    </div>
                    <div class="text-error hidden group-[.form.error]:block space-y-10"> Error: <div class="mt-10 text-12" data-form="error-message"></div></div>
                </div>

                <?php
            } ?>
        </form>
    </div>


<?php }
