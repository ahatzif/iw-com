<?php

namespace WCML\EditorScopedSync;

use WCML\PostHog\Event\EditorScopedSyncSettingChanged;
use WPML\PostHog\Event\CaptureEvent;

class Settings implements \IWPML_Backend_Action {

	const PRIORITY_AFTER_WCML_REQUESTS = 11;

	const FIELD_SAVE_MODE = 'wcml_editor_save_mode';

	private $mode;

	public function __construct( Mode $mode ) {
		$this->mode = $mode;
	}

	public function add_hooks() {
		add_action( 'wcml_settings_before_synchronization', [ $this, 'renderSection' ] );

		if ( $this->isWcmlSettingsSubmit() ) {
			add_action( 'init', [ $this, 'maybeSaveOnWcmlSubmit' ], self::PRIORITY_AFTER_WCML_REQUESTS );
		}
	}

	private function isWcmlSettingsSubmit() {
		return ! empty( $_POST['wcml_save_settings'] );
	}

	public function maybeSaveOnWcmlSubmit() {
		$nonce = isset( $_POST['wcml_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wcml_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wcml_save_settings_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'wpml_operate_woocommerce_multilingual' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$value = Mode::VALUE_EDITOR_SCOPED === sanitize_text_field( wp_unslash( $_POST[ self::FIELD_SAVE_MODE ] ?? '' ) )
			? Mode::VALUE_EDITOR_SCOPED
			: Mode::VALUE_COMPLETE;

		if ( $value === $this->mode->current() ) {
			return;
		}

		$this->mode->set( $value );

		try {
			CaptureEvent::capture(
				new EditorScopedSyncSettingChanged( [ 'mode' => $value ] )
			);
		} catch ( \Throwable $e ) {
		}
	}

	public function renderSection() {
		$mode = $this->mode->current();
		?>
		<div class="wcml-section">
			<div class="wcml-section-header">
				<h3>
					<?php esc_html_e( 'Variation translation sync on product save', 'woocommerce-multilingual' ); ?>
					<i class="otgs-ico-help wcml-tip" data-tip="<?php echo esc_attr__( 'Choose how WPML updates variation translations when you save a variable product. Affects saves in the WooCommerce product editor only — Translation Dashboard and Translate Everything Automatically always run a complete sync.', 'woocommerce-multilingual' ); ?>"></i>
				</h3>
			</div>
			<div class="wcml-section-content">
				<p style="margin:0 0 8px;color:#3c434a;">
					<?php esc_html_e( 'Choose how WPML updates variation translations when you save a variable product.', 'woocommerce-multilingual' ); ?>
				</p>
				<p style="margin:0 0 12px;color:#646970;font-style:italic;font-size:13px;">
					<?php esc_html_e( 'Affects saves in the WooCommerce product editor only. Translation Dashboard and Translate Everything Automatically always run a complete sync.', 'woocommerce-multilingual' ); ?>
				</p>
				<ul>
					<li>
						<input type="radio" name="<?php echo esc_attr( self::FIELD_SAVE_MODE ); ?>"
							value="<?php echo esc_attr( Mode::VALUE_COMPLETE ); ?>"
							<?php checked( $mode, Mode::VALUE_COMPLETE ); ?>
							id="wcml_editor_save_mode_complete" />
						<label for="wcml_editor_save_mode_complete">
							<strong><?php esc_html_e( 'Always check all variations', 'woocommerce-multilingual' ); ?></strong><br/>
							<span style="color:#3c434a;">
								<?php esc_html_e( "On every save, WPML checks every variation in every language and syncs anything that's out of date.", 'woocommerce-multilingual' ); ?>
							</span>
						</label>
					</li>
					<li>
						<input type="radio" name="<?php echo esc_attr( self::FIELD_SAVE_MODE ); ?>"
							value="<?php echo esc_attr( Mode::VALUE_EDITOR_SCOPED ); ?>"
							<?php checked( $mode, Mode::VALUE_EDITOR_SCOPED ); ?>
							id="wcml_editor_save_mode_editor_scoped" />
						<label for="wcml_editor_save_mode_editor_scoped">
							<strong><?php esc_html_e( 'Only sync variations edited in the editor', 'woocommerce-multilingual' ); ?></strong><br/>
							<span style="color:#3c434a;">
								<?php esc_html_e( 'WPML syncs only the variations changed in this save. After each save, WPML shows what was synced and offers a one-click full sync.', 'woocommerce-multilingual' ); ?>
							</span>
						</label>
					</li>
				</ul>
			</div>
		</div>
		<?php
	}
}

