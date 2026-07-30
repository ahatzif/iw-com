<?php

namespace WCML\Multicurrency\WpQueryMcPrice;

class PriceOrderByPostMeta extends AbstractPriceByPostMeta implements \IWPML_Backend_Action {

	const ORDER_BY_VALUE = [ 'price-desc', 'price' ];

	public function add_hooks() {
		if ( $this->detectProductOrderByPriceInMultiCurrency() ) {
			add_filter( 'pre_get_posts', [ $this, 'pre_get_posts' ] );
			add_filter( 'posts_clauses', [ $this, 'posts_clauses_wcml_order_by_price_post_meta' ], 11, 2 );
		}
	}

	private function detectProductOrderByPriceInMultiCurrency(): bool {
		if ( ! isset( $_GET['orderby'] ) ) {
			return false;
		}

		if ( ! in_array( $_GET['orderby'], self::ORDER_BY_VALUE ) ) {
			return false;
		}

		return $this->default_currency !== $this->client_currency;
	}

	public function pre_get_posts( $wp_query ) {
		if ( isset( $wp_query->query['orderby'] ) && in_array( $wp_query->query['orderby'], self::ORDER_BY_VALUE ) && isset( $wp_query->query_vars['wc_query'] ) && $wp_query->query_vars['wc_query'] === 'product_query' ) {
			$wp_query->wcml_orderby_price = $wp_query->query['orderby'];

			unset( $wp_query->query['orderby'] );
			unset( $wp_query->query_vars['orderby'] );
		}

		return $wp_query;
	}

	public function posts_clauses_wcml_order_by_price_post_meta( $clauses, $wp_query ) {
		if ( empty( $wp_query->wcml_orderby_price ) ) {
			return $clauses;
		}

		$exchange_rates = $this->woocommerce_wpml->multi_currency->get_exchange_rates();
		if ( ! isset( $exchange_rates[ $this->client_currency ] ) ) {
			return $clauses;
		}
		$exchange_rate = $exchange_rates[ $this->client_currency ];

		$clauses['join'] = $this->buildWCMLMultiCurrencyQueryJoin( $clauses['join'] );

		$orderBy = "CASE " . self::WCML_CUSTOM_PRICES_STATUS_ALIAS . ".meta_value ";
		$orderBy .= "WHEN '1' THEN CAST(" . self::WCML_MC_PRICE_ALIAS . ".meta_value AS decimal(19,4)) ";
		$orderBy .= "ELSE ( CAST(" . self::WCML_PRICE_ALIAS . ".meta_value AS decimal(19,4)) * " . $exchange_rate . " ) END ";
		$orderBy .= sprintf( ' %s ', $wp_query->wcml_orderby_price == 'price' ? 'ASC' : 'DESC' );
		$orderBy .= empty( $clauses['orderby'] ) ? ' ' : ', ' . $clauses['orderby'];

		$clauses['orderby'] = $orderBy;

		return $clauses;
	}
}
