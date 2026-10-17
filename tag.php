<?php
/**
 *  Tag archive  —  /tag/{slug}
 */

require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';

// Resolve tag
$stmt = db()->prepare('SELECT * FROM tags WHERE slug = ? LIMIT 1');
$stmt->execute([$slug]);
$tag = $stmt->fetch() ?: null;

if (!$tag) {
    http_response_code(404);
    $page_title = 'Tag Not Found | ' . setting('site_name', SITE_NAME);
    require __DIR__ . '/includes/header.php';
    echo '<section class="mx-auto max-w-3xl px-6 py-28 text-center">
            <p class="kicker text-accent">404</p>
            <h1 class="headline-xl mt-3">Tag not found</h1>
            <a href="' . url('/blog') . '" class="mt-8 inline-block rounded-lg bg-ink px-6 py-3 text-sm font-bold text-paper hover:bg-accent">Browse all articles</a>
          </section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$perPage = (int) setting('posts_per_page', '9');
$reqPage = max(1, (int) ($_GET['page'] ?? 1));

// Count tagged posts
$countStmt = db()->prepare(
    "SELECT COUNT(*) FROM posts p
     JOIN post_tags pt ON pt.post_id = p.id
     WHERE pt.tag_id = ? AND p.status = 'published'"
);
$countStmt->execute([(int) $tag['id']]);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
if ($reqPage > $pages) {
    abort_404();
}
$page  = $reqPage;
$offset = ($page - 1) * $perPage;

if ($total === 0) {
    seo_noindex();          // empty archive — keep out of the index
}

// Fetch posts
$stmt = db()->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
            u.name AS author_name, u.slug AS author_slug
     FROM posts p
     LEFT JOIN categories c ON c.id = p.category_id
     JOIN users u ON u.id = p.author_id
     JOIN post_tags pt ON pt.post_id = p.id
     WHERE pt.tag_id = ? AND p.status = 'published'
     ORDER BY p.published_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute([(int) $tag['id']]);
$posts = $stmt->fetchAll();

$page_title = '#' . $tag['name'] . ' — Tag | ' . setting('site_name', SITE_NAME);
$page_desc  = 'Browse articles tagged "' . $tag['name'] . '" on ' . setting('site_name', SITE_NAME)
    . ' — practical guides, tutorials and expert insights on AI, technology, programming and more.';

seo_jsonld_breadcrumb([
    'Home'      => '/',
    'Blog'      => '/blog',
    '#' . $tag['name'] => '/tag/' . $tag['slug'],
]);
seo_jsonld_add([
    '@context' => 'https://schema.org',
    '@type'    => 'CollectionPage',
    'name'     => '#' . $tag['name'] . ' — ' . setting('site_name', SITE_NAME),
    'description' => $page_desc,
    'url'      => SITE_URL . '/tag/' . $tag['slug'],
    'numberOfItems' => $total,
]);

require __DIR__ . '/includes/header.php';
$crumbs = [
    'Home'          => '/',
    'Blog'          => '/blog',
    '#' . $tag['name'] => '/tag/' . $tag['slug'],
];
require __DIR__ . '/includes/breadcrumb.php';
?>

<section class="border-b border-line bg-surface">
  <div class="mx-auto max-w-[96rem] px-6 py-14">
    <h1 class="headline-xl">#<?= e($tag['name']) ?></h1>
    <p class="mt-4 text-sm font-semibold text-primary"><?= $total ?> article<?= $total !== 1 ? 's' : '' ?></p>
  </div>
</section>

<section class="mx-auto max-w-[96rem] px-6 py-14">
  <?php if (!$posts): ?>
    <div class="rounded-2xl border border-dashed border-line bg-surface py-20 text-center">
      <p class="font-display text-2xl text-muted">No articles with this tag yet.</p>
      <a href="<?= url('/blog') ?>" class="mt-3 inline-block text-sm font-semibold text-primary">Browse everything &rarr;</a>
    </div>
  <?php else: ?>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <?php foreach ($posts as $i => $post): ?>
        <?php require __DIR__ . '/includes/post-card.php'; ?>
      <?php endforeach; ?>
    </div>

    <?php
    render_pagination($page, $pages, fn(int $p) => url('/tag/' . $tag['slug'] . '?page=' . $p));
    ?>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
