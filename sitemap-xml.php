<?php
/**
 *  XML sitemap  —  /sitemap.xml
 *
 *  Contains only canonical, indexable URLs that return 200:
 *   - static pages (accurate lastmod omitted rather than guessed)
 *   - categories + tags that hold at least one published post
 *   - active authors
 *   - published posts (lastmod = updated_at)
 *  Excludes: admin, search, empty archives, drafts.
 */

require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$escape = fn(string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');

/** Emit one <url> entry. lastmod is only printed when genuinely known. */
function sitemap_url(string $loc, ?string $lastmod = null, string $changefreq = 'weekly', string $priority = '0.5'): void
{
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
    if ($lastmod !== null && $lastmod !== '' && strtotime($lastmod) !== false) {
        echo '    <lastmod>' . date('Y-m-d', strtotime($lastmod)) . "</lastmod>\n";
    }
    echo '    <changefreq>' . $changefreq . "</changefreq>\n";
    echo '    <priority>' . $priority . "</priority>\n";
    echo "  </url>\n";
}

// Static pages
$static = ['/' => '1.0', '/blog' => '0.9', '/categories' => '0.8', '/about' => '0.5',
           '/contact' => '0.5', '/privacy' => '0.3', '/terms' => '0.3', '/disclaimer' => '0.3'];
foreach ($static as $path => $prio) {
    sitemap_url(url($path), null, 'weekly', $prio);
}

// Categories — lastmod reflects the newest post inside the category
$catRows = db()->query(
    "SELECT c.slug, MAX(p.updated_at) AS lastmod
     FROM categories c
     JOIN posts p ON p.category_id = c.id AND p.status = 'published'
     GROUP BY c.id, c.slug
     ORDER BY c.sort_order"
)->fetchAll();
foreach ($catRows as $cat) {
    sitemap_url(url('/category/' . $cat['slug']), $cat['lastmod'], 'weekly', '0.8');
}

// Tags — only tags attached to at least one published post (no empty/thin archives)
$tagRows = db()->query(
    "SELECT t.slug, MAX(p.updated_at) AS lastmod
     FROM tags t
     JOIN post_tags pt ON pt.tag_id = t.id
     JOIN posts p ON p.id = pt.post_id AND p.status = 'published'
     GROUP BY t.id, t.slug"
)->fetchAll();
foreach ($tagRows as $tag) {
    sitemap_url(url('/tag/' . $tag['slug']), $tag['lastmod'], 'monthly', '0.4');
}

// Active authors
foreach (db()->query('SELECT slug, created_at FROM users WHERE status = 1')->fetchAll() as $u) {
    sitemap_url(url('/author/' . $u['slug']), $u['created_at'], 'monthly', '0.5');
}

// Published posts
$stmt = db()->query(
    "SELECT slug, updated_at FROM posts WHERE status = 'published' ORDER BY published_at DESC"
);
foreach ($stmt as $post) {
    sitemap_url(url('/post/' . $post['slug']), $post['updated_at'], 'monthly', '0.7');
}

echo '</urlset>';
