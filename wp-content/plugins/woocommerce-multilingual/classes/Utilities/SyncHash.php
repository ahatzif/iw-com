<?php

namespace WCML\Utilities;

use WPML\FP\Obj;

class SyncHash {

	const META_KEY                 = 'wcml_sync_hash';
	const HAS_CHANGES_FLAG         = 'has_changes';
	const GROUP_FIELDS             = 'postmeta_fields';
	const GROUP_TAXONOMIES         = 'taxonomies';
	const GROUP_DEFAULT_ATTRIBUTES = 'default_attributes';

	const SOURCE_META  = 'meta';
	const SOURCE_EMPTY = 'empty';

	private $hashes = [];

	private function getEmpty() {
		return [
			self::GROUP_FIELDS             => '',
			self::GROUP_TAXONOMIES         => '',
			self::GROUP_DEFAULT_ATTRIBUTES => '',
		];
	}

	private function validate( $hash ) {
		if ( is_array( $hash ) ) {
			return $hash;
		}
		return $this->getEmpty();
	}

	public function initialize( $objectId, $source, $setChanged = false ) {
		switch ( $source ) {
			case self::SOURCE_META:
				$meta                                                = get_post_meta( $objectId, self::META_KEY, true );
				$this->hashes[ $objectId ]                           = empty( $meta ) ? $this->getEmpty() : $this->validate( maybe_unserialize( $meta ) );
				$this->hashes[ $objectId ][ self::HAS_CHANGES_FLAG ] = $setChanged;
				break;
			case self::SOURCE_EMPTY:
				$this->hashes[ $objectId ]                           = $this->getEmpty();
				$this->hashes[ $objectId ][ self::HAS_CHANGES_FLAG ] = $setChanged;
				break;
		}
	}

	private function getHash( $objectId ) {
		return array_key_exists( $objectId, $this->hashes ) ? $this->validate( $this->hashes[ $objectId ] ) : $this->getEmpty();
	}

	private function setHash( $objectId, $hash ) {
		$this->hashes[ $objectId ] = $hash;
	}

	public function updateGroupValue( $objectId, $group, $hashValue ) {
		if ( $this->isNewGroupValue( $objectId, $group, $hashValue ) ) {
			$hash                           = $this->getHash( $objectId );
			$hash[ $group ]                 = $hashValue;
			$hash[ self::HAS_CHANGES_FLAG ] = true;
			$this->setHash( $objectId, $hash );
		}
	}

	public function isNewGroupValue( $objectId, $group, $hashValue ) {
		$hash = $this->getHash( $objectId );
		return Obj::propOr( null, $group, $hash ) !== $hashValue;
	}

	public function saveHash( $objectId, $clear = false ) {
		$hash       = $this->getHash( $objectId );
		$hasChanges = (bool) Obj::propOr( null, self::HAS_CHANGES_FLAG, $hash );
		if ( $hasChanges ) {
			unset( $hash[ self::HAS_CHANGES_FLAG ] );
			update_post_meta( $objectId, self::META_KEY, $hash );
		}
		if ( $clear ) {
			unset( $this->hashes[ $objectId ] );
		}
	}

}
