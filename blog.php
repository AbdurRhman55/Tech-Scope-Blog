<?php
/**
 *  Blog listing (paginated)
 */

require_once __DIR__ . '/includes/functions.php';

$perPage   = (int) setting('posts_per_page', '9');
$reqPage   = max(1, (int) ($_GET['page'] ?? 1));
$total     = count_posts();
$pages     = max(1, (int) ceil($total / $perPage));
if ($reqPage > $pages) {
    abort_404();                    // /blog?page=99 must 404, not duplicate page 1
}
$page      = $reqPage;
$posts     = get_posts($page, $perPage);

$page_title = 'Blog — All Articles | ' . setting('site_name', SITE_NAME);
$page_desc  = 'Browse all published articles on AI, technology, data science, programming and career guidance — fresh tutorials, in-depth guides and practical insights.';

seo_jsonld_breadcrumb(['Home' => '/', 'All Articles' => '/blog']);
seo_jsonld_add([
    '@context' => 'https://schema.org',
    '@type'    => 'Blog',
    'name'     => 'Articles — ' . setting('site_name', SITE_NAME),
    'description' => $page_desc,
    'url'      => SITE_URL . '/blog',
]);

require __DIR__ . '/includes/header.php';
$crumbs = ['Home' => '/', 'All Articles' => '/blog'];
require __DIR__ . '/includes/breadcrumb.php';

$allCategories = get_categories();
?>

<section class="relative overflow-hidden border-b border-line bg-surface">
  <div class="mx-auto max-w-[96rem] px-6 py-14">
    <div class="flex flex-wrap items-end justify-between gap-6">
      <div>
        <p class="kicker text-accent reveal reveal-1">Library</p>
        <h1 class="headline-xl mt-3 reveal reveal-2">All Articles</h1>
        <p class="mt-4 max-w-2xl text-muted reveal reveal-3">
          <?= $total ?> article<?= $total !== 1 ? 's' : '' ?> and counting — filter by topic, search, or just explore.
        </p>
      </div>
      <p class="font-display text-6xl font-bold leading-none text-line reveal reveal-4 sm:text-7xl"><?= str_pad((string) $total, 2, '0', STR_PAD_LEFT) ?></p>
    </div>

    <!-- category quick-filter -->
    <div class="scroll-x mt-8 -mb-1 overflow-x-auto">
      <div class="flex min-w-max items-center gap-2 pb-1">
        <a href="<?= url('/blog') ?>"
           class="rounded-full border border-ink bg-ink px-4 py-1.5 text-sm font-bold text-paper transition">
          All
        </a>
        <?php foreach ($allCategories as $cat): ?>
          <a href="<?= url('/category/' . $cat['slug']) ?>"
             class="rounded-full border border-line bg-paper px-4 py-1.5 text-sm font-bold text-ink-soft transition hover:border-ink hover:bg-ink hover:text-paper">
            <?= e($cat['name']) ?>
            <span class="ml-1 text-xs opacity-60"><?= (int) $cat['post_count'] ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="mx-auto max-w-[96rem] px-6 py-14">
  <?php if (!$posts): ?>
    <div class="rounded-2xl border border-dashed border-line bg-surface py-20 text-center">
      <p class="font-display text-2xl text-muted">Nothing here yet.</p>
      <a href="<?= url('/') ?>" class="mt-3 inline-block text-sm font-semibold text-primary">Back to home &rarr;</a>
    </div>
  <?php else: ?>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <?php foreach ($posts as $i => $post): ?>
        <?php require __DIR__ . '/includes/post-card.php'; ?>
      <?php endforeach; ?>
    </div>

    <?php
    render_pagination($page, $pages, fn(int $p) => url('/blog?page=' . $p));
    ?>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
