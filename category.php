<?php
/**
 *  Category detail  —  /category/{slug}
 */

require_once __DIR__ . '/includes/functions.php';

$slug     = $_GET['slug'] ?? '';
$category = $slug !== '' ? get_category_by_slug($slug) : null;

if (!$category) {
    http_response_code(404);
    $page_title = 'Category Not Found | ' . setting('site_name', SITE_NAME);
    require __DIR__ . '/includes/header.php';
    echo '<section class="mx-auto max-w-3xl px-6 py-28 text-center">
            <p class="kicker text-accent">404</p>
            <h1 class="headline-xl mt-3">Category not found</h1>
            <a href="' . url('/blog') . '" class="mt-8 inline-block rounded-lg bg-ink px-6 py-3 text-sm font-bold text-paper hover:bg-accent">Browse all articles</a>
          </section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$perPage  = (int) setting('posts_per_page', '9');
$reqPage  = max(1, (int) ($_GET['page'] ?? 1));

// Parent archives include their subcategories' posts (self + descendants)
$catIds   = category_descendant_ids((int) $category['id']);
$children = db()->prepare('SELECT name, slug FROM categories WHERE parent_id = ? ORDER BY sort_order, name');
$children->execute([(int) $category['id']]);
$children = $children->fetchAll();

$total    = count_posts($catIds);
$pages    = max(1, (int) ceil($total / $perPage));
if ($reqPage > $pages) {
    abort_404();
}
$page     = $reqPage;
$posts    = get_posts($page, $perPage, $catIds);

if ($total === 0) {
    seo_noindex();          // empty archive — keep out of the index
}

$page_title = ($category['meta_title'] ?: ($category['name'] . ' — Category')) . ' | ' . setting('site_name', SITE_NAME);
$page_desc  = $category['meta_desc'] ?: ($category['description'] ?: ('Read the latest articles in ' . $category['name']));

// Breadcrumb chain: Home > Categories > [Parent…] > Category
$crumbs = ['Home' => '/', 'Categories' => '/categories'];
$_catIds = array_reverse(category_ancestry((int) $category['id']));
$_catMap = [];
if ($_catIds) {
    $_ph = implode(',', array_fill(0, count($_catIds), '?'));
    $_st = db()->prepare("SELECT id, name, slug FROM categories WHERE id IN ($_ph)");
    $_st->execute($_catIds);
    foreach ($_st->fetchAll() as $_r) {
        $_catMap[(int) $_r['id']] = $_r;
    }
}
foreach ($_catIds as $_cid) {
    if (isset($_catMap[$_cid])) {
        $crumbs[$_catMap[$_cid]['name']] = '/category/' . $_catMap[$_cid]['slug'];
    }
}

seo_jsonld_breadcrumb($crumbs);
seo_jsonld_add([
    '@context' => 'https://schema.org',
    '@type'    => 'CollectionPage',
    'name'     => $category['name'] . ' — ' . setting('site_name', SITE_NAME),
    'description' => $page_desc,
    'url'      => SITE_URL . '/category/' . $category['slug'],
    'numberOfItems' => $total,
]);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/breadcrumb.php';   // renders the $crumbs chain built above
?>

<section class="border-b border-line bg-surface">
  <div class="mx-auto max-w-[96rem] px-6 py-14">
    <h1 class="headline-xl"><?= e($category['name']) ?></h1>
    <?php if ($category['description']): ?>
      <p class="mt-4 max-w-2xl text-muted"><?= e($category['description']) ?></p>
    <?php endif; ?>
    <p class="mt-5 text-sm font-semibold text-primary"><?= $total ?> article<?= $total !== 1 ? 's' : '' ?></p>
    <?php if ($children): ?>
      <div class="mt-5 flex flex-wrap items-center gap-2">
        <span class="kicker text-muted">Subcategories</span>
        <?php foreach ($children as $ch): ?>
          <a href="<?= url('/category/' . $ch['slug']) ?>"
             class="rounded-full border border-line bg-paper px-4 py-1.5 text-xs font-bold text-ink-soft transition hover:border-primary hover:text-primary">
            <?= e($ch['name']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="mx-auto max-w-[96rem] px-6 py-14">
  <?php if (!$posts): ?>
    <div class="rounded-2xl border border-dashed border-line bg-surface py-20 text-center">
      <p class="font-display text-2xl text-muted">No articles in this category yet.</p>
      <a href="<?= url('/blog') ?>" class="mt-3 inline-block text-sm font-semibold text-primary">Browse everything &rarr;</a>
    </div>
  <?php else: ?>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <?php foreach ($posts as $i => $post): ?>
        <?php require __DIR__ . '/includes/post-card.php'; ?>
      <?php endforeach; ?>
    </div>

    <?php
    render_pagination($page, $pages, fn(int $p) => url('/category/' . $category['slug'] . '?page=' . $p));
    ?>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
