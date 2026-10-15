<?php
/**
 *  Homepage — editorial hero + trending + latest + stats
 */

require_once __DIR__ . '/includes/functions.php';

$featured   = get_featured_post();
$latest     = get_posts(1, 9);
$total      = count_posts();
$categories = get_categories();
$tags       = get_popular_tags(12);

// trending = most viewed published posts (excluding featured)
$trending = db()->query(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug
     FROM posts p LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.status = 'published'" . ($featured ? ' AND p.id != ' . (int) $featured['id'] : '') . "
     ORDER BY p.views DESC, p.published_at DESC LIMIT 4"
)->fetchAll();

$stats = [
    'posts'      => count_posts(),
    'categories' => count($categories),
    'comments'   => (int) db()->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'")->fetchColumn(),
    'writers'    => (int) db()->query('SELECT COUNT(*) FROM users WHERE status = 1')->fetchColumn(),
];

require __DIR__ . '/includes/header.php';
?>

<!-- ================= HERO ================= -->
<?php if ($featured): ?>
<section class="relative border-b border-line">
  <div class="mx-auto grid max-w-[96rem] gap-12 px-6 py-14 lg:grid-cols-[1.15fr_1fr] lg:py-20">

    <div class="reveal reveal-1 flex flex-col justify-center">
      <div class="flex items-center gap-3">
        <span class="kicker bg-accent px-2.5 py-1 text-white">★ Featured</span>
        <?php if ($featured['category_name']): ?>
          <a href="<?= url('/category/' . $featured['category_slug']) ?>" class="kicker text-primary transition hover:text-accent">
            <?= e($featured['category_name']) ?>
          </a>
        <?php endif; ?>
        <span class="hidden h-px flex-1 bg-line sm:block"></span>
        <span class="hidden text-xs font-bold tracking-wide text-muted sm:block">
          <?= e(date('M j, Y', strtotime($featured['published_at']))) ?>
        </span>
      </div>

      <h1 class="headline-xl mt-6">
        <a href="<?= url('/post/' . $featured['slug']) ?>" class="transition hover:text-primary">
          <?= e($featured['title']) ?>
        </a>
      </h1>

      <p class="mt-6 max-w-xl text-lg leading-relaxed text-muted">
        <?= e($featured['excerpt'] ?: excerpt_text($featured['content'], 180)) ?>
      </p>

      <div class="mt-8 flex flex-wrap items-center gap-4 text-sm text-muted">
        <a href="<?= url('/author/' . $featured['author_slug']) ?>" class="group flex items-center gap-2.5">
          <?= author_avatar_img($featured['author_name'], $featured['author_avatar'] ?? null, 'h-9 w-9 transition group-hover:ring-2 group-hover:ring-accent') ?>
          <span class="font-bold text-ink transition group-hover:text-accent"><?= e($featured['author_name']) ?></span>
        </a>
        <span class="h-1 w-1 rounded-full bg-line"></span>
        <span><?= reading_time($featured['content']) ?> min read</span>
        <span class="h-1 w-1 rounded-full bg-line"></span>
        <span><?= number_format((int) $featured['views']) ?> reads</span>
      </div>

      <div class="mt-9">
        <a href="<?= url('/post/' . $featured['slug']) ?>"
           class="group inline-flex items-center gap-2.5 rounded-lg bg-ink px-7 py-3.5 text-sm font-bold text-paper transition hover:bg-accent">
          Read the story
          <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
        </a>
      </div>
    </div>

    <div class="reveal reveal-2 relative flex items-center">
      <!-- decorative offset frame -->
      <div class="absolute -left-3 -top-3 hidden h-full w-full rounded-2xl border border-line sm:block"></div>
      <a href="<?= url('/post/' . $featured['slug']) ?>" class="group relative block w-full overflow-hidden rounded-2xl border border-line bg-surface shadow-[0_30px_60px_-40px_rgba(16,19,25,.5)]">
        <img src="<?= e(post_image($featured)) ?>" alt="<?= e($featured['title']) ?>"
             class="h-72 w-full object-cover transition-transform duration-700 group-hover:scale-[1.04] lg:h-[430px]">
        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-6 pt-16">
          <p class="kicker text-white/80">Editor's pick</p>
          <p class="mt-1.5 font-display text-xl font-semibold text-white"><?= e(mb_substr($featured['title'], 0, 60)) ?>…</p>
        </div>
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ================= TRENDING ================= -->
<?php if ($trending): ?>
<section class="mx-auto max-w-[96rem] px-6 py-16">
  <div class="section-head" data-reveal>
    <div>
      <p class="kicker text-accent">Most read</p>
      <h2 class="headline-lg mt-2">Trending this week</h2>
    </div>
    <a href="<?= url('/blog') ?>" class="more hidden sm:inline-flex">All articles
      <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
    </a>
  </div>

  <div class="mt-10 grid gap-x-10 gap-y-12 sm:grid-cols-2 lg:grid-cols-4">
    <?php foreach ($trending as $i => $t): ?>
      <a href="<?= url('/post/' . $t['slug']) ?>" class="group flex gap-4" data-reveal style="--reveal-delay: <?= $i * 0.08 ?>s">
        <span class="rank-badge shrink-0"><?= $i + 1 ?></span>
        <div class="min-w-0">
          <?php if ($t['category_name']): ?>
            <span class="kicker text-primary"><?= e($t['category_name']) ?></span>
          <?php endif; ?>
          <h3 class="mt-1.5 font-display text-lg font-semibold leading-snug transition group-hover:text-accent">
            <?= e($t['title']) ?>
          </h3>
          <p class="mt-2 text-xs font-semibold text-muted">
            <?= number_format((int) $t['views']) ?> reads · <?= reading_time($t['content']) ?> min
          </p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ================= LATEST POSTS ================= -->
<section class="border-t border-line bg-surface">
  <div class="mx-auto max-w-[96rem] px-6 py-16">
    <div class="section-head" data-reveal>
      <div>
        <p class="kicker text-accent">Fresh from the desk</p>
        <h2 class="headline-lg mt-2">Latest articles</h2>
      </div>
      <a href="<?= url('/blog') ?>" class="more hidden sm:inline-flex">View all <?= $total ?>
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
      </a>
    </div>

    <?php if (!$latest): ?>
      <div class="mt-12 rounded-2xl border border-dashed border-line py-20 text-center">
        <p class="font-display text-2xl text-muted">No articles published yet.</p>
        <p class="mt-2 text-sm text-muted">Publish your first post from the <a href="<?= url('/admin') ?>" class="font-semibold text-primary">admin panel</a>.</p>
      </div>
    <?php else: ?>
      <!-- lead post (first) spans wide -->
      <?php $lead = $latest[0]; ?>
      <div class="mt-10 grid gap-8 lg:grid-cols-[1.4fr_1fr]">
        <a href="<?= url('/post/' . $lead['slug']) ?>" class="group block" data-reveal>
          <div class="zoom-frame rounded-2xl border border-line bg-paper">
            <img src="<?= e(post_image($lead)) ?>" alt="<?= e($lead['title']) ?>" class="h-72 w-full object-cover lg:h-[400px]">
          </div>
          <div class="mt-5 flex items-center gap-3">
            <?php if ($lead['category_name']): ?>
              <span class="kicker text-primary"><?= e($lead['category_name']) ?></span>
            <?php endif; ?>
            <span class="h-1 w-1 rounded-full bg-line"></span>
            <span class="text-xs text-muted"><?= e(time_ago($lead['published_at'])) ?></span>
          </div>
          <h3 class="headline-md mt-3 transition group-hover:text-primary"><?= e($lead['title']) ?></h3>
          <p class="mt-3 max-w-xl text-[0.95rem] leading-relaxed text-muted">
            <?= e($lead['excerpt'] ?: excerpt_text($lead['content'], 160)) ?>
          </p>
        </a>

        <!-- side list of next 3 -->
        <div class="flex flex-col justify-between gap-6 divide-y divide-line border-t border-line pt-6 lg:border-l lg:border-t-0 lg:pl-8 lg:pt-0">
          <?php foreach (array_slice($latest, 1, 3) as $side): ?>
            <a href="<?= url('/post/' . $side['slug']) ?>" class="group flex gap-4 <?= 'first:pt-0' ?>">
              <div class="zoom-frame h-20 w-28 shrink-0 overflow-hidden rounded-xl border border-line bg-paper">
                <img src="<?= e(post_image($side)) ?>" alt="" class="h-full w-full object-cover">
              </div>
              <div class="min-w-0">
                <?php if ($side['category_name']): ?>
                  <span class="kicker text-primary"><?= e($side['category_name']) ?></span>
                <?php endif; ?>
                <h4 class="mt-1 font-display text-[1.05rem] font-semibold leading-snug transition group-hover:text-accent">
                  <?= e($side['title']) ?>
                </h4>
                <p class="mt-1.5 text-xs text-muted"><?= reading_time($side['content']) ?> min · <?= e(time_ago($side['published_at'])) ?></p>
              </div>
            </a>
          <?php endforeach; ?>
          <a href="<?= url('/blog') ?>" class="group flex items-center justify-between pt-2">
            <span class="font-display text-lg font-semibold transition group-hover:text-accent">Browse the full library</span>
            <svg class="h-5 w-5 text-muted transition group-hover:translate-x-1 group-hover:text-accent" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
          </a>
        </div>
      </div>

      <!-- rest of grid -->
      <?php $rest = array_slice($latest, 4); if ($rest): ?>
        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <?php foreach ($rest as $i => $post): ?>
            <?php require __DIR__ . '/includes/post-card.php'; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="mt-14 text-center sm:hidden">
        <a href="<?= url('/blog') ?>" class="inline-block rounded-lg bg-ink px-7 py-3.5 text-sm font-bold text-paper transition hover:bg-accent">View all posts</a>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ================= STATS STRIP ================= -->
<section class="border-y border-line">
  <div class="mx-auto grid max-w-[96rem] grid-cols-2 divide-line px-6 py-12 sm:grid-cols-4 sm:divide-x">
    <?php
    $statItems = [
        [$stats['posts'], 'Articles published'],
        [$stats['categories'], 'Expert topics'],
        [$stats['comments'], 'Community comments'],
        [$stats['writers'], 'Writers on board'],
    ];
    foreach ($statItems as $i => [$num, $label]): ?>
      <div class="px-4 py-4 text-center <?= $i < 2 ? 'border-b border-line sm:border-b-0' : '' ?>" data-reveal style="--reveal-delay: <?= $i * 0.07 ?>s">
        <p class="font-display text-4xl font-bold tracking-tight text-ink sm:text-5xl"><span data-count="<?= (int) $num ?>"><?= (int) $num ?></span>+</p>
        <p class="kicker mt-2 text-muted"><?= e($label) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ================= NEWSLETTER ================= -->
<section class="relative overflow-hidden border-b border-line bg-ink text-paper grain">
  <div class="relative z-10 mx-auto flex max-w-[96rem] flex-col items-center gap-8 px-6 py-18 text-center lg:flex-row lg:justify-between lg:py-20 lg:text-left">
    <div>
      <p class="kicker text-accent">Stay in the loop</p>
      <h2 class="headline-lg mt-3 text-paper">The best articles,<br>straight to your inbox.</h2>
      <p class="mt-3 text-sm text-paper/60">One thoughtful email a week. No spam, unsubscribe anytime.</p>
    </div>
    <form action="<?= url('/contact') ?>" method="get" class="flex w-full max-w-md flex-col gap-2.5 sm:flex-row">
      <input type="hidden" name="newsletter" value="1">
      <input type="email" name="email" required placeholder="you@example.com"
             class="w-full rounded-lg border border-paper/20 bg-paper/10 px-4 py-3.5 text-sm text-paper placeholder-paper/40 outline-none transition focus:border-accent">
      <button class="shrink-0 rounded-lg bg-accent px-6 py-3.5 text-sm font-bold text-white transition hover:bg-white hover:text-ink">
        Subscribe
      </button>
    </form>
  </div>
</section>

<!-- ================= TRENDING TAGS ================= -->
<?php if ($tags): ?>
<section class="mx-auto max-w-[96rem] px-6 py-14">
  <p class="rule-label kicker text-muted" data-reveal>Trending tags</p>
  <div class="mt-6 flex flex-wrap justify-center gap-2.5" data-reveal>
    <?php foreach ($tags as $tag): ?>
      <a href="<?= url('/tag/' . $tag['slug']) ?>"
         class="rounded-md border border-line bg-surface px-3.5 py-1.5 text-sm font-bold text-ink-soft transition hover:border-accent hover:bg-accent hover:text-white">
        #<?= e($tag['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
