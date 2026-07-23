<?php

namespace IW\ElementorWidgets;

class CLI
{

    function __construct() {

    }

    /**
     * Creates a new Elementor Widget
     *
     * ## OPTIONS
     *
     * <name>
     * : The name of the widget.
     * ---
     * ---
     *
     * ## EXAMPLES
     *
     *     wp elementor widget "my widget name "
     *
     * @when after_wp_load
     */

    public function widget($args, $assoc_args)
    {
        list($name) = $args;
        $widgetName = str_replace(" ", "", ucwords(str_replace('-', ' ', $name)));
        $readableName = ucwords(str_replace('-', ' ', $name));
        $directory = ElementorWidgets::$folder_path . '/' . $widgetName;
        $file = $directory . '/' . $widgetName . '.php';
        $htmlFile = $directory . '/html.php';

        if (!is_dir($directory)) {
            if ( ! mkdir($directory, 0777, true)) {
                \WP_CLI::error( 'Failed to create directory: ' . $directory . ', aborting.' );
                die();
            }
        } else {
            \WP_CLI::error( 'Widget "' . $readableName . '" already exists, aborting.' );
            die();
        }

        $classContents = self::getClassFileContents();
        $classContents = str_replace( '{{ClassName}}', $widgetName,  $classContents);
        $classContents = str_replace( '{{WidgetName}}', str_replace( ' ', '-', strtolower($readableName)),  $classContents);
        $classContents = str_replace( '{{WidgetTitle}}', $readableName,  $classContents);



        if (file_put_contents($file, $classContents ) !== false) {
            file_put_contents($htmlFile, self::getTemplateFileContents() );
            \WP_CLI::success('Widget "' . $readableName . '" created.');

        } else {

            \WP_CLI::error( 'Failed to create file ' . $readableName . ', aborting' );
        }

    }

    public function getTemplateFileContents(){
        return <<<'EOD'
<div style="height: 500px;background-color: #F1F1F1;display: flex;align-items: center;justify-content: center;">
    <div style="text-align: center;">
        <div style="font-weight: bold;"><?php echo $title; ?></div>
        <div><?php echo $text; ?></div>
    </div>
</div>
EOD;

    }

    public function getClassFileContents(){
        return <<<'EOD'
<?php
namespace IW\ElementorWidgets;

class {{ClassName}} extends \Elementor\Widget_Base {

    use IW_Elementor_Widget_Trait;
    
    static $name = 'iw-{{WidgetName}}-widget';
    static $title = '{{WidgetTitle}}';
    static $icon = 'eicon-document-file';
    static $categories = [ 'iw-category' ];
    static $keywords = [ 'Your', 'keywords', 'here' ];

    protected function register_controls() {
        $this->start_controls_section('heading_section', [ 'label' => esc_html__( 'Heading' ), 'tab' => \Elementor\Controls_Manager::TAB_CONTENT] );
        $this->add_control(
            'title',
            [
                'label' => __('Title'),
                'label_block' => true,
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '{{WidgetTitle}}'
            ]
        );
        
        $this->add_control(
            'text',
            [
                'label' => __('Text'),
                'label_block' => true,
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => '{{WidgetTitle}} text'
            ]
        );
        $this->end_controls_section();
    }
    
}
EOD;

    }

}

if ( class_exists( 'WP_CLI' ) ) {
    \WP_CLI::add_command( 'elementor', 'IW\ElementorWidgets\CLI' );
}
