<?php

class Password_Protected_Admin {

	var $settings_page_id;
	var $options_group = 'password-protected';
	var $setting_tabs = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wp_version;
		add_action( 'admin_init', array( $this, 'password_protected_register_setting_tabs' ) );
		add_action( 'admin_init', array( $this, 'password_protected_settings' ), 15 );
		add_action( 'admin_init', array( $this, 'add_privacy_policy' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'password_protected_subtab_password-protected-page-description_content', array( $this, 'password_protected_page_description_tab' ) );
		add_action( 'password_protected_help_tabs', array( $this, 'help_tabs' ), 5 );
		add_action( 'admin_notices', array( $this, 'password_protected_admin_notices' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 4 );
		add_filter( 'plugin_action_links_password-protected/password-protected.php', array( $this, 'plugin_action_links' ) );
		add_filter( 'pre_update_option_password_protected_password', array( $this, 'pre_update_option_password_protected_password' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'init', array( $this, 'init' ) );

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_upsell_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_classic_editor_upsell_assets' ) );

		add_action( 'password_protected_subtab_cache-issue_content', array( $this, 'cache_related_issue' ) );
		add_action( 'admin_footer', array( $this, 'add_script_in_footer' ), 9999 );
        add_filter( 'password_protected_setting_tabs', array( $this, 'register_setting_tabs' ), -1 );
        add_filter( 'password_protected_setting_tabs', array( $this, 'register_help_tabs' ), 9999 );
	}

    public function register_setting_tabs( $tabs ) {
        $new_tabs = array(
            'general'  => array(
                'title' => __( 'General', 'password-protected' ),
                'slug'  => 'general',
                'icon'  => 'dashicons-migrate',
            ),

            'advanced' => array(
                'title'    => __( 'Advanced', 'password-protected' ),
                'slug'     => 'advanced',
                'icon'     => 'dashicons-admin-settings',
                'sub-tabs' => array(
                    'exclude-from-protection' => array(
                        'title' => __( 'Exclude From Protection', 'password-protected' ),
                        'slug'  => 'exclude-from-protection',
                    ),

                    'password-protected-page-description' => array(
                        'title' => __( 'Protected Page Content', 'password-protected' ),
                        'slug'  => 'password-protected-page-description',
                    ),

                    'bypass-url' => array(
                        'title' => __( 'Bypass URL', 'password-protected' ),
                        'slug'  => 'bypass-url',
                    ),

                    'cache-issue' => array(
                        'title' => __( 'Cache Issue', 'password-protected' ),
                        'slug'  => 'cache-issue',
                    ),

                    'custom-error-message' => array(
                            'title' => __( 'Custom Error Message', 'password-protected' ),
                            'slug'  => 'custom-error-message',
                        ),
                ),
            ),

            'manage_passwords' => array(
                'title'           => __( 'Multiple Passwords', 'password-protected' ),
                'slug'            => 'manage_passwords',
                'icon'            => 'dashicons-shield',
                'show_as_submenu' => true,
                'position'        => 2,
            ),

            'integrations' => array(
                'title'           => __( 'Integrations', 'password-protected' ),
                'slug'            => 'integrations',
                'icon'            => 'dashicons-admin-plugins',
            ),

            'content-protection' => array(
                'title'           => __( 'Content Protection', 'password-protected' ),
                'slug'            => 'content-protection',
                'icon'            => 'dashicons-superhero',
                'position'        => 1,
                'show_as_submenu' => true,
                'sub-tabs'        => array(
                    'post-type-protection' => array(
                        'title' => __( 'Post Type Protection', 'password-protected' ),
                        'slug'  => 'post-type-protection',
                    ),

                    'taxonomy-protection' => array(
                        'title' => __( 'Taxonomy Protection', 'password-protected' ),
                        'slug'  => 'taxonomy-protection',
                    ),

                    'partial-protection' => array(
                            'title' => __( 'Partial Content Protection', 'password-protected' ),
                            'slug'  => 'partial-protection',
                        ),
                ),
            ),

            'security' => array(
                'title'    => __( 'Security', 'password-protected' ),
                'slug'     => 'security',
                'icon'     => 'dashicons-shield-alt',
                'sub-tabs' => array(
                    'whitelist-user-role' => array(
                        'title' => __( 'Whitelist User Role', 'password-protected' ),
                        'slug'  => 'whitelist-user-role',
                    ),

                    'all-captchas' => array(
                        'title' => __( 'Captcha', 'password-protected' ),
                        'slug'  => 'all-captchas',
                    ),

                    'wp-admin-protection' => array(
                        'title' => __( 'WP-Admin Protection', 'password-protected' ),
                        'slug'  => 'wp-admin-protection',
                    ),

                    'attempt-limitation' => array(
                        'title' => __( 'Attempt Limitation', 'password-protected' ),
                        'slug'  => 'attempt-limitation',
                    ),
                ),
            ),

            'logs' => array(
                'title'           => __( 'Logs', 'password-protected' ),
                'slug'            => 'logs',
                'icon'            => 'dashicons-media-text',
                'show_as_submenu' => true,
                'position'        => 3,
                'sub-tabs'        => array(
                    'activity_logs' => array(
                        'title' => __( 'Activity Logs', 'password-protected' ),
                        'slug'  => 'activity_logs',
                    ),

                    'activity-report' => array(
                        'title' => __( 'Activity Report', 'password-protected' ),
                        'slug'  => 'activity-report',
                    ),
                ),
            ),

            'protected-screen' => array(
                'title'           => __( 'Customize', 'password-protected' ),
                'slug'            => 'protected-screen',
                'icon'            => 'dashicons-admin-customizer',
                'show_as_submenu' => true,
                'position'        => 5,
            ),

            'request-password' => array(
                'title'           => __( 'Password Request', 'password-protected' ),
                'slug'            => 'request-password',
                'icon'            => 'dashicons-email-alt',
                'show_as_submenu' => true,
                'position'        => 4,
                'sub-tabs'        => array(
                    'password-request' => array(
                        'title' => __( 'Request Password', 'password-protected' ),
                        'slug'  => 'password-request',
                    ),
                    'requests'         => array(
                        'title' => __( 'Requests', 'password-protected' ),
                        'slug'  => 'requests',
                    ),
                    'email-templates'  => array(
                        'title' => __( 'Email Templates', 'password-protected' ),
                        'slug'  => 'email-templates',
                    ),
                ),
            ),
        );

        return array_merge( $tabs, $new_tabs );
    }

    public function register_help_tabs( $tabs ) {

        $tabs['help'] = array(
            'title' => __( 'Help', 'password-protected' ),
            'slug'  => 'help',
            'icon'  => 'dashicons-editor-help',
        );

        if ( ! class_exists( 'Password_Protected_Pro' ) ) {
            $tabs['getpro'] = array(
                'title' => __( 'Get Pro', 'password-protected' ),
                'slug'  => 'getpro',
                'icon'  => 'dashicons-superhero-alt',
            );
        }

        return $tabs;
    }

	public function add_script_in_footer() {
		?>
        <script type="text/javascript">
            jQuery( document ).ready( function( $ ) {
                $( '.toplevel_page_password-protected a' ).each( function( index,element ) {
                    if ( 'admin.php?page=password-protected-get-pro' === $( element ).attr( 'href' ) ) {
                        $( element ).css( { 'background-color': '#8076ff', 'color': '#ffffff', 'padding': '15px auto' } );
                    }
                } );
            } );
        </script>
		<?php
	}

	/**
	 * Password protected setting tabs
	 * customizable using filter hook
	 */
	public function password_protected_register_setting_tabs() {
		$this->setting_tabs = apply_filters( 'password_protected_setting_tabs', array() );
	}

	/**
	 * Admin enqueue scripts.
	 *
	 * @param string $hooks Page Hook.
	 */
	public function admin_enqueue_scripts( $hooks ) {

		if ( 'settings_page_password-protected' === $hooks || 'toplevel_page_password-protected' === $hooks ) {
			global $Password_Protected;
			wp_enqueue_style(
				'password-protected-poppins',
				'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap',
				array(),
				null
			);
			wp_enqueue_style( 'password-protected-page-script', PASSWORD_PROTECTED_URL . 'assets/css/admin.css', array( 'password-protected-poppins' ), $Password_Protected->version );
			wp_enqueue_script( 'password-protected-admin-script', PASSWORD_PROTECTED_URL . 'assets/js/admin.js', array('jquery'), $Password_Protected->version );
			wp_localize_script(
				'password-protected-admin-script',
				'passwordProtectedAdminObject',
				array(
					'imageURL'       => PASSWORD_PROTECTED_URL . 'assets/images/',
					'heading'        => __( "Don't Settle for Limited Password Protection", 'password-protected' ),
					'description'    => __( 'Upgrade to Business plan and unlock all premium features!', 'password-protected' ),
					'buttonText'     => __( 'Upgrade to Premium', 'password-protected' ),
					'buttonRedirect' => add_query_arg(
						array(
							'page' => 'password-protected',
							'tab'  => 'getpro',
						),
						admin_url( 'admin.php' )
					),
				)
			);
		}
	}

	/**
	 * Enqueue assets for block editor native visibility upsell.
	 */
	public function enqueue_block_editor_upsell_assets() {
		if ( class_exists( 'Password_Protected_Pro' ) ) {
			return;
		}
		global $Password_Protected;
		
		wp_enqueue_script(
			'password-protected-native-visibility-upsell',
			PASSWORD_PROTECTED_URL . 'assets/js/native-visibility-upsell.js',
			array( 'jquery' ),
			$Password_Protected->version,
			true
		);

		wp_localize_script(
			'password-protected-native-visibility-upsell',
			'passwordProtectedUpsell',
			array(
				'settingsUrl' => admin_url( 'admin.php?page=password-protected' ),
			)
		);
	}

	/**
	 * Enqueue assets for classic editor native visibility upsell.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_classic_editor_upsell_assets( $hook ) {
		if ( class_exists( 'Password_Protected_Pro' ) ) {
			return;
		}
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		global $Password_Protected;

		wp_enqueue_script(
			'password-protected-classic-visibility-upsell',
			PASSWORD_PROTECTED_URL . 'assets/js/classic-visibility-upsell.js',
			array( 'jquery' ),
			$Password_Protected->version,
			true
		);

		wp_localize_script(
			'password-protected-classic-visibility-upsell',
			'passwordProtectedUpsell',
			array(
				'settingsUrl' => admin_url( 'admin.php?page=password-protected' ),
			)
		);
	}

	public function init() {

		if ( ! class_exists( 'Password_Protected_Pro' ) ) {
			
			add_action( 'password_protected_subtab_exclude-from-protection_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_custom-error-message_content', array( $this, 'dummy_content' ) );
			add_action( 'text_before_after_login_form', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_attempt-limitation_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_bypass-url_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_tab_manage_passwords_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_tab_integrations_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_post-type-protection_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_taxonomy-protection_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_partial-protection_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_whitelist-user-role_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_wp-admin-protection_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_activity_logs_content', array( $this, 'dummy_content' ) );

			add_action( 'password_protected_subtab_logo-styles_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_label-styles_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_field-styles_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_button-styles_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_remember-me-styles_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_form-background_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_body-background_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_below-form_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_below-page_content', array( $this, 'dummy_content' ) );
			add_action( 'password_protected_subtab_custom-css_content', array( $this, 'dummy_content' ) );
            add_action( 'password_protected_subtab_password-request_content', array( $this, 'dummy_content' ) );
            add_action( 'password_protected_subtab_requests_content', array( $this, 'dummy_content' ) );
            add_action( 'password_protected_subtab_email-templates_content', array( $this, 'dummy_content' ) );
		}

		if ( isset( $_GET['page'] ) && 'password-protected-get-pro' === $_GET['page'] ) {
			wp_redirect( 'https://passwordprotectedwp.com/pricing/?utm_source=Plugin&utm_medium=Submenu' );
			exit;
		}
	}

	/**
	 * Add Privacy Policy
	 */
	public function add_privacy_policy() {

		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return 1;
		}

		$content = _x( 'The Password Protected plugin stores a cookie on successful password login containing a hashed version of the entered password. It does not store any information about the user. The cookie stored is named <code>bid_n_password_protected_auth</code> where <code>n</code> is the blog ID in a multisite network', 'privacy policy content', 'password-protected' );

		wp_add_privacy_policy_content( __( 'Password Protected Plugin', 'password-protected' ), wp_kses_post( wpautop( $content, false ) ) );

	}

	/**
	 * Admin Menu
	 */
	public function admin_menu() {

		$capability             = apply_filters( 'password_protected_options_page_capability', 'manage_options' );
		$this->settings_page_id = add_options_page(
			__( 'Password Protected', 'password-protected' ),
			__( 'Password Protected', 'password-protected' ),
			$capability,
			'password-protected',
			array(
				$this,
				'settings_page'
			)
		);

		add_menu_page(
			'Password Protected',
			'Password Protected',
			'manage_options',
			'password-protected',
			array( $this, 'pp_admin_menu_page_callback' ),
			'dashicons-lock',
			99
		);

        add_submenu_page(
            'password-protected',
            'Password Protected',
            'General',
            'manage_options',
            'password-protected',
            array( $this, 'pp_admin_menu_page_callback' )
        );

		add_action( 'load-' . $this->settings_page_id, array( $this, 'add_help_tabs' ), 20 );

        $menus = apply_filters( 'password_protected_setting_tabs', array() );

        foreach ( $menus as $menu_key => $menu ) {
            if ( isset( $menu['show_as_submenu'] ) && $menu['show_as_submenu'] ) {
                add_submenu_page(
                    'password-protected',
                    $menu['title'],
                    $menu['title'],
                    'manage_options',
                    add_query_arg(
                        array(
                            'page' => 'password-protected',
                            'tab'  => $menu['slug'],
                        ),
                        admin_url( 'admin.php' )
                    ),
                    null
                );
            }
        }

		if ( ! class_exists( 'Password_Protected_Pro' ) ) {
			add_submenu_page(
				'password-protected',
				__( 'Get Pro Now', 'password-protected' ),
				__( '⭐ Get Pro Now', 'password-protected' ),
				'manage_options',
				'password-protected-get-pro',
				array( $this, 'password_protected_get_pro_features' )
			);
		}
	}

	/**
	 * Settings Page
	 */
	public function settings_page() {
		?>

        <div class="wrap">
            <div id="icon-options-general" class="icon32"><br /></div>
            <h2><?php _e( 'Password Protected Settings', 'password-protected' ) ?></h2>
            <form method="post" action="options.php">
				<?php
				settings_fields( 'password-protected' );
				do_settings_sections( 'password-protected' );
				?>
                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Changes' ) ?>"></p>
            </form>
			<?php
			// do_settings_sections( 'password-protected-login-designer' );
			?>

            <div id="help-notice">
				<?php do_settings_sections( 'password-protected-compat' ); ?>
            </div>
        </div>

		<?php
	}

	/** @since 2.6
	 * Admin Menu Settings Page
	 */
	public function pp_admin_menu_page_callback() {
		$tab    = ( isset( $_GET['tab'] ) && sanitize_text_field( $_GET['page'] ) == 'password-protected' ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
		$subtab = ( isset( $_GET['sub-tab'] ) && sanitize_text_field( $_GET['page'] ) == 'password-protected' ) ? sanitize_text_field( $_GET['sub-tab'] ) : '';

		// for backward compatibility.
		$this->setting_tabs = array_filter(
			$this->setting_tabs,
			function( $tab ) {
				return isset( $tab['title'] ) && isset( $tab['slug'] ) && isset( $tab['icon'] );
			}
		);
		if ( isset( $this->setting_tabs[ $tab ]['sub-tabs'] ) && ! empty( $this->setting_tabs[ $tab ]['sub-tabs'] ) ) {
			$this->setting_tabs[ $tab ]['sub-tabs'] = array_filter(
				$this->setting_tabs[ $tab ]['sub-tabs'],
				function ( $subtab ) {
					return isset( $subtab['title'] ) && isset( $subtab['slug'] );
				}
			);
		}
		?>
        <div class="wrap">
            <?php $attributes = class_exists( 'Password_Protected_Pro' ) ? 'style="display: block;"' : 'class="wrap-row"' ; ?>
            <div <?php echo $attributes; ?>>
                <?php $attributes = class_exists( 'Password_Protected_Pro' ) ? 'style="width: 100%;"' : 'class="wrap-col-70"'; ?>
                <div <?php echo $attributes; ?>>
					<?php settings_errors(); ?>

                    <div class="pp-wrapper">

                        <div class="pp-nav-wrapper">
							<?php foreach( $this->setting_tabs as $index => $setting_tab ) : ?>
                                <div class="pp-nav-tab <?php echo ( $tab === $setting_tab['slug'] ) ? 'pp-nav-tab-active' : ''; ?> <?php echo ( 'getpro' === $setting_tab['slug'] ) ? 'pp-pro-tab' : ''; ?>">
                                    <a href="<?php echo admin_url( 'admin.php?page=password-protected&tab=' . $setting_tab['slug'] ); ?>" class="get-pro-txt">
										<?php if ( 'getpro' === $setting_tab['slug'] ) : ?>
											<span class="pp-get-pro-tab-icon">
												<img src="<?php echo esc_url( PASSWORD_PROTECTED_URL . 'assets/images/pro-tab-icon.png' ); ?>" alt="" class="pro-tab-icon">
											</span>

										<?php elseif ( filter_var( $setting_tab['icon'], FILTER_VALIDATE_URL ) ) : ?>

											<span>
												<img src="<?php echo esc_url( $setting_tab['icon'] ); ?>" alt="">
											</span>

										<?php else : ?>

											<span class="dashicons <?php echo esc_attr( $setting_tab['icon'] ); ?>"></span>

										<?php endif; ?>

										<?php echo esc_html( $setting_tab['title'] ); ?>
                                    </a>
                                </div>
							<?php endforeach; ?>
                        </div>

                        <div class="pp-content-wrapper">
							<?php if ( isset( $this->setting_tabs[ $tab ] ) && isset( $this->setting_tabs[ $tab ]['sub-tabs'] ) && ! empty( $this->setting_tabs[ $tab ]['sub-tabs'] ) ) : ?>
                                <div class="pp-sub-tabs-wrapper">
                                    <div class="pp-subtabs-links">
										<?php if ( empty( $subtab ) ) { ?>
											<?php
											$subtab = array_keys( $this->setting_tabs[ $tab ]['sub-tabs'] );
											$subtab = $subtab[0];
											?>
										<?php } ?>
										<?php foreach ( $this->setting_tabs[ $tab ]['sub-tabs'] as $sub_tab ) : ?>
                                            <a class="<?php echo $subtab === $sub_tab['slug'] ? 'active' : '' ?>" href="<?php echo admin_url( 'admin.php?page=password-protected&tab=' . $tab . '&sub-tab=' . $sub_tab['slug'] ); ?>"><?php echo $sub_tab['title']; ?></a>
										<?php endforeach; ?>
                                    </div>
                                </div>
							<?php endif; ?>

                            <div class="pp-settings-wrapper">
								<?php $this->password_protected_render_tab_content( $tab, $subtab ); ?>
                            </div>
                        </div>
                    </div>
                </div>

	            <?php $attributes = class_exists( 'Password_Protected_Pro' ) ? 'style="display:none;"' : 'id="pp-sidebar" class="wrap-col-25"'; ?>
                <div <?php echo $attributes; ?>>
					<?php
					$_tab = '';
					if ( isset( $_GET['tab'] ) ) {
						$_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
					}
					if ( 'getpro' !== $_tab ) :
						do_settings_sections( 'password-protected-try-pro' );
						// do_settings_sections( 'password-protected-login-designer' );
						do_action('password_protected_sidebar');
					endif;
					?>
                </div>
            </div>
        </div>
		<?php
	}

	public function password_protected_page_description_tab() {
		echo '<form action="options.php" method="post" enctype="multipart/form-data">';
		settings_fields( 'password-protected-advanced-protected-page-content' );
		do_settings_sections( 'password-protected&tab=advanced&sub-tab=password-protected-page-description' );
		do_action('text_before_after_login_form', $_GET);
		submit_button();
		echo '</form>';
	}

	/**
	 * password protected render settings page in menu
	 */
	public function password_protected_render_tab_content( $tab, $sub_tab ) {
		
		switch ( $tab ) {
			case 'general':
				do_settings_sections( 'password-protected-help' );
				echo '<form method="post" action="options.php">';
				settings_fields( 'password-protected' );
				do_settings_sections( 'password-protected' );
				submit_button();
				echo '</form>';
				break;

			case 'help':
				?>
                <div id="help-notice">
					<?php do_settings_sections( 'password-protected-compat' ); ?>
                </div>
				<?php
				break;

			case 'getpro':
				$this->password_protected_get_pro_features();
				break;

			case $tab:
			
				if ( ! empty( $sub_tab ) ) {
					do_action(
						'password_protected_subtab_' . $sub_tab . '_content',
						$this->setting_tabs[ $tab ]['sub-tabs'][ $sub_tab ]
					);
				} else {
					do_action(
						'password_protected_tab_' . $tab . '_content',
						$this->setting_tabs[ $tab ]
					);
				}
				break;
		}
	}

	/**
	 * Add Help Tabs
	 */
	public function add_help_tabs() {

		global $wp_version;

		if ( version_compare( $wp_version, '3.3', '<' ) ) {
			return 1;
		}

		do_action( 'password_protected_help_tabs', get_current_screen() );

	}

	/**
	 * Help Tabs
	 *
	 * @param  object  $current_screen  Screen object.
	 */
	public function help_tabs( $current_screen ) {

		$current_screen->add_help_tab( array(
			'id'      => 'PASSWORD_PROTECTED_SETTINGS',
			'title'   => __( 'Password Protected', 'password-protected' ),
			'content' => __( '<p><strong>Password Protected Status</strong><br />Turn on/off password protection.</p>', 'password-protected' )
			             . __( '<p><strong>Protected Permissions</strong><br />Allow access for logged in users and administrators without needing to enter a password. You will need to enable this option if you want administrators to be able to preview the site in the Theme Customizer. Also allow RSS Feeds to be accessed when the site is password protected.</p>', 'password-protected' )
			             . __( '<p><strong>Password Fields</strong><br />To set a new password, enter it into both fields. You cannot set an `empty` password. To disable password protection uncheck the Enabled checkbox.</p>', 'password-protected' )
		) );

	}

	/**
	 * Settings API
	 */
	public function password_protected_settings() {
		// general tab
		add_settings_section(
			'password_protected',
			__( 'Password Protected Configuration', 'password-protected' ),
			array( $this, 'password_protected_settings_section' ),
			$this->options_group
		);

		add_settings_field(
			'password_protected_status',
			__( 'Password Protected Status', 'password-protected' ),
			array( $this, 'password_protected_status_field' ),
			$this->options_group,
			'password_protected'
		);

		add_settings_field(
			'password_protected_permissions',
			__( 'Protected Permissions', 'password-protected' ),
			array( $this, 'password_protected_permissions_field' ),
			$this->options_group,
			'password_protected'
		);

		add_settings_field(
			'password_protected_password',
			__( 'New Password', 'password-protected' ),
			array( $this, 'password_protected_password_field' ),
			$this->options_group,
			'password_protected'
		);

		add_settings_field(
			'password_protected_allowed_ip_addresses',
			__( 'Allow IP Addresses', 'password-protected' ),
			array( $this, 'password_protected_allowed_ip_addresses_field' ),
			$this->options_group,
			'password_protected'
		);

		add_settings_field(
			'password_protected_remember_me',
			__( 'Allow Remember me', 'password-protected' ),
			array( $this, 'password_protected_remember_me_field' ),
			$this->options_group,
			'password_protected'
		);

		add_settings_field(
			'password_protected_remember_me_lifetime',
			__( 'Remember for this many days', 'password-protected' ),
			array( $this, 'password_protected_remember_me_lifetime_field' ),
			$this->options_group,
			'password_protected'
		);

		// password protected advanced tab
		add_settings_section(
			'password-protected-advanced-tab',
			__( 'Password Protected Page description', 'password-protected' ),
			array( $this, 'password_protected_page_description' ),
			'password-protected&tab=advanced&sub-tab=password-protected-page-description'
		);

		add_settings_field(
			'text-above-password',
			__( 'Text Above Password Field', 'password-protected' ),
			array( $this, 'password_protected_text_above_password' ),
			'password-protected&tab=advanced&sub-tab=password-protected-page-description',
			'password-protected-advanced-tab'
		);

		add_settings_field(
			'text-below-password',
			__( 'Text Below Password Field ', 'password-protected' ),
			array( $this, 'password_protected_text_below_password' ),
			'password-protected&tab=advanced&sub-tab=password-protected-page-description',
			'password-protected-advanced-tab'
		);

		add_settings_section(
			'password-protected-advanced-tab-cache-issue',
			__( 'Cache Issue', 'password-protected' ),
			'__return_null',
			'password-protected&tab=advanced&sub-tab=cache-issue'
		);

		add_settings_field(
			'password-protected-advance-cache',
			__( 'Problem With Cookie Cache', 'password-protected' ),
			array( $this, 'password_protected_use_transient' ),
			'password-protected&tab=advanced&sub-tab=cache-issue',
			'password-protected-advanced-tab-cache-issue',
			array(
				'label_for' => 'password-protected-use-transient',
			)
		);

		add_settings_field(
			'password-protected-page-cache',
			__( 'Problem With Page Cache', 'password-protected' ),
			array( $this, 'password_protected_enable_dynamic_args' ),
			'password-protected&tab=advanced&sub-tab=cache-issue',
			'password-protected-advanced-tab-cache-issue',
			array(
				'label_for' => 'pp_enable_dynamic_args',
			)
		);

		// password protected help tab
		add_settings_section(
			'password-protected-help',
			'',
			array( $this, 'password_protected_help_tab' ),
			'password-protected-help'
		);

		if( !$this->password_protected_pro_is_installed_and_activated() ) {
			add_settings_section(
				'password-protected-try-pro',
				'',
				array( $this, 'password_protected_try_pro' ),
				'password-protected-try-pro'
			);
		}

		if ( ! $this->login_designer_is_installed_and_activated() ) {
			/* add_settings_section(
				'password-protected-login-designer',
				'',
				array( $this, 'password_protected_login_designer' ),
				'password-protected-login-designer'
			); */
		}

		// registering settings
		register_setting( $this->options_group, 'password_protected_status', 'intval' );
		register_setting( $this->options_group, 'password_protected_feeds', 'intval' );
		register_setting( $this->options_group, 'password_protected_rest', 'intval' );
		register_setting( $this->options_group, 'password_protected_administrators', 'intval' );
		register_setting( $this->options_group, 'password_protected_users', 'intval' );
		register_setting( $this->options_group, 'password_protected_password', array( $this, 'sanitize_password_protected_password' ) );
		register_setting( $this->options_group, 'password_protected_allowed_ip_addresses', array( $this, 'sanitize_ip_addresses' ) );
		register_setting( $this->options_group, 'password_protected_remember_me', 'boolval' );
		register_setting( $this->options_group, 'password_protected_remember_me_lifetime', 'intval' );

		register_setting( 'password-protected-advanced-protected-page-content', 'password_protected_text_above_password', array( 'type' => 'string' ) );
		register_setting( 'password-protected-advanced-protected-page-content', 'password_protected_text_below_password', array( 'type' => 'string' ) );

		register_setting( 'password_protected_cache_issue', 'password_protected_use_transient' );
		register_setting( 'password_protected_cache_issue', 'pp_enable_dynamic_args' );
	}

	/**
	 * Sanitize Password Field Input
	 *
	 * @param   string  $val  Password.
	 * @return  string        Sanitized password.
	 */
	public function sanitize_password_protected_password( $val ) {

		$old_val = get_option( 'password_protected_password' );

		if ( is_array( $val ) ) {
			if ( empty( $val['new'] ) ) {
				return $old_val;
			} elseif ( empty( $val['confirm'] ) ) {
				add_settings_error( 'password_protected_password', 'password_protected_password', __( 'New password not saved. When setting a new password please enter it in both fields.', 'password-protected' ) );
				return $old_val;
			} elseif ( $val['new'] != $val['confirm'] ) {
				add_settings_error( 'password_protected_password', 'password_protected_password', __( 'New password not saved. Password fields did not match.', 'password-protected' ) );
				return $old_val;
			} elseif ( $val['new'] == $val['confirm'] ) {
				add_settings_error( 'password_protected_password', 'password_protected_password', __( 'New password saved.', 'password-protected' ), 'updated' );
				return $val['new'];
			}
			return get_option( 'password_protected_password' );
		}


		return $val;

	}

	/**
	 * Sanitize IP Addresses
	 *
	 * @param   string  $val  IP addresses.
	 * @return  string        Sanitized IP addresses.
	 */
	public function sanitize_ip_addresses( $val ) {
		$un_sanitized_value = $val;

		$ip_addresses = explode( "\n", $val );
		$ip_addresses = array_map( 'sanitize_text_field', $ip_addresses );
		$ip_addresses = array_map( 'trim', $ip_addresses );
		$ip_addresses = array_map( array( $this, 'validate_ip_address' ), $ip_addresses );
		$ip_addresses = array_filter( $ip_addresses );

		$val = implode( "\n", $ip_addresses );

		return apply_filters( 'password_protected__sanitize_ip_addresses', $val, $un_sanitized_value );

	}

	/**
	 * Validate IP Address
	 *
	 * @param   string  $ip_address  IP Address.
	 * @return  string               Validated IP Address.
	 */
	private function validate_ip_address( $ip_address ) {

		return filter_var( $ip_address, FILTER_VALIDATE_IP );

	}

	/**
	 * Password Protected Section
	 */
	public function password_protected_settings_section() {

		return 1;

	}

	/**
	 * Password Protection Status Field
	 */
	public function password_protected_status_field() {

		echo '
            <div class="pp-toggle-wrapper">
                <input type="checkbox" name="password_protected_status" id="password_protected_status" value="1" ' . checked( 1, get_option( 'password_protected_status' ), false ) . ' />
                <label class="pp-toggle" for="password_protected_status">
                    <span class="pp-toggle-slider"></span>
                </label>
            </div>
        <p>
            <label for="password_protected_status">' . __( 'Do you want to enable password protection for whole site?', 'password-protected' ) . '</label>
        </p>
        ';

	}

	/**
	 * Password Protection Permissions Field
	 */
	public function password_protected_permissions_field() {

		echo '<p>
            <label for="password_protected_administrators">
                <input type="checkbox" name="password_protected_administrators" id="password_protected_administrators" value="1" ' . checked( 1, get_option( 'password_protected_administrators' ), false ) . ' />'
		     . __( 'Allow Administrators', 'password-protected' )
		     . '</label>
        </p>
        <p>
            <label for="password_protected_users">
                <input type="checkbox" name="password_protected_users" id="password_protected_users" value="1" ' . checked( 1, get_option( 'password_protected_users' ), false ) . ' />'
		     . __( 'Allow Logged In Users', 'password-protected' )
		     . '</label>
        </p>
        <p>
            <label for="password_protected_feeds">
                <input type="checkbox" name="password_protected_feeds" id="password_protected_feeds" value="1" ' . checked( 1, get_option( 'password_protected_feeds' ), false ) . ' />'
		     . __( 'Allow RSS Feeds', 'password-protected' )
		     . '</label>
        </p>
        <p>
            <label for="password_protected_rest">
                <input type="checkbox" name="password_protected_rest" id="password_protected_rest" value="1" ' . checked( 1, get_option( 'password_protected_rest' ), false ) . ' />'
		     . __( 'Allow REST API', 'password-protected' )
		     . '</label>
        </p>';

	}

	/**
	 * Password Field
	 */
	public function password_protected_password_field() {

		echo '<input type="password" name="password_protected_password[new]" id="password_protected_password_new" size="16" value="" autocomplete="off"> <p><span class="description">' . __( 'If you would like to change the password, type a new one. Otherwise, leave this blank.', 'password-protected' ) . '</span></p><br>
			<input type="password" name="password_protected_password[confirm]" id="password_protected_password_confirm" size="16" value="" autocomplete="off"> <p><span class="description">' . __( 'Type your new password again.', 'password-protected' ) . '</span></p>';

	}

	/**
	 * Allowed IP Addresses Field
	 */
	public function password_protected_allowed_ip_addresses_field() {
		echo '<textarea name="password_protected_allowed_ip_addresses" id="password_protected_allowed_ip_addresses" rows="3" />' . esc_html( get_option( 'password_protected_allowed_ip_addresses' ) ) . '</textarea>';

		echo '<p class="description">' . esc_html__( 'Enter one IP address per line.', 'password-protected' );
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			echo ' ' . esc_html( sprintf( __( 'Your IP address is %s.', 'password-protected' ), $_SERVER['REMOTE_ADDR'] ) );
		}
		echo '</p>';

	}

	/**
	 * Remember Me Field
	 */
	public function password_protected_remember_me_field() {

		echo '<div class="pp-toggle-wrapper">
            <input type="checkbox" name="password_protected_remember_me" id="password_protected_remember_me" value="1" ' . checked( 1, get_option( 'password_protected_remember_me' ), false ) . ' />
            <label class="pp-toggle" for="password_protected_remember_me">
                <span class="pp-toggle-slider"></span>
            </label>
        </div>
        <p>
            <label for="password_protected_remember_me">' . __( 'Allow Remember me', 'password-protected' ) . '</label>
        </p>';

	}

	/**
	 * Remember Me lifetime field
	 */
	public function password_protected_remember_me_lifetime_field() {

		echo '<label><input name="password_protected_remember_me_lifetime" id="password_protected_remember_me_lifetime" min="1" type="number" value="' . get_option( 'password_protected_remember_me_lifetime', 14 ) . '" /></label>';

	}

	/**
	 * Password Protected Page description
	 */
	public function password_protected_page_description() {
		return 1;
	}

	/**
	 * Password Protected text above passsword
	 */
	public function password_protected_text_above_password() {
		echo '<label><textarea id="password_protected_text_above_password" name="password_protected_text_above_password" rows="4" cols="50" class="regular-text">' . esc_attr( get_option('password_protected_text_above_password') ) . '</textarea></label>';
	}

	/**
	 * Password Protected below above passsword
	 */
	public function password_protected_text_below_password() {
		echo '<label><textarea id="password_protected_text_below_password" name="password_protected_text_below_password" rows="4" cols="50" class="regular-text">' . esc_attr( get_option('password_protected_text_below_password') ) . '</textarea></label>';
	}

	public function password_protected_use_transient() {
		$use_transient = get_option( 'password_protected_use_transient', 'default' );

		$cache_issue = array(
			array(
				'name' => 'default',
				'title' => __( 'Use default settings', 'password-protected' ),
			),
			array(
				'name' => 'transient',
				'title' => __( 'You can enable this option if you are having trouble with cookies due to cache or server restrictions.', 'password-protected' ),
                'description' => __( 'Note: It uses transients,  which are saved based on the user\'s IP address, unlike cookies that are tied to the specific browser. This means that once a user logs in using any browser, they can access the page from any other browser as long as they are on the same IP address.', 'password-protected' ),
			),
		);

		foreach ( $cache_issue as $issue ) :
			echo '<p>
                <label>
                    <input type="radio" name="password_protected_use_transient" value="' . esc_attr( $issue['name'] ) . '" ' . checked( $use_transient, $issue['name'], false ) . ' />' . esc_html( $issue['title'] ) . '
                </label>
            </p>';

            if ( isset( $issue['description'] ) ) :
                echo '<p class="desc"><strong>' . esc_attr( $issue['description'] ) . '</strong></p>';
            endif;
		endforeach;
	}

	/**
	 * Page cache: dynamic URL arguments to bust full-page cache.
	 */
	public function password_protected_enable_dynamic_args() {
		$value = get_option( 'pp_enable_dynamic_args', false );
		echo '<div class="pp-toggle-wrapper">
				<input value="yes" type="checkbox" name="pp_enable_dynamic_args" id="pp_enable_dynamic_args" ' . checked( $value, 'yes', false ) . '>
				<label for="pp_enable_dynamic_args" class="pp-toggle">
					<span class="pp-toggle-slider"></span>
				</label>
			</div>
			<p class="desc">
				<strong>
					<label for="pp_enable_dynamic_args">' . esc_html__( 'Enable if page cache causes login or access issues; adds dynamic query args to URLs.', 'password-protected' ) . '</label>
				</strong>
			</p>';
	}

	/**
	 * Help Tab text field
	 */
	public function password_protected_help_tab() {
		echo '<div class="pp-help-notice">
            <p>'
		     . __( 'Password protect your web site. Users will be asked to enter a password to view the site.', 'password-protected' )
		     . '<br />'
		     . __( 'For more information about Password Protected settings, view the "Help" tab at the top of this page.', 'password-protected' )
		     . '</p>
        </div>';
	}

	/**
	 * Try pro sideabr
	 */
	public function password_protected_try_pro() {
		$image_url = PASSWORD_PROTECTED_URL . 'assets/images/';
		echo '<div class="pp-sidebar-widget">
            <div class="pp-container">

				<div class="pp-sidebar-header">	
					<div class="pp-row">
						<div class="pp-crown-icon">
							<img src="' . $image_url . 'pro-crown.png" />
						</div>
						<div>
							<p class="heading-2">Password</p>
							<div class="pp-head-wt-pro-tag">
								<p class="heading-2">Protected</p> 
								<p class="pp-pro-tag">
									PRO
								</p>
							</div>
							<p class="pp-sm-txt-under-head">Level up your WordPress protection</p>
						</div>
					</div>
                </div>

                <div class="pp-sidebar-body">
                    <ul>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Protect Custom Post Types</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Exclude Specific Page, Post & Product</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Partial Content Protection</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Protect Categories</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Protect WordPress Login Page</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Lock Specific Posts & Pages</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Manage Unlimited Passwords</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Set Expiration & Usage Limits</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Limit Login Attempts</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Create Secure Bypass Links</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Lock Screen Customization</span>
                        </li>
						<li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Password Access Request</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Whitelist User Roles</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">Track Password Activity</span>
                        </li>
                        <li>
                            <span class="sidebar-body-image-container"><img src="' . $image_url . 'lock-2.png"  alt="" /></span> <span class="sidebar-body-text-container">hCaptcha & Cloudflare Turnstile</span>
                        </li>
                    </ul>
                </div>
                <div class="pp-sidebar-footer">
                    <a target="_blank" href="https://passwordprotectedwp.com/pricing/?utm_source=plugin&utm_medium=side_banner&utm_campaign=plugin">' . esc_html__( 'Upgrade to Premium', 'password-protected' ) . '</a>
                </div>
            </div>
        </div>';
	}

	public function password_protected_login_designer() {
		$search_login_designer = add_query_arg(
			array(
				's'    => 'login designer',
				'tab'  => 'search',
				'type' => 'term',
			),
			admin_url( 'plugin-install.php' )
		);
		echo '<div class="pp-sidebar-widget">
            <div id="pp-sidebar-box">
                <h3>' .
		     sprintf(
			     __( '%1$s Now you can customize your Password Protected screen with the %3$s %2$s', 'password-protected' ),
			     '🎨',
			     '🌈',
			     '<a href="' . $search_login_designer . '">' . __( 'Login Designer Plugin', 'password-protected' ) . '</a>'
		     )
		     . '</h3>
                
                <img width="100%" src="'. PASSWORD_PROTECTED_URL .'assets/images/login-designer-demo.gif" alt="Login Designer Demo GIF">
                
                <h3>
                    <a class="pp-try button-primary" href="' . $search_login_designer . '">
                        👉 ' . __( 'Try it now! It\'s Free', 'password-protected' ) . '
                    </a>
                </h3>
            </div>
        </div>';
	}

	/**
	 * Pre-update 'password_protected_password' Option
	 *
	 * Before the password is saved, MD5 it!
	 * Doing it in this way allows developers to intercept with an earlier filter if they
	 * need to do something with the plaintext password.
	 *
	 * @param   string  $newvalue  New Value.
	 * @param   string  $oldvalue  Old Value.
	 * @return  string             Filtered new value.
	 */
	public function pre_update_option_password_protected_password( $newvalue, $oldvalue ) {

		global $Password_Protected;

		if ( $newvalue != $oldvalue ) {
			$newvalue = $Password_Protected->encrypt_password( $newvalue );
		}

		return $newvalue;

	}

	/**
	 * Plugin Row Meta
	 *
	 * Adds GitHub and translate links below the plugin description on the plugins page.
	 *
	 * @param   array   $plugin_meta  Plugin meta display array.
	 * @param   string  $plugin_file  Plugin reference.
	 * @param   array   $plugin_data  Plugin data.
	 * @param   string  $status       Plugin status.
	 * @return  array                 Plugin meta array.
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {

		if ( 'password-protected/password-protected.php' == $plugin_file ) {
			$plugin_meta[] = sprintf( '<a href="%s">%s</a>', __( 'http://github.com/benhuson/password-protected', 'password-protected' ), __( 'GitHub', 'password-protected' ) );
			$plugin_meta[] = sprintf( '<a href="%s">%s</a>', __( 'https://translate.wordpress.org/projects/wp-plugins/password-protected', 'password-protected' ), __( 'Translate', 'password-protected' ) );
		}

		return $plugin_meta;

	}

	/**
	 * Plugin Action Links
	 *
	 * Adds settings link on the plugins page.
	 *
	 * @param   array  $actions  Plugin action links array.
	 * @return  array            Plugin action links array.
	 */
	public function plugin_action_links( $actions ) {

		$actions[] = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=password-protected' ), __( 'Settings', 'password-protected' ) );
		return $actions;

	}

	/**
	 * Password Admin Notice
	 * Warns the user if they have enabled password protection but not entered a password
	 */
	public function password_protected_admin_notices() {
		global $Password_Protected;

		// Check Support
		$screens = $this->plugin_screen_ids( array( 'dashboard', 'plugins' ) );
		if ( $this->is_current_screen( $screens ) ) {
			$supported = $Password_Protected->is_plugin_supported();

			if ( is_wp_error( $supported ) ) {
				echo $this->admin_error_display( $supported->get_error_message( $supported->get_error_code() ) );
			}
		}

		// Settings
		if ( $this->is_current_screen( $this->plugin_screen_ids() ) ) {
			$status = get_option( 'password_protected_status' );
			$pwd = get_option( 'password_protected_password' );

			if ( (bool) $status && empty( $pwd ) ) {
				$error_message = __( 'You have enabled password protection but not yet set a password. Please set one below.', 'password-protected' );
				$error = apply_filters( 'password_protected_password_status_activation', $error_message );
				if( !empty( $error ) ) {
					echo $this->admin_error_display( $error );
				}
			}

			if ( current_user_can( 'manage_options' ) && ( (bool) get_option( 'password_protected_administrators' ) || (bool) get_option( 'password_protected_users' ) ) ) {
				if ( (bool) get_option( 'password_protected_administrators' ) && (bool) get_option( 'password_protected_users' ) ) {
					echo $this->admin_error_display( __( 'You have enabled password protection and allowed administrators and logged in users - other users will still need to enter a password to view the site.', 'password-protected' ) );
				} elseif ( (bool) get_option( 'password_protected_administrators' ) ) {
					if ( (bool) get_option( 'password_protected_status' ) ) {
						echo $this->admin_error_display( __( 'You have enabled password protection and allowed administrators - other users will still need to enter a password to view the site.', 'password-protected' ) );
					}
				} elseif ( (bool) get_option( 'password_protected_users' ) ) {
					if ( (bool) get_option( 'password_protected_status' ) ) {
						echo $this->admin_error_display( __( 'You have enabled password protection and allowed logged in users - other users will still need to enter a password to view the site.', 'password-protected' ) );
					}
				}
			}

		}

	}

	/**
	 * Admin Error Display
	 *
	 * Returns a string wrapped in HTML to display an admin error.
	 *
	 * @param   string  $string  Error string.
	 * @return  string           HTML error.
	 */
	private function admin_error_display( $string ) {

		return '<div class="error"><p>' .  $string . '</p></div>';

	}

	/**
	 * Is Current Screen
	 *
	 * Checks wether the admin is displaying a specific screen.
	 *
	 * @param   string|array  $screen_id  Admin screen ID(s).
	 * @return  boolean
	 */
	public function is_current_screen( $screen_id ) {

		if ( function_exists( 'get_current_screen' ) ) {
			$current_screen = get_current_screen();
			if ( ! is_array( $screen_id ) ) {
				$screen_id = array( $screen_id );
			}
			if ( in_array( $current_screen->id, $screen_id ) ) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Plugin Screen IDs
	 *
	 * @param   string|array  $screen_id  Additional screen IDs to add to the returned array.
	 * @return  array                     Screen IDs.
	 */
	public function plugin_screen_ids( $screen_id = '' ) {

		$screen_ids = array( 'options-' . $this->options_group, 'settings_page_' . $this->options_group );
		array_push( $screen_ids, 'toplevel_page_'.$this->options_group );
		if ( ! empty( $screen_id ) ) {
			if ( is_array( $screen_id ) ) {
				$screen_ids = array_merge( $screen_ids, $screen_id );
			} else {
				$screen_ids[] = $screen_id;
			}
		}
		// toplevel_page_password-protected
		return $screen_ids;

	}

	/**
	 * @return  bool
	 * true if password protected pro is installed and activated otherwise false
	 */
	public function password_protected_pro_is_installed_and_activated(): bool {
		return class_exists( 'Password_Protected_Pro' );
	}

	public function login_designer_is_installed_and_activated() {
		return class_exists( 'Login_designer' );
	}

	/**
	 * @return  void
	 * Display Pro Features
	 */
	public function password_protected_get_pro_features() {
		$image_url = PASSWORD_PROTECTED_URL . 'assets/images/';
		echo '<div class="pp-pro-banner">
            <div class="pp-container">

				<div class="pp-banner-header">
					<div class="pp-row">
						<div class="pp-crown-icon">
							<img src="' . $image_url . 'pro-crown.png" />
						</div>
						<div>
							<div class="pp-head-wt-pro-tag">
								<p class="pp-sm-txt-heading">Unlock Premium Content Protection Features with</p> 
								<p class="pp-pro-tag">
									PRO
								</p>
							</div>
							<p class="heading-1">Password Protected</p>
						</div>
					</div>
				</div>
                
                <div class="pp-banner-body">
                    <div class="pp-cols">

						<div class="pp-features-list-banner">
                            <img src="' . $image_url . 'pro-feature-lock.png">
                            <p>
								<a target="_blank" href="https://passwordprotectedwp.com/docs/post-and-page-protection/how-to-secure-all-posts-and-pages/?utm_source=plugin&utm_medium=pro_tab">
									Protect Custom Post Types
								</a>
							</p>
                        </div>

						<div class="pp-features-list-banner">
                            <img src="' . $image_url . 'pro-feature-lock.png">
                            <p>
								<a target="_blank" href="https://passwordprotectedwp.com/docs/bypass-url/?utm_source=plugin&utm_medium=pro_tab">
									Create Secure Bypass Links
								</a>
							</p>
                        </div>

						<div class="pp-features-list-banner">
                            <img src="' . $image_url . 'pro-feature-lock.png">
                            <p>
								<a target="_blank" href="https://passwordprotectedwp.com/docs/post-and-page-protection/?utm_source=plugin&utm_medium=pro_tab">
									Lock Specific Posts & Pages
								</a>
							</p>
                        </div>

						<div class="pp-features-list-banner">
                            <img src="' . $image_url . 'pro-feature-lock.png">
                            <p>
								<a target="_blank" href="https://passwordprotectedwp.com/docs/pro/manage-multiple-websites/?utm_source=plugin&utm_medium=pro_tab">
									Manage Unlimited Passwords
								</a>
							</p>
                        </div>

						<div class="pp-features-list-banner">
                            <img src="' . $image_url . 'pro-feature-lock.png">
                            <p>
								<a target="_blank" href="https://passwordprotectedwp.com/docs/pro/limit-password-attempts-and-lockdown-time/?utm_source=plugin&utm_medium=pro_tab">
									Limit Login Attempts
								</a>
							</p>
                        </div>

						<div class="pp-features-list-banner">
                            <img src="' . $image_url . 'pro-feature-lock.png">
                            <p>
								<a target="_blank" href="https://passwordprotectedwp.com/docs/integration/?utm_source=plugin&utm_medium=pro_tab">
									hCaptcha & Cloudflare Turnstile
								</a>
							</p>
                        </div>

						<div class="pp-features-list-banner">
                            <img src="' . $image_url . 'pro-feature-lock.png">
                            <p>
								<a target="_blank" href="https://passwordprotectedwp.com/docs/pro/customize-your-password-protected-screen/?utm_source=plugin&utm_medium=pro_tab">
									Lock Screen Customization
								</a>
							</p>
                        </div>
                        
                    </div>
                    <div class="pp-cols pp-cols-section-2">

						<div class="pp-features-list-banner">
                            <img src="' . $image_url . 'pro-feature-lock.png">
                            <p>
								<a target="_blank" href="https://passwordprotectedwp.com/docs/pro/partial-content-protection/?utm_source=plugin&utm_medium=pro_tab">
									Partial Content Protection
								</a>
							</p>
                        </div>

						<div class="pp-features-list-banner">
                            <img src="' . $image_url . 'pro-feature-lock.png">
                            <p>
								<a target="_blank" href="https://passwordprotectedwp.com/docs/pro/password-protect-wp-admin/?utm_source=plugin&utm_medium=pro_tab">
									Protect WordPress Login Page
								</a>
							</p>
                        </div>

						<div class="pp-features-list-banner">
                            <img src="' . $image_url . 'pro-feature-lock.png">
                            <p>
								<a target="_blank" href="https://passwordprotectedwp.com/docs/pro/exclude-pages-posts-and-post-types/?utm_source=plugin&utm_medium=pro_tab">
									Exclude Specific Page, Post, & Product
								</a>
							</p>
                        </div>

						<div class="pp-features-list-banner">
                            <img src="' . $image_url . 'pro-feature-lock.png">
                            <p>
								<a target="_blank" href="https://passwordprotectedwp.com/documentation/?utm_source=plugin&utm_medium=pro_tab">
									Set Expiration & Usage Limits
								</a>
							</p>
                        </div>

						<div class="pp-features-list-banner">
                            <img src="' . $image_url . 'pro-feature-lock.png">
                            <p>
								<a target="_blank" href="https://passwordprotectedwp.com/docs/pro/whitelist-specific-user-roles/?utm_source=plugin&utm_medium=pro_tab">
									Whitelist User Roles
								</a>
							</p>
                        </div>

						<div class="pp-features-list-banner">
                            <img src="' . $image_url . 'pro-feature-lock.png">
                            <p>
								<a target="_blank" href="https://passwordprotectedwp.com/docs/pro/password-activity-logs/?utm_source=plugin&utm_medium=pro_tab">
									Track Password Activity
								</a>
							</p>
                        </div>

						<div class="pp-features-list-banner">
                            <img src="' . $image_url . 'pro-feature-lock.png">
                            <p>
								<a target="_blank" href="https://passwordprotectedwp.com/docs/pro/request-access-password/?utm_source=plugin&utm_medium=pro_tab">
									Password Access Request
								</a>
							</p>
                        </div>
                        
                    </div>
                </div>
                
                <div class="pp-banner-footer">
                    <a target="_blank" href="https://passwordprotectedwp.com/pricing/?utm_source=plugin&utm_medium=pro_tab&utm_campaign=plugin">' . esc_html__( 'Upgrade to Premium', 'password-protected' ) . '</a>
                </div>
            </div>
        </div>';
	}

	public function dummy_content( $k ) {
		require plugin_dir_path( __FILE__ ) . '../templates/admin/dummy-content.php';
	}

	public function cache_related_issue() {
		echo '<form action="options.php" method="post">';
		do_settings_sections( 'password-protected&tab=advanced&sub-tab=cache-issue' );
		settings_fields( 'password_protected_cache_issue' );
		submit_button();
		echo '</form>';
	}

}
