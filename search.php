<?php
/**
 *  Search results  —  /search?q=…
 */

require_once __DIR__ . '/includes/functions.php';

/* ---------------------------------------------------------------------
 *  Live suggestions endpoint — /search?q=…&ajax=1
 *  Returns a small JSON payload for the navbar search dropdown.
 * --------------------------------------------------------------------- */
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');

    $q = trim((string) ($_GET['q'] ?? ''));
    $out = ['results' => [], 'total' => 0];

    if ($q !== '') {
        $total = count_posts(null, $q);
        foreach (get_posts(1, 6, null, $q) as $row) {
            $out['results'][] = [
                'title'    => $row['title'],
                'url'      => url('/post/' . $row['slug']),
                'category' => (string) ($row['category_name'] ?? ''),
                'cat_url'  => $row['category_slug'] ? url('/category/' . $row['category_slug']) : '',
                'date'     => time_ago($row['published_at']),
            ];
        }
        $out['total']    = $total;
        $out['more_url'] = url('/search?q=' . rawurlencode($q));
    }

    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$q       = trim((string) ($_GET['q'] ?? ''));
$perPage = 10;
$page    = max(1, (int) ($_GET['page'] ?? 1));
$total   = 0;
$posts   = [];

if ($q !== '') {
    $total = count_posts(null, $q);
    $pages = max(1, (int) ceil($total / $perPage));
    if ($page > $pages) {
        abort_404();
    }
    $posts = get_posts($page, $perPage, null, $q);
} else {
    $pages = 1;
}

$page_title = ($q !== '' ? 'Search: ' . $q : 'Search') . ' | ' . setting('site_name', SITE_NAME);
$page_desc  = $q !== '' ? 'Search results for "' . $q . '"' : 'Search the article library.';
seo_noindex();                  // internal search results are never indexable
require __DIR__ . '/includes/header.php';
?>

<section class="border-b border-line bg-surface">
  <div class="mx-auto max-w-[96rem] px-6 py-14">
    <p class="kicker text-accent">Search</p>
    <h1 class="headline-xl mt-3"><?= $q !== '' ? 'Results for &ldquo;' . e($q) . '&rdquo;' : 'Search the library' ?></h1>

    <form method="get" action="<?= url('/search') ?>" class="mt-7">
      <div class="relative">
        <input type="search" name="q" value="<?= e($q) ?>" autofocus placeholder="Try “machine learning”, “career”, “PHP”…"
               class="w-full rounded-xl border border-line bg-paper py-4 pl-5 pr-36 text-base outline-none transition focus:border-primary">
        <button class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg bg-ink px-5 py-2.5 text-sm font-bold text-paper transition hover:bg-accent">
          Search
        </button>
      </div>
    </form>

    <?php if ($q !== ''): ?>
      <p class="mt-4 text-sm text-muted"><?= $total ?> result<?= $total !== 1 ? 's' : '' ?> found</p>
    <?php endif; ?>
  </div>
</section>

<section class="mx-auto max-w-[96rem] px-6 py-12">
  <?php if ($q === ''): ?>
    <div class="rounded-2xl border border-dashed border-line bg-surface py-16 text-center text-muted">
      Type a keyword above to search articles, guides and tutorials.
    </div>
  <?php elseif (!$posts): ?>
    <div class="rounded-2xl border border-dashed border-line bg-surface py-16 text-center">
      <p class="font-display text-2xl text-muted">No results for &ldquo;<?= e($q) ?>&rdquo;</p>
      <p class="mt-2 text-sm text-muted">Check spelling or try a broader keyword.</p>
      <div class="mt-6 flex flex-wrap justify-center gap-2">
        <?php foreach (get_popular_tags(6) as $tag): ?>
          <a href="<?= url('/tag/' . $tag['slug']) ?>" class="rounded-md border border-line bg-paper px-3 py-1.5 text-sm font-medium transition hover:border-accent hover:text-accent">
            #<?= e($tag['name']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php else: ?>
    <ul class="divide-y divide-line">
      <?php foreach ($posts as $post): ?>
        <li class="py-7">
          <div class="flex gap-5">
            <a href="<?= url('/post/' . $post['slug']) ?>" class="hidden w-44 shrink-0 overflow-hidden rounded-lg border border-line sm:block">
              <img src="<?= e(post_image($post)) ?>" alt="<?= e($post['title']) ?>" loading="lazy" class="h-28 w-full object-cover">
            </a>
            <div>
              <?php if ($post['category_name']): ?>
                <a href="<?= url('/category/' . $post['category_slug']) ?>" class="kicker text-primary transition hover:text-accent">
                  <?= e($post['category_name']) ?>
                </a>
              <?php endif; ?>
              <h2 class="mt-1.5 font-display text-xl font-semibold leading-snug">
                <a href="<?= url('/post/' . $post['slug']) ?>" class="transition hover:text-primary"><?= e($post['title']) ?></a>
              </h2>
              <p class="mt-1.5 text-sm leading-relaxed text-muted">
                <?= e($post['excerpt'] ?: excerpt_text($post['content'], 150)) ?>
              </p>
              <p class="mt-2.5 text-xs text-muted">
                <?= e($post['author_name']) ?> · <?= e(time_ago($post['published_at'])) ?> · <?= reading_time($post['content']) ?> min read
              </p>
            </div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php
    render_pagination($page, $pages, fn(int $p) => url('/search?q=' . urlencode($q) . '&page=' . $p));
    ?>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
