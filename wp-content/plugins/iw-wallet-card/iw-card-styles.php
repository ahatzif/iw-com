<?php

class IW_Member_Card_Styles {
    private static function theme_colors(): array {
        $colors = [];
        $config_path = apply_filters( 'iw_wallet_card_theme_config_path', get_template_directory() . '/assets/tailwind/theme.config.json' );

        if ( is_string( $config_path ) && file_exists( $config_path ) ) {
            $theme_config = json_decode( file_get_contents( $config_path ), true );
            if ( is_array( $theme_config['colors'] ?? null ) ) {
                $colors = $theme_config['colors'];
            }
        }

        return apply_filters( 'iw_wallet_card_theme_colors', $colors );
    }

    private static function color_from_card_settings( array $settings, string $type_key, string $fallback, array $theme_colors ): string {
        $selected_key = $settings[ $type_key ] ?? '';
        $color = $selected_key && isset( $settings[ $selected_key ] ) ? $settings[ $selected_key ] : $fallback;

        if ( is_string( $color ) && '' !== $color && ! str_starts_with( $color, '#' ) ) {
            $color = $theme_colors[ $color ] ?? $fallback;
        }

        return $color ?: $fallback;
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

    public static function get( $user_id, $hex = false ){

        $themeHeXColors = self::theme_colors();
        $card = function_exists( 'get_field' ) ? get_field( 'member_card', 'user_' . $user_id ) : null;
        $wallet = $card && function_exists( 'get_field' ) ? get_field( 'wallet', $card ) : [];
        $wallet = is_array( $wallet ) ? $wallet : [];
        $apple = is_array( $wallet['apple'] ?? null ) ? $wallet['apple'] : [];

        $google = $wallet['google'] ?? [];
        $google_background_color = $google['background_color'] ?? null;
        $google_logo = empty($google['logo']) ? null : wp_get_attachment_url($google['logo']);
        $google_hero = empty($google['hero_image']) ? null : wp_get_attachment_url($google['hero_image']);




        $background_color = self::color_from_card_settings( $apple, 'background_color_type', '#000000', $themeHeXColors );
        if( ! $hex ) {
            $background_color = self::hexToRGB($background_color);
        }



        $text_color = self::color_from_card_settings( $apple, 'text_color_type', '#ffffff', $themeHeXColors );
        if( ! $hex ){
            $text_color = self::hexToRGB( $text_color );
        }

        

        return [
            'background_color' => $background_color,
            'text_color'       => $text_color,
            'apple_images' => [
                'icon' => empty($apple['icon']) ? null : get_attached_file($apple['icon']),
                'logo' => empty($apple['logo']) ? null : get_attached_file($apple['logo']),
                'strip' => empty($apple['strip']) ? null : get_attached_file($apple['strip']),

            ],
            'google_images' => [
                'logo' => $google_logo,
                'hero' => $google_hero,
                'image_modules' => empty($google['image_module']) ? [] : [ [ 'url'  => wp_get_attachment_url($google['image_module']), 'desc' => '', 'id'   => 'google_module_1' ] ]
            ]
        ];
    }

}

