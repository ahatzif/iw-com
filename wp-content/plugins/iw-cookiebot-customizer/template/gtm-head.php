<?php
if ( ! empty( $scriptId ) ) { ?>
        <!-- Google Tag Manager -->
        <script> window.cookiebotLang = '<?php echo apply_filters( 'wpml_current_language', null ); ?>'; </script>
        <script>
            window.CustomCookieConsentConfig = {
                logo : '<?php echo $logo; ?>',
                logoAlt : '<?php get_bloginfo('name'); ?>',
                onCheckboxLabel : '<?php _e( 'YES', 'custom-cookie-consent' );?>',
                offCheckBoxLabel : '<?php _e( 'NO', 'custom-cookie-consent' );?>',
                alwaysCheckBoxLabel : '<?php _e( 'NECESSARY', 'custom-cookie-consent' );?>',
            }
        </script>
        <link rel="stylesheet" href="<?php echo $pluginURL . 'assets/css/main.css'; ?>" />
        <script src="<?php echo $pluginURL . 'assets/js/main.js'; ?>"></script>
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','<?php echo $scriptId; ?>');</script>
        <!-- End Google Tag Manager -->
<?php }
