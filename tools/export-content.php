<?php
/** One-off: export current published post content to docs/content/_current/*.html + manifest */
require dirname(__DIR__) . '/includes/functions.php';

$outDir = dirname(__DIR__) . '/docs/content/_current';
if (!is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

$posts = db()->query(
    "SELECT p.id, p.slug, p.title, p.content, p.excerpt, c.name AS cat,
            ROUND(CHAR_LENGTH(p.content)/6) AS words
     FROM posts p LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.status = 'published' ORDER BY p.id"
)->fetchAll();

$manifest = [];
foreach ($posts as $p) {
    file_put_contents($outDir . '/' . $p['slug'] . '.html', $p['content']);
    $manifest[] = [
        'id' => (int) $p['id'], 'slug' => $p['slug'], 'title' => $p['title'],
        'category' => $p['cat'], 'words' => (int) $p['words'],
    ];
}
file_put_contents(dirname(__DIR__) . '/docs/content/_current/manifest.json',
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo 'exported ' . count($posts) . " posts to docs/content/_current/\n";
