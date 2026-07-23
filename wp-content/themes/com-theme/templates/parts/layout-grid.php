<?php if ( is_user_logged_in()) { ?>
    <div class="fixed top-0 left-0 w-full h-full transition opacity-0 pointer-events-none z-[5000] group-[&.show-layout-grid]:opacity-80" data-module-layout-grid>
        <div class="w-full h-full ">
            <div class="grid-cols-24 h-full hidden md:grid ">
                <?php for ( $i = 1; $i <= 24; $i++){ ?>
                    <div class="border-l border-[#00FFFF]"></div>
                <?php } ?>
            </div>
            <div class="h-full page-wrapper">
                <div class="grid grid-cols-12 h-full md:hidden border-r border-[#00FFFF]">
                    <?php for ( $i = 1; $i <= 12; $i++){ ?>
                        <div class="border-l border-[#00FFFF]"></div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
