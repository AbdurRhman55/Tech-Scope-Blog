<?php
/**
 *  About Us
 */

require_once __DIR__ . '/includes/functions.php';

$categories = get_categories();
$totalPosts = count_posts();
$authors     = db()->query("SELECT COUNT(*) FROM users WHERE status = 1")->fetchColumn();

$page_title = 'About Us | ' . setting('site_name', SITE_NAME);
$page_desc  = 'Learn about ' . setting('site_name') . ' — our mission, the topics we cover and the people behind the articles, plus what you will find on the blog and how to get started.';
require __DIR__ . '/includes/header.php';
?>

<section class="border-b border-line bg-surface">
  <div class="mx-auto max-w-4xl px-6 py-16 text-center">
    <p class="kicker text-accent reveal reveal-1">About us</p>
    <h1 class="headline-xl mt-4 reveal reveal-2">Ideas worth reading,<br>written with care.</h1>
    <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-muted reveal reveal-3">
      <?= e(setting('site_description')) ?>
    </p>
  </div>
</section>

<!-- Stats strip -->
<section class="border-b border-line">
  <div class="mx-auto grid max-w-4xl grid-cols-3 divide-x divide-line px-6 py-10 text-center">
    <div>
      <p class="font-display text-4xl font-bold text-ink"><?= (int) $totalPosts ?>+</p>
      <p class="kicker mt-1.5 text-muted">Articles</p>
    </div>
    <div>
      <p class="font-display text-4xl font-bold text-ink"><?= count($categories) ?></p>
      <p class="kicker mt-1.5 text-muted">Topics</p>
    </div>
    <div>
      <p class="font-display text-4xl font-bold text-ink"><?= (int) $authors ?></p>
      <p class="kicker mt-1.5 text-muted">Writers</p>
    </div>
  </div>
</section>

<!-- Mission -->
<section class="mx-auto max-w-4xl px-6 py-16">
  <div class="prose-ed">
    <h2>Our mission</h2>
    <p>
      <?= e(setting('site_name', SITE_NAME)) ?> exists to make complex ideas in
      <strong>artificial intelligence, technology, data science and research</strong> understandable
      for everyone — from curious students to working professionals.
    </p>
    <p>
      Every article is researched, reviewed and written to be genuinely useful: no clickbait,
      no filler, no outdated information. If it does not help you learn something or do
      something better, we do not publish it.
    </p>

    <h2>What we cover</h2>
    <ul>
      <?php foreach (array_filter($categories, static fn(array $c): bool => (int) $c['post_count'] > 0) as $cat): ?>
        <li>
          <a href="<?= url('/category/' . $cat['slug']) ?>"><?= e($cat['name']) ?></a>
          <?php if ($cat['description']): ?> — <?= e($cat['description']) ?><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>

    <h2>Editorial standards</h2>
    <ol>
      <li>Sources are verified and linked wherever possible.</li>
      <li>Corrections are issued transparently and promptly.</li>
      <li>Opinions are clearly separated from reported facts.</li>
      <li>Sponsored content, if any, is always disclosed.</li>
    </ol>

    <h2>Contact</h2>
    <p>
      Questions, feedback or a topic suggestion? Reach us through our
      <a href="<?= url('/contact') ?>">contact page</a>
      <?php if (setting('contact_email')): ?> or email <a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a><?php endif; ?>.
    </p>
  </div>
</section>

<!-- CTA -->
<section class="border-y border-line bg-ink text-paper">
  <div class="mx-auto flex max-w-4xl flex-col items-center gap-6 px-6 py-14 text-center">
    <h2 class="headline-lg text-paper">Ready to start reading?</h2>
    <div class="flex flex-wrap justify-center gap-3">
      <a href="<?= url('/blog') ?>" class="rounded-lg bg-accent px-6 py-3 text-sm font-bold text-white transition hover:bg-white hover:text-ink">Browse articles</a>
      <a href="<?= url('/contact') ?>" class="rounded-lg border border-paper/25 px-6 py-3 text-sm font-bold text-paper transition hover:border-paper">Get in touch</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
