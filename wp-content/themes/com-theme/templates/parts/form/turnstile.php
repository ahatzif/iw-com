<?php
extract( wp_parse_args( $args, [ 'wrapperClass' => 'col-span-2' ] ));

$formID = get_field( "form" );
if( ! empty( $siteKey = get_option('iw_form_submissions_turnstile_site_key')  ) ) { ?>
    <div class="<?php echo $wrapperClass; ?>">
        <div class="flex justify-end mix-blend-multiply">
            <div id="turnstile-container-<?php echo $formID; ?>" data-form="turnstile" data-site-key="<?php echo $siteKey ?>"></div>
        </div>
    </div>
<?php } ?>
