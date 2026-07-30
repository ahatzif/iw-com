<?php

namespace WCML\EditorScopedSync;

class HooksFactory implements \IWPML_Backend_Action_Loader {

	public function create() {
		global $woocommerce_wpml, $wpdb;

		$mode = new Mode( $woocommerce_wpml );

		return [
			new EditorChangeTracker(),
			new SyncGate( $mode ),
			new ForceUpdateEndpoint(),
			new Settings( $mode ),
			new Notices( $mode, $woocommerce_wpml, $wpdb ),
		];
	}
}
