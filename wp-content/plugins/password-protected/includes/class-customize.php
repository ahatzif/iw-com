<?php
/**
 * Class WP Customize
 *
 * @since 2.8.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Password_Protected_Customize' ) ) :
	class Password_Protected_Customize {
		private static $instance = null;

		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new Password_Protected_Customize();
			}

			return self::$instance;
		}

		private function __construct() {
			add_action( 'password_protected_tab_protected-screen_content', array( $this, 'protected_screen_customize_screen' ) );
		}

		public function protected_screen_customize_screen() {
			if ( ! class_exists( 'Password_Protected_Pro' ) && apply_filters( 'password_protected__activate_customizer', false ) ) {
				return;
			}

			$learn_more_url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'customize_tab',
					'utm_campaign' => 'plugin',
				),
				'https://passwordprotectedwp.com/docs/pro/customize-your-password-protected-screen/'
			);
			?>
			<div class="pp-customization-screen-wrapper">
				<div class="pp-customization-screen-header">
					<h1 class="pp-customization-screen-title">
						<?php esc_html_e( 'Design your protected page with the Realtime Customizer.', 'password-protected' ); ?>
					</h1>
					<p class="pp-customization-screen-desc">
						<?php
						echo wp_kses(
							sprintf(
								/* translators: %s: learn more documentation URL */
								__( 'Unlock the live customizer to design your password screen with logos, colors, fonts, backgrounds, and custom CSS — and see changes instantly. <a href="%s" class="pp-customization-screen-link" target="_blank" rel="noopener noreferrer">Learn More</a>.', 'password-protected' ),
								esc_url( $learn_more_url )
							),
							array(
								'a' => array(
									'href'   => true,
									'class'  => true,
									'target' => true,
									'rel'    => true,
								),
							)
						);
						?>
					</p>
				</div>

				<div class="pp-customization-screen-content">
					<?php $this->render_customizer_preview_mockup(); ?>
				</div>
			</div>
			<?php
		}

		private function render_customizer_preview_mockup() {
			$image_url   = PASSWORD_PROTECTED_URL . 'assets/images/customizer-screen.png';
			$pricing_url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'customize_tab',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'customizer_preview',
				),
				'https://passwordprotectedwp.com/pricing/'
			);
			?>
			<div
				class="pp-customizer-mockup click-to-display-upgrade-popup"
				data-pricing-url="<?php echo esc_url( $pricing_url ); ?>"
				role="button"
				tabindex="0"
				aria-label="<?php esc_attr_e( 'Unlock Realtime Customizer', 'password-protected' ); ?>"
			>
				<img
					class="pp-customizer-mockup__image"
					src="<?php echo esc_url( $image_url ); ?>"
					alt="<?php esc_attr_e( 'Password Protected realtime customizer preview', 'password-protected' ); ?>"
				/>
				<div class="pp-customizer-mockup__lock-badge">
					<svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M17 10H7V8C7 5.23858 9.23858 3 12 3C14.7614 3 17 5.23858 17 8V10Z" stroke="currentColor" stroke-width="1.75"/>
						<rect x="5" y="10" width="14" height="11" rx="2" stroke="currentColor" stroke-width="1.75"/>
						<circle cx="12" cy="15" r="1.5" fill="currentColor"/>
						<path d="M12 16.5V18" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
					</svg>
				</div>
			</div>
			<?php
		}
	}

	Password_Protected_Customize::get_instance();
endif;
