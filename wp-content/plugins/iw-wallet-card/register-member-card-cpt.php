<?php

/**
 * Registers the `member_card` post type.
 */

Class Register_Member_Card_CPT{
    public function __construct()
    {
        add_action( 'init', [ $this, 'member_card_init' ] );
        add_filter( 'post_updated_messages', [ $this, 'member_card_updated_messages'] );
        add_filter( 'bulk_post_updated_messages', [ $this, 'member_card_bulk_updated_messages' ], 10, 2 );
        add_action( 'acf/init', [ $this, 'register_member_card_acf_fields' ] );
        add_action( 'acf/save_post', [ $this, 'ensure_single_default_member_card' ], 20 );
    }

    public function ensure_single_default_member_card( $post_id ) {

        // Only for our CPT
        if ( get_post_type( $post_id ) !== 'member-card' ) {
            return;
        }

        // Avoid autosave / revisions
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Get current value
        $is_default = get_field( 'members_default', $post_id );

        // If not set as default, nothing to do
        if ( ! $is_default ) {
            return;
        }

        // Find other member cards marked as default
        $query = new WP_Query([
            'post_type'      => 'member-card',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'post__not_in'   => [ $post_id ],
            'meta_query'     => [
                [
                    'key'   => 'members_default',
                    'value' => 1,
                ],
            ],
        ]);

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {
                // Remove default from others
                update_field( 'members_default', 0, $post->ID );
            }
        }
    }
    function member_card_init() {
        register_post_type(
            'member-card',
            [
                'labels'                => [
                    'name'                  => __( 'Member cards', 'iw-architecture' ),
                    'singular_name'         => __( 'Member card', 'iw-architecture' ),
                    'all_items'             => 'All Member cards',
                    'archives'              => 'Member card Archives',
                    'attributes'            => 'Member card Attributes',
                    'insert_into_item'      => 'Insert into member card',
                    'uploaded_to_this_item' => 'Uploaded to this member card',
                    'featured_image'        => 'Featured Image',
                    'set_featured_image'    => 'Set featured image',
                    'remove_featured_image' => 'Remove featured image',
                    'use_featured_image'    => 'Use as featured image',
                    'filter_items_list'     => 'Filter member cards list',
                    'items_list_navigation' => 'Member cards list navigation',
                    'items_list'            => 'Member cards list',
                    'new_item'              => 'New Member card',
                    'add_new'               => 'Add New',
                    'add_new_item'          => 'Add New Member card',
                    'edit_item'             => 'Edit Member card',
                    'view_item'             => 'View Member card',
                    'view_items'            => 'View Member cards',
                    'search_items'          => 'Search member cards',
                    'not_found'             => 'No member cards found',
                    'not_found_in_trash'    => 'No member cards found in trash',
                    'parent_item_colon'     => 'Parent Member card:',
                    'menu_name'             => 'Member cards',
                ],
                'public'                => false,
                'hierarchical'          => false,
                'show_ui'               => true,
                'show_in_nav_menus'     => false,
                'supports'              => [ 'title', 'excerpt', 'revisions', 'author' ],
                'has_archive'           => false,
                'rewrite'               => false,
                'query_var'             => false,
                'menu_position'         => null,
                'menu_icon'             => 'dashicons-money',
                // Keep templates available to wallet/member-card internals without
                // exposing the unused editor in the WordPress admin menu.
                'show_in_menu'          => false,
                'show_in_rest'          => true,
                'rest_base'             => 'member-card',
                'rest_controller_class' => 'WP_REST_Posts_Controller',
            ]
        );
    }

    function member_card_updated_messages( $messages ) {
        global $post;

        $permalink = get_permalink( $post );

        $messages['member-card'] = [
            0  => '', // Unused. Messages start at index 1.
            /* translators: %s: post permalink */
            1  => sprintf( 'Member card updated. <a target="_blank" href="%s">View member card</a>', esc_url( $permalink ) ),
            2  => 'Custom field updated.',
            3  => 'Custom field deleted.',
            4  => 'Member card updated.',
            /* translators: %s: date and time of the revision */
            5  => isset( $_GET['revision'] ) ? sprintf( 'Member card restored to revision from %s', wp_post_revision_title( (int) $_GET['revision'], false ) ) : false, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            /* translators: %s: post permalink */
            6  => sprintf( 'Member card published. <a href="%s">View member card</a>', esc_url( $permalink ) ),
            7  => 'Member card saved.',
            /* translators: %s: post permalink */
            8  => sprintf( 'Member card submitted. <a target="_blank" href="%s">Preview member card</a>', esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
            /* translators: 1: Publish box date format, see https://secure.php.net/date 2: Post permalink */
            9  => sprintf( 'Member card scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview member card</a>', date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ), esc_url( $permalink ) ),
            /* translators: %s: post permalink */
            10 => sprintf( 'Member card draft updated. <a target="_blank" href="%s">Preview member card</a>', esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
        ];

        return $messages;
    }

    function member_card_bulk_updated_messages( $bulk_messages, $bulk_counts ) {
        global $post;

        $bulk_messages['member-card'] = [
            /* translators: %s: Number of member cards. */
            'updated'   => ( 1 === (int) $bulk_counts['updated'] ? '%s member card updated.' : '%s member cards updated.' ),
            'locked'    => ( 1 === $bulk_counts['locked'] ) ? '1 member card not updated, somebody is editing it.' :
                /* translators: %s: Number of member cards. */
                ( 1 === (int) $bulk_counts['locked'] ? '%s member card not updated, somebody is editing it.' : '%s member cards not updated, somebody is editing them.' ),
            /* translators: %s: Number of member cards. */
            'deleted'   => ( 1 === (int) $bulk_counts['deleted'] ? '%s member card permanently deleted.' : '%s member cards permanently deleted.' ),
            /* translators: %s: Number of member cards. */
            'trashed'   => ( 1 === (int) $bulk_counts['trashed'] ? '%s member card moved to the Trash.' : '%s member cards moved to the Trash.' ),
            /* translators: %s: Number of member cards. */
            'untrashed' => ( 1 === (int) $bulk_counts['untrashed'] ? '%s member card restored from the Trash.' : '%s member cards restored from the Trash.' ),
        ];

        return $bulk_messages;
    }
    public function register_member_card_acf_fields(): void {
        if ( ! function_exists( 'acf_add_local_field_group' ) ) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_682728e236669',
            'title' => 'Card Options',
            'fields' => [
                [
                    'key' => 'field_6994e2c1c2f91',
                    'label' => 'Members Default',
                    'name' => 'members_default',
                    'aria-label' => '',
                    'type' => 'true_false',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'message' => '',
                    'default_value' => 0,
                    'allow_in_bindings' => 0,
                    'ui' => 1,
                    'ui_on_text' => 'Default',
                    'ui_off_text' => 'Not Default',
                ],
                [
                    'key' => 'field_68272bbb2c11a',
                    'label' => 'Front Face',
                    'name' => 'front_face',
                    'aria-label' => '',
                    'type' => 'group',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'layout' => 'block',
                    'acfe_seamless_style' => 0,
                    'acfe_group_modal' => 0,
                    'acfe_group_modal_close' => 0,
                    'acfe_group_modal_button' => '',
                    'acfe_group_modal_size' => 'large',
                    'sub_fields' => [
                        [
                            'key' => 'field_68272bbb2c11b',
                            'label' => 'Type',
                            'name' => 'type',
                            'aria-label' => '',
                            'type' => 'radio',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => [
                                'width' => '25',
                                'class' => '',
                                'id' => '',
                            ],
                            'choices' => [
                                'benaki_color' => 'Brand Color',
                                'custom_color' => 'Custom Color',
                                'image' => 'Image',
                            ],
                            'default_value' => 'benaki_color',
                            'return_format' => 'value',
                            'allow_null' => 0,
                            'other_choice' => 0,
                            'allow_in_bindings' => 0,
                            'layout' => 'horizontal',
                            'save_other_choice' => 0,
                        ],
                        [
                            'key' => 'field_68272bbb2c11e',
                            'label' => 'Color',
                            'name' => 'benaki_color',
                            'aria-label' => '',
                            'type' => 'radio',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_68272bbb2c11b',
                                        'operator' => '==',
                                        'value' => 'benaki_color',
                                    ],
                                ],
                            ],
                            'wrapper' => [
                                'width' => '75',
                                'class' => 'theme-color',
                                'id' => '',
                            ],
                            'choices' => [],
                            'default_value' => '',
                            'return_format' => 'value',
                            'allow_null' => 0,
                            'other_choice' => 0,
                            'allow_in_bindings' => 1,
                            'layout' => 'vertical',
                            'save_other_choice' => 0,
                        ],
                        [
                            'key' => 'field_68272bbb2c11d',
                            'label' => 'Color',
                            'name' => 'custom_color',
                            'aria-label' => '',
                            'type' => 'color_picker',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_68272bbb2c11b',
                                        'operator' => '==',
                                        'value' => 'custom_color',
                                    ],
                                ],
                            ],
                            'wrapper' => [
                                'width' => '75',
                                'class' => '',
                                'id' => '',
                            ],
                            'default_value' => '',
                            'enable_opacity' => 0,
                            'return_format' => 'string',
                            'allow_in_bindings' => 0,
                            'custom_palette_source' => '',
                            'palette_colors' => '',
                            'show_color_wheel' => true,
                        ],
                        [
                            'key' => 'field_68272bbb2c11c',
                            'label' => 'Image',
                            'name' => 'image',
                            'aria-label' => '',
                            'type' => 'image',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_68272bbb2c11b',
                                        'operator' => '==',
                                        'value' => 'image',
                                    ],
                                ],
                            ],
                            'wrapper' => [
                                'width' => '75',
                                'class' => '',
                                'id' => '',
                            ],
                            'uploader' => '',
                            'return_format' => 'id',
                            'library' => 'all',
                            'acfe_thumbnail' => 0,
                            'min_width' => '',
                            'min_height' => '',
                            'min_size' => '',
                            'max_width' => '',
                            'max_height' => '',
                            'max_size' => '',
                            'mime_types' => '',
                            'allow_in_bindings' => 0,
                            'preview_size' => 'medium',
                        ],
                        [
                            'key' => 'field_68272bbb2c11b_background_color_type',
                            'label' => 'Background Color Type',
                            'name' => 'background_color_type',
                            'aria-label' => '',
                            'type' => 'radio',
                            'instructions' => '',
                            'required' => 1,
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_68272bbb2c11b',
                                        'operator' => '==',
                                        'value' => 'image',
                                    ],
                                ],
                            ],
                            'wrapper' => [
                                'width' => '25',
                                'class' => '',
                                'id' => '',
                            ],
                            'choices' => [
                                'background_benaki_color' => 'Brand Color',
                                'background_custom_color' => 'Custom Color',
                            ],
                            'default_value' => 'background_benaki_color',
                            'return_format' => 'value',
                            'allow_null' => 0,
                            'other_choice' => 0,
                            'allow_in_bindings' => 0,
                            'layout' => 'horizontal',
                            'save_other_choice' => 0,
                        ],
                        [
                            'key' => 'field_68272bbb2c11e_background_benaki_color',
                            'label' => 'Color',
                            'name' => 'background_benaki_color',
                            'aria-label' => '',
                            'type' => 'radio',
                            'instructions' => '',
                            'required' => 1,
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_68272bbb2c11b_background_color_type',
                                        'operator' => '==',
                                        'value' => 'background_benaki_color',
                                    ],
                                ],
                            ],
                            'wrapper' => [
                                'width' => '75',
                                'class' => 'theme-color',
                                'id' => '',
                            ],
                            'choices' => [],
                            'default_value' => '',
                            'return_format' => 'value',
                            'allow_null' => 0,
                            'other_choice' => 0,
                            'allow_in_bindings' => 1,
                            'layout' => 'vertical',
                            'save_other_choice' => 0,
                        ],
                        [
                            'key' => 'field_68272bbb2c11d_background_custom_color',
                            'label' => 'Color',
                            'name' => 'background_custom_color',
                            'aria-label' => '',
                            'type' => 'color_picker',
                            'instructions' => '',
                            'required' => 1,
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_68272bbb2c11b_background_color_type',
                                        'operator' => '==',
                                        'value' => 'background_custom_color',
                                    ],
                                ],
                            ],
                            'wrapper' => [
                                'width' => '75',
                                'class' => '',
                                'id' => '',
                            ],
                            'default_value' => '',
                            'enable_opacity' => 0,
                            'return_format' => 'string',
                            'allow_in_bindings' => 0,
                            'show_custom_palette' => 0,
                            'show_color_wheel' => 1,
                            'custom_palette_source' => '',
                            'palette_colors' => '',
                        ],
                    ],
                ],
                [
                    'key' => 'field_68272e0cb27f0',
                    'label' => 'Back Face',
                    'name' => 'back_face',
                    'aria-label' => '',
                    'type' => 'group',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'layout' => 'block',
                    'acfe_seamless_style' => 0,
                    'acfe_group_modal' => 0,
                    'acfe_group_modal_close' => 0,
                    'acfe_group_modal_button' => '',
                    'acfe_group_modal_size' => 'large',
                    'sub_fields' => [
                        [
                            'key' => 'field_68272e0cb27f1',
                            'label' => 'Type',
                            'name' => 'type',
                            'aria-label' => '',
                            'type' => 'radio',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => [
                                'width' => '25',
                                'class' => '',
                                'id' => '',
                            ],
                            'choices' => [
                                'benaki_color' => 'Brand Color',
                                'custom_color' => 'Custom Color',
                                'image' => 'Image',
                            ],
                            'default_value' => 'benaki_color',
                            'return_format' => 'value',
                            'allow_null' => 0,
                            'other_choice' => 0,
                            'allow_in_bindings' => 0,
                            'layout' => 'horizontal',
                            'save_other_choice' => 0,
                        ],
                        [
                            'key' => 'field_68272e0cb27f2',
                            'label' => 'Color',
                            'name' => 'benaki_color',
                            'aria-label' => '',
                            'type' => 'radio',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_68272e0cb27f1',
                                        'operator' => '==',
                                        'value' => 'benaki_color',
                                    ],
                                ],
                            ],
                            'wrapper' => [
                                'width' => '75',
                                'class' => 'theme-color',
                                'id' => '',
                            ],
                            'choices' => [],
                            'default_value' => '',
                            'return_format' => 'value',
                            'allow_null' => 0,
                            'other_choice' => 0,
                            'allow_in_bindings' => 1,
                            'layout' => 'vertical',
                            'save_other_choice' => 0,
                        ],
                        [
                            'key' => 'field_68272e0cb27f3',
                            'label' => 'Color',
                            'name' => 'custom_color',
                            'aria-label' => '',
                            'type' => 'color_picker',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_68272e0cb27f1',
                                        'operator' => '==',
                                        'value' => 'custom_color',
                                    ],
                                ],
                            ],
                            'wrapper' => [
                                'width' => '75',
                                'class' => '',
                                'id' => '',
                            ],
                            'default_value' => '',
                            'enable_opacity' => 0,
                            'return_format' => 'string',
                            'allow_in_bindings' => 0,
                            'custom_palette_source' => '',
                            'palette_colors' => '',
                            'show_color_wheel' => true,
                        ],
                        [
                            'key' => 'field_68272e0cb27f4',
                            'label' => 'Image',
                            'name' => 'image',
                            'aria-label' => '',
                            'type' => 'image',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_68272e0cb27f1',
                                        'operator' => '==',
                                        'value' => 'image',
                                    ],
                                ],
                            ],
                            'wrapper' => [
                                'width' => '75',
                                'class' => '',
                                'id' => '',
                            ],
                            'uploader' => '',
                            'return_format' => 'id',
                            'library' => 'all',
                            'acfe_thumbnail' => 0,
                            'min_width' => '',
                            'min_height' => '',
                            'min_size' => '',
                            'max_width' => '',
                            'max_height' => '',
                            'max_size' => '',
                            'mime_types' => '',
                            'allow_in_bindings' => 0,
                            'preview_size' => 'medium',
                        ],
                    ],
                ],
                [
                    'key' => 'field_68272eff76612',
                    'label' => 'Text',
                    'name' => 'text',
                    'aria-label' => '',
                    'type' => 'group',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'layout' => 'block',
                    'acfe_seamless_style' => 0,
                    'acfe_group_modal' => 0,
                    'acfe_group_modal_close' => 0,
                    'acfe_group_modal_button' => '',
                    'acfe_group_modal_size' => 'large',
                    'sub_fields' => [
                        [
                            'key' => 'field_68272eff76613',
                            'label' => 'Type',
                            'name' => 'type',
                            'aria-label' => '',
                            'type' => 'radio',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => [
                                'width' => '25',
                                'class' => '',
                                'id' => '',
                            ],
                            'choices' => [
                                'benaki_color' => 'Brand Color',
                                'custom_color' => 'Custom Color',
                            ],
                            'default_value' => 'benaki_color',
                            'return_format' => 'value',
                            'allow_null' => 0,
                            'other_choice' => 0,
                            'allow_in_bindings' => 0,
                            'layout' => 'horizontal',
                            'save_other_choice' => 0,
                        ],
                        [
                            'key' => 'field_68272eff76614',
                            'label' => 'Color',
                            'name' => 'benaki_color',
                            'aria-label' => '',
                            'type' => 'radio',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_68272eff76613',
                                        'operator' => '==',
                                        'value' => 'benaki_color',
                                    ],
                                ],
                            ],
                            'wrapper' => [
                                'width' => '75',
                                'class' => 'theme-color',
                                'id' => '',
                            ],
                            'choices' => [],
                            'default_value' => 'dark',
                            'return_format' => 'value',
                            'allow_null' => 0,
                            'other_choice' => 0,
                            'allow_in_bindings' => 1,
                            'layout' => 'vertical',
                            'save_other_choice' => 0,
                        ],
                        [
                            'key' => 'field_68272eff76615',
                            'label' => 'Color',
                            'name' => 'custom_color',
                            'aria-label' => '',
                            'type' => 'color_picker',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_68272eff76613',
                                        'operator' => '==',
                                        'value' => 'custom_color',
                                    ],
                                ],
                            ],
                            'wrapper' => [
                                'width' => '75',
                                'class' => '',
                                'id' => '',
                            ],
                            'default_value' => '',
                            'enable_opacity' => 0,
                            'return_format' => 'string',
                            'allow_in_bindings' => 0,
                            'custom_palette_source' => '',
                            'palette_colors' => '',
                            'show_color_wheel' => true,
                        ],
                    ],
                ],
                [
                    'key' => 'field_692eeb5381a90',
                    'label' => 'Wallet',
                    'name' => 'wallet',
                    'aria-label' => '',
                    'type' => 'group',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'layout' => 'block',
                    'acfe_seamless_style' => 0,
                    'acfe_group_modal' => 0,
                    'sub_fields' => [
                        [
                            'key' => 'field_692eeb6e81a91',
                            'label' => 'Apple',
                            'name' => 'apple',
                            'aria-label' => '',
                            'type' => 'group',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => [
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ],
                            'layout' => 'block',
                            'acfe_seamless_style' => 0,
                            'acfe_group_modal' => 0,
                            'sub_fields' => [
                                [
                                    'key' => 'field_692eebdd81a94',
                                    'label' => 'Background Color Type',
                                    'name' => 'background_color_type',
                                    'aria-label' => '',
                                    'type' => 'radio',
                                    'instructions' => '',
                                    'required' => 1,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '50',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'choices' => [
                                        'benaki_background_color' => 'Brand Color',
                                        'custom_background_color' => 'Custom',
                                    ],
                                    'default_value' => '',
                                    'return_format' => 'value',
                                    'allow_null' => 0,
                                    'other_choice' => 0,
                                    'allow_in_bindings' => 0,
                                    'layout' => 'horizontal',
                                    'save_other_choice' => 0,
                                ],
                                [
                                    'key' => 'field_692eed1181a98',
                                    'label' => 'Brand Color',
                                    'name' => 'benaki_background_color',
                                    'aria-label' => '',
                                    'type' => 'radio',
                                    'instructions' => '',
                                    'required' => 1,
                                    'conditional_logic' => [
                                        [
                                            [
                                                'field' => 'field_692eebdd81a94',
                                                'operator' => '==',
                                                'value' => 'benaki_background_color',
                                            ],
                                        ],
                                    ],
                                    'wrapper' => [
                                        'width' => '50',
                                        'class' => 'theme-color',
                                        'id' => '',
                                    ],
                                    'choices' => [],
                                    'default_value' => '',
                                    'return_format' => 'value',
                                    'allow_null' => 0,
                                    'other_choice' => 0,
                                    'allow_in_bindings' => 0,
                                    'layout' => 'vertical',
                                    'save_other_choice' => 0,
                                ],
                                [
                                    'key' => 'field_692eed4081a99',
                                    'label' => 'Custom Color',
                                    'name' => 'custom_background_color',
                                    'aria-label' => '',
                                    'type' => 'color_picker',
                                    'instructions' => '',
                                    'required' => 1,
                                    'conditional_logic' => [
                                        [
                                            [
                                                'field' => 'field_692eebdd81a94',
                                                'operator' => '==',
                                                'value' => 'custom_background_color',
                                            ],
                                        ],
                                    ],
                                    'wrapper' => [
                                        'width' => '50',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'default_value' => '',
                                    'enable_opacity' => 0,
                                    'return_format' => 'string',
                                    'allow_in_bindings' => 0,
                                    'show_custom_palette' => 0,
                                    'show_color_wheel' => 1,
                                    'custom_palette_source' => '',
                                    'palette_colors' => '',
                                ],
                                [
                                    'key' => 'field_692eebea81a95',
                                    'label' => 'Text Color Type',
                                    'name' => 'text_color_type',
                                    'aria-label' => '',
                                    'type' => 'radio',
                                    'instructions' => '',
                                    'required' => 1,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '50',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'choices' => [
                                        'benaki_text_color' => 'Brand Text Color',
                                        'custom_text_color' => 'Custom Text Color',
                                    ],
                                    'default_value' => '',
                                    'return_format' => 'value',
                                    'allow_null' => 0,
                                    'other_choice' => 0,
                                    'allow_in_bindings' => 0,
                                    'layout' => 'horizontal',
                                    'save_other_choice' => 0,
                                ],
                                [
                                    'key' => 'field_692eec8b81a97',
                                    'label' => 'Brand Color',
                                    'name' => 'benaki_text_color',
                                    'aria-label' => '',
                                    'type' => 'radio',
                                    'instructions' => '',
                                    'required' => 1,
                                    'conditional_logic' => [
                                        [
                                            [
                                                'field' => 'field_692eebea81a95',
                                                'operator' => '==',
                                                'value' => 'benaki_text_color',
                                            ],
                                        ],
                                    ],
                                    'wrapper' => [
                                        'width' => '50',
                                        'class' => 'theme-color',
                                        'id' => '',
                                    ],
                                    'choices' => [],
                                    'default_value' => '',
                                    'return_format' => 'value',
                                    'allow_null' => 0,
                                    'other_choice' => 0,
                                    'allow_in_bindings' => 0,
                                    'layout' => 'vertical',
                                    'save_other_choice' => 0,
                                ],
                                [
                                    'key' => 'field_692eec4981a96',
                                    'label' => 'Custom Color',
                                    'name' => 'custom_text_color',
                                    'aria-label' => '',
                                    'type' => 'color_picker',
                                    'instructions' => '',
                                    'required' => 1,
                                    'conditional_logic' => [
                                        [
                                            [
                                                'field' => 'field_692eebea81a95',
                                                'operator' => '==',
                                                'value' => 'custom_text_color',
                                            ],
                                        ],
                                    ],
                                    'wrapper' => [
                                        'width' => '50',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'default_value' => '',
                                    'enable_opacity' => 0,
                                    'return_format' => 'string',
                                    'allow_in_bindings' => 0,
                                    'show_custom_palette' => 0,
                                    'show_color_wheel' => 1,
                                    'custom_palette_source' => '',
                                    'palette_colors' => '',
                                ],
                                [
                                    'key' => 'field_692eebc881a93_icon',
                                    'label' => 'Icon',
                                    'name' => 'icon',
                                    'aria-label' => '',
                                    'type' => 'image',
                                    'instructions' => '87 x 87',
                                    'required' => 1,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '33.33333',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'return_format' => 'id',
                                    'preview_size' => 'medium',
                                    'library' => 'all',
                                    'min_width' => 87,
                                    'min_height' => 87,
                                    'min_size' => '',
                                    'max_width' => 87,
                                    'max_height' => 87,
                                    'max_size' => '',
                                    'mime_types' => '',
                                    'allow_in_bindings' => 0,
                                ],
                                [
                                    'key' => 'field_692eebc881a93_logo',
                                    'label' => 'Logo',
                                    'name' => 'logo',
                                    'aria-label' => '',
                                    'type' => 'image',
                                    'instructions' => '480 x 150',
                                    'required' => 1,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '33.333333',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'return_format' => 'id',
                                    'preview_size' => 'medium',
                                    'library' => 'all',
                                    'min_width' => 480,
                                    'min_height' => 150,
                                    'min_size' => '',
                                    'max_width' => 480,
                                    'max_height' => 150,
                                    'max_size' => '',
                                    'mime_types' => '',
                                    'allow_in_bindings' => 0,
                                ],
                                [
                                    'key' => 'field_692eeba981a92strip',
                                    'label' => 'Strip',
                                    'name' => 'strip',
                                    'aria-label' => '',
                                    'type' => 'image',
                                    'instructions' => '1125 x 432 pixels',
                                    'required' => 1,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '33.3333333',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'return_format' => 'id',
                                    'preview_size' => 'medium',
                                    'library' => 'all',
                                    'min_width' => 1125,
                                    'min_height' => 432,
                                    'max_width' => 1125,
                                    'max_height' => 432,
                                    'mime_types' => '',
                                    'allow_in_bindings' => 0,
                                ],
                            ],
                            'acfe_group_modal_close' => 0,
                            'acfe_group_modal_button' => '',
                            'acfe_group_modal_size' => 'large',
                        ],
                        [
                            'key' => 'field_google_wallet_group',
                            'label' => 'Google',
                            'name' => 'google',
                            'aria-label' => '',
                            'type' => 'group',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => [
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ],
                            'layout' => 'block',
                            'sub_fields' => [
                                [
                                    'key' => 'field_google_hero_image',
                                    'label' => 'Hero Image',
                                    'name' => 'hero_image',
                                    'type' => 'image',
                                    'instructions' => '1125 x 432 px',
                                    'required' => 0,
                                    'wrapper' => [
                                        'width' => '33.33333',
                                    ],
                                    'return_format' => 'id',
                                    'preview_size' => 'medium',
                                    'library' => 'all',
                                    'min_width' => 1125,
                                    'min_height' => 432,
                                    'max_width' => 1125,
                                    'max_height' => 432,
                                ],
                                [
                                    'key' => 'field_google_logo',
                                    'label' => 'Logo',
                                    'name' => 'logo',
                                    'type' => 'image',
                                    'instructions' => '660 x 660 px (Square)',
                                    'required' => 0,
                                    'wrapper' => [
                                        'width' => '33.33333',
                                    ],
                                    'return_format' => 'id',
                                    'preview_size' => 'medium',
                                    'library' => 'all',
                                    'min_width' => 660,
                                    'min_height' => 660,
                                    'max_width' => 660,
                                    'max_height' => 660,
                                ],
                            ],
                        ],
                    ],
                    'acfe_group_modal_close' => 0,
                    'acfe_group_modal_button' => '',
                    'acfe_group_modal_size' => 'large',
                ]
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'member-card',
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'left',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => '',
            'show_in_rest' => 0,
            'display_title' => '',
            'acfe_display_title' => '',
            'acfe_autosync' => [
                'json',
            ],
            'acfe_form' => 0,
            'acfe_meta' => '',
            'acfe_note' => '',
        ]);
    }
}

new Register_Member_Card_CPT();
