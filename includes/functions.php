<?php
/**
 * ---------------------------------------------------------------------
 *  Core helper functions
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/** Escape output (XSS-safe) */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Load all settings into an associative array (cached) */
function settings(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $cache = db()->query('SELECT setting_key, setting_value FROM settings')
                     ->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        $cache = [];
    }
    return $cache;
}

/** Get single setting with default */
function setting(string $key, string $default = ''): string
{
    $all = settings();
    return ($all[$key] ?? '') !== '' ? (string) $all[$key] : $default;
}

/** Build internal URL: url('/blog') -> SITE_URL/blog */
function url(string $path = '/'): string
{
    $base = rtrim(SITE_URL, '/');
    if ($path === '' || $path === '/') {
        return $base . '/';
    }
    return $base . '/' . ltrim($path, '/');
}

/** Build internal Admin URL: admin_url('posts.php') -> SITE_URL/admin/posts.php */
function admin_url(string $path = ''): string
{
    $sub = 'admin' . ($path !== '' ? '/' . ltrim($path, '/') : '');
    return url($sub);
}

/**
 * Cache-busting token for static assets: newest mtime across the front-end
 * JS + CSS files. Appended as ?v=… so a deploy instantly busts browser caches.
 */
function asset_version(): string
{
    static $ver = null;
    if ($ver !== null) {
        return $ver;
    }
    $files = [
        ROOT_PATH . '/assets/css/app.css',
        ROOT_PATH . '/assets/js/main.js',
        ROOT_PATH . '/assets/js/alpine.min.js',
        ROOT_PATH . '/assets/js/admin.js',
        ROOT_PATH . '/assets/js/chart.umd.min.js',
        ROOT_PATH . '/assets/js/dashboard-charts.js',
    ];
    $newest = 0;
    foreach ($files as $f) {
        $mtime = @filemtime($f);
        if ($mtime !== false && $mtime > $newest) {
            $newest = $mtime;
        }
    }
    $ver = $newest > 0 ? (string) $newest : (string) time();
    return $ver;
}

/** Generate URL-friendly slug */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item-' . substr(md5((string) microtime(true)), 0, 6);
}

/** Unique slug inside a table (excluding optional id) */
function unique_slug(string $table, string $slug, ?int $excludeId = null): string
{
    $sql    = "SELECT COUNT(*) FROM {$table} WHERE slug = ?" . ($excludeId ? ' AND id != ?' : '');
    $params = $excludeId ? [$slug, $excludeId] : [$slug];
    $stmt   = db()->prepare($sql);
    $stmt->execute($params);
    if ((int) $stmt->fetchColumn() === 0) {
        return $slug;
    }
    $i = 2;
    do {
        $candidate = $slug . '-' . $i;
        $stmt = db()->prepare($sql);
        $stmt->execute($excludeId ? [$candidate, $excludeId] : [$candidate]);
        $i++;
    } while ((int) $stmt->fetchColumn() > 0);
    return $candidate;
}

/* ---------------------------------------------------------------------
 *  CSRF protection
 * --------------------------------------------------------------------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        // A reason phrase is required: Apache's HTTP SAPI maps unknown status
        // codes without one to 500 (the PHP built-in dev server passes them through).
        $proto = (string) ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1');
        header($proto . ' 419 CSRF Token Mismatch', true, 419);
        die('Invalid or expired security token. Please go back and try again.');
    }
}

/* ---------------------------------------------------------------------
 *  Flash messages
 * --------------------------------------------------------------------- */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/* ---------------------------------------------------------------------
 *  SEO helpers
 * --------------------------------------------------------------------- */
function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 3600)  return max(1, intdiv($diff, 60)) . ' min ago';
    if ($diff < 86400) return intdiv($diff, 3600) . ' hour' . (intdiv($diff, 3600) > 1 ? 's' : '') . ' ago';
    if ($diff < 604800){ $d = intdiv($diff, 86400); return $d . ' day' . ($d > 1 ? 's' : '') . ' ago'; }
    return date('M j, Y', strtotime($datetime));
}

function excerpt_text(string $html, int $length = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $length), " ,.;:-") . '…';
}

function reading_time(string $html): int
{
    // Count words in the rendered text — never trust a stored value.
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
    $words = $text === '' ? 0 : str_word_count($text);
    return max(1, (int) ceil($words / 220));
}

/** ISO 8601 timestamp (JSON-LD / Open Graph require the T separator + offset) */
function iso8601(?string $datetime): string
{
    $ts = $datetime ? strtotime($datetime) : false;
    return date('c', $ts !== false ? $ts : time());
}

/** Flag the current response as noindex (search results, empty archives) */
function seo_noindex(bool $on = true): void
{
    $GLOBALS['seo_noindex'] = $on;
}

/**
 * Request path with the app's base path (derived from SITE_URL) removed,
 * so matching works both at the domain root and under a subdirectory
 * deployment (local vhosts, cPanel add-on domains).
 *   /Blog-website/blog?page=2  ->  /blog?page=2
 */
function app_request_path(): string
{
    $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
    $base = rtrim((string) (parse_url(SITE_URL, PHP_URL_PATH) ?: ''), '/');
    if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
        $path = substr($path, strlen($base)) ?: '/';
    }
    return $path !== '' ? $path : '/';
}

/**
 * Canonical URL for the current request.
 *  - keeps meaningful params (?page=2, ?q=…) so paginated pages self-canonical
 *  - drops tracking params (utm_*, fbclid, gclid, ref…)
 *  - normalises trailing slashes and duplicate slashes
 */
function canonical_url(): string
{
    $uri   = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path  = app_request_path();
    $query = (string) (parse_url($uri, PHP_URL_QUERY) ?: '');

    $keep = [];
    if ($query !== '') {
        parse_str($query, $params);
        foreach (['page', 'q'] as $key) {
            if (!isset($params[$key]) || $params[$key] === '') {
                continue;
            }
            $value = (string) $params[$key];
            if ($key === 'page') {
                $value = (string) max(1, (int) $value);
                if ($value === '1') continue;   // ?page=1 == canonical base URL
            }
            $keep[$key] = $value;
        }
    }

    $path = preg_replace('#/+#', '/', $path) ?? $path;
    if ($path !== '/') {
        $path = rtrim($path, '/');
    }

    return SITE_URL . $path . ($keep !== [] ? '?' . http_build_query($keep) : '');
}

/**
 * Social crawlers cannot fetch data: URIs — normalise any generated cover
 * to a real absolute image URL for og:image / JSON-LD.
 */
function normalize_og_image(string $image): string
{
    $path = strtolower((string) (parse_url($image, PHP_URL_PATH) ?: ''));
    if ($image === '' || str_starts_with($image, 'data:') || str_ends_with($path, '.svg')) {
        return url('/assets/img/default-og.png');
    }
    return $image;
}

/* ---------------------------------------------------------------------
 *  SEO — structured data (JSON-LD) helpers
 * --------------------------------------------------------------------- */

/** Queue a JSON-LD schema block; printed by seo_head() */
function seo_jsonld_add(array $schema): void
{
    $GLOBALS['seo_jsonld'][] = $schema;
}

/** Render <script type="application/ld+json"> for queued schemas + defaults */
function seo_jsonld_render(): void
{
    $siteName = setting('site_name', SITE_NAME);
    $siteUrl  = rtrim(SITE_URL, '/');

    // Default: WebSite + Organization (sitewide — powers rich results)
    $defaults = [
        [
            '@context' => 'https://schema.org',
            '@type'    => 'WebSite',
            'name'     => $siteName,
            'url'      => $siteUrl,
            'description' => setting('site_description'),
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => $siteUrl . '/search?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => $siteName,
            'url'      => $siteUrl,
            'logo'     => site_logo_src() ?: ($siteUrl . '/assets/img/favicon.svg'),
        ],
    ];

    foreach (array_merge($defaults, $GLOBALS['seo_jsonld'] ?? []) as $schema) {
        echo '<script type="application/ld+json">'
           . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
           . "</script>\n";
    }
}

/** Breadcrumb schema helper — pass ['Home' => '/', 'Category' => '/category/x'] */
function seo_jsonld_breadcrumb(array $crumbs): void
{
    $items = [];
    $n     = count($crumbs);
    $i     = 1;
    foreach ($crumbs as $label => $path) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $i++,
            'name'     => $label,
            'item'     => SITE_URL . ($path === '/' ? '/' : rtrim($path, '/')),
        ];
    }
    seo_jsonld_add([
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $items,
    ]);
    unset($n);
}

/* ---------------------------------------------------------------------
 *  SEO — head tags
 * --------------------------------------------------------------------- */

/** Render <title> + meta + Open Graph + Twitter tags */
function seo_head(?string $title = null, ?string $desc = null, string $type = 'website', ?string $image = null): void
{
    $siteName  = setting('site_name', SITE_NAME);
    $title     = $title !== null ? $title : $siteName . ' — ' . setting('site_tagline');

    // SERP-safe title length: Google truncates around 60 characters and
    // Ahrefs flags anything over 70 as "Title too long". First try dropping
    // the trailing " | Brand" suffix (keeps every word of the page title
    // intact), otherwise cut on a word boundary — never mid-word, never
    // below 40 chars (avoids tripping "Title too short").
    if (mb_strlen($title) > 70) {
        $bare = (string) preg_replace('/\s*\|\s*' . preg_quote($siteName, '/') . '$/u', '', $title);
        if ($bare !== '' && mb_strlen($bare) >= 40 && mb_strlen($bare) <= 70) {
            $title = $bare;
        } else {
            $cut = mb_substr($title, 0, 70);
            $sp  = mb_strrpos($cut, ' ');
            if ($sp !== false && $sp >= 40) {
                $title = mb_substr($cut, 0, $sp);
            }
        }
    }

    $desc      = $desc !== null ? $desc : setting('site_description');
    $desc      = mb_substr($desc, 0, 300);
    $image     = normalize_og_image($image ?: url('/assets/img/default-og.png'));
    $url       = canonical_url();

    // Search results and flagged archives must never be indexed, whatever
    // the query string looks like (/search?q=…, empty tags, etc.).
    $reqPath   = app_request_path();
    $noindex   = rtrim($reqPath, '/') === '/search' || !empty($GLOBALS['seo_noindex']);

    echo "<title>" . e($title) . "</title>\n";
    echo '<meta name="description" content="' . e($desc) . "\">\n";
    echo '<meta name="keywords" content="' . e(setting('meta_keywords')) . "\">\n";
    echo '<meta name="author" content="' . e(setting('site_name', SITE_NAME)) . "\">\n";
    echo '<meta name="theme-color" content="#101319">' . "\n";
    echo '<link rel="canonical" href="' . e($url) . "\">\n";

    // Open Graph
    echo '<meta property="og:site_name" content="' . e($siteName) . "\">\n";
    echo '<meta property="og:title" content="' . e($title) . "\">\n";
    echo '<meta property="og:description" content="' . e($desc) . "\">\n";
    echo '<meta property="og:type" content="' . e($type) . "\">\n";
    echo '<meta property="og:url" content="' . e($url) . "\">\n";
    echo '<meta property="og:image" content="' . e($image) . "\">\n";
    echo '<meta property="og:locale" content="en_US">' . "\n";
    if ($type === 'article') {
        $extra = $GLOBALS['seo_og_article'] ?? [];
        if (!empty($extra['published_time'])) echo '<meta property="article:published_time" content="' . e(iso8601($extra['published_time'])) . "\">\n";
        if (!empty($extra['modified_time']))  echo '<meta property="article:modified_time" content="' . e(iso8601($extra['modified_time'])) . "\">\n";
        if (!empty($extra['section']))        echo '<meta property="article:section" content="' . e($extra['section']) . "\">\n";
        foreach (($extra['tags'] ?? []) as $t) {
            echo '<meta property="article:tag" content="' . e($t) . "\">\n";
        }
    }

    // Twitter
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . e($title) . "\">\n";
    echo '<meta name="twitter:description" content="' . e($desc) . "\">\n";
    echo '<meta name="twitter:image" content="' . e($image) . "\">\n";

    echo '<meta name="robots" content="' . ($noindex
        ? 'noindex, follow'
        : 'index, follow, max-image-preview:large') . '">' . "\n";

    seo_jsonld_render();
}

/* ---------------------------------------------------------------------
 *  Data access
 * --------------------------------------------------------------------- */

/** Fetch all published posts with author + category (paginated) */
function get_posts(int $page = 1, int $perPage = 9, int|array|null $categoryId = null, ?string $search = null, ?int $authorId = null, string $orderBy = 'p.published_at DESC'): array
{
    $offset  = max(0, ($page - 1) * $perPage);
    $where   = ["p.status = 'published'"];
    $params  = [];

    $catIds = array_values(array_filter(array_map('intval', (array) ($categoryId ?? []))));
    if ($catIds) {
        $where[] = 'p.category_id IN (' . implode(',', array_fill(0, count($catIds), '?')) . ')';
        array_push($params, ...$catIds);
    }
    if ($authorId)   { $where[] = 'p.author_id = ?';   $params[] = $authorId; }
    if ($search) {
        $where[] = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like);
    }
    $whereSql = implode(' AND ', $where);

    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                   u.name AS author_name, u.slug AS author_slug, u.avatar AS author_avatar
            FROM posts p
            LEFT JOIN categories c ON c.id = p.category_id
            JOIN users u ON u.id = p.author_id
            WHERE {$whereSql}
            ORDER BY {$orderBy}
            LIMIT {$perPage} OFFSET {$offset}";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Total count matching same filters (for pagination) */
function count_posts(int|array|null $categoryId = null, ?string $search = null, ?int $authorId = null): int
{
    $where  = ["p.status = 'published'"];
    $params = [];
    $catIds = array_values(array_filter(array_map('intval', (array) ($categoryId ?? []))));
    if ($catIds) {
        $where[] = 'p.category_id IN (' . implode(',', array_fill(0, count($catIds), '?')) . ')';
        array_push($params, ...$catIds);
    }
    if ($authorId)   { $where[] = 'p.author_id = ?';   $params[] = $authorId; }
    if ($search) {
        $where[] = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like);
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM posts p WHERE ' . implode(' AND ', $where));
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/** Single post by slug (with relations) */
function get_post_by_slug(string $slug): ?array
{
    $stmt = db()->prepare(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                u.name AS author_name, u.slug AS author_slug, u.avatar AS author_avatar,
                u.bio AS author_bio, u.twitter AS author_twitter, u.website AS author_website
         FROM posts p
         LEFT JOIN categories c ON c.id = p.category_id
         JOIN users u ON u.id = p.author_id
         WHERE p.slug = ? AND p.status = 'published'
         LIMIT 1"
    );
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Tags for a post */
function get_post_tags(int $postId): array
{
    $stmt = db()->prepare(
        'SELECT t.name, t.slug FROM tags t
         JOIN post_tags pt ON pt.tag_id = t.id
         WHERE pt.post_id = ? ORDER BY t.name'
    );
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

/** Approved comments for a post */
function get_post_comments(int $postId): array
{
    $stmt = db()->prepare(
        "SELECT c.*, u.avatar AS user_avatar
         FROM comments c
         LEFT JOIN users u ON u.email = c.email
         WHERE c.post_id = ? AND c.status = 'approved'
         ORDER BY c.created_at ASC"
    );
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

/** All categories with published post counts (own + every descendant's) */
function get_categories(bool $onlyWithPosts = false): array
{
    $rows = db()->query(
        "SELECT c.*, COUNT(p.id) AS post_count
         FROM categories c
         LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'published'
         GROUP BY c.id
         ORDER BY c.sort_order ASC, c.name ASC"
    )->fetchAll();

    // Roll child counts up to their parents (categories are few; cycle-safe via seen-set)
    $kids = [];
    foreach ($rows as $r) {
        $kids[(int) ($r['parent_id'] ?? 0)][] = (int) $r['id'];
    }
    $own  = [];
    $byId = [];
    foreach ($rows as $r) {
        $own[(int) $r['id']]  = (int) $r['post_count'];
        $byId[(int) $r['id']] = $r;
    }
    $collect = function (int $id, array &$seen) use (&$collect, $kids): void {
        if (isset($seen[$id])) {
            return;
        }
        $seen[$id] = true;
        foreach ($kids[$id] ?? [] as $child) {
            $collect($child, $seen);
        }
    };
    foreach ($rows as &$r) {
        $seen = [];
        $collect((int) $r['id'], $seen);
        $total = 0;
        foreach (array_keys($seen) as $id) {
            $total += $own[$id] ?? 0;
        }
        $r['own_post_count'] = $own[(int) $r['id']];
        $r['post_count']     = $total;
    }
    unset($r);

    // Public listings link only archives that hold posts; empty archives stay
    // noindex (category.php) and must not be advertised across the site.
    if ($onlyWithPosts) {
        $rows = array_values(array_filter($rows, static fn(array $r): bool => (int) $r['post_count'] > 0));
    }

    return $rows;
}

/**
 * Navigation tree: top-level categories (parent_id = 0), each with a 'children'
 * array. Capped at 3 levels for display; cycles are unreachable from the root
 * and additionally stopped by the level cap.
 */
function get_category_tree(int $maxDepth = 3, bool $onlyWithPosts = false): array
{
    $kids = [];
    foreach (get_categories($onlyWithPosts) as $c) {
        $kids[(int) ($c['parent_id'] ?? 0)][] = $c;
    }
    $build = function (int $parentId, int $level) use (&$build, $kids, $maxDepth): array {
        if ($level >= $maxDepth) {
            return [];
        }
        $out = [];
        foreach ($kids[$parentId] ?? [] as $c) {
            $c['children'] = $build((int) $c['id'], $level + 1);
            $out[] = $c;
        }
        return $out;
    };
    return $build(0, 0);
}

/** Flat list in tree order with a 'depth' key (admin lists & selects) */
function get_categories_nested(): array
{
    $out  = [];
    $seen = [];
    $walk = function (array $nodes, int $depth) use (&$walk, &$out, &$seen): void {
        foreach ($nodes as $c) {
            $seen[(int) $c['id']] = true;
            $c['depth']           = $depth;
            $out[]                = $c;
            if (!empty($c['children'])) {
                $walk($c['children'], $depth + 1);
            }
        }
    };
    $walk(get_category_tree(PHP_INT_MAX), 0);
    // Orphaned rows (parent deleted / impossible cycle) are still listed so they can be fixed
    foreach (get_categories() as $c) {
        if (!isset($seen[(int) $c['id']])) {
            $c['depth'] = 0;
            $out[]      = $c;
        }
    }
    return $out;
}

/** Self + all descendant category IDs (cycle-safe via seen-set) */
function category_descendant_ids(int $id): array
{
    $kids = [];
    foreach (db()->query('SELECT id, parent_id FROM categories') as $r) {
        $kids[(int) ($r['parent_id'] ?? 0)][] = (int) $r['id'];
    }
    $seen = [];
    $walk = function (int $cid) use (&$walk, &$seen, $kids): void {
        if (isset($seen[$cid])) {
            return;
        }
        $seen[$cid] = true;
        foreach ($kids[$cid] ?? [] as $ch) {
            $walk($ch);
        }
    };
    $walk($id);
    return array_keys($seen);
}

/** Self + ancestor category IDs (nav active states), cycle-capped */
function category_ancestry(int $id): array
{
    $parent = [];
    foreach (db()->query('SELECT id, parent_id FROM categories') as $r) {
        $parent[(int) $r['id']] = (int) ($r['parent_id'] ?? 0);
    }
    $out  = [];
    $cur  = $id;
    while ($cur > 0 && !in_array($cur, $out, true)) {
        $out[] = $cur;
        $cur   = $parent[$cur] ?? 0;
    }
    return $out;
}

function get_category_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_author_by_slug(string $slug): ?array
{
    $stmt = db()->prepare("SELECT * FROM users WHERE slug = ? AND status = 1 LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Popular tags with counts */
function get_popular_tags(int $limit = 20): array
{
    $stmt = db()->prepare(
        "SELECT t.name, t.slug, COUNT(pt.post_id) AS tag_count
         FROM tags t
         JOIN post_tags pt ON pt.tag_id = t.id
         JOIN posts p ON p.id = pt.post_id AND p.status = 'published'
         GROUP BY t.id
         ORDER BY tag_count DESC
         LIMIT {$limit}"
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

/** Featured + recent posts for homepage hero */
function get_featured_post(): ?array
{
    $stmt = db()->query(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                u.name AS author_name, u.slug AS author_slug, u.avatar AS author_avatar
         FROM posts p
         LEFT JOIN categories c ON c.id = p.category_id
         JOIN users u ON u.id = p.author_id
         WHERE p.status = 'published' AND p.is_featured = 1
         ORDER BY p.published_at DESC LIMIT 1"
    );
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Increment view counter */
function bump_views(int $postId): void
{
    db()->prepare('UPDATE posts SET views = views + 1 WHERE id = ?')->execute([$postId]);
}

/* ---------------------------------------------------------------------
 *  Related articles — relevance (shared tags) first, never RAND()
 * --------------------------------------------------------------------- */
function get_related_posts(array $post, int $limit = 4): array
{
    $postId     = (int) $post['id'];
    $categoryId = $post['category_id'] !== null ? (int) $post['category_id'] : null;
    $pdo        = db();

    // 1) articles sharing at least one tag
    $stmt = $pdo->prepare(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                u.name AS author_name, u.slug AS author_slug,
                COUNT(DISTINCT pt2.tag_id) AS shared_tags
         FROM posts p
         JOIN post_tags pt1 ON pt1.post_id = ? AND pt1.tag_id IS NOT NULL
         JOIN post_tags pt2 ON pt2.tag_id = pt1.tag_id AND pt2.post_id = p.id
         LEFT JOIN categories c ON c.id = p.category_id
         JOIN users u ON u.id = p.author_id
         WHERE p.status = 'published' AND p.id <> ?
         GROUP BY p.id
         ORDER BY shared_tags DESC, p.published_at DESC
         LIMIT {$limit}"
    );
    $stmt->execute([$postId, $postId]);
    $related = $stmt->fetchAll();
    $picked  = array_column($related, 'id');

    // 2) fill from the same topical cluster (category)
    if (count($related) < $limit && $categoryId !== null) {
        $exclude = $picked;
        $exclude[] = $postId;
        $in  = implode(',', array_fill(0, count($exclude), '?'));
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                       u.name AS author_name, u.slug AS author_slug
                FROM posts p
                LEFT JOIN categories c ON c.id = p.category_id
                JOIN users u ON u.id = p.author_id
                WHERE p.status = 'published' AND p.id NOT IN ({$in})
                ORDER BY p.published_at DESC LIMIT " . ($limit - count($related));
        $stmt = $pdo->prepare($sql);
        $stmt->execute($exclude);
        foreach ($stmt->fetchAll() as $row) {
            $related[]  = $row;
            $picked[]   = $row['id'];
        }
    }

    // 3) final fill: most recent published
    if (count($related) < $limit) {
        $exclude   = $picked;
        $exclude[] = $postId;
        $in  = implode(',', array_fill(0, count($exclude), '?'));
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                       u.name AS author_name, u.slug AS author_slug
                FROM posts p
                LEFT JOIN categories c ON c.id = p.category_id
                JOIN users u ON u.id = p.author_id
                WHERE p.status = 'published' AND p.id NOT IN ({$in})
                ORDER BY p.published_at DESC LIMIT " . ($limit - count($related));
        $stmt = $pdo->prepare($sql);
        $stmt->execute($exclude);
        $related = array_merge($related, $stmt->fetchAll());
    }

    return $related;
}

/* ---------------------------------------------------------------------
 *  Article body processing — heading IDs + table of contents
 * --------------------------------------------------------------------- */

/**
 * Assign unique, stable IDs to H2/H3 headings and build a TOC structure.
 * Returns [html with ids, [['level','id','text'], …]].
 */
function prepare_article_html(string $html): array
{
    $toc  = [];
    $used = [];

    $html = preg_replace_callback(
        '/<h([23])(\s[^>]*)?>(.*?)<\/h\1>/is',
        function (array $m) use (&$toc, &$used): string {
            $level = (int) $m[1];
            $attrs = (string) ($m[2] ?? '');
            $inner = $m[3];
            $text  = trim(strip_tags($inner));
            if ($text === '') {
                return $m[0];                       // never emit empty headings
            }
            $id   = slugify($text);
            $base = $id;
            $n    = 2;
            while (isset($used[$id])) {             // duplicate-safe
                $id = $base . '-' . $n++;
            }
            $used[$id] = true;
            if (!preg_match('/\sid\s*=/i', $attrs)) {
                $attrs .= ' id="' . $id . '"';
            }
            $toc[] = ['level' => $level, 'id' => $id, 'text' => $text];
            return '<h' . $level . $attrs . '>' . $inner . '</h' . $level . '>';
        },
        $html,
        300
    );

    return [$html, $toc];
}

/** Show a TOC only on genuinely long articles (never on thin posts) */
function should_render_toc(array $toc, string $content): bool
{
    return count($toc) >= 3 && str_word_count(strip_tags($content)) >= 300;
}

/**
 * Wrap content tables in a horizontal-scroll container so wide tables
 * never break the mobile layout. (`div` is not in the sanitize allowlist,
 * so tables coming from the editor can never already be wrapped.)
 */
function wrap_scrollable_tables(string $html): string
{
    if (!str_contains($html, '<table')) {
        return $html;
    }
    $html = preg_replace('/<table\b/i', '<div class="table-scroll"><table', $html) ?? $html;
    $html = preg_replace('/<\/table>/i', '</table></div>', $html) ?? $html;
    return $html;
}

/**
 * Parse editor-supplied reference lines into [['label' => …, 'url' => …], …].
 * Accepts "Label - https://…" or a bare http(s) URL. Anything that is not a
 * valid http(s) URL is skipped — the template never emits broken links.
 */
function parse_reference_lines(?string $text): array
{
    $out = [];
    foreach (preg_split('/\R/', (string) $text) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $url   = $line;
        $label = '';
        if (preg_match('/^(.*?)\s+[-–—]\s+(https?:\/\/\S+)$/u', $line, $m)) {
            $label = trim($m[1]);
            $url   = $m[2];
        }
        $url = rtrim($url, '.,;');
        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
            continue;
        }
        if ($label === '') {
            $label = ((string) parse_url($url, PHP_URL_HOST)) . ((string) parse_url($url, PHP_URL_PATH));
        }
        $out[] = ['label' => $label, 'url' => $url];
        if (count($out) >= 20) {
            break;
        }
    }
    return $out;
}

/* ---------------------------------------------------------------------
 *  Contextual internal links (render-time only — stored content untouched)
 *
 *  Curated phrase → article map. Each anchor:
 *   - links an exact phrase that genuinely describes the destination
 *   - first occurrence only, max 3 per article
 *   - never inside <a>, <pre> or <code>
 *   - skipped when the destination is the current post or already linked
 *   - only published slugs resolve (no broken internal links)
 * --------------------------------------------------------------------- */
function internal_link_map(): array
{
    return [
        '/\blarge language models?\b/i'          => 'how-large-language-models-actually-work',
        '/\blanguage models?\b/i'                => 'how-large-language-models-actually-work',
        '/\bprompt engineering\b/i'              => 'context-engineering-over-prompt-engineering',
        '/\bcontext engineering\b/i'             => 'context-engineering-over-prompt-engineering',
        '/\bprompt\b/i'                          => 'context-engineering-over-prompt-engineering',
        '/\bRAG\b/i'                             => 'rag-vs-fine-tuning-llm',
        '/\bretrieval.augmented generation\b/i'  => 'rag-vs-fine-tuning-llm',
        '/\bfine.tuning\b/i'                     => 'rag-vs-fine-tuning-llm',
        '/\blocal LLMs?\b/i'                     => 'local-llms-laptop-guide',
        '/\blocal models?\b/i'                   => 'local-llms-laptop-guide',
        '/\bSQL index(?:es|ing)?\b/i'            => 'sql-indexing-practical-guide',
        '/\bcovering index\b/i'                  => 'sql-indexing-practical-guide',
        '/\bslow queries|\bqueries\b/i'          => 'sql-indexing-practical-guide',
        '/\bfeature engineering\b/i'             => 'feature-engineering-tabular-data',
        '/\bcohort analysis\b/i'                 => 'cohort-analysis-retention-guide',
        '/\bdata visualization\b/i'              => 'data-visualization-principles',
        '/\bserverless (?:architecture|functions?)\b/i' => 'serverless-architecture-when-it-shines-and-when-it-fails',
        '/\bpasskeys?\b/i'                       => 'passkeys-passwordless-web',
        '/\bGit workflows?\b/i'                  => 'git-workflows-team-scale',
        '/\bclean code\b/i'                      => 'clean-code-php-patterns',
        '/\bdesign patterns?\b/i'                => 'clean-code-php-patterns',
        '/\bTypeScript\b/i'                      => 'typescript-tips-hard-way',
        '/\bresearch papers?\b/i'                => 'how-to-read-research-paper',
        '/\breproducible (?:research|environments?)\b/i' => 'reproducible-research-environment',
        '/\bdeep work\b/i'                       => 'deep-work-realistic-system',
        '/\bZettelkasten\b/i'                    => 'zettelkasten-note-taking-simple',
        '/\bspaced repetition\b/i'               => 'evidence-based-learning-techniques',
        '/\bSTAR method\b/i'                     => 'interview-prep-star-method',
        '/\bgetting interviews\b|\binterviews\b/i' => 'student-portfolio-gets-interviews',
        '/\bstudent portfolio\b|\bportfolio\b/i' => 'student-portfolio-gets-interviews',
        '/\bscope creep\b/i'                     => 'client-onboarding-emails-scope-creep',
        '/\bclient onboarding\b/i'               => 'client-onboarding-emails-scope-creep',
        '/\bfreelance niche\b|\bniche positioning\b/i' => 'freelance-niche-positioning',
        // generic fallbacks — placed last so specific phrases win each paragraph
        '/\bmodels\b/i'                          => 'how-large-language-models-actually-work',
    ];
}

function auto_internal_links(string $html, int $currentPostId, int $maxLinks = 3): string
{
    static $slugById = null;
    if ($slugById === null) {
        $slugById = db()->query("SELECT id, slug FROM posts WHERE status = 'published'")
                        ->fetchAll(PDO::FETCH_KEY_PAIR);   // id => slug
    }

    $parts    = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$html];
    $skip     = 0;          // inside <a>/<pre>/<code>?
    $added    = 0;
    $linked   = [];         // slugs linked in this pass

    foreach ($parts as $i => $part) {
        if ($i % 2 === 1) {                             // a tag
            if (preg_match('#^<\s*(a|pre|code)\b#i', $part)) {
                $skip++;
            } elseif (preg_match('#^<\s*/\s*(a|pre|code)\s*>#i', $part)) {
                $skip = max(0, $skip - 1);
            }
            continue;
        }
        if ($skip > 0 || $added >= $maxLinks) {
            continue;
        }

        foreach (internal_link_map() as $pattern => $slug) {
            if ($added >= $maxLinks) break;
            if (($slugById[$currentPostId] ?? '') === $slug) continue;     // no self-link
            if (in_array($slug, $linked, true))        continue;           // once per article
            if (str_contains($html, '/post/' . $slug . '"')) continue;     // already in the source HTML

            $before = $added;
            $part = preg_replace_callback(
                $pattern,
                function (array $m) use (&$added, $maxLinks, $slug): string {
                    if ($added >= $maxLinks) return $m[0];
                    $added++;
                    return '<a href="' . url('/post/' . $slug) . '" class="font-semibold text-primary underline decoration-primary/30 underline-offset-2 transition hover:text-accent hover:decoration-accent">'
                         . $m[0] . '</a>';
                },
                $part,
                1
            );
            if ($part !== null && $added > $before) {
                $linked[] = $slug;
                break;                                  // one link per text block
            }
        }
        if ($part !== null) {
            $parts[$i] = $part;
        }
    }

    return implode('', $parts);
}

/**
 * Render the 404 page and stop. Call BEFORE outputting the header —
 * used for out-of-range ?page= parameters so pagination never
 * silently duplicates page 1 content.
 */
function abort_404(): void
{
    http_response_code(404);
    require dirname(__DIR__) . '/404.php';
    exit;
}

/* ---------------------------------------------------------------------
 *  Pagination renderer
 * --------------------------------------------------------------------- */
function render_pagination(int $page, int $totalPages, callable $linkFn): void
{
    if ($totalPages <= 1) {
        return;
    }
    echo '<nav class="mt-12 flex items-center justify-center gap-1.5" aria-label="Pagination">';

    if ($page > 1) {
        echo '<a href="' . e($linkFn($page - 1)) . '" class="rounded-lg border border-line bg-surface px-3.5 py-2 text-sm font-semibold transition hover:border-ink hover:text-ink">&larr; Prev</a>';
    }
    for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++) {
        if ($i === $page) {
            echo '<span class="rounded-lg bg-ink px-4 py-2 text-sm font-semibold text-paper">' . $i . '</span>';
        } else {
            echo '<a href="' . e($linkFn($i)) . '" class="rounded-lg border border-line bg-surface px-3.5 py-2 text-sm font-semibold transition hover:border-ink hover:text-ink">' . $i . '</a>';
        }
    }
    if ($page < $totalPages) {
        echo '<a href="' . e($linkFn($page + 1)) . '" class="rounded-lg border border-line bg-surface px-3.5 py-2 text-sm font-semibold transition hover:border-ink hover:text-ink">Next &rarr;</a>';
    }
    echo '</nav>';
}

/**
 *  Sanitise rich-text HTML from the admin editor (allowlist).
 *  Keeps structure, strips scripts/events/dangerous attributes.
 */
function sanitize_html(string $html): string
{
    $allowed = '<p><br><h2><h3><h4><strong><b><em><i><u><s><blockquote><ul><ol><li>'
             . '<a><pre><code><hr><img><span><figure><figcaption><table><thead><tbody><tr><th><td>';

    $html = strip_tags($html, $allowed);

    // Remove event handlers & javascript: URLs
    $html = preg_replace('/\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? $html;
    $html = preg_replace('/\s(href|src)\s*=\s*("|\'?)\s*javascript:[^"\'>\s]+("|\'?)/i', '$1="#"', $html) ?? $html;

    return trim($html);
}

/** Gradient initials avatar (inline SVG data URI) — used when no photo uploaded */
function avatar_src(string $name): string
{
    $initial = strtoupper(mb_substr(trim($name) ?: 'U', 0, 1));
    $h       = crc32($name);
    $pairs   = [
        ['#1f4fd8', '#3b74f0'], ['#e8402a', '#f0743c'], ['#1d7a54', '#3fae74'],
        ['#c9a227', '#e3c04f'], ['#6d4bd8', '#9b7df0'], ['#0e7490', '#22a3bd'],
    ];
    [$a, $b] = $pairs[$h % count($pairs)];
    $gid = 'av' . substr(md5($name), 0, 6);
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" viewBox="0 0 160 160">
  <defs><linearGradient id="{$gid}" x1="0" y1="0" x2="1" y2="1">
    <stop offset="0" stop-color="{$a}"/><stop offset="1" stop-color="{$b}"/>
  </linearGradient></defs>
  <rect width="160" height="160" fill="url(#{$gid})"/>
  <circle cx="128" cy="34" r="46" fill="#ffffff" opacity="0.10"/>
  <text x="80" y="104" text-anchor="middle" font-family="Georgia, serif" font-size="66" font-weight="bold" fill="#ffffff" opacity="0.95">{$initial}</text>
</svg>
SVG;
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 *  Public URL for a stored avatar value — accepts any shape
 *  (full URL, "assets/uploads/…", bare file name) and returns null when
 *  the file is missing so callers can fall back to initials.
 */
function _avatar_url(string $avatar): ?string
{
    $avatar = trim($avatar);
    if ($avatar === '') return null;
    if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
        $path = (string) (parse_url($avatar, PHP_URL_PATH) ?: '');
        $root = rtrim((string) (parse_url(url('/'), PHP_URL_PATH) ?: ''), '/');
        $rel  = ($root !== '' && str_starts_with($path, $root)) ? substr($path, strlen($root)) : $path;
        $local = ROOT_PATH . '/' . ltrim($rel, '/');
        $url   = $avatar;
    } elseif (str_starts_with($avatar, 'assets/')) {
        $local = ROOT_PATH . '/' . $avatar;
        $url   = url('/' . $avatar);
    } else {
        $local = UPLOADS_DIR . '/' . ltrim($avatar, '/');
        $url   = UPLOADS_URL . '/' . ltrim($avatar, '/');
    }
    return (is_file($local) && filesize($local) > 0) ? $url : null;
}

/** <img> tag for an author (uploaded photo or gradient initials) */
function author_avatar_img(string $name, ?string $avatar, string $class = 'h-11 w-11'): string
{
    $src = !empty($avatar) ? _avatar_url($avatar) : null;
    $src ??= avatar_src($name);
    return '<img src="' . e($src) . '" alt="' . e($name) . ' — author" width="80" height="80" loading="lazy" class="' . e($class) . ' rounded-full object-cover">';
}

/** Topical line-art illustration for a category (drawn inside the cover SVG) */
function _cover_icon(string $category): string
{
    // All icons drawn on a 800x500 canvas, anchored right-of-centre
    $s = 'stroke="#ffffff" fill="none" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" opacity="0.26"';

    switch ($category) {
        case 'artificial-intelligence': // neural network
            $nodes = [[560,140],[560,250],[560,360],[680,100],[680,190],[680,280],[680,370],[790,150],[790,250],[790,350]];
            $links = [[0,3],[0,4],[1,3],[1,5],[1,6],[2,5],[2,6],[3,7],[3,8],[4,7],[4,9],[5,8],[5,9],[6,9]];
            $out = '<g ' . $s . '>';
            foreach ($links as [$a, $b]) {
                $out .= sprintf('<path d="M%d %d L%d %d"/>', $nodes[$a][0], $nodes[$a][1], $nodes[$b][0], $nodes[$b][1]);
            }
            foreach ($nodes as [$x, $y]) {
                $out .= sprintf('<circle cx="%d" cy="%d" r="11" fill="#ffffff" fill-opacity="0.10"/>', $x, $y);
            }
            return $out . '</g>';

        case 'technology': // microchip
            return '<g ' . $s . '>'
                 . '<rect x="580" y="130" width="180" height="180" rx="18"/>'
                 . '<rect x="628" y="178" width="84" height="84" rx="10"/>'
                 . '<path d="M620 130v-28M660 130v-28M700 130v-28M740 130v-28"/>'
                 . '<path d="M620 310v28M660 310v28M700 310v28M740 310v28"/>'
                 . '<path d="M580 170h-28M580 220h-28M580 270h-28"/>'
                 . '<path d="M760 170h28M760 220h28M760 270h28"/>'
                 . '</g>';

        case 'data-science': // bar chart + trend line
            return '<g ' . $s . '>'
                 . '<path d="M545 330V240M605 330V180M665 330V215M725 330V140M785 330V195"/>'
                 . '<path d="M545 330h240" stroke-width="3.2"/>'
                 . '<path d="M545 260 605 205 665 230 725 150 785 170" stroke-width="3.6"/>'
                 . '<circle cx="605" cy="205" r="7" fill="#ffffff" fill-opacity="0.18"/>'
                 . '<circle cx="725" cy="150" r="7" fill="#ffffff" fill-opacity="0.18"/>'
                 . '</g>';

        case 'programming': // code window
            return '<g ' . $s . '>'
                 . '<rect x="550" y="120" width="240" height="180" rx="16"/>'
                 . '<path d="M550 160h240"/>'
                 . '<circle cx="578" cy="140" r="5" fill="#ffffff" fill-opacity="0.2"/>'
                 . '<circle cx="600" cy="140" r="5" fill="#ffffff" fill-opacity="0.2"/>'
                 . '<circle cx="622" cy="140" r="5" fill="#ffffff" fill-opacity="0.2"/>'
                 . '<path d="M600 205l-24 22 24 22M676 205l24 22-24 22M654 195l-28 64"/>'
                 . '</g>';

        case 'research': // document + magnifier
            return '<g ' . $s . '>'
                 . '<path d="M585 110h130l45 45v170H585z"/>'
                 . '<path d="M715 110v45h45"/>'
                 . '<path d="M615 190h90M615 225h90M615 260h55"/>'
                 . '<circle cx="735" cy="290" r="52" stroke-width="4"/>'
                 . '<path d="m772 328 38 38" stroke-width="5"/>'
                 . '</g>';

        case 'student-career': // ascending steps + growth arrow
            return '<g ' . $s . '>'
                 . '<path d="M555 330h60v-55h60v-60h60v-65h60"/>'
                 . '<path d="M565 250 745 115"/>'
                 . '<path d="M705 112h42v42" stroke-width="4"/>'
                 . '</g>';

        case 'productivity': // clock + check
            return '<g ' . $s . '>'
                 . '<circle cx="670" cy="220" r="105"/>'
                 . '<path d="M670 150v72l52 30" stroke-width="4"/>'
                 . '<path d="m742 300 22 22 40-44" stroke-width="4.5"/>'
                 . '</g>';

        case 'freelancing': // laptop
            return '<g ' . $s . '>'
                 . '<rect x="565" y="120" width="230" height="150" rx="12"/>'
                 . '<path d="M530 300h300l-18 34H548z"/>'
                 . '<path d="M610 165h95M610 200h130M610 235h70"/>'
                 . '</g>';

        default: // abstract editorial marks
            return '<g ' . $s . '>'
                 . '<circle cx="680" cy="205" r="95"/>'
                 . '<circle cx="680" cy="205" r="60"/>'
                 . '<path d="M545 335 800 120M600 345 810 175"/>'
                 . '</g>';
    }
}

/**
 * Professional gradient cover with a topical illustration (inline SVG data URI).
 * $seed format: "category-slug|Post Title" — category picks the illustration.
 */
function placeholder_image(string $label, ?string $seed = null): string
{
    $label = substr($label, 0, 48);
    $seed  = $seed ?: $label;
    $h = crc32($seed);

    // Curated editorial palettes (dark base → vivid edge)
    $palettes = [
        ['#0e1220', '#1f4fd8', '#3b74f0'],  // ink → blue
        ['#141118', '#e8402a', '#f0743c'],  // ink → red-orange
        ['#0f1512', '#1d7a54', '#3fae74'],  // ink → green
        ['#15120b', '#c9a227', '#e3c04f'],  // ink → gold
        ['#12101c', '#6d4bd8', '#9b7df0'],  // ink → violet
        ['#0d1418', '#0e7490', '#22a3bd'],  // ink → teal
    ];
    [$c1, $c2, $c3] = $palettes[$h % count($palettes)];
    $angle = 25 + ($h % 4) * 20;
    $gid   = 'g' . substr(md5($seed), 0, 6);

    // Category is everything before the first '|' in the seed
    $category = str_contains($seed, '|') ? strstr($seed, '|', true) : '';
    $icon     = _cover_icon($category);

    $labelEsc = htmlspecialchars($label, ENT_QUOTES | ENT_XML1, 'UTF-8');

    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500">
  <defs>
    <linearGradient id="{$gid}a" gradientTransform="rotate({$angle})">
      <stop offset="0" stop-color="{$c1}"/>
      <stop offset="1" stop-color="{$c2}"/>
    </linearGradient>
    <radialGradient id="{$gid}b" cx="78%" cy="30%" r="80%">
      <stop offset="0" stop-color="{$c3}" stop-opacity="0.8"/>
      <stop offset="1" stop-color="{$c3}" stop-opacity="0"/>
    </radialGradient>
    <pattern id="{$gid}d" width="44" height="44" patternUnits="userSpaceOnUse">
      <circle cx="1.5" cy="1.5" r="1.5" fill="#ffffff" opacity="0.06"/>
    </pattern>
  </defs>
  <rect width="800" height="500" fill="url(#{$gid}a)"/>
  <rect width="800" height="500" fill="url(#{$gid}b)"/>
  <rect width="800" height="500" fill="url(#{$gid}d)"/>
  {$icon}
  <rect x="52" y="392" width="54" height="4" fill="#ffffff" opacity="0.9"/>
  <text x="52" y="446" font-family="Georgia, 'Times New Roman', serif" font-size="29" font-style="italic" fill="#ffffff" opacity="0.95">{$labelEsc}</text>
</svg>
SVG;

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 *  Topic photo files for a category, walking up the category tree
 *  (subcategory → parent → …) until the local library has a match.
 *  Returns absolute file paths sorted by name, or [] when nothing matches.
 */
function _topic_photo_files(string $categorySlug): array
{
    static $parentMap = null;
    $slug = $categorySlug;
    for ($hops = 0; $slug !== '' && $hops < 5; $hops++) {
        $files = glob(dirname(__DIR__) . '/assets/img/topics/' . basename($slug) . '-*.jpg') ?: [];
        if ($files) {
            sort($files);
            return $files;
        }
        if ($parentMap === null) {
            $parentMap = [];
            foreach (db()->query('SELECT c.slug AS slug, p.slug AS parent_slug
                                  FROM categories c LEFT JOIN categories p ON p.id = c.parent_id') as $row) {
                $parentMap[$row['slug']] = $row['parent_slug'];
            }
        }
        $slug = $parentMap[$slug] ?? '';
    }
    return [];
}

/** The post's own editorial diagram (assets/img/diagrams/{slug}-*.svg) — original & content-exact */
function _post_diagram(string $slug): ?string
{
    if ($slug === '') return null;
    $files = glob(dirname(__DIR__) . '/assets/img/diagrams/' . basename($slug) . '-*.svg') ?: [];
    if (!$files) return null;
    sort($files);
    return url('/assets/img/diagrams/' . basename($files[0]));
}

/** Uploaded site logo URL (settings.site_logo) — null when no logo has been set */
function site_logo_src(): ?string
{
    $logo = trim(setting('site_logo', ''));
    if ($logo === '') return null;
    if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) return $logo;
    $path = ltrim($logo, '/');
    if (!is_file(ROOT_PATH . '/' . $path)) return null;
    return url('/' . $path);
}

/** Favicon URL — the stored value may be a relative path, a full URL or empty */
function favicon_url(): string
{
    $fav = trim(setting('site_favicon', ''));
    if ($fav === '') return url('/assets/img/favicon.svg');
    if (str_starts_with($fav, 'http://') || str_starts_with($fav, 'https://')) return $fav;
    $path = ltrim($fav, '/');
    return is_file(ROOT_PATH . '/' . $path) ? url('/' . $path) : url('/assets/img/favicon.svg');
}

/** Featured image for a post: uploaded cover → topic photo → own diagram → gradient illustration */
function post_image(array $post): string
{
    if (!empty($post['cover_image'])) {
        if (str_starts_with($post['cover_image'], 'http')) {
            return $post['cover_image'];
        }
        // Relative site paths (e.g. "assets/img/diagrams/…svg") vs legacy uploads file names
        if (str_starts_with($post['cover_image'], 'assets/')) {
            return url('/' . ltrim($post['cover_image'], '/'));
        }
        return UPLOADS_URL . '/' . ltrim($post['cover_image'], '/');
    }
    // 1) real topic-related photo (local library, walks up the category tree)
    $seed = ($post['category_slug'] ?? '') . '|' . $post['title'];
    $photo = topic_image($post['category_slug'] ?? null, $seed);
    if ($photo) return $photo;

    // 2) the post's own editorial diagram — original, drawn for this exact article
    $diagram = _post_diagram($post['slug'] ?? '');
    if ($diagram) return $diagram;

    // 3) fallback: topical gradient illustration
    return placeholder_image($post['title'], $seed);
}

/**
 *  Topic photo from the local library (assets/img/topics/{category}-N.jpg).
 *  Falls back through the category ancestry so subcategory posts still get a real photo.
 *  Deterministic pick from the seed → same post always gets same photo.
 */
function topic_image(?string $categorySlug, string $seed): ?string
{
    if (!$categorySlug) return null;
    $files = _topic_photo_files($categorySlug);
    if (!$files) return null;
    $pick = basename($files[crc32($seed) % count($files)]);
    return url('/assets/img/topics/' . $pick);
}

/** First available topic photo for a category (used on category cards) — walks ancestry too */
function category_cover_image(string $categorySlug): ?string
{
    $files = _topic_photo_files($categorySlug);
    if (!$files) return null;
    return url('/assets/img/topics/' . basename($files[0]));
}


/* ---------------------------------------------------------------------
 *  Analytics — accurate, privacy-friendly traffic tracking
 *
 *  Accuracy rules:
 *   - bot/crawler user-agents and browser prefetch/prerender requests
 *     are never counted
 *   - logged-in admin sessions are excluded (no self-traffic)
 *   - the same reader reopening the same article within 30 minutes
 *     counts once (refresh dedupe)
 *   - uniques = distinct anonymised visitor hash per day; raw IPs are
 *     never stored — sha256(ip + ua + HASH_SALT)
 *   - day buckets use ANALYTICS_TZ (not UTC) so late-night traffic
 *     lands on the correct local date
 *   - dashboards read closed days from the daily_stats rollup; no live
 *     full-table COUNT(*) on every page view
 * --------------------------------------------------------------------- */

/** Today's date in the analytics timezone (site-local, not UTC). */
function analytics_today(): string
{
    return analytics_now()->format('Y-m-d');
}

/** Current DateTimeImmutable in the analytics timezone. */
function analytics_now(): DateTimeImmutable
{
    static $tz = null;
    if ($tz === null) {
        $tz = new DateTimeZone(ANALYTICS_TZ);
    }
    return new DateTimeImmutable('now', $tz);
}

/** Known crawler/bot/preview signatures. */
function is_bot_ua(string $ua): bool
{
    return (bool) preg_match(
        '~bot|crawl|spider|slurp|bingpreview|curl|wget|python|scrapy|httpie|libwww|'
        . 'headless|phantomjs|selenium|playwright|lighthouse|pingdom|uptimerobot|'
        . 'statuscake|feedfetcher|facebookexternalhit|whatsapp|telegrambot|slackbot|'
        . 'discordbot|embedly|quora link preview|outbrain|vkshare|archive|ia_archiver|'
        . 'semantic|gptbot|claudebot|oai-|ccbot|perplexitybot|amazonbot|applebot|'
        . 'yandex|baidu|sogou|bytespider|petalbot|chrome-lighthouse|gtmetrix|pingdom~i',
        $ua
    );
}

/** Coarse device class from the user-agent. */
function analytics_device(string $ua): string
{
    if (preg_match('~iPad|Tablet|Kindle|Silk|PlayBook~i', $ua)) {
        return 'tablet';
    }
    if (preg_match('~Android~i', $ua) && !preg_match('~Mobile~i', $ua)) {
        return 'tablet';
    }
    if (preg_match('~Mobi|iPhone|iPod|Windows Phone|BlackBerry|BB10|Opera Mini|IEMobile~i', $ua)) {
        return 'mobile';
    }
    return 'desktop';
}

/** External referrer host (same-site and invalid referrers → null = direct). */
function analytics_referrer_host(): ?string
{
    $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    if ($ref === '' || !preg_match('#^https?://#i', $ref)) {
        return null;
    }
    $host = mb_strtolower((string) parse_url($ref, PHP_URL_HOST));
    if ($host === '') {
        return null;
    }
    $host = preg_replace('/^www\./', '', $host);
    $self = mb_strtolower(preg_replace('/^www\./', '', (string) parse_url(SITE_URL, PHP_URL_HOST)));
    if ($host === $self || str_ends_with($host, 'localhost')) {
        return null;
    }
    return mb_substr($host, 0, 120);
}

/**
 * Record one accurate article view: log a page_views row (when it passes
 * every accuracy gate) and bump the lifetime counter in lockstep so
 * posts.views only ever reflects real, human, deduped reads.
 */
function track_post_view(int $postId): void
{
    if ($postId <= 0) {
        return;
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        return;
    }

    // Browser prefetch/prerender — not a real reader
    $purpose = ($_SERVER['HTTP_SEC_PURPOSE'] ?? '') . ' ' . ($_SERVER['HTTP_PURPOSE'] ?? '');
    if (preg_match('~prefetch|prerender~i', $purpose)) {
        return;
    }

    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '' || is_bot_ua($ua)) {
        return;
    }

    // Never count the admin's own sessions
    if (!empty($_SESSION['admin_id']) || !empty($_SESSION['user']['id'])) {
        return;
    }

    $ip   = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $hash = substr(hash('sha256', $ip . '|' . $ua . '|' . HASH_SALT), 0, 32);

    $pdo = db();

    // 30-minute refresh window: same reader + same article = one view
    $since = analytics_now()->modify('-30 minutes')->format('Y-m-d H:i:s');
    $dup   = $pdo->prepare(
        'SELECT 1 FROM page_views WHERE post_id = ? AND visitor_hash = ? AND viewed_at >= ? LIMIT 1'
    );
    $dup->execute([$postId, $hash, $since]);
    if ($dup->fetchColumn()) {
        return; // refresh / duplicate within the session window
    }

    $now = analytics_now();
    $pdo->prepare(
        'INSERT INTO page_views (post_id, visitor_hash, device, referrer_host, viewed_at, stat_date)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([
        $postId,
        $hash,
        analytics_device($ua),
        analytics_referrer_host(),
        $now->format('Y-m-d H:i:s'),
        $now->format('Y-m-d'),
    ]);

    bump_views($postId); // lifetime counter stays in sync with logged views
}

/** Persist a single setting (used by internal systems like the rollup watermark). */
function set_setting(string $key, string $value): void
{
    db()->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    )->execute([$key, $value]);
}

/**
 * Fold closed days (before today) from page_views into daily_stats.
 * Runs lazily from the dashboard, driven by a settings watermark so
 * each load only processes the days since the last rollup.
 */
function analytics_rollup(): void
{
    try {
        $pdo   = db();
        $today = analytics_today();

        $last = setting('analytics_last_rollup', '1970-01-01');
        if ($last >= $today) {
            return;
        }

        $stmt = $pdo->prepare(
            'SELECT stat_date, COUNT(*) AS pv, COUNT(DISTINCT visitor_hash) AS uv
             FROM page_views
             WHERE stat_date > ? AND stat_date < ?
             GROUP BY stat_date'
        );
        $stmt->execute([$last, $today]);

        $upsert = $pdo->prepare(
            'INSERT INTO daily_stats (stat_date, pageviews, uniques) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE pageviews = VALUES(pageviews), uniques = VALUES(uniques)'
        );
        foreach ($stmt->fetchAll() as $r) {
            $upsert->execute([$r['stat_date'], (int) $r['pv'], (int) $r['uv']]);
        }

        // Watermark = yesterday: every closed day is settled, including
        // zero-view days (absent rows simply chart as zero).
        set_setting('analytics_last_rollup', analytics_now()->modify('-1 day')->format('Y-m-d'));
    } catch (PDOException $e) {
        // Tracking must never break page rendering
    }
}

/**
 * All dashboard analytics, assembled server-side for the admin dashboard.
 * Returns zero-filled day arrays (current + previous period), KPI deltas,
 * top articles, category reads, comment health and publishing cadence.
 */
function analytics_dashboard_data(int $days = 30): array
{
    analytics_rollup();

    $pdo    = db();
    $today  = analytics_today();
    $curFrom  = analytics_now()->modify('-' . ($days - 1) . ' days')->format('Y-m-d');
    $prevFrom = analytics_now()->modify('-' . (2 * $days - 1) . ' days')->format('Y-m-d');
    $prevTo   = analytics_now()->modify('-' . $days . ' days')->format('Y-m-d');

    // Closed days for both periods come from the rollup table
    $stmt = $pdo->prepare(
        'SELECT stat_date, pageviews, uniques FROM daily_stats
         WHERE stat_date BETWEEN ? AND ? ORDER BY stat_date'
    );
    $stmt->execute([$prevFrom, $prevTo]);
    $prevBy = array_column($stmt->fetchAll(), null, 'stat_date');
    $stmt->execute([$curFrom, $today]); // current period (closed days; today is absent here by design)
    $curBy = [];
    foreach ($stmt->fetchAll() as $r) {
        if ($r['stat_date'] >= $curFrom) {
            $curBy[$r['stat_date']] = $r;
        }
    }

    // Today is always live (small index-range query, never a full scan)
    $live = $pdo->prepare(
        'SELECT COUNT(*) AS pv, COUNT(DISTINCT visitor_hash) AS uv FROM page_views WHERE stat_date = ?'
    );
    $live->execute([$today]);
    $liveRow  = $live->fetch() ?: ['pv' => 0, 'uv' => 0];
    $todayPv  = (int) $liveRow['pv'];
    $todayUv  = (int) $liveRow['uv'];

    // Zero-filled label/value series for the charts
    $labels = [];
    $pvSeries = $uvSeries = $prevSeries = [];
    $pvTotal = $uvTotal = $prevPvTotal = 0;
    for ($i = $days - 1; $i >= 0; $i--) {
        $d       = analytics_now()->modify("-{$i} days")->format('Y-m-d');
        $labels[] = date('M j', strtotime($d));
        if ($d === $today) {
            $pvSeries[] = $todayPv;
            $uvSeries[] = $todayUv;
            $pvTotal   += $todayPv;
            continue;
        }
        $pv = (int) ($curBy[$d]['pageviews'] ?? 0);
        $uv = (int) ($curBy[$d]['uniques'] ?? 0);
        $pvSeries[] = $pv;
        $uvSeries[] = $uv;
        $pvTotal   += $pv;
        $uvTotal   += $uv;
    }
    foreach ($prevBy as $r) {
        $prevPvTotal += (int) $r['pageviews'];
    }
    // Previous-period series stays aligned by day index (dashed comparison line)
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = analytics_now()->modify("-{$i} days")->format('Y-m-d');
        $prevSeries[] = (int) ($prevBy[$d]['pageviews'] ?? 0);
    }

    // True period uniques: one visitor on N days counts once per period
    $uvStmt = $pdo->prepare(
        'SELECT COUNT(DISTINCT visitor_hash) FROM page_views WHERE stat_date BETWEEN ? AND ?'
    );
    $uvStmt->execute([$curFrom, $today]);
    $uv30 = (int) $uvStmt->fetchColumn();
    $uvStmt->execute([$prevFrom, $prevTo]);
    $uvPrev = (int) $uvStmt->fetchColumn();

    $delta = static function (int $cur, int $prev): ?float {
        if ($prev <= 0) {
            return null;
        }
        return round(($cur - $prev) / $prev * 100, 1);
    };

    // Top articles in the current period
    $top = $pdo->prepare(
        "SELECT p.title, p.slug, COUNT(*) AS views
         FROM page_views pv JOIN posts p ON p.id = pv.post_id
         WHERE pv.stat_date BETWEEN ? AND ?
         GROUP BY p.id, p.title, p.slug
         ORDER BY views DESC, p.title ASC LIMIT 8"
    );
    $top->execute([$curFrom, $today]);

    // Reads per category in the current period
    $cat = $pdo->prepare(
        "SELECT COALESCE(c.name, 'Uncategorized') AS name, COUNT(*) AS views
         FROM page_views pv
         JOIN posts p ON p.id = pv.post_id
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE pv.stat_date BETWEEN ? AND ?
         GROUP BY c.id, c.name
         ORDER BY views DESC, c.name ASC LIMIT 8"
    );
    $cat->execute([$curFrom, $today]);

    // Comment moderation health
    $comments = ['approved' => 0, 'pending' => 0, 'spam' => 0, 'trash' => 0];
    foreach ($pdo->query('SELECT status, COUNT(*) AS c FROM comments GROUP BY status') as $r) {
        $key = (string) $r['status'];
        if (isset($comments[$key])) {
            $comments[$key] = (int) $r['c'];
        }
    }

    // Publishing cadence — last 12 calendar months (zero-filled)
    $months = [];
    $counts = [];
    $mFrom  = analytics_now()->modify('first day of this month')->modify('-11 months');
    $stmt   = $pdo->prepare(
        "SELECT DATE_FORMAT(published_at, '%Y-%m') AS m, COUNT(*) AS c
         FROM posts WHERE published_at IS NOT NULL AND published_at >= ?
         GROUP BY m"
    );
    $stmt->execute([$mFrom->format('Y-m-d 00:00:00')]);
    $monthCounts = array_column($stmt->fetchAll(), null, 'm');
    for ($i = 0; $i < 12; $i++) {
        $m     = $mFrom->modify("+{$i} months");
        $months[] = $m->format('M y');
        $counts[] = (int) ($monthCounts[$m->format('Y-m')] ?? 0);
    }

    return [
        'days'       => $days,
        'tz'         => ANALYTICS_TZ,
        'range'      => ['from' => $curFrom, 'to' => $today],
        'labels'     => $labels,
        'pageviews'  => $pvSeries,
        'visitors'   => $uvSeries,
        'prev'       => $prevSeries,
        'today'      => ['pageviews' => $todayPv, 'visitors' => $todayUv],
        'totals'     => [
            'pageviews'  => $pvTotal,
            'visitors'   => $uv30,
            'prev_pv'    => $prevPvTotal,
            'prev_uv'    => $uvPrev,
        ],
        'delta'      => [
            'pageviews' => $delta($pvTotal, $prevPvTotal),
            'visitors'  => $delta($uv30, $uvPrev),
        ],
        'top_posts'  => array_map(
            static fn(array $r): array => ['title' => $r['title'], 'slug' => $r['slug'], 'views' => (int) $r['views']],
            $top->fetchAll()
        ),
        'categories' => array_map(
            static fn(array $r): array => ['name' => $r['name'], 'views' => (int) $r['views']],
            $cat->fetchAll()
        ),
        'comments'   => $comments,
        'months'     => ['labels' => $months, 'counts' => $counts],
    ];
}
