<?php

namespace WCML\StandAlone;

use WPML_Action_Filter_Loader;

use function WCML\functions\isStandAlone;

class ActionFilterLoader {

	private $loader;

	public function __construct( $loader = null ) {
		$this->loader = null === $loader ? new WPML_Action_Filter_Loader() : $loader;
	}

	public function load( $loaders ) {
		$this->loader->load( $this->mayBeFilterLoaders( $loaders ) );
	}

	private function mayBeFilterLoaders( $loaders ) {
		if ( isStandAlone() ) {
			$filtered_loaders = [];
			foreach ( $loaders as $loader ) {
				if ( is_subclass_of( $loader, IStandAloneAction::class ) ) {
					$filtered_loaders[] = $loader;
				}
			}
			return $filtered_loaders;
		}
		return $loaders;
	}
}
