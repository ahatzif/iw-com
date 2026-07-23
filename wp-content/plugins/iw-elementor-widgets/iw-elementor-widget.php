<?php

namespace IW\ElementorWidgets;

trait IW_Elementor_Widget_Trait {
    public function get_name() { return self::$name; }
    public function get_title() { return self::$title; }
    public function get_icon() { return self::$icon; }
    public function get_categories() { return self::$categories; }
    public function get_keywords() { return self::$icon; }
    protected function render() {
        $reflection = new \ReflectionClass($this);
        $dir = dirname($reflection->getFileName());
        $settings = $this->get_settings_for_display();
        $template_file = $dir . '/html.php';
        if (file_exists($template_file)) {
            extract($settings );
            include $template_file;
        }
    }
}
