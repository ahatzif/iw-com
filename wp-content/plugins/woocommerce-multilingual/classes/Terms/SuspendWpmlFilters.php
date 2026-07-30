<?php

namespace WCML\Terms;

use WCML\Utilities\Suspend\Filters as SuspendFilters;
use WCML\Utilities\Suspend\Suspend;
use WPML\Collect\Support\Collection;
use function WPML\FP\spreadArgs;

class SuspendWpmlFilters implements Suspend {

	private $suspendFilters;

	private $filterArgs;

	public function __construct( SuspendFilters $suspendFilters ) {
		$this->suspendFilters = $suspendFilters;
		$this->getTaxonomyChildrenOptionFiltersArgs()->each( spreadArgs( 'add_filter' ) );
	}

	private function getTaxonomyChildrenOptionFiltersArgs() {
		$this->filterArgs = $this->filterArgs ?: wpml_collect( get_taxonomies() )
			->filter( 'is_taxonomy_translated' )
			->map( function( $taxonomy ) {
				return [ "pre_option_{$taxonomy}_children", self::getTaxonomyChildrenInAllLanguages( $taxonomy ), 20 ];
			} );

		return $this->filterArgs;
	}

	private static function getTaxonomyChildrenInAllLanguages( $taxonomy ) {
		return function() use ( $taxonomy ) {
			return get_option( "{$taxonomy}_children_all", false );
		};
	}

	public function resume() {
		$this->suspendFilters->resume();
		$this->getTaxonomyChildrenOptionFiltersArgs()->each( spreadArgs( 'remove_filter' ) );
	}

	public function runAndResume( callable $function ) {
		$result = $function();

		$this->resume();

		return $result;
	}
}
