<?php

add_action( 'init', function () {
	register_taxonomy( 'museum-location', [ 'museum' ], [
		'labels' => [
			'name'              => __( 'Museum Locations', 'iw-architecture' ),
			'singular_name'     => __( 'Museum Location', 'iw-architecture' ),
			'search_items'      => __( 'Search Museum Locations', 'iw-architecture' ),
			'all_items'         => __( 'All Museum Locations', 'iw-architecture' ),
			'parent_item'       => __( 'Parent Museum Location', 'iw-architecture' ),
			'parent_item_colon' => __( 'Parent Museum Location:', 'iw-architecture' ),
			'edit_item'         => __( 'Edit Museum Location', 'iw-architecture' ),
			'update_item'       => __( 'Update Museum Location', 'iw-architecture' ),
			'add_new_item'      => __( 'Add New Museum Location', 'iw-architecture' ),
			'new_item_name'     => __( 'New Museum Location Name', 'iw-architecture' ),
			'menu_name'         => __( 'Locations', 'iw-architecture' ),
		],
		'public'            => true,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => [
			'slug'       => 'museum-location',
			'with_front' => false,
		],
	] );
} );
