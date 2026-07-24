<?php // Title: Contact Form ?>


<section class="<?php echo esc_attr( com_theme_block_style_classes() ); ?>">
<div class="px-page-padding">
    <div class="flex flex-gap-20">
        <div class="md:mx-2/12 pb-[65px] md:pb-160r">
            <form action="<?php echo admin_url( 'admin-ajax.php' ); ?>" data-module-form enctype="multipart/form-data" class="group form [&.loading]:cursor-wait">
                <input type="hidden" name="action" value="contact-form">
                <input type="hidden" name="contact-form-nonce" value="<?php echo wp_create_nonce( "contact-form-nonce" ) ?>">
                <div class="group-[.loading]:pointer-events-none">
                    <div class="flex flex-wrap flex-gap-20">
                        <div class="w-full md:w-1/2">
                            <?php get_template_part('templates/parts/form/input', false, [ 'name' => 'name', 'label' => __( 'Name', 'com-theme' ), 'required' => true, 'validate' => 'required' ] ); ?>
                        </div>
                        <div class="w-full md:w-1/2">
                            <?php get_template_part('templates/parts/form/input', false, [ 'name' => 'email', 'label' => __( 'Email', 'com-theme' ), 'required' => true, 'validate' => 'required|email' ] ); ?>
                        </div>
                        <div class="w-full md:w-1/2">
                            <?php get_template_part('templates/parts/form/input', false, [ 'name' => 'company', 'label' => __( 'Company', 'com-theme' ) ] ); ?>
                            <?php get_template_part('templates/parts/form/input', false, [ 'name' => 'phone', 'label' => __( 'Phone', 'com-theme' ) ] ); ?>
                        </div>
                        <div class="w-full md:w-1/2">
                            <?php
                            $value = '';
                            $product = false;
                            if( isset( $_GET[ 'pr' ] ) ) {
                                $product = get_post((int) $_GET[ 'pr' ]);
                                if( $product && $product->post_type === 'product' ){
                                    $value = __( "I'm interested in the product ", 'com-theme' ) .$product->post_title;
                                }
                            } ?>
                            <?php get_template_part('templates/parts/form/textarea', false, [ 'height' => 'h-[calc(10.5rem+11px+25px)]', 'value' => $value ,'name' => 'message', 'label' => __( 'How can we help?', 'com-theme' ), 'required' => true, 'validate' => 'required' ] ); ?>
                        </div>
                    </div>


                    <?php if( empty( $product) ) { ?>
                    <?php get_template_part('templates/parts/form/file', false, [ 'name' => 'attachments[]', 'label' => __( 'Add Attachments', 'com-theme' ), 'multiple' => true, 'validate' => 'max_files:2' ] ); ?>
                    <?php } ?>

                    <div class="text-12 leading-[1.3333333333]">
                        <?php $privacyLink = get_field( 'privacy_policy_page', 'options' ); ?>
                        <?php echo sprintf( __('Asset Interiors will use the information you provide on this form to get in touch with you. We will treat your information with confidentiality and will not share it with others. For more information, visit our our <a href="%s" class="underline">Privacy Policy</a> page. By clicking below, you agree that we may process your information in accordance with these terms.', 'com-theme'), $privacyLink ); ?>
                    </div>

                    <div class="text-success mt-20r text-14 bg-success text-white leading-none p-20 rounded-10 hidden group-[.form.success]:block">
                        <div class="font-bold text-14"><?php _e('Your form was submitted successfully!', 'com-theme'); ?></div>
                        <div class="mt-[0.5rem]"><?php _e('Thank you for reaching out. We will get back to you as soon as possible.', 'com-theme'); ?></div>
                    </div>

                    <div class="text-success mt-20r text-14 bg-error text-white leading-none p-20 rounded-10 hidden group-[.form.error]:block">
                        <div class="font-bold text-14"><?php _e('There was a problem with your form submission', 'com-theme'); ?></div>
                        <div class="mt-[0.5rem]" data-form="error-message"></div>
                    </div>

                    <div class="md:flex justify-between mt-20r items-center">
                        <?php get_template_part('templates/parts/form/checkbox', false, [ 'name' => 'terms', 'label' => __( 'I agree with the above <a href="#">terms</a>', 'com-theme' ), 'required' => true, 'validate' => 'required' ] ); ?>
                        <div class="pt-[35px] md:pt-0">
                            <?php get_template_part('templates/parts/button', false, [ 'tag' => 'button', 'attrs' => 'type="submit"', 'text' => __( 'SUBMIT', 'com-theme') ]); ?>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
</section>
