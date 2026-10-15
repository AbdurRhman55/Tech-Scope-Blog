<?php
/**
 *  Apply reviewed expansion files from docs/content/<slug>.html to the database.
 *  - Runs every file through sanitize_html() (same allowlist as the admin editor)
 *  - Recomputes reading_time from the actual text
 *  - Never touches title/slug/excerpt/meta/status/category
 *  Usage: php tools/apply-expansions.php [--dry-run]
 */
require dirname(__DIR__) . '/includes/functions.php';

$dry = in_array('--dry-run', $argv, true);
$dir = dirname(__DIR__) . '/docs/content';
$files = glob($dir . '/*.html') ?: [];

$applied = 0;
$skipped = [];
foreach ($files as $file) {
    $slug = basename($file, '.html');
    $html = trim((string) file_get_contents($file));
    if ($html === '') {
        $skipped[] = "$slug (empty file)";
        continue;
    }

    $stmt = db()->prepare("SELECT id, content FROM posts WHERE slug = ? AND status = 'published'");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
    if (!$post) {
        $skipped[] = "$slug (no published post)";
        continue;
    }

    if ($html === $post['content']) {
        $skipped[] = "$slug (unchanged)";
        continue;
    }

    $clean = sanitize_html($html);
    $reading = max(1, (int) ceil(str_word_count(strip_tags($clean)) / 220));
    $words = str_word_count(strip_tags($clean));

    if ($dry) {
        echo "[dry] $slug: $words words, ~{$reading} min\n";
        $applied++;
        continue;
    }

    db()->prepare('UPDATE posts SET content = ?, reading_time = ?, updated_at = NOW() WHERE id = ?')
        ->execute([$clean, $reading, (int) $post['id']]);
    $applied++;
    echo sprintf("applied %-55s %5d words  %2d min\n", $slug, $words, $reading);
}

echo "\napplied: $applied\n";
if ($skipped) {
    echo "skipped:\n  " . implode("\n  ", $skipped) . "\n";
}
