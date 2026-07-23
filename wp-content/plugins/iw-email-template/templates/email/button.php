<?php extract( wp_parse_args( $args,[ 'url' => "#", 'text' => "BUTTON TEXT" ]) ); ?>
<div>
    <a href="<?php echo htmlspecialchars( $url ); ?>" style="color: #0000ff;font-weight:bold;" ><?php echo $text; ?></a>
</div>
