<?php
/**
 *  Categories index  —  /categories
 */

require_once __DIR__ . '/includes/functions.php';

$categoryTree = get_category_tree(3, true);   // parents with nested subcategories (populated only)
$categories   = get_categories();      // for count / empty check

$page_title = 'All Categories | ' . setting('site_name', SITE_NAME);
$page_desc  = 'Browse every topic covered on ' . setting('site_name', SITE_NAME) . ' — AI, technology, data science, programming, careers and more, with a dedicated page for each subject.';

seo_jsonld_breadcrumb(['Home' => '/', 'Categories' => '/categories']);

require __DIR__ . '/includes/header.php';
$crumbs = ['Home' => '/', 'Categories' => '/categories'];
require __DIR__ . '/includes/breadcrumb.php';
?>

<section class="border-b border-line bg-surface">
  <div class="mx-auto max-w-[96rem] px-6 py-14">
    <p class="kicker text-accent reveal reveal-1">Browse by topic</p>
    <h1 class="headline-xl mt-3 reveal reveal-2">Categories</h1>
    <p class="mt-4 max-w-2xl text-muted reveal reveal-3">
      Pick a topic and dive in — every article we publish is filed under one of these.
    </p>
  </div>
</section>

<section class="mx-auto max-w-[96rem] px-6 py-14">
  <?php if (!$categories): ?>
    <div class="rounded-2xl border border-dashed border-line bg-surface py-20 text-center">
      <p class="font-display text-2xl text-muted">No categories yet.</p>
    </div>
  <?php else: ?>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <?php $idx = 0; foreach ($categoryTree as $cat): $idx++; ?>
        <a href="<?= url('/category/' . $cat['slug']) ?>"
           class="card-hover group flex flex-col overflow-hidden rounded-2xl border border-line bg-surface">
          <!-- topical cover photo (or illustration fallback) -->
          <div class="zoom-frame relative h-36 w-full overflow-hidden border-b border-line bg-ink">
            <?php $cover = category_cover_image($cat['slug']); ?>
            <img src="<?= e($cover ?: placeholder_image($cat['name'], $cat['slug'] . '|' . $cat['name'])) ?>"
                 alt="<?= e($cat['name']) ?> — topic" loading="lazy"
                 width="800" height="500" class="h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
            <span class="absolute left-4 top-4 rounded-full bg-black/45 px-3 py-1 text-[0.68rem] font-extrabold uppercase tracking-widest text-white backdrop-blur-sm">
              <?= str_pad((string) $idx, 2, '0', STR_PAD_LEFT) ?>
            </span>
          </div>

          <div class="flex flex-1 flex-col p-6">
            <span class="kicker text-primary"><?= (int) $cat['post_count'] ?> article<?= $cat['post_count'] !== 1 ? 's' : '' ?></span>
            <h2 class="mt-2.5 font-display text-xl font-semibold leading-snug transition group-hover:text-accent">
              <?= e($cat['name']) ?>
            </h2>
            <?php if ($cat['description']): ?>
              <p class="mt-2 flex-1 text-sm leading-relaxed text-muted"><?= e($cat['description']) ?></p>
            <?php endif; ?>

            <?php if ($cat['children']): ?>
              <div class="mt-4 flex flex-wrap gap-1.5">
                <?php foreach ($cat['children'] as $ch): ?>
                  <span class="rounded-full border border-line bg-paper px-2.5 py-1 text-[0.68rem] font-bold text-ink-soft"><?= e($ch['name']) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <span class="mt-5 inline-flex items-center gap-1.5 text-xs font-bold text-ink transition group-hover:gap-2.5 group-hover:text-accent">
              Explore
              <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
