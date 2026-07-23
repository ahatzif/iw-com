<?php
    $params = wp_parse_args( $args, ['label' => 'SEND']);
    extract($params);
?>

<button class="btn-full-black flex items-center justify-center min-w-[30rem]">
    <span class="block w-0 transition-all" data-form-loader data-on-classes="mr-[1rem] w-[2rem]" data-off-classes="w-0">
        <svg class="spin-animation w-full" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M11 1C5.47715 1 1 5.47715 1 11C1 16.5228 5.47715 21 11 21C16.5228 21 21 16.5228 21 11C21 8.67079 20.2037 6.52757 18.8684 4.82775" stroke="url(#paint0_linear_8_2)" stroke-width="2"/>
            <defs>
                <linearGradient id="paint0_linear_8_2" x1="15.6411" y1="15.1627" x2="10.9522" y2="0.999999" gradientUnits="userSpaceOnUse">
                    <stop stop-color="white"/>
                    <stop offset="1" stop-color="white" stop-opacity="0"/>
                </linearGradient>
            </defs>
        </svg>
    </span>
    <span class="block h-[2rem] flex items-center"><?php echo $label; ?></span>
</button>