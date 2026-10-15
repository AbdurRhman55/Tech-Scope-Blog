<?php
/**
 *  Author profile  —  /author/{slug}
 */

require_once __DIR__ . '/includes/functions.php';

$slug   = $_GET['slug'] ?? '';
$author = $slug !== '' ? get_author_by_slug($slug) : null;

if (!$author) {
    http_response_code(404);
    $page_title = 'Author Not Found | ' . setting('site_name', SITE_NAME);
    require __DIR__ . '/includes/header.php';
    echo '<section class="mx-auto max-w-3xl px-6 py-28 text-center">
            <p class="kicker text-accent">404</p>
            <h1 class="headline-xl mt-3">Author not found</h1>
            <a href="' . url('/blog') . '" class="mt-8 inline-block rounded-lg bg-ink px-6 py-3 text-sm font-bold text-paper hover:bg-accent">Browse all articles</a>
          </section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$perPage = (int) setting('posts_per_page', '9');
$reqPage = max(1, (int) ($_GET['page'] ?? 1));
$total   = count_posts(null, null, (int) $author['id']);
$pages   = max(1, (int) ceil($total / $perPage));
if ($reqPage > $pages) {
    abort_404();
}
$page    = $reqPage;
$posts   = get_posts($page, $perPage, null, null, (int) $author['id']);

if ($total === 0) {
    seo_noindex();          // empty archive — keep out of the index
}

$page_title = $author['name'] . ' — Author | ' . setting('site_name', SITE_NAME);
$page_desc  = $author['bio'] ?: ('Articles written by ' . $author['name'] . '.');

seo_jsonld_breadcrumb([
    'Home'                 => '/',
    'Blog'                 => '/blog',
    $author['name']        => '/author/' . $author['slug'],
]);
seo_jsonld_add([
    '@context' => 'https://schema.org',
    '@type'    => 'ProfilePage',
    'name'     => $author['name'] . ' — Author | ' . setting('site_name', SITE_NAME),
    'description' => $page_desc,
    'url'      => SITE_URL . '/author/' . $author['slug'],
    'mainEntity' => [
        '@type' => 'Person',
        'name'  => $author['name'],
        'url'   => SITE_URL . '/author/' . $author['slug'],
        'description' => $author['bio'] ?? '',
        'jobTitle'    => 'Author',
    ],
]);

require __DIR__ . '/includes/header.php';
$crumbs = [
    'Home'          => '/',
    'Blog'          => '/blog',
    $author['name'] => '/author/' . $author['slug'],
];
require __DIR__ . '/includes/breadcrumb.php';
?>

<section class="border-b border-line bg-surface">
  <div class="mx-auto flex max-w-4xl flex-col items-start gap-7 px-6 py-14 sm:flex-row sm:items-center">
    <?= author_avatar_img($author['name'], $author['avatar'] ?? null, 'h-24 w-24 ring-4 ring-paper') ?>

    <div>
      <p class="kicker text-accent">Author</p>
      <h1 class="headline-lg mt-2"><?= e($author['name']) ?></h1>
      <?php if ($author['bio']): ?>
        <p class="mt-3 max-w-xl text-muted"><?= e($author['bio']) ?></p>
      <?php endif; ?>
      <div class="mt-4 flex flex-wrap items-center gap-4 text-sm">
        <span class="font-semibold text-primary"><?= $total ?> article<?= $total !== 1 ? 's' : '' ?></span>
        <?php if ($author['website']): ?>
          <a href="<?= e($author['website']) ?>" target="_blank" rel="noopener" class="font-semibold text-ink transition hover:text-accent">Website &rarr;</a>
        <?php endif; ?>
        <?php if ($author['twitter']): ?>
          <a href="https://twitter.com/<?= e(ltrim($author['twitter'], '@')) ?>" target="_blank" rel="noopener" class="font-semibold text-ink transition hover:text-accent">Twitter &rarr;</a>
        <?php endif; ?>
        <?php if ($author['github']): ?>
          <a href="https://github.com/<?= e(ltrim($author['github'], '@')) ?>" target="_blank" rel="noopener" class="font-semibold text-ink transition hover:text-accent">GitHub &rarr;</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="mx-auto max-w-[96rem] px-6 py-14">
  <h2 class="headline-lg border-b border-line pb-4">Articles by <?= e($author['name']) ?></h2>

  <?php if (!$posts): ?>
    <div class="mt-10 rounded-2xl border border-dashed border-line bg-surface py-16 text-center">
      <p class="font-display text-xl text-muted">No published articles yet.</p>
    </div>
  <?php else: ?>
    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <?php foreach ($posts as $i => $post): ?>
        <?php require __DIR__ . '/includes/post-card.php'; ?>
      <?php endforeach; ?>
    </div>

    <?php
    render_pagination($page, $pages, fn(int $p) => url('/author/' . $author['slug'] . '?page=' . $p));
    ?>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
