<?php
/**
 *  Single blog post  —  /post/{slug}
 */

require_once __DIR__ . '/includes/functions.php';

/* ---------------------------------------------------------------
 *  Handle comment submission (POST → redirect = Post/Redirect/Get)
 * --------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_comment') {
    csrf_verify();

    $postId   = (int) ($_POST['post_id'] ?? 0);
    $name     = trim((string) ($_POST['name'] ?? ''));
    $email    = trim((string) ($_POST['email'] ?? ''));
    $content  = trim((string) ($_POST['content'] ?? ''));
    $website  = trim((string) ($_POST['website'] ?? ''));
    $redirect = !empty($postId) ? db()->prepare('SELECT slug, allow_comments FROM posts WHERE id = ? AND status = "published"') : null;
    $redirect?->execute([$postId]);
    $target = $redirect?->fetch();

    $errors = [];
    if (!$target)                             $errors[] = 'Invalid post.';
    if ($name === '' || mb_strlen($name) > 80)  $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if ($content === '' || mb_strlen($content) > 3000) $errors[] = 'Comment is required.';
    if ($website !== '')                      $errors[] = 'Invalid submission.'; // honeypot
    if (setting('comments_moderation', '1') === '1' && strlen($content) < 3) $errors[] = 'Comment too short.';

    // Simple rate limit: max 3 comments per session per 10 minutes
    $_SESSION['cmts'] = array_values(array_filter($_SESSION['cmts'] ?? [], fn($t) => $t > time() - 600));
    if (count($_SESSION['cmts']) >= 3) {
        $errors[] = 'You are commenting too quickly. Please wait a few minutes.';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO comments (post_id, name, email, website, content, ip_address, status)
             VALUES (?, ?, ?, ?, ?, INET6_ATON(?), ?)'
        );
        $moderate = setting('comments_moderation', '1') === '1' ? 'pending' : 'approved';
        $stmt->execute([
            $postId, $name, $email,
            $website !== '' ? $website : null,
            $content,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $moderate,
        ]);
        $_SESSION['cmts'][]      = time();
        $_SESSION['comment_name']  = $name;
        $_SESSION['comment_email'] = $email;

        flash_set('success', $moderate === 'pending'
            ? 'Comment submitted — it will appear once approved.'
            : 'Comment posted!');
        header('Location: ' . url('/post/' . $target['slug']) . '#comments');
        exit;
    }

    // Back to the article with errors
    flash_set('error', implode(' ', $errors));
    header('Location: ' . url('/post/' . ($target['slug'] ?? '')) . '#comment-form');
    exit;
}

$slug = $_GET['slug'] ?? '';
$post = $slug !== '' ? get_post_by_slug($slug) : null;

if (!$post) {
    http_response_code(404);
    $page_title = 'Post Not Found | ' . setting('site_name', SITE_NAME);
    require __DIR__ . '/includes/header.php';
    echo '<section class="mx-auto max-w-3xl px-6 py-28 text-center">
            <p class="kicker text-accent">404</p>
            <h1 class="headline-xl mt-3">Article not found</h1>
            <p class="mt-4 text-muted">The post you are looking for does not exist or has been unpublished.</p>
            <a href="' . url('/blog') . '" class="mt-8 inline-block rounded-lg bg-ink px-6 py-3 text-sm font-bold text-paper transition hover:bg-accent">Browse all articles</a>
          </section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

track_post_view((int) $post['id']); // accurate view (bots/prefetch/refresh filtered)

$tags        = get_post_tags((int) $post['id']);
$comments    = get_post_comments((int) $post['id']);
$related     = get_related_posts($post, 4);

// Adjacent posts (published, by id) for prev/next navigation
$adjStmt = db()->prepare(
    "SELECT title, slug FROM posts
     WHERE status = 'published' AND id < ?
     ORDER BY id DESC LIMIT 1"
);
$adjStmt->execute([(int) $post['id']]);
$prev = $adjStmt->fetch() ?: null;
$adjStmt = db()->prepare(
    "SELECT title, slug FROM posts
     WHERE status = 'published' AND id > ?
     ORDER BY id ASC LIMIT 1"
);
$adjStmt->execute([(int) $post['id']]);
$next = $adjStmt->fetch() ?: null;
$commentsOn  = setting('allow_comments', '1') === '1' && (int) $post['allow_comments'] === 1;

/* --- Body processing: contextual internal links + stable heading IDs ---- */
/* Writer-authored internal links are stored root-relative ("/post/x"); make
   them absolute at render time so they survive subdirectory deployments
   (cPanel add-on domains, local vhosts) — stored content stays untouched. */
$contentHtml = preg_replace_callback(
    '#\bhref="(/(?:post|category|tag|author)/[^"]+)"#',
    static fn(array $m): string => 'href="' . url($m[1]) . '"',
    (string) $post['content']
);
$contentHtml = auto_internal_links($contentHtml, (int) $post['id']);
[$contentHtml, $toc] = prepare_article_html($contentHtml);
$contentHtml = wrap_scrollable_tables($contentHtml);
$showToc     = should_render_toc($toc, $post['content']);
$readMins    = reading_time($post['content']);          // computed from actual text
$wordCount   = str_word_count(strip_tags($post['content']));
$modified    = $post['updated_at'] ?: $post['published_at'];
$wasUpdated  = $post['published_at'] && $modified
    && (strtotime($modified) - strtotime($post['published_at'])) > 86400;

/* --- Editorial brief: public references list ---------------------------- */
$briefStmt = db()->prepare('SELECT references_text FROM editorial_briefs WHERE post_id = ?');
$briefStmt->execute([(int) $post['id']]);
$references = parse_reference_lines($briefStmt->fetchColumn() ?: null);

$page_title = ($post['meta_title'] ?: $post['title']) . ' | ' . setting('site_name', SITE_NAME);
$page_desc  = $post['meta_desc'] ?: ($post['excerpt'] ?: excerpt_text($post['content'], 160));
$og_type    = 'article';
$og_image   = normalize_og_image(post_image($post));
$coverAlt   = trim((string) ($post['cover_alt'] ?? '')) !== ''
    ? (string) $post['cover_alt']
    : (($post['category_name'] ?? '') !== ''
        ? $post['category_name'] . ' topic visual for “' . $post['title'] . '”'
        : 'Cover illustration for “' . $post['title'] . '”');

/* --- SEO: article OG extras + JSON-LD (Article + Breadcrumb) ---------- */
$GLOBALS['seo_og_article'] = [
    'published_time' => $post['published_at'],
    'modified_time'  => $modified,
    'section'        => $post['category_name'] ?? 'Blog',
    'tags'           => array_column($tags, 'name'),
];
seo_jsonld_add([
    '@context' => 'https://schema.org',
    '@type'    => 'Article',
    'headline' => $post['title'],
    'description' => $page_desc,
    'image'    => [$og_image],
    'inLanguage' => 'en',
    'author'   => [
        '@type' => 'Person',
        'name'  => $post['author_name'],
        'url'   => SITE_URL . '/author/' . $post['author_slug'],
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name'  => setting('site_name', SITE_NAME),
        'url'   => rtrim(SITE_URL, '/'),
        'logo'  => [
            '@type' => 'ImageObject',
            'url'   => site_logo_src() ?: url('/assets/img/favicon.svg'),
        ],
    ],
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => url('/post/' . $post['slug'])],
    'datePublished'    => iso8601($post['published_at']),
    'dateModified'     => iso8601($modified),
    'wordCount'        => $wordCount,
    'timeRequired'     => 'PT' . $readMins . 'M',
]);
$crumbs = ['Home' => '/'];
if (!empty($post['category_id'])) {
    // Full ancestor chain — Home > Parent > Subcategory > Post (richer BreadcrumbList)
    $_catIds = array_reverse(category_ancestry((int) $post['category_id']));
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
}
$crumbs[$post['title']] = '/post/' . $post['slug'];
seo_jsonld_breadcrumb($crumbs);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/breadcrumb.php';
?>

<!-- reading progress — gradient scroll line -->
<div class="pointer-events-none fixed inset-x-0 top-0 z-[60] h-[3px] bg-transparent">
  <div id="reading-progress" class="h-full w-0 rounded-r-full bg-gradient-to-r from-accent via-gold to-primary shadow-[0_0_10px_rgba(232,64,42,.5)] transition-[width] duration-150"></div>
</div>

<article>
  <!-- ================= ARTICLE HEADER ================= -->
  <header class="border-b border-line">
    <div class="mx-auto max-w-[96rem] px-6 pt-14 pb-10">
      <div class="flex flex-wrap items-center gap-3">
        <?php if ($post['category_name']): ?>
          <a href="<?= url('/category/' . $post['category_slug']) ?>" class="kicker bg-accent px-2.5 py-1 text-white transition hover:bg-ink">
            <?= e($post['category_name']) ?>
          </a>
        <?php endif; ?>
        <time datetime="<?= e($post['published_at']) ?>" class="text-xs font-semibold text-muted">
          <?= e(date('F j, Y', strtotime($post['published_at']))) ?>
        </time>
        <?php if ($wasUpdated): ?>
          <span class="h-1 w-1 rounded-full bg-line"></span>
          <time datetime="<?= e($modified) ?>" class="text-xs font-semibold text-accent" title="Last updated">
            Updated <?= e(date('M j, Y', strtotime($modified))) ?>
          </time>
        <?php endif; ?>
        <span class="h-1 w-1 rounded-full bg-line"></span>
        <span class="text-xs font-semibold text-muted"><?= $readMins ?> min read</span>
        <span class="h-1 w-1 rounded-full bg-line"></span>
        <span class="text-xs font-semibold text-muted"><?= number_format((int) $post['views']) ?> reads</span>
      </div>

      <h1 class="headline-xl mt-6"><?= e($post['title']) ?></h1>

      <?php if ($post['excerpt']): ?>
        <p class="mt-5 max-w-4xl text-lg leading-relaxed text-muted"><?= e($post['excerpt']) ?></p>
      <?php endif; ?>

      <!-- Author row -->
      <div class="mt-8 flex items-center justify-between gap-4 border-t border-line pt-6">
        <a href="<?= url('/author/' . $post['author_slug']) ?>" class="group flex items-center gap-3.5">
          <?= author_avatar_img($post['author_name'], $post['author_avatar'] ?? null, 'h-11 w-11 transition group-hover:ring-2 group-hover:ring-accent') ?>
          <span>
            <span class="block text-sm font-bold transition group-hover:text-accent"><?= e($post['author_name']) ?></span>
            <span class="block text-xs text-muted">Author</span>
          </span>
        </a>

        <div class="flex gap-2 text-xs font-semibold">
          <a href="https://twitter.com/intent/tweet?text=<?= urlencode($post['title']) ?>&url=<?= urlencode(url('/post/' . $post['slug'])) ?>"
             target="_blank" rel="noopener"
             class="rounded-lg border border-line px-3 py-2 transition hover:border-ink hover:bg-ink hover:text-paper">Share</a>
          <button data-copy-link class="cursor-pointer rounded-lg border border-line px-3 py-2 transition hover:border-ink hover:bg-ink hover:text-paper">Copy link</button>
        </div>
      </div>
    </div>
  </header>

  <!-- ================= COVER IMAGE (always shown) ================= -->
  <div class="mx-auto max-w-[96rem] px-6 pt-10">
    <div class="zoom-frame overflow-hidden rounded-2xl border border-line">
      <img src="<?= e($og_image) ?>" alt="<?= e($coverAlt) ?>" width="1200" height="630"
           fetchpriority="high" decoding="async"
           class="h-64 w-full object-cover sm:h-80 lg:h-[440px]">
    </div>
  </div>

  <!-- ================= CONTENT + STICKY SHARE RAIL ================= -->
  <div class="mx-auto grid max-w-[96rem] gap-8 px-6 py-12 lg:grid-cols-[auto_1fr]">

    <!-- sticky share rail (desktop) -->
    <aside class="hidden lg:block">
      <div class="sticky top-28 flex flex-col items-center gap-2.5">
        <span class="kicker mb-1 rotate-0 text-muted [writing-mode:vertical-rl]">Share</span>
        <a href="https://twitter.com/intent/tweet?text=<?= urlencode($post['title']) ?>&url=<?= urlencode(url('/post/' . $post['slug'])) ?>"
           target="_blank" rel="noopener" aria-label="Share on X"
           class="flex h-10 w-10 items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:border-ink hover:bg-ink hover:text-paper">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 8.2c-.5.3-1.1.5-1.7.6.6-.4 1.1-1 1.3-1.7-.6.4-1.2.6-1.9.8A3 3 0 0 0 12 10.4c0 .2 0 .5.1.7-2.5-.1-4.7-1.3-6.2-3.2-.3.5-.4 1-.4 1.6 0 1 .5 1.9 1.3 2.4-.5 0-1-.1-1.4-.4v.1c0 1.5 1 2.7 2.4 3-.3.1-.5.1-.8.1h-.6c.4 1.2 1.5 2.1 2.8 2.1A6 6 0 0 1 5 17.6a8.5 8.5 0 0 0 4.6 1.3c5.5 0 8.5-4.6 8.5-8.5v-.4c.6-.4 1.1-1 1.5-1.6Z"/></svg>
        </a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(url('/post/' . $post['slug'])) ?>"
           target="_blank" rel="noopener" aria-label="Share on Facebook"
           class="flex h-10 w-10 items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:border-ink hover:bg-ink hover:text-paper">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 9H16V6h-2.5C11.6 6 10 7.6 10 9.5V11H8v3h2v7h3v-7h2.2l.5-3H13v-1.2c0-.5.2-.8.5-.8Z"/></svg>
        </a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode(url('/post/' . $post['slug'])) ?>"
           target="_blank" rel="noopener" aria-label="Share on LinkedIn"
           class="flex h-10 w-10 items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:border-ink hover:bg-ink hover:text-paper">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M8 10H5v9h3v-9ZM6.5 5.5A1.75 1.75 0 1 0 6.5 9a1.75 1.75 0 0 0 0-3.5ZM19 19v-5.2c0-2.6-1.4-3.8-3.2-3.8-1.5 0-2.2.8-2.6 1.4V10H10v9h3.2v-5c0-1.3.6-2.1 1.8-2.1s1.7.8 1.7 2.1V19H19Z"/></svg>
        </a>
        <button data-copy-link aria-label="Copy link"
           class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:border-accent hover:bg-accent hover:text-white">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m9.182-3.493 4.5-4.5a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
        </button>
      </div>
    </aside>

    <div class="min-w-0">
      <?php if ($showToc): ?>
        <!-- Table of contents — generated from the article's own headings -->
        <nav aria-label="Table of contents" class="mb-9 rounded-2xl border border-line bg-surface p-5">
          <p class="kicker text-accent">In this article</p>
          <ol class="mt-3 grid gap-x-8 gap-y-1.5 text-sm sm:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($toc as $entry): ?>
              <li class="<?= $entry['level'] === 3 ? 'ml-5' : '' ?>">
                <a href="#<?= e($entry['id']) ?>"
                   class="text-ink-soft transition hover:text-primary">
                  <?= e($entry['text']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ol>
        </nav>
      <?php endif; ?>

      <div class="prose-ed">
        <?= $contentHtml /* sanitised on save; internal links + heading IDs added at render */ ?>
      </div>

      <!-- Tags -->
      <?php if ($tags): ?>
        <div class="mt-12 flex flex-wrap items-center gap-2">
          <span class="kicker mr-1 text-muted">Filed under</span>
          <?php foreach ($tags as $tag): ?>
            <a href="<?= url('/tag/' . $tag['slug']) ?>"
               class="rounded-md border border-line bg-surface px-3 py-1.5 text-sm font-medium transition hover:border-accent hover:bg-accent hover:text-white">
              #<?= e($tag['name']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Prev / Next navigation -->
      <?php if ($prev || $next): ?>
        <nav class="mt-12 grid gap-4 border-t border-line pt-8 sm:grid-cols-2">
          <?php if ($prev): ?>
            <a href="<?= url('/post/' . $prev['slug']) ?>" class="group rounded-xl border border-line bg-surface p-5 transition hover:border-ink">
              <span class="kicker text-muted">← Previous</span>
              <p class="mt-2 font-display text-[1.05rem] font-semibold leading-snug transition group-hover:text-accent"><?= e($prev['title']) ?></p>
            </a>
          <?php else: ?><span></span><?php endif; ?>
          <?php if ($next): ?>
            <a href="<?= url('/post/' . $next['slug']) ?>" class="group rounded-xl border border-line bg-surface p-5 text-right transition hover:border-ink sm:col-start-2">
              <span class="kicker text-muted">Next →</span>
              <p class="mt-2 font-display text-[1.05rem] font-semibold leading-snug transition group-hover:text-accent"><?= e($next['title']) ?></p>
            </a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>

      <?php if ($references): ?>
        <!-- References & further reading (editor-supplied, URL-validated) -->
        <section class="mt-12 border-t border-line pt-8" aria-label="References">
          <h2 class="font-display text-xl font-bold">References &amp; further reading</h2>
          <ul class="mt-4 space-y-2.5">
            <?php foreach ($references as $ref): ?>
              <li class="text-sm leading-relaxed">
                <a href="<?= e($ref['url']) ?>" rel="external noopener"
                   class="font-semibold text-primary underline decoration-primary/30 underline-offset-2 transition hover:text-accent hover:decoration-accent">
                  <?= e($ref['label']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>
    </div>
  </div>
</article>

<!-- ================= AUTHOR BOX ================= -->
<div class="mx-auto max-w-[96rem] px-6 pb-14">
  <div class="rounded-2xl border border-line bg-surface p-7">
    <p class="kicker text-accent">Written by</p>
    <div class="mt-4 flex items-start gap-4">
      <?= author_avatar_img($post['author_name'], $post['author_avatar'] ?? null, 'h-16 w-16 shrink-0') ?>
      <div>
        <a href="<?= url('/author/' . $post['author_slug']) ?>" class="font-display text-lg font-semibold transition hover:text-accent">
          <?= e($post['author_name']) ?>
        </a>
        <?php if ($post['author_bio']): ?>
          <p class="mt-1 text-sm leading-relaxed text-muted"><?= e($post['author_bio']) ?></p>
        <?php endif; ?>
        <?php if ($post['author_website']): ?>
          <a href="<?= e($post['author_website']) ?>" target="_blank" rel="noopener" class="mt-2 inline-block text-sm font-semibold text-primary hover:text-accent">
            Visit website &rarr;
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ================= COMMENTS ================= -->
<?php if ($commentsOn): ?>
<section class="mx-auto max-w-[96rem] px-6 pb-16">
  <h2 class="headline-lg border-b border-line pb-4">
    Comments <span class="text-muted">(<?= count($comments) ?>)</span>
  </h2>

  <?php if ($comments): ?>
    <ul class="mt-8 space-y-7">
      <?php foreach ($comments as $c): ?>
        <li class="flex gap-4">
          <img src="<?= e(!empty($c['user_avatar']) ? _avatar_url($c['user_avatar']) : avatar_src($c['name'])) ?>" alt="<?= e($c['name']) ?>" width="40" height="40" loading="lazy" class="h-10 w-10 shrink-0 rounded-full object-cover">
          <div class="flex-1">
            <div class="flex items-baseline gap-3">
              <span class="text-sm font-bold"><?= e($c['name']) ?></span>
              <time class="text-xs text-muted" datetime="<?= e($c['created_at']) ?>"><?= e(time_ago($c['created_at'])) ?></time>
            </div>
            <p class="mt-1.5 text-[0.95rem] leading-relaxed text-ink-soft"><?= nl2br(e($c['content'])) ?></p>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="mt-6 text-sm text-muted">No comments yet — be the first to share your thoughts.</p>
  <?php endif; ?>

  <!-- Comment form -->
  <div class="mt-10 rounded-2xl border border-line bg-surface p-7">
    <h3 class="font-display text-xl font-semibold">Leave a comment</h3>
    <p class="mt-1 text-xs text-muted">Comments are moderated and appear after approval.</p>

    <form method="post" action="<?= url('/post/' . $post['slug']) ?>" class="mt-5 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_comment">
      <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="kicker text-muted" for="c-name">Name *</label>
          <input id="c-name" name="name" required maxlength="80"
                 class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary"
                 value="<?= e($_SESSION['comment_name'] ?? '') ?>">
        </div>
        <div>
          <label class="kicker text-muted" for="c-email">Email * <span class="normal-case tracking-normal opacity-60">(never published)</span></label>
          <input id="c-email" type="email" name="email" required maxlength="190"
                 class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary"
                 value="<?= e($_SESSION['comment_email'] ?? '') ?>">
        </div>
      </div>

      <div>
        <label class="kicker text-muted" for="c-body">Comment *</label>
        <textarea id="c-body" name="content" required rows="5" maxlength="3000"
                  class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-3 text-sm outline-none focus:border-primary"></textarea>
      </div>

      <div class="flex items-center gap-3">
        <input type="text" name="website" placeholder="Website (optional)" class="hidden" aria-hidden="true" tabindex="-1">
        <button class="rounded-lg bg-ink px-6 py-3 text-sm font-bold text-paper transition hover:bg-accent">
          Submit comment
        </button>
        <span class="text-xs text-muted">By submitting you agree to our Terms.</span>
      </div>
    </form>
  </div>
</section>
<?php endif; ?>

<!-- ================= RELATED ================= -->
<?php if ($related): ?>
<section class="border-t border-line bg-surface">
  <div class="mx-auto max-w-[96rem] px-6 py-14">
    <div class="flex items-end justify-between border-b border-line pb-4">
      <h2 class="headline-lg">Keep reading</h2>
      <a href="<?= url('/blog') ?>" class="text-sm font-bold text-primary transition hover:text-accent">All articles &rarr;</a>
    </div>
    <div class="mt-9 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <?php foreach ($related as $ri => $r): $post = $r; $i = $ri; ?>
        <?php require __DIR__ . '/includes/post-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<button id="back-to-top" class="fixed bottom-6 right-6 z-40 hidden h-11 w-11 rounded-full bg-ink text-lg font-bold text-paper shadow-lg transition hover:bg-accent" aria-label="Back to top">&uarr;</button>

<?php require __DIR__ . '/includes/footer.php'; ?>
