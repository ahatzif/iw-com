<?php

class IW_Ticketing_ACF {

    public function __construct() {
        add_action( 'acf/init', [ $this, 'register_acf_fields' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
    }

    public static $post_types = [ 'event', 'guided-tour', 'exhibition', 'learning-program', 'experience', 'building' ];

    private function get_current_admin_post_type(): ?string {
        if ( ! is_admin() ) return null;

        // Prefer WP_Screen when available.
        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            if ( $screen && ! empty( $screen->post_type ) ) {
                return (string) $screen->post_type;
            }
        }

        // Fallbacks for common edit/new screens.
        if ( ! empty( $_GET['post_type'] ) ) {
            return sanitize_key( (string) $_GET['post_type'] );
        }

        if ( ! empty( $_GET['post'] ) ) {
            $post_id = (int) $_GET['post'];
            if ( $post_id > 0 ) {
                $pt = get_post_type( $post_id );
                if ( $pt ) return (string) $pt;
            }
        }

        return null;
    }

    private function get_tickets_fields_for_post_types( array $post_types_map ): array {
        $fields = [];

        $current_admin_post_type = $this->get_current_admin_post_type();
        if ( $current_admin_post_type && in_array( $current_admin_post_type, self::$post_types, true ) ) {
            $file = __DIR__ . '/acf/' . $current_admin_post_type . '.php';
            if ( file_exists( $file ) ) {
                $loaded = include $file;
                if ( is_array( $loaded ) ) {
                    $fields = array_merge( $fields, $loaded );
                }
            }

        }

        return $fields;
    }

    private static function slot_organizers_field( string $key, string $parent_repeater = '' ): array {
        $field = [
            'key' => $key,
            'label' => 'Organizers',
            'name' => 'organizers',
            'aria-label' => '',
            'type' => 'post_object',
            'instructions' => 'Used for slot conflict checks. Falls back to Staff when empty.',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => [
                'width' => '100',
                'class' => '',
                'id' => '',
            ],
            'post_type' => [ 'staff', 'external-staff' ],
            'taxonomy' => '',
            'allow_null' => 1,
            'multiple' => 1,
            'return_format' => 'id',
            'ui' => 1,
            'allow_in_bindings' => 0,
        ];

        if ( $parent_repeater !== '' ) {
            $field['parent_repeater'] = $parent_repeater;
        }

        return $field;
    }

    public function enqueue_admin_styles(): void {
        if ( ! is_admin() ) return;

        // Only load on relevant post types to avoid styling other ACF screens.
        $post_type = $this->get_current_admin_post_type();
        if ( $post_type && ! in_array( $post_type, self::$post_types, true ) ) {
            return;
        }

        $css = <<<CSS
        .acf-ticket-categories > .acf-input > .acf-fields {
            border: none;
        }

        .acf-ticket-categories > .acf-input > .acf-fields > .acf-field{
            
            margin-bottom: 20px;
            border: none;   
        }
        
        .acf-ticket-categories > .acf-input > .acf-fields > .acf-field .acf-fields.-border{
            border: none;
        }

        .acf-ticket-categories > .acf-input > .acf-fields > .acf-field .acf-fields .acf-fields.-left>.acf-field:before{
            background: none;
        }

        .acf-ticket-categories > .acf-input > .acf-fields > .acf-field .acf-fields.-left > .acf-field:before{
           
            display: none;
        }
        
        .acf-ticket-categories > .acf-input > .acf-fields > .acf-field .acf-fields.-left > .acf-field{
            padding: 5px 0;
        }
        
        .acf-ticket-categories > .acf-input > .acf-fields > .acf-field .acf-fields.-left > .acf-field .acf-label{
            padding: 0 ;
            width: 80%;
        }
        
        .acf-ticket-categories > .acf-input > .acf-fields > .acf-field .acf-fields.-left > .acf-field .acf-input{
            width: 100px;
            float: right;
        }
        
        .acf-ticket-categories > .acf-input > .acf-fields > .acf-field .acf-fields  .acf-label label{
            font-weight: 400;
            line-height: 30px;
            margin: 0 ;
        }
        
        .acf-ticket-categories > .acf-input > .acf-fields > .acf-field .acf-fields.-left {
            background: #f5f5f5;
            border-radius: 20px;
            padding: 20px;
        }
        
        .acf-ticket-categories .max-tickets {
            
            
            margin-bottom: 10px !important;
            
        }
        
        .acf-ticket-categories .max-tickets label{
            font-weight: bold !important;
            font-size: 10px;
        }
        
        
        .category-max-tickets  label{
            font-size: 10px;
            font-weight: bold !important;
        }
        
    

        CSS;

        wp_add_inline_style( 'wp-admin', $css );
    }

    public function get_post_tpyes_locations(){
        $locations = [];
        foreach (IW_Ticketing::get_supported_post_types() as $supported_post_type) {
            $locations [] = [ [ 'param' => 'post_type', 'operator' => '==', 'value' => $supported_post_type ] ];
        }
        return$locations;
    }

    public function register_acf_fields() {
        if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

        $ticket_categories = get_field( 'iw_ticket_categories', 'option' );
        if ( ! is_array( $ticket_categories ) ) {
            $ticket_categories = [];
        }
        $ticket_categories_group_fields = [
            [
                'key' =>  'field_iw_ticket_categories_group_min_tickets',
                'label' => 'MIN TICKETS',
                'name' => 'min_tickets',
                'wrapper' => [
                    'width' => '50',
                    'class' => 'max-tickets',
                    'id' => '',
                ],
                'aria-label' => '',
                'type' => 'number',
            ],
            [
                'key' =>  'field_iw_ticket_categories_group_max_tickets',
                'label' => 'MAX TICKETS',
                'name' => 'max_tickets',
                'wrapper' => [
                    'width' => '50',
                    'class' => 'max-tickets',
                    'id' => '',
                ],
                'aria-label' => '',
                'type' => 'number',
            ],
            [
                'key' =>  'field_iw_ticket_categories_group_one_booking_per_slot',
                'label' => 'ONE BOOKING PER SLOT',
                'name' => 'one_booking_per_slot',
                'wrapper' => [
                    'width' => '100',
                    'class' => 'max-tickets',
                    'id' => '',
                ],
                'aria-label' => '',
                'type' => 'true_false',
                'default_value' => 0,
                'ui' => 1,
                'ui_on_text' => 'YES',
                'ui_off_text' => 'NO',
            ]
        ];

        foreach ( $ticket_categories as $ticket_category ) {


            $label = strip_tags( (string) $ticket_category['label'] );
            $key   = sanitize_key( (string) $ticket_category['key'] );

            if ( $label === '' || $key === '' ) continue;


            $subcategories = [];
            if ( isset( $ticket_category['subcategories'] ) && is_array( $ticket_category['subcategories'] ) ) {
                $subcategories = $ticket_category['subcategories'];
            }

            $category_field_key = 'field_iw_ticket_category_' . $key;


            $subcategory_fields = [];
            foreach ( $subcategories as $subcat ) {
                if ( ! is_array( $subcat ) ) continue;
                $sub_label = isset( $subcat['label'] ) ? strip_tags( (string) $subcat['label'] ) : '';
                $sub_key   = isset( $subcat['key'] ) ? sanitize_key( (string) $subcat['key'] ) : '';
                if ( $sub_label === '' || $sub_key === '' ) continue;

                $subcategory_fields[] = [ 'key' => $category_field_key . '_sub_' . $sub_key,  'label' => $sub_label,  'name' => $sub_key,  'type' => 'number' ];
            }

            if( empty( $subcategory_fields ) ) continue;

            array_unshift( $subcategory_fields, [
                'key' => $category_field_key . '_sub_max_tickets',
                'label' => 'MAX TICKETS',
                'name' => 'max_tickets',
                'wrapper' => [
                    'width' => '',
                    'class' => 'category-max-tickets',
                    'id' => '',
                ],
                'aria-label' => '',
                'type' => 'number',
            ] );
            array_unshift( $subcategory_fields, [
                'key' => $category_field_key . '_sub_min_tickets',
                'label' => 'MIN TICKETS',
                'name' => 'min_tickets',
                'wrapper' => [
                    'width' => '',
                    'class' => 'category-max-tickets',
                    'id' => '',
                ],
                'aria-label' => '',
                'type' => 'number',
            ] );
            array_unshift( $subcategory_fields, [
                'key' => $category_field_key . '_sub_require_full_name',
                'label' => 'FULL NAME REQUIRED',
                'name' => 'require_full_name',
                'wrapper' => [
                    'width' => '',
                    'class' => 'category-max-tickets',
                    'id' => '',
                ],
                'aria-label' => '',
                'type' => 'true_false',
                'default_value' => 1,
                'ui' => 1,
                'ui_on_text' => 'YES',
                'ui_off_text' => 'NO',
            ] );
            $ticket_categories_group_fields[] = [
                'key'        => $category_field_key,
                'label'      => $label,
                'name'       => $key,
                'type'       => 'group',
                'layout'     => 'row',
                'sub_fields' => $subcategory_fields,
            ];
        }

        $ticket_categories_fields = [
            'key'        => 'field_iw_ticket_categories_group',
            'label'      => '',
            'name'       => 'ticket_categories',
            'type'       => 'group',
            'layout'     => 'block',
            'wrapper' => [
                'width' => '100',
                'class' => 'acf-ticket-categories',
                'id' => '',
            ],
            'sub_fields' => $ticket_categories_group_fields,
        ];

        // Allow editors to define custom ticket categories/subcategories per post.
        $custom_ticket_categories_field = [
            'key' => 'field_iw_custom_ticket_categories',
            'label' => 'Custom Ticket Categories',
            'name' => 'custom_ticket_categories',
            'type' => 'repeater',
            'instructions' => 'Add custom categories and subcategories. Prices are set per subcategory.',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => [
                'width' => '',
                'class' => '',
                'id' => '',
            ],
            'layout' => 'block',
            'pagination' => 0,
            'min' => 0,
            'max' => 0,
            'collapsed' => 'field_iw_custom_ticket_category_label',
            'button_label' => 'Add Category',
            'rows_per_page' => 20,
            'sub_fields' => [
                [
                    'key' => 'field_iw_custom_ticket_category_label',
                    'label' => 'Category Label',
                    'name' => 'label',
                    'type' => 'textarea',
                    'required' => 1,
                    'new_lines'     => 'br',
                    'rows'          => 3,
                    'wrapper' => [ 'width' => '40', 'class' => '', 'id' => '' ],
                    'default_value' => '',
                    'placeholder' => 'e.g. VIP',
                    'instructions' => '&nbsp'
                ],
                [
                    'key' => 'field_iw_custom_ticket_category_description',
                    'label' => 'Description',
                    'name' => 'description',
                    'type' => 'textarea',
                    'instructions' =>  'Optional, short description shown in UI/help texts',
                    'new_lines'     => 'br',
                    'rows'          => 3,
                    'required' => 0,
                    'wrapper' => [ 'width' => '40', 'class' => 'max-tickets', 'id' => '' ],
                ],
                [
                    'key' => 'field_iw_custom_ticket_category_key',
                    'label' => 'Category Key',
                    'name' => 'key',
                    'type' => 'text',
                    'required' => 1,
                    'instructions' => 'Unique identifier.',
                    'wrapper' => [ 'width' => '20', 'class' => '', 'id' => '' ],
                    'default_value' => '',
                    'placeholder' => 'vip',
                ],
                [
                    'key' => 'field_iw_custom_ticket_category_min_tickets',
                    'label' => 'Min Tickets',
                    'name' => 'min_tickets',
                    'type' => 'number',
                    'required' => 0,
                    'wrapper' => [ 'width' => '50', 'class' => 'max-tickets', 'id' => '' ],
                    'min' => 0,
                    'step' => 1,
                ],
                [
                    'key' => 'field_iw_custom_ticket_category_max_tickets',
                    'label' => 'Max Tickets',
                    'name' => 'max_tickets',
                    'type' => 'number',
                    'required' => 0,
                    'wrapper' => [ 'width' => '50', 'class' => 'max-tickets', 'id' => '' ],
                    'min' => 0,
                    'step' => 1,
                ],
                [
                    'key' => 'field_iw_custom_ticket_category_require_full_name',
                    'label' => 'FULL NAME REQUIRED',
                    'name' => 'require_full_name',
                    'type' => 'true_false',
                    'required' => 0,
                    'wrapper' => [ 'width' => '100', 'class' => 'max-tickets', 'id' => '' ],
                    'message' => '',
                    'default_value' => 1,
                    'ui' => 1,
                    'ui_on_text' => 'YES',
                    'ui_off_text' => 'NO',
                ],
                [
                    'key' => 'field_iw_custom_ticket_subcategories',
                    'label' => 'Subcategories',
                    'name' => 'subcategories',
                    'type' => 'repeater',
                    'instructions' => 'Define subcategories and set their prices.',
                    'required' => 1,
                    'wrapper' => [ 'width' => '100', 'class' => '', 'id' => '' ],
                    'layout' => 'row',
                    'pagination' => 0,
                    'min' => 1,
                    'max' => 0,
                    'collapsed' => '',
                    'button_label' => 'Add Subcategory',
                    'rows_per_page' => 20,
                    'sub_fields' => [
                        [
                            'key' => 'field_iw_custom_ticket_subcategory_label',
                            'label' => 'Label',
                            'name' => 'label',
                            'type' => 'text',
                            'required' => 1,
                            'wrapper' => [ 'width' => '30', 'class' => '', 'id' => '' ],
                            'default_value' => '',
                            'placeholder' => 'e.g. Adult',
                        ],
                        [
                            'key' => 'field_iw_custom_ticket_subcategory_key',
                            'label' => 'Key',
                            'name' => 'key',
                            'type' => 'text',
                            'required' => 0,
                            'instructions' => 'Optional. If empty, it will be derived from the label in code (recommended).',
                            'wrapper' => [ 'width' => '20', 'class' => '', 'id' => '' ],
                            'default_value' => '',
                            'placeholder' => 'adult',
                        ],
                        [
                            'key' => 'field_iw_custom_ticket_subcategory_price',
                            'label' => 'Price',
                            'name' => 'price',
                            'type' => 'number',
                            'required' => 1,
                            'wrapper' => [ 'width' => '15', 'class' => '', 'id' => '' ],
                            'default_value' => '',
                            'placeholder' => '10',
                        ],
                        [
                            'key' => 'field_iw_custom_ticket_subcategory_description',
                            'label' => 'Description',
                            'name' => 'description',
                            'type' => 'textarea',
                            'new_lines'     => 'br',
                            'rows'          => 3,
                            'required' => 0,
                            'wrapper' => [ 'width' => '20', 'class' => 'max-tickets', 'id' => '' ],
                        ],
                    ],
                ],
            ],
        ];


        acf_add_local_field_group( [
            'key' => 'group_iw_ticketing_main',
            'title' => 'Ticketing',
            'fields' => array_merge( [
                [
                    'key' => 'field_67d45021d2291344abce',
                    'label' => 'Sellable',
                    'name' => '_sellable',
                    'aria-label' => '',
                    'type' => 'true_false',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '25',
                        'class' => '',
                        'id' => '',
                    ],
                    'message' => '',
                    'default_value' => 0,
                    'allow_in_bindings' => 0,
                    'ui_on_text' => '',
                    'ui_off_text' => '',
                    'ui' => 1,
                ],
                [
                    'key' => 'field_67d45021d229134418890requi',
                    'label' => 'Requires Login',
                    'name' => '_requires_login',
                    'aria-label' => '',
                    'type' => 'true_false',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '25',
                        'class' => '',
                        'id' => '',
                    ],
                    'message' => '',
                    'default_value' => 0,
                    'allow_in_bindings' => 0,
                    'ui_on_text' => '',
                    'ui_off_text' => '',
                    'ui' => 1,
                ],
                [
                    'key' => 'field_67d45021d229e',
                    'label' => 'Price',
                    'name' => '_price',
                    'aria-label' => '',
                    'type' => 'text',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '25',
                        'class' => '',
                        'id' => '',
                    ],
                    'default_value' => '',
                    'maxlength' => '',
                    'allow_in_bindings' => 0,
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                ],
                [
                    'key' => 'field_67d7f2e668299',
                    'label' => 'Reduced Price',
                    'name' => '_price_reduced',
                    'aria-label' => '',
                    'type' => 'text',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '25',
                        'class' => '',
                        'id' => '',
                    ],
                    'default_value' => '',
                    'maxlength' => '',
                    'allow_in_bindings' => 0,
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                ],

                [
                    'key' => 'field_iw_tab_schedule',
                    'label' => 'Schedule',
                    'name' => '',
                    'aria-label' => '',
                    'type' => 'tab',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'placement' => 'top',
                    'endpoint' => 0,
                ],
                [
                    'key' => 'field_65980a4568123442ajklkkqa6a0',
                    'label' => '',
                    'name' => '',
                    'aria-label' => '',
                    'type' => 'message',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'message' => 'If you want to give extension, copy the End Date to the Original End Date, and set End Date to the extension date',
                    'new_lines' => 'wpautop',
                    'esc_html' => 0,
                ],
                [
                    'key' => 'field_67483794b91b7',
                    'label' => 'Start Date',
                    'name' => 'min_date',
                    'aria-label' => '',
                    'type' => 'date_picker',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '33.333',
                        'class' => '',
                        'id' => '',
                    ],
                    'display_format' => 'd/m/Y',
                    'return_format' => 'd/m/Y',
                    'first_day' => 1,
                    'allow_in_bindings' => 0,
                    'default_to_current_date' => 0,
                ],
                [
                    'key' => 'field_67c723bfd11fa',
                    'label' => 'End Date',
                    'name' => 'max_date',
                    'aria-label' => '',
                    'type' => 'date_picker',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '33.333',
                        'class' => '',
                        'id' => '',
                    ],
                    'display_format' => 'd/m/Y',
                    'return_format' => 'd/m/Y',
                    'first_day' => 1,
                    'allow_in_bindings' => 0,
                    'default_to_current_date' => 0,
                ],
                [
                    'key' => 'field_9028472ac723bfd11fa',
                    'label' => 'Original End Date',
                    'name' => 'original_max_date',
                    'aria-label' => '',
                    'type' => 'date_picker',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '33.333',
                        'class' => '',
                        'id' => '',
                    ],
                    'display_format' => 'd/m/Y',
                    'return_format' => 'd/m/Y',
                    'first_day' => 1,
                    'allow_in_bindings' => 0,
                    'default_to_current_date' => 0,
                ],

                [
                    'key' => 'field_7aa00001a',
                    'label' => 'Opening Hours',
                    'name' => 'opening_hours_mode',
                    'aria-label' => '',
                    'type' => 'radio',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '100',
                        'class' => '',
                        'id' => '',
                    ],
                    'choices' => [
                        'custom_hours' => 'Weekly Schedule',
                        'specific_days' => 'Specific Days',
                        'museum_hours' => 'Museum Hours',
                        'from_notes' => 'Just Notes',
                    ],
                    'default_value' => 'custom_hours',
                    'return_format' => 'value',
                    'allow_null' => 0,
                    'other_choice' => 0,
                    'allow_in_bindings' => 1,
                    'layout' => 'horizontal',
                    'save_other_choice' => 0,
                ],
                [
                    'key' => 'field_7aa00002b',
                    'label' => '',
                    'name' => 'opening_hours_per_day',
                    'aria-label' => '',
                    'type' => 'group',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => [
                        [
                            [
                                'field' => 'field_7aa00001a',
                                'operator' => '==',
                                'value' => 'custom_hours',
                            ],
                        ],
                    ],
                    'wrapper' => [
                        'width' => '100',
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
                            'key' => 'field_7aa_monday',
                            'label' => 'Δευτέρα',
                            'name' => 'monday',
                            'aria-label' => '',
                            'type' => 'repeater',
                            'instructions' => '',
                            'required' => false,
                            'conditional_logic' => false,
                            'wrapper' => [
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ],
                            'button_label' => 'Προσθήκη ώρας',
                            'min' => 0,
                            'max' => 0,
                            'rows_per_page' => 20,
                            'layout' => 'block',
                            'collapsed' => '',
                            'acfe_repeater_stylised_button' => 0,
                            'sub_fields' => [
                                [
                                    'key' => 'field_monday_from',
                                    'label' => 'Από',
                                    'name' => 'from',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'H:i',
                                    'return_format' => 'H:i',
                                    'parent_repeater' => 'field_7aa_monday',
                                ],
                                [
                                    'key' => 'field_monday_to',
                                    'label' => 'Έως',
                                    'name' => 'to',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'H:i',
                                    'return_format' => 'H:i',
                                    'parent_repeater' => 'field_7aa_monday',
                                ],
                                [
                                    'key' => 'field_monday_capacity',
                                    'label' => 'Capacity',
                                    'name' => 'capacity',
                                    'aria-label' => '',
                                    'type' => 'number',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'default_value' => '',
                                    'min' => 0,
                                    'step' => 1,
                                    'parent_repeater' => 'field_7aa_monday',
                                ],
                                [
                                    'key' => 'field_monday_staff',
                                    'label' => 'Staff',
                                    'name' => 'staff',
                                    'aria-label' => '',
                                    'type' => 'post_object',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '100',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'post_type' => [ 'staff', 'external-staff' ],
                                    'taxonomy' => '',
                                    'allow_null' => 1,
                                    'multiple' => 1,
                                    'return_format' => 'id',
                                    'ui' => 1,
                                    'parent_repeater' => 'field_7aa_monday',
                                ],
                                self::slot_organizers_field( 'field_monday_organizers', 'field_7aa_monday' ),
                            ],
                        ],

                        [
                            'key' => 'field_7aa_tuesday',
                            'label' => 'Τρίτη',
                            'name' => 'tuesday',
                            'aria-label' => '',
                            'type' => 'repeater',
                            'instructions' => '',
                            'required' => false,
                            'conditional_logic' => false,
                            'wrapper' => [
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ],
                            'button_label' => 'Προσθήκη ώρας',
                            'min' => 0,
                            'max' => 0,
                            'rows_per_page' => 20,
                            'layout' => 'block',
                            'collapsed' => '',
                            'acfe_repeater_stylised_button' => 0,
                            'sub_fields' => [
                                [
                                    'key' => 'field_tuesday_from',
                                    'label' => 'Από',
                                    'name' => 'from',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'H:i',
                                    'return_format' => 'H:i',
                                    'parent_repeater' => 'field_7aa_tuesday',
                                ],
                                [
                                    'key' => 'field_tuesday_to',
                                    'label' => 'Έως',
                                    'name' => 'to',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'H:i',
                                    'return_format' => 'H:i',
                                    'parent_repeater' => 'field_7aa_tuesday',
                                ],
                                [
                                    'key' => 'field_tuesday_capacity',
                                    'label' => 'Capacity',
                                    'name' => 'capacity',
                                    'aria-label' => '',
                                    'type' => 'number',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'default_value' => '',
                                    'min' => 0,
                                    'step' => 1,
                                    'parent_repeater' => 'field_7aa_tuesday',
                                ],
                                [
                                    'key' => 'field_tuesday_staff',
                                    'label' => 'Staff',
                                    'name' => 'staff',
                                    'aria-label' => '',
                                    'type' => 'post_object',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '100',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'post_type' => [ 'staff', 'external-staff' ],
                                    'taxonomy' => '',
                                    'allow_null' => 1,
                                    'multiple' => 1,
                                    'return_format' => 'id',
                                    'ui' => 1,
                                    'parent_repeater' => 'field_7aa_tuesday',
                                ],
                                self::slot_organizers_field( 'field_tuesday_organizers', 'field_7aa_tuesday' ),
                            ],
                        ],

                        [
                            'key' => 'field_7aa_wednesday',
                            'label' => 'Τετάρτη',
                            'name' => 'wednesday',
                            'aria-label' => '',
                            'type' => 'repeater',
                            'instructions' => '',
                            'required' => false,
                            'conditional_logic' => false,
                            'wrapper' => [
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ],
                            'button_label' => 'Προσθήκη ώρας',
                            'min' => 0,
                            'max' => 0,
                            'rows_per_page' => 20,
                            'layout' => 'block',
                            'collapsed' => '',
                            'acfe_repeater_stylised_button' => 0,
                            'sub_fields' => [
                                [
                                    'key' => 'field_wednesday_from',
                                    'label' => 'Από',
                                    'name' => 'from',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'H:i',
                                    'return_format' => 'H:i',
                                    'parent_repeater' => 'field_7aa_wednesday',
                                ],
                                [
                                    'key' => 'field_wednesday_to',
                                    'label' => 'Έως',
                                    'name' => 'to',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'H:i',
                                    'return_format' => 'H:i',
                                    'parent_repeater' => 'field_7aa_wednesday',
                                ],
                                [
                                    'key' => 'field_wednesday_capacity',
                                    'label' => 'Capacity',
                                    'name' => 'capacity',
                                    'aria-label' => '',
                                    'type' => 'number',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'default_value' => '',
                                    'min' => 0,
                                    'step' => 1,
                                    'parent_repeater' => 'field_7aa_wednesday',
                                ],
                                [
                                    'key' => 'field_wednesday_staff',
                                    'label' => 'Staff',
                                    'name' => 'staff',
                                    'aria-label' => '',
                                    'type' => 'post_object',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '100',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'post_type' => [ 'staff', 'external-staff' ],
                                    'taxonomy' => '',
                                    'allow_null' => 1,
                                    'multiple' => 1,
                                    'return_format' => 'id',
                                    'ui' => 1,
                                    'parent_repeater' => 'field_7aa_wednesday',
                                ],
                                self::slot_organizers_field( 'field_wednesday_organizers', 'field_7aa_wednesday' ),
                            ],
                        ],

                        [
                            'key' => 'field_7aa_thursday',
                            'label' => 'Πέμπτη',
                            'name' => 'thursday',
                            'aria-label' => '',
                            'type' => 'repeater',
                            'instructions' => '',
                            'required' => false,
                            'conditional_logic' => false,
                            'wrapper' => [
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ],
                            'button_label' => 'Προσθήκη ώρας',
                            'min' => 0,
                            'max' => 0,
                            'rows_per_page' => 20,
                            'layout' => 'block',
                            'collapsed' => '',
                            'acfe_repeater_stylised_button' => 0,
                            'sub_fields' => [
                                [
                                    'key' => 'field_thursday_from',
                                    'label' => 'Από',
                                    'name' => 'from',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'H:i',
                                    'return_format' => 'H:i',
                                    'parent_repeater' => 'field_7aa_thursday',
                                ],
                                [
                                    'key' => 'field_thursday_to',
                                    'label' => 'Έως',
                                    'name' => 'to',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'H:i',
                                    'return_format' => 'H:i',
                                    'parent_repeater' => 'field_7aa_thursday',
                                ],
                                [
                                    'key' => 'field_thursday_capacity',
                                    'label' => 'Capacity',
                                    'name' => 'capacity',
                                    'aria-label' => '',
                                    'type' => 'number',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'default_value' => '',
                                    'min' => 0,
                                    'step' => 1,
                                    'parent_repeater' => 'field_7aa_thursday',
                                ],
                                [
                                    'key' => 'field_thursday_staff',
                                    'label' => 'Staff',
                                    'name' => 'staff',
                                    'aria-label' => '',
                                    'type' => 'post_object',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '100',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'post_type' => [ 'staff', 'external-staff' ],
                                    'taxonomy' => '',
                                    'allow_null' => 1,
                                    'multiple' => 1,
                                    'return_format' => 'id',
                                    'ui' => 1,
                                    'parent_repeater' => 'field_7aa_thursday',
                                ],
                                self::slot_organizers_field( 'field_thursday_organizers', 'field_7aa_thursday' ),
                            ],
                        ],

                        [
                            'key' => 'field_7aa_friday',
                            'label' => 'Παρασκευή',
                            'name' => 'friday',
                            'aria-label' => '',
                            'type' => 'repeater',
                            'instructions' => '',
                            'required' => false,
                            'conditional_logic' => false,
                            'wrapper' => [
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ],
                            'button_label' => 'Προσθήκη ώρας',
                            'min' => 0,
                            'max' => 0,
                            'rows_per_page' => 20,
                            'layout' => 'block',
                            'collapsed' => '',
                            'acfe_repeater_stylised_button' => 0,
                            'sub_fields' => [
                                [
                                    'key' => 'field_friday_from',
                                    'label' => 'Από',
                                    'name' => 'from',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'H:i',
                                    'return_format' => 'H:i',
                                    'parent_repeater' => 'field_7aa_friday',
                                ],
                                [
                                    'key' => 'field_friday_to',
                                    'label' => 'Έως',
                                    'name' => 'to',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'H:i',
                                    'return_format' => 'H:i',
                                    'parent_repeater' => 'field_7aa_friday',
                                ],
                                [
                                    'key' => 'field_friday_capacity',
                                    'label' => 'Capacity',
                                    'name' => 'capacity',
                                    'aria-label' => '',
                                    'type' => 'number',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'default_value' => '',
                                    'min' => 0,
                                    'step' => 1,
                                    'parent_repeater' => 'field_7aa_friday',
                                ],
                                [
                                    'key' => 'field_friday_staff',
                                    'label' => 'Staff',
                                    'name' => 'staff',
                                    'aria-label' => '',
                                    'type' => 'post_object',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '100',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'post_type' => [ 'staff', 'external-staff' ],
                                    'taxonomy' => '',
                                    'allow_null' => 1,
                                    'multiple' => 1,
                                    'return_format' => 'id',
                                    'ui' => 1,
                                    'parent_repeater' => 'field_7aa_friday',
                                ],
                                self::slot_organizers_field( 'field_friday_organizers', 'field_7aa_friday' ),
                            ],
                        ],

                        [
                            'key' => 'field_7aa_saturday',
                            'label' => 'Σάββατο',
                            'name' => 'saturday',
                            'aria-label' => '',
                            'type' => 'repeater',
                            'instructions' => '',
                            'required' => false,
                            'conditional_logic' => false,
                            'wrapper' => [
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ],
                            'button_label' => 'Προσθήκη ώρας',
                            'min' => 0,
                            'max' => 0,
                            'rows_per_page' => 20,
                            'layout' => 'block',
                            'collapsed' => '',
                            'acfe_repeater_stylised_button' => 0,
                            'sub_fields' => [
                                [
                                    'key' => 'field_saturday_from',
                                    'label' => 'Από',
                                    'name' => 'from',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'H:i',
                                    'return_format' => 'H:i',
                                    'parent_repeater' => 'field_7aa_saturday',
                                ],
                                [
                                    'key' => 'field_saturday_to',
                                    'label' => 'Έως',
                                    'name' => 'to',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'H:i',
                                    'return_format' => 'H:i',
                                    'parent_repeater' => 'field_7aa_saturday',
                                ],
                                [
                                    'key' => 'field_saturday_capacity',
                                    'label' => 'Capacity',
                                    'name' => 'capacity',
                                    'aria-label' => '',
                                    'type' => 'number',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'default_value' => '',
                                    'min' => 0,
                                    'step' => 1,
                                    'parent_repeater' => 'field_7aa_saturday',
                                ],
                                [
                                    'key' => 'field_saturday_staff',
                                    'label' => 'Staff',
                                    'name' => 'staff',
                                    'aria-label' => '',
                                    'type' => 'post_object',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '100',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'post_type' => [ 'staff', 'external-staff' ],
                                    'taxonomy' => '',
                                    'allow_null' => 1,
                                    'multiple' => 1,
                                    'return_format' => 'id',
                                    'ui' => 1,
                                    'parent_repeater' => 'field_7aa_saturday',
                                ],
                                self::slot_organizers_field( 'field_saturday_organizers', 'field_7aa_saturday' ),
                            ],
                        ],

                        [
                            'key' => 'field_7aa_sunday',
                            'label' => 'Κυριακή',
                            'name' => 'sunday',
                            'aria-label' => '',
                            'type' => 'repeater',
                            'instructions' => '',
                            'required' => false,
                            'conditional_logic' => false,
                            'wrapper' => [
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ],
                            'button_label' => 'Προσθήκη ώρας',
                            'min' => 0,
                            'max' => 0,
                            'rows_per_page' => 20,
                            'layout' => 'block',
                            'collapsed' => '',
                            'acfe_repeater_stylised_button' => 0,
                            'sub_fields' => [
                                [
                                    'key' => 'field_sunday_from',
                                    'label' => 'Από',
                                    'name' => 'from',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'H:i',
                                    'return_format' => 'H:i',
                                    'parent_repeater' => 'field_7aa_sunday',
                                ],
                                [
                                    'key' => 'field_sunday_to',
                                    'label' => 'Έως',
                                    'name' => 'to',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'H:i',
                                    'return_format' => 'H:i',
                                    'parent_repeater' => 'field_7aa_sunday',
                                ],
                                [
                                    'key' => 'field_sunday_capacity',
                                    'label' => 'Capacity',
                                    'name' => 'capacity',
                                    'aria-label' => '',
                                    'type' => 'number',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'default_value' => '',
                                    'min' => 0,
                                    'step' => 1,
                                    'parent_repeater' => 'field_7aa_sunday',
                                ],
                                [
                                    'key' => 'field_sunday_staff',
                                    'label' => 'Staff',
                                    'name' => 'staff',
                                    'aria-label' => '',
                                    'type' => 'post_object',
                                    'instructions' => '',
                                    'required' => false,
                                    'conditional_logic' => false,
                                    'wrapper' => [
                                        'width' => '100',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'post_type' => [ 'staff', 'external-staff' ],
                                    'taxonomy' => '',
                                    'allow_null' => 1,
                                    'multiple' => 1,
                                    'return_format' => 'id',
                                    'ui' => 1,
                                    'parent_repeater' => 'field_7aa_sunday',
                                ],
                                self::slot_organizers_field( 'field_sunday_organizers', 'field_7aa_sunday' ),
                            ],
                        ],
                    ],
                ],

                [
                    'key' => 'field_681ddd8857588',
                    'label' => '',
                    'name' => 'choose_specifics_days',
                    'aria-label' => '',
                    'type' => 'repeater',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => [
                        [
                            [
                                'field' => 'field_7aa00001a',
                                'operator' => '==',
                                'value' => 'specific_days',
                            ],
                        ],
                    ],
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'acfe_repeater_stylised_button' => 0,
                    'layout' => 'row',
                    'pagination' => 0,
                    'min' => 0,
                    'max' => 0,
                    'collapsed' => '',
                    'button_label' => 'Add Date',
                    'rows_per_page' => 20,
                    'sub_fields' => [
                        [
                            'key' => 'field_681ddda057589',
                            'label' => 'Date',
                            'name' => 'choose_date',
                            'aria-label' => '',
                            'type' => 'date_picker',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => [
                                'width' => '33.3333',
                                'class' => '',
                                'id' => '',
                            ],
                            'display_format' => 'd/m/Y',
                            'return_format' => 'd/m/Y',
                            'first_day' => 1,
                            'allow_in_bindings' => 0,
                            'parent_repeater' => 'field_681ddd8857588',
                            'default_to_current_date' => 0,
                        ],
                        [
                            'key' => 'field_681ddddc5758a',
                            'label' => 'Slots',
                            'name' => 'choose_time',
                            'aria-label' => '',
                            'type' => 'repeater',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => [
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ],
                            'acfe_repeater_stylised_button' => 0,
                            'layout' => 'block',
                            'min' => 0,
                            'max' => 0,
                            'collapsed' => '',
                            'button_label' => 'Add Slot',
                            'rows_per_page' => 20,
                            'parent_repeater' => 'field_681ddd8857588',
                            'sub_fields' => [
                                [
                                    'key' => 'field_681dde575758b',
                                    'label' => 'From',
                                    'name' => 'time',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'g:i a',
                                    'return_format' => 'H:i',
                                    'allow_in_bindings' => 0,
                                    'parent_repeater' => 'field_681ddddc5758a',
                                ],
                                [
                                    'key' => 'field_681dde575758bto',
                                    'label' => 'To (optional)',
                                    'name' => 'time_to',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'g:i a',
                                    'return_format' => 'H:i',
                                    'allow_in_bindings' => 0,
                                    'parent_repeater' => 'field_681ddddc5758a',
                                ],
                                [
                                    'key' => 'field_673asbcssf1f365adfsfab13244bb2fcapacityc',
                                    'label' => 'Capacity',
                                    'name' => 'capacity',
                                    'aria-label' => '',
                                    'type' => 'text',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'default_value' => '',
                                ],
                                [
                                    'key' => 'field_681ddddc5758a_staff',
                                    'label' => 'Staff',
                                    'name' => 'staff',
                                    'aria-label' => '',
                                    'type' => 'post_object',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '100',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'post_type' => [ 'staff', 'external-staff' ],
                                    'taxonomy' => '',
                                    'allow_null' => 1,
                                    'multiple' => 1,
                                    'return_format' => 'id',
                                    'ui' => 1,
                                    'allow_in_bindings' => 0,
                                    'parent_repeater' => 'field_681ddddc5758a',
                                ],
                                self::slot_organizers_field( 'field_681ddddc5758a_organizers', 'field_681ddddc5758a' ),
                                [
                                    'key' => 'field_681dec20fa6f1',
                                    'label' => 'Canceled',
                                    'name' => 'cancel',
                                    'aria-label' => '',
                                    'type' => 'true_false',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'message' => '',
                                    'default_value' => 0,
                                    'allow_in_bindings' => 0,
                                    'ui_on_text' => '',
                                    'ui_off_text' => '',
                                    'ui' => 1,
                                    'parent_repeater' => 'field_681ddddc5758a',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'field_iw_tab_notes',
                    'label' => 'Notes',
                    'name' => '',
                    'aria-label' => '',
                    'type' => 'tab',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'placement' => 'top',
                    'endpoint' => 0,
                ],
                [
                    'key' => 'field_673f1f365b13244bb2fc',
                    'label' => 'Notes',
                    'name' => 'opening_hours_notes',
                    'aria-label' => '',
                    'type' => 'wysiwyg',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'default_value' => '',
                    'allow_in_bindings' => 0,
                    'tabs' => 'all',
                    'toolbar' => 'simple',
                    'media_upload' => 0,
                    'delay' => 0,
                ],
                [
                    'key' => 'field_iw_tab_exceptions',
                    'label' => 'Exceptions',
                    'name' => '',
                    'aria-label' => '',
                    'type' => 'tab',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'placement' => 'top',
                    'endpoint' => 0,
                ],
                [
                    'key' => 'field_iw_exceptions',
                    'label' => 'Exceptions',
                    'name' => 'exceptions',
                    'aria-label' => '',
                    'type' => 'repeater',
                    'instructions' => 'Use exceptions to cancel a whole date, cancel a specific slot, or override capacity for a slot/date. You can override Greek movable and fixed holidays in which the museum is closed by default. 
                        Override Day  → Cancel Range → Schedule → Holidays',
                    'required' => 0,

                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'layout' => 'row',
                    'pagination' => 0,
                    'min' => 0,
                    'max' => 0,
                    'collapsed' => '',
                    'button_label' => 'Add Exception',
                    'rows_per_page' => 20,
                    'sub_fields' => [
                        [
                            'key' => 'field_iw_exception_type',
                            'label' => 'Type',
                            'name' => 'type',
                            'aria-label' => '',
                            'type' => 'radio',
                            'instructions' => '',
                            'required' => 1,
                            'conditional_logic' => 0,
                            'wrapper' => [
                                'width' => '100',
                                'class' => '',
                                'id' => '',
                            ],
                            'choices' => [
                                'cancel_range' => 'Cancel date (or range)',
                                'override_day' => 'Override day schedule',
                            ],
                            'default_value' => 'cancel_range',
                            'return_format' => 'value',
                            'allow_null' => 0,
                            'other_choice' => 0,
                            'allow_in_bindings' => 0,
                            'layout' => 'horizontal',
                            'save_other_choice' => 0,
                        ],
                        [
                            'key' => 'field_iw_exception_date',
                            'label' => 'Date',
                            'name' => 'date',
                            'aria-label' => '',
                            'type' => 'date_picker',
                            'instructions' => '',
                            'required' => 1,
                            'conditional_logic' => 0,
                            'wrapper' => [
                                'width' => '20',
                                'class' => '',
                                'id' => '',
                            ],
                            'display_format' => 'd/m/Y',
                            'return_format' => 'd/m/Y',
                            'first_day' => 1,
                            'default_to_current_date' => 0,
                        ],
                        [
                            'key' => 'field_iw_exception_date_to',
                            'label' => 'End date (optional)',
                            'name' => 'date_to',
                            'aria-label' => '',
                            'type' => 'date_picker',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_iw_exception_type',
                                        'operator' => '==',
                                        'value' => 'cancel_range',
                                    ],
                                ],
                            ],
                            'wrapper' => [
                                'width' => '20',
                                'class' => '',
                                'id' => '',
                            ],
                            'display_format' => 'd/m/Y',
                            'return_format' => 'd/m/Y',
                            'first_day' => 1,
                            'default_to_current_date' => 0,
                        ],
                        [
                            'key' => 'field_iw_exception_range_recurrence',
                            'label' => 'Recurrence',
                            'name' => 'range_recurrence',
                            'aria-label' => '',
                            'type' => 'radio',
                            'instructions' => '',
                            'required' => 1,
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_iw_exception_type',
                                        'operator' => '==',
                                        'value' => 'cancel_range',
                                    ],
                                ],
                            ],
                            'wrapper' => [
                                'width' => '60',
                                'class' => '',
                                'id' => '',
                            ],
                            'choices' => [
                                'single_year' => 'This year only (use selected dates)',
                                'every_year' => 'Every year (recurring)',
                            ],
                            'default_value' => 'single_year',
                            'return_format' => 'value',
                            'allow_null' => 0,
                            'other_choice' => 0,
                            'layout' => 'horizontal',
                            'save_other_choice' => 0,
                        ],

                        [
                            'key' => 'field_iw_exception_override_slots',
                            'label' => 'Override slots',
                            'name' => 'slots',
                            'aria-label' => '',
                            'type' => 'repeater',
                            'instructions' => 'Define the full slots schedule for this date (replaces the default schedule for that day).',
                            'required' => 0,
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_iw_exception_type',
                                        'operator' => '==',
                                        'value' => 'override_day',
                                    ],
                                ],
                            ],
                            'wrapper' => [
                                'width' => '100',
                                'class' => '',
                                'id' => '',
                            ],
                            'layout' => 'block',
                            'pagination' => 0,
                            'min' => 0,
                            'max' => 0,
                            'collapsed' => '',
                            'button_label' => 'Add Slot',
                            'rows_per_page' => 20,
                            'sub_fields' => [
                                [
                                    'key' => 'field_iw_exception_override_time_from',
                                    'label' => 'From',
                                    'name' => 'time_from',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => 1,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'H:i',
                                    'return_format' => 'H:i',
                                ],
                                [
                                    'key' => 'field_iw_exception_override_time_to',
                                    'label' => 'To (optional)',
                                    'name' => 'time_to',
                                    'aria-label' => '',
                                    'type' => 'time_picker',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'display_format' => 'H:i',
                                    'return_format' => 'H:i',
                                ],
                                [
                                    'key' => 'field_iw_exception_override_capacity',
                                    'label' => 'Capacity',
                                    'name' => 'capacity',
                                    'aria-label' => '',
                                    'type' => 'number',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'default_value' => '',
                                    'min' => 0,
                                    'step' => 1,
                                ],
                                [
                                    'key' => 'field_iw_exception_override_staff',
                                    'label' => 'Staff',
                                    'name' => 'staff',
                                    'aria-label' => '',
                                    'type' => 'post_object',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '100',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'post_type' => [ 'staff', 'external-staff' ],
                                    'taxonomy' => '',
                                    'allow_null' => 1,
                                    'multiple' => 1,
                                    'return_format' => 'id',
                                    'ui' => 1,
                                ],
                                self::slot_organizers_field( 'field_iw_exception_override_organizers' ),
                                [
                                    'key' => 'field_iw_exception_override_cancel',
                                    'label' => 'Canceled',
                                    'name' => 'cancel',
                                    'aria-label' => '',
                                    'type' => 'true_false',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '25',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'message' => '',
                                    'default_value' => 0,
                                    'ui_on_text' => '',
                                    'ui_off_text' => '',
                                    'ui' => 1,
                                ],
                            ],
                        ],
                        [
                            'key' => 'field_iw_exception_note',
                            'label' => 'Note',
                            'name' => 'note',
                            'aria-label' => '',
                            'type' => 'text',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => [
                                'width' => '20',
                                'class' => '',
                                'id' => '',
                            ],
                            'default_value' => '',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ],
                    ],
                ],


                [
                    'key' => 'field_iw_tab_tickets',
                    'label' => 'Tickets',
                    'name' => '',
                    'aria-label' => '',
                    'type' => 'tab',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'placement' => 'top',
                    'endpoint' => 0,
                ],


            ], [$ticket_categories_fields, $custom_ticket_categories_field], $this->get_tickets_fields_for_post_types( [
                'event'            => 'Event',
                'guided-tour'      => 'Guided Tour',
                'exhibition'       => 'Exhibition',
                'learning-program' => 'Learning Program',
                'experience'       => 'Experience',
                'building'         => 'Building',
            ] ),

                [
                    [
                        'key' => 'field_iw_tab_booking_rules',
                        'label' => 'Booking Rules',
                        'name' => '',
                        'aria-label' => '',
                        'type' => 'tab',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ],
                        'placement' => 'top',
                        'endpoint' => 0,
                    ],
                    [
                        'key' => 'field_iw_ticketing_booking_rules_mode',
                        'label' => 'Booking rules mode',
                        'name' => 'iw_ticketing_booking_rules_mode',
                        'aria-label' => '',
                        'type' => 'radio',
                        'instructions' => 'Choose how booking availability is determined for this post.',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ],
                        'choices' => [
                            'inherit'  => 'Global Ticketing Settings',
                            'relative' => 'Relative window (days/hours/minutes)',
                            'absolute' => 'Absolute booking dates (start/end)',
                        ],
                        'default_value' => 'inherit',
                        'return_format' => 'value',
                        'allow_null' => 0,
                        'other_choice' => 0,
                        'layout' => 'horizontal',
                        'save_other_choice' => 0,
                    ],
                    [
                        'key' => 'field_iw_ticketing_booking_window_override_group',
                        'label' => 'Relative booking window override',
                        'name' => 'iw_ticketing_booking_window_override',
                        'aria-label' => '',
                        'type' => 'group',
                        'instructions' => 'Define how far in advance and how close to the slot start users are allowed to book for this post.',
                        'required' => 0,
                        'conditional_logic' => [
                            [
                                [
                                    'field' => 'field_iw_ticketing_booking_rules_mode',
                                    'operator' => '==',
                                    'value' => 'relative',
                                ],
                            ],
                        ],
                        'wrapper' => [
                            'width' => '100',
                            'class' => '',
                            'id' => '',
                        ],
                        'layout' => 'table',
                        'sub_fields' => [
                            [
                                'key' => 'field_iw_ticketing_booking_override_earliest_group',
                                'label' => 'Earliest booking (before slot)',
                                'name' => 'earliest',
                                'type' => 'group',
                                'instructions' => 'Users can book starting this long before the slot start time.',
                                'required' => 0,
                                'layout' => 'table',
                                'sub_fields' => [
                                    [
                                        'key' => 'field_iw_ticketing_booking_override_earliest_days',
                                        'label' => 'Days',
                                        'name' => 'days',
                                        'type' => 'number',
                                        'default_value' => 60,
                                        'min' => 0,
                                        'max' => 365,
                                        'step' => 1,
                                        'wrapper' => [ 'width' => '33' ],
                                    ],
                                    [
                                        'key' => 'field_iw_ticketing_booking_override_earliest_hours',
                                        'label' => 'Hours',
                                        'name' => 'hours',
                                        'type' => 'number',
                                        'default_value' => 0,
                                        'min' => 0,
                                        'max' => 23,
                                        'step' => 1,
                                        'wrapper' => [ 'width' => '33' ],
                                    ],
                                    [
                                        'key' => 'field_iw_ticketing_booking_override_earliest_minutes',
                                        'label' => 'Minutes',
                                        'name' => 'minutes',
                                        'type' => 'number',
                                        'default_value' => 0,
                                        'min' => 0,
                                        'max' => 59,
                                        'step' => 1,
                                        'wrapper' => [ 'width' => '33' ],
                                    ],
                                ],
                            ],
                            [
                                'key' => 'field_iw_ticketing_booking_override_latest_group',
                                'label' => 'Latest booking (cutoff before slot)',
                                'name' => 'latest',
                                'type' => 'group',
                                'instructions' => 'Users can book up to this long before the slot start time. Use 0 to allow booking until slot start.',
                                'required' => 0,
                                'layout' => 'table',
                                'sub_fields' => [
                                    [
                                        'key' => 'field_iw_ticketing_booking_override_latest_days',
                                        'label' => 'Days',
                                        'name' => 'days',
                                        'type' => 'number',
                                        'default_value' => 0,
                                        'min' => 0,
                                        'max' => 365,
                                        'step' => 1,
                                        'wrapper' => [ 'width' => '33' ],
                                    ],
                                    [
                                        'key' => 'field_iw_ticketing_booking_override_latest_hours',
                                        'label' => 'Hours',
                                        'name' => 'hours',
                                        'type' => 'number',
                                        'default_value' => 2,
                                        'min' => 0,
                                        'max' => 23,
                                        'step' => 1,
                                        'wrapper' => [ 'width' => '33' ],
                                    ],
                                    [
                                        'key' => 'field_iw_ticketing_booking_override_latest_minutes',
                                        'label' => 'Minutes',
                                        'name' => 'minutes',
                                        'type' => 'number',
                                        'default_value' => 0,
                                        'min' => 0,
                                        'max' => 59,
                                        'step' => 1,
                                        'wrapper' => [ 'width' => '33' ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'field_iw_ticketing_booking_absolute_override_group',
                        'label' => 'Absolute booking dates override',
                        'name' => 'iw_ticketing_booking_absolute_override',
                        'aria-label' => '',
                        'type' => 'group',
                        'instructions' => 'Allow booking only within a fixed date/time range (independent of slot time).',
                        'required' => 0,
                        'conditional_logic' => [
                            [
                                [
                                    'field' => 'field_iw_ticketing_booking_rules_mode',
                                    'operator' => '==',
                                    'value' => 'absolute',
                                ],
                            ],
                        ],
                        'wrapper' => [
                            'width' => '100',
                            'class' => '',
                            'id' => '',
                        ],
                        'layout' => 'block',
                        'sub_fields' => [
                            [
                                'key' => 'field_iw_ticketing_booking_absolute_start',
                                'label' => 'Booking start (date/time)',
                                'name' => 'start',
                                'aria-label' => '',
                                'type' => 'date_time_picker',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => [
                                    'width' => '50',
                                    'class' => '',
                                    'id' => '',
                                ],
                                'display_format' => 'd/m/Y H:i',
                                'return_format' => 'Y-m-d H:i:s',
                                'first_day' => 1,
                                'allow_in_bindings' => 0,
                            ],
                            [
                                'key' => 'field_iw_ticketing_booking_absolute_end',
                                'label' => 'Booking end (date/time)',
                                'name' => 'end',
                                'aria-label' => '',
                                'type' => 'date_time_picker',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => [
                                    'width' => '50',
                                    'class' => '',
                                    'id' => '',
                                ],
                                'display_format' => 'd/m/Y H:i',
                                'return_format' => 'Y-m-d H:i:s',
                                'first_day' => 1,
                                'allow_in_bindings' => 0,
                            ],
                        ],
                    ],
                ]


            ),
            'location' => $this->get_post_tpyes_locations(),
        ] );

    }


}

new IW_Ticketing_ACF();
