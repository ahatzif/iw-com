<?php
    $params = wp_parse_args( $args, ['html' => '']);
    extract($params);
?>
<div class="bg-green p-[3rem] text-white hidden" data-form-success><?php echo $html;?></div>
