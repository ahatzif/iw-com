<?php

use WCML\COT\Helper as COTHelper;

class WCML_Multi_Currency_Reports {

	const TOP_SELLER_QUERY_USE_SELECT_ORDERS_FROM = '8.8.0';

	private $woocommerce_wpml;
	private $wpdb;

	protected $reports_currency;

	public function __construct( woocommerce_wpml $woocommerce_wpml, wpdb $wpdb ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->wpdb             = $wpdb;
	}

	public function add_hooks() {

		add_action( 'init', [ $this, 'reports_init' ] );

		if ( is_admin() ) {

			add_action( 'wp_ajax_wcml_reports_set_currency', [ $this, 'set_reports_currency' ] );

			add_action( 'wc_reports_tabs', [ $this, 'reports_currency_selector' ] );

			if ( current_user_can( 'view_woocommerce_reports' ) ||
			     current_user_can( 'manage_woocommerce' ) ||
			     current_user_can( 'publish_shop_orders' )
			) {
				add_filter( 'woocommerce_dashboard_status_widget_top_seller_query', [
					$this,
					'filterDashboardstatusWidgetTopSellerQuery'
				] );
			}

			add_action( 'current_screen', [ $this, 'admin_screen_loaded' ], 10, 1 );
		}
	}

	public function admin_screen_loaded( $screen ) {

		if ( $screen->id === 'dashboard' ) {
			add_filter( 'woocommerce_reports_get_order_report_query', [
				$this,
				'filterOrdersAsPostsByCurrencyPostmeta'
			] );
		}

	}

	public function reports_init() {

		$isReportsPage = isset( $_GET['page'] ) && 'wc-reports' === $_GET['page'];

		if( $isReportsPage || \WCML\Rest\Functions::isRestApiRequest() ){
			add_filter( 'woocommerce_reports_get_order_report_query', [ $this, 'admin_reports_query_filter' ] );
		}

		if ( $isReportsPage ) {

			$wcml_reports_set_currency_nonce  = esc_js( wp_create_nonce( 'reports_set_currency' ) );
			$wcml_reports_set_currency_script = <<<JS
                jQuery('#dropdown_shop_report_currency').on('change', function(){
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'post',
                        data: {
                            action: 'wcml_reports_set_currency',
                            currency: jQuery('#dropdown_shop_report_currency').val(),
                            wcml_nonce: '$wcml_reports_set_currency_nonce'
                            },
                        success: function( response ){
                            if(typeof response.error !== 'undefined'){
                                alert(response.error);
                            }else{
                               window.location = window.location.href;
                            }
                        }
                    })
                });
JS;

			$handle = 'wcml_reports_set_currency_dropdown';
			wp_register_script( $handle, '', [ 'jquery' ], WCML_VERSION, true );
			wp_enqueue_script( $handle );
			wp_add_inline_script( $handle, $wcml_reports_set_currency_script );

			$this->reports_currency = $_COOKIE['_wcml_reports_currency'] ?? wcml_get_woocommerce_currency_option();

			add_filter( 'woocommerce_currency_symbol', [ $this, '_set_reports_currency_symbol' ] );
		}
	}

	public function admin_reports_query_filter( $query ) {

		if( \WCML\Rest\Functions::isRestApiRequest() ) {
			$this->reports_currency = $this->woocommerce_wpml->multi_currency->get_rest_currency();
		}

		if( !$this->reports_currency ){
			return $query;
		}

		$query['join']  .= " LEFT JOIN {$this->wpdb->postmeta} AS meta_order_currency ON meta_order_currency.post_id = posts.ID ";
		$query['where'] .= sprintf( " AND meta_order_currency.meta_key='_order_currency' AND meta_order_currency.meta_value = '%s' ",
			$this->reports_currency );

		return $query;
	}

	public function _set_reports_currency_symbol( $currency ) {
		static $no_recur = false;
		if ( ! empty( $this->reports_currency ) && empty( $no_recur ) ) {
			$no_recur = true;
			$currency = get_woocommerce_currency_symbol( $this->reports_currency );
			$no_recur = false;
		}

		return $currency;
	}

	public function set_reports_currency() {

		$nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'reports_set_currency' ) ) {
			echo json_encode( [ 'error' => __( 'Invalid nonce', 'woocommerce-multilingual' ) ] );
			die();
		}

		$cookie_name = '_wcml_reports_currency';
		setcookie( $cookie_name, filter_input( INPUT_POST, 'currency', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
			time() + 86400, COOKIEPATH, COOKIE_DOMAIN );

		exit;

	}

	public function reports_currency_selector() {
		$currency_codes = $this->woocommerce_wpml->multi_currency->get_currency_codes();
		$currencies     = get_woocommerce_currencies();

		remove_filter( 'woocommerce_currency_symbol', [ $this, '_set_reports_currency_symbol' ] );
		?>
        <select id="dropdown_shop_report_currency" style="margin-left:5px;">
			<?php if ( empty( $currency_codes ) ): ?>
                <option value=""><?php _e( 'Currency - no orders found', 'woocommerce-multilingual' ) ?></option>
			<?php else: ?>
				<?php foreach ( $currency_codes as $currency ): ?>
                    <option value="<?php echo esc_attr( $currency ) ?>" <?php selected( $currency, $this->reports_currency ); ?>>
						<?php printf( "%s (%s)", $currencies[ $currency ], get_woocommerce_currency_symbol( $currency ) ) ?>
                    </option>
				<?php endforeach; ?>
			<?php endif; ?>
        </select>
		<?php

		add_filter( 'woocommerce_currency_symbol', [ $this, '_set_reports_currency_symbol' ] );
	}

	public function filterOrdersAsPostsByCurrencyPostmeta( $query, $tableAlias = 'posts' ) {

		$currency = $this->woocommerce_wpml->multi_currency->admin_currency_selector->get_cookie_dashboard_currency();

		$query['join']  .= " INNER JOIN {$this->wpdb->postmeta} AS currency_postmeta ON {$tableAlias}.ID = currency_postmeta.post_id";
		$query['where'] .= $this->wpdb->prepare( " AND currency_postmeta.meta_key = '_order_currency' AND currency_postmeta.meta_value = %s", $currency );

		return $query;
	}

	public function filterDashboardstatusWidgetTopSellerQuery( $query ) {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, self::TOP_SELLER_QUERY_USE_SELECT_ORDERS_FROM, '<' ) ) {
			return $this->filterOrdersAsPostsByCurrencyPostmeta( $query );
		}

		if ( false === COTHelper::isUsageEnabled() ) {
			return $this->filterOrdersAsPostsByCurrencyPostmeta( $query, 'orders' );
		}

		$query['where'] .= $this->wpdb->prepare( " AND orders.currency = %s", $this->woocommerce_wpml->multi_currency->admin_currency_selector->get_cookie_dashboard_currency() );

		return $query;
	}

}
