<?php

namespace WCML\Multicurrency\WpQueryMcPrice;

class PriceFilteringByPostMeta extends AbstractPriceByPostMeta implements \IWPML_Backend_Action {

	public function add_hooks() {
		if ( $this->detectProductFilteringByPriceInMultiCurrency() ) {
			add_filter( 'pre_get_posts', [ $this, 'pre_get_posts' ] );
			add_filter( 'posts_clauses', [ $this, 'posts_clauses_wcml_price_filter_post_meta' ], 10, 2 );
		}
	}

	private function detectProductFilteringByPriceInMultiCurrency(): bool {
		if ( ! isset( $_GET['min_price'] ) && ! isset( $_GET['max_price'] ) ) {
			return false;
		}

		return $this->default_currency !== $this->client_currency;
	}

	public function searchAndRemoveMetaQueryPrice( $needle, &$haystack, &$matches ): bool {
		if ( ! is_array( $haystack ) ) {
			return false;
		}

		if ( isset( $haystack['key'] ) ) {
			if ( $haystack['key'] === $needle ) {
				$matches[] = $haystack;

				return true;
			}

			return false;
		}

		foreach ( $haystack as $key => &$row ) {
			if ( $this->searchAndRemoveMetaQueryPrice( $needle, $row, $matches ) ) {
				unset( $haystack[ $key ] );
			}
		}

		return false;
	}

	public function pre_get_posts( $wp_query ) {
		if ( isset( $wp_query->query['post_type'] ) && $wp_query->query['post_type'] === 'product' ) {
			if ( isset( $wp_query->query['meta_query'] ) ) {
				$needle = '_price';

				$matchesQuery     = [];
				$matchesQueryVars = [];
				$this->searchAndRemoveMetaQueryPrice( $needle, $wp_query->query['meta_query'], $matchesQuery );
				$this->searchAndRemoveMetaQueryPrice( $needle, $wp_query->query_vars['meta_query'], $matchesQueryVars );
				$wp_query->wcml_filter_price = $this->findMinMax( $matchesQuery );
			} else {
				$query = [];
				if ( $wp_query->query['min_price'] ) {
					$query['min'] = floatval( $wp_query->query['min_price'] );
					unset( $wp_query->query['min_price'] );
					unset( $wp_query->query_vars['min_price'] );
				}

				if ( $wp_query->query['max_price'] ) {
					$query['max'] = floatval( $wp_query->query['max_price'] );
					unset( $wp_query->query['max_price'] );
					unset( $wp_query->query_vars['max_price'] );
				}
				$wp_query->wcml_filter_price = $query;
			}
		}

		return $wp_query;
	}

	public function posts_clauses_wcml_price_filter_post_meta( $clauses, $wp_query ) {
		if ( empty( $wp_query->wcml_filter_price ) || ! is_array( $wp_query->wcml_filter_price ) ) {
			return $clauses;
		}

		$min_price_in_MC = $wp_query->wcml_filter_price['min'] ?? null;
		$max_price_in_MC = $wp_query->wcml_filter_price['max'] ?? null;

		$min_price_in_default_currency = $this->woocommerce_wpml->multi_currency->prices->unconvert_price_amount( $min_price_in_MC );
		$max_price_in_default_currency = $this->woocommerce_wpml->multi_currency->prices->unconvert_price_amount( $max_price_in_MC );

		$clauses['join'] = $this->buildWCMLMultiCurrencyQueryJoin( $clauses['join'] );

		if ( ! is_null( $min_price_in_MC ) ) {
			$min_searchCustomPriceOn  = "( ( CAST(" . self::WCML_CUSTOM_PRICES_STATUS_ALIAS . ".meta_value AS SIGNED) = '1') AND ( CAST(" . self::WCML_MC_PRICE_ALIAS . ".meta_value AS decimal(19,4)) >= '{$min_price_in_MC}') ) ";
			$min_searchCustomPriceOff = "( ( " . self::WCML_CUSTOM_PRICES_STATUS_ALIAS . ".meta_value IS NULL OR CAST(" . self::WCML_CUSTOM_PRICES_STATUS_ALIAS . ".meta_value AS SIGNED) <> '1') AND ( CAST(" . self::WCML_PRICE_ALIAS . ".meta_value AS decimal(19,4)) >= '{$min_price_in_default_currency}') )";

			$clauses['where'] .= ' AND ( ' . $min_searchCustomPriceOn . ' OR ' . $min_searchCustomPriceOff . ' )';
		}

		if ( ! is_null( $max_price_in_MC ) ) {
			$max_searchCustomPriceOn  = "( ( CAST(" . self::WCML_CUSTOM_PRICES_STATUS_ALIAS . ".meta_value AS SIGNED) = '1') AND ( CAST(" . self::WCML_MC_PRICE_ALIAS . ".meta_value AS decimal(19,4)) <= '{$max_price_in_MC}') ) ";
			$max_searchCustomPriceOff = "( ( " . self::WCML_CUSTOM_PRICES_STATUS_ALIAS . ".meta_value IS NULL OR CAST(" . self::WCML_CUSTOM_PRICES_STATUS_ALIAS . ".meta_value AS SIGNED) <> '1') AND ( CAST(" . self::WCML_PRICE_ALIAS . ".meta_value AS decimal(19,4)) <= '{$max_price_in_default_currency}') )";

			$clauses['where'] .= ' AND ( ' . $max_searchCustomPriceOn . ' OR ' . $max_searchCustomPriceOff . ' )';
		}

		return $clauses;
	}

	public function findMinMax( array $wcml_filter_price ): array {
		$result = [
			'min' => null,
			'max' => null,
		];

		foreach ( $wcml_filter_price as $meta ) {
			if ( isset( $meta['compare'] ) ) {
				if ( $meta['compare'] == '<=' ) {
					$result['max'] = floatval( wp_unslash( $meta['value'] ) );
				} else if ( $meta['compare'] == '>=' ) {
					$result['min'] = floatval( wp_unslash( $meta['value'] ) );
				}
			}
		}

		return $result;
	}
}
