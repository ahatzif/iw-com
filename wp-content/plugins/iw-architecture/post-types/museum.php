<?php

add_action( 'init', function () {
	register_post_type( 'museum', [
		'labels' => [
			'name'                  => __( 'Museums', 'iw-architecture' ),
			'singular_name'         => __( 'Museum', 'iw-architecture' ),
			'add_new'               => __( 'Add New', 'iw-architecture' ),
			'add_new_item'          => __( 'Add New Museum', 'iw-architecture' ),
			'edit_item'             => __( 'Edit Museum', 'iw-architecture' ),
			'new_item'              => __( 'New Museum', 'iw-architecture' ),
			'view_item'             => __( 'View Museum', 'iw-architecture' ),
			'search_items'          => __( 'Search Museums', 'iw-architecture' ),
			'not_found'             => __( 'No museums found', 'iw-architecture' ),
			'not_found_in_trash'    => __( 'No museums found in Trash', 'iw-architecture' ),
			'all_items'             => __( 'All Museums', 'iw-architecture' ),
			'menu_name'             => __( 'Museums', 'iw-architecture' ),
			'featured_image'        => __( 'Museum Image', 'iw-architecture' ),
			'set_featured_image'    => __( 'Set museum image', 'iw-architecture' ),
			'remove_featured_image' => __( 'Remove museum image', 'iw-architecture' ),
		],
		'public'        => true,
		'show_in_rest'  => true,
		'map_meta_cap'  => true,
		'menu_icon'     => 'dashicons-building',
		'menu_position' => 20,
		'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ],
		'has_archive'   => true,
		'rewrite'       => [
			'slug'       => 'museums',
			'with_front' => false,
		],
	] );
} );
