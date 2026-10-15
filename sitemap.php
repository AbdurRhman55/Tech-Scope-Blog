<?php
/**
 *  HTML Sitemap
 */

require_once __DIR__ . '/includes/functions.php';

$categories = get_categories();
$recent     = get_posts(1, 50);

$staticPages = [
    'Home'             => '/',
    'All Articles'     => '/blog',
    'Categories'       => '/categories',
    'About Us'         => '/about',
    'Contact Us'       => '/contact',
    'Privacy Policy'   => '/privacy',
    'Terms & Conditions' => '/terms',
    'Disclaimer'       => '/disclaimer',
];

$page_title = 'Sitemap | ' . setting('site_name', SITE_NAME);
$page_desc  = 'Complete index of all pages and articles on ' . setting('site_name', SITE_NAME) . '.';
require __DIR__ . '/includes/header.php';
?>

<section class="border-b border-line bg-surface">
  <div class="mx-auto max-w-[96rem] px-6 py-14">
    <nav class="kicker text-muted">
      <a href="<?= url('/') ?>" class="transition hover:text-accent">Home</a>
      <span class="mx-2">/</span>
      <span class="text-accent">Sitemap</span>
    </nav>
    <h1 class="headline-xl mt-4">Sitemap</h1>
    <p class="mt-4 text-muted">Every corner of the site, in one place.</p>
  </div>
</section>

<section class="mx-auto max-w-[96rem] px-6 py-14">
  <div class="grid gap-10 sm:grid-cols-2">

    <!-- Static pages -->
    <div>
      <h2 class="kicker border-b border-line pb-3 text-accent">Pages</h2>
      <ul class="mt-4 space-y-2.5 text-sm">
        <?php foreach ($staticPages as $label => $path): ?>
          <li>
            <a href="<?= url($path) ?>" class="font-semibold text-ink-soft transition hover:text-accent"><?= e($label) ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- Categories -->
    <div>
      <h2 class="kicker border-b border-line pb-3 text-accent">Categories</h2>
      <ul class="mt-4 space-y-2.5 text-sm">
        <?php foreach ($categories as $cat): ?>
          <li>
            <a href="<?= url('/category/' . $cat['slug']) ?>" class="font-semibold text-ink-soft transition hover:text-accent">
              <?= e($cat['name']) ?>
              <span class="ml-1 text-xs text-muted">(<?= (int) $cat['post_count'] ?>)</span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <!-- Articles -->
  <?php if ($recent): ?>
    <div class="mt-12">
      <h2 class="kicker border-b border-line pb-3 text-accent">Latest articles</h2>
      <ul class="mt-4 grid gap-2.5 text-sm sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($recent as $post): ?>
          <li>
            <a href="<?= url('/post/' . $post['slug']) ?>" class="font-semibold text-ink-soft transition hover:text-accent">
              <?= e($post['title']) ?>
            </a>
            <span class="block text-xs text-muted"><?= e(time_ago($post['published_at'])) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="mt-12 rounded-2xl border border-line bg-ink p-7 text-paper">
    <p class="kicker text-accent">XML sitemap</p>
    <p class="mt-2 text-sm text-paper/70">
      For search engines, submit <code class="rounded bg-paper/10 px-1.5 py-0.5">/sitemap.xml</code> to Google Search Console.
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
