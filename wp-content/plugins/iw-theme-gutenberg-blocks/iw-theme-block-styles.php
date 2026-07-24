<?php

class IW_Theme_Block_Styles {

    private static $themeTailwindConfig;

    public function __construct() {
        add_action('acf/load_field/type=select', [$this, 'renderSelectField']);
        add_action('acf/render_field/type=radio', [$this, 'renderRadioField']);
        add_action('admin_head', [$this, 'addCustomStyles']);
        add_filter('theme_block_colors', [$this, 'applyBlockColors'], 1, 1);
        add_filter('theme_block_spacings', [$this, 'applyBlockSpacings'], 1, 1);
    }

    public function renderSelectField($field) {
        if (str_contains($field['wrapper']['class'], 'theme-spacing')) {
            $field['choices'] = ['default' => 'Default'] +  self::getThemeConfig()['spacing'];
        }
        return $field;
    }


    public function renderRadioField($field) {
        if (str_contains($field['wrapper']['class'], 'theme-color')) {
            $availableColors = $field['choices'];
            $themeConfig = $this->getThemeConfig();
            $choices = ['default' => '#F1f1f1'] + $themeConfig['colors'];
            echo '<div class="theme-color-radio-field">';
            foreach ($choices as $colorName => $label) {
                if (!empty($availableColors) && !in_array($colorName, $availableColors)) {
                    continue;
                }
                echo '<label class="theme-color-radio-field-tooltip" style="position: relative; width: 40px; height: 40px;" title="' . ucwords(str_replace("-", " ", $colorName)) . '">';
                echo '<input style="position: absolute; width: 0; height: 0; opacity: 0;" type="radio" name="' . esc_attr($field['name']) . '" value="' . esc_attr($colorName) . '" ' . checked($field['value'], $colorName, false) . ' />';
                echo '<span><span style="display: block; width: 100%; height: 100%; border-radius: 4px; background-color:' . esc_attr($label) . ';"></span></span>';
                echo '</label>';
            }
            echo '</div>';
        }
    }

    public function addCustomStyles() { ?>
        <style>
        .theme-color-radio-field { padding: 10px 0; display: flex; flex-wrap: wrap; gap: 5px; }
        .theme-color-radio-field input + span { border-radius: 8px; padding: 3px; cursor: pointer; width: 28px; height: 28px; display: block; border: 3px solid #F1f1f1; }
        .theme-color-radio-field input:checked + span { border-color: black; }
        </style>
    <?php }

    public static function getThemeConfig() {
        if ( ! self::$themeTailwindConfig ) {
            self::$themeTailwindConfig = json_decode(file_get_contents(get_template_directory() . '/assets/tailwind/theme.config.json'), true);
        }
        return self::$themeTailwindConfig;
    }

    public function applyBlockColors($value) {
        $defaultClasses = preg_split('/\s+/', trim((string) $value)) ?: [];
        $backgroundColor = get_field('background_color');
        $textColor = get_field('text_color');
        $selectedBackgroundColor = !empty($backgroundColor) && $backgroundColor !== 'default';
        $selectedTextColor = !empty($textColor) && $textColor !== 'default';

        $classes = array_values(array_filter($defaultClasses, function($class) {
            return ! $this->is_color_class($class, 'bg-') && ! $this->is_color_class($class, 'text-');
        }));

        $defaultBackgroundClass = $this->find_color_class($defaultClasses, 'bg-');
        $defaultTextClass = $this->find_color_class($defaultClasses, 'text-');

        if ($selectedBackgroundColor) {
            $classes[] = 'bg-' . $backgroundColor;
        } elseif (!empty($defaultBackgroundClass)) {
            $classes[] = $defaultBackgroundClass;
        }

        if ($selectedTextColor) {
            $classes[] = 'text-' . $textColor;
        } elseif ($selectedBackgroundColor) {
            $classes[] = $this->get_contrast_text_class($backgroundColor);
        } elseif (!empty($defaultTextClass)) {
            $classes[] = $defaultTextClass;
        }

        $classes = trim(implode(' ', array_unique(array_filter($classes))));
        return empty($classes) ? $value : $classes;
    }

    protected function find_color_class($classes, $prefix) {
        foreach ($classes as $class) {
            if ($this->is_color_class($class, $prefix)) {
                return $class;
            }
        }

        return '';
    }

    protected function is_color_class($class, $prefix) {
        if (!str_starts_with($class, $prefix)) {
            return false;
        }

        $color = substr($class, strlen($prefix));
        $color = strtok($color, '/');

        return isset(self::getThemeConfig()['colors'][$color]) || in_array($color, ['current', 'transparent'], true);
    }

    protected function get_contrast_text_class($backgroundColor) {
        $lightTextBackgrounds = ['blue', 'dark', 'black', 'validated', 'error', 'success', 'limited'];

        return in_array($backgroundColor, $lightTextBackgrounds, true) ? 'text-white' : 'text-blue';
    }
    public function applyBlockSpacings($default_spacing) {
        $default_spacing = wp_parse_args($default_spacing, [ "desktop" => ["mt" => "", "mb" => "", "pt" => "", "pb" => ""], "mobile" => ["mt" => "", "mb" => "", "pt" => "", "pb" => ""] ]);
        $user_spacing = get_field('spacing') ?: [];
        $classes = [];



        $get_value = function($device, $property) use ($user_spacing, $default_spacing) { return ! empty($user_spacing[$device][$property]) ? $user_spacing[$device][$property] : ( ! empty( $default_spacing[$device][$property] ) ? $default_spacing[$device][$property] : '' ); };
		foreach ( [ "mt", "mb", "pt", "pb" ] as $property ) {
			$mobile_value = $get_value( 'mobile', $property );
			$desktop_value = $get_value( 'desktop', $property );
			if ( $mobile_value ) {
				$classes[] = $this->build_class( '', $property, $mobile_value );
			}
			if ( $desktop_value ) {
				$prefix = $mobile_value ? 'md:' : '';
				$classes[] = $this->build_class( $prefix, $property, $desktop_value );
			}
		}

		if( ! empty( $user_border_radius_top = get_field('border_radius_top') ) ){
            $classes[] = 'rounded-t-' . $user_border_radius_top;
        }

        if( ! empty( $user_border_radius_bottom = get_field('border_radius_bottom') ) ){
            $classes[] = 'rounded-b-' . $user_border_radius_bottom;
        }




        return implode(' ', array_filter($classes));
    }

    public function build_class($prefix, $property, $value) {
        if ( $value === null || $value === '' )
            return null;
        // If value is negative, prepend the dash before the property
        if ( str_starts_with( $value, '-' ) ) {
            return $prefix . '-' . $property . '-' . ltrim( $value, '-' );
        }
        return $prefix . $property . '-' . $value;
    }



}

new IW_Theme_Block_Styles();
