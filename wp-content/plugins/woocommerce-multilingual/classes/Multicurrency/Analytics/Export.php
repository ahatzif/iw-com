<?php

namespace WCML\Multicurrency\Analytics;

use WPML\FP\Obj;

abstract class Export implements \IWPML_Backend_Action, \IWPML_REST_Action, \IWPML_DIC_Action {

	const COL_LANGUAGE = 'language';
	const COL_CURRENCY = 'currency';

	protected $wpdb;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function add_hooks() {
		add_filter( 'woocommerce_analytics_clauses_join_orders_subquery', [ $this, 'addJoinClauses' ] );
		add_filter( 'woocommerce_analytics_clauses_select_orders_subquery', [ $this, 'addSelectClauses' ] );
		add_filter( 'woocommerce_analytics_clauses_join_products_subquery', [ $this, 'addJoinClauses' ] );
		add_filter( 'woocommerce_analytics_clauses_select_products_subquery', [ $this, 'addSelectClauses' ] );

		add_filter( 'woocommerce_report_orders_export_columns', $this->addColumnTitle( self::COL_LANGUAGE, __( 'Language', 'woocommerce-multilingual' ) ) );
		add_filter( 'woocommerce_report_orders_prepare_export_item', $this->copyProp( self::COL_LANGUAGE ), 10, 2 );

		if ( wcml_is_multi_currency_on() ) {
			add_filter( 'woocommerce_report_orders_export_columns', $this->addColumnTitle( self::COL_CURRENCY, __( 'Currency', 'woocommerce-multilingual' ) ) );
			add_filter( 'woocommerce_report_orders_prepare_export_item', $this->copyProp( self::COL_CURRENCY ), 10, 2 );
			add_filter( 'woocommerce_report_products_export_columns', $this->addColumnTitle( self::COL_CURRENCY, __( 'Currency', 'woocommerce-multilingual' ) ) );
			add_filter( 'woocommerce_report_products_prepare_export_item', $this->copyProp( self::COL_CURRENCY ), 10, 2 );
		}
	}

	private function addColumnTitle( $column, $title ) {
		return Obj::assoc( $column, $title );
	}

	abstract public function addJoinClauses( $clauses );

	abstract public function addSelectClauses( $clauses );

	public function copyProp( $column ) {
		return function ( $export_item, $item ) use ( $column ) {
			return Obj::assoc( $column, Obj::prop( $column, $item ), $export_item );
		};
	}
}
