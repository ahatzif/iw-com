<?php
$termsPage = get_field("terms_page", "options");
$privacyPage = get_field("privacy_page", "options");

echo sprintf(__('I have read and accept the <a class="underline"  href="%s">terms</a> and the <a class="underline" href="%s" target="_blank">privacy policy</a>', 'com-theme'), $termsPage, $privacyPage);
