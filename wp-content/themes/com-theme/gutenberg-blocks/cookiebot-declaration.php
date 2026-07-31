<?php // Title: Cookiebot Declaration

$section_classes = trim( com_theme_block_wrapper_classes( 'mx-auto w-full px-1/12 md:px-1/24 lg:px-2/24' ) . ' ' . com_theme_block_style_classes() );
$culture = defined( 'ICL_LANGUAGE_CODE' ) ? strtoupper( (string) ICL_LANGUAGE_CODE ) : '';
?>
<section class="<?= esc_attr( $section_classes ) ?>">
    <div class="grid min-w-0 lg:grid-cols-12">
        <div class="min-w-0 lg:col-span-8 lg:col-start-5">
            <div
                class="<?= esc_attr( com_theme_legal_content_classes() ) ?>"
                data-module-cookie-declaration
                data-wysiwyg
                <?php if ( $culture !== '' ) : ?>data-culture="<?= esc_attr( $culture ) ?>"<?php endif; ?>
            ></div>
        </div>
    </div>
</section>
