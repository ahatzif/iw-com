<?php

$type = $args["type"] ?? "regular";

if (!in_array($type, ["regular", "outline"])) {
    get_template_part("templates/parts/button", $type, $args);
    return;
}

extract(wp_parse_args($args, [
    "link" => [
        "url" => "",
        "title" => __("ΠΕΡΙΣΣΟΤΕΡΑ", "com-theme"),
        "target" => "",
    ],
    "tag" => "a",
    "classes" => false,
    "attrs" => "",
    "color" => "black",
    "size" => "large",
    "button" => [],
]));

$url = $link["url"];
$target = $link["target"];
$title = $link["title"];

$baseClasses = "border border-{$color}";

if ($type === "regular") {
    $colorClasses = implode(' ', [
        $baseClasses,
        "bg-{$color}",
        "text-" . ($color === "white" ? "black" : "white"),
        "hover:bg-transparent",
        "hover:text-{$color}"
    ]);
} else {
    $colorClasses = implode(' ', [
        $baseClasses,
        "bg-transparent",
        "text-{$color}",
        "hover:bg-{$color}",
        "hover:text-" . ($color === "white" ? "black" : "white")
    ]);
}

$sizeClasses = [
    "large" => "h-65 px-35 text-[1.75rem]",
    "medium" => "h-50 px-25 text-[1.55rem]",
    "small" => "h-35 px-20 text-[1.35rem]",
];

$targetAttr = $target === "_blank" ? 'target="_blank"' : ""; ?>

<<?php echo $tag; ?> <?php if (!empty($url) && $tag === "a") echo ' href="' . $url . '"' ?> <?php echo 'class="cursor-pointer group btn transition-all duration-300 inline-flex leading-none items-center justify-center rounded-full whitespace-nowrap font-bold' . " " . $classes . " " . $colorClasses  . " " . $sizeClasses[$size] . '"' ?> <?php if (!empty($attrs)) echo " " . trim($attrs); ?> <?php if (!empty($targetAttr)) echo " " . $targetAttr; ?>>
    <span class="inline-block group-[.loading]:animate-loading"><?php echo com\theme::remove_accents($title); ?></span>
</<?php echo $tag; ?>>