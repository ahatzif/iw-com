<?php
/**
 * Plugin Name:     IW Email Template
 * Plugin URI:      https://interweaveagency.com/
 * Description:     Alters wp_mail to send HTML email template to every email sent from Wordpress of plugins
 * Author:          Andreas Hatzifotis
 * Text Domain:     iw-email-template
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Iw_Email_Template
 */

require_once 'inc/fields.php';
require_once __DIR__ . '/inc/class-iw-email-template-email-previews.php';

class IW_Email_Template{

    private $requiredPlugins = [
        'advanced-custom-fields-pro/acf.php' => 'ACF PRO',
        'acf-image-aspect-ratio-crop/acf-image-aspect-ratio-crop.php' => 'ACF Image Aspect Ratio Crop',
        'acf-nav-menu-field/advanced-custom-nav-menu-field.php' => 'ACF Nav Menu Field'
    ];

    private $missingPlugins = [];

    function __construct() {

        $this->missingPlugins = [];
        foreach ( $this->requiredPlugins as $key => $pluginName ){
            if( ! is_plugin_active( $key ) ){
                $this->missingPlugins[] = '<strong>' . $pluginName . '</strong>s';
            }
        }

        add_action( 'admin_notices', [ $this, 'admin_notices' ] );
        add_filter( 'wp_mail', [ $this, 'wp_mail' ] );
        add_action( 'admin_footer', [ $this, 'admin_inline_js' ] );
        add_action( 'wp_ajax_iw_preview_email', [ $this, 'ajax_preview_email' ] );
        add_action( 'wp_ajax_iw_send_test_email', [ $this, 'ajax_send_test_email' ] );
    }

    function admin_notices(){
        if ( ! current_user_can('activate_plugins') ) return;
        if( ! empty( $this->missingPlugins ) ){
            echo '<div class="error"><p>The following plugin' . ( count( $this->missingPlugins ) > 1 ? 's' : '' )  . ' should be active to use IW Email Template: ' .  implode( ', ', $this->missingPlugins ) . '.</p></div>';
        }
    }

    function wp_mail( $attrs ){

        add_filter( 'wp_mail_content_type', function(){ return "text/html"; } );

        $attrs[ 'message' ] = $this->render_email_message( $attrs[ 'subject'], $attrs[ 'message' ] );

        return $attrs;
    }

    function render_email_message( $subject, $body ){
        $message = $this->load_template( '/templates/email/email-base.php', [ 'subject' => $subject, 'body' => apply_filters( 'the_content', $body ) ] );

        if( empty( $message ) ){
            return $body;
        }

        return $this->replace_button_links( $message );
    }

    function replace_button_links( $message ){
        $dom = new DOMDocument( '1.0', 'UTF-8' );

        $previous_use_internal_errors = libxml_use_internal_errors( true );
        libxml_clear_errors();
        $loaded = $dom->loadHTML( '<?xml encoding="UTF-8">' . $message, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
        libxml_clear_errors();
        libxml_use_internal_errors( $previous_use_internal_errors );
        if( ! $loaded ){
            return $message;
        }

        foreach ( $dom->childNodes as $node ) {
            if ( $node->nodeType === XML_PI_NODE ) {
                $dom->removeChild( $node );
                break;
            }
        }

        $xpath = new DOMXPath( $dom );
        $button_nodes = [];
        foreach ( $xpath->evaluate( "//a[contains(concat(' ', normalize-space(@class), ' '), ' btn ')]" ) as $node ) {
            $button_nodes[] = $node;
        }

        foreach ( $button_nodes as $node ) {
            $button = $this->load_template( '/templates/email/button.php', [ 'url' => $node->getAttribute('href'), 'text' => $node->nodeValue ] );
            if( empty( $button ) || empty( $node->parentNode ) || empty( $node->parentNode->parentNode ) ){
                continue;
            }

            $fragment = $dom->createDocumentFragment();
            if( ! $fragment->appendXML( $button ) ){
                continue;
            }

            $table = $dom->createElement( 'table' );
            $table->setAttribute( 'role', 'presentation' );
            $table->setAttribute( 'cellpadding', '0' );
            $table->setAttribute( 'cellspacing', '0' );
            $table->setAttribute( 'border', '0' );
            $table->setAttribute( 'width', '100%' );
            $table->setAttribute( 'style', 'border-collapse:collapse;' );

            $tbody = $dom->createElement( 'tbody' );
            $tr = $dom->createElement( 'tr' );
            $td = $dom->createElement( 'td' );
            $td->setAttribute( 'align', 'center' );
            $td->setAttribute( 'style', 'padding:0;' );

            $table->appendChild( $tbody );
            $tbody->appendChild( $tr );
            $tr->appendChild( $td );

            $node->parentNode->parentNode->replaceChild( $table, $node->parentNode );

            $td->appendChild( $fragment );
        }

        return $dom->saveHTML();
    }

    function load_template( $name, $args ){
        $template = false;
        if ( file_exists( STYLESHEETPATH . $name ) ) {
            $template = get_template_part_string( ltrim( $name, '/' ), false, $args   );
        } else if ( file_exists( dirname(__FILE__) . $name  ) ) {
            ob_start();
            load_template( dirname(__FILE__) . $name, false, $args );
            $template = ob_get_contents();
            ob_end_clean();
        }
        return $template;
    }

    function admin_inline_js(){
        $screen = get_current_screen();
        if( ! $screen || $screen->id !== 'settings_page_iw-email-template' || ! current_user_can( 'edit_posts' ) ){
            return;
        }

        $nonce = wp_create_nonce( 'iw_email_template_preview' );
        ?>
        <script>
        (function(){
            let iwPreviewWindow = null;
            const nonce = <?php echo wp_json_encode( $nonce ); ?>;
            const ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;

            document.addEventListener('click', async function(e) {
                if (e.target.id === 'iw-preview-email') {
                    e.preventDefault();
                    const url = ajaxUrl + '?action=iw_preview_email&_wpnonce=' + encodeURIComponent(nonce);
                    if (iwPreviewWindow && !iwPreviewWindow.closed) {
                        iwPreviewWindow.location.href = url;
                        iwPreviewWindow.focus();
                    } else {
                        iwPreviewWindow = window.open(url, '_blank');
                    }
                }

                if (e.target.id === 'iw-send-test-email') {
                    e.preventDefault();
                    const emailField = document.querySelector('[name="acf[field_iw_email_test_recipient]"]');
                    const email = emailField ? emailField.value : '';
                    if (!email) {
                        alert('Please enter a test recipient email.');
                        return;
                    }

                    const btn = e.target;
                    btn.disabled = true;
                    const previousText = btn.innerText;
                    btn.innerText = 'Sending...';

                    try {
                        const body = new URLSearchParams({
                            action: 'iw_send_test_email',
                            _wpnonce: nonce,
                            email: email
                        });
                        const res = await fetch(ajaxUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: body
                        });
                        const data = await res.json();
                        alert(data && data.data && data.data.message ? data.data.message : 'Done.');
                    } catch (err) {
                        alert('Request failed.');
                    }

                    btn.disabled = false;
                    btn.innerText = previousText;
                }
            });
        })();
        </script>
        <?php
    }

    function ajax_preview_email(){
        if( ! current_user_can( 'edit_posts' ) ){
            wp_die( 'Forbidden', '', [ 'response' => 403 ] );
        }
        check_ajax_referer( 'iw_email_template_preview' );

        $body = get_field( 'iw_email_test_body', 'option' );
        if( empty( $body ) ){
            $body = '<p>This is a preview email.</p><p><a href="#" class="btn">Button</a></p>';
        }

        nocache_headers();
        header( 'Content-Type: text/html; charset=UTF-8' );
        echo $this->render_email_message( 'Preview Email', wp_kses_post( $body ) );
        exit;
    }

    function ajax_send_test_email(){
        if( ! current_user_can( 'edit_posts' ) ){
            wp_send_json_error( [ 'message' => 'Forbidden' ], 403 );
        }
        check_ajax_referer( 'iw_email_template_preview' );

        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        if( empty( $email ) || ! is_email( $email ) ){
            wp_send_json_error( [ 'message' => 'Invalid email.' ], 400 );
        }

        $body = get_field( 'iw_email_test_body', 'option' );
        if( empty( $body ) ){
            $body = '<p>This is a test email.</p><p><a href="' . esc_url( home_url( '/' ) ) . '" class="btn">Button</a></p>';
        }

        $sent = wp_mail( $email, 'Test Email', wp_kses_post( $body ), [ 'Content-Type: text/html; charset=UTF-8' ] );
        if( ! $sent ){
            wp_send_json_error( [ 'message' => 'Email could not be sent.' ], 500 );
        }

        wp_send_json_success( [ 'message' => 'Email sent successfully.' ] );
    }

}

new IW_Email_Template();
new IW_Email_Template_Email_Previews();

register_activation_hook( __FILE__, function (){
    if ( ! is_plugin_active('advanced-custom-fields-pro/acf.php' ) || ! is_plugin_active( 'acf-image-aspect-ratio-crop/acf-image-aspect-ratio-crop.php' ) ) {
        if( current_user_can( 'activate_plugins' )  ){
            wp_die('Sorry, but this plugin requires the Parent Plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
        }
    }

    IW_Email_Template_Email_Previews::activate();
} );

register_deactivation_hook( __FILE__, function (){
    IW_Email_Template_Email_Previews::deactivate();
} );

add_action( 'after_setup_theme', function(){
    register_nav_menu( 'iw-email-template', "Email template footer" );
});
