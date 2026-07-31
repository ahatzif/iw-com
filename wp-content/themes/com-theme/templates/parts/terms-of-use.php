<?php
$termsPage = com_theme_page_url( 'terms-of-use' );
$privacyPage = com_theme_page_url( 'privacy-policy' );

echo sprintf(__('I have read and accept the <a class="underline"  href="%s">terms</a> and the <a class="underline" href="%s" target="_blank">privacy policy</a>', 'com-theme'), $termsPage, $privacyPage);
