<?php

add_action( 'init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$post_types = array_merge(
		get_post_types( [ 'public' => true, '_builtin' => false ], 'names' ),
		[ 'post', 'page' ]
	);
	$post_types = apply_filters( 'iw_architecture_post_list_post_types', $post_types );
	$post_types = array_values( array_unique( array_filter( (array) $post_types ) ) );
	sort( $post_types );

	$theme_post_types = [];
	foreach ( $post_types as $post_type ) {
		if ( ! iw_architecture_post_list_should_include_post_type( $post_type ) ) {
			continue;
		}

		$post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type_object ) {
			continue;
		}

		$theme_post_types[ $post_type ] = $post_type_object->label ?: ucfirst( str_replace( [ '-', '_' ], ' ', $post_type ) );
	}
	$theme_post_types = apply_filters( 'iw_architecture_post_list_post_type_choices', $theme_post_types );

	$template_choices = iw_architecture_post_list_template_choices();
	$view_choices = [
		'default' => 'Grid',
		'slider'  => 'Slider',
	];
	if ( ! empty( $template_choices ) ) {
		$view_choices['custom'] = 'Custom Selection';
	}
	$view_choices = apply_filters( 'iw_architecture_post_list_view_choices', $view_choices, $template_choices );

	$fields = [
		[
			'key' => 'field_63cab3989a65e',
			'label' => 'Post Type',
			'name' => 'post_type_select',
			'aria-label' => '',
			'type' => 'select',
			'instructions' => '',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '33.3333',
				'class' => '',
				'id' => '',
			],
			'choices' => $theme_post_types,
			'default_value' => false,
			'return_format' => 'value',
			'multiple' => 0,
			'allow_null' => 0,
			'ui' => 0,
			'ajax' => 0,
			'placeholder' => '',
		],
		[
			'key' => 'field_65c3a34b544da',
			'label' => 'Post Selection',
			'name' => 'post_selection',
			'aria-label' => '',
			'type' => 'radio',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '33.3333',
				'class' => '',
				'id' => '',
			],
			'choices' => [
				'auto' => 'Auto',
				'manual' => 'Manual',
				'related' => 'Related',
			],
			'default_value' => '',
			'return_format' => 'value',
			'allow_null' => 0,
			'other_choice' => 0,
			'layout' => 'horizontal',
			'save_other_choice' => 0,
		],
		[
			'key' => 'field_65c3a3da544df',
			'label' => 'View',
			'name' => 'list_view_template',
			'aria-label' => '',
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'wrapper' => [
				'width' => '33.3333',
				'class' => '',
				'id' => '',
			],
			'choices' => $view_choices,
			'default_value' => false,
			'return_format' => 'value',
			'multiple' => 0,
			'allow_null' => 0,
			'ui' => 0,
			'ajax' => 0,
			'placeholder' => '',
		],
	];

	if ( ! empty( $template_choices ) ) {
		$fields[] = [
			'key' => 'field_65c3a3da544df_custom',
			'label' => 'Custom Selection',
			'name' => 'list_view_template_custom',
			'aria-label' => '',
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => 'field_65c3a3da544df',
						'operator' => '==',
						'value' => 'custom',
					],
				],
			],
			'wrapper' => [
				'width' => '33.3333',
				'class' => '',
				'id' => '',
			],
			'choices' => $template_choices,
			'default_value' => false,
			'return_format' => 'value',
			'multiple' => 0,
			'allow_null' => 0,
			'ui' => 0,
			'ajax' => 0,
			'placeholder' => '',
		];
	}

	$fields[] = [
		'key' => 'field_65c3a374544db',
		'label' => 'Select Posts',
		'name' => 'selected_posts',
		'aria-label' => '',
		'type' => 'post_object',
		'instructions' => '',
		'required' => 0,
		'conditional_logic' => [
			[
				[
					'field' => 'field_65c3a34b544da',
					'operator' => '==',
					'value' => 'manual',
				],
			],
		],
		'wrapper' => [
			'width' => '100',
			'class' => '',
			'id' => '',
		],
		'post_type' => '',
		'post_status' => 'publish',
		'taxonomy' => '',
		'return_format' => 'id',
		'multiple' => 1,
		'allow_null' => 0,
		'bidirectional' => 0,
		'ui' => 1,
		'bidirectional_target' => [],
	];

	$fields[] = [
		'key' => 'field_65c3a3a4544dc',
		'label' => 'How Many Posts',
		'name' => 'how_many',
		'aria-label' => '',
		'type' => 'number',
		'instructions' => '',
		'required' => 0,
		'conditional_logic' => [
			[
				[
					'field' => 'field_65c3a34b544da',
					'operator' => '==',
					'value' => 'auto',
				],
			],
			[
				[
					'field' => 'field_65c3a34b544da',
					'operator' => '==',
					'value' => 'related',
				],
			],
		],
		'wrapper' => [
			'width' => '',
			'class' => '',
			'id' => '',
		],
		'default_value' => 3,
		'min' => '',
		'max' => '',
		'placeholder' => '',
		'step' => '',
		'prepend' => '',
		'append' => '',
	];

	foreach ( array_keys( $theme_post_types ) as $post_type ) {
		$taxonomy_choices = [];
		foreach ( get_object_taxonomies( $post_type, 'objects' ) as $taxonomy_name => $taxonomy ) {
			if ( 'post_format' === $taxonomy_name ) {
				continue;
			}

			$taxonomy_choices[ $taxonomy_name ] = $taxonomy->label;
			$fields[] = [
				'key' => 'field_65c5154d17661_' . $post_type . '_' . $taxonomy_name,
				'label' => $taxonomy->label,
				'name' => $post_type . '_' . $taxonomy_name,
				'aria-label' => '',
				'type' => 'taxonomy',
				'conditional_logic' => [
					[
						[
							'field' => 'field_63cab3989a65e',
							'operator' => '==',
							'value' => $post_type,
						],
						[
							'field' => 'field_65c3a34b544da',
							'operator' => '!=',
							'value' => 'manual',
						],
						[
							'field' => 'field_65c3a34b544da',
							'operator' => '!=',
							'value' => 'related',
						],
					],
				],
				'wrapper' => [
					'width' => '33.3333',
					'class' => '',
					'id' => '',
				],
				'taxonomy' => $taxonomy_name,
				'add_term' => 0,
				'return_format' => 'id',
				'field_type' => 'multi_select',
				'allow_null' => 1,
				'bidirectional' => 0,
				'multiple' => 0,
			];
		}

		if ( empty( $taxonomy_choices ) ) {
			continue;
		}

		$fields[] = [
			'key' => 'field_6571a0a75f781_' . $post_type . '_filters',
			'label' => 'Main Taxonomy',
			'name' => $post_type . '_taxonomy',
			'aria-label' => '',
			'type' => 'select',
			'instructions' => '',
			'required' => 1,
			'conditional_logic' => [
				[
					[
						'field' => 'field_63cab3989a65e',
						'operator' => '==',
						'value' => $post_type,
					],
					[
						'field' => 'field_65c3a34b544da',
						'operator' => '!=',
						'value' => 'manual',
					],
				],
			],
			'wrapper' => [
				'width' => '33.3333',
				'class' => '',
				'id' => '',
			],
			'choices' => array_merge( [ '' => 'No' ], $taxonomy_choices ),
			'default_value' => false,
			'return_format' => 'value',
			'multiple' => 1,
			'allow_null' => 0,
			'ui' => 0,
			'ajax' => 0,
			'placeholder' => '',
		];
	}

	$fields[] = [
		'key' => 'field_65c655c5135c7',
		'label' => 'Load More Button',
		'name' => 'has_load_more_button',
		'aria-label' => '',
		'type' => 'true_false',
		'instructions' => '',
		'required' => 0,
		'conditional_logic' => [
			[
				[
					'field' => 'field_65c3a34b544da',
					'operator' => '!=',
					'value' => 'manual',
				],
			],
		],
		'wrapper' => [
			'width' => '33.33333',
			'class' => '',
			'id' => '',
		],
		'message' => '',
		'default_value' => 1,
		'ui_on_text' => '',
		'ui_off_text' => '',
		'ui' => 1,
	];

	$fields[] = [
		'key' => 'field_65c655c5135c7_filters',
		'label' => 'Show Filters',
		'name' => 'show_filters',
		'aria-label' => '',
		'type' => 'true_false',
		'instructions' => '',
		'required' => 0,
		'conditional_logic' => [
			[
				[
					'field' => 'field_65c3a34b544da',
					'operator' => '==',
					'value' => 'auto',
				],
			],
		],
		'wrapper' => [
			'width' => '33.33333',
			'class' => '',
			'id' => '',
		],
		'message' => '',
		'default_value' => 1,
		'ui_on_text' => '',
		'ui_off_text' => '',
		'ui' => 1,
	];

	$fields[] = [
		'key' => 'field_65c655c5135c7890_filters',
		'label' => 'Allow Multiple',
		'name' => 'allow_multiple',
		'aria-label' => '',
		'type' => 'true_false',
		'instructions' => '',
		'required' => 0,
		'conditional_logic' => [
			[
				[
					'field' => 'field_65c3a34b544da',
					'operator' => '==',
					'value' => 'auto',
				],
				[
					'field' => 'field_65c655c5135c7_filters',
					'operator' => '==',
					'value' => 1,
				],
			],
		],
		'wrapper' => [
			'width' => '33.33333',
			'class' => '',
			'id' => '',
		],
		'message' => '',
		'default_value' => 0,
		'ui_on_text' => '',
		'ui_off_text' => '',
		'ui' => 1,
	];

	$fields[] = [
		'key' => 'field_65c655c5135c78901_filters',
		'label' => 'Terms Relation',
		'name' => 'terms_relation',
		'aria-label' => '',
		'type' => 'true_false',
		'instructions' => '',
		'required' => 0,
		'conditional_logic' => [
			[
				[
					'field' => 'field_65c3a34b544da',
					'operator' => '==',
					'value' => 'auto',
				],
				[
					'field' => 'field_65c655c5135c7_filters',
					'operator' => '==',
					'value' => 1,
				],
			],
		],
		'wrapper' => [
			'width' => '33.33333',
			'class' => '',
			'id' => '',
		],
		'message' => '',
		'default_value' => 0,
		'ui_on_text' => 'OR',
		'ui_off_text' => 'AND',
		'ui' => 1,
	];

	$fields = apply_filters( 'iw_architecture_post_list_fields', $fields, $theme_post_types, $template_choices );

	acf_add_local_field_group( [
		'key' => 'group_63cab3981b8d9-iw',
		'title' => 'Block: Post List Options',
		'fields' => $fields,
		'location' => [
			[
				[
					'param' => 'block',
					'operator' => '==',
					'value' => 'acf/post-list',
				],
			],
		],
		'menu_order' => -10,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => true,
		'description' => '',
		'show_in_rest' => 0,
	] );
}, 20 );

if ( ! function_exists( 'iw_architecture_post_list_should_include_post_type' ) ) {
	function iw_architecture_post_list_should_include_post_type( $post_type ) {
		if ( in_array( $post_type, [ 'post', 'page' ], true ) ) {
			return true;
		}

		if ( class_exists( 'IW_Architecture' ) && in_array( $post_type, IW_Architecture::$post_type_names, true ) ) {
			return true;
		}

		return (bool) apply_filters( 'iw_architecture_post_list_include_external_post_type', false, $post_type );
	}
}

if ( ! function_exists( 'iw_architecture_post_list_template_choices' ) ) {
	function iw_architecture_post_list_template_choices() {
		$choices = [];
		$views = glob( trailingslashit( get_template_directory() ) . 'templates/parts/post-list/*.php' );

		foreach ( is_array( $views ) ? $views : [] as $file_path ) {
			$file_name = pathinfo( $file_path, PATHINFO_FILENAME );
			if (
				0 === strpos( $file_name, 'item' )
				|| 0 === strpos( $file_name, 'filter' )
				|| 0 === strpos( $file_name, 'paging' )
			) {
				continue;
			}

			$choices[ $file_name ] = ucfirst( str_replace( '-', ' ', $file_name ) );
		}

		return apply_filters( 'iw_architecture_post_list_template_choices', $choices );
	}
}
