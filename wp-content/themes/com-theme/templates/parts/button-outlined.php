<?php
extract( wp_parse_args( $args, [
    'link' => "",
    'text' => '',
    'tag' => 'a',
    'classes' => false,
    'target' => '',
    'attrs' => '',
    'color' => 'black'
]));

$colorClasses = [
    'white-outlined' => 'border border-current text-white hover:bg-white hover:text-black',
    'black-outlined' => 'border border-current text-black hover:bg-black hover:text-white',
    'black' => 'bg-black text-white hover:bg-black hover:text-white',
    'white' => 'border border-black bg-white text-black hover:bg-black hover:text-white',
];


$targetAttr = $target === '_blank' ? 'target="_blank"' : '';
?>

<<?php echo $tag;?><?php if( ! empty( $link) && $tag === 'a' ) echo ' href="' . $link . '"'?><?php echo ' class="group btn transition-all duration-500 text-14 h-[4.8rem] px-[3rem] inline-flex items-center whitespace-nowrap font-bold ' . ' ' . $classes . ' ' . $colorClasses[ 'color' ]  . '"'?>

<?php if( ! empty( $attrs )) echo " ". trim( $attrs ); ?><?php if( ! empty( $targetAttr )) echo " ". $targetAttr; ?>>
    <span class="group-[btn:hover]:-translate-y-[1px]"><?php echo !empty($text) ? com\theme::remove_accents($text) : __('ΠΕΡΙΣΣΟΤΕΡΑ', 'com-theme');?></span>
</<?php echo $tag;?>>
