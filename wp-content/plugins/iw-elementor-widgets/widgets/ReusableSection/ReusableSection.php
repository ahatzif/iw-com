<?php
namespace IW\ElementorWidgets;

class ReusableSection extends \Elementor\Widget_Base {
    use IW_Elementor_Widget_Trait;
    static $name = 'iw-reusable-section-widget';
    static $title = 'Reusable Section';
    static $icon = 'eicon-document-file';
    static $categories = [ 'general' ];
    static $keywords = [ 'reusable', 'section', 'template' ];


    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__( 'Content', 'elementor-oembed-widget' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'selected_section_id',
            [
                'label' => __('Section', 'my-elementor-widget'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_post_options(),
                'required' => true, // Set the control as required

            ]
        );

        $this->end_controls_section();
    }

    private function get_post_options() {
        $posts = get_posts(['post_type' => 'elementor_library', 'posts_per_page' => -1]);
        $options = [];
        foreach ($posts as $post) {
            $options[$post->ID] = $post->post_title;
        }
        return $options;
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        if( ! empty( $settings['selected_section_id'] ) ) {
            echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($settings['selected_section_id'] );
        }
    }
}
