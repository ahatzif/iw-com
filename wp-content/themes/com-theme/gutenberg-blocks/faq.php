<?php
// Title: FAQ

$faqs = get_field( 'faqs' );
if( ! empty( $faqs ) ){  ?>
    <div class="page-wrapper my-100">
        <div class="mx-1/12 md:mx-2/12 " data-module-accordion>
            <div class="border-black border-t-[1px] mt-[5.7rem]">
                <?php
                foreach ($faqs as $key => $faq){ ?>
                    <div class="border-black border-b-[1px] py-40" data-accordion="parent">
                        <div class="text-1 font-bold flex justify-between cursor-pointer" data-accordion="toggle">
                            <?php echo $faq['question']; ?>
                            <div class="h-[2.5rem] w-[2.5rem] ml-30 relative flex items-center flex-shrink-0">
                                <div class="w-full h-[3px] bg-black rounded-full"></div>
                                <div class="w-full h-[3px] bg-black rounded-full absolute origin-center top-1/2 left-1/2 transition duration-300 -translate-x-1/2 -translate-y-1/2 <?php if( $key !== 0 ) echo 'rotate-90' ?>" data-class-toggle="rotate-90"></div>
                            </div>
                        </div>
                        <div class="text-14 leading-[1.33] mt-10 <?php if( $key !== 0 ) echo 'hidden';  ?>"  data-accordion="target"> <?php echo  $faq['answer']; ?></div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php
    get_template_part( 'schema/faq', false, ['faqs' => $faqs]);
}
?>
