<?php // Title: Page Header

$title = trim( get_field( 'title' ) );
$text = trim( get_field( 'text' ) );

if( is_tax( ) ){
    $taxonomy = get_queried_object();
    if( empty( $title ) ) $title = $taxonomy->name;
    if( empty( $text ) ) $text = $taxonomy->description;
    $taxonomyObj = get_taxonomy( $taxonomy->taxonomy );
} else {
    if( empty( $title ) ) $title = get_the_title();
    if( empty( $text ) && has_excerpt() ) $title = get_the_excerpt();
}
?>
<section class="my-100">
    <div class="page-wrapper">
        <div class="md:w-11/24 space-y-10">
            <h1 class="text-[2.986rem] font-heading leading-none"><?php echo $title; ?></h1>
            <?php if( ! empty( $text )  ) { ?>
                <p class="text-14 leading-[1.5714285714]"><?php echo $text; ?></p>
            <?php } ?>
        </div>
    </div>
</section>
