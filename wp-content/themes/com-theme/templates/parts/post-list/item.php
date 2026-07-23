<?php
extract(wp_parse_args($args, ['wrapperClass' => '', 'taxonomyName' => '' ]));
$posType = get_post_type();
if( ! empty( $taxonomyName) ){
    $terms = $terms = get_the_terms(get_the_ID(), $taxonomyName );
}
// col group [&:nth-child(10n+5)]:col-span-2 [&:nth-child(10n+9)]:col-span-2
// group-[.col:nth-child(10n+5)]:aspect-[1.9047619048] group-[.col:nth-child(10n+9)]:aspect-[1.9047619048]
?>

<div class="col-span-1 item-<?php echo $posType; ?> <?php echo $wrapperClass ?>">
    <a class="block relative aspect-[0.9523809524] image bg-black bg-opacity-[0.02]" href="<?php the_permalink(); ?>" >
        <?php get_template_part(has_post_thumbnail() ? 'templates/parts/image' : 'templates/parts/no-image', false, [ 'id' => get_post_thumbnail_id(), 'additionalClasses', 'size' => com\theme::post_list_image_size( $posType ) ] ); ?>
    </a>
    <div class="p-30">
        <?php if ( ! empty( $terms = get_the_terms( get_the_ID(), $posType . 's' ) )  && ! is_wp_error( $terms ) ) { ?>
            <div class="font-bold text-12 mb-10">
            <?php foreach ( $terms as $key => $term ) { ?>
                 <a href="<?php echo get_term_link($term); ?>"><?php echo $term->name; ?></a><?php if( $key !== count( $terms  ) - 1 ) echo ', '; ?>
            <?php  } ?>
            </div>
        <?php } ?>
        <h3 class="text-21 leading-[1.4285714286] m-0 line-clamp-2"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
    </div>
</div>
