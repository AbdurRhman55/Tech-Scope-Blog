<?php
/**
 *  XML sitemap  —  /sitemap.xml
 *
 *  Contains only canonical, indexable URLs that return 200:
 *   - static pages (accurate lastmod omitted rather than guessed)
 *   - categories whose subtree (self + descendants) holds a published post —
 *     mirrors category.php, which counts descendant posts before deciding
 *     index vs noindex (parent categories with only subcategory posts belong
 *     here too)
 *   - tags attached to at least one published post
 *   - active authors
 *   - published posts (lastmod = updated_at)
 *   - archive pagination pages beyond page 1 (index, follow, self-canonical)
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

$perPage = max(1, (int) setting('posts_per_page', '9'));

// Static pages
$static = ['/' => '1.0', '/blog' => '0.9', '/categories' => '0.8', '/sitemap' => '0.3',
           '/about' => '0.5', '/contact' => '0.5', '/privacy' => '0.3', '/terms' => '0.3', '/disclaimer' => '0.3'];
foreach ($static as $path => $prio) {
    sitemap_url(url($path), null, 'weekly', $prio);
}

// Published-post counts + newest update per direct category (shared aggregate)
$postsPerCat = [];
foreach (db()->query(
    "SELECT category_id, COUNT(*) AS n, MAX(updated_at) AS m
     FROM posts
     WHERE status = 'published' AND category_id IS NOT NULL
     GROUP BY category_id"
) as $r) {
    $postsPerCat[(int) $r['category_id']] = ['n' => (int) $r['n'], 'm' => $r['m']];
}

// Categories — subtree-aware (self + descendants, cycle-safe via pre-seeded memo)
$catAll = db()->query('SELECT id, parent_id, slug FROM categories ORDER BY sort_order, name')->fetchAll();
$kids   = [];
foreach ($catAll as $c) {
    $kids[(int) ($c['parent_id'] ?? 0)][] = (int) $c['id'];
}
$subMemo = [];
$collectSubtree = function (int $cid) use (&$collectSubtree, &$subMemo, $kids): array {
    if (isset($subMemo[$cid])) {
        return $subMemo[$cid];
    }
    $subMemo[$cid] = [$cid];               // pre-seed breaks any parent cycle
    foreach ($kids[$cid] ?? [] as $ch) {
        foreach ($collectSubtree($ch) as $d) {
            $subMemo[$cid][] = $d;
        }
    }
    return $subMemo[$cid];
};

foreach ($catAll as $c) {
    $ids  = $collectSubtree((int) $c['id']);
    $n    = 0;
    $last = null;
    foreach ($ids as $id) {
        if (isset($postsPerCat[$id])) {
            $n += $postsPerCat[$id]['n'];
            $m  = $postsPerCat[$id]['m'];
            if ($m !== null) {
                $last = ($last === null) ? $m : max($last, $m);
            }
        }
    }
    if ($n === 0) {
        continue;                          // empty subtree — category.php serves it noindex
    }
    sitemap_url(url('/category/' . $c['slug']), $last, 'weekly', '0.8');
    for ($i = 2, $pages = (int) ceil($n / $perPage); $i <= $pages; $i++) {
        sitemap_url(url('/category/' . $c['slug'] . '?page=' . $i), $last, 'monthly', '0.4');
    }
}

// Tags — only tags attached to at least one published post (no empty/thin archives)
$tagRows = db()->query(
    "SELECT t.slug, MAX(p.updated_at) AS lastmod, COUNT(p.id) AS n
     FROM tags t
     JOIN post_tags pt ON pt.tag_id = t.id
     JOIN posts p ON p.id = pt.post_id AND p.status = 'published'
     GROUP BY t.id, t.slug"
)->fetchAll();
foreach ($tagRows as $tag) {
    sitemap_url(url('/tag/' . $tag['slug']), $tag['lastmod'], 'monthly', '0.4');
    for ($i = 2, $pages = (int) ceil((int) $tag['n'] / $perPage); $i <= $pages; $i++) {
        sitemap_url(url('/tag/' . $tag['slug'] . '?page=' . $i), $tag['lastmod'], 'monthly', '0.3');
    }
}

// Active authors (+ their archive pagination)
foreach (db()->query(
    "SELECT u.slug, u.created_at, COUNT(p.id) AS n, MAX(p.updated_at) AS lastmod
     FROM users u
     LEFT JOIN posts p ON p.author_id = u.id AND p.status = 'published'
     WHERE u.status = 1
     GROUP BY u.id"
) as $u) {
    sitemap_url(url('/author/' . $u['slug']), $u['created_at'], 'monthly', '0.5');
    for ($i = 2, $pages = (int) ceil((int) $u['n'] / $perPage); $i <= $pages; $i++) {
        sitemap_url(url('/author/' . $u['slug'] . '?page=' . $i), $u['lastmod'], 'monthly', '0.3');
    }
}

// Blog archive pagination (page 2+) — index, follow, self-canonical
for ($i = 2, $pages = (int) ceil(count_posts() / $perPage); $i <= $pages; $i++) {
    sitemap_url(url('/blog?page=' . $i), null, 'monthly', '0.4');
}

// Published posts
$stmt = db()->query(
    "SELECT slug, updated_at FROM posts WHERE status = 'published' ORDER BY published_at DESC"
);
foreach ($stmt as $post) {
    sitemap_url(url('/post/' . $post['slug']), $post['updated_at'], 'monthly', '0.7');
}

echo '</urlset>';
