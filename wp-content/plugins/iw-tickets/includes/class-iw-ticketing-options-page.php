<?php

class IW_Ticketing_Options_Page {

    const OPTION_PAGE_SLUG = 'iw-ticketing-options';

    public function __construct() {
        add_action( 'acf/init', [ $this, 'register_options_page' ] );
        add_action( 'acf/init', [ $this, 'register_field_group' ] );
        add_action( 'acf/init', [ $this, 'register_wallet_acf_fields' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_filter( 'acf/load_field/key=field_iw_ticketing_supported_post_types', [ $this, 'load_supported_post_types_field_choices' ] );
        add_filter( 'acf/load_value/key=field_iw_ticketing_supported_post_types', [ $this, 'load_supported_post_types_field_value' ], 10, 3 );
    }

    public function register_options_page() {
        acf_add_options_page( [
            'page_title' => __( 'Ticketing Settings', 'iw' ),
            'menu_title' => __( 'Ticketing', 'iw' ),
            'menu_slug'  => self::OPTION_PAGE_SLUG,
            'capability' => 'manage_options',
            'redirect'   => false,
        ] );
    }

    public function enqueue_admin_scripts( $hook ): void {
        if ( ! is_string( $hook ) || strpos( $hook, self::OPTION_PAGE_SLUG ) === false ) {
            return;
        }

        wp_enqueue_script( 'jquery' );
        wp_enqueue_style( 'dashicons' );

        $config = [
            'restUrl'             => esc_url_raw( rest_url( 'iw/v1/cashier/zebra/test-print' ) ),
            'nonce'               => wp_create_nonce( 'wp_rest' ),
            'sendingText'         => __( 'Sending test print...', 'iw' ),
            'queuedText'          => __( 'Test print queued.', 'iw' ),
            'sentText'            => __( 'Test print sent.', 'iw' ),
            'failedText'          => __( 'Test print failed.', 'iw' ),
            'generateTokenText'   => __( 'Generate', 'iw' ),
            'copyTokenText'       => __( 'Copy', 'iw' ),
            'copiedTokenText'     => __( 'Copied', 'iw' ),
            'overwriteTokenText'  => __( 'Replace the existing token?', 'iw' ),
        ];

        wp_add_inline_script(
            'jquery',
            'window.iwZebraTestPrint=' . wp_json_encode( $config ) . ';' . <<<'JS'
(function($, config) {
    if (!config) return;

    function makeToken() {
        var length = 32;
        var bytes = new Uint8Array(length);

        if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
            window.crypto.getRandomValues(bytes);
        } else {
            for (var i = 0; i < length; i += 1) {
                bytes[i] = Math.floor(Math.random() * 256);
            }
        }

        var binary = '';
        for (var index = 0; index < bytes.length; index += 1) {
            binary += String.fromCharCode(bytes[index]);
        }

        return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
    }

    function addTokenButtons($scope) {
        var fieldKeys = [
            'field_iw_zebra_print_agent_token',
            'field_iw_zebra_print_websocket_token'
        ];

        fieldKeys.forEach(function(fieldKey) {
            ($scope || $(document)).find('.acf-field[data-key="' + fieldKey + '"]').each(function() {
                var $field = $(this);
                var $input = $field.find('.acf-input input').first();

                if (!$input.length || $field.find('.js-iw-zebra-generate-token').length) {
                    return;
                }

                var $control = $('<div class="iw-zebra-token-control"></div>');
                var $button = $('<button type="button" class="button button-secondary js-iw-zebra-generate-token"></button>');
                var $copyButton = $('<button type="button" class="button button-secondary js-iw-zebra-copy-token"></button>');

                $button.text(config.generateTokenText || 'Generate');
                $copyButton
                    .attr('aria-label', config.copyTokenText || 'Copy')
                    .attr('title', config.copyTokenText || 'Copy')
                    .html('<span class="dashicons dashicons-clipboard" aria-hidden="true"></span>');
                $input.addClass('iw-zebra-token-input').wrap($control);
                $input.after($copyButton);
                $copyButton.after($button);
            });
        });
    }

    var tokenStyles = [
        '.iw-zebra-token-control{display:flex;align-items:center;gap:8px;}',
        '.iw-zebra-token-control .iw-zebra-token-input{flex:1;min-width:0;}',
        '.iw-zebra-token-control .js-iw-zebra-generate-token,.iw-zebra-token-control .js-iw-zebra-copy-token{flex:0 0 auto;height:32px;min-height:32px;margin:0;line-height:32px;}',
        '.iw-zebra-token-control .js-iw-zebra-generate-token{padding:0 14px;}',
        '.iw-zebra-token-control .js-iw-zebra-copy-token{display:inline-flex;align-items:center;justify-content:center;width:32px;padding:0;}',
        '.iw-zebra-token-control .js-iw-zebra-copy-token .dashicons{width:16px;height:16px;font-size:16px;line-height:16px;}'
    ].join('');

    if (!document.getElementById('iw-zebra-admin-token-styles')) {
        $('<style id="iw-zebra-admin-token-styles"></style>').text(tokenStyles).appendTo(document.head);
    }

    $(function() {
        addTokenButtons($(document));
    });

    if (window.acf && typeof window.acf.addAction === 'function') {
        window.acf.addAction('ready', function($el) {
            addTokenButtons($el || $(document));
        });
        window.acf.addAction('append', function($el) {
            addTokenButtons($el || $(document));
        });
    }

    $(document).on('click', '.js-iw-zebra-generate-token', function(event) {
        event.preventDefault();

        var $button = $(this);
        var $input = $button.siblings('input').first();

        if (!$input.length) {
            return;
        }

        if ($input.val() && !window.confirm(config.overwriteTokenText || 'Replace the existing token?')) {
            return;
        }

        $input.val(makeToken()).trigger('input').trigger('change');
    });

    $(document).on('click', '.js-iw-zebra-copy-token', function(event) {
        event.preventDefault();

        var $button = $(this);
        var $input = $button.siblings('input').first();
        var token = $input.val() || '';

        if (!$input.length || !token) {
            $input.trigger('focus');
            return;
        }

        function showCopyIcon() {
            $button
                .attr('aria-label', config.copyTokenText || 'Copy')
                .attr('title', config.copyTokenText || 'Copy')
                .html('<span class="dashicons dashicons-clipboard" aria-hidden="true"></span>');
        }

        function showCopied() {
            $button
                .attr('aria-label', config.copiedTokenText || 'Copied')
                .attr('title', config.copiedTokenText || 'Copied')
                .html('<span class="dashicons dashicons-saved" aria-hidden="true"></span>')
                .prop('disabled', true);
            window.setTimeout(function() {
                showCopyIcon();
                $button.prop('disabled', false);
            }, 1200);
        }

        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            navigator.clipboard.writeText(token).then(showCopied).catch(function() {
                $input.trigger('focus').trigger('select');
                document.execCommand('copy');
                showCopied();
            });
            return;
        }

        $input.trigger('focus').trigger('select');
        document.execCommand('copy');
        showCopied();
    });

    $(document).on('click', '.js-iw-zebra-test-print', function(event) {
        event.preventDefault();

        var $button = $(this);
        var $status = $button.siblings('.js-iw-zebra-test-print-status');

        $button.prop('disabled', true);
        $status.text(config.sendingText).removeClass('error').removeClass('success');

        fetch(config.restUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': config.nonce
            },
            body: JSON.stringify({})
        })
            .then(function(response) {
                return response.json().catch(function() {
                    return {};
                }).then(function(payload) {
                    if (!response.ok || payload.success === false) {
                        throw new Error(payload.message || config.failedText);
                    }
                    return payload;
                });
            })
            .then(function(payload) {
                $status.text(payload.queued ? config.queuedText : config.sentText).addClass('success');
            })
            .catch(function(error) {
                $status.text(error.message || config.failedText).addClass('error');
            })
            .finally(function() {
                $button.prop('disabled', false);
            });
    });
})(jQuery, window.iwZebraTestPrint);
JS
        );
    }

    private function get_ticketing_post_type_choices(): array {
        $choices = [];
        $post_types = [];

        if ( class_exists( 'IW_Architecture' ) ) {
            if ( ! empty( IW_Architecture::$post_types ) && is_array( IW_Architecture::$post_types ) ) {
                $post_types = array_keys( IW_Architecture::$post_types );
            } elseif ( property_exists( 'IW_Architecture', 'post_type_names' ) && ! empty( IW_Architecture::$post_type_names ) && is_array( IW_Architecture::$post_type_names ) ) {
                $post_types = IW_Architecture::$post_type_names;
            }
        }

        if ( empty( $post_types ) ) {
            $post_types = $this->get_default_supported_post_types();
        }

        $post_types = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) $post_types ) ) ) );

        foreach ( $post_types as $post_type ) {
            $choices[ $post_type ] = $this->get_post_type_label( $post_type );
        }

        return $choices;
    }

    private function get_default_supported_post_types(): array {
        return class_exists( 'IW_Ticketing_ACF' )
            ? IW_Ticketing_ACF::$post_types
            : [ 'event', 'guided-tour', 'exhibition', 'learning-program', 'experience', 'building' ];
    }

    private function get_post_type_label( string $post_type ): string {
        // Prefer project-defined labels (IW_Architecture) when available.
        if ( class_exists( 'IW_Architecture' ) && isset( IW_Architecture::$post_types[ $post_type ] ) ) {
            $pt = IW_Architecture::$post_types[ $post_type ];
            if ( is_object( $pt ) && ! empty( $pt->label ) ) {
                return (string) $pt->label;
            }
        }

        // Fallback to WP post type labels.
        $obj = get_post_type_object( $post_type );
        if ( $obj && ! empty( $obj->labels ) ) {
            // Prefer singular name for tab labels.
            if ( ! empty( $obj->labels->singular_name ) ) {
                return (string) $obj->labels->singular_name;
            }
            if ( ! empty( $obj->labels->name ) ) {
                return (string) $obj->labels->name;
            }
        }

        // Final fallback: humanize the slug.
        $human = str_replace( [ '-', '_' ], ' ', $post_type );
        $human = ucwords( $human );
        return $human !== '' ? $human : $post_type;
    }

    private function suffix_acf_field_tree( array $field, string $suffix ): array {
        // Ensure uniqueness for ACF field keys and names when duplicating.
        if ( isset( $field['key'] ) && is_string( $field['key'] ) && $field['key'] !== '' ) {
            $field['key'] = $field['key'] . '__' . $suffix;
        }

        if ( isset( $field['name'] ) && is_string( $field['name'] ) && $field['name'] !== '' ) {
            $field['name'] = $field['name'] . '__' . $suffix;
        }

        // Also update conditional logic references to point to the duplicated fields.
        if ( isset( $field['conditional_logic'] ) && is_array( $field['conditional_logic'] ) ) {
            foreach ( $field['conditional_logic'] as $group_index => $group ) {
                if ( ! is_array( $group ) ) {
                    continue;
                }
                foreach ( $group as $rule_index => $rule ) {
                    if ( ! is_array( $rule ) ) {
                        continue;
                    }
                    if ( isset( $rule['field'] ) && is_string( $rule['field'] ) && $rule['field'] !== '' ) {
                        // ACF stores references by field key (e.g. field_xxx). When duplicating,
                        // we suffix keys, so we must suffix references too.
                        if ( ! str_ends_with( $rule['field'], '__' . $suffix ) ) {
                            $rule['field'] = $rule['field'] . '__' . $suffix;
                        }
                    }
                    $group[ $rule_index ] = $rule;
                }
                $field['conditional_logic'][ $group_index ] = $group;
            }
        }

        if ( ! empty( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
            $new_sub_fields = [];
            foreach ( $field['sub_fields'] as $sub ) {
                if ( is_array( $sub ) ) {
                    $new_sub_fields[] = $this->suffix_acf_field_tree( $sub, $suffix );
                } else {
                    $new_sub_fields[] = $sub;
                }
            }
            $field['sub_fields'] = $new_sub_fields;
        }

        return $field;
    }

    private function make_acf_tab_field( string $key, string $label, string $placement = 'top' ): array {
        return [
            'key' => $key,
            'label' => $label,
            'name' => '',
            'type' => 'tab',
            'placement' => $placement,
            'endpoint' => 0,
        ];
    }

    private function make_acf_accordion_field( string $key, string $label, bool $open = false, bool $endpoint = false ): array {
        return [
            'key' => $key,
            'label' => $label,
            'name' => '',
            'type' => 'accordion',
            'open' => $open ? 1 : 0,
            'multi_expand' => 0,
            'endpoint' => $endpoint ? 1 : 0,
        ];
    }

    private function get_wallet_options_fields(): array {
        $fields = [
            $this->make_acf_tab_field( 'field_iw_ticketing_tab_wallet', 'Wallet' ),
        ];

        $general_fields = $this->walletFields['fields'];
        $fields[] = $this->make_acf_accordion_field( 'field_wallet_options_default_accordion', 'Default', true );
        foreach ( $general_fields as $field ) {
            $fields[] = $field;
        }

        foreach ( IW_Ticketing::get_supported_post_types() as $supported_post_type ) {
            $tab_label = $this->get_post_type_label( (string) $supported_post_type );
            $fields[] = $this->make_acf_accordion_field( 'field_wallet_options_' . $supported_post_type . '_accordion', $tab_label );

            foreach ( $general_fields as $field ) {
                if ( is_array( $field ) ) {
                    $fields[] = $this->suffix_acf_field_tree( $field, (string) $supported_post_type );
                }
            }
        }

        $fields[] = $this->make_acf_accordion_field( 'field_wallet_options_end_accordion', '', false, true );

        return $fields;
    }

    public function load_supported_post_types_field_choices( $field ) {
        $field['choices'] = $this->get_ticketing_post_type_choices();
        return $field;
    }

    public function load_supported_post_types_field_value( $value, $post_id, $field ) {
        $value = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) $value ) ) ) );

        if ( empty( $value ) ) {
            return $this->get_default_supported_post_types();
        }

        return $value;
    }

    public function register_field_group() {
        acf_add_local_field_group( [
            'key'    => 'group_iw_ticketing_ticket_categories',
            'title'  => __( 'Ticketing Settings', 'iw' ),
            'fields' => array_merge( [
                [
                    'key'       => 'field_iw_ticketing_tab_general',
                    'label'     => __( 'General', 'iw' ),
                    'name'      => '',
                    'type'      => 'tab',
                    'placement' => 'top',
                    'endpoint'  => 0,
                ],
                [
                    'key'           => 'field_iw_ticketing_supported_post_types',
                    'label'         => __( 'Supported Post Types', 'iw' ),
                    'name'          => 'iw_ticketing_supported_post_types',
                    'type'          => 'select',
                    'instructions'  => __( 'Post types that can be configured and sold through ticketing.', 'iw' ),
                    'choices'       => [],
                    'multiple'      => 1,
                    'ui'            => 1,
                    'ajax'          => 0,
                    'placeholder'   => __( 'Select post types…', 'iw' ),
                    'allow_null'    => 0,
                    'return_format' => 'value',
                    'default_value' => [],
                    'required'      => 0,
                    'wrapper'       => [ 'width' => '50' ],
                ],
                [
                    'key'           => 'field_iw_ticketing_slot_hold_minutes',
                    'label'         => __( 'Slot Hold Duration (minutes)', 'iw' ),
                    'name'          => 'iw_ticketing_slot_hold_minutes',
                    'type'          => 'number',
                    'instructions'  => __( 'How long (in minutes) a selected slot is reserved before it expires. If empty, defaults to 15 minutes.', 'iw' ),
                    'required'      => 0,
                    'default_value' => 15,
                    'min'           => 1,
                    'max'           => 1440,
                    'step'          => 1,
                    'wrapper'       => [ 'width' => '50' ],
                ],
                [
                    'key'          => 'field_iw_ticketing_booking_window_group',
                    'label'        => __( 'Booking Window', 'iw' ),
                    'name'         => 'iw_ticketing_booking_window',
                    'type'         => 'group',
                    'instructions' => __( 'Define how far in advance and how close to the slot start users are allowed to book.', 'iw' ),
                    'required'     => 0,
                    'wrapper'      => [ 'width' => '100' ],
                    'layout'       => 'table',
                    'sub_fields'   => [
                        [
                            'key'          => 'field_iw_ticketing_earliest_booking_group',
                            'label'        => __( 'Earliest booking (before slot)', 'iw' ),
                            'name'         => 'earliest',
                            'type'         => 'group',
                            'instructions' => __( 'Users can book starting this long before the slot start time.', 'iw' ),
                            'required'     => 0,
                            'layout'       => 'table',
                            'sub_fields'   => [
                                [
                                    'key'           => 'field_iw_ticketing_earliest_days',
                                    'label'         => __( 'Days', 'iw' ),
                                    'name'          => 'days',
                                    'type'          => 'number',
                                    'default_value' => 60,
                                    'min'           => 0,
                                    'max'           => 365,
                                    'step'          => 1,
                                    'wrapper'       => [ 'width' => '33' ],
                                ],
                                [
                                    'key'           => 'field_iw_ticketing_earliest_hours',
                                    'label'         => __( 'Hours', 'iw' ),
                                    'name'          => 'hours',
                                    'type'          => 'number',
                                    'default_value' => 0,
                                    'min'           => 0,
                                    'max'           => 23,
                                    'step'          => 1,
                                    'wrapper'       => [ 'width' => '33' ],
                                ],
                                [
                                    'key'           => 'field_iw_ticketing_earliest_minutes',
                                    'label'         => __( 'Minutes', 'iw' ),
                                    'name'          => 'minutes',
                                    'type'          => 'number',
                                    'default_value' => 0,
                                    'min'           => 0,
                                    'max'           => 59,
                                    'step'          => 1,
                                    'wrapper'       => [ 'width' => '33' ],
                                ],
                            ],
                        ],
                        [
                            'key'          => 'field_iw_ticketing_latest_booking_group',
                            'label'        => __( 'Latest booking (cutoff before slot)', 'iw' ),
                            'name'         => 'latest',
                            'type'         => 'group',
                            'instructions' => __( 'Users can book up to this long before the slot start time. Use 0 to allow booking until slot start.', 'iw' ),
                            'required'     => 0,
                            'layout'       => 'table',
                            'sub_fields'   => [
                                [
                                    'key'           => 'field_iw_ticketing_latest_days',
                                    'label'         => __( 'Days', 'iw' ),
                                    'name'          => 'days',
                                    'type'          => 'number',
                                    'default_value' => 0,
                                    'min'           => 0,
                                    'max'           => 365,
                                    'step'          => 1,
                                    'wrapper'       => [ 'width' => '33' ],
                                ],
                                [
                                    'key'           => 'field_iw_ticketing_latest_hours',
                                    'label'         => __( 'Hours', 'iw' ),
                                    'name'          => 'hours',
                                    'type'          => 'number',
                                    'default_value' => 1,
                                    'min'           => 0,
                                    'max'           => 23,
                                    'step'          => 1,
                                    'wrapper'       => [ 'width' => '33' ],
                                ],
                                [
                                    'key'           => 'field_iw_ticketing_latest_minutes',
                                    'label'         => __( 'Minutes', 'iw' ),
                                    'name'          => 'minutes',
                                    'type'          => 'number',
                                    'default_value' => 0,
                                    'min'           => 0,
                                    'max'           => 59,
                                    'step'          => 1,
                                    'wrapper'       => [ 'width' => '33' ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key'           => 'field_iw_ticketing_excluded_dates_repeater',
                    'label'         => __( 'Excluded dates', 'iw' ),
                    'name'          => 'iw_ticketing_excluded_dates',
                    'type'          => 'repeater',
                    'instructions'  => __( 'Global excluded dates. Use “Recurring (MM-DD)” for fixed public holidays and “Single date” for one-off closures.', 'iw' ),
                    'required'      => 0,
                    'wrapper'       => [ 'width' => '100' ],
                    'layout'        => 'table',
                    'button_label'  => __( 'Add excluded date', 'iw' ),
                    'acfe_repeater_stylised_button' => 0,
                    'default_value' => [
                        [ 'kind' => 'recurring', 'month_day' => '01-01', 'label' => 'New Year\'s Day' ],
                        [ 'kind' => 'recurring', 'month_day' => '01-06', 'label' => 'Epiphany' ],
                        [ 'kind' => 'recurring', 'month_day' => '03-25', 'label' => 'Independence Day' ],
                        [ 'kind' => 'recurring', 'month_day' => '05-01', 'label' => 'Labour Day' ],
                        [ 'kind' => 'recurring', 'month_day' => '08-15', 'label' => 'Assumption of Mary' ],
                        [ 'kind' => 'recurring', 'month_day' => '10-28', 'label' => 'Ohi Day' ],
                        [ 'kind' => 'recurring', 'month_day' => '12-25', 'label' => 'Christmas Day' ],
                        [ 'kind' => 'recurring', 'month_day' => '12-26', 'label' => 'Synaxis of the Mother of God' ],
                    ],
                    'sub_fields'    => [
                        [
                            'key'           => 'field_iw_ticketing_excluded_date_kind',
                            'label'         => __( 'Type', 'iw' ),
                            'name'          => 'kind',
                            'type'          => 'radio',
                            'choices'       => [
                                'recurring' => __( 'Recurring (MM-DD)', 'iw' ),
                                'single'    => __( 'Single date', 'iw' ),
                            ],
                            'default_value' => 'recurring',
                            'layout'        => 'horizontal',
                            'wrapper'       => [ 'width' => '25' ],
                        ],
                        [
                            'key'               => 'field_iw_ticketing_excluded_date_month_day',
                            'label'             => __( 'MM-DD', 'iw' ),
                            'name'              => 'month_day',
                            'type'              => 'text',
                            'instructions'      => __( 'Format: MM-DD (e.g. 03-25).', 'iw' ),
                            'required'          => 0,
                            'conditional_logic' => [
                                [
                                    [
                                        'field'    => 'field_iw_ticketing_excluded_date_kind',
                                        'operator' => '==',
                                        'value'    => 'recurring',
                                    ],
                                ],
                            ],
                            'wrapper'           => [ 'width' => '20' ],
                        ],
                        [
                            'key'               => 'field_iw_ticketing_excluded_date_single',
                            'label'             => __( 'Date', 'iw' ),
                            'name'              => 'date',
                            'type'              => 'date_picker',
                            'instructions'      => '',
                            'required'          => 0,
                            'conditional_logic' => [
                                [
                                    [
                                        'field'    => 'field_iw_ticketing_excluded_date_kind',
                                        'operator' => '==',
                                        'value'    => 'single',
                                    ],
                                ],
                            ],
                            'display_format'    => 'd/m/Y',
                            'return_format'     => 'Y-m-d',
                            'first_day'         => 1,
                            'wrapper'           => [ 'width' => '25' ],
                        ],
                        [
                            'key'      => 'field_iw_ticketing_excluded_date_label',
                            'label'    => __( 'Label', 'iw' ),
                            'name'     => 'label',
                            'type'     => 'text',
                            'required' => 0,
                            'wrapper'  => [ 'width' => '30' ],
                        ],
                    ],
                ],
                [
                    'key'           => 'field_iw_ticketing_exclude_orthodox_movable_holidays',
                    'label'         => __( 'Exclude Orthodox movable holidays (Easter-based)', 'iw' ),
                    'name'          => 'iw_ticketing_exclude_orthodox_movable_holidays',
                    'type'          => 'true_false',
                    'instructions'  => __( 'If enabled, the calendar will also exclude Easter-based movable holidays (Clean Monday, Easter Sunday/Monday, etc.).', 'iw' ),
                    'required'      => 0,
                    'default_value' => 1,
                    'ui'            => 1,
                    'ui_on_text'    => 'YES',
                    'ui_off_text'   => 'NO',
                    'wrapper'       => [ 'width' => '50' ],
                ],
                [
                    'key'       => 'field_iw_ticketing_tab_categories',
                    'label'     => __( 'Categories', 'iw' ),
                    'name'      => '',
                    'type'      => 'tab',
                    'placement' => 'top',
                    'endpoint'  => 0,
                ],
                [
                    'key'          => 'field_iw_ticket_categories',
                    'label'        => __( 'Ticket Categories', 'iw' ),
                    'name'         => 'iw_ticket_categories',
                    'type'         => 'repeater',
                    'instructions' => __( 'Add categories and (optionally) subcategories. Each can have its own price.', 'iw' ),
                    'required'     => 1,
                    'layout'       => 'block',
                    'button_label' => __( 'Add Category', 'iw' ),
                    'acfe_repeater_stylised_button' => 0,
                    'sub_fields'   => [
                        [
                            'key'          => 'field_iw_ticket_category_label',
                            'label'        => __( 'Label', 'iw' ),
                            'name'         => 'label',
                            'type'         => 'textarea',
                            'instructions' => '&nbsp;',
                            'required'     => 1,
                            'new_lines'    => 'br',
                            'rows'         => 3,
                            'acfe_textarea_code' => 0,
                            'wrapper'      => [ 'width' => '40' ],
                        ],
                        [
                            'key'          => 'field_iw_ticket_category_description',
                            'label'        => __( 'Description', 'iw' ),
                            'name'         => 'description',
                            'type'         => 'textarea',
                            'instructions' => __( 'Optional, short description shown in UI/help texts.', 'iw' ),
                            'required'     => 0,
                            'new_lines'    => 'br',
                            'rows'         => 3,
                            'acfe_textarea_code' => 0,
                            'wrapper'      => [ 'width' => '40' ],
                        ],
                        [
                            'key'          => 'field_iw_ticket_category_key',
                            'label'        => __( 'Key', 'iw' ),
                            'name'         => 'key',
                            'type'         => 'text',
                            'instructions' => __( 'Unique identifier. ', 'iw' ),
                            'placeholder'  => 'e.g. general, reduced, free',
                            'required'     => 1,
                            'wrapper'      => [ 'width' => '20' ],
                        ],
                        [
                            'key'          => 'field_iw_ticket_subcategories',
                            'label'        => __( 'Subcategories', 'iw' ),
                            'name'         => 'subcategories',
                            'type'         => 'repeater',
                            'instructions' => __( 'If you have only one, you can add the parent category', 'iw' ),
                            'required'     => 1,
                            'layout'       => 'table',
                            'button_label' => __( 'Add Subcategory', 'iw' ),
                            'acfe_repeater_stylised_button' => 0,
                            'sub_fields'   => [
                                [
                                    'key'      => 'field_iw_ticket_subcategory_label',
                                    'label'    => __( 'Label', 'iw' ),
                                    'name'     => 'label',
                                    'type'     => 'text',
                                    'required' => 1,
                                ],
                                [
                                    'key'   => 'field_iw_ticket_subcategory_description',
                                    'label' => __( 'Description', 'iw' ),
                                    'name'  => 'description',
                                    'type'  => 'text',
                                ],
                                [
                                    'key'      => 'field_iw_ticket_subcategory_key',
                                    'label'    => __( 'Key', 'iw' ),
                                    'name'     => 'key',
                                    'type'     => 'text',
                                    'required' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key'       => 'field_iw_zebra_print_tab',
                    'label'     => __( 'Zebra Printing', 'iw' ),
                    'name'      => '',
                    'type'      => 'tab',
                    'placement' => 'top',
                    'endpoint'  => 0,
                ],
                [
                    'key'           => 'field_iw_zebra_print_mode',
                    'label'         => __( 'Mode', 'iw' ),
                    'name'          => 'iw_zebra_print_mode',
                    'type'          => 'radio',
                    'instructions'  => __( 'Direct prints from PHP. Queue sends jobs to local agents.', 'iw' ),
                    'choices'       => [
                        'direct' => __( 'Direct', 'iw' ),
                        'queue'  => __( 'Queue / Local agents', 'iw' ),
                    ],
                    'default_value' => 'direct',
                    'layout'        => 'horizontal',
                    'return_format' => 'value',
                ],
                [
                    'key'          => 'field_iw_zebra_print_queue_settings',
                    'label'        => __( 'Print Queue', 'iw' ),
                    'name'         => 'iw_zebra_print_queue_settings',
                    'type'         => 'group',
                    'instructions' => __( 'Configure the cashier Zebra print queue. Constants in wp-config.php can still override these values.', 'iw' ),
                    'required'     => 0,
                    'layout'       => 'block',
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_iw_zebra_print_mode',
                                'operator' => '==',
                                'value'    => 'queue',
                            ],
                        ],
                    ],
                    'sub_fields'   => [
                        [
                            'key'          => 'field_iw_zebra_print_agent_token',
                            'label'        => __( 'Agent token', 'iw' ),
                            'name'         => 'agent_token',
                            'type'         => 'text',
                            'instructions' => __( 'Shared secret used by local print agents.', 'iw' ),
                            'required'     => 0,
                            'wrapper'      => [ 'width' => '50' ],
                        ],
                        [
                            'key'          => 'field_iw_zebra_print_websocket_token',
                            'label'        => __( 'WebSocket token', 'iw' ),
                            'name'         => 'websocket_token',
                            'type'         => 'text',
                            'instructions' => __( 'Shared with PRINT_WS_TOKEN. If empty, the agent token is used.', 'iw' ),
                            'required'     => 0,
                            'wrapper'      => [ 'width' => '50' ],
                        ],
                        [
                            'key'           => 'field_iw_zebra_print_default_printer_id',
                            'label'         => __( 'Default printer ID', 'iw' ),
                            'name'          => 'default_printer_id',
                            'type'          => 'text',
                            'instructions'  => __( 'Fallback target when no building-specific printer is found.', 'iw' ),
                            'default_value' => 'default',
                            'placeholder'   => 'pireos-main',
                            'wrapper'       => [ 'width' => '50' ],
                        ],
                        [
                            'key'          => 'field_iw_zebra_print_websocket_notify_url',
                            'label'        => __( 'WebSocket notify URL', 'iw' ),
                            'name'         => 'websocket_notify_url',
                            'type'         => 'url',
                            'instructions' => __( 'Local hub endpoint used by WordPress to wake connected agents, e.g. http://127.0.0.1:8788/print-ws/notify.', 'iw' ),
                            'required'     => 0,
                            'placeholder'  => 'http://127.0.0.1:8788/print-ws/notify',
                            'wrapper'      => [ 'width' => '50' ],
                        ],
                        [
                            'key'          => 'field_iw_zebra_print_targets',
                            'label'        => __( 'Printer targets', 'iw' ),
                            'name'         => 'targets',
                            'type'         => 'repeater',
                            'instructions' => __( 'Each target should match the PRINTER_ID used by a local agent.', 'iw' ),
                            'required'     => 0,
                            'layout'       => 'block',
                            'button_label' => __( 'Add printer target', 'iw' ),
                            'acfe_repeater_stylised_button' => 0,
                            'sub_fields'   => [
                                [
                                    'key'         => 'field_iw_zebra_print_target_id',
                                    'label'       => __( 'Printer ID', 'iw' ),
                                    'name'        => 'id',
                                    'type'        => 'text',
                                    'placeholder' => 'pireos-main',
                                    'required'    => 1,
                                    'wrapper'     => [ 'width' => '25' ],
                                ],
                                [
                                    'key'         => 'field_iw_zebra_print_target_label',
                                    'label'       => __( 'Label', 'iw' ),
                                    'name'        => 'label',
                                    'type'        => 'text',
                                    'placeholder' => __( 'Pireos cashier', 'iw' ),
                                    'required'    => 1,
                                    'wrapper'     => [ 'width' => '25' ],
                                ],
                                [
                                    'key'         => 'field_iw_zebra_print_target_subtitle',
                                    'label'       => __( 'Subtitle', 'iw' ),
                                    'name'        => 'subtitle',
                                    'type'        => 'text',
                                    'placeholder' => 'Zebra 192.168.2.9',
                                    'required'    => 0,
                                    'wrapper'     => [ 'width' => '25' ],
                                ],
                                [
                                    'key'           => 'field_iw_zebra_print_target_building_ids',
                                    'label'         => __( 'Buildings', 'iw' ),
                                    'name'          => 'building_ids',
                                    'type'          => 'post_object',
                                    'post_type'     => [ 'building' ],
                                    'multiple'      => 1,
                                    'return_format' => 'id',
                                    'ui'            => 1,
                                    'required'      => 0,
                                    'wrapper'       => [ 'width' => '25' ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key'          => 'field_iw_zebra_ticket_printer_settings',
                    'label'        => __( 'Direct Printer', 'iw' ),
                    'name'         => 'iw_zebra_ticket_printer_settings',
                    'type'         => 'group',
                    'instructions' => __( 'Used only when Mode is Direct. WordPress/PHP sends ZPL directly to this network printer.', 'iw' ),
                    'required'     => 0,
                    'layout'       => 'block',
                    'conditional_logic' => [
                        [
                            [
                                'field'    => 'field_iw_zebra_print_mode',
                                'operator' => '==',
                                'value'    => 'direct',
                            ],
                        ],
                    ],
                    'sub_fields'   => [
                        [
                            'key'           => 'field_iw_zebra_ticket_printer_host',
                            'label'         => __( 'Host / IP', 'iw' ),
                            'name'          => 'host',
                            'type'          => 'text',
                            'instructions'  => __( 'Network IP or hostname of the Zebra printer.', 'iw' ),
                            'default_value' => '192.168.2.9',
                            'placeholder'   => '192.168.2.9',
                            'required'      => 0,
                            'wrapper'       => [ 'width' => '40' ],
                        ],
                        [
                            'key'           => 'field_iw_zebra_ticket_printer_port',
                            'label'         => __( 'Port', 'iw' ),
                            'name'          => 'port',
                            'type'          => 'number',
                            'instructions'  => __( 'Raw socket port. Zebra printers usually use 9100.', 'iw' ),
                            'default_value' => 9100,
                            'placeholder'   => 9100,
                            'min'           => 1,
                            'max'           => 65535,
                            'step'          => 1,
                            'required'      => 0,
                            'wrapper'       => [ 'width' => '30' ],
                        ],
                        [
                            'key'           => 'field_iw_zebra_ticket_printer_timeout',
                            'label'         => __( 'Timeout', 'iw' ),
                            'name'          => 'timeout',
                            'type'          => 'number',
                            'instructions'  => __( 'Connection timeout in seconds.', 'iw' ),
                            'default_value' => 5,
                            'placeholder'   => 5,
                            'min'           => 1,
                            'max'           => 30,
                            'step'          => 1,
                            'required'      => 0,
                            'wrapper'       => [ 'width' => '30' ],
                        ],
                        [
                            'key'     => 'field_iw_zebra_ticket_printer_test_print',
                            'label'   => __( 'Test print', 'iw' ),
                            'name'    => '',
                            'type'    => 'message',
                            'message' => sprintf(
                                '<button type="button" class="button js-iw-zebra-test-print">%s</button><span class="js-iw-zebra-test-print-status" style="margin-left:8px;"></span>',
                                esc_html__( 'Send test print', 'iw' )
                            ),
                        ],
                    ],
                ],
            ], $this->get_wallet_options_fields() ),
            'location' => [
                [
                    [
                        'param'    => 'options_page',
                        'operator' => '==',
                        'value'    => self::OPTION_PAGE_SLUG,
                    ],
                ],
            ],
            'menu_order'            => 0,
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
            'active'                => true,
            'description'           => '',
        ] );
    }

    public function get_post_types_locations(){
        $locations = [];
        foreach (IW_Ticketing::get_supported_post_types() as $supported_post_type) {
            $locations [] = [ [ 'param' => 'post_type', 'operator' => '==', 'value' => $supported_post_type ] ];
        }
        return $locations;
    }

    public function register_wallet_acf_fields(): void {

        $fields = $this->walletFields;
        $fields[ 'location' ] = $this->get_post_types_locations();
        acf_add_local_field_group( $fields );
    }

    public $walletFields = [
        'key' => 'group_682728e236669_wallet',
        'title' => 'Wallet Ticket Options',
        'fields' => [
            [
                'key' => 'field_692eeb6e81a91_wallet',
                'label' => 'Apple',
                'name' => 'apple_wallet',
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
                        'key' => 'field_692eebdd81a94_wallet',
                        'label' => 'Background Color Type',
                        'name' => 'background_color_type',
                        'aria-label' => '',
                        'type' => 'radio',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '50',
                            'class' => '',
                            'id' => '',
                        ],
                        'choices' => [
                            'default_background_color' => 'Default',
                            'theme_background_color' => 'Theme Color',
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

                        'key' => 'field_692eebdd81a94_wallet_message',
                        'label' => '',
                        'message' => '',
                        'name' => 'theme_text_color_message',
                        'aria-label' => '',
                        'type' => 'message',
                        'instructions' => '',
                        'conditional_logic' => [
                            [
                                [
                                    'field' => 'field_692eebdd81a94_wallet',
                                    'operator' => '==',
                                    'value' => 'default_background_color',
                                ],
                            ],
                        ],
                        'wrapper' => [
                            'width' => '50',
                            'class' => '',
                            'id' => '',
                        ],
                    ],
                    [
                        'key' => 'field_692eed1181a98_wallet',
                        'label' => 'Theme Color',
                        'name' => 'theme_background_color',
                        'aria-label' => '',
                        'type' => 'radio',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => [
                            [
                                [
                                    'field' => 'field_692eebdd81a94_wallet',
                                    'operator' => '==',
                                    'value' => 'theme_background_color',
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
                        'key' => 'field_692eed4081a99_wallet',
                        'label' => 'Custom Color',
                        'name' => 'custom_background_color',
                        'aria-label' => '',
                        'type' => 'color_picker',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => [
                            [
                                [
                                    'field' => 'field_692eebdd81a94_wallet',
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
                        'key' => 'field_692eebea81a95_wallet',
                        'label' => 'Text Color Type',
                        'name' => 'text_color_type',
                        'aria-label' => '',
                        'type' => 'radio',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '50',
                            'class' => '',
                            'id' => '',
                        ],
                        'choices' => [
                            'default_text_color' => 'Default Text Color',
                            'theme_text_color' => 'Theme Text Color',
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

                        'key' => 'field_692eec4981a96_wallet_message',
                        'label' => '',
                        'message' => '',
                        'name' => 'theme_text_color_message',
                        'aria-label' => '',
                        'type' => 'message',
                        'instructions' => '',
                        'conditional_logic' => [
                            [
                                [
                                    'field' => 'field_692eebea81a95_wallet',
                                    'operator' => '==',
                                    'value' => 'default_text_color',
                                ],
                            ],
                        ],
                        'wrapper' => [
                            'width' => '50',
                            'class' => '',
                            'id' => '',
                        ],
                    ],
                    [
                        'key' => 'field_692eec8b81a97_wallet',
                        'label' => 'Theme Color',
                        'name' => 'theme_text_color',
                        'aria-label' => '',
                        'type' => 'radio',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => [
                            [
                                [
                                    'field' => 'field_692eebea81a95_wallet',
                                    'operator' => '==',
                                    'value' => 'theme_text_color',
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
                        'key' => 'field_692eec4981a96_wallet',
                        'label' => 'Custom Color',
                        'name' => 'custom_text_color',
                        'aria-label' => '',
                        'type' => 'color_picker',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => [
                            [
                                [
                                    'field' => 'field_692eebea81a95_wallet',
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
                        'key' => 'field_692eebc881a93_icon_wallet',
                        'label' => 'Icon',
                        'name' => 'icon',
                        'aria-label' => '',
                        'type' => 'image',
                        'instructions' => '87 x 87',
                        'required' => 0,
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
                        'key' => 'field_692eebc881a93_logo_wallet',
                        'label' => 'Logo',
                        'name' => 'logo',
                        'aria-label' => '',
                        'type' => 'image',
                        'instructions' => '480 x 150',
                        'required' => 0,
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
                        'key' => 'field_692eeba981a92strip_wallet',
                        'label' => 'Strip',
                        'name' => 'strip',
                        'aria-label' => '',
                        'type' => 'image',
                        'instructions' => '1125 x 432 pixels',
                        'required' => 0,
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
                'key' => 'field_google_wallet_group_wallet',
                'label' => 'Google',
                'name' => 'google_wallet',
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
                        'key' => 'field_google_hero_image_wallet',
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
                        'key' => 'field_google_logo_wallet',
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
    ];
}

new IW_Ticketing_Options_Page();


add_action('acf/init', function () {
    if ( ! function_exists('get_field') || ! function_exists('update_field') ) return;

    $rows = get_field('iw_ticketing_excluded_dates', 'option');
    if ( ! empty($rows) ) return;

    $default = [
        [ 'kind' => 'recurring', 'month_day' => '01-01', 'label' => 'Πρωτοχρονιά' ],
        [ 'kind' => 'recurring', 'month_day' => '01-06', 'label' => 'Θεοφάνεια' ],
        [ 'kind' => 'recurring', 'month_day' => '03-25', 'label' => '25η Μαρτίου' ],
        [ 'kind' => 'recurring', 'month_day' => '05-01', 'label' => 'Πρωτομαγιά' ],
        [ 'kind' => 'recurring', 'month_day' => '08-15', 'label' => 'Δεκαπενταύγουστος' ],
        [ 'kind' => 'recurring', 'month_day' => '10-28', 'label' => '28η Οκτωβρίου' ],
        [ 'kind' => 'recurring', 'month_day' => '12-25', 'label' => 'Χριστούγεννα' ],
        [ 'kind' => 'recurring', 'month_day' => '12-26', 'label' => 'Σύναξη Θεοτόκου' ],
    ];

    update_field('iw_ticketing_excluded_dates', $default, 'option');
});
