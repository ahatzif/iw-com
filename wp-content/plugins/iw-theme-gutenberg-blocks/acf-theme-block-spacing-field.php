<?php
class ACF_Theme_Block_Spacing_Field extends acf_field {

    private mixed $settings;

    function __construct($settings ) {
        $this->name = 'theme_block_spacing';
        $this->label = __( 'Theme Block Spacing' );
        $this->category = 'layout';
        $this->defaults = array();
        $this->settings = $settings;
        parent::__construct();
    }
    function render_field( $field ) {

        $spacingOptions  = IW_Theme_Block_Styles::getThemeConfig()['spacing'];

        $viewPorts = [ 'desktop' , 'mobile' ];
        $properties = [
            'mt' => '<svg class="fui-container-icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"  width="800px" height="800px" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve"><path d="M19,2H5C4.4,2,4,1.6,4,1s0.4-1,1-1h14c0.6,0,1,0.4,1,1S19.6,2,19,2z"/><path d="M19,20H5c-0.6,0-1-0.4-1-1V5c0-0.6,0.4-1,1-1h14c0.6,0,1,0.4,1,1v14C20,19.6,19.6,20,19,20z M6,18h12V6H6V18z"/></svg>',
            'mb' => '<svg class="fui-container-icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"  width="800px" height="800px" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve"><path d="M19,24H5c-0.6,0-1-0.4-1-1s0.4-1,1-1h14c0.6,0,1,0.4,1,1S19.6,24,19,24z"/><path d="M19,20H5c-0.6,0-1-0.4-1-1V5c0-0.6,0.4-1,1-1h14c0.6,0,1,0.4,1,1v14C20,19.6,19.6,20,19,20z M6,18h12V6H6V18z"/></svg>',
            'pt' => '<svg class="fui-container-icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"  width="800px" height="800px" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve"><path d="M16,6H8C7.4,6,7,5.6,7,5s0.4-1,1-1h8c0.6,0,1,0.4,1,1S16.6,6,16,6z"/><path d="M23,24H1c-0.6,0-1-0.4-1-1V1c0-0.6,0.4-1,1-1h22c0.6,0,1,0.4,1,1v22C24,23.6,23.6,24,23,24z M2,22h20V2H2V22z"/></svg>',
            'pb' => '<svg class="fui-container-icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"  width="800px" height="800px" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve"><path d="M16,20H8c-0.6,0-1-0.4-1-1s0.4-1,1-1h8c0.6,0,1,0.4,1,1S16.6,20,16,20z"/><path d="M23,24H1c-0.6,0-1-0.4-1-1V1c0-0.6,0.4-1,1-1h22c0.6,0,1,0.4,1,1v22C24,23.6,23.6,24,23,24z M2,22h20V2H2V22z"/></svg>'
        ];
        ?>
            <style>
                .fui-container{background-color: #f5f5f5;height: 40px;border-radius: 8px;display: inline-flex;align-items: center;padding-left: 12px;gap: 8px;}
                .fui-container .fui-container-icon{width: 16px;height: 16px;}
                .fui-container input{width: 40px !important;height: 100% !important;background: transparent !important;padding: 0 5px !important;border: none  !important;box-sizing: border-box !important;}
                .fui-container select{width: 60px !important;height: 100% !important;background: transparent !important;padding: 5px !important;border: none  !important;box-sizing: border-box !important;}
                .fui-container select:focus{border: none  !important;box-shadow: none !important;}
                .fui-title{font-weight: bold;font-size: 11px;margin-bottom: 8px;}
                .fui-group{display: flex;gap: 24px;}
            </style>
            <div class="fui-group">
                <?php foreach ( $viewPorts  as $viewPort) { ?>
                <div>
                    <div class="fui-title"><?php echo strtoupper( $viewPort ); ?></div>
                    <?php foreach ( $properties as $property => $icon ) { ?>
                        <label class="fui-container">
                            <?php echo $icon; ?>
                            <select name="<?php echo esc_attr($field['name']) ?>[<?php echo $viewPort; ?>][<?php echo $property ?>]" >
                                <option value="">-</option>
                                <?php foreach ( $spacingOptions as $key => $value ) {?>
                                <option value="<?php echo $key; ?>" <?php if( ! empty($field['value'][$viewPort][$property]) && $key == $field['value'][$viewPort][$property]) echo 'selected'; ?>><?php echo $value; ?></option>
                                <?php } ?>
                            </select>
                        </label>
                    <?php } ?>
                </div>
                <?php } ?>
            </div>
        <?php
    }
}
