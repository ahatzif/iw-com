<?php
$dot_size = $args['size'] ?? 'size-[.5rem]';
?>
<span
    data-button-loader
    class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-0 transition-opacity"
    aria-hidden="true"
>
    <span class="relative flex w-[2rem] items-center">
        <span class="<?= esc_attr( $dot_size ) ?> absolute left-0 rounded-full bg-current animate-loading-dots"></span>
    </span>
</span>
