<?php
extract( wp_parse_args( $args,[ 'url' => "#", 'text' => "BUTTON TEXT" ]) );
$button_text = class_exists( '\com\theme' ) && method_exists( '\com\theme', 'remove_accents' )
    ? \com\theme::remove_accents( (string) $text )
    : ( function_exists( 'mb_strtoupper' ) ? mb_strtoupper( (string) $text, 'UTF-8' ) : strtoupper( (string) $text ) );
?>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;margin:0 0 30px 0;">
    <tr>
        <td align="center" bgcolor="#173276" style="background-color:#173276;border-radius:10px;">
            <a href="<?php echo esc_url( $url ); ?>" target="_blank" style="display:inline-block;padding:15px 24px;color:#FFFFFF;font-family:Arial,sans-serif;font-size:14px;line-height:18px;font-weight:700;text-decoration:none;border-radius:10px;">
                <?php echo esc_html( $button_text ); ?>
            </a>
        </td>
    </tr>
</table>
