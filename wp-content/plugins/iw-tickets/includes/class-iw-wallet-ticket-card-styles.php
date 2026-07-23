<?php


class IW_Wallet_Ticket_Card_Styles {
    private static function theme_colors(): array {
        $colors = [];
        $config_path = apply_filters( 'iw_ticketing_theme_config_path', get_template_directory() . '/assets/tailwind/theme.config.json' );

        if ( is_string( $config_path ) && file_exists( $config_path ) ) {
            $theme_config = json_decode( file_get_contents( $config_path ), true );
            if ( is_array( $theme_config['colors'] ?? null ) ) {
                $colors = $theme_config['colors'];
            }
        }

        return apply_filters( 'iw_ticketing_theme_colors', $colors );
    }

    public static function hexToRGB( $hex ) {
        if ( ! is_string( $hex ) ) return null;
        $hex = ltrim( trim( $hex ), '#' );
        if ( strlen( $hex ) === 3 ) {
            $r = hexdec( str_repeat( substr( $hex, 0, 1 ), 2 ) );
            $g = hexdec( str_repeat( substr( $hex, 1, 1 ), 2 ) );
            $b = hexdec( str_repeat( substr( $hex, 2, 1 ), 2 ) );
        } elseif ( strlen( $hex ) === 6 ) {
            $r = hexdec( substr( $hex, 0, 2 ) );
            $g = hexdec( substr( $hex, 2, 2 ) );
            $b = hexdec( substr( $hex, 4, 2 ) );
        } else {
            return null;
        }
        return "rgb($r,$g,$b)";
    }

    private static function resolveColor( $value, array $themeHeXColors, bool $hex ) {
        if ( ! is_string( $value ) || trim( $value ) === '' ) {
            return null;
        }

        if ( ! str_starts_with( $value, '#' ) ) {
            $value = $themeHeXColors[ $value ] ?? $value;
        }

        if ( ! $hex ) {
            return self::hexToRGB( $value );
        }

        return $value;
    }

    private static function is_set_value( $v ): bool {
        // null is never set
        if ( is_null( $v ) ) {
            return false;
        }
        // empty strings are treated as not set
        if ( is_string( $v ) ) {
            return trim( $v ) !== '';
        }
        // empty arrays are treated as not set
        if ( is_array( $v ) ) {
            return count( $v ) > 0;
        }
        // IMPORTANT: allow false / 0 as valid values
        return true;
    }

    private static function merge_preferring_override( array $base, array $override ): array {
        foreach ( $override as $k => $v ) {
            if ( is_array( $v ) ) {
                $base_val = ( isset( $base[ $k ] ) && is_array( $base[ $k ] ) ) ? $base[ $k ] : [];
                $merged   = self::merge_preferring_override( $base_val, $v );
                if ( self::is_set_value( $merged ) ) {
                    $base[ $k ] = $merged;
                }
                continue;
            }

            if ( self::is_set_value( $v ) ) {
                $base[ $k ] = $v;
            }
        }

        return $base;
    }

    private static function normalize_suffixed_keys( $value, string $suffix ) {
        if ( ! is_array( $value ) ) {
            return $value;
        }

        $out = [];
        foreach ( $value as $k => $v ) {
            $new_key = $k;
            if ( is_string( $k ) && $suffix !== '' ) {
                $needle = '__' . $suffix;
                if ( str_ends_with( $k, $needle ) ) {
                    $new_key = substr( $k, 0, - strlen( $needle ) );
                }
            }

            // Recurse into nested arrays.
            $out[ $new_key ] = self::normalize_suffixed_keys( $v, $suffix );
        }

        return $out;
    }



    public static function resolve_wallet_fields_for_post( int $post_id, string $group_name ): array {
        if ( ! function_exists( 'get_field' ) ) {
            return [];
        }

        $resolved = [];
        $post_type = get_post_type( $post_id );
        $fields = [
            get_field( $group_name, $post_id ), // post
            self::normalize_suffixed_keys( get_field( $group_name . '__' . $post_type, 'option' ), (string) $post_type ), // cpt
            get_field( $group_name, 'option' ) // general
        ];
        foreach ( ['background_color',  'text_color' ] as $field_name ) {
            foreach ( $fields as $field ) {
                if( isset( $field[ $field_name . '_type' ] ) && ! str_starts_with( $field[ $field_name . '_type' ], 'default' ) ) {
                    $resolved[ $field_name ] = $field[ $field[ $field_name . '_type' ] ];
                    break;
                }
            }
        }
        foreach ( [ 'icon', 'logo', 'strip', 'hero_image' ] as $field_name ) {
            foreach ( $fields as $field ) {
                if( ! empty( $field[ $field_name ] ) ) {
                    $resolved[ $field_name ] = $field[ $field_name ];
                    break;
                }
            }
        }

        return $resolved;
    }

    public static function get( $post_id, $hex = false ){
        $themeHeXColors = self::theme_colors();
        $apple  = self::resolve_wallet_fields_for_post( (int) $post_id, 'apple_wallet' );
        $google = self::resolve_wallet_fields_for_post( (int) $post_id, 'google_wallet' );


        $out =  [
            'background_color' => self::resolveColor($apple['background_color'] ?? '#31312F', $themeHeXColors, $hex),
            'text_color'       => self::resolveColor($apple['text_color'] ?? '#FFFFFF', $themeHeXColors, $hex),
            'apple_images' => [
                'icon' => empty($apple['icon']) ? null : get_attached_file($apple['icon']),
                'logo' => empty($apple['logo']) ? null : get_attached_file($apple['logo']),
                'strip' => empty($apple['strip']) ? null : get_attached_file($apple['strip']),

            ],
            'google_images' => [
                'logo' => empty($google['logo']) ? null : wp_get_attachment_url($google['logo']),
                'hero' => empty($google['hero_image']) ? null : wp_get_attachment_url($google['hero_image']),
                'image_modules' => empty($google['image_module']) ? [] : [ [ 'url'  => wp_get_attachment_url($google['image_module']), 'desc' => '', 'id'   => 'google_module_1' ] ]
            ]
        ];
        return $out;
    }

}
