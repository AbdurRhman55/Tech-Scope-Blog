<?php
/** One-off QA: rendered cover image per published post + duplicate/placeholder check */
$root = dirname(__DIR__);
require $root . '/config/config.php';
require $root . '/config/database.php';
require $root . '/includes/functions.php';

$rows = db()->query("
    SELECT p.id, p.slug, p.title, p.cover_image,
           c.slug AS category_slug, c.name AS category_name,
           pc.id AS parent_id, pc.slug AS parent_slug, pc.name AS parent_name
    FROM posts p
    JOIN categories c  ON c.id  = p.category_id
    LEFT JOIN categories pc ON pc.id = c.parent_id
    WHERE p.status = 'published'
    ORDER BY p.id")->fetchAll();

$byParent = [];
$placeholder = [];
foreach ($rows as $r) {
    $img  = post_image($r);
    $base = str_contains($img, 'data:') ? 'GRADIENT-PLACEHOLDER' : basename(parse_url($img, PHP_URL_PATH));
    printf("post %-3d %-22s -> %s\n", $r['id'], $r['category_slug'], $base);
    if ($base === 'GRADIENT-PLACEHOLDER') $placeholder[] = $r['id'] . ' (' . $r['category_slug'] . ')';
    $key = $r['parent_slug'] ?: $r['category_slug'];
    $byParent[$key][] = [$r['id'], $base];
    $byCat[$r['category_slug']][] = [$r['id'], $base];
}
echo "\n--- gradient placeholders remaining: " . (count($placeholder) ? implode(', ', $placeholder) : 'NONE') . "\n";

echo "--- duplicate images within a parent category page:\n";
$dupFound = false;
foreach ([$byParent, $byCat] as $scope) {
    foreach ($scope as $key => $items) {
        $seen = [];
        foreach ($items as [$id, $base]) $seen[$base][] = $id;
        foreach ($seen as $base => $ids) {
            if (count($ids) > 1) {
                $dupFound = true;
                echo "  [$key] $base used by posts: " . implode(', ', $ids) . "\n";
            }
        }
    }
}
if (!$dupFound) echo "  NONE\n";

echo "\n--- diagrams present (inline art reused only if no photo):\n";
foreach ($rows as $r) {
    $d = _post_diagram($r['slug']);
    if ($d) echo "  post {$r['id']}: " . basename(parse_url($d, PHP_URL_PATH)) . "\n";
}
