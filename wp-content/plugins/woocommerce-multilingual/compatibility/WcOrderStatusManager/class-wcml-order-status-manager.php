<?php
class WCML_Order_Status_Manager implements \IWPML_Action {
	private $wp_query;

	public function __construct( WP_Query $wp_query ) {
		$this->wp_query = $wp_query;
	}

	public function add_hooks() {
		add_action( 'pre_get_posts', [ $this, 'pre_get_posts' ], 10, 1 );
	}

	public function pre_get_posts( $q = null ) {
		if ( isset( $q->query['post_type'] )
			&& 'wc_order_status' === $q->query['post_type']
			&& doing_filter( 'woocommerce_register_shop_order_post_statuses' )
		) {
			$q->set( 'post__not_in', $this->prepare_post_not_in( $q, $this->get_statuses() ) );
		}
	}

	private function get_statuses() {
		remove_action( 'pre_get_posts', [ $this, 'pre_get_posts' ], 10 );
		$this->wp_query->query(
			[
				'post_type'        => 'wc_order_status',
				'posts_per_page'   => 100,
				'suppress_filters' => false,

			]
		);
		add_action( 'pre_get_posts', [ $this, 'pre_get_posts' ], 10, 1 );
		return $this->wp_query->posts;
	}

	private function prepare_post_not_in( $q, $statuses ) {
		$post__not_in = [];

		if ( $statuses ) {
			$current_language = apply_filters( 'wpml_current_language', null );

			foreach ( $statuses as $status ) {
				$post_language_details = apply_filters( 'wpml_post_language_details', '', $status->ID );
				if ( isset( $post_language_details['language_code'] ) ) {
					if ( $post_language_details['language_code'] !== $current_language ) {
						$post__not_in[] = $status->ID;
					}
				}
			}
		}

		$post__not_in_query = isset( $q->query_vars['post__not_in'] ) ? $q->query_vars['post__not_in'] : [];
		return array_merge( $post__not_in_query, $post__not_in );
	}
}
