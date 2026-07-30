<?php

namespace WCML\EditorScopedSync;

class SyncGate implements \IWPML_Backend_Action {

	private $mode;

	public function __construct( Mode $mode ) {
		$this->mode = $mode;
	}

	public function add_hooks() {
		add_filter( 'wcml_editor_scoped_variation_ids', [ $this, 'narrowVariationIds' ], 10, 2 );
	}

	public function narrowVariationIds( $current, $productId ) {
		if ( $this->mode->isEditorScoped() ) {
			return EditorChangeTracker::editedVariationIdsFor( $productId );
		}
		return $current;
	}
}
