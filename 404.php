<?php
/**
 *  404 Not Found  (also routed by router.php / .htaccess)
 */

require_once __DIR__ . '/includes/functions.php';

http_response_code(404);

$page_title = 'Page Not Found (404) | ' . setting('site_name', SITE_NAME);
$page_desc  = 'The page you are looking for does not exist.';
require __DIR__ . '/includes/header.php';
?>

<section class="mx-auto max-w-3xl px-6 py-24 text-center">
  <p class="kicker text-accent reveal reveal-1">Error 404</p>
  <h1 class="headline-xl mt-4 reveal reveal-2">This page went off the grid.</h1>
  <p class="mx-auto mt-5 max-w-xl text-muted reveal reveal-3">
    The link may be broken, or the article may have been moved or unpublished.
    Try searching, or head back to the front page.
  </p>

  <form action="<?= url('/search') ?>" method="get" class="mx-auto mt-9 max-w-md reveal reveal-3">
    <div class="relative">
      <input type="search" name="q" placeholder="Search articles…"
             class="w-full rounded-xl border border-line bg-surface py-3.5 pl-5 pr-28 text-sm outline-none transition focus:border-primary">
      <button class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg bg-ink px-4 py-2 text-xs font-bold text-paper transition hover:bg-accent">
        Search
      </button>
    </div>
  </form>

  <div class="mt-8 flex flex-wrap justify-center gap-3 reveal reveal-4">
    <a href="<?= url('/') ?>" class="rounded-lg bg-ink px-6 py-3 text-sm font-bold text-paper transition hover:bg-accent">Back to home</a>
    <a href="<?= url('/blog') ?>" class="rounded-lg border border-line bg-surface px-6 py-3 text-sm font-bold transition hover:border-ink">Browse articles</a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
