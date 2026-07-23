<?php

get_header();

$user = wp_get_current_user();
$user_meta = get_user_meta($user->ID);
?>
    <div class="px-page-padding bg-white" data-scroll-section>
        <div class="py-[14rem] max-w-[70rem] mx-auto" data-form-container>
        <?php get_template_part('templates/form-parts/form-header', false, [
                'title' => 'Edit profile',
                'description' => 'Nullam id dolor id nibh ultricies vehicula ut id elit. Donec sed odio dui. Nullam id dolor id nibh ultricies vehicula ut id elit. Cras justo odio.',
        ]);?>
            <div class="bg-green p-[3rem] text-white hidden" data-form-success>Congratulations, your profile is updated!</div>
            <form action="<?php the_permalink(); ?>"  data-module-form-validation data-error-class="border !border-red !outline-none !shadow-none" >
                <input type="hidden" name="action" value="waction-register">
                <div class="bg-red p-[3rem] text-white hidden" data-form-error data-toggle-class="hidden"></div>
                <label class="block mb-8">
                    <span class="block mb-4 font-bold uppercase">First name</span>
                    <input placeholder="<?php echo $user_meta["first_name"][0];?>" type="text" name="first_name" class="form-input block w-full px-4 py-3  h-[6rem] text-16" data-validate="">
                    <span class="text-red text-[12px]" data-error-message></span>
                </label>
                <label class="block mb-8">
                    <span class="block mb-4 font-bold uppercase">Last name</span>
                    <input placeholder="<?php echo $user_meta["last_name"][0];?>" type="text" name="last_name" class="form-input block w-full px-4 py-3  h-[6rem] text-16" data-validate="">
                    <span class="text-red text-[12px]" data-error-message></span>
                </label>
                <label class="block mb-8">
                    <span class="block mb-4 font-bold uppercase">Email</span>
                    <input placeholder="<?php echo $user->user_email;?>" type="text" name="user_email" class="form-input block w-full px-4 py-3  h-[6rem] text-16" data-validate="email">
                    <span class="text-red text-[12px]" data-error-message></span>
                </label>
                <label class="block mb-8 ">
                    <span class="block mb-4 font-bold uppercase">Password</span>
                    <span class="block relative">
                        <input type="password" name="user_password" class="form-input block w-full px-4 py-3  h-[6rem] text-16" data-validate="password">
                        <span class="absolute right-0 top-1/2 -translate-y-1/2 p-[2rem] text-[10px] font-bold cursor-pointer select-none" data-toggle-password>SHOW PASSWORD</span>
                    </span>
                    <span class="hidden text-green mr-[1rem] w-[2rem] pointer-events-none cursor-wait"></span>
                    <span class="block text-[12px] pt-[1rem]">
                        <span class="font-bold">Password must</span>
                        <span data-password-confirm="length">be at least 12 characters long</span>,
                        <span data-password-confirm="uppercase">have at least one uppercase</span>,
                        <span data-password-confirm="lowercase">have at least one lowercase</span>,
                        <span data-password-confirm="number">have at least one number</span>,
                        <span data-password-confirm="special">have at least one special character !@#$%^&*</span>.
                    </span>
                    <span class="text-red text-[12px]" data-error-message></span>

                </label>

                <button class="btn-full-black flex items-center">
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
                    <span class="block h-[2rem] flex items-center">SAVE CHANGES</span>
                </button>
                <div class="pt-[3rem]">
                     <a href="<?php echo get_permalink( iw_get_user_page( 'login' ) ) ?>" class="font-bold"><?php _e( 'Already have an account?', 'iw-theme') ?></a>
                </div>
            </form>
        </div>
    </div>
<?php
get_footer();
