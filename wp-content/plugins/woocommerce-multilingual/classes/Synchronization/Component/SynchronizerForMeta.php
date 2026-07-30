<?php

namespace WCML\Synchronization\Component;

use WCML\Utilities\DB;

abstract class SynchronizerForMeta extends Synchronizer {

	protected function getMeta( $metaKey, $itemIds ) {
		$storedRawData = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"
				SELECT post_id, meta_value
				FROM {$this->wpdb->postmeta}
				WHERE meta_key = %s
				AND post_id IN (" . DB::prepareIn( $itemIds ) . ")
				LIMIT %d
				",
				$metaKey,
				count( $itemIds )
			),
			OBJECT_K
		);

		if ( empty( $storedRawData ) ) {
			return [];
		}

		$storedData = array_map( function( $data ) {
			return maybe_unserialize( $data->meta_value );
		}, $storedRawData );

		return $storedData;
	}

	protected function insertMeta( $metaKey, $metaValues ) {
		if ( empty( $metaValues ) ) {
			return;
		}

		$insertValues = [];
		foreach ( $metaValues as $idToInsert => $valueToInsert ) {
			$insertValues[] = $this->wpdb->prepare( "(%d,%s,%s)", $idToInsert, $metaKey, maybe_serialize( $valueToInsert ) );
		}
		$this->wpdb->query(
				"INSERT INTO {$this->wpdb->postmeta}
				(`post_id`,`meta_key`,`meta_value`)
				VALUES " . implode( ',', $insertValues )
		);
	}

	protected function updateMeta( $metaKey, $metaValues ) {
		if ( empty( $metaValues ) ) {
			return;
		}
		foreach ( $metaValues as $idToUpdate => $valueToUpdate ) {
			$this->wpdb->update(
				$this->wpdb->postmeta,
				[ 'meta_value' => maybe_serialize( $valueToUpdate ) ],
				[ 'post_id' => $idToUpdate, 'meta_key' => $metaKey ]
			);
		}
	}

	protected function deleteMetaByIds( $metaIds ) {
		if ( empty( $metaIds ) ) {
			return;
		}
		$this->wpdb->query(
			$this->wpdb->prepare(
				"
				DELETE FROM {$this->wpdb->postmeta}
				WHERE meta_id IN (" . DB::prepareIn( $metaIds ) . ")
				LIMIT %d
				",
				count( $metaIds )
			)
		);
	}

	protected function unifyMeta( $metaKey, $metaValue, $idsToUnify ) {
		if ( empty( $idsToUnify ) ) {
			return;
		}
		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->wpdb->postmeta}
				SET meta_value = %s
				WHERE meta_key = %s
				AND post_id IN (" . DB::prepareIn( $idsToUnify ) . ")",
				maybe_serialize( $metaValue ),
				$metaKey
			)
		);
	}

	protected function synchronizeMeta( $productId, $translationsIds, $metaKey ) {
		$productsIds = array_merge( [ $productId ], $translationsIds );
		$storedMeta  = $this->getMeta( $metaKey, $productsIds );
		$productMeta = $storedMeta[ $productId ] ?? null;
		if ( null === $productMeta ) {
			return;
		}

		$metaToInsert = [];
		$idsToUpdate  = [];
		foreach ( $translationsIds as $translationId ) {
			if ( ! array_key_exists( $translationId, $storedMeta ) ) {
				$metaToInsert[ $translationId ] = $productMeta;
				continue;
			}
			if ( $productMeta === $storedMeta[ $translationId ] ) {
				continue;
			}
			$idsToUpdate[] = $translationId;
		}

		$this->insertMeta( $metaKey, $metaToInsert );
		$this->unifyMeta( $metaKey, $productMeta, $idsToUpdate );
	}

	protected function spreadEmptyValue( $translationsIds, $storedData, $metaKey ) {
		$metaToInsert = [];
		$metaToUpdate = [];
		foreach ( $translationsIds as $translationId ) {
			if ( ! array_key_exists( $translationId, $storedData ) ) {
				$metaToInsert[ $translationId ] = [];
				continue;
			}
			$translationStoredData = $storedData[ $translationId ];
			if ( ! empty( $translationStoredData ) ) {
				$metaToUpdate[ $translationId ] = [];
			}
		}

		$this->insertMeta( $metaKey, $metaToInsert );
		$this->updateMeta( $metaKey, $metaToUpdate );
	}

	protected function clearTranslationsValue( $translationsIds, $metaKey ) {
		if ( empty( $translationsIds ) ) {
			return;
		}
		$this->wpdb->query(
			$this->wpdb->prepare(
				"
				DELETE FROM {$this->wpdb->postmeta}
				WHERE meta_key = %s
				AND post_id IN (" . DB::prepareIn( $translationsIds ) . ")
				LIMIT %d
				",
				$metaKey,
				count( $translationsIds )
			)
		);
	}

	protected function deleteOrphanedFields( $productId, $translationsIds ) {
		$productsIds   = array_merge( [ $productId ], $translationsIds );
		$storedRawData = $this->wpdb->get_results(
			"
			SELECT *
			FROM {$this->wpdb->postmeta}
			WHERE post_id IN (" . DB::prepareIn( $productsIds ) . ")
			"
		);

		$storedData = [];
		foreach ( $storedRawData as $rawData ) {
			$storedData[ $rawData->post_id ][ $rawData->meta_key ] = $rawData->meta_id;
		}
		$productData = $storedData[ $productId ] ?? [];

		$orphanedMetaIds = [];
		$settingsFactory = wpml_load_core_tm()->settings_factory();
		foreach ( $translationsIds as $translationId ) {
			$translationData = $storedData[ $translationId ] ?? [];
			foreach ( $translationData as $metaKey => $metaId ) {
				if ( WPML_COPY_CUSTOM_FIELD !== $settingsFactory->post_meta_setting( $metaKey )->status() ) {
					continue;
				}
				if ( ! array_key_exists( $metaKey, $productData ) ) {
					$orphanedMetaIds[] = $metaId;
				}
			}
		}

		$this->deleteMetaByIds( $orphanedMetaIds );
	}

}
