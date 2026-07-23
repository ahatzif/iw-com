<?php

$type = $args['type'] ?? 'regular';
if (empty($type)) { $type = 'regular'; }

extract(wp_parse_args($args, [
  'link' => ['url' => '#', 'title' => __('ΠΕΡΙΣΣΟΤΕΡΑ', 'com-theme'), 'target' => ''],
  'tag' => 'a',
  'name' => '',
  'classes' => '',
  'attrs' => '',
  'color' => 'dark',
  'size' => 'regular',
  'button' => [],
  'icon' => false,
  'loading' => true
]));

$url = $link['url'] ?? false;
$target = $link['target'] ?? false;
$title = $link['title'] ?? __('ΠΕΡΙΣΣΟΤΕΡΑ', 'com-theme');

$baseClasses = "border border-" . $color; //($color === "white" ? "medium" : $color);

if ($type === "regular") {
  $colorClasses = implode(' ', [
    $baseClasses,
    "bg-{$color}",
    "text-" . ($color === "white" ? "dark" : "white"),
    "hover:bg-transparent",
    "hover:text-" . ($color === "white" ? "dark" : $color),
    "hover:border-" . ($color === "white" ? "dark" : $color)
  ]);
} else {
  $colorClasses = implode(' ', [
    $baseClasses,
    "bg-transparent",
    "text-{$color}",
    "hover:bg-" . ($color === "white" ? "dark" : $color),
    "hover:text-" . ($color === "white" ? "dark" : "white")
  ]);
}

$sizeClasses = [
  "extra-large" => "h-30 px-50 text-[3.6rem] leading-[1.1] -tracking-[.1em]",
  "large" => "h-[5.9rem] px-[2.6rem] text-Button-L",
  "regular" => "h-60 px-35 text-Button-L",
  "medium" => "h-[4.6rem] px-[1.9rem] text-Button-M space-x-[1.2rem] [&_svg]:w-[1.6rem] [&_svg]:h-[1.6rem]",
  "small" => "h-[3.9rem] px-[1.7rem] text-Button-S",
  "extra-small" => "h-[2.7rem] px-[1.3rem] text-Button-XS",
];

$targetAttr = ($target === "_blank" && $tag !== "button") ? 'target="_blank"' : "";
?>

<<?php echo $tag; ?> <?php if (!empty($url) && $tag === "a") echo ' href="' . $url . '"'; ?> <?php if ($tag === 'button') echo 'type="submit" name="' . $name . '"'; ?> class="<?php if ($loading) echo 'group-[.loading]:pointer-events-none'; ?> cursor-pointer group btn transition-all duration-300 inline-flex items-center justify-center rounded-full whitespace-nowrap<?php echo ' ' . $classes . ' ' . $colorClasses . ' ' . $sizeClasses[$size] ?>" <?php if (!empty($attrs)) echo " " . trim($attrs); ?> <?php if (!empty($targetAttr)) echo " " . $targetAttr; ?>>
  <?php if (!empty($icon)) { ?><svg class="fill-current size-[1.6rem]"><use xlink:href="#icon-<?php echo $icon ?>"></use></svg><?php } ?>
  <span class="inline-block <?php if ($loading) echo 'group-[.loading]:animate-loading group-[.loading]:pointer-events-none'; ?>" data-button-text><?php echo com\theme::remove_accents($title); ?></span>
</<?php echo $tag; ?>>